<?php
/**
 * Webhook Culqi: confirma cargos / registra eventos (charge.succeeded, refund, etc.).
 *
 * URL: POST /wp-json/yuniorrojas/v1/culqi/webhook
 * Configura en panel Culqi la misma URL. Cabecera de firma opcional vía
 * opción o constante YUNIORROJAS_CULQI_WEBHOOK_SECRET.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Secreto de webhook (wp-config o settings).
 */
function jr_culqi_webhook_secret(): string
{
    if (defined('YUNIORROJAS_CULQI_WEBHOOK_SECRET') && (string) YUNIORROJAS_CULQI_WEBHOOK_SECRET !== '') {
        return (string) YUNIORROJAS_CULQI_WEBHOOK_SECRET;
    }
    $s = get_option('yuniorrojas_pagos_settings', array());
    if (is_array($s) && !empty($s['culqi_webhook_secret'])) {
        return (string) $s['culqi_webhook_secret'];
    }
    return '';
}

/**
 * Registra ruta REST.
 */
function jr_culqi_register_webhook_route(): void
{
    register_rest_route('yuniorrojas/v1', '/culqi/webhook', array(
        'methods'             => 'POST',
        'permission_callback' => '__return_true',
        'callback'            => 'jr_culqi_webhook_handle',
    ));
}
add_action('rest_api_init', 'jr_culqi_register_webhook_route');

/**
 * Procesa evento Culqi.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function jr_culqi_webhook_handle(WP_REST_Request $request)
{
    if (function_exists('yuniorrojas_rate_limit')) {
        $rl = yuniorrojas_rate_limit('culqi_webhook', 120, MINUTE_IN_SECONDS);
        if (is_wp_error($rl)) {
            return $rl;
        }
    }

    $secret = jr_culqi_webhook_secret();
    if ($secret !== '') {
        $sig = (string) $request->get_header('x-culqi-signature');
        if ($sig === '') {
            $sig = (string) $request->get_header('x-signature');
        }
        $raw = $request->get_body();
        $ok  = hash_equals($secret, $sig)
            || hash_equals(hash_hmac('sha256', $raw, $secret), $sig);
        if (!$ok) {
            return new WP_Error('webhook_sig', 'Firma de webhook no válida.', array('status' => 401));
        }
    }

    $payload = $request->get_json_params();
    if (!is_array($payload)) {
        $payload = json_decode($request->get_body(), true);
    }
    if (!is_array($payload)) {
        return new WP_Error('webhook_body', 'JSON inválido.', array('status' => 400));
    }

    $type = '';
    if (isset($payload['type'])) {
        $type = sanitize_key((string) $payload['type']);
    } elseif (isset($payload['event'])) {
        $type = sanitize_key((string) $payload['event']);
    }

    $data = isset($payload['data']) && is_array($payload['data'])
        ? $payload['data']
        : $payload;

    $charge_id = '';
    if (!empty($data['id']) && is_string($data['id']) && str_starts_with($data['id'], 'chr_')) {
        $charge_id = sanitize_text_field($data['id']);
    } elseif (!empty($data['charge_id'])) {
        $charge_id = sanitize_text_field((string) $data['charge_id']);
    }

    if (function_exists('error_log')) {
        error_log('[jr-core] Culqi webhook type=' . $type . ' charge=' . $charge_id);
    }

    if ($charge_id === '') {
        return new WP_REST_Response(array('ok' => true, 'ignored' => true), 200);
    }

    $post_id = 0;
    if (function_exists('jr_db_post_id_by_charge')) {
        $post_id = jr_db_post_id_by_charge($charge_id);
    }
    if ($post_id <= 0 && function_exists('yuniorrojas_reserva_meta_key')) {
        $q = new WP_Query(array(
            'post_type'      => defined('YUNIORROJAS_CPT_RESERVAS') ? YUNIORROJAS_CPT_RESERVAS : 'jr_reservas',
            'post_status'    => 'any',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_key'       => yuniorrojas_reserva_meta_key('culqi_charge_id'),
            'meta_value'     => $charge_id,
        ));
        if (!empty($q->posts[0])) {
            $post_id = (int) $q->posts[0];
        }
    }

    if ($post_id <= 0) {
        return new WP_REST_Response(array('ok' => true, 'matched' => false), 200);
    }

    $key = function_exists('yuniorrojas_reserva_meta_key')
        ? static fn (string $c) => yuniorrojas_reserva_meta_key($c)
        : static fn (string $c) => '_jr_reserva_' . $c;

    // charge success / capture
    if (
        str_contains($type, 'charge') && (str_contains($type, 'success') || str_contains($type, 'creat') || $type === '')
        || (!empty($data['outcome']['type']) && (string) $data['outcome']['type'] === 'venta_exitosa')
    ) {
        update_post_meta($post_id, $key('pago_verificado'), '1');
        update_post_meta($post_id, $key('pago_proveedor'), 'culqi');
        $estado = (string) get_post_meta($post_id, $key('estado'), true);
        if ($estado === 'pendiente') {
            update_post_meta($post_id, $key('estado'), 'confirmada');
        }
    }

    // refund
    if (str_contains($type, 'refund') || !empty($data['refund_id'])) {
        $rid = (string) ($data['id'] ?? $data['refund_id'] ?? '');
        if ($rid !== '' && str_starts_with($rid, 'ref_')) {
            update_post_meta($post_id, $key('culqi_refund_id'), $rid);
        }
        update_post_meta($post_id, $key('culqi_refund_at'), current_time('mysql'));
    }

    if (function_exists('jr_db_sync_reserva_from_post')) {
        jr_db_sync_reserva_from_post($post_id);
    }

    return new WP_REST_Response(array(
        'ok'      => true,
        'matched' => true,
        'post_id' => $post_id,
        'type'    => $type,
    ), 200);
}

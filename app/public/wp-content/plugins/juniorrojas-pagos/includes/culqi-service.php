<?php
/**
 * Integración Culqi (Perú): configuración y cargos únicos.
 *
 * Llaves (prioridad):
 * 1) Ajustes Culqi en WP-Admin (option yuniorrojas_pagos_settings)
 * 2) Opcional: constantes YUNIORROJAS_CULQI_* en wp-config.php (fallback)
 *
 * Nunca se envía la llave secreta al frontend.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Defaults de ajustes de pago.
 *
 * @return array{
 *     culqi_public_key:string,
 *     culqi_secret_key:string,
 *     yape_numero:string,
 *     yape_titular:string,
 *     yape_qr_id:int,
 *     banco_nombre:string,
 *     banco_cuenta:string,
 *     banco_cci:string,
 *     banco_titular:string
 * }
 */
function yuniorrojas_pagos_defaults(): array
{
    return array(
        'culqi_public_key'      => '',
        'culqi_secret_key'      => '',
        'culqi_webhook_secret'  => '',
        'yape_numero'           => '',
        'yape_titular'          => '',
        'yape_qr_id'            => 0,
        'banco_nombre'          => '',
        'banco_cuenta'          => '',
        'banco_cci'             => '',
        'banco_titular'         => '',
    );
}

/**
 * Si las option están vacías, importa SOLO desde constantes de wp-config (nunca llaves embebidas).
 */
function yuniorrojas_culqi_hidratar_llaves_si_vacio(): void
{
    static $ran = false;
    if ($ran) {
        return;
    }
    $ran = true;

    $saved = get_option('yuniorrojas_pagos_settings', array());
    if (!is_array($saved)) {
        $saved = array();
    }

    $public  = trim((string) ($saved['culqi_public_key'] ?? ''));
    $secret  = trim((string) ($saved['culqi_secret_key'] ?? ''));
    $changed = false;

    // Solo constantes de entorno / wp-config — nunca semillas test en código.
    if ($public === '' && defined('YUNIORROJAS_CULQI_PUBLIC_KEY') && (string) YUNIORROJAS_CULQI_PUBLIC_KEY !== '') {
        $saved['culqi_public_key'] = trim((string) YUNIORROJAS_CULQI_PUBLIC_KEY);
        $public  = $saved['culqi_public_key'];
        $changed = true;
    }
    if ($secret === '' && defined('YUNIORROJAS_CULQI_SECRET_KEY') && (string) YUNIORROJAS_CULQI_SECRET_KEY !== '') {
        $saved['culqi_secret_key'] = trim((string) YUNIORROJAS_CULQI_SECRET_KEY);
        $secret  = $saved['culqi_secret_key'];
        $changed = true;
    }

    // Limpieza: si en un deploy previo se importaron test keys hardcodeadas y el admin no las tocó,
    // no las reintroducimos; el admin debe pegar live/test desde el panel Culqi.

    if ($changed) {
        update_option('yuniorrojas_pagos_settings', $saved, false);
    }
}

/**
 * Lee settings de pagos.
 *
 * @return array{
 *     culqi_public_key:string,
 *     culqi_secret_key:string,
 *     yape_numero:string,
 *     yape_titular:string,
 *     yape_qr_id:int,
 *     banco_nombre:string,
 *     banco_cuenta:string,
 *     banco_cci:string,
 *     banco_titular:string
 * }
 */
function yuniorrojas_pagos_settings(): array
{
    yuniorrojas_culqi_hidratar_llaves_si_vacio();

    $defaults = yuniorrojas_pagos_defaults();
    $saved    = get_option('yuniorrojas_pagos_settings', array());
    if (!is_array($saved)) {
        $saved = array();
    }

    return array(
        'culqi_public_key'     => (string) ($saved['culqi_public_key'] ?? $defaults['culqi_public_key']),
        'culqi_secret_key'     => (string) ($saved['culqi_secret_key'] ?? $defaults['culqi_secret_key']),
        'culqi_webhook_secret' => (string) ($saved['culqi_webhook_secret'] ?? $defaults['culqi_webhook_secret']),
        'yape_numero'          => (string) ($saved['yape_numero'] ?? $defaults['yape_numero']),
        'yape_titular'         => (string) ($saved['yape_titular'] ?? $defaults['yape_titular']),
        'yape_qr_id'           => absint($saved['yape_qr_id'] ?? $defaults['yape_qr_id']),
        'banco_nombre'         => (string) ($saved['banco_nombre'] ?? $defaults['banco_nombre']),
        'banco_cuenta'         => (string) ($saved['banco_cuenta'] ?? $defaults['banco_cuenta']),
        'banco_cci'            => (string) ($saved['banco_cci'] ?? $defaults['banco_cci']),
        'banco_titular'        => (string) ($saved['banco_titular'] ?? $defaults['banco_titular']),
    );
}

/**
 * Llave pública Culqi: prioriza la option del admin; fallback a wp-config.
 */
function yuniorrojas_culqi_public_key(): string
{
    $settings = yuniorrojas_pagos_settings();
    $from_opt = trim((string) $settings['culqi_public_key']);
    if ($from_opt !== '') {
        return $from_opt;
    }

    if (defined('YUNIORROJAS_CULQI_PUBLIC_KEY') && (string) YUNIORROJAS_CULQI_PUBLIC_KEY !== '') {
        return (string) YUNIORROJAS_CULQI_PUBLIC_KEY;
    }

    return '';
}

/**
 * Llave privada Culqi (solo servidor). Prioriza admin; fallback wp-config.
 */
function yuniorrojas_culqi_secret_key(): string
{
    $settings = yuniorrojas_pagos_settings();
    $from_opt = trim((string) $settings['culqi_secret_key']);
    if ($from_opt !== '') {
        return $from_opt;
    }

    if (defined('YUNIORROJAS_CULQI_SECRET_KEY') && (string) YUNIORROJAS_CULQI_SECRET_KEY !== '') {
        return (string) YUNIORROJAS_CULQI_SECRET_KEY;
    }

    return '';
}

/**
 * ¿Culqi configurado para cobrar?
 */
function yuniorrojas_culqi_esta_configurado(): bool
{
    $pk = yuniorrojas_culqi_public_key();
    $sk = yuniorrojas_culqi_secret_key();

    return $pk !== '' && $sk !== ''
        && strpos($pk, 'pk_') === 0
        && strpos($sk, 'sk_') === 0;
}

/**
 * ¿Modo test (llaves pk_test / sk_test)?
 */
function yuniorrojas_culqi_es_test(): bool
{
    return strpos(yuniorrojas_culqi_public_key(), '_test_') !== false;
}

/**
 * Convierte precio legible (S/. 45.00) a céntimos enteros para Culqi.
 */
function yuniorrojas_precio_a_centimos(string $precio): int
{
    $precio = trim(str_replace(array('S/.', 'S/', 'PEN', ' '), '', $precio));
    $precio = str_replace(',', '.', $precio);
    if ($precio === '' || !is_numeric($precio)) {
        return 0;
    }

    return (int) round(((float) $precio) * 100);
}

/**
 * Datos de Yape / Plin / transferencia para el checkout.
 *
 * @return array{
 *     yape_numero:string,
 *     yape_digits:string,
 *     yape_titular:string,
 *     yape_qr_url:string,
 *     banco_nombre:string,
 *     banco_cuenta:string,
 *     banco_cci:string,
 *     banco_titular:string,
 *     tiene_transferencia:bool
 * }
 */
function yuniorrojas_datos_pago_alternativo(): array
{
    $settings = yuniorrojas_pagos_settings();
    $contacto = function_exists('yuniorrojas_contacto') ? yuniorrojas_contacto() : array();

    $yape_numero = trim((string) $settings['yape_numero']);
    if ($yape_numero === '') {
        $yape_numero = (string) ($contacto['whatsapp'] ?? '');
    }

    $digits = preg_replace('/\D+/', '', $yape_numero) ?: '';
    // QR: imagen subida o QR genérico del número.
    $qr_url = '';
    $qr_id  = (int) $settings['yape_qr_id'];
    if ($qr_id > 0) {
        $qr_url = (string) wp_get_attachment_image_url($qr_id, 'medium');
    }
    if ($qr_url === '' && $digits !== '') {
        $qr_url = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&margin=8&data=' . rawurlencode($digits);
    }

    $banco_nombre  = trim((string) $settings['banco_nombre']);
    $banco_cuenta  = trim((string) $settings['banco_cuenta']);
    $banco_cci     = trim((string) $settings['banco_cci']);
    $banco_titular = trim((string) $settings['banco_titular']);

    return array(
        'yape_numero'        => $yape_numero !== '' ? $yape_numero : '+51 999 999 999',
        'yape_digits'        => $digits !== '' ? $digits : '51999999999',
        'yape_titular'       => trim((string) $settings['yape_titular']),
        'yape_qr_url'        => $qr_url,
        'banco_nombre'       => $banco_nombre,
        'banco_cuenta'       => $banco_cuenta,
        'banco_cci'          => $banco_cci,
        'banco_titular'      => $banco_titular,
        'tiene_transferencia'=> $banco_nombre !== '' && ($banco_cuenta !== '' || $banco_cci !== ''),
    );
}

/**
 * Genera clave de idempotencia estable para un intento de cobro (evita doble cargo en retry).
 *
 * @param array<string,scalar> $parts
 */
function yuniorrojas_culqi_idempotency_key(array $parts): string
{
    $normalized = array();
    foreach ($parts as $k => $v) {
        $normalized[sanitize_key((string) $k)] = is_scalar($v) ? (string) $v : '';
    }
    ksort($normalized);
    $salt = defined('AUTH_SALT') ? (string) AUTH_SALT : 'jr-barberia';
    return hash('sha256', wp_json_encode($normalized) . '|' . $salt);
}

/**
 * Crea un cargo en Culqi a partir de un token (tkn_ / ype_).
 * Soporta Idempotency-Key + cache local (plugin juniorrojas-core).
 *
 * @param array{amount:int,currency_code?:string,email:string,source_id:string,description?:string,metadata?:array<string,string>,idempotency_key?:string} $args
 * @return array<string, mixed>|WP_Error
 */
function yuniorrojas_culqi_crear_cargo(array $args)
{
    if (!yuniorrojas_culqi_esta_configurado()) {
        return new WP_Error(
            'culqi_no_config',
            'Los pagos con tarjeta no están configurados. Contacta al estudio o elige otro método de pago.',
            array('status' => 503)
        );
    }

    $amount = isset($args['amount']) ? (int) $args['amount'] : 0;
    $email  = isset($args['email']) ? sanitize_email((string) $args['email']) : '';
    $source = isset($args['source_id']) ? sanitize_text_field((string) $args['source_id']) : '';
    $currency = isset($args['currency_code']) ? strtoupper(sanitize_text_field((string) $args['currency_code'])) : 'PEN';
    $description = isset($args['description'])
        ? substr(sanitize_text_field((string) $args['description']), 0, 80)
        : 'Reserva barbería';
    $idem = isset($args['idempotency_key']) ? preg_replace('/[^a-f0-9]/', '', strtolower((string) $args['idempotency_key'])) : '';
    if (!is_string($idem)) {
        $idem = '';
    }

    if ($amount < 100) {
        return new WP_Error('monto_invalido', 'El monto mínimo de pago es S/. 1.00.', array('status' => 400));
    }
    if ($email === '' || !is_email($email)) {
        return new WP_Error('email_invalido', 'Correo inválido para procesar el pago.', array('status' => 400));
    }
    if ($source === '' || !preg_match('/^(tkn|ype|crd)_(test|live)_/', $source)) {
        return new WP_Error('token_invalido', 'Token de pago inválido o expirado. Intenta de nuevo.', array('status' => 400));
    }
    if (!in_array($currency, array('PEN', 'USD'), true)) {
        $currency = 'PEN';
    }

    // Reutilizar cargo exitoso previo (retry client / timeout).
    if ($idem !== '' && function_exists('jr_db_idempotency_get')) {
        $cached = jr_db_idempotency_get($idem);
        if (is_array($cached) && !empty($cached['id'])) {
            return $cached;
        }
        // Si otra request está cobrando con la misma key, no lanzar segundo cargo.
        if (function_exists('jr_db_idempotency_begin') && !jr_db_idempotency_begin($idem, (int) get_current_user_id())) {
            $cached2 = jr_db_idempotency_get($idem);
            if (is_array($cached2) && !empty($cached2['id'])) {
                return $cached2;
            }
            return new WP_Error(
                'culqi_en_curso',
                'Tu pago se está procesando. Espera unos segundos e inténtalo de nuevo si no ves la confirmación.',
                array('status' => 409)
            );
        }
    }

    $body = array(
        'amount'        => $amount,
        'currency_code' => $currency,
        'email'         => $email,
        'source_id'     => $source,
        'description'   => $description,
        'capture'       => true,
    );

    if (!empty($args['metadata']) && is_array($args['metadata'])) {
        $meta = array();
        foreach ($args['metadata'] as $k => $v) {
            $key = sanitize_key((string) $k);
            if ($key === '') {
                continue;
            }
            $meta[$key] = substr(sanitize_text_field((string) $v), 0, 200);
        }
        if ($meta !== array()) {
            $body['metadata'] = $meta;
        }
    }

    $headers = array(
        'Authorization' => 'Bearer ' . yuniorrojas_culqi_secret_key(),
        'Content-Type'  => 'application/json',
        'Accept'        => 'application/json',
    );
    // Culqi / gateways suelen respetar Idempotency-Key en reintentos idénticos.
    if ($idem !== '') {
        $headers['Idempotency-Key'] = $idem;
    }

    $response = wp_remote_post(
        'https://api.culqi.com/v2/charges',
        array(
            'timeout' => 45,
            'headers' => $headers,
            'body'    => wp_json_encode($body),
        )
    );

    if (is_wp_error($response)) {
        if ($idem !== '' && function_exists('jr_db_idempotency_fail')) {
            jr_db_idempotency_fail($idem);
        }
        return new WP_Error(
            'culqi_red',
            'No pudimos conectar con Culqi. Verifica tu internet e inténtalo otra vez.',
            array('status' => 502)
        );
    }

    $code = (int) wp_remote_retrieve_response_code($response);
    $raw  = (string) wp_remote_retrieve_body($response);
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        $data = array();
    }

    if ($code >= 200 && $code < 300 && !empty($data['id'])) {
        if ($idem !== '' && function_exists('jr_db_idempotency_succeed')) {
            jr_db_idempotency_succeed($idem, $data);
        }
        if ($idem !== '') {
            $data['_idempotency_key'] = $idem;
        }
        return $data;
    }

    if ($idem !== '' && function_exists('jr_db_idempotency_fail')) {
        jr_db_idempotency_fail($idem);
    }

    $user_msg = yuniorrojas_culqi_mensaje_error($data, $code);

    return new WP_Error(
        'culqi_cargo',
        $user_msg,
        array(
            'status'      => $code >= 400 && $code < 600 ? $code : 402,
            'culqi'       => $data,
            'culqi_code'  => $code,
        )
    );
}

/**
 * Mensaje legible para el cliente a partir de la respuesta Culqi.
 *
 * @param array<string, mixed> $data
 */
function yuniorrojas_culqi_mensaje_error(array $data, int $http_code = 0): string
{
    $merchant = isset($data['merchant_message']) ? (string) $data['merchant_message'] : '';
    $user     = isset($data['user_message']) ? (string) $data['user_message'] : '';
    $type     = isset($data['type']) ? (string) $data['type'] : '';

    if ($user !== '') {
        return $user;
    }

    $map = array(
        'card_declined'           => 'Tu tarjeta fue rechazada. Prueba con otra, Yape en Culqi o Plin.',
        'insufficient_funds'      => 'Fondos insuficientes. Prueba con otra tarjeta o método de pago.',
        'expired_card'            => 'La tarjeta está vencida. Usa otra tarjeta.',
        'incorrect_cvv'           => 'El código CVV es incorrecto.',
        'invalid_card'            => 'Los datos de la tarjeta no son válidos.',
        'processing_error'        => 'Error al procesar el pago. Inténtalo en unos minutos.',
        'fraudulent'              => 'El pago no pudo completarse por seguridad. Contacta a tu banco.',
        'parameter_error'         => 'Datos de pago incompletos. Revisa la información e inténtalo de nuevo.',
        'authentication_error'    => 'Error de configuración de pagos del estudio. Intenta Plin o pago en estudio.',
    );

    if ($type !== '' && isset($map[$type])) {
        return $map[$type];
    }

    if ($merchant !== '') {
        // No exponer detalles internos en producción; en test es útil.
        if (yuniorrojas_culqi_es_test()) {
            return $merchant;
        }
    }

    if ($http_code === 401 || $http_code === 403) {
        return 'Error de configuración de pagos. El estudio debe verificar las llaves Culqi.';
    }

    return 'No se pudo completar el pago con tarjeta. Intenta de nuevo o elige otro método.';
}

/**
 * Reembolso total (o parcial) de un cargo Culqi.
 *
 * @return array<string,mixed>|WP_Error
 */
function yuniorrojas_culqi_refund_cargo(string $charge_id, int $amount_centimos = 0, string $reason = 'solicitud_comprador')
{
    $charge_id = sanitize_text_field($charge_id);
    if ($charge_id === '' || !preg_match('/^chr_(test|live)_/', $charge_id)) {
        return new WP_Error('culqi_refund', 'ID de cargo Culqi no válido.', array('status' => 400));
    }
    if (!yuniorrojas_culqi_esta_configurado()) {
        return new WP_Error('culqi_no_config', 'Culqi no está configurado para reembolsar.', array('status' => 503));
    }

    $allowed_reasons = array(
        'duplicado',
        'fraudulento',
        'solicitud_comprador',
    );
    if (!in_array($reason, $allowed_reasons, true)) {
        $reason = 'solicitud_comprador';
    }

    $body = array(
        'amount'    => $amount_centimos > 0 ? $amount_centimos : null,
        'charge_id' => $charge_id,
        'reason'    => $reason,
    );
    // Sin amount = reembolso total en Culqi API v2.
    if ($amount_centimos <= 0) {
        unset($body['amount']);
    }

    $response = wp_remote_post(
        'https://api.culqi.com/v2/refunds',
        array(
            'timeout' => 45,
            'headers' => array(
                'Authorization' => 'Bearer ' . yuniorrojas_culqi_secret_key(),
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ),
            'body'    => wp_json_encode($body),
        )
    );

    if (is_wp_error($response)) {
        return new WP_Error(
            'culqi_red',
            'No pudimos conectar con Culqi para reembolsar.',
            array('status' => 502)
        );
    }

    $code = (int) wp_remote_retrieve_response_code($response);
    $raw  = (string) wp_remote_retrieve_body($response);
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        $data = array();
    }

    if ($code >= 200 && $code < 300 && !empty($data['id'])) {
        return $data;
    }

    $msg = isset($data['merchant_message'])
        ? (string) $data['merchant_message']
        : 'No se pudo reembolsar el cargo en Culqi.';

    return new WP_Error(
        'culqi_refund',
        $msg,
        array('status' => $code >= 400 ? $code : 502, 'culqi' => $data)
    );
}

/**
 * Intenta reembolsar el cargo de una reserva y guarda meta.
 *
 * @return true|WP_Error
 */
function yuniorrojas_reserva_refund_culqi_si_aplica(int $reserva_id, string $motivo = 'solicitud_comprador')
{
    $reserva_id = absint($reserva_id);
    if ($reserva_id <= 0 || !function_exists('yuniorrojas_reserva_meta_key')) {
        return new WP_Error('reserva', 'Reserva no válida.');
    }

    $ya = (string) get_post_meta($reserva_id, yuniorrojas_reserva_meta_key('culqi_refund_id'), true);
    if ($ya !== '') {
        return true;
    }

    $charge_id = (string) get_post_meta($reserva_id, yuniorrojas_reserva_meta_key('culqi_charge_id'), true);
    if ($charge_id === '' || !function_exists('yuniorrojas_culqi_refund_cargo')) {
        return true; // Nada que reembolsar.
    }

    $result = yuniorrojas_culqi_refund_cargo($charge_id, 0, $motivo);
    if (is_wp_error($result)) {
        update_post_meta(
            $reserva_id,
            yuniorrojas_reserva_meta_key('culqi_refund_error'),
            $result->get_error_message()
        );
        if (function_exists('error_log')) {
            error_log('[yuniorrojas] Culqi refund falló reserva ' . $reserva_id . ': ' . $result->get_error_message());
        }
        return $result;
    }

    update_post_meta($reserva_id, yuniorrojas_reserva_meta_key('culqi_refund_id'), (string) ($result['id'] ?? ''));
    update_post_meta($reserva_id, yuniorrojas_reserva_meta_key('culqi_refund_at'), current_time('mysql'));
    delete_post_meta($reserva_id, yuniorrojas_reserva_meta_key('culqi_refund_error'));

    return true;
}

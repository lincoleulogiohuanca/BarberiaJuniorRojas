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
        'culqi_public_key' => '',
        'culqi_secret_key' => '',
        'yape_numero'      => '',
        'yape_titular'     => '',
        'yape_qr_id'       => 0,
        'banco_nombre'     => '',
        'banco_cuenta'     => '',
        'banco_cci'        => '',
        'banco_titular'    => '',
    );
}

/**
 * Si las option están vacías, importa desde wp-config (constantes) o seeds de prueba una sola vez.
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

    if ($public === '' && defined('YUNIORROJAS_CULQI_PUBLIC_KEY') && (string) YUNIORROJAS_CULQI_PUBLIC_KEY !== '') {
        $saved['culqi_public_key'] = (string) YUNIORROJAS_CULQI_PUBLIC_KEY;
        $public  = $saved['culqi_public_key'];
        $changed = true;
    }
    if ($secret === '' && defined('YUNIORROJAS_CULQI_SECRET_KEY') && (string) YUNIORROJAS_CULQI_SECRET_KEY !== '') {
        $saved['culqi_secret_key'] = (string) YUNIORROJAS_CULQI_SECRET_KEY;
        $secret  = $saved['culqi_secret_key'];
        $changed = true;
    }

    // Migración local: llaves test que estaban en wp-config (solo si sigue vacío).
    if ($public === '' && $secret === '' && !get_option('yuniorrojas_culqi_keys_imported', false)) {
        $saved['culqi_public_key'] = 'pk_test_AXEwZuPbByAfn7UE';
        $saved['culqi_secret_key'] = 'sk_test_UpbfImXXzu6YDQq5';
        $changed = true;
        update_option('yuniorrojas_culqi_keys_imported', 1, false);
    }

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
        'culqi_public_key' => (string) ($saved['culqi_public_key'] ?? $defaults['culqi_public_key']),
        'culqi_secret_key' => (string) ($saved['culqi_secret_key'] ?? $defaults['culqi_secret_key']),
        'yape_numero'      => (string) ($saved['yape_numero'] ?? $defaults['yape_numero']),
        'yape_titular'     => (string) ($saved['yape_titular'] ?? $defaults['yape_titular']),
        'yape_qr_id'       => absint($saved['yape_qr_id'] ?? $defaults['yape_qr_id']),
        'banco_nombre'     => (string) ($saved['banco_nombre'] ?? $defaults['banco_nombre']),
        'banco_cuenta'     => (string) ($saved['banco_cuenta'] ?? $defaults['banco_cuenta']),
        'banco_cci'        => (string) ($saved['banco_cci'] ?? $defaults['banco_cci']),
        'banco_titular'    => (string) ($saved['banco_titular'] ?? $defaults['banco_titular']),
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
 * Crea un cargo en Culqi a partir de un token (tkn_ / ype_).
 *
 * @param array{amount:int,currency_code?:string,email:string,source_id:string,description?:string,metadata?:array<string,string>} $args
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

    $response = wp_remote_post(
        'https://api.culqi.com/v2/charges',
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
        return $data;
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

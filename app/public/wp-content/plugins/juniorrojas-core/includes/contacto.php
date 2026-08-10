<?php
/**
 * Formulario de contacto nativo: honeypot + rate limit.
 */

if (!defined('ABSPATH')) {
    exit;
}

function yuniorrojas_contacto_ip(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    return sanitize_text_field(wp_unslash((string) $ip));
}

function yuniorrojas_contacto_rate_limit_ok(): bool
{
    $key = 'yuniorrojas_contacto_' . md5(yuniorrojas_contacto_ip());
    $hits = (int) get_transient($key);

    if ($hits >= 5) {
        return false;
    }

    set_transient($key, $hits + 1, 15 * MINUTE_IN_SECONDS);
    return true;
}

function yuniorrojas_procesar_contacto(): void
{
    $redirect = wp_get_referer() ?: home_url('/contacto/');

    if (!yuniorrojas_verificar_nonce('yuniorrojas_contacto_nonce', 'yuniorrojas_contacto')) {
        wp_safe_redirect(add_query_arg('contacto', 'error', $redirect));
        exit;
    }

    // Honeypot: los bots rellenan este campo oculto.
    $honeypot = sanitize_text_field(wp_unslash($_POST['yuniorrojas_company'] ?? ''));
    if ($honeypot !== '') {
        wp_safe_redirect(add_query_arg('contacto', 'ok', $redirect));
        exit;
    }

    // Tiempo mínimo de relleno (~3s) para descartar envíos automáticos.
    $started = absint($_POST['yuniorrojas_form_ts'] ?? 0);
    if ($started > 0 && (time() - $started) < 3) {
        wp_safe_redirect(add_query_arg('contacto', 'ok', $redirect));
        exit;
    }

    if (!yuniorrojas_contacto_rate_limit_ok()) {
        wp_safe_redirect(add_query_arg('contacto', 'limit', $redirect));
        exit;
    }

    $nombre  = sanitize_text_field(wp_unslash($_POST['nombre'] ?? ''));
    $email   = sanitize_email(wp_unslash($_POST['email'] ?? ''));
    $asunto  = sanitize_text_field(wp_unslash($_POST['asunto'] ?? ''));
    $mensaje = sanitize_textarea_field(wp_unslash($_POST['mensaje'] ?? ''));

    if ($nombre === '' || $email === '' || $asunto === '' || $mensaje === '' || !is_email($email)) {
        wp_safe_redirect(add_query_arg('contacto', 'error', $redirect));
        exit;
    }

    $to      = get_option('admin_email');
    $subject = sprintf('[Contacto] %s', $asunto);
    $body    = "Nombre: {$nombre}\nEmail: {$email}\n\n{$mensaje}";
    $headers = array(
        'Content-Type: text/plain; charset=UTF-8',
        'Reply-To: ' . $nombre . ' <' . $email . '>',
    );

    wp_mail($to, $subject, $body, $headers);

    wp_safe_redirect(add_query_arg('contacto', 'ok', $redirect));
    exit;
}
add_action('admin_post_nopriv_yuniorrojas_contacto', 'yuniorrojas_procesar_contacto');
add_action('admin_post_yuniorrojas_contacto', 'yuniorrojas_procesar_contacto');

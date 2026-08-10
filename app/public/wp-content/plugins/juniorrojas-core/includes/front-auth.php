<?php
/**
 * Auth de cliente: URLs WP → páginas del tema, avatar, redirecciones wp-login.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Foto de perfil del cliente en get_avatar / get_avatar_url.
 *
 * @param array<string, mixed> $args Args de avatar.
 * @param mixed                $id_or_email User ID, email o objeto.
 * @return array<string, mixed>
 */
function yuniorrojas_pre_get_avatar_data(array $args, $id_or_email): array
{
    if (!function_exists('yuniorrojas_cliente_avatar_id') || !function_exists('yuniorrojas_cliente_avatar_url')) {
        return $args;
    }

    $user_id = 0;
    if (is_numeric($id_or_email)) {
        $user_id = (int) $id_or_email;
    } elseif ($id_or_email instanceof WP_User) {
        $user_id = (int) $id_or_email->ID;
    } elseif (is_object($id_or_email) && isset($id_or_email->user_id)) {
        $user_id = (int) $id_or_email->user_id;
    } elseif (is_string($id_or_email) && is_email($id_or_email)) {
        $user = get_user_by('email', $id_or_email);
        if ($user instanceof WP_User) {
            $user_id = (int) $user->ID;
        }
    }

    if ($user_id <= 0 || yuniorrojas_cliente_avatar_id($user_id) <= 0) {
        return $args;
    }

    $size = isset($args['size']) ? (int) $args['size'] : 96;
    $url  = yuniorrojas_cliente_avatar_url($user_id, max(48, $size));
    if ($url === '') {
        return $args;
    }

    $args['url']          = $url;
    $args['found_avatar'] = true;

    return $args;
}
add_filter('pre_get_avatar_data', 'yuniorrojas_pre_get_avatar_data', 10, 2);

/**
 * @param string $login_url    URL por defecto.
 * @param string $redirect     Destino opcional.
 * @param bool   $force_reauth Forzar reautenticación.
 */
function yuniorrojas_filtrar_login_url(string $login_url, string $redirect = '', bool $force_reauth = false): string
{
    if ($redirect !== '' && str_contains($redirect, 'wp-admin')) {
        return $login_url;
    }

    $url = yuniorrojas_url_login();
    if ($redirect !== '') {
        $url = add_query_arg('redirect_to', rawurlencode($redirect), $url);
    }
    if ($force_reauth) {
        $url = add_query_arg('reauth', '1', $url);
    }

    return $url;
}
add_filter('login_url', 'yuniorrojas_filtrar_login_url', 10, 3);

/**
 * @param string $register_url URL por defecto de WP.
 */
function yuniorrojas_filtrar_register_url(string $register_url): string
{
    unset($register_url);
    return yuniorrojas_url_registro();
}
add_filter('register_url', 'yuniorrojas_filtrar_register_url');

/**
 * @param string $lostpassword_url URL WP.
 * @param string $redirect         Destino opcional.
 */
function yuniorrojas_filtrar_lostpassword_url(string $lostpassword_url, string $redirect = ''): string
{
    unset($lostpassword_url);
    $url = yuniorrojas_url_recuperar();
    if ($redirect !== '') {
        $url = add_query_arg('redirect_to', rawurlencode($redirect), $url);
    }
    return $url;
}
add_filter('lostpassword_url', 'yuniorrojas_filtrar_lostpassword_url', 10, 2);

/**
 * Correo de recuperación apunta a la página de cliente.
 *
 * @param string  $message    Mensaje.
 * @param string  $key        Key de reset.
 * @param string  $user_login Login.
 * @param WP_User $user_data  Usuario.
 */
function yuniorrojas_mensaje_recuperar_clave(string $message, string $key, string $user_login, $user_data): string
{
    unset($message, $user_data);

    $url = yuniorrojas_url_recuperar(array(
        'key'   => $key,
        'login' => $user_login,
    ));

    $site = wp_specialchars_decode((string) get_option('blogname'), ENT_QUOTES);

    $lines = array(
        'Hola,',
        '',
        'Recibimos una solicitud para restablecer la contraseña de tu cuenta en ' . $site . '.',
        '',
        'Usa este enlace para crear una nueva contraseña:',
        $url,
        '',
        'Si no solicitaste este cambio, ignora este correo.',
        '',
        'Junior Rojas Barber Studio',
    );

    return implode("\r\n", $lines);
}
add_filter('retrieve_password_message', 'yuniorrojas_mensaje_recuperar_clave', 10, 4);

/**
 * wp-login.php (salvo logout / wp-admin) → pantallas de cliente del tema.
 */
function yuniorrojas_redirigir_wp_login_cliente(): void
{
    if (defined('REST_REQUEST') && REST_REQUEST) {
        return;
    }

    $action = isset($_REQUEST['action']) ? sanitize_key(wp_unslash((string) $_REQUEST['action'])) : 'login';
    if (in_array($action, array('logout', 'postpass', 'confirmaction'), true)) {
        return;
    }

    $redirect_to = isset($_REQUEST['redirect_to'])
        ? (string) wp_unslash($_REQUEST['redirect_to'])
        : '';

    if ($redirect_to !== '' && str_contains($redirect_to, 'wp-admin')) {
        return;
    }

    if (in_array($action, array('lostpassword', 'retrievepassword'), true)) {
        wp_safe_redirect(yuniorrojas_url_recuperar());
        exit;
    }

    if (in_array($action, array('rp', 'resetpass'), true)) {
        $key   = isset($_REQUEST['key']) ? sanitize_text_field(wp_unslash((string) $_REQUEST['key'])) : '';
        $login = isset($_REQUEST['login']) ? sanitize_text_field(wp_unslash((string) $_REQUEST['login'])) : '';

        if ($login === '' && !empty($_COOKIE)) {
            foreach ($_COOKIE as $name => $value) {
                if (str_starts_with((string) $name, 'wp-resetpass-') || str_starts_with((string) $name, 'rp_')) {
                    $parts = explode(':', wp_unslash((string) $value), 2);
                    if (count($parts) === 2) {
                        $login = sanitize_text_field($parts[0]);
                        $key   = sanitize_text_field($parts[1]);
                    }
                    break;
                }
            }
        }

        $args = array();
        if ($key !== '') {
            $args['key'] = $key;
        }
        if ($login !== '') {
            $args['login'] = $login;
        }

        wp_safe_redirect(yuniorrojas_url_recuperar($args));
        exit;
    }

    if ($action === 'register') {
        wp_safe_redirect(yuniorrojas_url_registro());
        exit;
    }

    wp_safe_redirect(yuniorrojas_url_login());
    exit;
}
add_action('login_init', 'yuniorrojas_redirigir_wp_login_cliente');
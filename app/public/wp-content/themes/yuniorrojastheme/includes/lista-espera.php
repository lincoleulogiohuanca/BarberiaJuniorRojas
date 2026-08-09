<?php
/**
 * Lista de espera — avísame si hay hueco.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('YUNIORROJAS_CPT_ESPERA')) {
    define('YUNIORROJAS_CPT_ESPERA', 'jr_lista_espera');
}

/**
 * Registra CPT lista de espera.
 */
function yuniorrojas_registrar_cpt_lista_espera(): void
{
    if (post_type_exists(YUNIORROJAS_CPT_ESPERA)) {
        return;
    }

    register_post_type(YUNIORROJAS_CPT_ESPERA, array(
        'labels' => array(
            'name'          => __('Lista de espera', YUNIORROJAS_TEXT_DOMAIN),
            'singular_name' => __('Solicitud', YUNIORROJAS_TEXT_DOMAIN),
        ),
        'public'       => false,
        'show_ui'      => true,
        'show_in_menu' => 'edit.php?post_type=' . YUNIORROJAS_CPT_RESERVAS,
        'menu_icon'    => 'dashicons-clock',
        'supports'     => array('title'),
        'capability_type' => 'post',
    ));
}
add_action('init', 'yuniorrojas_registrar_cpt_lista_espera');

/**
 * Meta key espera.
 */
function yuniorrojas_espera_meta_key(string $campo): string
{
    return '_jr_espera_' . $campo;
}

/**
 * Crea solicitud de lista de espera.
 *
 * @param array<string, mixed> $data
 * @return int|WP_Error
 */
function yuniorrojas_lista_espera_crear(array $data)
{
    $user_id     = (int) ($data['user_id'] ?? get_current_user_id());
    $barbero_id  = (int) ($data['barbero_id'] ?? 0);
    $servicio_id = (int) ($data['servicio_id'] ?? 0);
    $fecha       = sanitize_text_field((string) ($data['fecha'] ?? ''));
    $telefono    = sanitize_text_field((string) ($data['telefono'] ?? ''));
    $email       = sanitize_email((string) ($data['email'] ?? ''));

    if ($user_id <= 0 || !yuniorrojas_es_cliente()) {
        return new WP_Error('auth', 'Debes iniciar sesión como cliente.', array('status' => 401));
    }
    if ($barbero_id <= 0 || get_post_type($barbero_id) !== 'barberos') {
        return new WP_Error('barbero', 'Barbero no válido.', array('status' => 400));
    }
    if ($fecha !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        return new WP_Error('fecha', 'Fecha no válida.', array('status' => 400));
    }

    $user = get_userdata($user_id);
    if ($user instanceof WP_User) {
        if ($email === '') {
            $email = $user->user_email;
        }
        if ($telefono === '') {
            $telefono = (string) get_user_meta($user_id, 'telefono', true);
        }
    }

    $barbero_nombre = get_the_title($barbero_id);
    $titulo = sprintf(
        'Espera — %s — %s%s',
        $user instanceof WP_User ? $user->display_name : ('User ' . $user_id),
        $barbero_nombre,
        $fecha !== '' ? ' · ' . $fecha : ''
    );

    $post_id = wp_insert_post(array(
        'post_type'   => YUNIORROJAS_CPT_ESPERA,
        'post_status' => 'publish',
        'post_title'  => $titulo,
        'post_author' => $user_id,
    ), true);

    if (is_wp_error($post_id)) {
        return $post_id;
    }

    $metas = array(
        'user_id'     => (string) $user_id,
        'barbero_id'  => (string) $barbero_id,
        'servicio_id' => (string) $servicio_id,
        'fecha'       => $fecha,
        'telefono'    => $telefono,
        'email'       => $email,
        'estado'      => 'activa',
    );
    foreach ($metas as $k => $v) {
        update_post_meta((int) $post_id, yuniorrojas_espera_meta_key($k), $v);
    }

    // Aviso al admin.
    $admin = sanitize_email((string) get_option('admin_email'));
    if ($admin !== '' && is_email($admin)) {
        $body = yuniorrojas_email_wrap(
            'Nueva lista de espera',
            '<p>Cliente quiere aviso si hay hueco.</p>'
            . '<p>Barbero: ' . esc_html($barbero_nombre) . '<br>Fecha preferida: ' . esc_html($fecha !== '' ? $fecha : 'Cualquiera') . '</p>'
            . '<p>Email: ' . esc_html($email) . ' · Tel: ' . esc_html($telefono) . '</p>'
        );
        wp_mail($admin, '[' . get_bloginfo('name') . '] Lista de espera', $body, yuniorrojas_mail_headers_html());
    }

    return (int) $post_id;
}

/**
 * Notifica lista de espera cuando se libera un hueco (cancelación).
 */
function yuniorrojas_lista_espera_avisar_hueco(int $barbero_id, string $fecha): void
{
    if ($barbero_id <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        return;
    }

    $ids = get_posts(array(
        'post_type'      => YUNIORROJAS_CPT_ESPERA,
        'post_status'    => 'publish',
        'posts_per_page' => 20,
        'fields'         => 'ids',
        'meta_query'     => array(
            'relation' => 'AND',
            array(
                'key'   => yuniorrojas_espera_meta_key('barbero_id'),
                'value' => (string) $barbero_id,
            ),
            array(
                'key'   => yuniorrojas_espera_meta_key('estado'),
                'value' => 'activa',
            ),
            array(
                'relation' => 'OR',
                array(
                    'key'   => yuniorrojas_espera_meta_key('fecha'),
                    'value' => $fecha,
                ),
                array(
                    'key'   => yuniorrojas_espera_meta_key('fecha'),
                    'value' => '',
                ),
            ),
        ),
    ));

    $barbero = get_the_title($barbero_id);
    $reservar = yuniorrojas_url_reservar(array('barbero' => $barbero_id, 'paso' => 'cita'));
    $dt = DateTime::createFromFormat('Y-m-d', $fecha);
    $fecha_label = $dt instanceof DateTime ? $dt->format('d/m/Y') : $fecha;

    foreach ($ids as $id) {
        $email = sanitize_email((string) get_post_meta((int) $id, yuniorrojas_espera_meta_key('email'), true));
        if ($email === '' || !is_email($email)) {
            continue;
        }

        $cuerpo = '<p>Se liberó un horario con <strong>' . esc_html($barbero) . '</strong> el ' . esc_html($fecha_label) . '.</p>'
            . '<p><a href="' . esc_url($reservar) . '" style="color:#c8a24a;">Reservar ahora</a></p>';

        wp_mail(
            $email,
            '[' . get_bloginfo('name') . '] Hay un hueco disponible',
            yuniorrojas_email_wrap('Hueco disponible', $cuerpo),
            yuniorrojas_mail_headers_html()
        );
        update_post_meta((int) $id, yuniorrojas_espera_meta_key('estado'), 'notificada');
    }
}

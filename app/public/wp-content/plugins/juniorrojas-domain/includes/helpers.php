<?php
/**
 * Helpers del tema — acceso seguro a ACF y datos de marca.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Verifica un nonce POST con unslash + sanitize.
 */
function yuniorrojas_verificar_nonce(string $field, string $action): bool
{
    if (!isset($_POST[$field])) {
        return false;
    }

    return (bool) wp_verify_nonce(
        sanitize_text_field(wp_unslash($_POST[$field])),
        $action
    );
}

/**
 * Obtiene un campo ACF con fallback si ACF no está activo.
 *
 * @param string     $selector
 * @param mixed      $post_id
 * @param mixed|null $default
 * @return mixed
 */
function yuniorrojas_field(string $selector, $post_id = false, $default = '')
{
    if (function_exists('get_field')) {
        $value = get_field($selector, $post_id);
        if ($value !== null && $value !== false && $value !== '') {
            return $value;
        }
    }

    return $default;
}

/**
 * URL de reserva (página o enlace externo).
 *
 * @param array{servicio?:int,barbero?:int,paso?:string,reprogramar?:int} $args Query opcional.
 */
function yuniorrojas_url_reservar(array $args = array()): string
{
    $url = yuniorrojas_field('url_reservar', 'option', '');

    if (!$url) {
        $page = get_page_by_path('reservar');
        if (!$page instanceof WP_Post) {
            $page = yuniorrojas_pagina_por_plantilla('page-reservar.php');
        }
        $url = $page instanceof WP_Post
            ? (string) get_permalink($page)
            : home_url('/contacto/');
    }

    $query = array();
    if (!empty($args['servicio'])) {
        $query['servicio'] = (int) $args['servicio'];
    }
    if (!empty($args['barbero'])) {
        $query['barbero'] = (int) $args['barbero'];
    }
    if (!empty($args['paso'])) {
        $paso = sanitize_key((string) $args['paso']);
        if (in_array($paso, array('experiencia', 'cita', 'datos', 'checkout'), true)) {
            $query['paso'] = $paso;
        }
    }
    if (!empty($args['reprogramar'])) {
        $query['reprogramar'] = (int) $args['reprogramar'];
    }

    if ($query !== array()) {
        $url = add_query_arg($query, $url);
    }

    return esc_url($url);
}

/**
 * URL de login del cliente (nunca wp-login.php).
 */
function yuniorrojas_url_login(): string
{
    $page = get_page_by_path('iniciar-sesion');
    if (!$page instanceof WP_Post) {
        $page = yuniorrojas_pagina_por_plantilla('page-iniciar-sesion.php');
    }

    if ($page instanceof WP_Post && $page->post_status === 'publish') {
        return esc_url((string) get_permalink($page));
    }

    return esc_url(home_url('/iniciar-sesion/'));
}

/**
 * URL de registro del cliente (nunca registro de WordPress admin).
 */
function yuniorrojas_url_registro(): string
{
    $page = get_page_by_path('registro');
    if (!$page instanceof WP_Post) {
        $page = yuniorrojas_pagina_por_plantilla('page-registro.php');
    }

    if ($page instanceof WP_Post && $page->post_status === 'publish') {
        return esc_url((string) get_permalink($page));
    }

    return esc_url(home_url('/registro/'));
}

/**
 * URL de recuperación de contraseña (cliente).
 */
function yuniorrojas_url_recuperar(array $args = array()): string
{
    $page = get_page_by_path('recuperar-clave');
    if (!$page instanceof WP_Post) {
        $page = yuniorrojas_pagina_por_plantilla('page-recuperar-clave.php');
    }

    $url = ($page instanceof WP_Post && $page->post_status === 'publish')
        ? (string) get_permalink($page)
        : home_url('/recuperar-clave/');

    if ($args !== array()) {
        $url = add_query_arg($args, $url);
    }

    return esc_url($url);
}

/**
 * URL del panel del cliente (mi cuenta).
 */
function yuniorrojas_url_cuenta(): string
{
    $page = get_page_by_path('mi-cuenta');
    if (!$page instanceof WP_Post) {
        $page = yuniorrojas_pagina_por_plantilla('page-mi-cuenta.php');
    }

    if ($page instanceof WP_Post) {
        return esc_url((string) get_permalink($page));
    }

    return esc_url(home_url('/mi-cuenta/'));
}

/**
 * Destino tras login/registro desde el front de clientes.
 * Solo clientes; admins no deben autenticarse por este flujo.
 */
function yuniorrojas_url_post_login(?WP_User $user = null): string
{
    if (!$user instanceof WP_User && is_user_logged_in()) {
        $user = wp_get_current_user();
    }

    if ($user instanceof WP_User && yuniorrojas_es_administrador($user)) {
        return esc_url(admin_url());
    }

    return yuniorrojas_url_cuenta();
}

/**
 * ¿Usuario con acceso al panel de administración (wp-admin)?
 */
function yuniorrojas_es_administrador(?WP_User $user = null): bool
{
    if (!$user instanceof WP_User) {
        if (!is_user_logged_in()) {
            return false;
        }
        $user = wp_get_current_user();
    }

    if (!$user instanceof WP_User || (int) $user->ID <= 0) {
        return false;
    }

    return user_can($user, 'manage_options');
}

/**
 * ¿El usuario actual es cliente del front (no admin)?
 */
function yuniorrojas_es_cliente(?WP_User $user = null): bool
{
    if (!$user instanceof WP_User) {
        if (!is_user_logged_in()) {
            return false;
        }
        $user = wp_get_current_user();
    }

    if (!$user instanceof WP_User || (int) $user->ID <= 0) {
        return false;
    }

    return !yuniorrojas_es_administrador($user);
}

/**
 * Admin bar en el front:
 * - Administradores: sí (acceso rápido a wp-admin).
 * - Clientes y resto: no (no es parte de la web del estudio).
 */
function yuniorrojas_mostrar_admin_bar(bool $show): bool
{
    if (is_admin()) {
        return $show;
    }

    if (!is_user_logged_in()) {
        return false;
    }

    return yuniorrojas_es_administrador();
}
add_filter('show_admin_bar', 'yuniorrojas_mostrar_admin_bar');

/**
 * Mensaje cuando un administrador intenta usar el login de clientes.
 */
function yuniorrojas_mensaje_admin_no_cliente(): string
{
    return 'Como administrador no estás permitido ingresar aquí. Usa tu panel de administración.';
}

/**
 * ¿Puede verse/usarse el CTA de reservar en el front?
 * Los administradores gestionan desde wp-admin, no reservan como clientes.
 */
function yuniorrojas_puede_reservar_en_front(?WP_User $user = null): bool
{
    return !yuniorrojas_es_administrador($user);
}

/**
 * Datos mock del dashboard cliente (UI demo).
 *
 * @return array{
 *   nivel:array{nombre:string,descripcion:string,progreso:int,faltan:int,siguiente:string},
 *   proximas:array<int,array{dia:string,mes:string,servicio:string,hora:string,barbero:string}>,
 *   historial:array<int,array{titulo:string,fecha:string,anio:string,servicio:string,barbero:string,descripcion:string,imagen:string,cta:string}>
 * }
 */
function yuniorrojas_cuenta_mock_data(): array
{
    $theme_uri = get_template_directory_uri();

    return array(
        'nivel' => array(
            'nombre'      => 'Miembro Gold',
            'descripcion' => 'Disfruta de bebidas premium y prioridad en reservas.',
            'progreso'    => 66,
            'faltan'      => 2,
            'siguiente'   => 'Platinum',
        ),
        'proximas' => array(
            array(
                'dia'      => '15',
                'mes'      => 'Nov',
                'servicio' => 'Corte de autor + arreglo de barba',
                'hora'     => '14:00 - 15:30',
                'barbero'  => 'Mateo Silva',
            ),
        ),
        'historial' => array(
            array(
                'titulo'      => 'Corte ejecutivo & barba',
                'fecha'       => '15 Oct 2024',
                'anio'        => '2024',
                'servicio'    => 'corte-barba',
                'barbero'     => 'Junior Rojas',
                'descripcion' => 'Fade medio con volumen superior texturizado. Perfilado de barba con navaja y toalla caliente.',
                'imagen'      => $theme_uri . '/img/logo.png',
                'cta'         => 'solid',
            ),
            array(
                'titulo'      => 'Mantenimiento clásico',
                'fecha'       => '28 Sep 2024',
                'anio'        => '2024',
                'servicio'    => 'mantenimiento',
                'barbero'     => 'Mateo V.',
                'descripcion' => 'Corte a tijera clásico, rebaje en laterales sin fade. Alineación de contornos naturales.',
                'imagen'      => $theme_uri . '/img/logo monograma.png',
                'cta'         => 'outline',
            ),
        ),
        'estilos' => array(
            array(
                'id'       => 'pompadour',
                'nombre'   => 'Pompadour Clásico',
                'imagen'   => $theme_uri . '/img/logo.png',
                'selected' => true,
            ),
            array(
                'id'       => 'fade',
                'nombre'   => 'Fade Texturizado',
                'imagen'   => $theme_uri . '/img/logo monograma.png',
                'selected' => false,
            ),
        ),
    );
}

/**
 * URL del logo de marca (Custom Logo de WP o fallback del tema).
 *
 * Cada instalación/cliente sube el suyo en:
 * Apariencia → Personalizar → Identidad del sitio → Logo
 *
 * Filtro: `yuniorrojas_logo_url` para overrides por código / multi-tenant.
 */
function yuniorrojas_logo_url(): string
{
    $custom_id = (int) get_theme_mod('custom_logo');
    if ($custom_id > 0) {
        $url = wp_get_attachment_image_url($custom_id, 'full');
        if (is_string($url) && $url !== '') {
            return esc_url((string) apply_filters('yuniorrojas_logo_url', $url));
        }
    }

    $fallback = get_template_directory_uri() . '/img/logo.png';

    return esc_url((string) apply_filters('yuniorrojas_logo_url', $fallback));
}

/**
 * URL del monograma / icono (footer, avatares fallback, etc.).
 * Prioridad: theme_mod `yuniorrojas_logo_mark` → logo principal WP → monograma del tema.
 *
 * Filtro: `yuniorrojas_logo_mark_url`
 */
function yuniorrojas_logo_mark_url(): string
{
    $mark_id = (int) get_theme_mod('yuniorrojas_logo_mark', 0);
    if ($mark_id > 0) {
        $url = wp_get_attachment_image_url($mark_id, 'full');
        if (is_string($url) && $url !== '') {
            return esc_url((string) apply_filters('yuniorrojas_logo_mark_url', $url));
        }
    }

    $custom_id = (int) get_theme_mod('custom_logo');
    if ($custom_id > 0) {
        $url = wp_get_attachment_image_url($custom_id, 'full');
        if (is_string($url) && $url !== '') {
            return esc_url((string) apply_filters('yuniorrojas_logo_mark_url', $url));
        }
    }

    $fallback = get_template_directory_uri() . '/img/logo monograma.png';

    return esc_url((string) apply_filters('yuniorrojas_logo_mark_url', $fallback));
}

/**
 * Enlace a listado de servicios.
 */
function yuniorrojas_url_servicios(): string
{
    $page = get_page_by_path('servicios');
    if ($page instanceof WP_Post) {
        return esc_url(get_permalink($page));
    }

    $por_plantilla = yuniorrojas_pagina_por_plantilla('page-listado-servicios.php');
    if ($por_plantilla instanceof WP_Post) {
        return esc_url(get_permalink($por_plantilla));
    }

    return esc_url(get_post_type_archive_link(YUNIORROJAS_CPT_SERVICIOS) ?: home_url('/'));
}

/**
 * Datos de contacto del estudio (option nativa del tema, con fallback ACF y defaults).
 *
 * @return array{
 *     whatsapp:string,
 *     telefono:string,
 *     direccion:string,
 *     mapa_embed:string,
 *     mapa_lat:float,
 *     mapa_lng:float,
 *     mapa_zoom:int,
 *     horarios:array<int,array{dia:string,hora:string}>
 * }
 */
function yuniorrojas_contacto(): array
{
    if (function_exists('yuniorrojas_obtener_contacto_settings')) {
        return yuniorrojas_obtener_contacto_settings();
    }

    return array(
        'whatsapp'    => (string) yuniorrojas_field('whatsapp', 'option', '+51 999 999 999'),
        'telefono'    => (string) yuniorrojas_field('telefono', 'option', '+51 999 999 999'),
        'direccion'   => (string) yuniorrojas_field('direccion', 'option', 'Jr. Ayacucho N° 727 - Huánuco - Perú'),
        'mapa_embed' => (string) yuniorrojas_field('mapa_embed', 'option', ''),
        'mapa_lat'   => (float) yuniorrojas_field('mapa_lat', 'option', -9.9297),
        'mapa_lng'   => (float) yuniorrojas_field('mapa_lng', 'option', -76.2422),
        'mapa_zoom'  => (int) yuniorrojas_field('mapa_zoom', 'option', 17),
        'horarios'    => yuniorrojas_field('horarios', 'option', array(
            array('dia' => 'Lun – Vie', 'hora' => '10:00 am – 9:00 pm'),
            array('dia' => 'Sábado', 'hora' => '9:00 am – 8:00 pm'),
            array('dia' => 'Domingo', 'hora' => 'Cerrado'),
        )),
    );
}

/**
 * Redes sociales del footer (lista dinámica desde Contacto JR).
 *
 * @return array<int, array{nombre:string,url:string,icono:string}>
 */
function yuniorrojas_redes(): array
{
    if (function_exists('yuniorrojas_obtener_contacto_settings')) {
        $settings = yuniorrojas_obtener_contacto_settings();
        $redes    = isset($settings['redes']) && is_array($settings['redes']) ? $settings['redes'] : array();

        return array_values(array_filter($redes, static function (array $red): bool {
            $url = trim((string) ($red['url'] ?? ''));
            return $url !== '' && $url !== '#';
        }));
    }

    if (function_exists('yuniorrojas_redes_defaults')) {
        return array_values(array_filter(yuniorrojas_redes_defaults(), static function (array $red): bool {
            $url = trim((string) ($red['url'] ?? ''));
            return $url !== '' && $url !== '#';
        }));
    }

    return array();
}

/**
 * Eslogan de marca unificado.
 */
function yuniorrojas_eslogan(): string
{
    return (string) yuniorrojas_field('eslogan', 'option', 'Presencia en cada detalle.');
}

/**
 * Busca página por plantilla.
 */
function yuniorrojas_pagina_por_plantilla(string $template): ?WP_Post
{
    $pages = get_posts(array(
        'post_type'      => 'page',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'meta_key'       => '_wp_page_template',
        'meta_value'     => $template,
    ));

    return $pages[0] ?? null;
}

/**
 * Parte el título del hero: línea principal (blanco) + acento (oro).
 * Ej.: "Elegancia masculina con propósito" → principal / "con propósito".
 *
 * @return array{principal:string,acento:string}
 */
function yuniorrojas_partir_titulo_hero(string $titulo): array
{
    $titulo = trim(preg_replace('/\s+/u', ' ', $titulo) ?? $titulo);

    if ($titulo === '') {
        return array(
            'principal' => 'Elegancia masculina',
            'acento'    => 'con propósito',
        );
    }

    if (preg_match('/^(.*?)\s+(con\s+.+)$/iu', $titulo, $m)) {
        return array(
            'principal' => trim($m[1]),
            'acento'    => trim($m[2]),
        );
    }

    return array(
        'principal' => $titulo,
        'acento'    => '',
    );
}

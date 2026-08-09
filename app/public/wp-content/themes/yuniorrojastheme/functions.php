<?php
/**
 * Tema Junior Rojas — bootstrap.
 */

if (!defined('ABSPATH')) {
    exit;
}

$yuniorrojas_includes = array(
    'includes/constants.php',
    'includes/helpers.php',
    'includes/post-types.php',
    'includes/servicio-meta.php',
    'includes/metabox-procesos.php',
    'includes/metabox-galeria.php',
    'includes/metabox-imagen-perfil.php',
    'includes/metabox-especialidades.php',
    'includes/metabox-horario-barbero.php',
    'includes/queries.php',
    'includes/reservas-service.php',
    'includes/disponibilidad-service.php',
    'includes/admin-reservas.php',
    'includes/metabox-reserva.php',
    'includes/admin-ingresos.php',
    'includes/reservas-notificaciones.php',
    'includes/admin-notificaciones.php',
    'includes/admin-acciones.php',
    'includes/admin-agenda.php',
    'includes/admin-pagos.php',
    'includes/admin-clientes.php',
    'includes/admin-bloqueos.php',
    'includes/admin-productos.php',
    'includes/fidelidad.php',
    'includes/lista-espera.php',
    'includes/culqi-service.php',
    'includes/medios-pago-service.php',
    'includes/admin-medios-pago.php',
    'includes/settings-pagos.php',
    'includes/rest-galeria.php',
    'includes/rest-servicios.php',
    'includes/rest-reservas.php',
    'includes/servicio-resenas.php',
    'includes/prod-hardening.php',
    'includes/widgets.php',
    'includes/contacto.php',
    'includes/settings-contacto.php',
);

foreach ($yuniorrojas_includes as $yuniorrojas_file) {
    $yuniorrojas_path = get_template_directory() . '/' . $yuniorrojas_file;
    if (file_exists($yuniorrojas_path)) {
        require_once $yuniorrojas_path;
    }
}

function yuniorrojas_menus(): void
{
    register_nav_menus(array(
        'menu-principal'  => __('Menu Principal', YUNIORROJAS_TEXT_DOMAIN),
        'menu-secundario' => __('Menu Secundario', YUNIORROJAS_TEXT_DOMAIN),
    ));
}
add_action('after_setup_theme', 'yuniorrojas_menus');

function yuniorrojas_theme_setup(): void
{
    load_theme_textdomain(YUNIORROJAS_TEXT_DOMAIN, get_template_directory() . '/languages');

    add_theme_support('post-thumbnails');
    add_theme_support('title-tag');
    add_theme_support('html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption'));
    add_theme_support('custom-logo', array(
        'height'      => 100,
        'width'       => 400,
        'flex-height' => true,
        'flex-width'  => true,
    ));
}
add_action('after_setup_theme', 'yuniorrojas_theme_setup');

/**
 * Assets del front (locales).
 */
function yuniorrojas_scripts_styles(): void
{
    $theme_uri  = get_template_directory_uri();
    $theme_path = get_template_directory();

    $normalize_path = $theme_path . '/assets/vendor/normalize/normalize.css';
    $icons_path     = $theme_path . '/assets/vendor/tabler-icons/tabler-icons.min.css';
    $style_path     = get_stylesheet_directory() . '/style.css';
    $script_path    = $theme_path . '/js/main.js';
    $script_deps    = array();

    wp_enqueue_style(
        'normalize',
        $theme_uri . '/assets/vendor/normalize/normalize.css',
        array(),
        file_exists($normalize_path) ? (string) filemtime($normalize_path) : '8.0.1'
    );

    wp_enqueue_style(
        'tabler-icons',
        $theme_uri . '/assets/vendor/tabler-icons/tabler-icons.min.css',
        array(),
        file_exists($icons_path) ? (string) filemtime($icons_path) : '3.19.0'
    );

    wp_enqueue_style(
        'yuniorrojas-style',
        get_stylesheet_uri(),
        array('normalize', 'tabler-icons'),
        file_exists($style_path) ? (string) filemtime($style_path) : '1.0.0'
    );

    if (is_front_page()) {
        wp_enqueue_style(
            'swiper',
            'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css',
            array(),
            '11.2.6'
        );

        wp_enqueue_script(
            'swiper',
            'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js',
            array(),
            '11.2.6',
            true
        );

        $script_deps[] = 'swiper';
    }

    if (is_page_template('page-reservar.php') || is_page('reservar')) {
        wp_enqueue_script(
            'html2canvas',
            'https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js',
            array(),
            '1.4.1',
            true
        );
        $script_deps[] = 'html2canvas';
    }

    $culqi_enabled = function_exists('yuniorrojas_culqi_esta_configurado') && yuniorrojas_culqi_esta_configurado();
    if ($culqi_enabled && (is_page_template('page-reservar.php') || is_page('reservar'))) {
        // Checkout Culqi v4 — tokenización segura en PCI de Culqi.
        wp_enqueue_script(
            'culqi-checkout',
            'https://checkout.culqi.com/js/v4',
            array(),
            '4.0.0',
            true
        );
        $script_deps[] = 'culqi-checkout';
    }

    wp_enqueue_script(
        'yuniorrojas-scripts',
        $theme_uri . '/js/main.js',
        $script_deps,
        file_exists($script_path) ? (string) filemtime($script_path) : '1.0.0',
        true
    );
    // WP 6.3+: carga diferida sin bloquear el render.
    wp_script_add_data('yuniorrojas-scripts', 'strategy', 'defer');

    $pago_alt = function_exists('yuniorrojas_datos_pago_alternativo')
        ? yuniorrojas_datos_pago_alternativo()
        : array();

    $productos_checkout = function_exists('yuniorrojas_productos_checkout_lista')
        ? yuniorrojas_productos_checkout_lista()
        : array();

    wp_localize_script('yuniorrojas-scripts', 'yuniorrojasTheme', array(
        'restGaleria'        => esc_url_raw(rest_url('yuniorrojas/v1/galeria')),
        'restServicios'      => esc_url_raw(rest_url('yuniorrojas/v1/listado-servicios')),
        'restReservas'       => esc_url_raw(rest_url('yuniorrojas/v1/reservas')),
        'restDisponibilidad' => esc_url_raw(rest_url('yuniorrojas/v1/disponibilidad')),
        'restListaEspera'    => esc_url_raw(rest_url('yuniorrojas/v1/lista-espera')),
        'restPreferencias'   => esc_url_raw(rest_url('yuniorrojas/v1/cuenta/preferencias')),
        'restAvatar'         => esc_url_raw(rest_url('yuniorrojas/v1/cuenta/avatar')),
        'restResenasBase'    => esc_url_raw(rest_url('yuniorrojas/v1/servicios')),
        'restResenaLikeBase' => esc_url_raw(rest_url('yuniorrojas/v1/resenas')),
        'restNonce'          => wp_create_nonce('wp_rest'),
        'isCliente'          => function_exists('yuniorrojas_es_cliente') && yuniorrojas_es_cliente(),
        'loginUrl'           => esc_url_raw(yuniorrojas_url_login()),
        'homeUrl'            => esc_url_raw(home_url('/')),
        'cuentaUrl'          => esc_url_raw(yuniorrojas_url_cuenta()),
        'reservarUrl'        => esc_url_raw(yuniorrojas_url_reservar()),
        'serviciosUrl'       => esc_url_raw(yuniorrojas_url_servicios()),
        'siteName'           => (string) get_bloginfo('name'),
        'isLoggedIn'         => is_user_logged_in(),
        'userId'             => is_user_logged_in() ? (int) get_current_user_id() : 0,
        'userEmail'          => is_user_logged_in() ? (string) wp_get_current_user()->user_email : '',
        'mediosPago'         => function_exists('yuniorrojas_medios_pago_checkout_js')
            ? yuniorrojas_medios_pago_checkout_js()
            : array(),
        'productos'          => $productos_checkout,
        'culqi'              => array(
            'enabled'   => $culqi_enabled,
            'publicKey' => $culqi_enabled ? yuniorrojas_culqi_public_key() : '',
            'isTest'    => $culqi_enabled && yuniorrojas_culqi_es_test(),
            'currency'  => 'PEN',
        ),
        'pagos'              => array(
            'yapeTitular' => (string) ($pago_alt['yape_titular'] ?? ''),
            'banco'       => !empty($pago_alt['tiene_transferencia']),
        ),
    ));

    if (is_page_template('page-contacto.php') || is_page('contacto')) {
        $contacto   = yuniorrojas_contacto();
        $mapa_js    = $theme_path . '/js/mapa-contacto.js';

        wp_enqueue_style(
            'leaflet',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
            array(),
            '1.9.4'
        );

        wp_enqueue_script(
            'leaflet',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
            array(),
            '1.9.4',
            true
        );

        wp_enqueue_script(
            'yuniorrojas-mapa-contacto',
            $theme_uri . '/js/mapa-contacto.js',
            array('leaflet'),
            file_exists($mapa_js) ? (string) filemtime($mapa_js) : '1.0.0',
            true
        );

        wp_localize_script('yuniorrojas-mapa-contacto', 'yuniorrojasMapa', array(
            'lat'       => (float) $contacto['mapa_lat'],
            'lng'       => (float) $contacto['mapa_lng'],
            'zoom'      => (int) $contacto['mapa_zoom'],
            'titulo'    => (string) get_bloginfo('name'),
            'direccion' => (string) $contacto['direccion'],
        ));
    }
}
add_action('wp_enqueue_scripts', 'yuniorrojas_scripts_styles');

/**
 * Assets admin: Servicios (proceso/galería), Barberos y página Contacto JR.
 */
function yuniorrojas_admin_assets(string $hook): void
{
    $es_contacto = ($hook === 'toplevel_page_yuniorrojas-contacto');
    $es_cpt      = in_array($hook, array('post.php', 'post-new.php'), true);

    if (!$es_contacto && !$es_cpt) {
        return;
    }

    if ($es_cpt) {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        $tipo   = $screen && !empty($screen->post_type) ? $screen->post_type : '';

        if ($tipo === '' && isset($_GET['post_type'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $tipo = sanitize_key(wp_unslash($_GET['post_type'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        }

        // En post.php solo viene ?post=ID (sin post_type).
        if ($tipo === '' && isset($_GET['post'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $tipo = (string) get_post_type(absint($_GET['post'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        }

        $permitidos = array(
            YUNIORROJAS_CPT_SERVICIOS,
            'barberos',
            YUNIORROJAS_CPT_RESERVAS,
            YUNIORROJAS_CPT_MEDIOS_PAGO,
        );
        if (!in_array($tipo, $permitidos, true)) {
            return;
        }
    }

    $theme_uri  = get_template_directory_uri();
    $theme_path = get_template_directory();
    $css_path   = $theme_path . '/assets/admin/admin.css';
    $js_path    = $theme_path . '/assets/admin/admin.js';

    if ($es_cpt) {
        wp_enqueue_media();
    }

    wp_enqueue_script('jquery-ui-sortable');

    wp_enqueue_style(
        'yuniorrojas-admin',
        $theme_uri . '/assets/admin/admin.css',
        array(),
        file_exists($css_path) ? (string) filemtime($css_path) : '1.0.0'
    );

    wp_enqueue_script(
        'yuniorrojas-admin',
        $theme_uri . '/assets/admin/admin.js',
        array('jquery', 'jquery-ui-sortable'),
        file_exists($js_path) ? (string) filemtime($js_path) : '1.0.0',
        true
    );
}
add_action('admin_enqueue_scripts', 'yuniorrojas_admin_assets');

/**
 * Clases de enlaces del menú.
 * Menú secundario header: outline / sólido (btn-reservar).
 * Footer: solo “Reservar Ahora” como botón sólido.
 */
function yuniorrojas_menu_link_attributes($atts, $item, $args, $depth)
{
    if (empty($args->menu_class)) {
        return $atts;
    }

    $menu_class   = (string) $args->menu_class;
    $item_classes = isset($item->classes) ? (array) $item->classes : array();
    $item_title   = isset($item->title) ? (string) $item->title : '';
    $is_button    = in_array('btn-reservar', $item_classes, true);
    $is_reservar  = $is_button || (bool) preg_match('/reservar/i', $item_title);

    if (str_contains($menu_class, 'header__actions')) {
        $atts['class'] = $is_reservar
            ? 'header__menu-link header__menu-link--button'
            : 'header__menu-link header__menu-link--outline';
        return $atts;
    }

    if ($is_button && str_contains($menu_class, 'header__')) {
        $atts['class'] = 'header__menu-link header__menu-link--button';
        return $atts;
    }

    if (str_contains($menu_class, 'header__')) {
        $atts['class'] = 'header__menu-link';
        return $atts;
    }

    if (str_contains($menu_class, 'footer__')) {
        $atts['class'] = $is_reservar
            ? 'footer__menu-link footer__menu-link--button'
            : 'footer__menu-link';
    }

    return $atts;
}
add_filter('nav_menu_link_attributes', 'yuniorrojas_menu_link_attributes', 10, 4);

/**
 * Oculta ítems de menú de “Reservar” cuando el visitante es administrador.
 *
 * @param WP_Post[] $items
 * @return WP_Post[]
 */
function yuniorrojas_ocultar_menu_reservar_admin(array $items): array
{
    if (yuniorrojas_puede_reservar_en_front()) {
        return $items;
    }

    $reservar_url = yuniorrojas_url_reservar();
    $filtrados    = array();

    foreach ($items as $item) {
        $title   = isset($item->title) ? (string) $item->title : '';
        $url     = isset($item->url) ? (string) $item->url : '';
        $classes = isset($item->classes) ? (array) $item->classes : array();
        $es_btn  = in_array('btn-reservar', $classes, true);
        $es_titulo = (bool) preg_match('/reservar|agendar/i', $title);
        $es_url    = $url !== '' && (
            str_contains($url, '/reservar')
            || ($reservar_url !== '' && str_contains($url, (string) wp_parse_url($reservar_url, PHP_URL_PATH)))
        );

        if ($es_btn || $es_titulo || $es_url) {
            continue;
        }

        $filtrados[] = $item;
    }

    return $filtrados;
}
add_filter('wp_nav_menu_objects', 'yuniorrojas_ocultar_menu_reservar_admin', 20);

/**
 * Clase de body para pantallas de autenticación (login / registro).
 *
 * @param string[] $classes
 * @return string[]
 */
function yuniorrojas_auth_body_class(array $classes): array
{
    if (is_page('iniciar-sesion') || is_page_template('page-iniciar-sesion.php')) {
        $classes[] = 'auth-page';
        $classes[] = 'page-iniciar-sesion';
    }

    if (is_page('registro') || is_page_template('page-registro.php')) {
        $classes[] = 'auth-page';
        $classes[] = 'page-registro';
    }

    if (is_page('recuperar-clave') || is_page_template('page-recuperar-clave.php')) {
        $classes[] = 'auth-page';
        $classes[] = 'page-recuperar-clave';
    }

    if (is_page('mi-cuenta') || is_page_template('page-mi-cuenta.php')) {
        $classes[] = 'auth-page';
        $classes[] = 'cliente-cuenta';
        $classes[] = 'page-mi-cuenta';
    }

    return $classes;
}
add_filter('body_class', 'yuniorrojas_auth_body_class');

/**
 * Usa la foto de perfil del cliente en get_avatar / get_avatar_url (header, etc.).
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
 * Asegura plantillas auth en páginas iniciar-sesion, registro, recuperar y mi-cuenta.
 */
function yuniorrojas_asignar_plantillas_auth(): void
{
    $map = array(
        'iniciar-sesion' => array(
            'template' => 'page-iniciar-sesion.php',
            'title'    => 'Iniciar Sesión',
        ),
        'registro' => array(
            'template' => 'page-registro.php',
            'title'    => 'Registro',
        ),
        'recuperar-clave' => array(
            'template' => 'page-recuperar-clave.php',
            'title'    => 'Recuperar contraseña',
        ),
        'mi-cuenta' => array(
            'template' => 'page-mi-cuenta.php',
            'title'    => 'Mi Cuenta',
        ),
    );

    foreach ($map as $slug => $config) {
        $page = get_page_by_path($slug);

        if (!$page instanceof WP_Post) {
            $page_id = wp_insert_post(
                array(
                    'post_title'  => $config['title'],
                    'post_name'   => $slug,
                    'post_status' => 'publish',
                    'post_type'   => 'page',
                    'post_author' => 1,
                ),
                true
            );

            if (is_wp_error($page_id) || !$page_id) {
                continue;
            }

            $page = get_post((int) $page_id);
        }

        if (!$page instanceof WP_Post) {
            continue;
        }

        $actual = (string) get_post_meta($page->ID, '_wp_page_template', true);
        if ($actual !== $config['template']) {
            update_post_meta($page->ID, '_wp_page_template', $config['template']);
        }
    }
}
add_action('init', 'yuniorrojas_asignar_plantillas_auth', 30);

/**
 * WordPress debe apuntar login/registro a las páginas de cliente del tema.
 *
 * @param string $login_url    URL por defecto.
 * @param string $redirect     Destino opcional.
 * @param bool   $force_reauth Forzar reautenticación.
 */
function yuniorrojas_filtrar_login_url(string $login_url, string $redirect = '', bool $force_reauth = false): string
{
    // Acceso al panel WP: conservar wp-login.php.
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
 * El correo de recuperación debe abrir la página cliente, no wp-login.php.
 *
 * @param string  $message    Mensaje HTML/texto.
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
 * Si alguien entra a wp-login.php (registro/login/recuperar), redirigir al front de cliente.
 * Excepciones: logout y acceso a wp-admin.
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

        // Cookie legacy de WP (rp_ + cookiehash).
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


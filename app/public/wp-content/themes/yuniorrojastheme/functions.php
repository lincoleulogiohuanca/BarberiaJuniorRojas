<?php
/**
 * BarberFlow Theme — presentation only (UI).
 *
 * Platform modules: BarberFlow Core, BarberFlow Book, BarberFlow Payments, BarberFlow Pro.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Aviso + guard si falta el Core.
 */
function yuniorrojas_theme_require_core(): void
{
    if (defined('JUNIORROJAS_DOMAIN_LOADED') && JUNIORROJAS_DOMAIN_LOADED && defined('JUNIORROJAS_RESERVAS_LOADED') && JUNIORROJAS_RESERVAS_LOADED && defined('JUNIORROJAS_PAGOS_LOADED') && JUNIORROJAS_PAGOS_LOADED && defined('JUNIORROJAS_CORE_LOADED') && JUNIORROJAS_CORE_LOADED) {
        return;
    }

    add_action('admin_notices', static function (): void {
        if (!current_user_can('activate_plugins')) {
            return;
        }
        echo '<div class="notice notice-error"><p><strong>BarberFlow Theme</strong> necesita <strong>BarberFlow Core</strong>, <strong>BarberFlow Book</strong>, <strong>BarberFlow Payments</strong> y <strong>BarberFlow Pro</strong> activos.</p></div>';
    });

    // Minimal constants so theme setup does not fatal without Core.
    if (!defined('YUNIORROJAS_TEXT_DOMAIN')) {
        define('YUNIORROJAS_TEXT_DOMAIN', 'yuniorrojastheme');
    }
}
yuniorrojas_theme_require_core();

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
 * Logo monograma opcional en el Customizer (footer / iconos).
 * El logo principal del header se controla con "Logo" de Identidad del sitio.
 */
function yuniorrojas_customize_branding(WP_Customize_Manager $wp_customize): void
{
    $wp_customize->add_setting(
        'yuniorrojas_logo_mark',
        array(
            'default'           => 0,
            'sanitize_callback' => 'absint',
            'transport'         => 'refresh',
        )
    );

    $wp_customize->add_control(
        new WP_Customize_Media_Control(
            $wp_customize,
            'yuniorrojas_logo_mark',
            array(
                'label'       => __('Monograma / icono', YUNIORROJAS_TEXT_DOMAIN),
                'description' => __('Opcional. Se usa en footer y lugares compactos. Si no hay monograma, se reutiliza el Logo del sitio.', YUNIORROJAS_TEXT_DOMAIN),
                'section'     => 'title_tagline',
                'mime_type'   => 'image',
                'priority'    => 9,
            )
        )
    );
}
add_action('customize_register', 'yuniorrojas_customize_branding');

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

    // Fuentes locales-first vía Google con preconnect (sin @import en CSS).
    wp_enqueue_style(
        'yuniorrojas-fonts',
        'https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600;700&family=Montserrat:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap',
        array(),
        null
    );

    wp_enqueue_style(
        'yuniorrojas-style',
        get_stylesheet_uri(),
        array('normalize', 'tabler-icons', 'yuniorrojas-fonts'),
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
        wp_enqueue_script(
            'culqi-checkout',
            'https://checkout.culqi.com/js/v4',
            array(),
            '4.0.0',
            true
        );
        $script_deps[] = 'culqi-checkout';
    }

    // Módulos front (ex-main.js monólito).
    $modules = array(
        'header-menu' => true,
        'faq'         => true,
        'testimonios' => is_front_page(),
        'galeria'     => is_page_template('page-galeria.php') || is_page('galeria') || is_post_type_archive('galeria'),
        'servicios'   => is_page_template('page-listado-servicios.php')
            || is_page('servicios')
            || is_post_type_archive(YUNIORROJAS_CPT_SERVICIOS),
        'reservar'    => is_page_template('page-reservar.php') || is_page('reservar'),
        'auth'        => is_page('iniciar-sesion')
            || is_page('registro')
            || is_page('recuperar-clave')
            || is_page_template('page-iniciar-sesion.php')
            || is_page_template('page-registro.php')
            || is_page_template('page-recuperar-clave.php'),
        'cuenta'      => is_page('mi-cuenta') || is_page_template('page-mi-cuenta.php'),
        'resenas'     => is_singular(YUNIORROJAS_CPT_SERVICIOS),
        'scroll-top'  => true,
    );

    $last_handle = '';
    $localize_handle = '';
    foreach ($modules as $mod => $load) {
        if (!$load) {
            continue;
        }
        $mod_path = $theme_path . '/js/modules/' . $mod . '.js';
        if (!file_exists($mod_path)) {
            continue;
        }
        $handle = 'yuniorrojas-' . $mod;
        $deps   = array();
        // Deps CDN solo donde se usan.
        if ($mod === 'reservar') {
            $deps = $script_deps;
        } elseif ($mod === 'testimonios' && in_array('swiper', $script_deps, true)) {
            $deps[] = 'swiper';
        }
        if ($last_handle !== '') {
            $deps[] = $last_handle;
        }
        wp_enqueue_script(
            $handle,
            $theme_uri . '/js/modules/' . $mod . '.js',
            $deps,
            (string) filemtime($mod_path),
            true
        );
        wp_script_add_data($handle, 'strategy', 'defer');
        if ($localize_handle === '') {
            $localize_handle = $handle;
        }
        $last_handle = $handle;
    }

    // Fallback legacy si no hay módulos.
    if ($last_handle === '') {
        wp_enqueue_script(
            'yuniorrojas-scripts',
            $theme_uri . '/js/main.js',
            $script_deps,
            file_exists($script_path) ? (string) filemtime($script_path) : '1.0.0',
            true
        );
        wp_script_add_data('yuniorrojas-scripts', 'strategy', 'defer');
        $localize_handle = 'yuniorrojas-scripts';
    }

    $pago_alt = function_exists('yuniorrojas_datos_pago_alternativo')
        ? yuniorrojas_datos_pago_alternativo()
        : array();

    $productos_checkout = function_exists('yuniorrojas_productos_checkout_lista')
        ? yuniorrojas_productos_checkout_lista()
        : array();

    $fidelidad_pct = 0;
    if (is_user_logged_in() && function_exists('yuniorrojas_fidelidad_descuento_pct_usuario')) {
        $fidelidad_pct = yuniorrojas_fidelidad_descuento_pct_usuario((int) get_current_user_id());
    }

    wp_localize_script($localize_handle, 'yuniorrojasTheme', array(
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
        'fidelidadDescuento' => $fidelidad_pct,
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

    // header-menu siempre debe existir para localize en páginas sin el primero si falla
    // (ya cubierto).
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
 * Preconnect a orígenes de fuentes/CDN + SRI en scripts de terceros.
 */
function yuniorrojas_resource_hints(array $urls, string $relation_type): array
{
    if ($relation_type === 'preconnect') {
        $urls[] = array(
            'href' => 'https://fonts.googleapis.com',
            'crossorigin' => 'anonymous',
        );
        $urls[] = array(
            'href' => 'https://fonts.gstatic.com',
            'crossorigin' => 'anonymous',
        );
    }
    return $urls;
}
add_filter('wp_resource_hints', 'yuniorrojas_resource_hints', 10, 2);

/**
 * crossorigin anónimo en scripts de CDN.
 *
 * @param string $tag    Tag HTML.
 * @param string $handle Handle WP.
 * @param string $src    URL.
 */
function yuniorrojas_script_sri(string $tag, string $handle, string $src): string
{
    unset($handle);
    if (
        (str_contains($src, 'cdn.jsdelivr.net') || str_contains($src, 'unpkg.com'))
        && !str_contains($tag, 'crossorigin')
    ) {
        $tag = str_replace(' src', ' crossorigin="anonymous" src', $tag);
    }
    return $tag;
}
add_filter('script_loader_tag', 'yuniorrojas_script_sri', 10, 3);

/**
 * Clases de enlaces del menú.
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
    if (!function_exists('yuniorrojas_puede_reservar_en_front') || yuniorrojas_puede_reservar_en_front()) {
        return $items;
    }

    $reservar_url = function_exists('yuniorrojas_url_reservar') ? yuniorrojas_url_reservar() : '';
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
 * Clase de body para pantallas de autenticación / cuenta.
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
 * Asegura plantillas auth en páginas del tema.
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
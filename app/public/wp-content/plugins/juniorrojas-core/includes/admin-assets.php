<?php
/**
 * Assets admin compartidos (CPTs JR y Contacto).
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Encola CSS/JS admin en pantallas del dominio.
 */
function yuniorrojas_admin_assets(string $hook): void
{
    $es_contacto = ($hook === 'toplevel_page_yuniorrojas-contacto');
    $es_edit     = in_array($hook, array('post.php', 'post-new.php'), true);
    $es_list     = ($hook === 'edit.php');

    if (!$es_contacto && !$es_edit && !$es_list) {
        return;
    }

    $tipo = '';
    if ($es_edit || $es_list) {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        $tipo   = $screen && !empty($screen->post_type) ? (string) $screen->post_type : '';

        if ($tipo === '' && isset($_GET['post_type'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $tipo = sanitize_key(wp_unslash((string) $_GET['post_type'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        }

        if ($tipo === '' && $es_edit && isset($_GET['post'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $tipo = (string) get_post_type(absint($_GET['post'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        }

        $permitidos = array(
            YUNIORROJAS_CPT_SERVICIOS,
            'barberos',
            YUNIORROJAS_CPT_RESERVAS,
            YUNIORROJAS_CPT_MEDIOS_PAGO,
            defined('YUNIORROJAS_CPT_PRODUCTOS') ? YUNIORROJAS_CPT_PRODUCTOS : 'jr_productos',
        );
        if (!in_array($tipo, $permitidos, true)) {
            return;
        }
    }

    $css_path = jr_core_asset_path('assets/admin/admin.css');
    $js_path  = jr_core_asset_path('assets/admin/admin.js');
    $mapa_js  = jr_core_asset_path('assets/admin/mapa-contacto-admin.js');

    if ($es_edit || $es_contacto) {
        if ($es_edit) {
            wp_enqueue_media();
        }
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script(
            'yuniorrojas-admin',
            jr_core_asset_url('assets/admin/admin.js'),
            array('jquery', 'jquery-ui-sortable'),
            file_exists($js_path) ? (string) filemtime($js_path) : JR_CORE_VERSION,
            true
        );
    }

    if ($es_contacto) {
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
            'yuniorrojas-mapa-contacto-admin',
            jr_core_asset_url('assets/admin/mapa-contacto-admin.js'),
            array('leaflet'),
            file_exists($mapa_js) ? (string) filemtime($mapa_js) : JR_CORE_VERSION,
            true
        );
    }

    wp_enqueue_style(
        'yuniorrojas-admin',
        jr_core_asset_url('assets/admin/admin.css'),
        $es_contacto ? array('leaflet') : array(),
        file_exists($css_path) ? (string) filemtime($css_path) : JR_CORE_VERSION
    );
}
add_action('admin_enqueue_scripts', 'yuniorrojas_admin_assets');
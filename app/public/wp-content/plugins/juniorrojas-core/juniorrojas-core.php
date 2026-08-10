<?php
/**
 * Plugin Name: BarberFlow Pro
 * Plugin URI: https://github.com/lincoleulogiohuanca/BarberiaJuniorRojas
 * Description: Premium BarberFlow: agenda, CRM clientes, Gallery/Reviews admin, dashboard, contacto. Requiere Core + Book + Payments.
 * Version: 1.5.0
 * Author: Lincol Eulogio Huanca
 * Author URI: https://x.com/LincolEulogio
 * Text Domain: juniorrojas-core
 * Requires at least: 6.1
 * Requires PHP: 8.0
 * Requires Plugins: juniorrojas-domain, juniorrojas-reservas, juniorrojas-pagos
 */

if (!defined('ABSPATH')) {
    exit;
}

define('JR_CORE_VERSION', '1.5.0');
define('JR_CORE_FILE', __FILE__);
define('JR_CORE_PATH', plugin_dir_path(__FILE__));
define('JR_CORE_URL', plugin_dir_url(__FILE__));
define('JUNIORROJAS_CORE_LOADED', true);

/**
 * Domain activo.
 */
function jr_core_domain_ready(): bool
{
    return defined('JUNIORROJAS_DOMAIN_LOADED')
        && JUNIORROJAS_DOMAIN_LOADED
        && defined('JR_DOMAIN_MODULES_LOADED')
        && JR_DOMAIN_MODULES_LOADED;
}

/**
 * Motor de reservas activo.
 */
function jr_core_reservas_ready(): bool
{
    return defined('JUNIORROJAS_RESERVAS_LOADED')
        && JUNIORROJAS_RESERVAS_LOADED
        && defined('JR_RESERVAS_MODULES_LOADED')
        && JR_RESERVAS_MODULES_LOADED;
}

/**
 * Borde de pagos activo.
 */
function jr_core_pagos_ready(): bool
{
    return defined('JUNIORROJAS_PAGOS_LOADED')
        && JUNIORROJAS_PAGOS_LOADED
        && defined('JR_PAGOS_MODULES_LOADED')
        && JR_PAGOS_MODULES_LOADED;
}

/**
 * Stack mínimo listo.
 */
function jr_core_stack_ready(): bool
{
    return jr_core_domain_ready() && jr_core_reservas_ready() && jr_core_pagos_ready();
}

/**
 * Aviso dependencias.
 */
function jr_core_admin_notice_deps_missing(): void
{
    if (!current_user_can('activate_plugins')) {
        return;
    }
    $faltan = array();
    if (!jr_core_domain_ready()) {
        $faltan[] = 'BarberFlow Core';
    }
    if (!jr_core_reservas_ready()) {
        $faltan[] = 'BarberFlow Book';
    }
    if (!jr_core_pagos_ready()) {
        $faltan[] = 'BarberFlow Payments';
    }
    if ($faltan === array()) {
        return;
    }
    echo '<div class="notice notice-error"><p><strong>BarberFlow Pro</strong> requiere activos: <strong>'
        . esc_html(implode('</strong>, <strong>', $faltan))
        . '</strong>.</p></div>';
}

/**
 * Arranque app (tras Pagos prio 15).
 */
function jr_core_boot(): void
{
    if (!jr_core_stack_ready()) {
        add_action('admin_notices', 'jr_core_admin_notice_deps_missing');
        return;
    }

    require_once JR_CORE_PATH . 'includes/loader.php';
    jr_core_load_app();
    require_once JR_CORE_PATH . 'includes/bootstrap-domain.php';
}
add_action('plugins_loaded', 'jr_core_boot', 25);

/**
 * Activación: schema + rewrite (no borra datos).
 */
function jr_core_activate(): void
{
    if (!function_exists('jr_db_install_schema')) {
        $domain_main = WP_PLUGIN_DIR . '/juniorrojas-domain/juniorrojas-domain.php';
        if (is_readable($domain_main)) {
            include_once $domain_main;
        }
    }

    if (function_exists('jr_db_install_schema')) {
        jr_db_install_schema();
        update_option('jr_core_db_version', JR_CORE_VERSION, false);
    }

    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'jr_core_activate');

/**
 * Desactivar: limpia rewrite (no borra datos).
 */
function jr_core_deactivate(): void
{
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'jr_core_deactivate');

/**
 * Aviso tema UI.
 */
function jr_core_admin_notice_theme(): void
{
    if (!current_user_can('activate_plugins') || !jr_core_stack_ready()) {
        return;
    }
    $theme = wp_get_theme();
    $ok    = $theme->get_stylesheet() === 'yuniorrojastheme'
        || $theme->get_template() === 'yuniorrojastheme';
    if ($ok) {
        return;
    }
    echo '<div class="notice notice-warning"><p><strong>BarberFlow Pro</strong> est&aacute; activo; se recomienda el tema <strong>BarberFlow Theme</strong> (<code>yuniorrojastheme</code>) para la UI de cliente.</p></div>';
}
add_action('admin_notices', 'jr_core_admin_notice_theme');

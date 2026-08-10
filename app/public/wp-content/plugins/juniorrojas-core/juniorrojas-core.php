<?php
/**
 * Plugin Name: Junior Rojas Core
 * Plugin URI: https://github.com/lincoleulogiohuanca/BarberiaJuniorRojas
 * Description: Dominio de negocio (reservas, pagos Culqi, admin, REST, tablas). El tema yuniorrojastheme solo presenta la UI pública.
 * Version: 1.2.0
 * Author: Lincol Eulogio Huanca
 * Text Domain: juniorrojas-core
 * Requires at least: 6.1
 * Requires PHP: 8.0
 */

if (!defined('ABSPATH')) {
    exit;
}

define('JR_CORE_VERSION', '1.2.0');
define('JR_CORE_FILE', __FILE__);
define('JR_CORE_PATH', plugin_dir_path(__FILE__));
define('JR_CORE_URL', plugin_dir_url(__FILE__));
define('JUNIORROJAS_CORE_LOADED', true);

require_once JR_CORE_PATH . 'includes/db.php';
require_once JR_CORE_PATH . 'includes/loader.php';
require_once JR_CORE_PATH . 'includes/bootstrap-domain.php';
require_once JR_CORE_PATH . 'includes/culqi-webhook.php';

/**
 * Activación: tablas + dbDelta + versión schema.
 */
function jr_core_activate(): void
{
    jr_db_install_schema();
    update_option('jr_core_db_version', JR_CORE_VERSION, false);
    if (function_exists('jr_db_backfill_reservas')) {
        jr_db_backfill_reservas(500);
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
 * Asegura schema al cargar si faltó activate (deploy FTP).
 */
function jr_core_maybe_upgrade_db(): void
{
    $v = (string) get_option('jr_core_db_version', '');
    if ($v !== JR_CORE_VERSION) {
        jr_db_install_schema();
        update_option('jr_core_db_version', JR_CORE_VERSION, false);
    }
}
add_action('plugins_loaded', 'jr_core_maybe_upgrade_db', 5);

/**
 * Aviso si el tema de UI no está activo.
 */
function jr_core_admin_notice_theme(): void
{
    if (!current_user_can('activate_plugins')) {
        return;
    }
    $theme = wp_get_theme();
    $ok    = $theme->get_stylesheet() === 'yuniorrojastheme'
        || $theme->get_template() === 'yuniorrojastheme';
    if ($ok) {
        return;
    }
    echo '<div class="notice notice-warning"><p><strong>Junior Rojas Core</strong> está activo; se recomienda el tema <code>yuniorrojastheme</code> para la UI de cliente.</p></div>';
}
add_action('admin_notices', 'jr_core_admin_notice_theme');
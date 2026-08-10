<?php
/**
 * Plugin Name: Junior Rojas Domain
 * Plugin URI: https://github.com/lincoleulogiohuanca/BarberiaJuniorRojas
 * Description: Cimiento del producto JR: CPT, constants, helpers, metas de catálogo, schema wp_jr_* y queries. Requerido por Junior Rojas Core.
 * Version: 1.0.0
 * Author: Lincol Eulogio Huanca
 * Text Domain: juniorrojas-domain
 * Requires at least: 6.1
 * Requires PHP: 8.0
 */

if (!defined('ABSPATH')) {
    exit;
}

define('JR_DOMAIN_VERSION', '1.0.0');
define('JR_DOMAIN_FILE', __FILE__);
define('JR_DOMAIN_PATH', plugin_dir_path(__FILE__));
define('JR_DOMAIN_URL', plugin_dir_url(__FILE__));
define('JUNIORROJAS_DOMAIN_LOADED', true);

require_once JR_DOMAIN_PATH . 'includes/loader.php';

/**
 * Activación: schema base + rewrite.
 */
function jr_domain_activate(): void
{
    if (function_exists('jr_db_install_schema')) {
        jr_db_install_schema();
    }
    update_option('jr_domain_db_version', JR_DOMAIN_VERSION, false);
    // Compat con Core antiguo que leía jr_core_db_version.
    update_option('jr_core_db_version', JR_DOMAIN_VERSION, false);
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'jr_domain_activate');

/**
 * Desactivar: no borra datos.
 */
function jr_domain_deactivate(): void
{
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'jr_domain_deactivate');

/**
 * dbDelta si la versión del schema no coincide (deploy FTP).
 */
function jr_domain_maybe_upgrade_db(): void
{
    $v = (string) get_option('jr_domain_db_version', '');
    if ($v === JR_DOMAIN_VERSION) {
        return;
    }
    if (function_exists('jr_db_install_schema')) {
        jr_db_install_schema();
    }
    update_option('jr_domain_db_version', JR_DOMAIN_VERSION, false);
    update_option('jr_core_db_version', JR_DOMAIN_VERSION, false);
}
add_action('plugins_loaded', 'jr_domain_maybe_upgrade_db', 5);
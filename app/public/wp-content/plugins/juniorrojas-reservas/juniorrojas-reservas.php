<?php
/**
 * Plugin Name: BarberFlow Book
 * Plugin URI: https://github.com/lincoleulogiohuanca/BarberiaJuniorRojas
 * Description: Reservas BarberFlow (producto Booking): disponibilidad, locks, REST, lista de espera, notificaciones y fidelidad. Requiere BarberFlow Core.
 * Version: 1.0.0
 * Author: Lincol Eulogio Huanca
 * Text Domain: juniorrojas-reservas
 * Requires at least: 6.1
 * Requires PHP: 8.0
 * Requires Plugins: juniorrojas-domain
 */

if (!defined('ABSPATH')) {
    exit;
}

define('JR_RESERVAS_VERSION', '1.0.0');
define('JR_RESERVAS_FILE', __FILE__);
define('JR_RESERVAS_PATH', plugin_dir_path(__FILE__));
define('JR_RESERVAS_URL', plugin_dir_url(__FILE__));
define('JUNIORROJAS_RESERVAS_LOADED', true);

/**
 * Domain listo.
 */
function jr_reservas_domain_ready(): bool
{
    return defined('JUNIORROJAS_DOMAIN_LOADED')
        && JUNIORROJAS_DOMAIN_LOADED
        && defined('JR_DOMAIN_MODULES_LOADED')
        && JR_DOMAIN_MODULES_LOADED;
}

/**
 * Aviso si falta Domain.
 */
function jr_reservas_admin_notice_domain_missing(): void
{
    if (!current_user_can('activate_plugins')) {
        return;
    }
    echo '<div class="notice notice-error"><p><strong>BarberFlow Book</strong> requiere el plugin <strong>BarberFlow Core</strong> activo.</p></div>';
}

/**
 * Carga el motor (tras Domain; Core arranca después).
 */
function jr_reservas_boot(): void
{
    if (!jr_reservas_domain_ready()) {
        add_action('admin_notices', 'jr_reservas_admin_notice_domain_missing');
        return;
    }

    require_once JR_RESERVAS_PATH . 'includes/loader.php';
    jr_reservas_load_modules();

    if (!defined('JR_RESERVAS_MODULES_LOADED')) {
        define('JR_RESERVAS_MODULES_LOADED', true);
    }
}
add_action('plugins_loaded', 'jr_reservas_boot', 10);

/**
 * Activación: schema (Domain) + cron recordatorios + rewrite.
 */
function jr_reservas_activate(): void
{
    if (!function_exists('jr_db_install_schema')) {
        $domain = WP_PLUGIN_DIR . '/juniorrojas-domain/juniorrojas-domain.php';
        if (is_readable($domain)) {
            include_once $domain;
        }
    }

    if (function_exists('jr_db_install_schema')) {
        jr_db_install_schema();
    }

    require_once JR_RESERVAS_PATH . 'includes/loader.php';
    if (function_exists('jr_reservas_load_modules')) {
        jr_reservas_load_modules();
    }

    if (function_exists('yuniorrojas_activar_cron_recordatorios')) {
        yuniorrojas_activar_cron_recordatorios();
    }

    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'jr_reservas_activate');

/**
 * Desactivar: no borra citas ni tablas.
 */
function jr_reservas_deactivate(): void
{
    $timestamp = wp_next_scheduled('yuniorrojas_cron_recordatorios_reservas');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'yuniorrojas_cron_recordatorios_reservas');
    }
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'jr_reservas_deactivate');
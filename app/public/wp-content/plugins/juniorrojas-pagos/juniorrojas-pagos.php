<?php
/**
 * Plugin Name: BarberFlow Payments
 * Plugin URI: https://github.com/lincoleulogiohuanca/BarberiaJuniorRojas
 * Description: Pagos BarberFlow: Culqi, medios, settings, webhook e idempotencia. Requiere BarberFlow Core + BarberFlow Book.
 * Version: 1.0.0
 * Author: Lincol Eulogio Huanca
 * Author URI: https://x.com/LincolEulogio
 * Text Domain: juniorrojas-pagos
 * Requires at least: 6.1
 * Requires PHP: 8.0
 * Requires Plugins: juniorrojas-domain, juniorrojas-reservas
 */

if (!defined('ABSPATH')) {
    exit;
}

define('JR_PAGOS_VERSION', '1.0.0');
define('JR_PAGOS_FILE', __FILE__);
define('JR_PAGOS_PATH', plugin_dir_path(__FILE__));
define('JR_PAGOS_URL', plugin_dir_url(__FILE__));
define('JUNIORROJAS_PAGOS_LOADED', true);

/**
 * Domain listo.
 */
function jr_pagos_domain_ready(): bool
{
    return defined('JUNIORROJAS_DOMAIN_LOADED')
        && JUNIORROJAS_DOMAIN_LOADED
        && defined('JR_DOMAIN_MODULES_LOADED')
        && JR_DOMAIN_MODULES_LOADED;
}

/**
 * Reservas listo (cargos se amarran a citas).
 */
function jr_pagos_reservas_ready(): bool
{
    return defined('JUNIORROJAS_RESERVAS_LOADED')
        && JUNIORROJAS_RESERVAS_LOADED
        && defined('JR_RESERVAS_MODULES_LOADED')
        && JR_RESERVAS_MODULES_LOADED;
}

/**
 * Stack del borde de dinero.
 */
function jr_pagos_stack_ready(): bool
{
    return jr_pagos_domain_ready() && jr_pagos_reservas_ready();
}

/**
 * Aviso dependencias.
 */
function jr_pagos_admin_notice_deps_missing(): void
{
    if (!current_user_can('activate_plugins')) {
        return;
    }
    $faltan = array();
    if (!jr_pagos_domain_ready()) {
        $faltan[] = 'BarberFlow Core';
    }
    if (!jr_pagos_reservas_ready()) {
        $faltan[] = 'BarberFlow Book';
    }
    if ($faltan === array()) {
        return;
    }
    echo '<div class="notice notice-error"><p><strong>BarberFlow Payments</strong> requiere activos: <strong>'
        . esc_html(implode('</strong>, <strong>', $faltan))
        . '</strong>.</p></div>';
}

/**
 * Carga bordes de pago (tras Reservas prio 10).
 */
function jr_pagos_boot(): void
{
    if (!jr_pagos_stack_ready()) {
        add_action('admin_notices', 'jr_pagos_admin_notice_deps_missing');
        return;
    }

    require_once JR_PAGOS_PATH . 'includes/loader.php';
    jr_pagos_load_modules();
}
add_action('plugins_loaded', 'jr_pagos_boot', 15);

/**
 * Activación: asegura schema (idempotencia en Domain) + rewrite.
 */
function jr_pagos_activate(): void
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
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'jr_pagos_activate');

/**
 * Desactivar: no borra pagos ni tablas.
 */
function jr_pagos_deactivate(): void
{
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'jr_pagos_deactivate');

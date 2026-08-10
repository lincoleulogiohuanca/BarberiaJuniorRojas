<?php
/**
 * Módulos de pagos (Culqi, medios, webhook).
 * Idempotencia / tablas: juniorrojas-domain (jr_db_idempotency_*).
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @return list<string>
 */
function jr_pagos_module_files(): array
{
    return array(
        'medios-pago-service.php',
        'culqi-service.php',
        'settings-pagos.php',
        'admin-medios-pago.php',
        'admin-pagos.php',
        'culqi-webhook.php',
    );
}

/**
 * Require de módulos de pago.
 */
function jr_pagos_load_modules(): void
{
    if (defined('JR_PAGOS_MODULES_LOADED') && JR_PAGOS_MODULES_LOADED) {
        return;
    }

    foreach (jr_pagos_module_files() as $file) {
        $path = JR_PAGOS_PATH . 'includes/' . $file;
        if (is_readable($path)) {
            require_once $path;
        }
    }

    if (!defined('JR_PAGOS_MODULES_LOADED')) {
        define('JR_PAGOS_MODULES_LOADED', true);
    }
}
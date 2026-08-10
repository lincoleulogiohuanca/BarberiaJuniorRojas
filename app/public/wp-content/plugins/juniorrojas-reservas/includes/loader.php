<?php
/**
 * Módulos del motor de citas.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @return list<string>
 */
function jr_reservas_module_files(): array
{
    return array(
        'reservas-service.php',
        'disponibilidad-service.php',
        'lista-espera.php',
        'reservas-notificaciones.php',
        'fidelidad.php',
        'admin-reservas.php',
        'metabox-reserva.php',
        'rest-reservas.php',
    );
}

/**
 * Require de módulos del motor.
 */
function jr_reservas_load_modules(): void
{
    if (defined('JR_RESERVAS_MODULES_LOADED') && JR_RESERVAS_MODULES_LOADED) {
        return;
    }

    foreach (jr_reservas_module_files() as $file) {
        $path = JR_RESERVAS_PATH . 'includes/' . $file;
        if (is_readable($path)) {
            require_once $path;
        }
    }

    if (!defined('JR_RESERVAS_MODULES_LOADED')) {
        define('JR_RESERVAS_MODULES_LOADED', true);
    }
}
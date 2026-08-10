<?php
/**
 * Carga app restante (admin operativo, contacto, hardening).
 * Domain + Reservas + Pagos deben estar cargados antes.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Ruta absoluta a un asset del plugin Core.
 */
function jr_core_asset_path(string $relative): string
{
    return JR_CORE_PATH . ltrim(str_replace('\\', '/', $relative), '/');
}

/**
 * URL de un asset del plugin Core.
 */
function jr_core_asset_url(string $relative): string
{
    return JR_CORE_URL . ltrim(str_replace('\\', '/', $relative), '/');
}

/**
 * @return list<string>
 */
function jr_core_app_files(): array
{
    return array(
        'admin-ingresos.php',
        'admin-notificaciones.php',
        'admin-acciones.php',
        'admin-agenda.php',
        'admin-dashboard.php',
        'admin-clientes.php',
        'admin-bloqueos.php',
        'admin-productos.php',
        'rest-galeria.php',
        'rest-servicios.php',
        'servicio-resenas.php',
        'prod-hardening.php',
        'widgets.php',
        'contacto.php',
        'settings-contacto.php',
        'admin-assets.php',
        'front-auth.php',
    );
}

/**
 * Require de módulos de aplicación.
 */
function jr_core_load_app(): void
{
    if (defined('JR_CORE_APP_LOADED') && JR_CORE_APP_LOADED) {
        return;
    }

    foreach (jr_core_app_files() as $file) {
        $path = JR_CORE_PATH . 'includes/' . $file;
        if (is_readable($path)) {
            require_once $path;
        }
    }

    if (!defined('JR_CORE_APP_LOADED')) {
        define('JR_CORE_APP_LOADED', true);
    }
}
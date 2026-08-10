<?php
/**
 * Carga el dominio de negocio (antes en el tema).
 *
 * El tema yuniorrojastheme solo presenta UI y encola assets de front.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Ruta absoluta a un asset del plugin.
 */
function jr_core_asset_path(string $relative): string
{
    return JR_CORE_PATH . ltrim(str_replace('\\', '/', $relative), '/');
}

/**
 * URL de un asset del plugin.
 */
function jr_core_asset_url(string $relative): string
{
    return JR_CORE_URL . ltrim(str_replace('\\', '/', $relative), '/');
}

/**
 * Archivos de dominio (orden importa para dependencias suaves).
 *
 * @return list<string>
 */
function jr_core_domain_files(): array
{
    return array(
        'constants.php',
        'helpers.php',
        'post-types.php',
        'servicio-meta.php',
        'metabox-procesos.php',
        'metabox-galeria.php',
        'metabox-imagen-perfil.php',
        'metabox-especialidades.php',
        'metabox-horario-barbero.php',
        'queries.php',
        'reservas-service.php',
        'disponibilidad-service.php',
        'admin-reservas.php',
        'metabox-reserva.php',
        'admin-ingresos.php',
        'reservas-notificaciones.php',
        'admin-notificaciones.php',
        'admin-acciones.php',
        'admin-agenda.php',
        'admin-dashboard.php',
        'admin-pagos.php',
        'admin-clientes.php',
        'admin-bloqueos.php',
        'admin-productos.php',
        'fidelidad.php',
        'lista-espera.php',
        'culqi-service.php',
        'medios-pago-service.php',
        'admin-medios-pago.php',
        'settings-pagos.php',
        'rest-galeria.php',
        'rest-servicios.php',
        'rest-reservas.php',
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
 * Require de cada módulo de dominio.
 */
function jr_core_load_domain(): void
{
    if (defined('YUNIORROJAS_DOMAIN_LOADED_BY_CORE') && YUNIORROJAS_DOMAIN_LOADED_BY_CORE) {
        return;
    }

    foreach (jr_core_domain_files() as $file) {
        $path = JR_CORE_PATH . 'includes/' . $file;
        if (is_readable($path)) {
            require_once $path;
        }
    }

    if (!defined('YUNIORROJAS_DOMAIN_LOADED_BY_CORE')) {
        define('YUNIORROJAS_DOMAIN_LOADED_BY_CORE', true);
    }
}

jr_core_load_domain();
<?php
/**
 * Constantes del tema.
 *
 * CPT Servicios:
 * - Clave real en este sitio (ACF): juniorojas_servicios
 * - El tema debe consultar ESA clave para listar los posts creados.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('YUNIORROJAS_TEXT_DOMAIN')) {
    define('YUNIORROJAS_TEXT_DOMAIN', 'yuniorrojastheme');
}

/**
 * Clave del CPT Servicios (debe coincidir con ACF → Post Types).
 */
if (!defined('YUNIORROJAS_CPT_SERVICIOS')) {
    define('YUNIORROJAS_CPT_SERVICIOS', 'juniorojas_servicios');
}

/**
 * CPT Reservas del cliente (panel Mi Cuenta / flujo Reservar).
 */
if (!defined('YUNIORROJAS_CPT_RESERVAS')) {
    define('YUNIORROJAS_CPT_RESERVAS', 'jr_reservas');
}

/**
 * CPT Medios de pago (admin: Reservas → Medios de pago).
 */
if (!defined('YUNIORROJAS_CPT_MEDIOS_PAGO')) {
    define('YUNIORROJAS_CPT_MEDIOS_PAGO', 'jr_medio_pago');
}

/**
 * CPT Reseñas de servicios (clientes en ficha de servicio).
 */
if (!defined('YUNIORROJAS_CPT_RESENAS')) {
    define('YUNIORROJAS_CPT_RESENAS', 'jr_resena');
}

/**
 * Alias antiguos / intentos de rename (solo migración).
 */
if (!defined('YUNIORROJAS_CPT_SERVICIOS_ALIASES')) {
    define(
        'YUNIORROJAS_CPT_SERVICIOS_ALIASES',
        'juniorrojas_servicios,servicios,servicio'
    );
}

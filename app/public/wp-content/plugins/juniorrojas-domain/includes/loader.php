<?php
/**
 * Carga módulos del cimiento (domain).
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @return list<string>
 */
function jr_domain_module_files(): array
{
    return array(
        'db.php',
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
    );
}

/**
 * Require de módulos de cimiento.
 */
function jr_domain_load_modules(): void
{
    if (defined('JR_DOMAIN_MODULES_LOADED') && JR_DOMAIN_MODULES_LOADED) {
        return;
    }

    foreach (jr_domain_module_files() as $file) {
        $path = JR_DOMAIN_PATH . 'includes/' . $file;
        if (is_readable($path)) {
            require_once $path;
        }
    }

    if (!defined('JR_DOMAIN_MODULES_LOADED')) {
        define('JR_DOMAIN_MODULES_LOADED', true);
    }

    // Compat: código que aún mira esta constante (loader Core antiguo).
    if (!defined('YUNIORROJAS_DOMAIN_LOADED_BY_CORE')) {
        define('YUNIORROJAS_DOMAIN_LOADED_BY_CORE', true);
    }
}

jr_domain_load_modules();
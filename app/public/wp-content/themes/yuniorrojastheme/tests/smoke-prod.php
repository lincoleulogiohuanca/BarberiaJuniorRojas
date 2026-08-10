<?php
/**
 * Smoke + unit de dominio (helpers).
 *
 * WP-CLI:
 *   wp eval-file wp-content/themes/yuniorrojastheme/tests/smoke-prod.php
 */

if (!defined('ABSPATH')) {
    fwrite(STDERR, "Carga este archivo con WP-CLI (wp eval-file ...).\n");
    exit(1);
}

$failed = 0;
$passed = 0;

$assert = static function (bool $cond, string $msg) use (&$failed, &$passed): void {
    if ($cond) {
        $passed++;
        echo "OK  {$msg}\n";
        return;
    }
    $failed++;
    echo "FAIL {$msg}\n";
};

// Precio → céntimos.
if (function_exists('yuniorrojas_precio_a_centimos')) {
    $assert(yuniorrojas_precio_a_centimos('S/. 45.00') === 4500, 'precio_a_centimos 45.00');
    $assert(yuniorrojas_precio_a_centimos('10') === 1000, 'precio_a_centimos 10');
    $assert(yuniorrojas_precio_a_centimos('') === 0, 'precio_a_centimos vacío');
} else {
    $assert(false, 'yuniorrojas_precio_a_centimos no existe');
}

// Culqi sin semillas hardcodeadas.
$culqi_file = (defined('JR_CORE_PATH') ? JR_CORE_PATH . 'includes/culqi-service.php' : '');
$src = is_readable($culqi_file) ? (string) file_get_contents($culqi_file) : '';
$assert(
    strpos($src, 'pk_test_AXEwZuPbByAfn7UE') === false,
    'sin semillas Culqi test hardcodeadas'
);
$assert(function_exists('yuniorrojas_culqi_idempotency_key'), 'idempotency key helper');
if (function_exists('yuniorrojas_culqi_idempotency_key')) {
    $k1 = yuniorrojas_culqi_idempotency_key(array('user' => 1, 'amount' => 1000));
    $k2 = yuniorrojas_culqi_idempotency_key(array('user' => 1, 'amount' => 1000));
    $k3 = yuniorrojas_culqi_idempotency_key(array('user' => 1, 'amount' => 1001));
    $assert($k1 === $k2 && strlen($k1) === 64, 'idempotency estable 64 hex');
    $assert($k1 !== $k3, 'idempotency cambia con monto');
}

// Solape de intervalos.
if (function_exists('yuniorrojas_intervalos_solapan')) {
    $assert(yuniorrojas_intervalos_solapan(600, 660, 630, 690) === true, 'solape parcial');
    $assert(yuniorrojas_intervalos_solapan(600, 660, 660, 720) === false, 'adyacente no solapa');
    $assert(yuniorrojas_intervalos_solapan(600, 660, 500, 560) === false, 'antes no solapa');
} else {
    $assert(false, 'yuniorrojas_intervalos_solapan no existe');
}

// Rate limit y locks.
$assert(function_exists('yuniorrojas_rate_limit'), 'rate_limit helper');
$assert(function_exists('yuniorrojas_slot_adquirir_lock'), 'slot lock helper');
$assert(function_exists('yuniorrojas_culqi_refund_cargo'), 'refund Culqi helper');
$assert(function_exists('yuniorrojas_prod_settings'), 'prod settings');
$assert(function_exists('yuniorrojas_normalizar_lineas_productos'), 'productos checkout normalizar');
$assert(function_exists('yuniorrojas_validar_attachment_cliente'), 'validar attachment cliente');
$assert(function_exists('yuniorrojas_fidelidad_descuento_pct_nivel'), 'fidelidad descuento');
if (function_exists('yuniorrojas_fidelidad_descuento_pct_nivel')) {
    $assert(yuniorrojas_fidelidad_descuento_pct_nivel('classic') === 0, 'classic 0%');
    $assert(yuniorrojas_fidelidad_descuento_pct_nivel('gold') === 5, 'gold 5%');
    $assert(yuniorrojas_fidelidad_descuento_pct_nivel('platinum') === 10, 'platinum 10%');
}

// Comprobante max size.
$assert(
    defined('YUNIORROJAS_COMPROBANTE_MAX_BYTES') && YUNIORROJAS_COMPROBANTE_MAX_BYTES === 4 * 1024 * 1024,
    'límite comprobante 4MB'
);

// Módulos JS.
$mod_dir = get_template_directory() . '/js/modules';
foreach (array('header-menu', 'reservar', 'cuenta', 'resenas') as $mod) {
    $assert(is_readable($mod_dir . '/' . $mod . '.js'), "módulo js {$mod}");
}

// Plugin core (opcional en local sin activar).
if (defined('JUNIORROJAS_CORE_LOADED')) {
    $assert(function_exists('jr_db_install_schema'), 'jr_db schema');
    $assert(function_exists('jr_db_slot_adquirir_lock'), 'jr_db slot lock');
    $assert(function_exists('jr_db_idempotency_get'), 'jr_db idempotency');
    echo "INFO juniorrojas-core activo\n";
} else {
    echo "INFO juniorrojas-core no cargado (activa el plugin en admin)\n";
}

// xdebug no debe estar en tema.
$assert(
    !file_exists(ABSPATH . 'local-xdebuginfo.php') || (defined('WP_DEBUG') && WP_DEBUG),
    'xdebuginfo solo tolerable en debug (revisar .gitignore)'
);

echo "\nResultado: {$passed} OK, {$failed} FAIL\n";
exit($failed > 0 ? 1 : 0);

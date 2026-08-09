<?php
/**
 * Smoke tests de helpers de producción (ejecutar vía WP-CLI o PHP con bootstrap WP).
 *
 * WP-CLI:
 *   wp eval-file wp-content/themes/yuniorrojastheme/tests/smoke-prod.php
 *
 * Sin fallos: exit 0. Con fallos: exit 1.
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

// Culqi sin semillas hardcodeadas (el archivo no contiene pk_test_ fijo en seed).
$culqi_file = get_template_directory() . '/includes/culqi-service.php';
$src = is_readable($culqi_file) ? (string) file_get_contents($culqi_file) : '';
$assert(
    strpos($src, "pk_test_AXEwZuPbByAfn7UE") === false,
    'sin semillas Culqi test hardcodeadas'
);

// Rate limit y locks.
$assert(function_exists('yuniorrojas_rate_limit'), 'rate_limit helper');
$assert(function_exists('yuniorrojas_slot_adquirir_lock'), 'slot lock helper');
$assert(function_exists('yuniorrojas_culqi_refund_cargo'), 'refund Culqi helper');
$assert(function_exists('yuniorrojas_prod_settings'), 'prod settings');
$assert(function_exists('yuniorrojas_normalizar_lineas_productos'), 'productos checkout normalizar');

// Comprobante max size.
$assert(
    defined('YUNIORROJAS_COMPROBANTE_MAX_BYTES') && YUNIORROJAS_COMPROBANTE_MAX_BYTES === 4 * 1024 * 1024,
    'límite comprobante 4MB'
);

echo "\nResultado: {$passed} OK, {$failed} FAIL\n";
exit($failed > 0 ? 1 : 0);

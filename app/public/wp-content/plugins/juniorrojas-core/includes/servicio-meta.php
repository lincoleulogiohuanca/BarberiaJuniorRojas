<?php
/**
 * Lectores de procesos/galería desde meta del metabox (sin ACF Pro).
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @return array<int, array{titulo:string, descripcion:string}>
 */
function yuniorrojas_obtener_procesos($post_id = null): array
{
    $post_id = $post_id ? (int) $post_id : (int) get_the_ID();
    $procesos = get_post_meta($post_id, '_yuniorrojas_procesos', true);

    return is_array($procesos) ? yuniorrojas_normalizar_procesos($procesos) : array();
}

/**
 * @param array<int, mixed> $procesos
 * @return array<int, array{titulo:string, descripcion:string}>
 */
function yuniorrojas_normalizar_procesos(array $procesos): array
{
    $limpios = array();

    foreach ($procesos as $proceso) {
        if (!is_array($proceso)) {
            continue;
        }

        $titulo = sanitize_text_field((string) ($proceso['titulo'] ?? ''));
        $descripcion = sanitize_textarea_field((string) ($proceso['descripcion'] ?? ''));

        if ($titulo === '' && $descripcion === '') {
            continue;
        }

        $limpios[] = array(
            'titulo'      => $titulo,
            'descripcion' => $descripcion,
        );
    }

    return $limpios;
}

/**
 * @return array<int, int>
 */
function yuniorrojas_obtener_galeria($post_id = null): array
{
    $post_id = $post_id ? (int) $post_id : (int) get_the_ID();
    $imagenes = get_post_meta($post_id, '_yuniorrojas_galeria', true);

    if (!is_array($imagenes)) {
        return array();
    }

    return array_values(array_filter(array_map('absint', $imagenes)));
}

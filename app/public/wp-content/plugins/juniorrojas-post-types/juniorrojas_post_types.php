<?php
/*
    Plugin Name: BarberFlow Legacy CPTs
    Plugin URI: http://x.com/lincoleulogio
    Description: LEGACY. El CPT lo registra BarberFlow Core. Desactivar si Core + Theme están activos.
    Version: 1.1.0
    Author: Lincol Eulogio Huanca
    Author URI: http://x.com/lincoleulogio
    Text Domain: barberia-junior-rojas
*/

if (!defined('ABSPATH')) {
    die();
}

/**
 * Solo registra el CPT si Core/tema aún no lo hicieron.
 * Preferir: plugin juniorrojas-core + tema yuniorrojastheme.
 */
function juniorrojas_servicios_post_type(): void
{
    if (post_type_exists('juniorojas_servicios')) {
        return;
    }

    // Si el tema ya define la constante y se carga después, no re-registrar aquí a la fuerza
    // cuando Core suprimió el hook: se re-registra solo como fallback.
    $labels = array(
        'name'          => _x('Servicios', 'Post Type General Name', 'juniorojas'),
        'singular_name' => _x('Servicio', 'Post Type Singular Name', 'juniorojas'),
        'menu_name'     => __('Servicios', 'juniorojas'),
        'add_new_item'  => __('Agregar Servicio', 'juniorojas'),
        'edit_item'     => __('Editar Servicio', 'juniorojas'),
    );
    register_post_type(
        'juniorojas_servicios',
        array(
            'label'               => __('Servicio', 'juniorojas'),
            'labels'              => $labels,
            'supports'            => array('title', 'editor', 'thumbnail'),
            'hierarchical'        => true,
            'public'              => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'menu_position'       => 6,
            // Preferir SVG de tijeras del tema si está cargado (dashicons-scissors no existe en WP).
            'menu_icon'           => function_exists('yuniorrojas_menu_icon_tijeras')
                ? yuniorrojas_menu_icon_tijeras()
                : 'dashicons-admin-customizer',
            'has_archive'         => true,
            'publicly_queryable'  => true,
            'capability_type'     => 'page',
            'show_in_rest'        => true,
        )
    );
}
// Prioridad 25: después del tema (init 20). Core puede remove_action en 0.
add_action('init', 'juniorrojas_servicios_post_type', 25);

<?php
/*
    Plugin Name: Barberia Junior Rojas - Post Types
    Plugin URI: http://x.com/lincoleulogio
    Description: Añade Post Types al sitio Barberia Junior Rojas
    Version: 1.0.0
    Author: Lincol Eulogio Huanca
    Author URI: http://x.com/lincoleulogio
    Text Domain: barberia-junior-rojas
*/

if(!defined('ABSPATH')) die();

// Registrar Custom Post Type
function juniorrojas_servicios_post_type() {

	$labels = array(
		'name'                  => _x( 'Servicios', 'Post Type General Name', 'juniorojas' ),
		'singular_name'         => _x( 'Servicio', 'Post Type Singular Name', 'juniorojas' ),
		'menu_name'             => __( 'Servicios', 'juniorojas' ),
		'name_admin_bar'        => __( 'Servicio', 'juniorojas' ),
		'archives'              => __( 'Archivo', 'juniorojas' ),
		'attributes'            => __( 'Atributos', 'juniorojas' ),
		'parent_item_colon'     => __( 'Servicio Padre', 'juniorojas' ),
		'all_items'             => __( 'Todas Las Servicios', 'juniorojas' ),
		'add_new_item'          => __( 'Agregar Servicio', 'juniorojas' ),
		'add_new'               => __( 'Agregar Servicio', 'juniorojas' ),
		'new_item'              => __( 'Nueva Servicio', 'juniorojas' ),
		'edit_item'             => __( 'Editar Servicio', 'juniorojas' ),
		'update_item'           => __( 'Actualizar Servicio', 'juniorojas' ),
		'view_item'             => __( 'Ver Servicio', 'juniorojas' ),
		'view_items'            => __( 'Ver Servicios', 'juniorojas' ),
		'search_items'          => __( 'Buscar Servicio', 'juniorojas' ),
		'not_found'             => __( 'No Encontrado', 'juniorojas' ),
		'not_found_in_trash'    => __( 'No Encontrado en Papelera', 'juniorojas' ),
		'featured_image'        => __( 'Imagen Destacada', 'juniorojas' ),
		'set_featured_image'    => __( 'Guardar Imagen destacada', 'juniorojas' ),
		'remove_featured_image' => __( 'Eliminar Imagen destacada', 'juniorojas' ),
		'use_featured_image'    => __( 'Utilizar como Imagen Destacada', 'juniorojas' ),
		'insert_into_item'      => __( 'Insertar en Servicio', 'juniorojas' ),
		'uploaded_to_this_item' => __( 'Agregado en Servicio', 'juniorojas' ),
		'items_list'            => __( 'Lista de Servicios', 'juniorojas' ),
		'items_list_navigation' => __( 'Navegación de Servicios', 'juniorojas' ),
		'filter_items_list'     => __( 'Filtrar Servicios', 'juniorojas' ),
	);
	$args = array(
		'label'                 => __( 'Servicio', 'juniorojas' ),
		'description'           => __( 'Servicios para el Sitio Web', 'juniorojas' ),
		'labels'                => $labels,
		'supports'              => array( 'title', 'editor', 'thumbnail' ),
		'hierarchical'          => true, // true = posts , false = paginas
		'public'                => true,
		'show_ui'               => true,
		'show_in_menu'          => true,
        'menu_position'         => 6,
        'menu_icon'             => 'dashicons-welcome-learn-more',
		'show_in_admin_bar'     => true,
		'show_in_nav_menus'     => true,
		'can_export'            => true,
		'has_archive'           => true,
		'exclude_from_search'   => false,
		'publicly_queryable'    => true,
		'capability_type'       => 'page',
	);
	register_post_type( 'juniorojas_servicios', $args );

}
add_action( 'init', 'juniorrojas_servicios_post_type', 0 );
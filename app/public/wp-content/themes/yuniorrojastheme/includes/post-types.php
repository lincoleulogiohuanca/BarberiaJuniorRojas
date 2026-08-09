<?php
/**
 * CPT y taxonomías del tema.
 *
 * Fuente de verdad CPT Servicios: ACF (clave juniorojas_servicios).
 * El tema solo registra el CPT si ACF aún no lo creó.
 */

if (!defined('ABSPATH')) {
    exit;
}

function yuniorrojas_registrar_cpts(): void
{
    if (!post_type_exists(YUNIORROJAS_CPT_SERVICIOS)) {
        register_post_type(YUNIORROJAS_CPT_SERVICIOS, array(
            'labels' => array(
                'name'          => __('Servicios', YUNIORROJAS_TEXT_DOMAIN),
                'singular_name' => __('Servicio', YUNIORROJAS_TEXT_DOMAIN),
                'add_new_item'  => __('Añadir servicio', YUNIORROJAS_TEXT_DOMAIN),
                'edit_item'     => __('Editar servicio', YUNIORROJAS_TEXT_DOMAIN),
            ),
            'public'       => true,
            'has_archive'  => true,
            'rewrite'      => array('slug' => 'servicios-jr'),
            'menu_icon'    => 'dashicons-scissors',
            'supports'     => array('title', 'editor', 'thumbnail', 'excerpt', 'page-attributes'),
            'show_in_rest' => true,
        ));
    }

    if (!post_type_exists('barberos')) {
        register_post_type('barberos', array(
            'labels' => array(
                'name'          => __('Barberos', YUNIORROJAS_TEXT_DOMAIN),
                'singular_name' => __('Barbero', YUNIORROJAS_TEXT_DOMAIN),
                'add_new_item'  => __('Añadir barbero', YUNIORROJAS_TEXT_DOMAIN),
                'edit_item'     => __('Editar barbero', YUNIORROJAS_TEXT_DOMAIN),
            ),
            'public'            => true,
            'publicly_queryable'=> true,
            'has_archive'       => false,
            'rewrite'           => array(
                'slug'       => 'barbero',
                'with_front' => false,
            ),
            'menu_icon'    => 'dashicons-groups',
            'supports'     => array('title', 'editor', 'thumbnail', 'excerpt'),
            'show_in_rest' => true,
        ));
    }

    if (!post_type_exists('testimoniales')) {
        register_post_type('testimoniales', array(
            'labels' => array(
                'name'          => __('Testimoniales', YUNIORROJAS_TEXT_DOMAIN),
                'singular_name' => __('Testimonial', YUNIORROJAS_TEXT_DOMAIN),
            ),
            'public'       => true,
            'has_archive'  => false,
            'menu_icon'    => 'dashicons-format-quote',
            'supports'     => array('title', 'editor', 'thumbnail'),
            'show_in_rest' => true,
        ));
    }

    if (!post_type_exists('galeria')) {
        register_post_type('galeria', array(
            'labels' => array(
                'name'          => __('Galería', YUNIORROJAS_TEXT_DOMAIN),
                'singular_name' => __('Obra', YUNIORROJAS_TEXT_DOMAIN),
                'add_new_item'  => __('Añadir obra', YUNIORROJAS_TEXT_DOMAIN),
            ),
            'public'       => true,
            'has_archive'  => true,
            'rewrite'      => array('slug' => 'obras'),
            'menu_icon'    => 'dashicons-format-gallery',
            'supports'     => array('title', 'thumbnail', 'excerpt'),
            'show_in_rest' => true,
        ));
    }

    if (!taxonomy_exists('categoria_galeria')) {
        register_taxonomy('categoria_galeria', 'galeria', array(
            'labels' => array(
                'name'          => __('Categorías (filtros izq.)', YUNIORROJAS_TEXT_DOMAIN),
                'singular_name' => __('Categoría', YUNIORROJAS_TEXT_DOMAIN),
                'search_items'  => __('Buscar categorías', YUNIORROJAS_TEXT_DOMAIN),
                'all_items'     => __('Todas las categorías', YUNIORROJAS_TEXT_DOMAIN),
                'edit_item'     => __('Editar categoría', YUNIORROJAS_TEXT_DOMAIN),
                'update_item'   => __('Actualizar categoría', YUNIORROJAS_TEXT_DOMAIN),
                'add_new_item'  => __('Añadir categoría', YUNIORROJAS_TEXT_DOMAIN),
                'new_item_name' => __('Nueva categoría', YUNIORROJAS_TEXT_DOMAIN),
                'menu_name'     => __('Categorías', YUNIORROJAS_TEXT_DOMAIN),
            ),
            'public'            => true,
            'hierarchical'      => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => array('slug' => 'categoria-galeria'),
        ));
    } else {
        register_taxonomy_for_object_type('categoria_galeria', 'galeria');
    }

    if (!taxonomy_exists('etiqueta_galeria')) {
        register_taxonomy('etiqueta_galeria', 'galeria', array(
            'labels' => array(
                'name'              => __('Etiquetas (filtros der.)', YUNIORROJAS_TEXT_DOMAIN),
                'singular_name'     => __('Etiqueta', YUNIORROJAS_TEXT_DOMAIN),
                'search_items'      => __('Buscar etiquetas', YUNIORROJAS_TEXT_DOMAIN),
                'all_items'         => __('Todas las etiquetas', YUNIORROJAS_TEXT_DOMAIN),
                'parent_item'       => __('Etiqueta superior', YUNIORROJAS_TEXT_DOMAIN),
                'parent_item_colon' => __('Etiqueta superior:', YUNIORROJAS_TEXT_DOMAIN),
                'edit_item'         => __('Editar etiqueta', YUNIORROJAS_TEXT_DOMAIN),
                'update_item'       => __('Actualizar etiqueta', YUNIORROJAS_TEXT_DOMAIN),
                'add_new_item'      => __('Añadir etiqueta', YUNIORROJAS_TEXT_DOMAIN),
                'new_item_name'     => __('Nueva etiqueta', YUNIORROJAS_TEXT_DOMAIN),
                'menu_name'         => __('Etiquetas', YUNIORROJAS_TEXT_DOMAIN),
            ),
            'public'            => true,
            'hierarchical'      => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => array('slug' => 'etiqueta-galeria'),
        ));
    } else {
        register_taxonomy_for_object_type('etiqueta_galeria', 'galeria');
    }

    if (!post_type_exists(YUNIORROJAS_CPT_RESERVAS)) {
        register_post_type(YUNIORROJAS_CPT_RESERVAS, array(
            'labels' => array(
                'name'               => __('Reservas', YUNIORROJAS_TEXT_DOMAIN),
                'singular_name'      => __('Reserva', YUNIORROJAS_TEXT_DOMAIN),
                'add_new_item'       => __('Añadir reserva', YUNIORROJAS_TEXT_DOMAIN),
                'edit_item'          => __('Editar reserva', YUNIORROJAS_TEXT_DOMAIN),
                'new_item'           => __('Nueva reserva', YUNIORROJAS_TEXT_DOMAIN),
                'view_item'          => __('Ver reserva', YUNIORROJAS_TEXT_DOMAIN),
                'search_items'       => __('Buscar reservas', YUNIORROJAS_TEXT_DOMAIN),
                'not_found'          => __('No se encontraron reservas', YUNIORROJAS_TEXT_DOMAIN),
                'not_found_in_trash' => __('No hay reservas en la papelera', YUNIORROJAS_TEXT_DOMAIN),
                'menu_name'          => __('Reservas', YUNIORROJAS_TEXT_DOMAIN),
            ),
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_admin_bar'   => true,
            'exclude_from_search' => true,
            'publicly_queryable'  => false,
            'has_archive'         => false,
            'rewrite'             => false,
            'menu_icon'           => 'dashicons-calendar-alt',
            'supports'            => array('title'),
            'capability_type'     => 'post',
            'map_meta_cap'        => true,
            'show_in_rest'        => false,
        ));
    }

    $cpt_servicios = YUNIORROJAS_CPT_SERVICIOS;

    if (!taxonomy_exists('categoria_servicio')) {
        register_taxonomy('categoria_servicio', $cpt_servicios, array(
            'labels' => array(
                'name'          => __('Categorías (filtros izq.)', YUNIORROJAS_TEXT_DOMAIN),
                'singular_name' => __('Categoría', YUNIORROJAS_TEXT_DOMAIN),
                'search_items'  => __('Buscar categorías', YUNIORROJAS_TEXT_DOMAIN),
                'all_items'     => __('Todas las categorías', YUNIORROJAS_TEXT_DOMAIN),
                'edit_item'     => __('Editar categoría', YUNIORROJAS_TEXT_DOMAIN),
                'update_item'   => __('Actualizar categoría', YUNIORROJAS_TEXT_DOMAIN),
                'add_new_item'  => __('Añadir categoría', YUNIORROJAS_TEXT_DOMAIN),
                'new_item_name' => __('Nueva categoría', YUNIORROJAS_TEXT_DOMAIN),
                'menu_name'     => __('Categorías', YUNIORROJAS_TEXT_DOMAIN),
            ),
            'public'            => true,
            'hierarchical'      => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => array('slug' => 'categoria-servicio'),
        ));
    } else {
        register_taxonomy_for_object_type('categoria_servicio', $cpt_servicios);
    }

    if (!taxonomy_exists('etiqueta_servicio')) {
        register_taxonomy('etiqueta_servicio', $cpt_servicios, array(
            'labels' => array(
                'name'              => __('Etiquetas (filtros der.)', YUNIORROJAS_TEXT_DOMAIN),
                'singular_name'     => __('Etiqueta', YUNIORROJAS_TEXT_DOMAIN),
                'search_items'      => __('Buscar etiquetas', YUNIORROJAS_TEXT_DOMAIN),
                'all_items'         => __('Todas las etiquetas', YUNIORROJAS_TEXT_DOMAIN),
                'parent_item'       => __('Etiqueta superior', YUNIORROJAS_TEXT_DOMAIN),
                'parent_item_colon' => __('Etiqueta superior:', YUNIORROJAS_TEXT_DOMAIN),
                'edit_item'         => __('Editar etiqueta', YUNIORROJAS_TEXT_DOMAIN),
                'update_item'       => __('Actualizar etiqueta', YUNIORROJAS_TEXT_DOMAIN),
                'add_new_item'      => __('Añadir etiqueta', YUNIORROJAS_TEXT_DOMAIN),
                'new_item_name'     => __('Nueva etiqueta', YUNIORROJAS_TEXT_DOMAIN),
                'menu_name'         => __('Etiquetas', YUNIORROJAS_TEXT_DOMAIN),
            ),
            'public'            => true,
            'hierarchical'      => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => array('slug' => 'etiqueta-servicio'),
        ));
    } else {
        register_taxonomy_for_object_type('etiqueta_servicio', $cpt_servicios);
    }
}
add_action('init', 'yuniorrojas_registrar_cpts', 20);

/**
 * Fuerza checklist en etiquetas de galería/servicios (como categorías).
 */
function yuniorrojas_etiqueta_checklist(array $args, string $taxonomy): array
{
    if ($taxonomy !== 'etiqueta_galeria' && $taxonomy !== 'etiqueta_servicio') {
        return $args;
    }

    $args['hierarchical']      = true;
    $args['show_ui']           = true;
    $args['show_admin_column'] = true;
    $args['meta_box_cb']       = 'post_categories_meta_box';

    return $args;
}
add_filter('register_taxonomy_args', 'yuniorrojas_etiqueta_checklist', 30, 2);

/**
 * Crea términos base de filtros de galería una sola vez.
 */
function yuniorrojas_sembrar_terminos_galeria(): void
{
    if (get_option('yuniorrojas_galeria_terms_v2') === '1') {
        return;
    }

    if (!taxonomy_exists('categoria_galeria') || !taxonomy_exists('etiqueta_galeria')) {
        return;
    }

    $categorias = array(
        'cortes'       => 'Cortes',
        'barbas'       => 'Barbas',
        'ambiente'     => 'Ambiente',
        'productos'    => 'Productos',
        'herramientas' => 'Herramientas',
    );

    foreach ($categorias as $slug => $nombre) {
        if (!term_exists($slug, 'categoria_galeria')) {
            wp_insert_term($nombre, 'categoria_galeria', array('slug' => $slug));
        }
    }

    $etiquetas = array(
        'fade'     => 'Fade',
        'clasicos' => 'Clásicos',
        'premium'  => 'Premium',
    );

    foreach ($etiquetas as $slug => $nombre) {
        if (!term_exists($slug, 'etiqueta_galeria')) {
            wp_insert_term($nombre, 'etiqueta_galeria', array('slug' => $slug));
        }
    }

    update_option('yuniorrojas_galeria_terms_v2', '1');
}
add_action('init', 'yuniorrojas_sembrar_terminos_galeria', 30);

/**
 * Crea términos base de filtros de servicios (mockup: Cabello / Barba / Cuidado facial).
 */
function yuniorrojas_sembrar_terminos_servicios(): void
{
    if (get_option('yuniorrojas_servicios_terms_v2') === '1') {
        return;
    }

    if (!taxonomy_exists('categoria_servicio') || !taxonomy_exists('etiqueta_servicio')) {
        return;
    }

    $categorias = array(
        'cabello'         => 'Cabello',
        'barba'           => 'Barba',
        'cuidado-facial'  => 'Cuidado facial',
    );

    foreach ($categorias as $slug => $nombre) {
        if (!term_exists($slug, 'categoria_servicio')) {
            wp_insert_term($nombre, 'categoria_servicio', array('slug' => $slug));
        }
    }

    $etiquetas = array(
        'mas-vendidos' => 'Más vendidos',
        'nuevos'       => 'Nuevos',
        'sets'         => 'Sets',
    );

    foreach ($etiquetas as $slug => $nombre) {
        if (!term_exists($slug, 'etiqueta_servicio')) {
            wp_insert_term($nombre, 'etiqueta_servicio', array('slug' => $slug));
        }
    }

    // Limpia términos seed v1 vacíos (no borra si tienen posts asignados).
    $legacy = array(
        'categoria_servicio' => array('cortes', 'barbas', 'combo', 'tratamientos', 'premium'),
        'etiqueta_servicio'  => array('express', 'clasicos', 'modernos'),
    );

    foreach ($legacy as $tax => $slugs) {
        foreach ($slugs as $slug) {
            $term = get_term_by('slug', $slug, $tax);
            if ($term instanceof WP_Term && (int) $term->count === 0) {
                wp_delete_term((int) $term->term_id, $tax);
            }
        }
    }

    update_option('yuniorrojas_servicios_terms_v2', '1');
}
add_action('init', 'yuniorrojas_sembrar_terminos_servicios', 30);

/**
 * Flush de permalinks tras registrar taxonomías de servicios.
 */
function yuniorrojas_flush_rewrite_servicios_tax(): void
{
    if (get_option('yuniorrojas_flush_servicios_tax_v1') === '1') {
        return;
    }

    flush_rewrite_rules(false);
    update_option('yuniorrojas_flush_servicios_tax_v1', '1');
}
add_action('init', 'yuniorrojas_flush_rewrite_servicios_tax', 99);

/**
 * Evita conflicto Página /barberos/ vs CPT barberos.
 * Singles quedan en /barbero/{slug}/ ; el listado sigue en la página /barberos/.
 */
function yuniorrojas_ajustar_args_barberos(array $args, string $post_type): array
{
    if ($post_type !== 'barberos') {
        return $args;
    }

    $args['has_archive']       = false;
    $args['publicly_queryable'] = true;
    $args['rewrite']           = array(
        'slug'       => 'barbero',
        'with_front' => false,
    );

    return $args;
}
add_filter('register_post_type_args', 'yuniorrojas_ajustar_args_barberos', 20, 2);

/**
 * Flush de permalinks una vez tras el cambio de rewrite.
 */
function yuniorrojas_flush_rewrite_barbero(): void
{
    if (get_option('yuniorrojas_flush_barbero_v2') === '1') {
        return;
    }

    flush_rewrite_rules(false);
    update_option('yuniorrojas_flush_barbero_v2', '1');
}
add_action('init', 'yuniorrojas_flush_rewrite_barbero', 99);

/**
 * Consolida posts creados bajo alias (juniorrojas_servicios, etc.)
 * hacia la clave canónica YUNIORROJAS_CPT_SERVICIOS (ACF).
 */
function yuniorrojas_migrar_cpt_servicios(): void
{
    $canonico = YUNIORROJAS_CPT_SERVICIOS;
    $aliases  = array_filter(array_map('trim', explode(',', (string) YUNIORROJAS_CPT_SERVICIOS_ALIASES)));

    global $wpdb;
    $movidos = 0;

    foreach ($aliases as $alias) {
        if ($alias === '' || $alias === $canonico) {
            continue;
        }

        $count = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s",
                $alias
            )
        );

        if ($count < 1) {
            continue;
        }

        $updated = $wpdb->update(
            $wpdb->posts,
            array('post_type' => $canonico),
            array('post_type' => $alias),
            array('%s'),
            array('%s')
        );

        if ($updated) {
            $movidos += (int) $updated;
        }
    }

    if ($movidos > 0) {
        flush_rewrite_rules(false);
    }
}
add_action('init', 'yuniorrojas_migrar_cpt_servicios', 30);

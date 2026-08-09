<?php
/**
 * Queries y listados reutilizables del tema.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @param int    $cantidad
 * @param bool   $mostrar_precio
 * @param string $variante        default|home|listado
 */
function yuniorrojas_lista_servicios($cantidad = -1, $mostrar_precio = true, string $variante = 'default'): void
{
    $args = array(
        'post_type'      => YUNIORROJAS_CPT_SERVICIOS,
        'posts_per_page' => $cantidad,
        'post_status'    => 'publish',
        'orderby'        => 'menu_order',
        'order'          => 'ASC',
    );

    $servicios = new WP_Query($args);

    if (!$servicios->have_posts()) {
        echo '<p class="servicios-grid__empty">Aún no hay servicios publicados.</p>';
        return;
    }

    $grid_mods = array(
        'home'    => 'servicios-grid--home',
        'listado' => 'servicios-grid--listado',
    );
    $grid_class = 'servicios-grid';
    if (isset($grid_mods[$variante])) {
        $grid_class .= ' ' . $grid_mods[$variante];
    }

    $card_mod = $variante === 'home' ? ' servicio-card--home' : ($variante === 'listado' ? ' servicio-card--listado' : '');
    ?>
    <ul class="<?php echo esc_attr($grid_class); ?>">
        <?php while ($servicios->have_posts()) : $servicios->the_post(); ?>
            <li class="servicio-card<?php echo esc_attr($card_mod); ?>">
                <?php get_template_part('template-parts/card', 'servicio', array(
                    'mostrar_precio' => $mostrar_precio,
                    'variante'       => $variante === 'default' ? 'listado' : $variante,
                )); ?>
            </li>
        <?php endwhile; ?>
        <?php wp_reset_postdata(); ?>
    </ul>
    <?php
}

function yuniorrojas_lista_barberos($cantidad = -1): void
{
    $args = array(
        'post_type'      => 'barberos',
        'posts_per_page' => $cantidad,
        'post_status'    => 'publish',
        'orderby'        => 'menu_order',
        'order'          => 'ASC',
    );

    $barberos = new WP_Query($args);

    if (!$barberos->have_posts()) {
        echo '<p class="barberos-grid__empty">Aún no hay barberos publicados.</p>';
        return;
    }
    ?>
    <ul class="barberos-grid">
        <?php while ($barberos->have_posts()) : $barberos->the_post(); ?>
            <li class="barbero-card">
                <?php get_template_part('template-parts/card', 'barbero'); ?>
            </li>
        <?php endwhile; ?>
        <?php wp_reset_postdata(); ?>
    </ul>
    <?php
}

function yuniorrojas_lista_testimoniales($cantidad = 6): void
{
    $args = array(
        'post_type'      => 'testimoniales',
        'posts_per_page' => max(1, (int) $cantidad),
        'post_status'    => 'publish',
        'orderby'        => 'menu_order date',
        'order'          => 'ASC',
    );

    $testimoniales = new WP_Query($args);

    if (!$testimoniales->have_posts()) {
        return;
    }

    $slides = array();

    while ($testimoniales->have_posts()) {
        $testimoniales->the_post();
        $slides[] = array(
            'content' => (string) get_the_content(),
            'title'   => (string) get_the_title(),
        );
    }
    wp_reset_postdata();

    // Suficientes slides para loop infinito estable en desktop (3 visibles).
    $original = $slides;
    while (count($slides) < 6) {
        $slides = array_merge($slides, $original);
    }
    ?>
    <div
        class="swiper testimoniales-slider"
        data-testimoniales-slider
        aria-label="<?php esc_attr_e('Testimonios', YUNIORROJAS_TEXT_DOMAIN); ?>">
        <div class="swiper-wrapper">
            <?php foreach ($slides as $slide) : ?>
                <div class="swiper-slide">
                    <article class="testimonial-card">
                        <span class="testimonial-card__quote" aria-hidden="true">”</span>
                        <blockquote class="testimonial-card__text">
                            <?php echo wp_kses_post(wpautop($slide['content'])); ?>
                        </blockquote>
                        <footer class="testimonial-card__footer">
                            <cite class="testimonial-card__author">
                                <span class="testimonial-card__dash" aria-hidden="true">—</span>
                                <?php echo esc_html($slide['title']); ?>
                            </cite>
                        </footer>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}

/**
 * Listado de galería (máx. 10 por página).
 *
 * @param int         $cantidad
 * @param string|null $categoria slug categoria_galeria
 * @param string|null $etiqueta  slug etiqueta_galeria
 * @param int         $pagina
 * @return array{total:int,pages:int,page:int}
 */
function yuniorrojas_lista_galeria($cantidad = 10, $categoria = null, $etiqueta = null, int $pagina = 1): array
{
    $cantidad = max(1, min(10, (int) $cantidad));
    $pagina   = max(1, $pagina);

    $args = array(
        'post_type'      => 'galeria',
        'posts_per_page' => $cantidad,
        'paged'          => $pagina,
        'post_status'    => 'publish',
        'orderby'        => 'menu_order date',
        'order'          => 'ASC',
    );

    $tax_query = array();

    if ($categoria) {
        $tax_query[] = array(
            'taxonomy' => 'categoria_galeria',
            'field'    => 'slug',
            'terms'    => $categoria,
        );
    }

    if ($etiqueta) {
        $tax_query[] = array(
            'taxonomy' => 'etiqueta_galeria',
            'field'    => 'slug',
            'terms'    => $etiqueta,
        );
    }

    if (count($tax_query) > 1) {
        $tax_query['relation'] = 'AND';
        $args['tax_query'] = $tax_query;
    } elseif (count($tax_query) === 1) {
        $args['tax_query'] = $tax_query;
    }

    $obras = new WP_Query($args);
    $total = (int) $obras->found_posts;
    $pages = max(1, (int) $obras->max_num_pages);
    $count = (int) $obras->post_count;

    if ($count < 1) {
        echo '<p class="galeria-grid__empty">No hay obras para este filtro.</p>';
        return array(
            'total' => 0,
            'pages' => 1,
            'page'  => $pagina,
        );
    }
    ?>
    <ul class="galeria-grid" data-galeria-grid data-galeria-count="<?php echo esc_attr((string) $count); ?>">
        <?php
        $index = 0;
        while ($obras->have_posts()) :
            $obras->the_post();
            $cats = wp_get_post_terms(get_the_ID(), 'categoria_galeria', array('fields' => 'slugs'));
            $tags = wp_get_post_terms(get_the_ID(), 'etiqueta_galeria', array('fields' => 'slugs'));
            $filters = array_merge(
                is_wp_error($cats) ? array() : $cats,
                is_wp_error($tags) ? array() : $tags
            );
            $thumb_id = (int) get_post_thumbnail_id();
            $tamano   = yuniorrojas_galeria_tamano_item($thumb_id, $index, $count);
            $full_src = $thumb_id
                ? (wp_get_attachment_image_url($thumb_id, 'full') ?: wp_get_attachment_image_url($thumb_id, 'large'))
                : '';
            $thumb_src = $thumb_id ? (string) wp_get_attachment_image_url($thumb_id, 'large') : '';
            $caption   = get_the_title();
            ?>
            <li
                class="galeria-item galeria-item--<?php echo esc_attr($tamano); ?>"
                data-filters="<?php echo esc_attr(implode(' ', $filters)); ?>"
            >
                <button
                    type="button"
                    class="galeria-item__open"
                    data-lightbox-open
                    data-lightbox-src="<?php echo esc_url((string) $full_src); ?>"
                    data-thumb-src="<?php echo esc_url($thumb_src); ?>"
                    data-lightbox-caption="<?php echo esc_attr($caption); ?>"
                    <?php disabled($full_src === ''); ?>
                    aria-label="<?php echo esc_attr(sprintf(__('Ver imagen: %s', YUNIORROJAS_TEXT_DOMAIN), $caption)); ?>"
                >
                    <figure class="galeria-item__figure">
                        <?php if ($thumb_id) : ?>
                            <?php echo wp_get_attachment_image($thumb_id, 'large', false, array(
                                'class'    => 'galeria-item__img',
                                'alt'      => esc_attr($caption),
                                'loading'  => 'eager',
                                'decoding' => 'async',
                            )); ?>
                        <?php endif; ?>
                        <figcaption class="galeria-item__caption">
                            <span><?php echo esc_html($caption); ?></span>
                        </figcaption>
                    </figure>
                </button>
            </li>
            <?php
            $index++;
        endwhile;
        wp_reset_postdata();
        ?>
    </ul>
    <?php

    yuniorrojas_galeria_paginacion($pagina, $pages);

    return array(
        'total' => $total,
        'pages' => $pages,
        'page'  => $pagina,
    );
}

/**
 * Controles de paginación de galería.
 */
function yuniorrojas_galeria_paginacion(int $pagina, int $pages): void
{
    if ($pages <= 1) {
        return;
    }
    ?>
    <nav class="galeria-pagination" data-galeria-pagination aria-label="<?php esc_attr_e('Paginación de galería', YUNIORROJAS_TEXT_DOMAIN); ?>">
        <button
            type="button"
            class="galeria-pagination__btn"
            data-galeria-page="<?php echo esc_attr((string) max(1, $pagina - 1)); ?>"
            <?php disabled($pagina <= 1); ?>
        >
            ←
        </button>

        <ul class="galeria-pagination__pages">
            <?php for ($i = 1; $i <= $pages; $i++) : ?>
                <li>
                    <button
                        type="button"
                        class="galeria-pagination__page<?php echo $i === $pagina ? ' is-active' : ''; ?>"
                        data-galeria-page="<?php echo esc_attr((string) $i); ?>"
                        aria-current="<?php echo $i === $pagina ? 'page' : 'false'; ?>"
                    >
                        <?php echo esc_html((string) $i); ?>
                    </button>
                </li>
            <?php endfor; ?>
        </ul>

        <button
            type="button"
            class="galeria-pagination__btn"
            data-galeria-page="<?php echo esc_attr((string) min($pages, $pagina + 1)); ?>"
            <?php disabled($pagina >= $pages); ?>
        >
            →
        </button>
    </nav>
    <?php
}

/**
 * Patrones de layout según cantidad (1–10).
 * Evita celdas vacías: los "tall" van en pares.
 */
function yuniorrojas_galeria_tamano_item(int $attachment_id, int $index, int $total = 10): string
{
    $total = max(1, min(10, $total));

    $patrones = array(
        1  => array('full'),
        2  => array('wide', 'wide'),
        3  => array('wide', 'wide', 'full'),
        4  => array('wide', 'wide', 'wide', 'wide'),
        5  => array('wide', 'wide', 'wide', 'wide', 'full'),
        6  => array('wide', 'wide', 'wide', 'wide', 'wide', 'wide'),
        7  => array('wide', 'wide', 'wide', 'wide', 'wide', 'wide', 'full'),
        8  => array('wide', 'wide', 'tall', 'tall', 'wide', 'wide', 'wide', 'wide'),
        9  => array('wide', 'wide', 'tall', 'tall', 'wide', 'wide', 'wide', 'wide', 'full'),
        10 => array('wide', 'wide', 'tall', 'tall', 'full', 'wide', 'wide', 'tall', 'tall', 'full'),
    );

    $patron = $patrones[$total] ?? $patrones[10];
    $base   = $patron[$index] ?? 'wide';

    // Refinar solo si no rompe el patrón de pares.
    if ($attachment_id > 0 && $base === 'wide') {
        $meta = wp_get_attachment_metadata($attachment_id);
        $w    = (int) ($meta['width'] ?? 0);
        $h    = (int) ($meta['height'] ?? 0);

        if ($w > 0 && $h > 0) {
            $ratio = $w / $h;
            if ($ratio >= 2.3 && $index === $total - 1) {
                return 'full';
            }
        }
    }

    return $base;
}

/**
 * Estilos de referencia para preferencias del cliente (CPT galería).
 * Prioriza categoría "cortes"; si no hay resultados, usa toda la galería con imagen.
 *
 * @return list<array{id:string,nombre:string,imagen:string}>
 */
function yuniorrojas_estilos_referencia_para_cliente(int $limit = 24): array
{
    $limit = max(1, min(48, $limit));

    $consultar = static function (bool $filtrar_cortes) use ($limit): array {
        $args = array(
            'post_type'              => 'galeria',
            'posts_per_page'         => $limit,
            'post_status'            => 'publish',
            'orderby'                => 'menu_order date',
            'order'                  => 'ASC',
            'meta_query'             => array(
                array(
                    'key'     => '_thumbnail_id',
                    'compare' => 'EXISTS',
                ),
            ),
            'no_found_rows'          => true,
            'update_post_meta_cache' => true,
            'update_post_term_cache' => false,
        );

        if ($filtrar_cortes && taxonomy_exists('categoria_galeria')) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'categoria_galeria',
                    'field'    => 'slug',
                    'terms'    => 'cortes',
                ),
            );
        }

        $query = new WP_Query($args);
        $items = array();

        foreach ($query->posts as $post) {
            if (!$post instanceof WP_Post) {
                continue;
            }
            $id  = (int) $post->ID;
            $img = get_the_post_thumbnail_url($id, 'medium_large');
            if (!is_string($img) || $img === '') {
                continue;
            }
            $nombre = get_the_title($id);
            if ($nombre === '') {
                $nombre = sprintf(/* translators: %d: gallery post ID */ __('Obra #%d', YUNIORROJAS_TEXT_DOMAIN), $id);
            }
            $items[] = array(
                'id'     => (string) $id,
                'nombre' => $nombre,
                'imagen' => $img,
            );
        }

        wp_reset_postdata();

        return $items;
    };

    $items = $consultar(true);
    if ($items === array()) {
        $items = $consultar(false);
    }

    return $items;
}

/**
 * ¿El ID es una obra de galería válida como estilo de referencia?
 */
function yuniorrojas_estilo_referencia_es_valido(int $estilo_id): bool
{
    if ($estilo_id <= 0) {
        return false;
    }

    $post = get_post($estilo_id);
    if (!$post instanceof WP_Post) {
        return false;
    }

    return $post->post_type === 'galeria'
        && $post->post_status === 'publish'
        && has_post_thumbnail($estilo_id);
}

/** Aliases de compatibilidad (deprecados). */
function juniorrojas_lista_servicios($cantidad = -1, $mostrar_precio = true): void
{
    yuniorrojas_lista_servicios($cantidad, $mostrar_precio);
}

function juniorrojas_barberos($cantidad = -1): void
{
    yuniorrojas_lista_barberos($cantidad);
}

function juniorrojas_testimoniales($cantidad = 3): void
{
    yuniorrojas_lista_testimoniales($cantidad);
}

function juniorrojas_galeria($cantidad = 12, $categoria = null): void
{
    yuniorrojas_lista_galeria($cantidad, $categoria);
}

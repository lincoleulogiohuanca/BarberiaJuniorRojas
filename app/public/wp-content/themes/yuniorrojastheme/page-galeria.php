<?php
/**
 * Template Name: Galería
 * Diseño: Galería de Autor
 */
get_header();

$intro = yuniorrojas_field(
    'intro_galeria',
    false,
    'Una mirada a la disciplina, técnica y el ambiente que define nuestro estándar de excelencia.'
);

$categorias = get_terms(array(
    'taxonomy'   => 'categoria_galeria',
    'hide_empty' => false,
));
$etiquetas = get_terms(array(
    'taxonomy'   => 'etiqueta_galeria',
    'hide_empty' => false,
));
?>

<main class="page-galeria">

    <section class="page-galeria__intro seccion">
        <div class="contenedor page-galeria__intro-inner">
            <h1 class="page-galeria__title">Galería</h1>
            <p class="page-galeria__lead"><?php echo esc_html($intro); ?></p>
        </div>
    </section>

    <section class="galeria-section">
        <div class="contenedor">
            <div class="galeria-filters" data-galeria-filters>
                <div class="galeria-filters__cats">
                    <?php if (!is_wp_error($categorias) && !empty($categorias)) : ?>
                        <?php foreach ($categorias as $cat) : ?>
                            <button
                                type="button"
                                class="galeria-filters__btn"
                                data-filter="<?php echo esc_attr($cat->slug); ?>">
                                <?php echo esc_html($cat->name); ?>
                            </button>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="galeria-filters__tags">
                    <button type="button" class="galeria-filters__tag is-active" data-filter="*">Todos</button>
                    <?php if (!is_wp_error($etiquetas) && !empty($etiquetas)) : ?>
                        <?php foreach ($etiquetas as $tag) : ?>
                            <button
                                type="button"
                                class="galeria-filters__tag"
                                data-filter="<?php echo esc_attr($tag->slug); ?>">
                                <?php echo esc_html($tag->name); ?>
                            </button>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="galeria-section__grid" data-galeria-root>
                <?php yuniorrojas_lista_galeria(10); ?>
            </div>
        </div>
    </section>

    <?php
    get_template_part('template-parts/quote', 'block', array(
        'cita' => 'El estilo comienza con disciplina.',
    ));
    ?>

    <?php
    get_template_part('template-parts/cta', 'banner', array(
        'titulo'             => '¿Listo para elevar tu estilo?',
        'texto'              => 'Reserva tu cita hoy y experimenta la disciplina y técnica que nos define.',
        'variante'           => 'oro',
        'mostrar_secundario' => false,
        'cta_texto'          => 'Reservar ahora',
    ));
    ?>

</main>

<div
    class="lightbox"
    data-lightbox
    hidden
    role="dialog"
    aria-modal="true"
    aria-label="<?php esc_attr_e('Vista de imagen', YUNIORROJAS_TEXT_DOMAIN); ?>">
    <button
        type="button"
        class="lightbox__backdrop"
        data-lightbox-close
        aria-label="<?php esc_attr_e('Cerrar imagen', YUNIORROJAS_TEXT_DOMAIN); ?>"
    ></button>
    <div class="lightbox__dialog">
        <button
            type="button"
            class="lightbox__close"
            data-lightbox-close
            aria-label="<?php esc_attr_e('Cerrar', YUNIORROJAS_TEXT_DOMAIN); ?>">
            <span aria-hidden="true">&times;</span>
        </button>
        <figure class="lightbox__figure" data-lightbox-figure>
            <figcaption class="lightbox__caption" data-lightbox-caption></figcaption>
        </figure>
    </div>
</div>

<?php
get_footer();

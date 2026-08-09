<?php
/**
 * Contenido reutilizable: filtros + grid paginado de servicios.
 *
 * Usado por page-listado-servicios, home.php y archive del CPT.
 */
if (!defined('ABSPATH')) {
    exit;
}

$intro = yuniorrojas_field(
    'intro_servicios',
    false,
    'Descubre una experiencia de barbería pensada para realzar tu presencia. Cada servicio combina técnica, estilo y atención al detalle.'
);

$categorias = taxonomy_exists('categoria_servicio')
    ? get_terms(array(
        'taxonomy'   => 'categoria_servicio',
        'hide_empty' => false,
    ))
    : array();
$etiquetas = taxonomy_exists('etiqueta_servicio')
    ? get_terms(array(
        'taxonomy'   => 'etiqueta_servicio',
        'hide_empty' => false,
    ))
    : array();

if (is_wp_error($categorias)) {
    $categorias = array();
}
if (is_wp_error($etiquetas)) {
    $etiquetas = array();
}
?>

<main class="page-servicios">

    <section class="page-servicios__intro seccion">
        <div class="contenedor page-servicios__intro-inner">
            <h1 class="section-title">Nuestros servicios</h1>
            <p class="page-servicios__lead"><?php echo esc_html($intro); ?></p>
        </div>
    </section>

    <section class="page-servicios__grid">
        <div class="contenedor">
            <div class="servicios-filters" data-servicios-filters>
                <div class="servicios-filters__cats">
                    <?php if (!empty($categorias)) : ?>
                        <?php foreach ($categorias as $cat) : ?>
                            <button
                                type="button"
                                class="servicios-filters__btn"
                                data-filter="<?php echo esc_attr($cat->slug); ?>">
                                <?php echo esc_html($cat->name); ?>
                            </button>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="servicios-filters__tags">
                    <button type="button" class="servicios-filters__tag is-active" data-filter="*">Todos</button>
                    <?php if (!empty($etiquetas)) : ?>
                        <?php foreach ($etiquetas as $tag) : ?>
                            <button
                                type="button"
                                class="servicios-filters__tag"
                                data-filter="<?php echo esc_attr($tag->slug); ?>">
                                <?php echo esc_html($tag->name); ?>
                            </button>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="page-servicios__root" data-servicios-root>
                <?php yuniorrojas_lista_servicios(4, true, 'listado'); ?>
            </div>
        </div>
    </section>

    <?php
    get_template_part('template-parts/quote', 'block', array(
        'cita'             => yuniorrojas_field('cita_servicios', false, 'El estilo comienza con disciplina.'),
        'mostrar_comillas' => true,
    ));
    ?>

    <?php
    get_template_part('template-parts/cta', 'banner', array(
        'titulo'             => '¿Listo para el cambio?',
        'texto'              => 'Reserva tu lugar en la silla y experimenta la verdadera maestría en barbería.',
        'variante'           => 'oscuro',
        'mostrar_secundario' => false,
        'cta_texto'          => 'Agendar cita ahora',
    ));
    ?>

    <section class="faq seccion" data-faq>
        <div class="contenedor faq__inner">
            <h2 class="section-title">Preguntas frecuentes</h2>
            <p class="faq__lead">Todo lo que necesitas saber sobre tu próxima visita.</p>

            <?php
            $faqs = yuniorrojas_field('faqs', false, array(
                array(
                    'pregunta'  => '¿Necesito cita previa?',
                    'respuesta' => 'Recomendamos agendar con antelación para garantizar tu horario y una atención sin prisas.',
                ),
                array(
                    'pregunta'  => '¿Qué métodos de pago aceptan?',
                    'respuesta' => 'Aceptamos efectivo, tarjetas de crédito/débito y transferencias digitales.',
                ),
                array(
                    'pregunta'  => '¿Dónde están ubicados?',
                    'respuesta' => 'Estamos en el corazón de la ciudad, con estacionamiento privado para nuestros clientes.',
                ),
            ));
            ?>

            <div class="faq__list">
                <?php foreach ($faqs as $index => $faq) : ?>
                    <?php
                    $pregunta  = is_array($faq) ? ($faq['pregunta'] ?? '') : '';
                    $respuesta = is_array($faq) ? ($faq['respuesta'] ?? '') : '';
                    if ($pregunta === '') {
                        continue;
                    }
                    $panel_id = 'faq-panel-' . (int) $index;
                    ?>
                    <article class="faq__item" data-faq-item>
                        <h3 class="faq__question">
                            <button
                                type="button"
                                class="faq__trigger"
                                data-faq-trigger
                                aria-expanded="false"
                                aria-controls="<?php echo esc_attr($panel_id); ?>">
                                <span class="faq__trigger-text"><?php echo esc_html($pregunta); ?></span>
                                <span class="faq__icon" aria-hidden="true"></span>
                            </button>
                        </h3>
                        <div class="faq__answer" id="<?php echo esc_attr($panel_id); ?>" data-faq-panel hidden>
                            <p><?php echo esc_html($respuesta); ?></p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

</main>

<?php
/**
 * Template Name: Listado de Servicios
 * Diseño: página Servicios (grid, frase, CTA, FAQ)
 */
get_header();

$intro = yuniorrojas_field(
    'intro_servicios',
    false,
    'Descubre una experiencia de barbería pensada para realzar tu presencia. Cada servicio combina técnica, estilo y atención al detalle.'
);
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
            <?php yuniorrojas_lista_servicios(-1, true, 'listado'); ?>
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

<?php
get_footer();

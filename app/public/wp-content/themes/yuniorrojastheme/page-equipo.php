<?php
/**
 * Template Name: Equipo / Barberos
 * Diseño: Nuestro Equipo + compromiso
 */
get_header();

$compromiso = yuniorrojas_field(
    'texto_compromiso_equipo',
    false,
    'No creo solo cortes; construyo confianza. Cada detalle importa y cada servicio refleja mi compromiso con la calidad. Mi objetivo es ayudarte a proyectar tu mejor versión mediante técnica, precisión y dedicación en cada visita.'
);
?>

<main class="page-equipo">

    <section class="equipo-hero seccion">
        <div class="contenedor equipo-hero__inner">
            <div class="equipo-hero__watermark" aria-hidden="true">JR</div>
            <h1 class="equipo-hero__title">
                <?php echo esc_html(yuniorrojas_field('titulo_equipo', false, 'Mi compromiso es contigo')); ?>
            </h1>
            <p class="equipo-hero__lead"><?php echo esc_html($compromiso); ?></p>
        </div>
    </section>

    <section class="seccion">
        <div class="contenedor">
            <?php yuniorrojas_lista_barberos(); ?>
        </div>
    </section>

    <?php
    get_template_part('template-parts/cta', 'banner', array(
        'titulo'   => '¿Listo para elevar tu estilo?',
        'texto'    => 'Reserva tu cita hoy y experimenta la verdadera elegancia masculina en manos de expertos.',
        'variante' => 'oro',
    ));
    ?>

</main>

<?php
get_footer();

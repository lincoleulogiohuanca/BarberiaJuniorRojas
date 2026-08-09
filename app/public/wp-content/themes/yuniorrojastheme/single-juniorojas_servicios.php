<?php
/**
 * Single CPT: juniorojas_servicios (clave ACF)
 * Diseño: Detalle de Servicio
 */
get_header();
?>

<main class="servicio-detalle">

    <section class="servicio__hero">
        <?php if (has_post_thumbnail()) : ?>
            <?php the_post_thumbnail('full', array('class' => 'servicio__hero-imagen')); ?>
        <?php endif; ?>
        <div class="servicio__hero-overlay"></div>
        <div class="servicio__hero-contenido">
            <h1 class="servicio__titulo"><?php the_title(); ?></h1>
            <p class="servicio__subtitulo">
                <?php echo esc_html(yuniorrojas_field('slogan_de_servicio', false, 'Técnica impecable y estilo personal.')); ?>
            </p>
        </div>
    </section>

    <section class="servicio__contenido contenedor">

        <aside class="servicio__sidebar">
            <div class="servicio__card">
                <h2 class="servicio__card-title"><?php the_title(); ?></h2>
                <?php $precio = yuniorrojas_field('precio'); ?>
                <?php if ($precio !== '') : ?>
                    <p class="servicio__card-price">S/. <?php echo esc_html($precio); ?></p>
                <?php endif; ?>
                <?php $duracion = yuniorrojas_field('tiempo_de_servicio'); ?>
                <p class="servicio__card-duration">
                    <?php if ($duracion !== '') : ?>
                        Duración estimada: <?php echo esc_html($duracion); ?> minutos.
                    <?php endif; ?>
                    Incluye asesoramiento y bebida de cortesía.
                </p>
                <?php if (yuniorrojas_puede_reservar_en_front()) : ?>
                    <a href="<?php echo esc_url(yuniorrojas_url_reservar(array('servicio' => (int) get_the_ID()))); ?>" class="servicio__card-button">
                        Reservar cita
                    </a>
                    <p class="servicio__card-nota">Se requiere depósito para confirmar.</p>
                <?php endif; ?>
            </div>

            <div class="servicio__frases">
                <?php $frase_1 = yuniorrojas_field('frase_1', false, 'Tu imagen también comunica.'); ?>
                <?php $frase_2 = yuniorrojas_field('frase_2', false, 'El estilo comienza con disciplina.'); ?>
                <blockquote class="servicio__frase-item"><?php echo esc_html($frase_1); ?></blockquote>
                <blockquote class="servicio__frase-item"><?php echo esc_html($frase_2); ?></blockquote>
            </div>
        </aside>

        <div class="servicio__main">
            <div class="servicio__experiencia">
                <h2 class="servicio__titulo-seccion servicio__titulo-seccion--borde">
                    <?php echo esc_html(yuniorrojas_field('titulo_de_experiencia', false, 'La experiencia')); ?>
                </h2>
                <div class="servicio__texto">
                    <?php the_content(); ?>
                </div>
            </div>
        </div>

        <?php $procesos = yuniorrojas_obtener_procesos(); ?>
        <?php if (!empty($procesos)) : ?>
            <div class="servicio__proceso">
                <h2 class="servicio__titulo-seccion servicio__titulo-seccion--borde servicio__titulo-seccion--oro">
                    <?php
                    $titulo_proceso = (string) yuniorrojas_field('titulo_de_proceso', false, '');
                    if ($titulo_proceso === '' || preg_match('/^\d+$/', trim($titulo_proceso))) {
                        $titulo_proceso = 'El proceso';
                    }
                    echo esc_html($titulo_proceso);
                    ?>
                </h2>
                <div class="servicio__proceso-items">
                    <?php foreach ($procesos as $index => $proceso) : ?>
                        <article class="servicio__proceso-item">
                            <span class="servicio__numero-proceso">
                                <?php echo esc_html(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)); ?>
                            </span>
                            <h3 class="servicio__titulo-proceso-item">
                                <?php echo esc_html($proceso['titulo'] ?? ''); ?>
                            </h3>
                            <p class="servicio__descripcion-proceso-item">
                                <?php echo esc_html($proceso['descripcion'] ?? ''); ?>
                            </p>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php $galeria = yuniorrojas_obtener_galeria(); ?>
        <?php if (!empty($galeria)) : ?>
            <div class="servicio__galeria">
                <div class="servicio__galeria-items">
                    <?php foreach ($galeria as $imagen_id) : ?>
                        <?php
                        $imagen_url = wp_get_attachment_image_url((int) $imagen_id, 'large');
                        if (!$imagen_url) {
                            continue;
                        }
                        $alt = get_post_meta((int) $imagen_id, '_wp_attachment_image_alt', true) ?: get_the_title();
                        ?>
                        <article class="servicio__galeria-item">
                            <img
                                src="<?php echo esc_url($imagen_url); ?>"
                                alt="<?php echo esc_attr($alt); ?>"
                                class="servicio__galeria-imagen">
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    </section>

    <?php
    $servicio_actual = get_the_ID();
    $servicios_relacionados = new WP_Query(array(
        'post_type'      => YUNIORROJAS_CPT_SERVICIOS,
        'posts_per_page' => 2,
        'post__not_in'   => array($servicio_actual),
        'orderby'        => 'rand',
    ));
    ?>

    <?php if ($servicios_relacionados->have_posts()) : ?>
        <section class="servicio__sugerencias contenedor seccion">
            <h2 class="servicio__sugerencias-titulo">
                <?php echo esc_html(yuniorrojas_field('titulo_de_sugerencias', false, 'Sugerencias para complementar')); ?>
            </h2>
            <div class="servicio__sugerencias-items">
                <?php while ($servicios_relacionados->have_posts()) : $servicios_relacionados->the_post(); ?>
                    <article class="servicio__sugerencia-item">
                        <a class="servicio__sugerencia" href="<?php the_permalink(); ?>">
                            <?php if (has_post_thumbnail()) : ?>
                                <?php the_post_thumbnail('medium_large', array(
                                    'class' => 'servicio__sugerencia-img',
                                )); ?>
                            <?php endif; ?>
                            <div class="servicio__sugerencia-overlay" aria-hidden="true"></div>
                            <div class="servicio__sugerencia-copy">
                                <h3 class="servicio__sugerencia-titulo"><?php the_title(); ?></h3>
                                <p class="servicio__sugerencia-descripcion">
                                    <?php echo esc_html(yuniorrojas_field('slogan_de_servicio')); ?>
                                </p>
                            </div>
                            <span class="servicio__sugerencia-flecha" aria-hidden="true">→</span>
                        </a>
                    </article>
                <?php endwhile; ?>
            </div>
        </section>
        <?php wp_reset_postdata(); ?>
    <?php endif; ?>

</main>

<?php
get_footer();

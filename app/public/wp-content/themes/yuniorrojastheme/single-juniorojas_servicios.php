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

            <?php
            $servicio_id   = (int) get_the_ID();
            $user_id_res   = is_user_logged_in() ? (int) get_current_user_id() : 0;
            $resenas_data  = function_exists('yuniorrojas_servicio_resenas')
                ? yuniorrojas_servicio_resenas($servicio_id, $user_id_res)
                : array('items' => array(), 'promedio' => 0, 'total' => 0, 'mi_resena' => null);
            $puede_reseñar = function_exists('yuniorrojas_es_cliente') && yuniorrojas_es_cliente();
            $mi_resena     = is_array($resenas_data['mi_resena'] ?? null) ? $resenas_data['mi_resena'] : null;
            $rating_form   = $mi_resena ? (int) $mi_resena['rating'] : 5;
            $texto_form    = $mi_resena ? (string) $mi_resena['texto'] : '';
            ?>
            <section
                class="servicio-resenas"
                data-servicio-resenas
                data-servicio-id="<?php echo esc_attr((string) $servicio_id); ?>"
                aria-labelledby="servicio-resenas-title"
            >
                <header class="servicio-resenas__head">
                    <h2 id="servicio-resenas-title" class="servicio-resenas__title">
                        <?php esc_html_e('Reseñas', YUNIORROJAS_TEXT_DOMAIN); ?>
                    </h2>
                    <div class="servicio-resenas__summary" data-resenas-summary>
                        <?php if ((int) $resenas_data['total'] > 0) : ?>
                            <?php echo yuniorrojas_resena_estrellas_html((float) $resenas_data['promedio']); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            <span class="servicio-resenas__avg" data-resenas-avg>
                                <?php echo esc_html(number_format((float) $resenas_data['promedio'], 1)); ?>
                            </span>
                            <span class="servicio-resenas__count" data-resenas-count>
                                (<?php echo esc_html((string) (int) $resenas_data['total']); ?>)
                            </span>
                        <?php else : ?>
                            <p class="servicio-resenas__empty-summary" data-resenas-empty-summary>
                                <?php esc_html_e('Sé el primero en calificar este servicio.', YUNIORROJAS_TEXT_DOMAIN); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </header>

                <div class="servicio-resenas__list" data-resenas-list>
                    <?php if (empty($resenas_data['items'])) : ?>
                        <p class="servicio-resenas__empty" data-resenas-empty>
                            <?php esc_html_e('Aún no hay comentarios públicos.', YUNIORROJAS_TEXT_DOMAIN); ?>
                        </p>
                    <?php else : ?>
                        <?php foreach ($resenas_data['items'] as $item) : ?>
                            <article class="servicio-resenas__item" data-resena-id="<?php echo esc_attr((string) $item['id']); ?>">
                                <div class="servicio-resenas__item-top">
                                    <strong class="servicio-resenas__author"><?php echo esc_html((string) $item['nombre']); ?></strong>
                                    <time class="servicio-resenas__date"><?php echo esc_html((string) $item['fecha']); ?></time>
                                </div>
                                <?php echo yuniorrojas_resena_estrellas_html((float) $item['rating']); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                <p class="servicio-resenas__text"><?php echo esc_html((string) $item['texto']); ?></p>
                                <div class="servicio-resenas__item-foot">
                                    <?php echo yuniorrojas_resena_like_html($item); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php if ($puede_reseñar) : ?>
                    <form class="servicio-resenas__form" data-resena-form novalidate>
                        <h3 class="servicio-resenas__form-title">
                            <?php echo $mi_resena
                                ? esc_html__('Actualiza tu reseña', YUNIORROJAS_TEXT_DOMAIN)
                                : esc_html__('Deja tu reseña', YUNIORROJAS_TEXT_DOMAIN); ?>
                        </h3>
                        <p class="servicio-resenas__form-lead">
                            <?php esc_html_e('Califica tu experiencia y comparte un breve comentario.', YUNIORROJAS_TEXT_DOMAIN); ?>
                        </p>
                        <div class="servicio-resenas__field">
                            <span class="servicio-resenas__label"><?php esc_html_e('Tu calificación', YUNIORROJAS_TEXT_DOMAIN); ?></span>
                            <?php echo yuniorrojas_resena_estrellas_html((float) $rating_form, true); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            <input type="hidden" name="rating" value="<?php echo esc_attr((string) $rating_form); ?>" data-resena-rating>
                        </div>
                        <label class="servicio-resenas__field">
                            <span class="servicio-resenas__label"><?php esc_html_e('Comentario', YUNIORROJAS_TEXT_DOMAIN); ?></span>
                            <textarea
                                name="texto"
                                rows="4"
                                maxlength="800"
                                data-resena-texto
                                placeholder="<?php esc_attr_e('¿Cómo fue tu experiencia con este servicio?', YUNIORROJAS_TEXT_DOMAIN); ?>"
                                required
                            ><?php echo esc_textarea($texto_form); ?></textarea>
                        </label>
                        <button type="submit" class="servicio-resenas__submit" data-resena-submit>
                            <?php echo $mi_resena
                                ? esc_html__('Guardar cambios', YUNIORROJAS_TEXT_DOMAIN)
                                : esc_html__('Publicar reseña', YUNIORROJAS_TEXT_DOMAIN); ?>
                        </button>
                        <p class="servicio-resenas__status" data-resena-status hidden></p>
                    </form>
                <?php elseif (is_user_logged_in()) : ?>
                    <p class="servicio-resenas__login-note">
                        <?php esc_html_e('Solo las cuentas de cliente pueden dejar reseñas.', YUNIORROJAS_TEXT_DOMAIN); ?>
                    </p>
                <?php else : ?>
                    <p class="servicio-resenas__login-note">
                        <a href="<?php echo esc_url(yuniorrojas_url_login()); ?>">
                            <?php esc_html_e('Inicia sesión', YUNIORROJAS_TEXT_DOMAIN); ?>
                        </a>
                        <?php esc_html_e('como cliente para calificar y comentar.', YUNIORROJAS_TEXT_DOMAIN); ?>
                    </p>
                <?php endif; ?>
            </section>
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
                                class="servicio__galeria-imagen"
                                loading="lazy"
                                decoding="async"
                                width="640"
                                height="640">
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

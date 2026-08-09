<?php
/**
 * Portada — Junior Rojas Barber Studio
 */
get_header();

$hero_titulo    = yuniorrojas_field('encabezado_bienvenida', false, 'Elegancia masculina con propósito');
$hero_texto     = yuniorrojas_field('texto_bienvenida', false, 'Tu imagen también comunica. Presencia en cada detalle.');
$hero_imagen    = yuniorrojas_field('imagen_hero');
$filosofia_img  = yuniorrojas_field('imagen_filosofia');
$filosofia_txt  = yuniorrojas_field('texto_filosofia', false, 'En Junior Rojas Barber Studio no perseguimos tendencias pasajeras: cultivamos un estándar. Cada corte nace de la escucha, la precisión y el respeto por tu imagen.');
$compromiso_txt = yuniorrojas_field('texto_compromiso', false, 'No creo solo cortes; construyo confianza. Cada detalle importa y cada servicio refleja mi compromiso con la calidad.');

$hero_url = is_array($hero_imagen) && !empty($hero_imagen['url'])
    ? $hero_imagen['url']
    : (has_post_thumbnail() ? get_the_post_thumbnail_url(null, 'full') : '');

$hero_lineas = yuniorrojas_partir_titulo_hero((string) $hero_titulo);
?>

<main class="home">

    <section
        class="home-hero"
        <?php echo $hero_url ? 'style="--home-hero-image:url(' . esc_url($hero_url) . ')"' : ''; ?>>
        <div class="home-hero__overlay" aria-hidden="true"></div>
        <div class="home-hero__content contenedor">
            <h1 class="home-hero__title">
                <span class="home-hero__title-line"><?php echo esc_html($hero_lineas['principal']); ?></span>
                <?php if ($hero_lineas['acento'] !== '') : ?>
                    <span class="home-hero__title-accent"><?php echo esc_html($hero_lineas['acento']); ?></span>
                <?php endif; ?>
            </h1>
            <p class="home-hero__subtitle"><?php echo esc_html($hero_texto); ?></p>
            <?php if (yuniorrojas_puede_reservar_en_front()) : ?>
                <a class="btn btn--primary" href="<?php echo esc_url(yuniorrojas_url_reservar()); ?>">
                    Reservar cita
                </a>
            <?php endif; ?>
        </div>
    </section>

    <section class="home-filosofia seccion">
        <div class="contenedor home-filosofia__grid">
            <div class="home-filosofia__media">
                <?php if (is_array($filosofia_img) && !empty($filosofia_img['url'])) : ?>
                    <img src="<?php echo esc_url($filosofia_img['url']); ?>" alt="Filosofía Junior Rojas">
                <?php elseif (has_post_thumbnail()) : ?>
                    <?php the_post_thumbnail('large'); ?>
                <?php endif; ?>
            </div>
            <div class="home-filosofia__copy">
                <h2 class="section-title section-title--left">Filosofía</h2>
                <div class="home-filosofia__text">
                    <?php echo wp_kses_post(wpautop($filosofia_txt)); ?>
                </div>
                <ul class="home-filosofia__valores">
                    <li>Elegancia</li>
                    <li>Maestría</li>
                    <li>Confianza</li>
                    <li>Profesionalismo</li>
                </ul>
            </div>
        </div>
    </section>

    <section class="home-servicios seccion">
        <div class="contenedor">
            <h2 class="section-title section-title--light">Nuestros servicios</h2>
            <?php yuniorrojas_lista_servicios(3, false, 'home'); ?>
            <div class="home-servicios__more">
                <a class="home-servicios__all" href="<?php echo esc_url(yuniorrojas_url_servicios()); ?>">
                    Ver todos los servicios
                </a>
            </div>
        </div>
    </section>

    <section class="home-compromiso seccion">
        <div class="contenedor home-compromiso__inner">
            <h2 class="section-title">Mi compromiso contigo</h2>
            <p class="home-compromiso__lead"><?php echo esc_html($compromiso_txt); ?></p>

            <?php
            $fundador = new WP_Query(array(
                'post_type'      => 'barberos',
                'posts_per_page' => 1,
                'orderby'        => 'menu_order',
                'order'          => 'ASC',
            ));
            ?>

            <?php if ($fundador->have_posts()) : ?>
                <?php while ($fundador->have_posts()) : $fundador->the_post(); ?>
                    <figure class="home-compromiso__portrait">
                        <?php if (has_post_thumbnail()) : ?>
                            <?php the_post_thumbnail('large', array('class' => 'home-compromiso__img')); ?>
                        <?php endif; ?>
                        <figcaption>
                            <strong><?php the_title(); ?></strong>
                            <span><?php echo esc_html(yuniorrojas_field('cargo', get_the_ID(), 'Fundador & Master Barber')); ?></span>
                        </figcaption>
                    </figure>
                <?php endwhile; ?>
                <?php wp_reset_postdata(); ?>
            <?php else : ?>
                <figure class="home-compromiso__portrait">
                    <figcaption>
                        <strong>Junior Rojas</strong>
                        <span>Fundador &amp; Master Barber</span>
                    </figcaption>
                </figure>
            <?php endif; ?>
        </div>
    </section>

    <section class="home-testimonios seccion">
        <div class="contenedor">
            <h2 class="section-title section-title--light">Testimonios reales</h2>
            <?php yuniorrojas_lista_testimoniales(9); ?>
        </div>
    </section>

    <?php
    get_template_part('template-parts/cta', 'banner', array(
        'titulo'             => '¿Listo para elevar tu estilo?',
        'texto'              => 'Reserva tu cita hoy y experimenta la disciplina y técnica que nos define.',
        'variante'           => 'oro',
        'mostrar_secundario' => false,
        'cta_texto'          => 'Reservar cita',
    ));
    ?>

</main>

<?php
get_footer();

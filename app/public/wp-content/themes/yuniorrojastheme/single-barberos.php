<?php
/**
 * Single CPT: barberos
 * Diseño: Perfil del Barbero
 * URL: /barbero/{slug}/
 */
get_header();

if (!have_posts()) {
    echo '<main class="contenedor seccion"><p>No se encontró el perfil del barbero.</p></main>';
    get_footer();
    return;
}

the_post();

$cargo     = yuniorrojas_field('cargo', get_the_ID(), 'Barbero principal');
$frase     = yuniorrojas_field('frase_barbero', get_the_ID(), 'La precisión es mi lenguaje.');
$filosofia = yuniorrojas_field('filosofia_barbero', get_the_ID(), '');
$anios     = yuniorrojas_field('anios_experiencia', get_the_ID(), '10');
$cortes    = yuniorrojas_field('cortes_autor', get_the_ID(), '5K+');
?>

<main class="perfil-barbero">

    <section class="perfil-barbero__hero">
        <div class="contenedor perfil-barbero__hero-grid">
            <div class="perfil-barbero__intro">
                <h1 class="perfil-barbero__name"><?php the_title(); ?></h1>
                <p class="perfil-barbero__role"><?php echo esc_html((string) $cargo); ?></p>
                <span class="perfil-barbero__line" aria-hidden="true"></span>
                <blockquote class="perfil-barbero__quote">
                    <?php echo esc_html((string) $frase); ?>
                </blockquote>
                <?php if (yuniorrojas_puede_reservar_en_front()) : ?>
                    <a class="btn btn--primary" href="<?php echo esc_url(yuniorrojas_url_reservar(array('barbero' => (int) get_the_ID()))); ?>">
                        Reservar cita con <?php echo esc_html(get_the_title()); ?>
                    </a>
                <?php endif; ?>
            </div>
            <div class="perfil-barbero__portrait">
                <?php
                $imagen_perfil_id = yuniorrojas_imagen_perfil_barbero((int) get_the_ID());
                if ($imagen_perfil_id > 0) :
                    echo wp_get_attachment_image(
                        $imagen_perfil_id,
                        'large',
                        false,
                        array('class' => 'perfil-barbero__img')
                    );
                elseif (has_post_thumbnail()) :
                    the_post_thumbnail('large', array('class' => 'perfil-barbero__img'));
                endif;
                ?>
            </div>
        </div>
    </section>

    <section class="perfil-barbero__filosofia seccion">
        <div class="contenedor perfil-barbero__filosofia-grid">
            <h2 class="section-title section-title--left">Filosofía</h2>
            <div class="perfil-barbero__filosofia-copy">
                <?php if ($filosofia) : ?>
                    <?php echo wp_kses_post(wpautop((string) $filosofia)); ?>
                <?php else : ?>
                    <?php the_content(); ?>
                <?php endif; ?>

                <ul class="perfil-barbero__stats">
                    <li>
                        <strong><?php echo esc_html((string) $anios); ?></strong>
                        <span>Años de exp.</span>
                    </li>
                    <li>
                        <strong><?php echo esc_html((string) $cortes); ?></strong>
                        <span>Cortes de autor</span>
                    </li>
                </ul>
            </div>
        </div>
    </section>

    <?php
    $especialidades = yuniorrojas_obtener_especialidades((int) get_the_ID());
    ?>

    <?php if (!empty($especialidades)) : ?>
        <section class="perfil-barbero__trabajo seccion">
            <div class="contenedor">
                <h2 class="section-title">Especialidades & trabajo</h2>
                <ul class="perfil-barbero__gallery">
                    <?php foreach ($especialidades as $item) : ?>
                        <?php
                        $imagen_id = (int) ($item['id'] ?? 0);
                        $titulo    = (string) ($item['titulo'] ?? '');
                        $url       = $imagen_id ? wp_get_attachment_image_url($imagen_id, 'large') : '';
                        if (!$url) {
                            continue;
                        }
                        ?>
                        <li class="perfil-barbero__gallery-item">
                            <figure class="perfil-barbero__gallery-figure">
                                <img
                                    src="<?php echo esc_url($url); ?>"
                                    alt="<?php echo esc_attr($titulo !== '' ? $titulo : get_the_title()); ?>"
                                >
                                <?php if ($titulo !== '') : ?>
                                    <figcaption class="perfil-barbero__gallery-caption">
                                        <span><?php echo esc_html($titulo); ?></span>
                                    </figcaption>
                                <?php endif; ?>
                            </figure>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </section>
    <?php endif; ?>

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

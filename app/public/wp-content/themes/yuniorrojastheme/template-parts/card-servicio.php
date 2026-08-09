<?php
/**
 * Card de servicio — listados Home / Servicios.
 *
 * @var array $args
 */

$args = isset($args) && is_array($args) ? $args : array();
$mostrar_precio = array_key_exists('mostrar_precio', $args) ? (bool) $args['mostrar_precio'] : true;
$variante       = isset($args['variante']) ? (string) $args['variante'] : 'default';
$precio         = yuniorrojas_field('precio');
$slogan         = yuniorrojas_field('slogan_de_servicio');
$descripcion    = get_the_excerpt();
if ($descripcion === '' && is_string($slogan) && $slogan !== '') {
    $descripcion = $slogan;
}
if ($descripcion === '') {
    $descripcion = wp_trim_words(wp_strip_all_tags((string) get_the_content()), 32);
}

if ($variante === 'home') :
    ?>
    <article class="servicio-card__inner servicio-card__inner--home">
        <div class="servicio-card__media servicio-card__media--home">
            <?php if (has_post_thumbnail()) : ?>
                <?php the_post_thumbnail('large', array('class' => 'servicio-card__img')); ?>
            <?php endif; ?>
            <div class="servicio-card__overlay">
                <h3 class="servicio-card__title servicio-card__title--home">
                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                </h3>
                <?php if (yuniorrojas_puede_reservar_en_front()) : ?>
                    <a class="servicio-card__reservar" href="<?php echo esc_url(yuniorrojas_url_reservar(array('servicio' => (int) get_the_ID()))); ?>">
                        Reservar
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </article>
    <?php
    return;
endif;
?>

<article class="servicio-card__inner servicio-card__inner--listado">
    <a class="servicio-card__media" href="<?php the_permalink(); ?>">
        <?php if (has_post_thumbnail()) : ?>
            <?php the_post_thumbnail('large', array('class' => 'servicio-card__img')); ?>
        <?php endif; ?>
    </a>

    <div class="servicio-card__body servicio-card__body--listado">
        <div class="servicio-card__header">
            <h3 class="servicio-card__title">
                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
            </h3>
            <?php if ($mostrar_precio && $precio !== '') : ?>
                <span class="servicio-card__price">S/. <?php echo esc_html($precio); ?></span>
            <?php endif; ?>
        </div>

        <?php if ($descripcion !== '') : ?>
            <div class="servicio-card__excerpt">
                <p><?php echo esc_html($descripcion); ?></p>
            </div>
        <?php endif; ?>

        <?php if (yuniorrojas_puede_reservar_en_front()) : ?>
            <a class="servicio-card__cta" href="<?php echo esc_url(yuniorrojas_url_reservar(array('servicio' => (int) get_the_ID()))); ?>">
                Reservar cita
            </a>
        <?php endif; ?>
    </div>
</article>

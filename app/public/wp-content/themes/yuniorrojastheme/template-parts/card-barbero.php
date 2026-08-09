<?php
/**
 * Card de barbero — equipo / home.
 */

$cargo       = yuniorrojas_field('cargo', get_the_ID(), 'Barbero');
$experiencia = yuniorrojas_field('experiencia', get_the_ID(), array());
if (is_string($experiencia) && $experiencia !== '') {
    $experiencia = array_map('trim', explode(',', $experiencia));
}
if (!is_array($experiencia)) {
    $experiencia = array();
}
?>

<article class="barbero-card__inner">
    <a class="barbero-card__media" href="<?php the_permalink(); ?>">
        <?php if (has_post_thumbnail()) : ?>
            <?php the_post_thumbnail('large', array('class' => 'barbero-card__img')); ?>
        <?php endif; ?>
    </a>

    <div class="barbero-card__body">
        <h3 class="barbero-card__name"><?php the_title(); ?></h3>
        <p class="barbero-card__role"><?php echo esc_html($cargo); ?></p>

        <div class="barbero-card__bio">
            <?php echo wp_kses_post(wp_trim_words(get_the_excerpt() ?: get_the_content(), 28)); ?>
        </div>

        <?php if (is_array($experiencia) && !empty($experiencia)) : ?>
            <ul class="barbero-card__tags">
                <?php foreach ($experiencia as $item) : ?>
                    <li class="barbero-card__tag"><?php echo esc_html($item); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <a class="barbero-card__cta" href="<?php the_permalink(); ?>">
            Ver perfil
        </a>
    </div>
</article>

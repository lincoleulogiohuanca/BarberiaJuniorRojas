<?php
/**
 * Hero de página interna.
 *
 * @var array $args {
 *   @type string $titulo
 *   @type string $subtitulo
 *   @type string $imagen_url
 * }
 */

$titulo     = $args['titulo'] ?? get_the_title();
$subtitulo  = $args['subtitulo'] ?? yuniorrojas_eslogan();
$imagen_url = $args['imagen_url'] ?? '';

if (!$imagen_url && has_post_thumbnail()) {
    $imagen_url = get_the_post_thumbnail_url(null, 'full');
}
?>

<section class="page-hero" <?php echo $imagen_url ? 'style="--page-hero-image:url(' . esc_url($imagen_url) . ')"' : ''; ?>>
    <div class="page-hero__overlay"></div>
    <div class="page-hero__content contenedor">
        <h1 class="page-hero__title"><?php echo esc_html($titulo); ?></h1>
        <?php if ($subtitulo) : ?>
            <p class="page-hero__subtitle"><?php echo esc_html($subtitulo); ?></p>
        <?php endif; ?>
    </div>
</section>

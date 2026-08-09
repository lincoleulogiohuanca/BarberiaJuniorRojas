<?php
/**
 * Franja CTA reutilizable.
 *
 * @var array $args
 */

$titulo    = $args['titulo'] ?? '¿Listo para elevar tu estilo?';
$texto     = $args['texto'] ?? 'Reserva tu cita hoy y experimenta la verdadera elegancia masculina en manos de expertos.';
$variante  = $args['variante'] ?? 'oro'; // oro | oscuro
$mostrar_secundario = $args['mostrar_secundario'] ?? true;
$cta_texto = $args['cta_texto'] ?? 'Reservar ahora';
$mostrar_reservar = yuniorrojas_puede_reservar_en_front();
?>

<section class="cta-banner cta-banner--<?php echo esc_attr($variante); ?>">
    <div class="cta-banner__inner contenedor">
        <div class="cta-banner__copy">
            <h2 class="cta-banner__title"><?php echo esc_html($titulo); ?></h2>
            <?php if ($texto) : ?>
                <p class="cta-banner__text"><?php echo esc_html($texto); ?></p>
            <?php endif; ?>
        </div>
        <?php if ($mostrar_reservar || $mostrar_secundario) : ?>
            <div class="cta-banner__actions">
                <?php if ($mostrar_reservar) : ?>
                    <a class="btn btn--primary" href="<?php echo esc_url(yuniorrojas_url_reservar()); ?>">
                        <?php echo esc_html($cta_texto); ?>
                    </a>
                <?php endif; ?>
                <?php if ($mostrar_secundario) : ?>
                    <a class="btn btn--outline" href="<?php echo esc_url(yuniorrojas_url_servicios()); ?>">
                        Ver servicios
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

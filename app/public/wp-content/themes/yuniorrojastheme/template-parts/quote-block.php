<?php
/**
 * Cita tipográfica centrada.
 *
 * @var array $args
 */

$cita = $args['cita'] ?? 'El estilo comienza con disciplina.';
$mostrar_comillas = array_key_exists('mostrar_comillas', $args) ? (bool) $args['mostrar_comillas'] : true;
?>

<section class="quote-block">
    <div class="contenedor">
        <blockquote class="quote-block__text">
            <?php if ($mostrar_comillas) : ?>“<?php endif; ?><?php echo esc_html($cita); ?><?php if ($mostrar_comillas) : ?>”<?php endif; ?>
        </blockquote>
        <span class="quote-block__line" aria-hidden="true"></span>
    </div>
</section>

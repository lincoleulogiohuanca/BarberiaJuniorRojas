<?php
/**
 * Fallback de listado de servicios (si no se usa la plantilla Page).
 */
get_header();
?>

<main class="contenedor seccion">
    <h1 class="section-title">Servicios</h1>
    <?php yuniorrojas_lista_servicios(); ?>
</main>

<?php
get_footer();

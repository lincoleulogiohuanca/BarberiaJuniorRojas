<?php
/**
 * Página genérica.
 * Seguridad: /servicios/ siempre usa el listado con filtros y paginación
 * aunque no se asigne la plantilla "Listado de Servicios".
 */
get_header();

if (is_page('servicios')) {
    get_template_part('template-parts/listado', 'servicios');
} else {
    ?>
    <main class="contenedor seccion">
        <?php get_template_part('template-parts/pagina'); ?>
    </main>
    <?php
}

get_footer();

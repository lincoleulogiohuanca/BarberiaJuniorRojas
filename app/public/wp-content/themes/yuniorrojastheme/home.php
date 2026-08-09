<?php
/**
 * Fallback de listado de servicios (posts page / home.php).
 * Antes mostraba todos los servicios sin filtros ni paginación.
 */
get_header();
get_template_part('template-parts/listado', 'servicios');
get_footer();

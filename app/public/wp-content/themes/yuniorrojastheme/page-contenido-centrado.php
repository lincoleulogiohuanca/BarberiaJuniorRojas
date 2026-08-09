<?php
/*
 * Template Name: Contenido Centrado
 */
get_header();
?>

<main class="contenedor seccion contenido-centrado">
    <?php
    while (have_posts()) :
        the_post();
        the_title('<h1 class="section-title">', '</h1>');
        the_content();
    endwhile;
    ?>
</main>

<?php
get_footer();

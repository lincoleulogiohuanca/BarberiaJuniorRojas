<?php
/**
 * Single genérico (posts). El detalle de servicios vive en single-juniorojas_servicios.php
 */
get_header();
?>

<main class="contenedor seccion">
    <?php
    while (have_posts()) :
        the_post();
        ?>
        <article <?php post_class('entrada'); ?>>
            <h1><?php the_title(); ?></h1>
            <?php if (has_post_thumbnail()) : ?>
                <?php the_post_thumbnail('large', array('class' => 'imagen-destacada')); ?>
            <?php endif; ?>
            <div class="entrada__contenido">
                <?php the_content(); ?>
            </div>
        </article>
        <?php
    endwhile;
    ?>
</main>

<?php
get_footer();

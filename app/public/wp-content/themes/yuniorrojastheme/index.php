<?php
get_header();
?>

<main class="contenedor seccion">
    <?php if (have_posts()) : ?>
        <?php while (have_posts()) : the_post(); ?>
            <article <?php post_class(); ?>>
                <h1><?php the_title(); ?></h1>
                <?php the_content(); ?>
            </article>
        <?php endwhile; ?>
    <?php else : ?>
        <p>No hay contenido para mostrar.</p>
    <?php endif; ?>
</main>

<?php
get_footer();

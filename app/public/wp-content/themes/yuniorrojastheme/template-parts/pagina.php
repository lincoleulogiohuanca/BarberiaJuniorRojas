<?php
while (have_posts()) :
    the_post();
    ?>
    <article <?php post_class('pagina'); ?>>
        <h1 class="section-title"><?php the_title(); ?></h1>
        <?php if (has_post_thumbnail()) : ?>
            <?php the_post_thumbnail('large', array('class' => 'imagen-destacada')); ?>
        <?php endif; ?>
        <div class="pagina__contenido">
            <?php the_content(); ?>
        </div>
    </article>
    <?php
endwhile;

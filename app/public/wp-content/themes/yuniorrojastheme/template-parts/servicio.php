<?php
while (have_posts()):
    the_post();
    if (has_post_thumbnail()) {
        the_post_thumbnail('full', array('class' => 'imagen-destacada'));
    }
    ?>
    <article class="servicio__card">
        <div class="servicio__card-info">
            <div>
                <a href="<?php the_permalink(); ?>">
                    <h2 class="servicio__card-title">
                        <?php the_title(); ?>
                    </h2>
                </a>
                <?php if (get_field('precio')): ?>
                    <span class="servicio__card-price">
                        S/. <?php the_field('precio'); ?>
                    </span>
                <?php endif; ?>
            </div>

            <!-- Descripción -->
            <div>
                <div class="servicio__card-description">
                    <?php the_excerpt(); ?>
                </div>
            </div>
            <!-- Botón -->
            <?php if (yuniorrojas_puede_reservar_en_front()) : ?>
                <div>
                    <a class="servicio__card-button"
                        href="<?php echo esc_url(yuniorrojas_url_reservar(array('servicio' => (int) get_the_ID()))); ?>">
                        Reservar cita
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </article>

<?php
endwhile;

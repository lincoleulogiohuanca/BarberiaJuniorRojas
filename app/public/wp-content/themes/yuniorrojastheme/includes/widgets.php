<?php

if (!defined('ABSPATH')) {
    exit;
}

class yuniorrojas_servicios_widget extends WP_Widget
{
    public function __construct()
    {
        parent::__construct(
            'yuniorrojas_servicios_widget',
            esc_html__('Junior Servicios', YUNIORROJAS_TEXT_DOMAIN),
            array('description' => esc_html__('Agrega los Servicios como Widget', YUNIORROJAS_TEXT_DOMAIN))
        );
    }

    public function widget($args, $instance)
    {
        $cantidad = !empty($instance['cantidad']) ? absint($instance['cantidad']) : 3;

        echo $args['before_widget'] ?? '';

        $query_args = array(
            'post_type'      => YUNIORROJAS_CPT_SERVICIOS,
            'posts_per_page' => $cantidad,
            'post_status'    => 'publish',
        );

        $servicios = new WP_Query($query_args);
        ?>
        <ul class="servicios-sidebar">
            <?php while ($servicios->have_posts()) : $servicios->the_post(); ?>
                <li>
                    <div class="imagen">
                        <?php the_post_thumbnail('thumbnail'); ?>
                    </div>
                    <div class="contenido-servicio">
                        <a href="<?php the_permalink(); ?>">
                            <h3><?php the_title(); ?></h3>
                        </a>
                        <p><?php echo esc_html((string) yuniorrojas_field('descripcion')); ?></p>
                    </div>
                </li>
            <?php endwhile; ?>
            <?php wp_reset_postdata(); ?>
        </ul>
        <?php

        echo $args['after_widget'] ?? '';
    }

    public function form($instance)
    {
        $cantidad = !empty($instance['cantidad']) ? absint($instance['cantidad']) : 3;
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('cantidad')); ?>">
                <?php esc_html_e('¿Cuántos servicios deseas mostrar?', YUNIORROJAS_TEXT_DOMAIN); ?>
            </label>
            <input
                class="widefat"
                id="<?php echo esc_attr($this->get_field_id('cantidad')); ?>"
                name="<?php echo esc_attr($this->get_field_name('cantidad')); ?>"
                type="number"
                min="1"
                value="<?php echo esc_attr((string) $cantidad); ?>"
            >
        </p>
        <?php
    }

    public function update($new_instance, $old_instance)
    {
        return array(
            'cantidad' => !empty($new_instance['cantidad']) ? absint($new_instance['cantidad']) : 3,
        );
    }
}

function yuniorrojas_registrar_widget(): void
{
    register_widget('yuniorrojas_servicios_widget');
}
add_action('widgets_init', 'yuniorrojas_registrar_widget');

// Compatibilidad con instancias antiguas del widget.
if (!class_exists('juniorrojas_servicios_widget', false)) {
    class_alias('yuniorrojas_servicios_widget', 'juniorrojas_servicios_widget');
}
if (!class_exists('juniorojas_servicios_widget', false)) {
    class_alias('yuniorrojas_servicios_widget', 'juniorojas_servicios_widget');
}

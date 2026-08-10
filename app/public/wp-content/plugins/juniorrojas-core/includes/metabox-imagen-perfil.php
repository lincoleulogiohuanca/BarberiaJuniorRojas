<?php
/**
 * Metabox: imagen del perfil (detalle).
 * Meta: _yuniorrojas_imagen_perfil (attachment ID)
 *
 * Imagen destacada = home / listado.
 * Esta imagen = página de detalle (más alta).
 */

if (!defined('ABSPATH')) {
    exit;
}

function yuniorrojas_agregar_metabox_imagen_perfil(): void
{
    add_meta_box(
        'yuniorrojas_imagen_perfil',
        __('Imagen del perfil (detalle)', YUNIORROJAS_TEXT_DOMAIN),
        'yuniorrojas_renderizar_metabox_imagen_perfil',
        'barberos',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'yuniorrojas_agregar_metabox_imagen_perfil');

/**
 * @param WP_Post $post
 */
function yuniorrojas_renderizar_metabox_imagen_perfil($post): void
{
    wp_nonce_field('yuniorrojas_guardar_imagen_perfil', 'yuniorrojas_imagen_perfil_nonce');

    $imagen_id = (int) get_post_meta($post->ID, '_yuniorrojas_imagen_perfil', true);
    $url       = $imagen_id ? wp_get_attachment_image_url($imagen_id, 'medium') : '';
    ?>
    <div id="yuniorrojas-imagen-perfil" class="yuniorrojas-imagen-perfil">
        <p class="description">
            <?php esc_html_e('Foto vertical para la página de detalle. La imagen destacada se usa en Inicio / Equipo.', YUNIORROJAS_TEXT_DOMAIN); ?>
        </p>

        <div id="yuniorrojas-imagen-perfil-preview" class="yuniorrojas-imagen-perfil__preview">
            <?php if ($url) : ?>
                <img src="<?php echo esc_url($url); ?>" alt="">
            <?php endif; ?>
        </div>

        <input
            type="hidden"
            id="yuniorrojas-imagen-perfil-input"
            name="yuniorrojas_imagen_perfil"
            value="<?php echo esc_attr((string) $imagen_id); ?>"
        >

        <p class="yuniorrojas-imagen-perfil__actions">
            <button type="button" class="button" id="yuniorrojas-seleccionar-imagen-perfil">
                <?php echo $imagen_id ? esc_html__('Cambiar imagen', YUNIORROJAS_TEXT_DOMAIN) : esc_html__('Seleccionar imagen', YUNIORROJAS_TEXT_DOMAIN); ?>
            </button>
            <button
                type="button"
                class="button"
                id="yuniorrojas-quitar-imagen-perfil"
                <?php echo $imagen_id ? '' : 'hidden'; ?>
            >
                <?php esc_html_e('Quitar', YUNIORROJAS_TEXT_DOMAIN); ?>
            </button>
        </p>
    </div>
    <?php
}

function yuniorrojas_guardar_imagen_perfil(int $post_id): void
{
    if (!yuniorrojas_verificar_nonce('yuniorrojas_imagen_perfil_nonce', 'yuniorrojas_guardar_imagen_perfil')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $imagen_id = isset($_POST['yuniorrojas_imagen_perfil'])
        ? absint(wp_unslash($_POST['yuniorrojas_imagen_perfil']))
        : 0;

    if ($imagen_id > 0) {
        update_post_meta($post_id, '_yuniorrojas_imagen_perfil', $imagen_id);
    } else {
        delete_post_meta($post_id, '_yuniorrojas_imagen_perfil');
    }
}
add_action('save_post_barberos', 'yuniorrojas_guardar_imagen_perfil');

/**
 * ID de imagen del detalle de barbero (metabox o ACF imagen_perfil).
 */
function yuniorrojas_imagen_perfil_barbero(int $post_id = 0): int
{
    $post_id = $post_id > 0 ? $post_id : (int) get_the_ID();

    $meta = (int) get_post_meta($post_id, '_yuniorrojas_imagen_perfil', true);
    if ($meta > 0) {
        return $meta;
    }

    $acf = yuniorrojas_field('imagen_perfil', $post_id, null);
    if (is_numeric($acf)) {
        return (int) $acf;
    }
    if (is_array($acf) && !empty($acf['ID'])) {
        return (int) $acf['ID'];
    }

    return 0;
}

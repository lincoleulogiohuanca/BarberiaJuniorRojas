<?php
/**
 * Metabox: Galería del servicio (sin ACF Pro).
 * Meta: _yuniorrojas_galeria (array de attachment IDs)
 */

if (!defined('ABSPATH')) {
    exit;
}

function yuniorrojas_agregar_metabox_galeria(): void
{
    add_meta_box(
        'yuniorrojas_galeria',
        __('Galería del Servicio', YUNIORROJAS_TEXT_DOMAIN),
        'yuniorrojas_renderizar_metabox_galeria',
        YUNIORROJAS_CPT_SERVICIOS,
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'yuniorrojas_agregar_metabox_galeria');

function yuniorrojas_renderizar_metabox_galeria($post): void
{
    wp_nonce_field('yuniorrojas_guardar_galeria', 'yuniorrojas_galeria_nonce');

    $imagenes = get_post_meta($post->ID, '_yuniorrojas_galeria', true);
    if (!is_array($imagenes)) {
        $imagenes = array();
    }
    ?>
    <div id="yuniorrojas-galeria">
        <div id="yuniorrojas-galeria-preview">
            <?php foreach ($imagenes as $imagen_id) : ?>
                <?php
                $imagen_url = wp_get_attachment_image_url((int) $imagen_id, 'medium');
                if (!$imagen_url) {
                    continue;
                }
                ?>
                <div class="yuniorrojas-imagen" data-id="<?php echo esc_attr((string) $imagen_id); ?>">
                    <img src="<?php echo esc_url($imagen_url); ?>" alt="">
                    <button type="button" class="button yuniorrojas-eliminar-imagen" aria-label="<?php esc_attr_e('Eliminar', YUNIORROJAS_TEXT_DOMAIN); ?>">×</button>
                </div>
            <?php endforeach; ?>
        </div>

        <input
            type="hidden"
            id="yuniorrojas-galeria-input"
            name="yuniorrojas_galeria"
            value="<?php echo esc_attr(implode(',', array_map('strval', $imagenes))); ?>"
        >

        <button type="button" class="button button-primary" id="yuniorrojas-agregar-imagenes">
            + <?php esc_html_e('Añadir imágenes', YUNIORROJAS_TEXT_DOMAIN); ?>
        </button>
    </div>
    <?php
}

function yuniorrojas_guardar_galeria(int $post_id): void
{
    if (!yuniorrojas_verificar_nonce('yuniorrojas_galeria_nonce', 'yuniorrojas_guardar_galeria')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $imagenes = isset($_POST['yuniorrojas_galeria'])
        ? sanitize_text_field(wp_unslash($_POST['yuniorrojas_galeria']))
        : '';

    $imagenes = array_values(array_filter(array_map('absint', explode(',', $imagenes))));

    if (!empty($imagenes)) {
        update_post_meta($post_id, '_yuniorrojas_galeria', $imagenes);
    } else {
        delete_post_meta($post_id, '_yuniorrojas_galeria');
    }
}
add_action('save_post_' . YUNIORROJAS_CPT_SERVICIOS, 'yuniorrojas_guardar_galeria');

<?php
/**
 * Metabox: Especialidades & trabajo (barbero).
 * Meta: _yuniorrojas_especialidades
 * Formato: array<int, array{id:int, titulo:string}>
 */

if (!defined('ABSPATH')) {
    exit;
}

function yuniorrojas_agregar_metabox_especialidades(): void
{
    add_meta_box(
        'yuniorrojas_especialidades',
        __('Especialidades & trabajo', YUNIORROJAS_TEXT_DOMAIN),
        'yuniorrojas_renderizar_metabox_especialidades',
        'barberos',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'yuniorrojas_agregar_metabox_especialidades');

/**
 * @param WP_Post $post
 */
function yuniorrojas_renderizar_metabox_especialidades($post): void
{
    wp_nonce_field('yuniorrojas_guardar_especialidades', 'yuniorrojas_especialidades_nonce');

    $items = yuniorrojas_obtener_especialidades((int) $post->ID);
    ?>
    <div id="yuniorrojas-especialidades">
        <p class="description">
            <?php esc_html_e('Añade fotos y un título para cada especialidad (se muestra al pasar el mouse en el perfil).', YUNIORROJAS_TEXT_DOMAIN); ?>
        </p>

        <div id="yuniorrojas-especialidades-preview" class="yuniorrojas-galeria-preview yuniorrojas-especialidades-preview">
            <?php foreach ($items as $index => $item) : ?>
                <?php
                $imagen_id = (int) ($item['id'] ?? 0);
                $titulo    = (string) ($item['titulo'] ?? '');
                $imagen_url = $imagen_id ? wp_get_attachment_image_url($imagen_id, 'medium') : '';
                if (!$imagen_url) {
                    continue;
                }
                ?>
                <div class="yuniorrojas-especialidad" data-id="<?php echo esc_attr((string) $imagen_id); ?>">
                    <div class="yuniorrojas-especialidad__media">
                        <img src="<?php echo esc_url($imagen_url); ?>" alt="">
                        <button type="button" class="button yuniorrojas-eliminar-especialidad" aria-label="<?php esc_attr_e('Eliminar', YUNIORROJAS_TEXT_DOMAIN); ?>">×</button>
                    </div>
                    <input
                        type="text"
                        class="widefat yuniorrojas-especialidad__titulo"
                        name="yuniorrojas_especialidades[<?php echo esc_attr((string) $index); ?>][titulo]"
                        value="<?php echo esc_attr($titulo); ?>"
                        placeholder="<?php esc_attr_e('Ej: Degradado', YUNIORROJAS_TEXT_DOMAIN); ?>"
                    >
                    <input
                        type="hidden"
                        name="yuniorrojas_especialidades[<?php echo esc_attr((string) $index); ?>][id]"
                        value="<?php echo esc_attr((string) $imagen_id); ?>"
                    >
                </div>
            <?php endforeach; ?>
        </div>

        <button type="button" class="button button-primary" id="yuniorrojas-agregar-especialidades">
            + <?php esc_html_e('Añadir imágenes', YUNIORROJAS_TEXT_DOMAIN); ?>
        </button>
    </div>
    <?php
}

function yuniorrojas_guardar_especialidades(int $post_id): void
{
    if (!yuniorrojas_verificar_nonce('yuniorrojas_especialidades_nonce', 'yuniorrojas_guardar_especialidades')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $raw = isset($_POST['yuniorrojas_especialidades']) && is_array($_POST['yuniorrojas_especialidades'])
        ? wp_unslash($_POST['yuniorrojas_especialidades'])
        : array();

    $limpios = array();

    foreach ($raw as $item) {
        if (!is_array($item)) {
            continue;
        }

        $id = isset($item['id']) ? absint($item['id']) : 0;
        if ($id < 1) {
            continue;
        }

        $titulo = isset($item['titulo']) ? sanitize_text_field((string) $item['titulo']) : '';
        if ($titulo === '') {
            $titulo = (string) get_the_title($id);
        }

        $limpios[] = array(
            'id'     => $id,
            'titulo' => $titulo,
        );
    }

    if (!empty($limpios)) {
        update_post_meta($post_id, '_yuniorrojas_especialidades', $limpios);
    } else {
        delete_post_meta($post_id, '_yuniorrojas_especialidades');
    }
}
add_action('save_post_barberos', 'yuniorrojas_guardar_especialidades');

/**
 * @return array<int, array{id:int, titulo:string}>
 */
function yuniorrojas_obtener_especialidades(int $post_id = 0): array
{
    $post_id = $post_id > 0 ? $post_id : (int) get_the_ID();
    $meta    = get_post_meta($post_id, '_yuniorrojas_especialidades', true);

    if (is_array($meta) && !empty($meta)) {
        return yuniorrojas_normalizar_especialidades($meta);
    }

    $acf = yuniorrojas_field('galeria_especialidades', $post_id, array());
    if (!is_array($acf) || empty($acf)) {
        return array();
    }

    return yuniorrojas_normalizar_especialidades($acf);
}

/**
 * @param array<int, mixed> $items
 * @return array<int, array{id:int, titulo:string}>
 */
function yuniorrojas_normalizar_especialidades(array $items): array
{
    $limpios = array();

    foreach ($items as $item) {
        $id     = 0;
        $titulo = '';

        if (is_numeric($item)) {
            $id = (int) $item;
        } elseif (is_array($item)) {
            if (!empty($item['id'])) {
                $id = (int) $item['id'];
            } elseif (!empty($item['ID'])) {
                $id = (int) $item['ID'];
            }

            $titulo = sanitize_text_field((string) ($item['titulo'] ?? $item['title'] ?? $item['alt'] ?? ''));
        }

        if ($id < 1) {
            continue;
        }

        if ($titulo === '') {
            $titulo = (string) (get_post_meta($id, '_wp_attachment_image_alt', true) ?: get_the_title($id));
        }

        $limpios[] = array(
            'id'     => $id,
            'titulo' => $titulo,
        );
    }

    return $limpios;
}

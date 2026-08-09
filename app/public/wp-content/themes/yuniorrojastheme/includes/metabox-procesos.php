<?php
/**
 * Metabox: El Proceso (sin ACF Pro).
 * Meta: _yuniorrojas_procesos
 *
 * Cantidad variable: el editor añade 1, 4, 6… procesos por servicio.
 */

if (!defined('ABSPATH')) {
    exit;
}

function yuniorrojas_agregar_metabox_procesos(): void
{
    add_meta_box(
        'yuniorrojas_procesos',
        __('El Proceso', YUNIORROJAS_TEXT_DOMAIN),
        'yuniorrojas_renderizar_metabox_procesos',
        YUNIORROJAS_CPT_SERVICIOS,
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'yuniorrojas_agregar_metabox_procesos');

/**
 * @param WP_Post $post
 */
function yuniorrojas_renderizar_metabox_procesos($post): void
{
    wp_nonce_field('yuniorrojas_guardar_procesos', 'yuniorrojas_procesos_nonce');

    $procesos = yuniorrojas_obtener_procesos($post->ID);
    $total    = count($procesos);
    ?>
    <p class="yuniorrojas-procesos-help description">
        <?php
        esc_html_e(
            'Añade tantos pasos como necesite este servicio (1, 4, 6…). Arrastra para reordenar. Solo se guardan los que tengan título o descripción.',
            YUNIORROJAS_TEXT_DOMAIN
        );
        ?>
    </p>

    <p class="yuniorrojas-procesos-count" data-procesos-count>
        <?php
        printf(
            /* translators: %d: number of process steps */
            esc_html(_n('%d proceso', '%d procesos', $total, YUNIORROJAS_TEXT_DOMAIN)),
            $total
        );
        ?>
    </p>

    <div id="yuniorrojas-procesos" class="yuniorrojas-procesos" data-procesos-list>
        <?php if (empty($procesos)) : ?>
            <p class="yuniorrojas-procesos-empty" data-procesos-empty>
                <?php esc_html_e('Todavía no hay procesos. Pulsa “Añadir proceso” para crear el primero.', YUNIORROJAS_TEXT_DOMAIN); ?>
            </p>
        <?php endif; ?>

        <?php foreach ($procesos as $index => $proceso) : ?>
            <?php yuniorrojas_renderizar_fila_proceso((int) $index, $proceso); ?>
        <?php endforeach; ?>
    </div>

    <p class="yuniorrojas-procesos-actions">
        <button type="button" class="button button-primary" id="yuniorrojas-agregar-proceso">
            + <?php esc_html_e('Añadir proceso', YUNIORROJAS_TEXT_DOMAIN); ?>
        </button>
    </p>
    <?php
}

/**
 * Fila de un proceso en el metabox.
 *
 * @param array{titulo?:string,descripcion?:string} $proceso
 */
function yuniorrojas_renderizar_fila_proceso(int $index, array $proceso = array()): void
{
    $numero = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
    ?>
    <div class="yuniorrojas-proceso" data-proceso-item>
        <div class="yuniorrojas-proceso__header">
            <span class="yuniorrojas-proceso__handle" title="<?php esc_attr_e('Arrastrar para reordenar', YUNIORROJAS_TEXT_DOMAIN); ?>" aria-hidden="true">⋮⋮</span>
            <strong class="yuniorrojas-proceso__numero" data-proceso-numero>
                PROCESO <?php echo esc_html($numero); ?>
            </strong>
            <button type="button" class="button yuniorrojas-eliminar-proceso">
                <?php esc_html_e('Eliminar', YUNIORROJAS_TEXT_DOMAIN); ?>
            </button>
        </div>
        <div class="yuniorrojas-proceso__body">
            <p>
                <label><strong><?php esc_html_e('Título', YUNIORROJAS_TEXT_DOMAIN); ?></strong></label>
                <input
                    type="text"
                    class="widefat"
                    data-campo="titulo"
                    name="yuniorrojas_procesos[<?php echo esc_attr((string) $index); ?>][titulo]"
                    value="<?php echo esc_attr($proceso['titulo'] ?? ''); ?>"
                    placeholder="<?php esc_attr_e('Ejemplo: Consulta de imagen', YUNIORROJAS_TEXT_DOMAIN); ?>"
                >
            </p>
            <p>
                <label><strong><?php esc_html_e('Descripción', YUNIORROJAS_TEXT_DOMAIN); ?></strong></label>
                <textarea
                    class="widefat"
                    rows="4"
                    data-campo="descripcion"
                    name="yuniorrojas_procesos[<?php echo esc_attr((string) $index); ?>][descripcion]"
                    placeholder="<?php esc_attr_e('Describe este proceso...', YUNIORROJAS_TEXT_DOMAIN); ?>"
                ><?php echo esc_textarea($proceso['descripcion'] ?? ''); ?></textarea>
            </p>
        </div>
    </div>
    <?php
}

function yuniorrojas_guardar_procesos(int $post_id): void
{
    if (!yuniorrojas_verificar_nonce('yuniorrojas_procesos_nonce', 'yuniorrojas_guardar_procesos')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $procesos = isset($_POST['yuniorrojas_procesos']) && is_array($_POST['yuniorrojas_procesos'])
        ? wp_unslash($_POST['yuniorrojas_procesos'])
        : array();

    /** @var array<int, mixed> $procesos */
    $procesos_limpios = yuniorrojas_normalizar_procesos($procesos);

    if (!empty($procesos_limpios)) {
        update_post_meta($post_id, '_yuniorrojas_procesos', $procesos_limpios);
    } else {
        delete_post_meta($post_id, '_yuniorrojas_procesos');
    }
}
add_action('save_post_' . YUNIORROJAS_CPT_SERVICIOS, 'yuniorrojas_guardar_procesos');

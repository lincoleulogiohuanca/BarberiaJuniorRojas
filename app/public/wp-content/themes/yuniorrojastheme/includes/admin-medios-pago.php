<?php
/**
 * Admin: CPT + metabox Medios de pago.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registrar CPT medios de pago.
 */
function yuniorrojas_registrar_cpt_medios_pago(): void
{
    if (post_type_exists(YUNIORROJAS_CPT_MEDIOS_PAGO)) {
        return;
    }

    register_post_type(YUNIORROJAS_CPT_MEDIOS_PAGO, array(
        'labels' => array(
            'name'               => __('Medios de pago', YUNIORROJAS_TEXT_DOMAIN),
            'singular_name'      => __('Medio de pago', YUNIORROJAS_TEXT_DOMAIN),
            'add_new'            => __('Añadir medio', YUNIORROJAS_TEXT_DOMAIN),
            'add_new_item'       => __('Añadir medio de pago', YUNIORROJAS_TEXT_DOMAIN),
            'edit_item'          => __('Editar medio de pago', YUNIORROJAS_TEXT_DOMAIN),
            'new_item'           => __('Nuevo medio de pago', YUNIORROJAS_TEXT_DOMAIN),
            'view_item'          => __('Ver medio de pago', YUNIORROJAS_TEXT_DOMAIN),
            'search_items'       => __('Buscar medios de pago', YUNIORROJAS_TEXT_DOMAIN),
            'not_found'          => __('No hay medios de pago', YUNIORROJAS_TEXT_DOMAIN),
            'not_found_in_trash' => __('No hay medios en la papelera', YUNIORROJAS_TEXT_DOMAIN),
            'menu_name'          => __('Medios de pago', YUNIORROJAS_TEXT_DOMAIN),
        ),
        'public'              => false,
        'show_ui'             => true,
        'show_in_menu'        => 'edit.php?post_type=' . YUNIORROJAS_CPT_RESERVAS,
        'capability_type'     => 'post',
        'map_meta_cap'        => true,
        'hierarchical'        => false,
        'supports'            => array('title', 'page-attributes'),
        'has_archive'         => false,
        'rewrite'             => false,
        'query_var'           => false,
        'show_in_rest'        => false,
        'menu_position'       => 25,
    ));
}
add_action('init', 'yuniorrojas_registrar_cpt_medios_pago', 6);

/**
 * Seed defaults en admin.
 */
function yuniorrojas_medios_pago_admin_init_seed(): void
{
    if (!is_admin() || !function_exists('yuniorrojas_medios_pago_seed_si_vacio')) {
        return;
    }
    yuniorrojas_medios_pago_seed_si_vacio();
}
add_action('admin_init', 'yuniorrojas_medios_pago_admin_init_seed', 5);

/**
 * Metabox configuración.
 */
function yuniorrojas_medios_pago_metaboxes(): void
{
    add_meta_box(
        'jr_medio_pago_config',
        __('Configuración del medio', YUNIORROJAS_TEXT_DOMAIN),
        'yuniorrojas_medios_pago_render_metabox',
        YUNIORROJAS_CPT_MEDIOS_PAGO,
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'yuniorrojas_medios_pago_metaboxes');

/**
 * @param WP_Post $post Post.
 */
function yuniorrojas_medios_pago_render_metabox(WP_Post $post): void
{
    wp_nonce_field('yuniorrojas_medio_pago_save', 'yuniorrojas_medio_pago_nonce');

    $medio = yuniorrojas_medio_pago_desde_post($post);
    $tipos = yuniorrojas_medios_pago_tipos();
    $tipo  = (string) ($medio['tipo'] ?? 'manual');
    ?>
    <style>
        .jr-medio-fields { display:grid; gap:1rem; max-width:720px; }
        .jr-medio-fields label { display:block; font-weight:600; margin-bottom:.35rem; }
        .jr-medio-fields .description { margin-top:.35rem; }
        .jr-medio-grid { display:grid; grid-template-columns:1fr 1fr; gap:1rem; }
        .jr-medio-manual { <?php echo $tipo !== 'manual' ? 'display:none;' : ''; ?> }
        .jr-medio-tip { padding:.75rem 1rem; background:#f0f6fc; border-left:4px solid #2271b1; }
        @media (max-width:782px){ .jr-medio-grid{ grid-template-columns:1fr; } }
    </style>
    <div class="jr-medio-fields">
        <p class="jr-medio-tip">
            <?php esc_html_e('Publicado = visible en el checkout de reservas. Borrador = oculto. Orden = campo “Orden” del editor (atributos de página).', YUNIORROJAS_TEXT_DOMAIN); ?>
        </p>

        <p>
            <label for="jr_medio_tipo"><?php esc_html_e('Tipo de integración', YUNIORROJAS_TEXT_DOMAIN); ?></label>
            <select name="jr_medio_tipo" id="jr_medio_tipo" class="widefat">
                <?php foreach ($tipos as $key => $label) : ?>
                    <option value="<?php echo esc_attr($key); ?>" <?php selected($tipo, $key); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <span class="description">
                <?php esc_html_e('Culqi: cobro online automático. Manual: el cliente transfiere y envía código. Estudio: paga al llegar.', YUNIORROJAS_TEXT_DOMAIN); ?>
            </span>
        </p>

        <div class="jr-medio-grid">
            <p>
                <label for="jr_medio_icono"><?php esc_html_e('Icono (clase Tabler)', YUNIORROJAS_TEXT_DOMAIN); ?></label>
                <input type="text" class="widefat" name="jr_medio_icono" id="jr_medio_icono"
                    value="<?php echo esc_attr((string) ($medio['icono'] ?? '')); ?>"
                    placeholder="ti ti-credit-card">
                <span class="description"><?php esc_html_e('Ej.: ti ti-credit-card, ti ti-device-mobile, ti ti-building-store', YUNIORROJAS_TEXT_DOMAIN); ?></span>
            </p>
            <p>
                <label for="jr_medio_descripcion"><?php esc_html_e('Texto corto (checkout)', YUNIORROJAS_TEXT_DOMAIN); ?></label>
                <input type="text" class="widefat" name="jr_medio_descripcion" id="jr_medio_descripcion"
                    value="<?php echo esc_attr((string) ($medio['descripcion'] ?? '')); ?>">
            </p>
        </div>

        <p>
            <label for="jr_medio_instrucciones"><?php esc_html_e('Instrucciones en el panel del medio', YUNIORROJAS_TEXT_DOMAIN); ?></label>
            <textarea class="widefat" rows="3" name="jr_medio_instrucciones" id="jr_medio_instrucciones"><?php
                echo esc_textarea((string) ($medio['instrucciones'] ?? ''));
            ?></textarea>
        </p>

        <div class="jr-medio-manual" id="jr-medio-manual-fields">
            <h3><?php esc_html_e('Datos de transferencia (solo tipo Manual)', YUNIORROJAS_TEXT_DOMAIN); ?></h3>
            <div class="jr-medio-grid">
                <p>
                    <label for="jr_medio_numero"><?php esc_html_e('Número / celular', YUNIORROJAS_TEXT_DOMAIN); ?></label>
                    <input type="text" class="widefat" name="jr_medio_numero" id="jr_medio_numero"
                        value="<?php echo esc_attr((string) ($medio['numero'] ?? '')); ?>"
                        placeholder="+51 999 999 999">
                </p>
                <p>
                    <label for="jr_medio_titular"><?php esc_html_e('Titular', YUNIORROJAS_TEXT_DOMAIN); ?></label>
                    <input type="text" class="widefat" name="jr_medio_titular" id="jr_medio_titular"
                        value="<?php echo esc_attr((string) ($medio['titular'] ?? '')); ?>">
                </p>
            </div>
            <?php
            $qr_id  = (int) ($medio['qr_id'] ?? 0);
            $qr_src = $qr_id > 0 ? (string) wp_get_attachment_image_url($qr_id, 'medium') : '';
            if ($qr_src === '' && $qr_id > 0) {
                $qr_src = (string) wp_get_attachment_url($qr_id);
            }
            ?>
            <div class="jr-medio-qr" data-jr-medio-qr>
                <label><?php esc_html_e('Imagen QR de pago', YUNIORROJAS_TEXT_DOMAIN); ?></label>
                <p class="description" style="margin-top:0;margin-bottom:.5rem;">
                    <?php esc_html_e('Sube o elige el QR de Plin / transferencia desde la biblioteca de medios. Si no hay imagen, se genera un QR automático con el número de celular.', YUNIORROJAS_TEXT_DOMAIN); ?>
                </p>
                <div
                    class="jr-medio-qr__preview"
                    data-jr-medio-qr-preview
                    style="min-height:80px;max-width:180px;margin-bottom:.65rem;border:1px solid #ccd0d4;background:#f6f7f7;display:flex;align-items:center;justify-content:center;"
                >
                    <?php if ($qr_src !== '') : ?>
                        <img src="<?php echo esc_url($qr_src); ?>" alt="" style="max-width:100%;height:auto;display:block;">
                    <?php else : ?>
                        <span class="description" style="padding:.75rem;text-align:center;">
                            <?php esc_html_e('Sin imagen subida', YUNIORROJAS_TEXT_DOMAIN); ?>
                        </span>
                    <?php endif; ?>
                </div>
                <input
                    type="hidden"
                    name="jr_medio_qr_id"
                    id="jr_medio_qr_id"
                    value="<?php echo esc_attr((string) max(0, $qr_id)); ?>"
                    data-jr-medio-qr-id
                >
                <p class="jr-medio-qr__actions" style="margin:0;display:flex;gap:.5rem;flex-wrap:wrap;">
                    <button type="button" class="button button-secondary" data-jr-medio-qr-select>
                        <?php echo $qr_id > 0
                            ? esc_html__('Cambiar imagen QR', YUNIORROJAS_TEXT_DOMAIN)
                            : esc_html__('Seleccionar imagen QR', YUNIORROJAS_TEXT_DOMAIN); ?>
                    </button>
                    <button
                        type="button"
                        class="button"
                        data-jr-medio-qr-clear
                        <?php echo $qr_id > 0 ? '' : ' hidden'; ?>
                    >
                        <?php esc_html_e('Quitar imagen', YUNIORROJAS_TEXT_DOMAIN); ?>
                    </button>
                </p>
            </div>
            <div class="jr-medio-grid">
                <p>
                    <label for="jr_medio_banco_nombre"><?php esc_html_e('Banco (opcional)', YUNIORROJAS_TEXT_DOMAIN); ?></label>
                    <input type="text" class="widefat" name="jr_medio_banco_nombre" id="jr_medio_banco_nombre"
                        value="<?php echo esc_attr((string) ($medio['banco_nombre'] ?? '')); ?>">
                </p>
                <p>
                    <label for="jr_medio_banco_titular"><?php esc_html_e('Titular cuenta banco', YUNIORROJAS_TEXT_DOMAIN); ?></label>
                    <input type="text" class="widefat" name="jr_medio_banco_titular" id="jr_medio_banco_titular"
                        value="<?php echo esc_attr((string) ($medio['banco_titular'] ?? '')); ?>">
                </p>
                <p>
                    <label for="jr_medio_banco_cuenta"><?php esc_html_e('N° cuenta', YUNIORROJAS_TEXT_DOMAIN); ?></label>
                    <input type="text" class="widefat" name="jr_medio_banco_cuenta" id="jr_medio_banco_cuenta"
                        value="<?php echo esc_attr((string) ($medio['banco_cuenta'] ?? '')); ?>">
                </p>
                <p>
                    <label for="jr_medio_banco_cci"><?php esc_html_e('CCI', YUNIORROJAS_TEXT_DOMAIN); ?></label>
                    <input type="text" class="widefat" name="jr_medio_banco_cci" id="jr_medio_banco_cci"
                        value="<?php echo esc_attr((string) ($medio['banco_cci'] ?? '')); ?>">
                </p>
            </div>
        </div>
    </div>
    <script>
    (function () {
        var sel = document.getElementById('jr_medio_tipo');
        var box = document.getElementById('jr-medio-manual-fields');
        if (!sel || !box) return;
        function sync() {
            box.style.display = sel.value === 'manual' ? '' : 'none';
        }
        sel.addEventListener('change', sync);
        sync();
    })();
    </script>
    <?php
}

/**
 * Guardar meta del medio.
 *
 * @param int $post_id ID.
 */
function yuniorrojas_medios_pago_save(int $post_id): void
{
    if (!isset($_POST['yuniorrojas_medio_pago_nonce'])) {
        return;
    }
    if (!wp_verify_nonce(
        sanitize_text_field(wp_unslash((string) $_POST['yuniorrojas_medio_pago_nonce'])),
        'yuniorrojas_medio_pago_save'
    )) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    if (get_post_type($post_id) !== YUNIORROJAS_CPT_MEDIOS_PAGO) {
        return;
    }

    $tipo = isset($_POST['jr_medio_tipo']) ? sanitize_key(wp_unslash((string) $_POST['jr_medio_tipo'])) : 'manual';
    if (!isset(yuniorrojas_medios_pago_tipos()[$tipo])) {
        $tipo = 'manual';
    }

    // Solo un Culqi activo a la vez.
    if ($tipo === 'culqi' && get_post_status($post_id) === 'publish') {
        $otros = get_posts(array(
            'post_type'      => YUNIORROJAS_CPT_MEDIOS_PAGO,
            'post_status'    => 'publish',
            'posts_per_page' => 20,
            'post__not_in'   => array($post_id),
            'fields'         => 'ids',
            'meta_key'       => yuniorrojas_medio_pago_meta_key('tipo'),
            'meta_value'     => 'culqi',
        ));
        foreach ($otros as $oid) {
            wp_update_post(array(
                'ID'          => (int) $oid,
                'post_status' => 'draft',
            ));
        }
    }

    $fields = array(
        'tipo'           => $tipo,
        'icono'          => isset($_POST['jr_medio_icono']) ? sanitize_text_field(wp_unslash((string) $_POST['jr_medio_icono'])) : '',
        'descripcion'    => isset($_POST['jr_medio_descripcion']) ? sanitize_text_field(wp_unslash((string) $_POST['jr_medio_descripcion'])) : '',
        'instrucciones'  => isset($_POST['jr_medio_instrucciones']) ? sanitize_textarea_field(wp_unslash((string) $_POST['jr_medio_instrucciones'])) : '',
        'numero'         => isset($_POST['jr_medio_numero']) ? sanitize_text_field(wp_unslash((string) $_POST['jr_medio_numero'])) : '',
        'titular'        => isset($_POST['jr_medio_titular']) ? sanitize_text_field(wp_unslash((string) $_POST['jr_medio_titular'])) : '',
        'qr_id'          => isset($_POST['jr_medio_qr_id']) ? (string) absint($_POST['jr_medio_qr_id']) : '0',
        'banco_nombre'   => isset($_POST['jr_medio_banco_nombre']) ? sanitize_text_field(wp_unslash((string) $_POST['jr_medio_banco_nombre'])) : '',
        'banco_cuenta'   => isset($_POST['jr_medio_banco_cuenta']) ? sanitize_text_field(wp_unslash((string) $_POST['jr_medio_banco_cuenta'])) : '',
        'banco_cci'      => isset($_POST['jr_medio_banco_cci']) ? sanitize_text_field(wp_unslash((string) $_POST['jr_medio_banco_cci'])) : '',
        'banco_titular'  => isset($_POST['jr_medio_banco_titular']) ? sanitize_text_field(wp_unslash((string) $_POST['jr_medio_banco_titular'])) : '',
    );

    foreach ($fields as $key => $value) {
        update_post_meta($post_id, yuniorrojas_medio_pago_meta_key($key), $value);
    }
}
add_action('save_post_' . YUNIORROJAS_CPT_MEDIOS_PAGO, 'yuniorrojas_medios_pago_save');

/**
 * Columnas listado.
 *
 * @param array<string, string> $cols Columnas.
 * @return array<string, string>
 */
function yuniorrojas_medios_pago_columns(array $cols): array
{
    $new = array();
    foreach ($cols as $key => $label) {
        $new[$key] = $label;
        if ($key === 'title') {
            $new['jr_tipo']  = __('Tipo', YUNIORROJAS_TEXT_DOMAIN);
            $new['jr_orden'] = __('Orden', YUNIORROJAS_TEXT_DOMAIN);
        }
    }

    return $new;
}
add_filter('manage_' . YUNIORROJAS_CPT_MEDIOS_PAGO . '_posts_columns', 'yuniorrojas_medios_pago_columns');

/**
 * @param string $col Columna.
 * @param int    $post_id ID.
 */
function yuniorrojas_medios_pago_column_content(string $col, int $post_id): void
{
    if ($col === 'jr_tipo') {
        $tipo  = (string) get_post_meta($post_id, yuniorrojas_medio_pago_meta_key('tipo'), true);
        $tipos = yuniorrojas_medios_pago_tipos();
        echo esc_html($tipos[$tipo] ?? $tipo);
        return;
    }
    if ($col === 'jr_orden') {
        $post = get_post($post_id);
        echo esc_html((string) ($post instanceof WP_Post ? $post->menu_order : 0));
    }
}
add_action('manage_' . YUNIORROJAS_CPT_MEDIOS_PAGO . '_posts_custom_column', 'yuniorrojas_medios_pago_column_content', 10, 2);

/**
 * Mensaje arriba del listado.
 */
function yuniorrojas_medios_pago_admin_notice(): void
{
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || $screen->post_type !== YUNIORROJAS_CPT_MEDIOS_PAGO) {
        return;
    }
    if ($screen->base !== 'edit') {
        return;
    }
    echo '<div class="notice notice-info"><p>';
    echo esc_html__(
        'Estos medios aparecen en el checkout de Reservar. Culqi usa las llaves de “Ajustes pagos”. Puedes crear varios medios manuales (Plin, BCP, Yape personal, etc.).',
        YUNIORROJAS_TEXT_DOMAIN
    );
    echo ' <a href="' . esc_url(admin_url('admin.php?page=yuniorrojas-pagos-settings')) . '">';
    echo esc_html__('Configurar llaves Culqi', YUNIORROJAS_TEXT_DOMAIN);
    echo '</a></p></div>';
}
add_action('admin_notices', 'yuniorrojas_medios_pago_admin_notice');

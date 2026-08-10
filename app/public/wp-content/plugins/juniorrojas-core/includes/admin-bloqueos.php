<?php
/**
 * Cierres / bloqueos de agenda (feriados, vacaciones, “no atiende”).
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('YUNIORROJAS_CPT_BLOQUEOS')) {
    define('YUNIORROJAS_CPT_BLOQUEOS', 'jr_bloqueos');
}

/**
 * CPT bloqueos.
 */
function yuniorrojas_registrar_cpt_bloqueos(): void
{
    if (post_type_exists(YUNIORROJAS_CPT_BLOQUEOS)) {
        return;
    }
    register_post_type(YUNIORROJAS_CPT_BLOQUEOS, array(
        'labels' => array(
            'name'          => __('Bloqueos de agenda', YUNIORROJAS_TEXT_DOMAIN),
            'singular_name' => __('Bloqueo', YUNIORROJAS_TEXT_DOMAIN),
            'add_new_item'  => __('Añadir bloqueo', YUNIORROJAS_TEXT_DOMAIN),
        ),
        'public'       => false,
        'show_ui'      => true,
        'show_in_menu' => 'edit.php?post_type=' . YUNIORROJAS_CPT_RESERVAS,
        'menu_icon'    => 'dashicons-lock',
        'supports'     => array('title'),
    ));
}
add_action('init', 'yuniorrojas_registrar_cpt_bloqueos');

/**
 * Metabox bloqueo.
 */
function yuniorrojas_bloqueos_metaboxes(): void
{
    add_meta_box(
        'jr_bloqueo_datos',
        __('Datos del bloqueo', YUNIORROJAS_TEXT_DOMAIN),
        'yuniorrojas_bloqueos_metabox_render',
        YUNIORROJAS_CPT_BLOQUEOS,
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'yuniorrojas_bloqueos_metaboxes');

/**
 * @param WP_Post $post Post.
 */
function yuniorrojas_bloqueos_metabox_render(WP_Post $post): void
{
    wp_nonce_field('jr_bloqueo_save', 'jr_bloqueo_nonce');
    $barbero_id = (int) get_post_meta($post->ID, '_jr_bloqueo_barbero_id', true);
    $desde      = (string) get_post_meta($post->ID, '_jr_bloqueo_desde', true);
    $hasta      = (string) get_post_meta($post->ID, '_jr_bloqueo_hasta', true);
    $todo_dia   = get_post_meta($post->ID, '_jr_bloqueo_todo_dia', true) !== '0';
    $motivo     = (string) get_post_meta($post->ID, '_jr_bloqueo_motivo', true);
    $barberos   = function_exists('yuniorrojas_reserva_admin_opciones_barberos')
        ? yuniorrojas_reserva_admin_opciones_barberos()
        : array();
    ?>
    <p>
        <label for="jr_bloqueo_barbero"><strong><?php esc_html_e('Barbero', YUNIORROJAS_TEXT_DOMAIN); ?></strong></label><br>
        <select name="jr_bloqueo_barbero_id" id="jr_bloqueo_barbero" class="widefat">
            <option value="0"><?php esc_html_e('Todo el estudio', YUNIORROJAS_TEXT_DOMAIN); ?></option>
            <?php foreach ($barberos as $bid => $nombre) : ?>
                <option value="<?php echo esc_attr((string) $bid); ?>" <?php selected($barbero_id, (int) $bid); ?>>
                    <?php echo esc_html($nombre); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </p>
    <p>
        <label><strong><?php esc_html_e('Desde', YUNIORROJAS_TEXT_DOMAIN); ?></strong></label><br>
        <input type="date" name="jr_bloqueo_desde" value="<?php echo esc_attr($desde); ?>" required>
        &nbsp;
        <label><strong><?php esc_html_e('Hasta', YUNIORROJAS_TEXT_DOMAIN); ?></strong></label>
        <input type="date" name="jr_bloqueo_hasta" value="<?php echo esc_attr($hasta); ?>" required>
    </p>
    <p>
        <label>
            <input type="checkbox" name="jr_bloqueo_todo_dia" value="1" <?php checked($todo_dia); ?>>
            <?php esc_html_e('Día completo (no atiende)', YUNIORROJAS_TEXT_DOMAIN); ?>
        </label>
    </p>
    <p>
        <label for="jr_bloqueo_motivo"><strong><?php esc_html_e('Motivo', YUNIORROJAS_TEXT_DOMAIN); ?></strong></label><br>
        <input type="text" class="widefat" name="jr_bloqueo_motivo" id="jr_bloqueo_motivo" value="<?php echo esc_attr($motivo); ?>" placeholder="<?php esc_attr_e('Ej. Feriado, vacaciones, evento', YUNIORROJAS_TEXT_DOMAIN); ?>">
    </p>
    <?php
}

/**
 * Guardar bloqueo.
 */
function yuniorrojas_bloqueos_guardar(int $post_id): void
{
    if (!isset($_POST['jr_bloqueo_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash((string) $_POST['jr_bloqueo_nonce'])), 'jr_bloqueo_save')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (get_post_type($post_id) !== YUNIORROJAS_CPT_BLOQUEOS) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $barbero = isset($_POST['jr_bloqueo_barbero_id']) ? absint($_POST['jr_bloqueo_barbero_id']) : 0;
    $desde   = isset($_POST['jr_bloqueo_desde']) ? sanitize_text_field(wp_unslash((string) $_POST['jr_bloqueo_desde'])) : '';
    $hasta   = isset($_POST['jr_bloqueo_hasta']) ? sanitize_text_field(wp_unslash((string) $_POST['jr_bloqueo_hasta'])) : '';
    $todo    = isset($_POST['jr_bloqueo_todo_dia']) ? '1' : '0';
    $motivo  = isset($_POST['jr_bloqueo_motivo']) ? sanitize_text_field(wp_unslash((string) $_POST['jr_bloqueo_motivo'])) : '';

    if ($hasta === '' || $hasta < $desde) {
        $hasta = $desde;
    }

    update_post_meta($post_id, '_jr_bloqueo_barbero_id', (string) $barbero);
    update_post_meta($post_id, '_jr_bloqueo_desde', $desde);
    update_post_meta($post_id, '_jr_bloqueo_hasta', $hasta);
    update_post_meta($post_id, '_jr_bloqueo_todo_dia', $todo);
    update_post_meta($post_id, '_jr_bloqueo_motivo', $motivo);

    if (get_the_title($post_id) === '' || get_the_title($post_id) === 'Auto Draft') {
        $title = $motivo !== '' ? $motivo : __('Bloqueo de agenda', YUNIORROJAS_TEXT_DOMAIN);
        $title .= ' (' . $desde . ($hasta !== $desde ? ' → ' . $hasta : '') . ')';
        remove_action('save_post_' . YUNIORROJAS_CPT_BLOQUEOS, 'yuniorrojas_bloqueos_guardar');
        wp_update_post(array('ID' => $post_id, 'post_title' => $title));
        add_action('save_post_' . YUNIORROJAS_CPT_BLOQUEOS, 'yuniorrojas_bloqueos_guardar');
    }
}
add_action('save_post_' . YUNIORROJAS_CPT_BLOQUEOS, 'yuniorrojas_bloqueos_guardar');

/**
 * Bloqueos que afectan una fecha/barbero.
 *
 * @return list<array{id:int,barbero_id:int,motivo:string,todo_dia:bool,desde:string,hasta:string}>
 */
function yuniorrojas_bloqueos_para_fecha(string $fecha, int $barbero_id = 0): array
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        return array();
    }

    $ids = get_posts(array(
        'post_type'      => YUNIORROJAS_CPT_BLOQUEOS,
        'post_status'    => 'publish',
        'posts_per_page' => 100,
        'fields'         => 'ids',
    ));

    $out = array();
    foreach ($ids as $id) {
        $id = (int) $id;
        $desde = (string) get_post_meta($id, '_jr_bloqueo_desde', true);
        $hasta = (string) get_post_meta($id, '_jr_bloqueo_hasta', true);
        if ($desde === '') {
            continue;
        }
        if ($hasta === '') {
            $hasta = $desde;
        }
        if ($fecha < $desde || $fecha > $hasta) {
            continue;
        }
        $b = (int) get_post_meta($id, '_jr_bloqueo_barbero_id', true);
        // 0 = todo el estudio; si pedimos un barbero, aplica. Si barbero_id=0 devolvemos todos.
        if ($barbero_id > 0 && $b > 0 && $b !== $barbero_id) {
            continue;
        }
        $out[] = array(
            'id'         => $id,
            'barbero_id' => $b,
            'motivo'     => (string) get_post_meta($id, '_jr_bloqueo_motivo', true),
            'todo_dia'   => get_post_meta($id, '_jr_bloqueo_todo_dia', true) !== '0',
            'desde'      => $desde,
            'hasta'      => $hasta,
        );
    }

    return $out;
}

/**
 * ¿Fecha bloqueada para el barbero?
 */
function yuniorrojas_fecha_bloqueada(int $barbero_id, string $fecha): bool
{
    $bloqueos = yuniorrojas_bloqueos_para_fecha($fecha, $barbero_id);
    foreach ($bloqueos as $b) {
        if (!empty($b['todo_dia'])) {
            return true;
        }
    }
    return false;
}

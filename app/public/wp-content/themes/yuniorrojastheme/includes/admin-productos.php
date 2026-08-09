<?php
/**
 * Productos del estudio + ticket mixto en reservas.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('YUNIORROJAS_CPT_PRODUCTOS')) {
    define('YUNIORROJAS_CPT_PRODUCTOS', 'jr_productos');
}

/**
 * CPT productos.
 */
function yuniorrojas_registrar_cpt_productos(): void
{
    if (post_type_exists(YUNIORROJAS_CPT_PRODUCTOS)) {
        return;
    }
    register_post_type(YUNIORROJAS_CPT_PRODUCTOS, array(
        'labels' => array(
            'name'          => __('Productos', YUNIORROJAS_TEXT_DOMAIN),
            'singular_name' => __('Producto', YUNIORROJAS_TEXT_DOMAIN),
            'add_new_item'  => __('Añadir producto', YUNIORROJAS_TEXT_DOMAIN),
        ),
        'public'       => false,
        'show_ui'      => true,
        'show_in_menu' => true,
        'menu_icon'    => 'dashicons-cart',
        'menu_position'=> 28,
        'supports'     => array('title', 'thumbnail'),
    ));
}
add_action('init', 'yuniorrojas_registrar_cpt_productos');

/**
 * Metabox precio producto.
 */
function yuniorrojas_productos_metaboxes(): void
{
    add_meta_box(
        'jr_producto_precio',
        __('Precio', YUNIORROJAS_TEXT_DOMAIN),
        static function (WP_Post $post): void {
            wp_nonce_field('jr_producto_save', 'jr_producto_nonce');
            $precio = (string) get_post_meta($post->ID, '_jr_producto_precio', true);
            $sku    = (string) get_post_meta($post->ID, '_jr_producto_sku', true);
            echo '<p><label>S/. <input type="text" name="jr_producto_precio" value="' . esc_attr($precio) . '" class="regular-text"></label></p>';
            echo '<p><label>SKU <input type="text" name="jr_producto_sku" value="' . esc_attr($sku) . '" class="regular-text"></label></p>';
        },
        YUNIORROJAS_CPT_PRODUCTOS,
        'side',
        'high'
    );
}
add_action('add_meta_boxes', 'yuniorrojas_productos_metaboxes');

/**
 * Guardar producto.
 */
function yuniorrojas_productos_guardar(int $post_id): void
{
    if (!isset($_POST['jr_producto_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash((string) $_POST['jr_producto_nonce'])), 'jr_producto_save')) {
        return;
    }
    if (get_post_type($post_id) !== YUNIORROJAS_CPT_PRODUCTOS) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    $precio = isset($_POST['jr_producto_precio']) ? sanitize_text_field(wp_unslash((string) $_POST['jr_producto_precio'])) : '';
    $sku    = isset($_POST['jr_producto_sku']) ? sanitize_text_field(wp_unslash((string) $_POST['jr_producto_sku'])) : '';
    update_post_meta($post_id, '_jr_producto_precio', $precio);
    update_post_meta($post_id, '_jr_producto_sku', $sku);
}
add_action('save_post_' . YUNIORROJAS_CPT_PRODUCTOS, 'yuniorrojas_productos_guardar');

/**
 * @return array<int, array{id:int,nombre:string,precio:string}>
 */
function yuniorrojas_productos_opciones(): array
{
    $ids = get_posts(array(
        'post_type'      => YUNIORROJAS_CPT_PRODUCTOS,
        'post_status'    => 'publish',
        'posts_per_page' => 100,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'fields'         => 'ids',
    ));
    $out = array();
    foreach ($ids as $id) {
        $id = (int) $id;
        $out[$id] = array(
            'id'     => $id,
            'nombre' => get_the_title($id),
            'precio' => (string) get_post_meta($id, '_jr_producto_precio', true),
        );
    }
    return $out;
}

/**
 * Líneas de productos de una reserva.
 *
 * @return list<array{id:int,nombre:string,precio:float,qty:int}>
 */
function yuniorrojas_reserva_productos(int $reserva_id): array
{
    $raw = get_post_meta($reserva_id, yuniorrojas_reserva_meta_key('productos'), true);
    if (is_string($raw) && $raw !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $raw = $decoded;
        }
    }
    if (!is_array($raw)) {
        return array();
    }
    $out = array();
    foreach ($raw as $line) {
        if (!is_array($line)) {
            continue;
        }
        $out[] = array(
            'id'     => (int) ($line['id'] ?? 0),
            'nombre' => sanitize_text_field((string) ($line['nombre'] ?? '')),
            'precio' => (float) ($line['precio'] ?? 0),
            'qty'    => max(1, (int) ($line['qty'] ?? 1)),
        );
    }
    return $out;
}

/**
 * Total productos de la reserva.
 */
function yuniorrojas_reserva_total_productos(int $reserva_id): float
{
    $total = 0.0;
    foreach (yuniorrojas_reserva_productos($reserva_id) as $line) {
        $total += $line['precio'] * $line['qty'];
    }
    return round($total, 2);
}

/**
 * Metabox ticket en reserva.
 */
function yuniorrojas_productos_metabox_reserva(): void
{
    add_meta_box(
        'jr_reserva_ticket',
        __('Ticket: productos', YUNIORROJAS_TEXT_DOMAIN),
        'yuniorrojas_productos_metabox_reserva_render',
        YUNIORROJAS_CPT_RESERVAS,
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'yuniorrojas_productos_metabox_reserva');

/**
 * @param WP_Post $post Post.
 */
function yuniorrojas_productos_metabox_reserva_render(WP_Post $post): void
{
    $productos = yuniorrojas_productos_opciones();
    $lineas    = yuniorrojas_reserva_productos((int) $post->ID);
    wp_nonce_field('jr_ticket_save', 'jr_ticket_nonce');
    ?>
    <p class="description"><?php esc_html_e('Añade productos vendidos junto al servicio (ticket mixto). Se suman al total en Ingresos.', YUNIORROJAS_TEXT_DOMAIN); ?></p>
    <table class="widefat" id="jr-ticket-table">
        <thead>
            <tr>
                <th><?php esc_html_e('Producto', YUNIORROJAS_TEXT_DOMAIN); ?></th>
                <th style="width:90px"><?php esc_html_e('Cant.', YUNIORROJAS_TEXT_DOMAIN); ?></th>
                <th style="width:60px"></th>
            </tr>
        </thead>
        <tbody>
            <?php if ($lineas === array()) : ?>
                <tr class="jr-ticket-row">
                    <td>
                        <select name="jr_ticket_producto[]" class="widefat">
                            <option value="0"><?php esc_html_e('— Ninguno —', YUNIORROJAS_TEXT_DOMAIN); ?></option>
                            <?php foreach ($productos as $p) : ?>
                                <option value="<?php echo esc_attr((string) $p['id']); ?>">
                                    <?php echo esc_html($p['nombre'] . ' (S/. ' . $p['precio'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td><input type="number" name="jr_ticket_qty[]" min="1" value="1" class="small-text"></td>
                    <td><button type="button" class="button jr-ticket-remove">&times;</button></td>
                </tr>
            <?php else : ?>
                <?php foreach ($lineas as $line) : ?>
                    <tr class="jr-ticket-row">
                        <td>
                            <select name="jr_ticket_producto[]" class="widefat">
                                <option value="0"><?php esc_html_e('— Ninguno —', YUNIORROJAS_TEXT_DOMAIN); ?></option>
                                <?php foreach ($productos as $p) : ?>
                                    <option value="<?php echo esc_attr((string) $p['id']); ?>" <?php selected((int) $line['id'], (int) $p['id']); ?>>
                                        <?php echo esc_html($p['nombre'] . ' (S/. ' . $p['precio'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td><input type="number" name="jr_ticket_qty[]" min="1" value="<?php echo esc_attr((string) $line['qty']); ?>" class="small-text"></td>
                        <td><button type="button" class="button jr-ticket-remove">&times;</button></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <p>
        <button type="button" class="button" id="jr-ticket-add"><?php esc_html_e('Añadir línea', YUNIORROJAS_TEXT_DOMAIN); ?></button>
        <strong style="margin-left:12px;"><?php esc_html_e('Subtotal productos:', YUNIORROJAS_TEXT_DOMAIN); ?> S/. <?php echo esc_html((string) yuniorrojas_reserva_total_productos((int) $post->ID)); ?></strong>
    </p>
    <script>
    (function () {
      const table = document.getElementById("jr-ticket-table");
      const addBtn = document.getElementById("jr-ticket-add");
      if (!table || !addBtn) return;
      addBtn.addEventListener("click", function () {
        const row = table.querySelector(".jr-ticket-row");
        if (!row) return;
        const clone = row.cloneNode(true);
        clone.querySelectorAll("select").forEach(function (s) { s.selectedIndex = 0; });
        clone.querySelectorAll("input").forEach(function (i) { i.value = "1"; });
        table.querySelector("tbody").appendChild(clone);
      });
      table.addEventListener("click", function (e) {
        if (e.target && e.target.classList.contains("jr-ticket-remove")) {
          const rows = table.querySelectorAll(".jr-ticket-row");
          if (rows.length > 1) e.target.closest("tr").remove();
        }
      });
    })();
    </script>
    <?php
}

/**
 * Guardar ticket en reserva (hook aparte del metabox principal).
 */
function yuniorrojas_productos_guardar_ticket(int $post_id): void
{
    if (!isset($_POST['jr_ticket_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash((string) $_POST['jr_ticket_nonce'])), 'jr_ticket_save')) {
        return;
    }
    if (get_post_type($post_id) !== YUNIORROJAS_CPT_RESERVAS) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $ids  = isset($_POST['jr_ticket_producto']) ? (array) wp_unslash($_POST['jr_ticket_producto']) : array();
    $qtys = isset($_POST['jr_ticket_qty']) ? (array) wp_unslash($_POST['jr_ticket_qty']) : array();
    $lines = array();

    foreach ($ids as $i => $pid) {
        $pid = absint($pid);
        if ($pid <= 0 || get_post_type($pid) !== YUNIORROJAS_CPT_PRODUCTOS) {
            continue;
        }
        $qty = isset($qtys[$i]) ? max(1, absint($qtys[$i])) : 1;
        $lines[] = array(
            'id'     => $pid,
            'nombre' => get_the_title($pid),
            'precio' => (float) str_replace(',', '.', (string) get_post_meta($pid, '_jr_producto_precio', true)),
            'qty'    => $qty,
        );
    }

    update_post_meta($post_id, yuniorrojas_reserva_meta_key('productos'), wp_json_encode($lines));
    update_post_meta($post_id, yuniorrojas_reserva_meta_key('total_productos'), (string) yuniorrojas_reserva_total_productos($post_id));
}
add_action('save_post_' . YUNIORROJAS_CPT_RESERVAS, 'yuniorrojas_productos_guardar_ticket', 30);

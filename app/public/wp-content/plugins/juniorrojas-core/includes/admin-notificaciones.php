<?php
/**
 * Notificaciones admin (badge + centro breve).
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @return list<array{id:string,tipo:string,titulo:string,mensaje:string,url:string,time:int,leida:bool}>
 */
function yuniorrojas_admin_notif_list(): array
{
    $list = get_option('yuniorrojas_admin_notifs', array());
    return is_array($list) ? $list : array();
}

/**
 * Añade notificación.
 */
function yuniorrojas_admin_notif_push(string $tipo, string $titulo, string $mensaje, string $url = '', string $id = ''): void
{
    $list = yuniorrojas_admin_notif_list();
    $item = array(
        'id'      => $id !== '' ? $id : uniqid('n_', true),
        'tipo'    => sanitize_key($tipo),
        'titulo'  => sanitize_text_field($titulo),
        'mensaje' => sanitize_text_field($mensaje),
        'url'     => esc_url_raw($url),
        'time'    => time(),
        'leida'   => false,
    );
    array_unshift($list, $item);
    $list = array_slice($list, 0, 40);
    update_option('yuniorrojas_admin_notifs', $list, false);
}

/**
 * Marca leída por id (o prefijo).
 */
function yuniorrojas_admin_notif_marcar(string $id): void
{
    $list = yuniorrojas_admin_notif_list();
    $changed = false;
    foreach ($list as $i => $item) {
        if (!is_array($item)) {
            continue;
        }
        $nid = (string) ($item['id'] ?? '');
        if ($nid === $id || str_starts_with($nid, $id) || $id === $nid) {
            $list[$i]['leida'] = true;
            $changed = true;
        }
    }
    if ($changed) {
        update_option('yuniorrojas_admin_notifs', $list, false);
    }
}

/**
 * Número no leídas.
 */
function yuniorrojas_admin_notif_count(): int
{
    $n = 0;
    foreach (yuniorrojas_admin_notif_list() as $item) {
        if (is_array($item) && empty($item['leida'])) {
            $n++;
        }
    }
    return $n;
}

/**
 * Cuando se crea una reserva web: badge admin.
 */
function yuniorrojas_admin_notif_desde_reserva(int $reserva_id, string $evento): void
{
    $r = yuniorrojas_obtener_reserva($reserva_id);
    if ($r === null) {
        return;
    }
    $nombre = trim((string) ($r['cliente_nombres'] ?? '') . ' ' . (string) ($r['cliente_apellidos'] ?? ''));
    $edit   = get_edit_post_link($reserva_id, 'raw') ?: admin_url('edit.php?post_type=' . YUNIORROJAS_CPT_RESERVAS);

    if ($evento === 'creada') {
        $titulo = __('Nueva reserva web', YUNIORROJAS_TEXT_DOMAIN);
        $msg    = $nombre . ' · ' . (string) ($r['servicio_nombre'] ?? '') . ' · ' . (string) ($r['fecha'] ?? '');
        if ((string) ($r['estado'] ?? '') === 'pendiente') {
            $titulo = __('Pago por verificar', YUNIORROJAS_TEXT_DOMAIN);
            yuniorrojas_admin_notif_push('pago', $titulo, $msg, admin_url('admin.php?page=yuniorrojas-pagos'), 'pago_' . $reserva_id);
        } else {
            yuniorrojas_admin_notif_push('reserva', $titulo, $msg, $edit, 'reserva_' . $reserva_id);
        }
    }
}

/**
 * Enganche opcional (compat).
 */
function yuniorrojas_admin_notif_hook_creada(int $reserva_id): void
{
    yuniorrojas_admin_notif_desde_reserva($reserva_id, 'creada');
}

/** PHP 7.x polyfill-safe prefix check (WP 6+ is PHP 8). */
if (!function_exists('str_starts_with')) {
    /**
     * @param string $haystack
     * @param string $needle
     */
    function str_starts_with($haystack, $needle): bool
    {
        return $needle === '' || strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

/**
 * Admin bar badge.
 */
function yuniorrojas_admin_notif_admin_bar(WP_Admin_Bar $bar): void
{
    if (!current_user_can('edit_posts')) {
        return;
    }
    $n = yuniorrojas_admin_notif_count();
    $bar->add_node(array(
        'id'    => 'jr-admin-notifs',
        'title' => $n > 0
            ? sprintf(__('JR avisos (%d)', YUNIORROJAS_TEXT_DOMAIN), $n)
            : __('JR avisos', YUNIORROJAS_TEXT_DOMAIN),
        'href'  => admin_url('admin.php?page=yuniorrojas-avisos'),
        'meta'  => array('class' => $n > 0 ? 'jr-notifs-has' : ''),
    ));
}
add_action('admin_bar_menu', 'yuniorrojas_admin_notif_admin_bar', 80);

/**
 * Menú Avisos.
 */
function yuniorrojas_admin_notif_menu(): void
{
    $n = yuniorrojas_admin_notif_count();
    $title = __('Avisos JR', YUNIORROJAS_TEXT_DOMAIN);
    if ($n > 0) {
        $title .= ' <span class="awaiting-mod">' . (int) $n . '</span>';
    }
    add_submenu_page(
        'edit.php?post_type=' . YUNIORROJAS_CPT_RESERVAS,
        __('Avisos', YUNIORROJAS_TEXT_DOMAIN),
        $title,
        'edit_posts',
        'yuniorrojas-avisos',
        'yuniorrojas_admin_notif_page'
    );
}
add_action('admin_menu', 'yuniorrojas_admin_notif_menu', 30);

/**
 * Página avisos.
 */
function yuniorrojas_admin_notif_page(): void
{
    if (!current_user_can('edit_posts')) {
        return;
    }

    if (isset($_GET['jr_marcar_todas']) && check_admin_referer('jr_marcar_todas')) {
        $list = yuniorrojas_admin_notif_list();
        foreach ($list as $i => $item) {
            $list[$i]['leida'] = true;
        }
        update_option('yuniorrojas_admin_notifs', $list, false);
        echo '<div class="notice notice-success"><p>' . esc_html__('Avisos marcados como leídos.', YUNIORROJAS_TEXT_DOMAIN) . '</p></div>';
    }

    $list = yuniorrojas_admin_notif_list();
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Avisos del estudio', YUNIORROJAS_TEXT_DOMAIN); ?></h1>
        <p>
            <a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=yuniorrojas-avisos&jr_marcar_todas=1'), 'jr_marcar_todas')); ?>">
                <?php esc_html_e('Marcar todo como leído', YUNIORROJAS_TEXT_DOMAIN); ?>
            </a>
        </p>
        <?php if ($list === array()) : ?>
            <p><?php esc_html_e('Sin avisos recientes.', YUNIORROJAS_TEXT_DOMAIN); ?></p>
        <?php else : ?>
            <ul style="max-width:720px;">
                <?php foreach ($list as $item) : ?>
                    <?php
                    if (!is_array($item)) {
                        continue;
                    }
                    $leida = !empty($item['leida']);
                    ?>
                    <li style="padding:12px 0;border-bottom:1px solid #dcdcde;opacity:<?php echo $leida ? '0.65' : '1'; ?>">
                        <strong><?php echo esc_html((string) ($item['titulo'] ?? '')); ?></strong>
                        <?php if (!$leida) : ?><span class="awaiting-mod" style="margin-left:6px;">nuevo</span><?php endif; ?>
                        <br>
                        <span class="description"><?php echo esc_html((string) ($item['mensaje'] ?? '')); ?></span>
                        <br>
                        <span class="description"><?php echo esc_html(date_i18n('d/m/Y H:i', (int) ($item['time'] ?? time()))); ?></span>
                        <?php if (!empty($item['url'])) : ?>
                            · <a href="<?php echo esc_url((string) $item['url']); ?>"><?php esc_html_e('Abrir', YUNIORROJAS_TEXT_DOMAIN); ?></a>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
    <?php
}

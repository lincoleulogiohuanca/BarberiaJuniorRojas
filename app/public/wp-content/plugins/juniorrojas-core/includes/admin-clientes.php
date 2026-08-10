<?php
/**
 * CRM ligero de clientes (admin).
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @return array{
 *   user_id:int,
 *   nombre:string,
 *   email:string,
 *   telefono:string,
 *   visitas:int,
 *   total:float,
 *   ultima:string,
 *   nivel:string,
 *   estilo:string,
 *   notas:string
 * }
 */
function yuniorrojas_crm_perfil_cliente(int $user_id): array
{
    $user = get_userdata($user_id);
    $empty = array(
        'user_id'  => $user_id,
        'nombre'   => '',
        'email'    => '',
        'telefono' => '',
        'visitas'  => 0,
        'total'    => 0.0,
        'ultima'   => '',
        'nivel'    => 'Classic',
        'estilo'   => '',
        'notas'    => '',
    );
    if (!$user instanceof WP_User) {
        return $empty;
    }

    $items = function_exists('yuniorrojas_reservas_cliente')
        ? yuniorrojas_reservas_cliente($user_id, 'todas')
        : array();

    $visitas = 0;
    $total   = 0.0;
    $ultima  = '';
    foreach ($items as $item) {
        $estado = (string) ($item['estado'] ?? '');
        if ($estado === 'cancelada' || $estado === 'no_show') {
            continue;
        }
        $precio = (float) preg_replace('/[^\d.]/', '', (string) ($item['precio'] ?? '0'));
        if ($estado === 'completada') {
            $visitas++;
            $total += $precio;
        } elseif ($estado === 'confirmada' || $estado === 'pendiente') {
            $total += $precio;
        }
        $f = (string) ($item['fecha'] ?? '');
        if ($f !== '' && ($ultima === '' || $f > $ultima)) {
            $ultima = $f;
        }
    }

    $nivel = function_exists('yuniorrojas_nivel_cliente')
        ? yuniorrojas_nivel_cliente($user_id)
        : array('label' => 'Cliente Classic');

    $estilo_id = (int) get_user_meta($user_id, 'jr_estilo_referencia_id', true);
    $estilo    = $estilo_id > 0 ? get_the_title($estilo_id) : '';

    $tel = (string) get_user_meta($user_id, 'telefono', true);
    if ($tel === '') {
        $tel = (string) get_user_meta($user_id, 'whatsapp', true);
    }

    return array(
        'user_id'  => $user_id,
        'nombre'   => trim($user->first_name . ' ' . $user->last_name) !== ''
            ? trim($user->first_name . ' ' . $user->last_name)
            : $user->display_name,
        'email'    => $user->user_email,
        'telefono' => $tel,
        'visitas'  => $visitas,
        'total'    => round($total, 2),
        'ultima'   => $ultima,
        'nivel'    => (string) ($nivel['label'] ?? 'Classic'),
        'estilo'   => is_string($estilo) ? $estilo : '',
        'notas'    => (string) get_user_meta($user_id, 'jr_notas_barbero', true),
    );
}

/**
 * Listado de clientes WP (no admin) con al menos una reserva o rol subscriber.
 *
 * @return list<array<string, mixed>>
 */
function yuniorrojas_crm_listar_clientes(): array
{
    $users = get_users(array(
        'role__in' => array('subscriber', 'customer', 'cliente'),
        'number'   => 200,
        'orderby'  => 'display_name',
        'order'    => 'ASC',
        'fields'   => array('ID'),
    ));

    // Incluir autores de reservas web aunque no tengan rol subscriber.
    $extra_ids = get_posts(array(
        'post_type'      => YUNIORROJAS_CPT_RESERVAS,
        'post_status'    => 'publish',
        'posts_per_page' => 500,
        'fields'         => 'ids',
        'meta_key'       => yuniorrojas_reserva_meta_key('cliente_user_id'),
        'meta_value'     => '0',
        'meta_compare'   => '>',
    ));
    $seen = array();
    $list = array();

    foreach ($users as $u) {
        $id = is_object($u) ? (int) $u->ID : (int) $u;
        if ($id <= 0 || isset($seen[$id])) {
            continue;
        }
        if (user_can($id, 'manage_options')) {
            continue;
        }
        $seen[$id] = true;
        $list[] = yuniorrojas_crm_perfil_cliente($id);
    }

    foreach ($extra_ids as $rid) {
        $uid = (int) get_post_meta((int) $rid, yuniorrojas_reserva_meta_key('cliente_user_id'), true);
        if ($uid <= 0 || isset($seen[$uid]) || user_can($uid, 'manage_options')) {
            continue;
        }
        $seen[$uid] = true;
        $list[] = yuniorrojas_crm_perfil_cliente($uid);
    }

    usort(
        $list,
        static function (array $a, array $b): int {
            return strcmp((string) $a['nombre'], (string) $b['nombre']);
        }
    );

    return $list;
}

/**
 * Menú CRM.
 */
function yuniorrojas_crm_registrar_menu(): void
{
    add_menu_page(
        __('Clientes', YUNIORROJAS_TEXT_DOMAIN),
        __('Clientes', YUNIORROJAS_TEXT_DOMAIN),
        'edit_posts',
        'yuniorrojas-clientes',
        'yuniorrojas_crm_render_page',
        'dashicons-id',
        27
    );
}
add_action('admin_menu', 'yuniorrojas_crm_registrar_menu');

/**
 * Guarda notas admin del cliente.
 */
function yuniorrojas_crm_guardar_notas(): void
{
    if (!current_user_can('edit_posts')) {
        wp_die(esc_html__('Sin permiso.', YUNIORROJAS_TEXT_DOMAIN));
    }
    $uid = isset($_POST['user_id']) ? absint($_POST['user_id']) : 0;
    check_admin_referer('jr_crm_notas_' . $uid);
    $notas = isset($_POST['jr_notas_crm']) ? sanitize_textarea_field(wp_unslash((string) $_POST['jr_notas_crm'])) : '';
    if ($uid > 0) {
        update_user_meta($uid, 'jr_notas_crm_admin', $notas);
        // También sincroniza notas al barbero si se pide.
        if (isset($_POST['jr_sync_notas_barbero'])) {
            update_user_meta($uid, 'jr_notas_barbero', $notas);
        }
    }
    wp_safe_redirect(admin_url('admin.php?page=yuniorrojas-clientes&user_id=' . $uid . '&updated=1'));
    exit;
}
add_action('admin_post_jr_crm_notas', 'yuniorrojas_crm_guardar_notas');

/**
 * Página CRM.
 */
function yuniorrojas_crm_render_page(): void
{
    if (!current_user_can('edit_posts')) {
        return;
    }

    $user_id = isset($_GET['user_id']) ? absint($_GET['user_id']) : 0; // phpcs:ignore

    if ($user_id > 0) {
        $perfil  = yuniorrojas_crm_perfil_cliente($user_id);
        $notas_a = (string) get_user_meta($user_id, 'jr_notas_crm_admin', true);
        if ($notas_a === '') {
            $notas_a = $perfil['notas'];
        }
        $reservas = yuniorrojas_reservas_cliente($user_id, 'todas');
        ?>
        <div class="wrap">
            <h1>
                <?php echo esc_html($perfil['nombre']); ?>
                <a class="page-title-action" href="<?php echo esc_url(admin_url('admin.php?page=yuniorrojas-clientes')); ?>">
                    <?php esc_html_e('Volver', YUNIORROJAS_TEXT_DOMAIN); ?>
                </a>
            </h1>
            <?php if (isset($_GET['updated'])) : // phpcs:ignore ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Notas guardadas.', YUNIORROJAS_TEXT_DOMAIN); ?></p></div>
            <?php endif; ?>

            <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin:16px 0;">
                <div class="postbox" style="padding:12px 16px;margin:0;"><strong><?php esc_html_e('Visitas', YUNIORROJAS_TEXT_DOMAIN); ?></strong><p style="font-size:22px;margin:8px 0 0;"><?php echo esc_html((string) $perfil['visitas']); ?></p></div>
                <div class="postbox" style="padding:12px 16px;margin:0;"><strong><?php esc_html_e('Total gastado', YUNIORROJAS_TEXT_DOMAIN); ?></strong><p style="font-size:22px;margin:8px 0 0;">S/. <?php echo esc_html(number_format($perfil['total'], 2)); ?></p></div>
                <div class="postbox" style="padding:12px 16px;margin:0;"><strong><?php esc_html_e('Última cita', YUNIORROJAS_TEXT_DOMAIN); ?></strong><p style="font-size:18px;margin:8px 0 0;"><?php echo esc_html($perfil['ultima'] !== '' ? $perfil['ultima'] : '—'); ?></p></div>
                <div class="postbox" style="padding:12px 16px;margin:0;"><strong><?php esc_html_e('Nivel', YUNIORROJAS_TEXT_DOMAIN); ?></strong><p style="font-size:18px;margin:8px 0 0;"><?php echo esc_html($perfil['nivel']); ?></p></div>
            </div>

            <div class="postbox" style="padding:16px;max-width:720px;">
                <p><strong><?php esc_html_e('Email', YUNIORROJAS_TEXT_DOMAIN); ?>:</strong> <?php echo esc_html($perfil['email']); ?></p>
                <p><strong><?php esc_html_e('Teléfono', YUNIORROJAS_TEXT_DOMAIN); ?>:</strong> <?php echo esc_html($perfil['telefono'] !== '' ? $perfil['telefono'] : '—'); ?></p>
                <p><strong><?php esc_html_e('Estilo preferido', YUNIORROJAS_TEXT_DOMAIN); ?>:</strong> <?php echo esc_html($perfil['estilo'] !== '' ? $perfil['estilo'] : '—'); ?></p>

                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="jr_crm_notas">
                    <input type="hidden" name="user_id" value="<?php echo esc_attr((string) $user_id); ?>">
                    <?php wp_nonce_field('jr_crm_notas_' . $user_id); ?>
                    <p>
                        <label for="jr_notas_crm"><strong><?php esc_html_e('Notas internas', YUNIORROJAS_TEXT_DOMAIN); ?></strong></label><br>
                        <textarea class="large-text" rows="4" id="jr_notas_crm" name="jr_notas_crm"><?php echo esc_textarea($notas_a); ?></textarea>
                    </p>
                    <p>
                        <label>
                            <input type="checkbox" name="jr_sync_notas_barbero" value="1">
                            <?php esc_html_e('También guardar como notas del barbero (visibles en preferencias del cliente)', YUNIORROJAS_TEXT_DOMAIN); ?>
                        </label>
                    </p>
                    <p><button type="submit" class="button button-primary"><?php esc_html_e('Guardar notas', YUNIORROJAS_TEXT_DOMAIN); ?></button></p>
                </form>
            </div>

            <h2><?php esc_html_e('Historial de reservas', YUNIORROJAS_TEXT_DOMAIN); ?></h2>
            <table class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Fecha', YUNIORROJAS_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Servicio', YUNIORROJAS_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Barbero', YUNIORROJAS_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Estado', YUNIORROJAS_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Monto', YUNIORROJAS_TEXT_DOMAIN); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($reservas === array()) : ?>
                        <tr><td colspan="5"><?php esc_html_e('Sin reservas.', YUNIORROJAS_TEXT_DOMAIN); ?></td></tr>
                    <?php else : ?>
                        <?php foreach ($reservas as $r) : ?>
                            <tr>
                                <td>
                                    <a href="<?php echo esc_url(get_edit_post_link((int) $r['id'], 'raw') ?: '#'); ?>">
                                        <?php echo esc_html((string) ($r['fecha'] ?? '') . ' ' . (string) ($r['hora_label'] ?? '')); ?>
                                    </a>
                                </td>
                                <td><?php echo esc_html((string) ($r['servicio_nombre'] ?? '')); ?></td>
                                <td><?php echo esc_html((string) ($r['barbero_nombre'] ?? '')); ?></td>
                                <td><?php echo esc_html(yuniorrojas_reserva_estado_label((string) ($r['estado'] ?? ''))); ?></td>
                                <td>S/. <?php echo esc_html((string) ($r['precio'] ?? '0')); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
        return;
    }

    $clientes = yuniorrojas_crm_listar_clientes();
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Clientes', YUNIORROJAS_TEXT_DOMAIN); ?></h1>
        <p class="description"><?php esc_html_e('CRM ligero: visitas, gasto, nivel y notas.', YUNIORROJAS_TEXT_DOMAIN); ?></p>
        <table class="wp-list-table widefat striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Cliente', YUNIORROJAS_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Contacto', YUNIORROJAS_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Visitas', YUNIORROJAS_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Total', YUNIORROJAS_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Última', YUNIORROJAS_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Nivel', YUNIORROJAS_TEXT_DOMAIN); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($clientes === array()) : ?>
                    <tr><td colspan="6"><?php esc_html_e('Aún no hay clientes con cuenta.', YUNIORROJAS_TEXT_DOMAIN); ?></td></tr>
                <?php else : ?>
                    <?php foreach ($clientes as $c) : ?>
                        <tr>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=yuniorrojas-clientes&user_id=' . (int) $c['user_id'])); ?>">
                                    <strong><?php echo esc_html((string) $c['nombre']); ?></strong>
                                </a>
                            </td>
                            <td>
                                <?php echo esc_html((string) $c['email']); ?>
                                <?php if ((string) $c['telefono'] !== '') : ?>
                                    <br><span class="description"><?php echo esc_html((string) $c['telefono']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html((string) $c['visitas']); ?></td>
                            <td>S/. <?php echo esc_html(number_format((float) $c['total'], 2)); ?></td>
                            <td><?php echo esc_html((string) $c['ultima'] !== '' ? (string) $c['ultima'] : '—'); ?></td>
                            <td><?php echo esc_html((string) $c['nivel']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

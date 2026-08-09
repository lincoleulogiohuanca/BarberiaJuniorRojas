<?php
/**
 * Cola de verificación de pagos Plin / transferencias manuales.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Reservas pendientes de pago (web digital).
 *
 * @return list<array<string, mixed>>
 */
function yuniorrojas_pagos_pendientes(): array
{
    $ids = get_posts(array(
        'post_type'      => YUNIORROJAS_CPT_RESERVAS,
        'post_status'    => 'publish',
        'posts_per_page' => 100,
        'fields'         => 'ids',
        'meta_query'     => array(
            'relation' => 'AND',
            array(
                'key'   => yuniorrojas_reserva_meta_key('estado'),
                'value' => 'pendiente',
            ),
            array(
                'key'     => yuniorrojas_reserva_meta_key('pago_verificado'),
                'value'   => '1',
                'compare' => '!=',
            ),
        ),
        'orderby'        => 'date',
        'order'          => 'DESC',
    ));

    $out = array();
    foreach ($ids as $id) {
        $r = yuniorrojas_obtener_reserva((int) $id);
        if ($r === null) {
            continue;
        }
        // Culqi ya cobrado no pertenece a esta cola.
        if (!empty($r['pago_verificado']) || !empty($r['culqi_charge_id'])) {
            continue;
        }
        $out[] = $r;
    }

    return $out;
}

/**
 * Menú Pagos.
 */
function yuniorrojas_pagos_registrar_menu(): void
{
    $n = count(yuniorrojas_pagos_pendientes());
    $title = __('Pagos', YUNIORROJAS_TEXT_DOMAIN);
    if ($n > 0) {
        $title = sprintf(
            /* translators: %s: count */
            __('Pagos %s', YUNIORROJAS_TEXT_DOMAIN),
            '<span class="awaiting-mod">' . (int) $n . '</span>'
        );
    }

    add_submenu_page(
        'edit.php?post_type=' . YUNIORROJAS_CPT_RESERVAS,
        __('Verificar pagos', YUNIORROJAS_TEXT_DOMAIN),
        $title,
        'edit_posts',
        'yuniorrojas-pagos',
        'yuniorrojas_pagos_render_page'
    );
}
add_action('admin_menu', 'yuniorrojas_pagos_registrar_menu', 12);

/**
 * Página cola de pagos.
 */
function yuniorrojas_pagos_render_page(): void
{
    if (!current_user_can('edit_posts')) {
        return;
    }

    $items    = yuniorrojas_pagos_pendientes();
    $redirect = admin_url('admin.php?page=yuniorrojas-pagos');
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Verificar pagos', YUNIORROJAS_TEXT_DOMAIN); ?></h1>
        <p class="description"><?php esc_html_e('Cola de reservas con Plin/transferencia pendiente de verificación manual. Los cobros Culqi (tarjeta o Yape) se confirman automáticamente.', YUNIORROJAS_TEXT_DOMAIN); ?></p>

        <?php if ($items === array()) : ?>
            <div class="notice notice-success inline"><p><?php esc_html_e('No hay pagos pendientes de verificación.', YUNIORROJAS_TEXT_DOMAIN); ?></p></div>
        <?php else : ?>
            <table class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Cliente', YUNIORROJAS_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Cita', YUNIORROJAS_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Método', YUNIORROJAS_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Código', YUNIORROJAS_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Comprobante', YUNIORROJAS_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Monto', YUNIORROJAS_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Acciones', YUNIORROJAS_TEXT_DOMAIN); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $r) : ?>
                        <?php
                        $nombre = trim((string) ($r['cliente_nombres'] ?? '') . ' ' . (string) ($r['cliente_apellidos'] ?? ''));
                        $edit   = get_edit_post_link((int) $r['id'], 'raw') ?: '#';
                        $fecha  = (string) ($r['fecha'] ?? '');
                        $dt     = DateTime::createFromFormat('Y-m-d', $fecha);
                        $fecha_l = $dt instanceof DateTime ? $dt->format('d/m/Y') : $fecha;
                        ?>
                        <tr>
                            <td>
                                <strong><a href="<?php echo esc_url($edit); ?>"><?php echo esc_html($nombre !== '' ? $nombre : '—'); ?></a></strong>
                                <br><span class="description"><?php echo esc_html((string) ($r['cliente_telefono'] ?? '')); ?></span>
                            </td>
                            <td>
                                <?php echo esc_html($fecha_l . ' · ' . (string) ($r['hora_label'] ?? '')); ?>
                                <br><?php echo esc_html((string) ($r['servicio_nombre'] ?? '') . ' · ' . (string) ($r['barbero_nombre'] ?? '')); ?>
                            </td>
                            <td><?php echo esc_html(yuniorrojas_reserva_metodo_pago_label((string) ($r['metodo_pago'] ?? ''))); ?></td>
                            <td><code><?php echo esc_html((string) ($r['codigo_operacion'] ?? '—')); ?></code></td>
                            <td>
                                <?php if (!empty($r['comprobante_url'])) : ?>
                                    <a href="<?php echo esc_url((string) $r['comprobante_url']); ?>" target="_blank" rel="noopener noreferrer">
                                        <?php esc_html_e('Ver captura', YUNIORROJAS_TEXT_DOMAIN); ?>
                                    </a>
                                <?php else : ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td><strong>S/. <?php echo esc_html((string) ($r['precio'] ?? '0')); ?></strong></td>
                            <td>
                                <?php
                                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                echo yuniorrojas_admin_acciones_html((int) $r['id'], $r, $redirect);
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}

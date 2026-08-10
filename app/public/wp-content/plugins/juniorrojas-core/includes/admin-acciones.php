<?php
/**
 * Acciones rápidas de reserva (1 clic): pago, completar, rechazar.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Ejecuta una acción admin sobre una reserva.
 *
 * @param 'verificar_pago'|'completar'|'rechazar_pago'|'ya_pago' $accion
 * @return true|WP_Error
 */
function yuniorrojas_admin_accion_reserva(int $reserva_id, string $accion)
{
    if (!current_user_can('edit_post', $reserva_id) && !current_user_can('manage_options')) {
        return new WP_Error('permiso', 'No tienes permiso.');
    }

    $reserva = yuniorrojas_obtener_reserva($reserva_id);
    if ($reserva === null) {
        return new WP_Error('no_encontrada', 'Reserva no encontrada.');
    }

    $accion = sanitize_key($accion);
    $estado = (string) ($reserva['estado'] ?? '');

    if (in_array($estado, array('cancelada', 'no_show'), true) && $accion !== 'completar') {
        return new WP_Error('estado', 'Esta reserva no admite esa acción.');
    }

    switch ($accion) {
        case 'verificar_pago':
        case 'ya_pago':
            update_post_meta($reserva_id, yuniorrojas_reserva_meta_key('pago_verificado'), '1');
            if ($estado === 'pendiente' || $estado === '') {
                update_post_meta($reserva_id, yuniorrojas_reserva_meta_key('estado'), 'confirmada');
            }
            if (function_exists('yuniorrojas_notificar_reserva')) {
                yuniorrojas_notificar_reserva($reserva_id, 'pago_verificado');
            }
            if (function_exists('yuniorrojas_admin_notif_marcar')) {
                yuniorrojas_admin_notif_marcar('pago_' . $reserva_id);
            }
            break;

        case 'completar':
            update_post_meta($reserva_id, yuniorrojas_reserva_meta_key('estado'), 'completada');
            update_post_meta($reserva_id, yuniorrojas_reserva_meta_key('pago_verificado'), '1');
            break;

        case 'rechazar_pago':
            update_post_meta($reserva_id, yuniorrojas_reserva_meta_key('pago_verificado'), '0');
            update_post_meta($reserva_id, yuniorrojas_reserva_meta_key('estado'), 'cancelada');
            $notas = (string) get_post_meta($reserva_id, yuniorrojas_reserva_meta_key('notas_internas'), true);
            $nota  = 'Pago rechazado por el estudio (' . current_time('d/m/Y H:i') . ').';
            update_post_meta(
                $reserva_id,
                yuniorrojas_reserva_meta_key('notas_internas'),
                trim($notas . "\n" . $nota)
            );
            if (function_exists('yuniorrojas_notificar_reserva')) {
                yuniorrojas_notificar_reserva($reserva_id, 'cancelada');
            }
            if (function_exists('yuniorrojas_admin_notif_marcar')) {
                yuniorrojas_admin_notif_marcar('pago_' . $reserva_id);
            }
            // Reembolso Culqi automático si hubo cargo online.
            if (function_exists('yuniorrojas_reserva_refund_culqi_si_aplica')) {
                yuniorrojas_reserva_refund_culqi_si_aplica($reserva_id, 'solicitud_comprador');
            }
            // Liberar hueco para lista de espera.
            $bid = (int) ($reserva['barbero_id'] ?? 0);
            $fecha = (string) ($reserva['fecha'] ?? '');
            if ($bid > 0 && $fecha !== '' && function_exists('yuniorrojas_lista_espera_avisar_hueco')) {
                yuniorrojas_lista_espera_avisar_hueco($bid, $fecha);
            }
            break;

        default:
            return new WP_Error('accion', 'Acción no válida.');
    }

    return true;
}

/**
 * Handler admin-post genérico.
 */
function yuniorrojas_admin_post_accion_reserva(): void
{
    $reserva_id = isset($_GET['reserva_id']) ? absint($_GET['reserva_id']) : 0; // phpcs:ignore
    $accion     = isset($_GET['jr_accion']) ? sanitize_key(wp_unslash((string) $_GET['jr_accion'])) : ''; // phpcs:ignore
    $nonce      = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash((string) $_GET['_wpnonce'])) : ''; // phpcs:ignore

    if ($reserva_id <= 0 || $accion === '') {
        wp_die(esc_html__('Solicitud no válida.', YUNIORROJAS_TEXT_DOMAIN));
    }
    if (!wp_verify_nonce($nonce, 'jr_accion_' . $accion . '_' . $reserva_id)) {
        wp_die(esc_html__('Enlace no válido o expirado.', YUNIORROJAS_TEXT_DOMAIN));
    }

    $result = yuniorrojas_admin_accion_reserva($reserva_id, $accion);
    if (is_wp_error($result)) {
        wp_die(esc_html($result->get_error_message()));
    }

    $redirect = isset($_GET['redirect_to']) ? esc_url_raw(wp_unslash((string) $_GET['redirect_to'])) : ''; // phpcs:ignore
    if ($redirect === '') {
        $redirect = get_edit_post_link($reserva_id, 'raw') ?: admin_url('edit.php?post_type=' . YUNIORROJAS_CPT_RESERVAS);
    }

    wp_safe_redirect(add_query_arg('jr_accion_ok', $accion, $redirect));
    exit;
}
add_action('admin_post_jr_accion_reserva', 'yuniorrojas_admin_post_accion_reserva');

/**
 * URL firmada para acción rápida.
 */
function yuniorrojas_admin_accion_url(int $reserva_id, string $accion, string $redirect = ''): string
{
    $args = array(
        'action'     => 'jr_accion_reserva',
        'reserva_id' => $reserva_id,
        'jr_accion'  => $accion,
        '_wpnonce'   => wp_create_nonce('jr_accion_' . $accion . '_' . $reserva_id),
    );
    if ($redirect !== '') {
        $args['redirect_to'] = $redirect;
    }

    return add_query_arg($args, admin_url('admin-post.php'));
}

/**
 * Botones HTML de acciones según estado.
 */
function yuniorrojas_admin_acciones_html(int $reserva_id, ?array $reserva = null, string $redirect = ''): string
{
    if ($reserva === null) {
        $reserva = yuniorrojas_obtener_reserva($reserva_id);
    }
    if ($reserva === null) {
        return '';
    }

    $estado = (string) ($reserva['estado'] ?? '');
    $pago   = !empty($reserva['pago_verificado']);
    $html   = array();

    if ($estado === 'pendiente' || (!$pago && in_array($estado, array('pendiente', 'confirmada'), true))) {
        $html[] = sprintf(
            '<a class="button button-small button-primary" href="%s">%s</a>',
            esc_url(yuniorrojas_admin_accion_url($reserva_id, 'verificar_pago', $redirect)),
            esc_html__('Ya pagó', YUNIORROJAS_TEXT_DOMAIN)
        );
    }

    if ($estado === 'pendiente') {
        $html[] = sprintf(
            '<a class="button button-small" href="%s" onclick="return confirm(%s);">%s</a>',
            esc_url(yuniorrojas_admin_accion_url($reserva_id, 'rechazar_pago', $redirect)),
            esc_attr(wp_json_encode(__('¿Rechazar este pago y cancelar la cita?', YUNIORROJAS_TEXT_DOMAIN))),
            esc_html__('Rechazar', YUNIORROJAS_TEXT_DOMAIN)
        );
    }

    if (in_array($estado, array('pendiente', 'confirmada'), true)) {
        $html[] = sprintf(
            '<a class="button button-small" href="%s">%s</a>',
            esc_url(yuniorrojas_admin_accion_url($reserva_id, 'completar', $redirect)),
            esc_html__('Completada', YUNIORROJAS_TEXT_DOMAIN)
        );
    }

    if ($html === array()) {
        return '';
    }

    return '<div class="jr-acciones-rapidas">' . implode('', $html) . '</div>';
}

/**
 * Columna Acciones en listado de reservas.
 *
 * @param array<string, string> $columns
 * @return array<string, string>
 */
function yuniorrojas_admin_reservas_columna_acciones(array $columns): array
{
    $columns['jr_acciones'] = __('Acciones', YUNIORROJAS_TEXT_DOMAIN);
    return $columns;
}
add_filter('manage_' . YUNIORROJAS_CPT_RESERVAS . '_posts_columns', 'yuniorrojas_admin_reservas_columna_acciones', 20);

/**
 * Render columna acciones.
 */
function yuniorrojas_admin_reservas_columna_acciones_render(string $column, int $post_id): void
{
    if ($column !== 'jr_acciones') {
        return;
    }
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML con esc_url/esc_html interno.
    echo yuniorrojas_admin_acciones_html($post_id, null, admin_url('edit.php?post_type=' . YUNIORROJAS_CPT_RESERVAS));
}
add_action('manage_' . YUNIORROJAS_CPT_RESERVAS . '_posts_custom_column', 'yuniorrojas_admin_reservas_columna_acciones_render', 20, 2);

/**
 * Notice tras acción.
 */
function yuniorrojas_admin_accion_notice(): void
{
    if (!isset($_GET['jr_accion_ok'])) { // phpcs:ignore
        return;
    }
    $accion = sanitize_key(wp_unslash((string) $_GET['jr_accion_ok'])); // phpcs:ignore
    $msgs   = array(
        'verificar_pago' => __('Pago verificado y cita confirmada.', YUNIORROJAS_TEXT_DOMAIN),
        'ya_pago'        => __('Marcado como pagado.', YUNIORROJAS_TEXT_DOMAIN),
        'completar'      => __('Cita marcada como completada.', YUNIORROJAS_TEXT_DOMAIN),
        'rechazar_pago'  => __('Pago rechazado y cita cancelada.', YUNIORROJAS_TEXT_DOMAIN),
    );
    if (!isset($msgs[$accion])) {
        return;
    }
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($msgs[$accion]) . '</p></div>';
}
add_action('admin_notices', 'yuniorrojas_admin_accion_notice');

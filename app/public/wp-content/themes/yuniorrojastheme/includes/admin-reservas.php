<?php
/**
 * Listado admin de Reservas (columnas, vistas Activas/Historial, contadores).
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Etiqueta legible del estado de reserva.
 */
function yuniorrojas_reserva_estado_label(string $estado): string
{
    $estado = sanitize_key($estado);
    $map    = array(
        'pendiente'  => __('Pendiente de pago', YUNIORROJAS_TEXT_DOMAIN),
        'confirmada' => __('Confirmada', YUNIORROJAS_TEXT_DOMAIN),
        'cancelada'  => __('Cancelada', YUNIORROJAS_TEXT_DOMAIN),
        'completada' => __('Completada', YUNIORROJAS_TEXT_DOMAIN),
        'no_show'    => __('No show', YUNIORROJAS_TEXT_DOMAIN),
    );

    return $map[$estado] ?? ucfirst($estado !== '' ? $estado : 'confirmada');
}

/**
 * Etiqueta de estado orientada al cliente (Mi cuenta / emails).
 */
function yuniorrojas_reserva_estado_label_cliente(string $estado): string
{
    $estado = sanitize_key($estado);
    $map    = array(
        'pendiente'  => __('Pendiente de verificación', YUNIORROJAS_TEXT_DOMAIN),
        'confirmada' => __('Confirmada', YUNIORROJAS_TEXT_DOMAIN),
        'cancelada'  => __('Cancelada', YUNIORROJAS_TEXT_DOMAIN),
        'completada' => __('Completada', YUNIORROJAS_TEXT_DOMAIN),
        'no_show'    => __('No asistió', YUNIORROJAS_TEXT_DOMAIN),
    );

    return $map[$estado] ?? yuniorrojas_reserva_estado_label($estado);
}

/**
 * Etiqueta legible del método de pago.
 */
function yuniorrojas_reserva_metodo_pago_label(string $metodo): string
{
    $metodo = sanitize_key($metodo);
    if (function_exists('yuniorrojas_medios_pago_labels_map')) {
        $map = yuniorrojas_medios_pago_labels_map();
        if (isset($map[$metodo]) && $map[$metodo] !== '') {
            return $map[$metodo];
        }
    }

    $map = array(
        'estudio'       => __('Pago en estudio', YUNIORROJAS_TEXT_DOMAIN),
        'efectivo'      => __('Efectivo', YUNIORROJAS_TEXT_DOMAIN),
        'tarjeta'       => __('Tarjeta / Yape (Culqi)', YUNIORROJAS_TEXT_DOMAIN),
        'culqi'         => __('Tarjeta / Yape (Culqi)', YUNIORROJAS_TEXT_DOMAIN),
        'yape'          => __('Plin (manual)', YUNIORROJAS_TEXT_DOMAIN),
        'plin'          => __('Plin', YUNIORROJAS_TEXT_DOMAIN),
        'transferencia' => __('Transferencia', YUNIORROJAS_TEXT_DOMAIN),
    );

    if ($metodo === '') {
        return $map['estudio'];
    }

    return $map[$metodo] ?? ucfirst(str_replace(array('-', '_'), ' ', $metodo));
}

/**
 * Etiqueta de estado de pago orientada al cliente.
 *
 * @param array<string, mixed> $reserva
 */
function yuniorrojas_reserva_pago_label_cliente(array $reserva): string
{
    if (!empty($reserva['pago_verificado'])) {
        return __('Pago verificado', YUNIORROJAS_TEXT_DOMAIN);
    }

    $metodo = sanitize_key((string) ($reserva['metodo_pago'] ?? ''));
    if ($metodo === '' || $metodo === 'estudio' || $metodo === 'efectivo') {
        return __('Pago pendiente', YUNIORROJAS_TEXT_DOMAIN);
    }

    // Plin / transferencia / manual: el estudio debe revisar el depósito.
    return __('Pago pendiente de verificación', YUNIORROJAS_TEXT_DOMAIN);
}

/**
 * Estados “activas” (citas vigentes).
 *
 * @return string[]
 */
function yuniorrojas_reserva_estados_activos(): array
{
    return array('pendiente', 'confirmada');
}

/**
 * Estados de historial.
 *
 * @return string[]
 */
function yuniorrojas_reserva_estados_historial(): array
{
    return array('completada', 'cancelada', 'no_show');
}

/**
 * Vista actual del listado admin de reservas.
 *
 * @return 'all'|'activas'|'historial'
 */
function yuniorrojas_admin_reservas_vista_actual(): string
{
    $vista = isset($_GET['jr_vista']) ? sanitize_key(wp_unslash((string) $_GET['jr_vista'])) : 'activas'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if (!in_array($vista, array('all', 'activas', 'historial'), true)) {
        return 'activas';
    }

    return $vista;
}

/**
 * Cuenta reservas publicadas por grupo de estado.
 *
 * @return array{all:int,activas:int,historial:int}
 */
function yuniorrojas_admin_reservas_contadores(): array
{
    global $wpdb;

    $counts = array(
        'all'       => 0,
        'activas'   => 0,
        'historial' => 0,
    );

    $post_type = YUNIORROJAS_CPT_RESERVAS;
    $meta_key  = yuniorrojas_reserva_meta_key('estado');

    // Total publicadas.
    $counts['all'] = (int) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type = %s AND post_status = 'publish'",
            $post_type
        )
    );

    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT pm.meta_value AS estado, COUNT(p.ID) AS total
             FROM {$wpdb->posts} p
             LEFT JOIN {$wpdb->postmeta} pm
               ON pm.post_id = p.ID AND pm.meta_key = %s
             WHERE p.post_type = %s AND p.post_status = 'publish'
             GROUP BY pm.meta_value",
            $meta_key,
            $post_type
        ),
        ARRAY_A
    );

    if (!is_array($rows)) {
        return $counts;
    }

    $activos    = yuniorrojas_reserva_estados_activos();
    $historial  = yuniorrojas_reserva_estados_historial();

    foreach ($rows as $row) {
        $estado = sanitize_key((string) ($row['estado'] ?? ''));
        $total  = (int) ($row['total'] ?? 0);

        // Meta vacía = confirmada (comportamiento del servicio).
        if ($estado === '') {
            $estado = 'confirmada';
        }

        if (in_array($estado, $activos, true)) {
            $counts['activas'] += $total;
        } elseif (in_array($estado, $historial, true)) {
            $counts['historial'] += $total;
        } else {
            $counts['activas'] += $total;
        }
    }

    return $counts;
}

/**
 * Columnas del listado.
 *
 * @param array<string, string> $columns
 * @return array<string, string>
 */
function yuniorrojas_admin_reservas_columnas(array $columns): array
{
    return array(
        'cb'            => $columns['cb'] ?? '<input type="checkbox" />',
        'title'         => __('Servicio', YUNIORROJAS_TEXT_DOMAIN),
        'jr_cliente'    => __('Cliente', YUNIORROJAS_TEXT_DOMAIN),
        'jr_cita'       => __('Fecha / hora', YUNIORROJAS_TEXT_DOMAIN),
        'jr_barbero'    => __('Barbero', YUNIORROJAS_TEXT_DOMAIN),
        'jr_estado'     => __('Estado', YUNIORROJAS_TEXT_DOMAIN),
        'jr_pago'       => __('Pago', YUNIORROJAS_TEXT_DOMAIN),
    );
}
add_filter('manage_' . YUNIORROJAS_CPT_RESERVAS . '_posts_columns', 'yuniorrojas_admin_reservas_columnas');

/**
 * Contenido de columnas personalizadas.
 */
function yuniorrojas_admin_reservas_columna(string $column, int $post_id): void
{
    $reserva = yuniorrojas_obtener_reserva($post_id);
    if ($reserva === null) {
        echo '—';
        return;
    }

    switch ($column) {
        case 'jr_cliente':
            $nombre = trim(
                (string) ($reserva['cliente_nombres'] ?? '') . ' ' . (string) ($reserva['cliente_apellidos'] ?? '')
            );
            if ($nombre === '') {
                $nombre = '—';
            }
            echo esc_html($nombre);
            $tel = (string) ($reserva['cliente_telefono'] ?? '');
            $mail = (string) ($reserva['cliente_email'] ?? '');
            if ($tel !== '' || $mail !== '') {
                echo '<br><span style="color:#646970;font-size:12px;">';
                echo esc_html($tel !== '' ? $tel : $mail);
                echo '</span>';
            }
            break;

        case 'jr_cita':
            $fecha = (string) ($reserva['fecha'] ?? '');
            $hora  = (string) ($reserva['hora_label'] ?? '');
            if ($fecha === '') {
                echo '—';
                break;
            }
            $dt = DateTime::createFromFormat('Y-m-d', $fecha);
            $fecha_label = $dt instanceof DateTime ? $dt->format('d/m/Y') : $fecha;
            echo '<strong>' . esc_html($fecha_label) . '</strong>';
            if ($hora !== '') {
                echo '<br>' . esc_html($hora);
            }
            break;

        case 'jr_barbero':
            $barbero = (string) ($reserva['barbero_nombre'] ?? '');
            echo esc_html($barbero !== '' ? $barbero : '—');
            break;

        case 'jr_estado':
            $estado = (string) ($reserva['estado'] ?? 'confirmada');
            $label  = yuniorrojas_reserva_estado_label($estado);
            $color  = '#2271b1';
            if ($estado === 'confirmada' || $estado === 'pendiente') {
                $color = '#00a32a';
            } elseif ($estado === 'cancelada' || $estado === 'no_show') {
                $color = '#d63638';
            } elseif ($estado === 'completada') {
                $color = '#646970';
            }
            printf(
                '<span class="jr-reserva-estado" style="display:inline-block;padding:2px 8px;border-radius:999px;background:%1$s1a;color:%1$s;font-weight:600;font-size:12px;">%2$s</span>',
                esc_attr($color),
                esc_html($label)
            );
            break;

        case 'jr_pago':
            echo esc_html(yuniorrojas_reserva_metodo_pago_label((string) ($reserva['metodo_pago'] ?? '')));
            break;
    }
}
add_action('manage_' . YUNIORROJAS_CPT_RESERVAS . '_posts_custom_column', 'yuniorrojas_admin_reservas_columna', 10, 2);

/**
 * Columnas ordenables.
 *
 * @param array<string, string> $columns
 * @return array<string, string>
 */
function yuniorrojas_admin_reservas_columnas_ordenables(array $columns): array
{
    $columns['jr_cita']   = 'jr_cita';
    $columns['jr_estado'] = 'jr_estado';
    return $columns;
}
add_filter('manage_edit-' . YUNIORROJAS_CPT_RESERVAS . '_sortable_columns', 'yuniorrojas_admin_reservas_columnas_ordenables');

/**
 * Vistas: Todas / Activas / Historial (con contadores).
 *
 * @param array<string, string> $views
 * @return array<string, string>
 */
function yuniorrojas_admin_reservas_vistas(array $views): array
{
    $counts = yuniorrojas_admin_reservas_contadores();
    $actual = yuniorrojas_admin_reservas_vista_actual();
    $base   = admin_url('edit.php?post_type=' . YUNIORROJAS_CPT_RESERVAS);

    $links = array(
        'activas' => array(
            'label' => sprintf(
                /* translators: %d: count */
                __('Activas <span class="count">(%d)</span>', YUNIORROJAS_TEXT_DOMAIN),
                $counts['activas']
            ),
            'url'   => add_query_arg('jr_vista', 'activas', $base),
        ),
        'historial' => array(
            'label' => sprintf(
                /* translators: %d: count */
                __('Historial <span class="count">(%d)</span>', YUNIORROJAS_TEXT_DOMAIN),
                $counts['historial']
            ),
            'url'   => add_query_arg('jr_vista', 'historial', $base),
        ),
        'all' => array(
            'label' => sprintf(
                /* translators: %d: count */
                __('Todas <span class="count">(%d)</span>', YUNIORROJAS_TEXT_DOMAIN),
                $counts['all']
            ),
            'url'   => add_query_arg('jr_vista', 'all', $base),
        ),
    );

    $out = array();
    foreach ($links as $key => $link) {
        $class = $actual === $key ? 'class="current" aria-current="page"' : '';
        $out[$key] = sprintf(
            '<a href="%s" %s>%s</a>',
            esc_url($link['url']),
            $class,
            $link['label']
        );
    }

    return $out;
}
add_filter('views_edit-' . YUNIORROJAS_CPT_RESERVAS, 'yuniorrojas_admin_reservas_vistas');

/**
 * Aplica filtro de vista + orden por fecha de cita.
 */
function yuniorrojas_admin_reservas_pre_get_posts(WP_Query $query): void
{
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }

    global $pagenow;
    if ($pagenow !== 'edit.php') {
        return;
    }

    $post_type = $query->get('post_type');
    if ($post_type !== YUNIORROJAS_CPT_RESERVAS) {
        return;
    }

    $vista   = yuniorrojas_admin_reservas_vista_actual();
    $orderby = (string) $query->get('orderby');
    $order   = strtoupper((string) $query->get('order'));
    if ($order !== 'ASC' && $order !== 'DESC') {
        $order = $vista === 'historial' ? 'DESC' : 'ASC';
    }

    if ($vista === 'activas') {
        $query->set(
            'meta_query',
            array(
                'relation' => 'OR',
                array(
                    'key'     => yuniorrojas_reserva_meta_key('estado'),
                    'value'   => yuniorrojas_reserva_estados_activos(),
                    'compare' => 'IN',
                ),
                array(
                    'key'     => yuniorrojas_reserva_meta_key('estado'),
                    'compare' => 'NOT EXISTS',
                ),
            )
        );
    } elseif ($vista === 'historial') {
        $query->set(
            'meta_query',
            array(
                array(
                    'key'     => yuniorrojas_reserva_meta_key('estado'),
                    'value'   => yuniorrojas_reserva_estados_historial(),
                    'compare' => 'IN',
                ),
            )
        );
    }

    if ($orderby === 'jr_estado') {
        $query->set('meta_key', yuniorrojas_reserva_meta_key('estado'));
        $query->set('orderby', 'meta_value');
        $query->set('order', $order);
        return;
    }

    // Por defecto y columna Fecha/hora: ordenar por fecha de la cita (Y-m-d).
    $query->set('meta_key', yuniorrojas_reserva_meta_key('fecha'));
    $query->set('orderby', 'meta_value');
    $query->set('order', $order);
}
add_action('pre_get_posts', 'yuniorrojas_admin_reservas_pre_get_posts');

/**
 * Redirige el listado base a vista Activas (contadores claros al entrar).
 */
function yuniorrojas_admin_reservas_default_vista(): void
{
    global $pagenow;

    if ($pagenow !== 'edit.php') {
        return;
    }

    $post_type = isset($_GET['post_type']) ? sanitize_key(wp_unslash((string) $_GET['post_type'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if ($post_type !== YUNIORROJAS_CPT_RESERVAS) {
        return;
    }

    if (isset($_GET['jr_vista']) || isset($_GET['s']) || isset($_GET['post_status'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        return;
    }

    wp_safe_redirect(add_query_arg(
        array(
            'post_type' => YUNIORROJAS_CPT_RESERVAS,
            'jr_vista'  => 'activas',
        ),
        admin_url('edit.php')
    ));
    exit;
}
add_action('load-edit.php', 'yuniorrojas_admin_reservas_default_vista');

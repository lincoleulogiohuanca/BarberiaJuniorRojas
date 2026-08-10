<?php
/**
 * Módulo admin: Ingresos (KPIs, filtros, gráficos desde reservas).
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @return array{desde:string,hasta:string,vista:string,metodo:string,barbero_id:int,servicio_id:int}
 */
function yuniorrojas_ingresos_filtros_desde_request(): array
{
    $hoy    = current_time('Y-m-d');
    $desde  = isset($_GET['jr_desde']) ? sanitize_text_field(wp_unslash((string) $_GET['jr_desde'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $hasta  = isset($_GET['jr_hasta']) ? sanitize_text_field(wp_unslash((string) $_GET['jr_hasta'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    // Default: completadas + proyección, para ver citas confirmadas de clientes.
    $vista  = isset($_GET['jr_vista_ing']) ? sanitize_key(wp_unslash((string) $_GET['jr_vista_ing'])) : 'todas'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $metodo = isset($_GET['jr_metodo']) ? sanitize_key(wp_unslash((string) $_GET['jr_metodo'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $barbero_id  = isset($_GET['jr_barbero']) ? absint($_GET['jr_barbero']) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $servicio_id = isset($_GET['jr_servicio']) ? absint($_GET['jr_servicio']) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

    // Por defecto: mes completo (incluye citas futuras del mes).
    if ($desde === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) {
        $desde = gmdate('Y-m-01', strtotime($hoy . ' 12:00:00'));
    }
    if ($hasta === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) {
        $hasta = gmdate('Y-m-t', strtotime($hoy . ' 12:00:00'));
    }
    if ($desde > $hasta) {
        $tmp   = $desde;
        $desde = $hasta;
        $hasta = $tmp;
    }
    if (!in_array($vista, array('completadas', 'proyeccion', 'todas'), true)) {
        $vista = 'todas';
    }
    if ($metodo !== '' && !in_array($metodo, array('estudio', 'tarjeta', 'yape', 'plin', 'efectivo', 'transferencia'), true)) {
        $metodo = '';
    }

    return array(
        'desde'       => $desde,
        'hasta'       => $hasta,
        'vista'       => $vista,
        'metodo'      => $metodo,
        'barbero_id'  => $barbero_id,
        'servicio_id' => $servicio_id,
    );
}

/**
 * Estados según vista de ingresos.
 *
 * @return string[]
 */
function yuniorrojas_ingresos_estados_para_vista(string $vista): array
{
    if ($vista === 'proyeccion') {
        return array('pendiente', 'confirmada');
    }
    if ($vista === 'todas') {
        return array('pendiente', 'confirmada', 'completada');
    }

    // "Completadas": también traemos confirmadas/pendientes para marcar las ya pasadas como ganado.
    return array('completada', 'confirmada', 'pendiente');
}

/**
 * Parsea precio a float.
 */
function yuniorrojas_ingresos_parse_precio(string $precio): float
{
    $precio = trim(str_replace(array('S/.', 'S/', ' '), '', $precio));
    $precio = str_replace(',', '.', $precio);
    if ($precio === '' || !is_numeric($precio)) {
        return 0.0;
    }

    return round((float) $precio, 2);
}

/**
 * Agrega ingresos desde reservas.
 *
 * @param array{desde?:string,hasta?:string,vista?:string,metodo?:string,barbero_id?:int,servicio_id?:int} $filtros
 * @return array{
 *   filtros:array<string,mixed>,
 *   kpis:array{total:float,citas:int,ticket:float,por_metodo:array<string,float>},
 *   serie:array{labels:string[],values:float[],modo:string},
 *   metodos:array{labels:string[],values:float[]},
 *   barberos:array{labels:string[],values:float[]},
 *   servicios:array{labels:string[],values:float[]},
 *   filas:list<array<string,mixed>>
 * }
 */
function yuniorrojas_ingresos_agregar(array $filtros = array()): array
{
    $hoy = current_time('Y-m-d');
    $defaults = array(
        'desde'       => gmdate('Y-m-01', strtotime($hoy . ' 12:00:00')),
        'hasta'       => gmdate('Y-m-t', strtotime($hoy . ' 12:00:00')),
        'vista'       => 'todas',
        'metodo'      => '',
        'barbero_id'  => 0,
        'servicio_id' => 0,
    );
    $f = array_merge($defaults, $filtros);

    $estados = yuniorrojas_ingresos_estados_para_vista((string) $f['vista']);
    $vista   = (string) $f['vista'];

    $meta_query = array(
        'relation' => 'AND',
        array(
            'key'     => yuniorrojas_reserva_meta_key('fecha'),
            'value'   => array((string) $f['desde'], (string) $f['hasta']),
            'compare' => 'BETWEEN',
            'type'    => 'CHAR',
        ),
        array(
            'key'     => yuniorrojas_reserva_meta_key('estado'),
            'value'   => $estados,
            'compare' => 'IN',
        ),
    );

    if ((string) $f['metodo'] !== '') {
        $metodo = (string) $f['metodo'];
        if ($metodo === 'estudio') {
            $meta_query[] = array(
                'relation' => 'OR',
                array(
                    'key'   => yuniorrojas_reserva_meta_key('metodo_pago'),
                    'value' => 'estudio',
                ),
                array(
                    'key'   => yuniorrojas_reserva_meta_key('metodo_pago'),
                    'value' => 'efectivo',
                ),
                array(
                    'key'     => yuniorrojas_reserva_meta_key('metodo_pago'),
                    'compare' => 'NOT EXISTS',
                ),
            );
        } elseif ($metodo === 'yape' || $metodo === 'plin') {
            $meta_query[] = array(
                'relation' => 'OR',
                array(
                    'key'   => yuniorrojas_reserva_meta_key('metodo_pago'),
                    'value' => 'yape',
                ),
                array(
                    'key'   => yuniorrojas_reserva_meta_key('metodo_pago'),
                    'value' => 'plin',
                ),
                array(
                    'key'   => yuniorrojas_reserva_meta_key('metodo_pago'),
                    'value' => 'transferencia',
                ),
            );
        } else {
            $meta_query[] = array(
                'key'   => yuniorrojas_reserva_meta_key('metodo_pago'),
                'value' => $metodo,
            );
        }
    }

    if ((int) $f['barbero_id'] > 0) {
        $meta_query[] = array(
            'key'   => yuniorrojas_reserva_meta_key('barbero_id'),
            'value' => (string) (int) $f['barbero_id'],
        );
    }

    if ((int) $f['servicio_id'] > 0) {
        $meta_query[] = array(
            'key'   => yuniorrojas_reserva_meta_key('servicio_id'),
            'value' => (string) (int) $f['servicio_id'],
        );
    }

    $q = new WP_Query(array(
        'post_type'              => YUNIORROJAS_CPT_RESERVAS,
        'post_status'            => 'publish',
        'posts_per_page'         => 500,
        'orderby'                => 'meta_value',
        'meta_key'               => yuniorrojas_reserva_meta_key('fecha'),
        'order'                  => 'ASC',
        'meta_query'             => $meta_query,
        'no_found_rows'          => true,
        'update_post_term_cache' => false,
    ));

    $total        = 0.0;
    $citas        = 0;
    $por_metodo   = array();
    $por_dia      = array();
    $por_mes      = array();
    $por_barbero  = array();
    $por_servicio = array();
    $filas        = array();

    $desde_ts = strtotime((string) $f['desde'] . ' 00:00:00');
    $hasta_ts = strtotime((string) $f['hasta'] . ' 23:59:59');
    $dias     = max(1, (int) ceil(($hasta_ts - $desde_ts) / DAY_IN_SECONDS));
    $modo     = $dias > 62 ? 'mes' : 'dia';

    foreach ($q->posts as $post) {
        if (!$post instanceof WP_Post) {
            continue;
        }
        $reserva = yuniorrojas_obtener_reserva((int) $post->ID);
        if ($reserva === null) {
            continue;
        }

        $estado = (string) ($reserva['estado'] ?? '');
        if ($estado === '') {
            $estado = 'confirmada';
        }
        // Seguridad extra: no sumar canceladas/no_show.
        if (in_array($estado, array('cancelada', 'no_show'), true)) {
            continue;
        }

        $fecha = (string) ($reserva['fecha'] ?? '');

        // Vista "Completadas": solo ganado real (completada) o citas ya pasadas aún confirmadas.
        if ($vista === 'completadas') {
            $es_pasada = ($fecha !== '' && $fecha < $hoy);
            if ($estado === 'completada') {
                // ok
            } elseif (in_array($estado, array('confirmada', 'pendiente'), true) && $es_pasada) {
                update_post_meta((int) $post->ID, yuniorrojas_reserva_meta_key('estado'), 'completada');
                $estado = 'completada';
            } else {
                continue;
            }
        }

        $monto = yuniorrojas_ingresos_parse_precio((string) ($reserva['precio'] ?? '0'));
        if ($monto < 0) {
            $monto = 0.0;
        }
        // Ticket mixto: sumar productos del local.
        if (function_exists('yuniorrojas_reserva_total_productos')) {
            $monto += (float) yuniorrojas_reserva_total_productos((int) $post->ID);
        }

        $metodo = sanitize_key((string) ($reserva['metodo_pago'] ?? 'estudio'));
        if ($metodo === '' || $metodo === 'efectivo') {
            $metodo = 'estudio';
        }
        if ($metodo === 'plin' || $metodo === 'yape' || $metodo === 'transferencia') {
            $metodo = 'plin';
        }

        $clave_tiempo = $fecha;
        if ($modo === 'mes' && preg_match('/^(\d{4}-\d{2})/', $fecha, $m)) {
            $clave_tiempo = $m[1];
        }

        $barbero  = (string) ($reserva['barbero_nombre'] ?? __('Sin barbero', YUNIORROJAS_TEXT_DOMAIN));
        $servicio = (string) ($reserva['servicio_nombre'] ?? __('Sin servicio', YUNIORROJAS_TEXT_DOMAIN));
        if ($barbero === '') {
            $barbero = __('Sin barbero', YUNIORROJAS_TEXT_DOMAIN);
        }
        if ($servicio === '') {
            $servicio = __('Sin servicio', YUNIORROJAS_TEXT_DOMAIN);
        }

        $total += $monto;
        $citas++;

        $por_metodo[$metodo] = ($por_metodo[$metodo] ?? 0.0) + $monto;
        if ($modo === 'mes') {
            $por_mes[$clave_tiempo] = ($por_mes[$clave_tiempo] ?? 0.0) + $monto;
        } else {
            $por_dia[$clave_tiempo] = ($por_dia[$clave_tiempo] ?? 0.0) + $monto;
        }
        $por_barbero[$barbero]   = ($por_barbero[$barbero] ?? 0.0) + $monto;
        $por_servicio[$servicio] = ($por_servicio[$servicio] ?? 0.0) + $monto;

        $cliente = trim(
            (string) ($reserva['cliente_nombres'] ?? '') . ' ' . (string) ($reserva['cliente_apellidos'] ?? '')
        );

        $filas[] = array(
            'id'       => (int) $reserva['id'],
            'cliente'  => $cliente !== '' ? $cliente : '—',
            'fecha'    => $fecha,
            'hora'     => (string) ($reserva['hora_label'] ?? $reserva['hora'] ?? ''),
            'servicio' => $servicio,
            'barbero'  => $barbero,
            'metodo'   => $metodo,
            'monto'    => $monto,
            'estado'   => $estado,
        );
    }

    // Serie temporal continua.
    $serie_map = $modo === 'mes' ? $por_mes : $por_dia;
    ksort($serie_map);
    $labels = array();
    $values = array();

    if ($modo === 'dia') {
        $cursor = $desde_ts;
        while ($cursor <= $hasta_ts) {
            $key      = gmdate('Y-m-d', $cursor);
            $labels[] = gmdate('d/m', $cursor);
            $values[] = round((float) ($serie_map[$key] ?? 0), 2);
            $cursor  += DAY_IN_SECONDS;
        }
    } else {
        $cursor = strtotime(gmdate('Y-m-01', $desde_ts) . ' 12:00:00');
        $end    = strtotime(gmdate('Y-m-01', $hasta_ts) . ' 12:00:00');
        while ($cursor <= $end) {
            $key      = gmdate('Y-m', $cursor);
            $labels[] = gmdate('m/Y', $cursor);
            $values[] = round((float) ($serie_map[$key] ?? 0), 2);
            $cursor   = strtotime('+1 month', $cursor);
        }
    }

    arsort($por_barbero);
    arsort($por_servicio);
    $por_barbero  = array_slice($por_barbero, 0, 8, true);
    $por_servicio = array_slice($por_servicio, 0, 8, true);

    $metodo_labels = array();
    $metodo_values = array();
    foreach ($por_metodo as $mkey => $mval) {
        $metodo_labels[] = yuniorrojas_reserva_metodo_pago_label((string) $mkey);
        $metodo_values[] = round((float) $mval, 2);
    }

    // Filas más recientes primero en tabla.
    usort(
        $filas,
        static function (array $a, array $b): int {
            return strcmp((string) $b['fecha'], (string) $a['fecha']);
        }
    );

    return array(
        'filtros' => $f,
        'kpis'    => array(
            'total'      => round($total, 2),
            'citas'      => $citas,
            'ticket'     => $citas > 0 ? round($total / $citas, 2) : 0.0,
            'por_metodo' => array_map(static fn ($v) => round((float) $v, 2), $por_metodo),
        ),
        'serie' => array(
            'labels' => $labels,
            'values' => $values,
            'modo'   => $modo,
        ),
        'metodos' => array(
            'labels' => $metodo_labels,
            'values' => $metodo_values,
        ),
        'barberos' => array(
            'labels' => array_keys($por_barbero),
            'values' => array_map(static fn ($v) => round((float) $v, 2), array_values($por_barbero)),
        ),
        'servicios' => array(
            'labels' => array_keys($por_servicio),
            'values' => array_map(static fn ($v) => round((float) $v, 2), array_values($por_servicio)),
        ),
        'filas' => $filas,
    );
}

/**
 * Formatea soles.
 */
function yuniorrojas_ingresos_formato_soles(float $monto): string
{
    return 'S/. ' . number_format($monto, 2, '.', ',');
}

/**
 * Menú Ingresos.
 */
function yuniorrojas_ingresos_registrar_menu(): void
{
    add_menu_page(
        __('Ingresos', YUNIORROJAS_TEXT_DOMAIN),
        __('Ingresos', YUNIORROJAS_TEXT_DOMAIN),
        'manage_options',
        'yuniorrojas-ingresos',
        'yuniorrojas_ingresos_render_page',
        'dashicons-money-alt',
        26
    );
}
add_action('admin_menu', 'yuniorrojas_ingresos_registrar_menu');

/**
 * Assets solo en la pantalla de ingresos.
 */
function yuniorrojas_ingresos_admin_assets(string $hook): void
{
    if ($hook !== 'toplevel_page_yuniorrojas-ingresos') {
        return;
    }

    $core_uri   = defined('JR_CORE_URL') ? JR_CORE_URL : get_template_directory_uri();
    $core_path  = defined('JR_CORE_PATH') ? JR_CORE_PATH : get_template_directory() . '/';
    $css_path   = jr_core_asset_path('assets/admin/ingresos.css');
    $js_path    = jr_core_asset_path('assets/admin/ingresos.js');

    wp_enqueue_style(
        'yuniorrojas-ingresos',
        jr_core_asset_url('assets/admin/ingresos.css'),
        array(),
        file_exists($css_path) ? (string) filemtime($css_path) : '1.0.0'
    );

    wp_enqueue_script(
        'chartjs',
        'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
        array(),
        '4.4.1',
        true
    );

    wp_enqueue_script(
        'yuniorrojas-ingresos',
        jr_core_asset_url('assets/admin/ingresos.js'),
        array('chartjs'),
        file_exists($js_path) ? (string) filemtime($js_path) : '1.0.0',
        true
    );
}
add_action('admin_enqueue_scripts', 'yuniorrojas_ingresos_admin_assets');

/**
 * Render de la página.
 */
function yuniorrojas_ingresos_render_page(): void
{
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('No tienes permiso para ver esta página.', YUNIORROJAS_TEXT_DOMAIN));
    }

    $filtros = yuniorrojas_ingresos_filtros_desde_request();
    $data    = yuniorrojas_ingresos_agregar($filtros);
    $kpis    = $data['kpis'];

    $servicios = yuniorrojas_reserva_admin_opciones_servicios();
    $barberos  = yuniorrojas_reserva_admin_opciones_barberos();

    $base_url = admin_url('admin.php?page=yuniorrojas-ingresos');

    $chart_payload = array(
        'serie'      => $data['serie'],
        'metodos'    => $data['metodos'],
        'barberos'   => $data['barberos'],
        'servicios'  => $data['servicios'],
        'i18n'       => array(
            'ingresos'  => __('Ingresos', YUNIORROJAS_TEXT_DOMAIN),
            'metodos'   => __('Métodos de pago', YUNIORROJAS_TEXT_DOMAIN),
            'barberos'  => __('Por barbero', YUNIORROJAS_TEXT_DOMAIN),
            'servicios' => __('Por servicio', YUNIORROJAS_TEXT_DOMAIN),
            'vacio'     => __('Sin datos para este filtro.', YUNIORROJAS_TEXT_DOMAIN),
        ),
    );
    wp_localize_script('yuniorrojas-ingresos', 'yuniorrojasIngresos', $chart_payload);
    ?>
    <div class="wrap jr-ingresos">
        <h1><?php esc_html_e('Ingresos', YUNIORROJAS_TEXT_DOMAIN); ?></h1>
        <p class="jr-ingresos__intro">
            <?php
            if ($filtros['vista'] === 'completadas') {
                esc_html_e('Mostrando solo dinero ganado: citas completadas (las confirmadas ya pasadas se marcan como completadas).', YUNIORROJAS_TEXT_DOMAIN);
            } elseif ($filtros['vista'] === 'proyeccion') {
                esc_html_e('Mostrando proyección de citas pendientes/confirmadas (aún no cobradas como completadas).', YUNIORROJAS_TEXT_DOMAIN);
            } else {
                esc_html_e('Mostrando confirmadas + completadas del rango (incluye citas futuras). Usa esta vista para ver reservas de clientes.', YUNIORROJAS_TEXT_DOMAIN);
            }
            ?>
        </p>

        <form class="jr-ingresos__filters" method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>">
            <input type="hidden" name="page" value="yuniorrojas-ingresos">

            <label>
                <span><?php esc_html_e('Desde', YUNIORROJAS_TEXT_DOMAIN); ?></span>
                <input type="date" name="jr_desde" value="<?php echo esc_attr($filtros['desde']); ?>">
            </label>
            <label>
                <span><?php esc_html_e('Hasta', YUNIORROJAS_TEXT_DOMAIN); ?></span>
                <input type="date" name="jr_hasta" value="<?php echo esc_attr($filtros['hasta']); ?>">
            </label>
            <label>
                <span><?php esc_html_e('Vista', YUNIORROJAS_TEXT_DOMAIN); ?></span>
                <select name="jr_vista_ing">
                    <option value="todas" <?php selected($filtros['vista'], 'todas'); ?>><?php esc_html_e('Completadas + proyección', YUNIORROJAS_TEXT_DOMAIN); ?></option>
                    <option value="completadas" <?php selected($filtros['vista'], 'completadas'); ?>><?php esc_html_e('Completadas (ganado)', YUNIORROJAS_TEXT_DOMAIN); ?></option>
                    <option value="proyeccion" <?php selected($filtros['vista'], 'proyeccion'); ?>><?php esc_html_e('Proyección', YUNIORROJAS_TEXT_DOMAIN); ?></option>
                </select>
            </label>
            <label>
                <span><?php esc_html_e('Método', YUNIORROJAS_TEXT_DOMAIN); ?></span>
                <select name="jr_metodo">
                    <option value=""><?php esc_html_e('Todos', YUNIORROJAS_TEXT_DOMAIN); ?></option>
                    <option value="estudio" <?php selected($filtros['metodo'], 'estudio'); ?>><?php echo esc_html(yuniorrojas_reserva_metodo_pago_label('estudio')); ?></option>
                    <option value="tarjeta" <?php selected($filtros['metodo'], 'tarjeta'); ?>><?php echo esc_html(yuniorrojas_reserva_metodo_pago_label('tarjeta')); ?></option>
                    <option value="plin" <?php selected($filtros['metodo'], 'plin'); ?>><?php echo esc_html(yuniorrojas_reserva_metodo_pago_label('plin')); ?></option>
                </select>
            </label>
            <label>
                <span><?php esc_html_e('Barbero', YUNIORROJAS_TEXT_DOMAIN); ?></span>
                <select name="jr_barbero">
                    <option value="0"><?php esc_html_e('Todos', YUNIORROJAS_TEXT_DOMAIN); ?></option>
                    <?php foreach ($barberos as $bid => $blabel) : ?>
                        <option value="<?php echo esc_attr((string) $bid); ?>" <?php selected($filtros['barbero_id'], (int) $bid); ?>>
                            <?php echo esc_html($blabel); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span><?php esc_html_e('Servicio', YUNIORROJAS_TEXT_DOMAIN); ?></span>
                <select name="jr_servicio">
                    <option value="0"><?php esc_html_e('Todos', YUNIORROJAS_TEXT_DOMAIN); ?></option>
                    <?php foreach ($servicios as $sid => $info) : ?>
                        <?php $nombre = is_array($info) ? (string) ($info['nombre'] ?? '') : (string) $info; ?>
                        <option value="<?php echo esc_attr((string) $sid); ?>" <?php selected($filtros['servicio_id'], (int) $sid); ?>>
                            <?php echo esc_html($nombre); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <button type="submit" class="button button-primary"><?php esc_html_e('Filtrar', YUNIORROJAS_TEXT_DOMAIN); ?></button>
            <a class="button" href="<?php echo esc_url($base_url); ?>"><?php esc_html_e('Limpiar', YUNIORROJAS_TEXT_DOMAIN); ?></a>
            <?php
            $export_url = add_query_arg(
                array(
                    'action'        => 'jr_ingresos_csv',
                    'jr_desde'      => $filtros['desde'],
                    'jr_hasta'      => $filtros['hasta'],
                    'jr_vista_ing'  => $filtros['vista'],
                    'jr_metodo'     => $filtros['metodo'],
                    'jr_barbero'    => $filtros['barbero_id'],
                    'jr_servicio'   => $filtros['servicio_id'],
                    '_wpnonce'      => wp_create_nonce('jr_ingresos_csv'),
                ),
                admin_url('admin-post.php')
            );
            ?>
            <a class="button" href="<?php echo esc_url($export_url); ?>"><?php esc_html_e('Exportar CSV', YUNIORROJAS_TEXT_DOMAIN); ?></a>
        </form>

        <div class="jr-ingresos__kpis">
            <div class="jr-ingresos__kpi">
                <span class="jr-ingresos__kpi-label"><?php esc_html_e('Total', YUNIORROJAS_TEXT_DOMAIN); ?></span>
                <strong class="jr-ingresos__kpi-value"><?php echo esc_html(yuniorrojas_ingresos_formato_soles((float) $kpis['total'])); ?></strong>
            </div>
            <div class="jr-ingresos__kpi">
                <span class="jr-ingresos__kpi-label"><?php esc_html_e('Citas', YUNIORROJAS_TEXT_DOMAIN); ?></span>
                <strong class="jr-ingresos__kpi-value"><?php echo esc_html((string) (int) $kpis['citas']); ?></strong>
            </div>
            <div class="jr-ingresos__kpi">
                <span class="jr-ingresos__kpi-label"><?php esc_html_e('Ticket promedio', YUNIORROJAS_TEXT_DOMAIN); ?></span>
                <strong class="jr-ingresos__kpi-value"><?php echo esc_html(yuniorrojas_ingresos_formato_soles((float) $kpis['ticket'])); ?></strong>
            </div>
            <div class="jr-ingresos__kpi jr-ingresos__kpi--metodos">
                <span class="jr-ingresos__kpi-label"><?php esc_html_e('Por método', YUNIORROJAS_TEXT_DOMAIN); ?></span>
                <ul>
                    <?php if ($kpis['por_metodo'] === array()) : ?>
                        <li>—</li>
                    <?php else : ?>
                        <?php foreach ($kpis['por_metodo'] as $mkey => $mval) : ?>
                            <li>
                                <?php echo esc_html(yuniorrojas_reserva_metodo_pago_label((string) $mkey)); ?>:
                                <strong><?php echo esc_html(yuniorrojas_ingresos_formato_soles((float) $mval)); ?></strong>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <div class="jr-ingresos__charts" data-jr-ingresos-charts>
            <div class="jr-ingresos__chart-card jr-ingresos__chart-card--wide">
                <h2>
                    <?php
                    echo esc_html(
                        $data['serie']['modo'] === 'mes'
                            ? __('Ingresos por mes', YUNIORROJAS_TEXT_DOMAIN)
                            : __('Ingresos por día', YUNIORROJAS_TEXT_DOMAIN)
                    );
                    ?>
                </h2>
                <div class="jr-ingresos__chart-box jr-ingresos__chart-box--serie">
                    <canvas id="jr-ingresos-serie"></canvas>
                </div>
            </div>
            <div class="jr-ingresos__chart-card">
                <h2><?php esc_html_e('Métodos de pago', YUNIORROJAS_TEXT_DOMAIN); ?></h2>
                <div class="jr-ingresos__chart-box jr-ingresos__chart-box--donut">
                    <canvas id="jr-ingresos-metodos"></canvas>
                </div>
            </div>
            <div class="jr-ingresos__chart-card">
                <h2><?php esc_html_e('Por barbero', YUNIORROJAS_TEXT_DOMAIN); ?></h2>
                <div class="jr-ingresos__chart-box">
                    <canvas id="jr-ingresos-barberos"></canvas>
                </div>
            </div>
            <div class="jr-ingresos__chart-card jr-ingresos__chart-card--wide">
                <h2><?php esc_html_e('Por servicio', YUNIORROJAS_TEXT_DOMAIN); ?></h2>
                <div class="jr-ingresos__chart-box jr-ingresos__chart-box--servicios">
                    <canvas id="jr-ingresos-servicios"></canvas>
                </div>
            </div>
        </div>

        <div class="jr-ingresos__table-wrap">
            <h2><?php esc_html_e('Detalle de reservas', YUNIORROJAS_TEXT_DOMAIN); ?></h2>
            <table class="wp-list-table widefat striped jr-ingresos__table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Fecha', YUNIORROJAS_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Cliente', YUNIORROJAS_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Servicio', YUNIORROJAS_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Barbero', YUNIORROJAS_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Método', YUNIORROJAS_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Estado', YUNIORROJAS_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Monto', YUNIORROJAS_TEXT_DOMAIN); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($data['filas'] === array()) : ?>
                        <tr>
                            <td colspan="7"><?php esc_html_e('No hay reservas para estos filtros.', YUNIORROJAS_TEXT_DOMAIN); ?></td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($data['filas'] as $fila) : ?>
                            <?php
                            $edit = get_edit_post_link((int) $fila['id'], 'raw');
                            $fecha_label = (string) $fila['fecha'];
                            $dt = DateTime::createFromFormat('Y-m-d', $fecha_label);
                            if ($dt instanceof DateTime) {
                                $fecha_label = $dt->format('d/m/Y');
                            }
                            if ((string) $fila['hora'] !== '') {
                                $fecha_label .= ' · ' . (string) $fila['hora'];
                            }
                            ?>
                            <tr>
                                <td>
                                    <?php if (is_string($edit) && $edit !== '') : ?>
                                        <a href="<?php echo esc_url($edit); ?>"><?php echo esc_html($fecha_label); ?></a>
                                    <?php else : ?>
                                        <?php echo esc_html($fecha_label); ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html((string) $fila['cliente']); ?></td>
                                <td><?php echo esc_html((string) $fila['servicio']); ?></td>
                                <td><?php echo esc_html((string) $fila['barbero']); ?></td>
                                <td><?php echo esc_html(yuniorrojas_reserva_metodo_pago_label((string) $fila['metodo'])); ?></td>
                                <td><?php echo esc_html(yuniorrojas_reserva_estado_label((string) $fila['estado'])); ?></td>
                                <td><strong><?php echo esc_html(yuniorrojas_ingresos_formato_soles((float) $fila['monto'])); ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}

/**
 * Export CSV de ingresos según filtros actuales.
 */
function yuniorrojas_ingresos_export_csv(): void
{
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Sin permiso.', YUNIORROJAS_TEXT_DOMAIN));
    }
    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash((string) $_GET['_wpnonce'])), 'jr_ingresos_csv')) {
        wp_die(esc_html__('Enlace no válido.', YUNIORROJAS_TEXT_DOMAIN));
    }

    $filtros = yuniorrojas_ingresos_filtros_desde_request();
    $data    = yuniorrojas_ingresos_agregar($filtros);
    $filas   = $data['filas'] ?? array();

    $filename = 'ingresos-' . $filtros['desde'] . '_' . $filtros['hasta'] . '.csv';

    nocache_headers();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);

    $out = fopen('php://output', 'w');
    if ($out === false) {
        exit;
    }

    // BOM UTF-8 para Excel.
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, array('Fecha', 'Hora', 'Cliente', 'Servicio', 'Barbero', 'Método', 'Estado', 'Monto'), ';');

    foreach ($filas as $fila) {
        if (!is_array($fila)) {
            continue;
        }
        fputcsv(
            $out,
            array(
                (string) ($fila['fecha'] ?? ''),
                (string) ($fila['hora'] ?? ''),
                (string) ($fila['cliente'] ?? ''),
                (string) ($fila['servicio'] ?? ''),
                (string) ($fila['barbero'] ?? ''),
                yuniorrojas_reserva_metodo_pago_label((string) ($fila['metodo'] ?? '')),
                yuniorrojas_reserva_estado_label((string) ($fila['estado'] ?? '')),
                number_format((float) ($fila['monto'] ?? 0), 2, '.', ''),
            ),
            ';'
        );
    }

    fclose($out);
    exit;
}
add_action('admin_post_jr_ingresos_csv', 'yuniorrojas_ingresos_export_csv');

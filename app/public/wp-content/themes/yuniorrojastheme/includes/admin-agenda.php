<?php
/**
 * Agenda calendario — vistas día / semana (estilo dashboard).
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Reservas de un día (opcional filtro barbero).
 *
 * @return list<array<string, mixed>>
 */
function yuniorrojas_agenda_reservas_dia(string $fecha, int $barbero_id = 0): array
{
    return yuniorrojas_agenda_reservas_rango($fecha, $fecha, $barbero_id);
}

/**
 * Reservas en rango de fechas inclusive.
 *
 * @return list<array<string, mixed>>
 */
function yuniorrojas_agenda_reservas_rango(string $desde, string $hasta, int $barbero_id = 0): array
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) {
        return array();
    }
    if ($desde > $hasta) {
        $tmp   = $desde;
        $desde = $hasta;
        $hasta = $tmp;
    }

    $meta = array(
        'relation' => 'AND',
        array(
            'key'     => yuniorrojas_reserva_meta_key('fecha'),
            'value'   => array($desde, $hasta),
            'compare' => 'BETWEEN',
            'type'    => 'CHAR',
        ),
        array(
            'key'     => yuniorrojas_reserva_meta_key('estado'),
            'value'   => array('cancelada', 'no_show'),
            'compare' => 'NOT IN',
        ),
    );

    if ($barbero_id > 0) {
        $meta[] = array(
            'key'   => yuniorrojas_reserva_meta_key('barbero_id'),
            'value' => (string) $barbero_id,
        );
    }

    $ids = get_posts(array(
        'post_type'      => YUNIORROJAS_CPT_RESERVAS,
        'post_status'    => 'publish',
        'posts_per_page' => 500,
        'fields'         => 'ids',
        'meta_query'     => $meta,
        'no_found_rows'  => true,
    ));

    $items = array();
    foreach ($ids as $id) {
        $r = yuniorrojas_obtener_reserva((int) $id);
        if ($r !== null) {
            $items[] = $r;
        }
    }

    usort(
        $items,
        static function (array $a, array $b): int {
            $fa = (string) ($a['fecha'] ?? '');
            $fb = (string) ($b['fecha'] ?? '');
            if ($fa !== $fb) {
                return strcmp($fa, $fb);
            }
            return strcmp((string) ($a['hora'] ?? ''), (string) ($b['hora'] ?? ''));
        }
    );

    return $items;
}

/**
 * Lunes de la semana (Y-m-d) que contiene $fecha.
 */
function yuniorrojas_agenda_lunes_semana(string $fecha): string
{
    $ts = strtotime($fecha . ' 12:00:00');
    if ($ts === false) {
        return $fecha;
    }
    $dow = (int) gmdate('N', $ts); // 1=lun … 7=dom
    return gmdate('Y-m-d', $ts - (($dow - 1) * DAY_IN_SECONDS));
}

/**
 * Colores de bloque por barbero (paleta suave tipo dashboard).
 *
 * @return array{bg:string,border:string,text:string}
 */
function yuniorrojas_agenda_color_barbero(int $barbero_id): array
{
    $palette = array(
        array('bg' => '#e8f5ef', 'border' => '#3d8f6a', 'text' => '#1a3d2e'),
        array('bg' => '#e8f0fa', 'border' => '#3a6ea5', 'text' => '#1a2f4a'),
        array('bg' => '#faf3e0', 'border' => '#b08930', 'text' => '#3d3010'),
        array('bg' => '#f3eaf7', 'border' => '#7a5a96', 'text' => '#2e2040'),
        array('bg' => '#fdeceb', 'border' => '#c45c4a', 'text' => '#4a221c'),
        array('bg' => '#e9f7f8', 'border' => '#2a9d8f', 'text' => '#164038'),
    );

    $idx = abs($barbero_id) % count($palette);
    return $palette[$idx];
}

/**
 * Menú Agenda.
 */
function yuniorrojas_agenda_registrar_menu(): void
{
    add_menu_page(
        __('Agenda', YUNIORROJAS_TEXT_DOMAIN),
        __('Agenda', YUNIORROJAS_TEXT_DOMAIN),
        'edit_posts',
        'yuniorrojas-agenda',
        'yuniorrojas_agenda_render_page',
        'dashicons-calendar-alt',
        26
    );
}
add_action('admin_menu', 'yuniorrojas_agenda_registrar_menu');

/**
 * Assets agenda.
 */
function yuniorrojas_agenda_assets(string $hook): void
{
    if ($hook !== 'toplevel_page_yuniorrojas-agenda') {
        return;
    }
    $path = get_template_directory() . '/assets/admin/agenda.css';
    $uri  = get_template_directory_uri() . '/assets/admin/agenda.css';
    $js   = get_template_directory() . '/assets/admin/agenda.js';
    $jsu  = get_template_directory_uri() . '/assets/admin/agenda.js';

    wp_enqueue_style(
        'yuniorrojas-agenda',
        $uri,
        array(),
        file_exists($path) ? (string) filemtime($path) : '1.0.0'
    );
    wp_enqueue_script(
        'yuniorrojas-agenda',
        $jsu,
        array(),
        file_exists($js) ? (string) filemtime($js) : '1.0.0',
        true
    );
}
add_action('admin_enqueue_scripts', 'yuniorrojas_agenda_assets');

/**
 * Render de un evento en el grid.
 *
 * @param array<string, mixed> $item Reserva.
 * @param int                  $inicio_min Hora inicio vista (minutos).
 * @param float                $px_min     px por minuto.
 * @param string               $redirect   Redirect acciones.
 */
function yuniorrojas_agenda_render_evento(array $item, int $inicio_min, float $px_min, string $redirect): void
{
    $hora_m = function_exists('yuniorrojas_hhmm_a_minutos')
        ? yuniorrojas_hhmm_a_minutos((string) ($item['hora'] ?? ''))
        : -1;
    if ($hora_m < 0) {
        return;
    }

    $dur = max(20, (int) ($item['duracion'] ?? 60));
    $gap = 5; // aire entre bloques
    $top = max(0, ($hora_m - $inicio_min) * $px_min) + $gap;
    $h   = max(48, $dur * $px_min - ($gap * 2));

    $bid   = (int) ($item['barbero_id'] ?? 0);
    $color = yuniorrojas_agenda_color_barbero($bid);
    $estado = (string) ($item['estado'] ?? 'confirmada');
    $cliente = trim((string) ($item['cliente_nombres'] ?? '') . ' ' . (string) ($item['cliente_apellidos'] ?? ''));
    $edit  = get_edit_post_link((int) $item['id'], 'raw') ?: '#';

    $hora_ini = (string) ($item['hora_label'] ?? $item['hora'] ?? '');
    $hora_fin = '';
    if (function_exists('yuniorrojas_minutos_a_hhmm') && function_exists('yuniorrojas_formatear_hora_label')) {
        $hora_fin = yuniorrojas_formatear_hora_label(yuniorrojas_minutos_a_hhmm($hora_m + $dur));
    }
    $rango = $hora_ini . ($hora_fin !== '' ? ' – ' . $hora_fin : '');
    ?>
    <article
        class="jr-cal__event jr-cal__event--<?php echo esc_attr(sanitize_html_class($estado)); ?>"
        style="top:<?php echo esc_attr((string) round($top, 1)); ?>px;height:<?php echo esc_attr((string) round($h, 1)); ?>px;--jr-ev-bg:<?php echo esc_attr($color['bg']); ?>;--jr-ev-bd:<?php echo esc_attr($color['border']); ?>;--jr-ev-tx:<?php echo esc_attr($color['text']); ?>"
        title="<?php echo esc_attr($cliente . ' · ' . (string) ($item['servicio_nombre'] ?? '')); ?>"
    >
        <a class="jr-cal__event-link" href="<?php echo esc_url($edit); ?>">
            <span class="jr-cal__event-time"><?php echo esc_html($rango); ?></span>
            <span class="jr-cal__event-title"><?php echo esc_html($cliente !== '' ? $cliente : '—'); ?></span>
            <span class="jr-cal__event-meta"><?php echo esc_html((string) ($item['servicio_nombre'] ?? '')); ?></span>
            <?php if ($bid > 0) : ?>
                <span class="jr-cal__event-barbero"><?php echo esc_html((string) ($item['barbero_nombre'] ?? '')); ?></span>
            <?php endif; ?>
        </a>
        <div class="jr-cal__event-actions">
            <?php
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo yuniorrojas_admin_acciones_html((int) $item['id'], $item, $redirect);
            ?>
        </div>
    </article>
    <?php
}

/**
 * Página Agenda.
 */
function yuniorrojas_agenda_render_page(): void
{
    if (!current_user_can('edit_posts')) {
        return;
    }

    $hoy      = current_time('Y-m-d');
    $fecha    = isset($_GET['jr_fecha']) ? sanitize_text_field(wp_unslash((string) $_GET['jr_fecha'])) : $hoy; // phpcs:ignore
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        $fecha = $hoy;
    }
    $barbero_f = isset($_GET['jr_barbero']) ? absint($_GET['jr_barbero']) : 0; // phpcs:ignore
    $vista     = isset($_GET['jr_vista_cal']) ? sanitize_key(wp_unslash((string) $_GET['jr_vista_cal'])) : 'semana'; // phpcs:ignore
    if (!in_array($vista, array('dia', 'semana'), true)) {
        $vista = 'semana';
    }

    $barberos = function_exists('yuniorrojas_reserva_admin_opciones_barberos')
        ? yuniorrojas_reserva_admin_opciones_barberos()
        : array();

    // Config grid: 8:00–20:00 (filas más altas para leer citas cortas).
    $inicio_min = 8 * 60;
    $fin_min    = 20 * 60;
    $px_min     = 1.5; // 90px por hora
    $horas      = (int) (($fin_min - $inicio_min) / 60);
    $altura     = (int) (($fin_min - $inicio_min) * $px_min);

    $dias_es_corto = array(1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb', 7 => 'Dom');
    $meses_es = array(
        1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun',
        7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic',
    );

    if ($vista === 'semana') {
        $lunes  = yuniorrojas_agenda_lunes_semana($fecha);
        $domingo = gmdate('Y-m-d', strtotime($lunes . ' +6 days'));
        $columnas = array();
        for ($i = 0; $i < 7; $i++) {
            $d = gmdate('Y-m-d', strtotime($lunes . ' +' . $i . ' days'));
            $ts = strtotime($d . ' 12:00:00');
            $n = (int) gmdate('N', $ts ?: time());
            $columnas[] = array(
                'fecha'   => $d,
                'label'   => ($dias_es_corto[$n] ?? '') . ' ' . gmdate('j', $ts ?: time()),
                'sub'     => $meses_es[(int) gmdate('n', $ts ?: time())] ?? '',
                'es_hoy'  => $d === $hoy,
            );
        }
        $desde = $lunes;
        $hasta = $domingo;
        $prev  = gmdate('Y-m-d', strtotime($lunes . ' -7 days'));
        $next  = gmdate('Y-m-d', strtotime($lunes . ' +7 days'));
        $rango_label = gmdate('j', strtotime($lunes . ' 12:00:00')) . ' ' .
            ($meses_es[(int) gmdate('n', strtotime($lunes . ' 12:00:00'))] ?? '') .
            ' – ' . gmdate('j', strtotime($domingo . ' 12:00:00')) . ' ' .
            ($meses_es[(int) gmdate('n', strtotime($domingo . ' 12:00:00'))] ?? '') .
            ' ' . gmdate('Y', strtotime($domingo . ' 12:00:00'));
    } else {
        $ts = strtotime($fecha . ' 12:00:00') ?: time();
        $n  = (int) gmdate('N', $ts);
        $columnas = array(
            array(
                'fecha'  => $fecha,
                'label'  => ($dias_es_corto[$n] ?? '') . ' ' . gmdate('j', $ts),
                'sub'    => $meses_es[(int) gmdate('n', $ts)] ?? '',
                'es_hoy' => $fecha === $hoy,
            ),
        );
        $desde = $fecha;
        $hasta = $fecha;
        $prev  = gmdate('Y-m-d', strtotime($fecha . ' -1 day'));
        $next  = gmdate('Y-m-d', strtotime($fecha . ' +1 day'));
        $rango_label = ($dias_es_corto[$n] ?? '') . ' ' . gmdate('j', $ts) . ' ' .
            ($meses_es[(int) gmdate('n', $ts)] ?? '') . ' ' . gmdate('Y', $ts);
    }

    $reservas = yuniorrojas_agenda_reservas_rango($desde, $hasta, $barbero_f);

    // Index por fecha.
    $por_fecha = array();
    foreach ($columnas as $col) {
        $por_fecha[$col['fecha']] = array();
    }
    foreach ($reservas as $r) {
        $f = (string) ($r['fecha'] ?? '');
        if (!isset($por_fecha[$f])) {
            continue;
        }
        $por_fecha[$f][] = $r;
    }

    $base_args = array(
        'page' => 'yuniorrojas-agenda',
    );
    if ($barbero_f > 0) {
        $base_args['jr_barbero'] = $barbero_f;
    }

    $url_vista = static function (string $v) use ($base_args, $fecha): string {
        return add_query_arg(
            array_merge($base_args, array('jr_vista_cal' => $v, 'jr_fecha' => $fecha)),
            admin_url('admin.php')
        );
    };

    $redirect = add_query_arg(
        array_merge($base_args, array('jr_vista_cal' => $vista, 'jr_fecha' => $fecha)),
        admin_url('admin.php')
    );

    $ahora_m = -1;
    if ($hoy >= $desde && $hoy <= $hasta) {
        $ahora_m = function_exists('yuniorrojas_hhmm_a_minutos')
            ? yuniorrojas_hhmm_a_minutos(current_time('H:i'))
            : -1;
    }
    $now_line_top = ($ahora_m >= $inicio_min && $ahora_m <= $fin_min)
        ? ($ahora_m - $inicio_min) * $px_min
        : null;
    $now_label = current_time('g:i A');

    $mes_titulo = $meses_es[(int) gmdate('n', strtotime($fecha . ' 12:00:00'))] ?? '';
    $mes_titulo .= ' ' . gmdate('Y', strtotime($fecha . ' 12:00:00'));
    ?>
    <div class="wrap jr-cal" data-jr-cal>
        <header class="jr-cal__top">
            <div class="jr-cal__top-left">
                <h1><?php esc_html_e('Agenda', YUNIORROJAS_TEXT_DOMAIN); ?></h1>
                <p><?php esc_html_e('Calendario de citas del estudio por día o semana.', YUNIORROJAS_TEXT_DOMAIN); ?></p>
            </div>
            <div class="jr-cal__top-right">
                <a class="jr-cal__btn-new" href="<?php echo esc_url(admin_url('post-new.php?post_type=' . YUNIORROJAS_CPT_RESERVAS)); ?>">
                    + <?php esc_html_e('Nueva cita', YUNIORROJAS_TEXT_DOMAIN); ?>
                </a>
            </div>
        </header>

        <div class="jr-cal__toolbar">
            <form class="jr-cal__filters" method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>">
                <input type="hidden" name="page" value="yuniorrojas-agenda">
                <input type="hidden" name="jr_vista_cal" value="<?php echo esc_attr($vista); ?>">

                <div class="jr-cal__month">
                    <strong><?php echo esc_html($mes_titulo); ?></strong>
                    <a class="jr-cal__today" href="<?php echo esc_url(add_query_arg(array_merge($base_args, array('jr_vista_cal' => $vista, 'jr_fecha' => $hoy)), admin_url('admin.php'))); ?>">
                        <?php esc_html_e('Hoy', YUNIORROJAS_TEXT_DOMAIN); ?>
                    </a>
                </div>

                <div class="jr-cal__seg" role="tablist">
                    <a class="jr-cal__seg-btn<?php echo $vista === 'dia' ? ' is-active' : ''; ?>" href="<?php echo esc_url($url_vista('dia')); ?>">
                        <?php esc_html_e('Día', YUNIORROJAS_TEXT_DOMAIN); ?>
                    </a>
                    <a class="jr-cal__seg-btn<?php echo $vista === 'semana' ? ' is-active' : ''; ?>" href="<?php echo esc_url($url_vista('semana')); ?>">
                        <?php esc_html_e('Semana', YUNIORROJAS_TEXT_DOMAIN); ?>
                    </a>
                </div>

                <div class="jr-cal__nav">
                    <a class="jr-cal__nav-btn" href="<?php echo esc_url(add_query_arg(array_merge($base_args, array('jr_vista_cal' => $vista, 'jr_fecha' => $prev)), admin_url('admin.php'))); ?>" aria-label="<?php esc_attr_e('Anterior', YUNIORROJAS_TEXT_DOMAIN); ?>">&lsaquo;</a>
                    <span class="jr-cal__range"><?php echo esc_html($rango_label); ?></span>
                    <a class="jr-cal__nav-btn" href="<?php echo esc_url(add_query_arg(array_merge($base_args, array('jr_vista_cal' => $vista, 'jr_fecha' => $next)), admin_url('admin.php'))); ?>" aria-label="<?php esc_attr_e('Siguiente', YUNIORROJAS_TEXT_DOMAIN); ?>">&rsaquo;</a>
                </div>

                <label class="jr-cal__barbero-filter">
                    <span><?php esc_html_e('Barbero', YUNIORROJAS_TEXT_DOMAIN); ?></span>
                    <select name="jr_barbero" onchange="this.form.submit()">
                        <option value="0"><?php esc_html_e('Todos', YUNIORROJAS_TEXT_DOMAIN); ?></option>
                        <?php foreach ($barberos as $bid => $bnombre) : ?>
                            <option value="<?php echo esc_attr((string) $bid); ?>" <?php selected($barbero_f, (int) $bid); ?>>
                                <?php echo esc_html($bnombre); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <input type="hidden" name="jr_fecha" value="<?php echo esc_attr($fecha); ?>">
            </form>
        </div>

        <div class="jr-cal__stats">
            <span><strong><?php echo esc_html((string) count($reservas)); ?></strong> <?php esc_html_e('citas en el rango', YUNIORROJAS_TEXT_DOMAIN); ?></span>
        </div>

        <div class="jr-cal__board jr-cal__board--<?php echo esc_attr($vista); ?>">
            <div class="jr-cal__corner" aria-hidden="true"></div>
            <div class="jr-cal__head-row" style="--jr-cols: <?php echo esc_attr((string) count($columnas)); ?>">
                <?php foreach ($columnas as $col) : ?>
                    <div class="jr-cal__day-head<?php echo !empty($col['es_hoy']) ? ' is-today' : ''; ?>">
                        <span class="jr-cal__day-name"><?php echo esc_html((string) $col['label']); ?></span>
                        <span class="jr-cal__day-sub"><?php echo esc_html((string) $col['sub']); ?></span>
                        <span class="jr-cal__day-n"><?php echo esc_html((string) count($por_fecha[$col['fecha']] ?? array())); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="jr-cal__time-col" style="height:<?php echo esc_attr((string) $altura); ?>px">
                <?php for ($h = 0; $h < $horas; $h++) : ?>
                    <?php
                    $mins = $inicio_min + ($h * 60);
                    $top  = $h * 60 * $px_min;
                    $lab  = sprintf('%d:00', intdiv($mins, 60));
                    // 12h style optional for polish - keep simple 24h with AM feel
                    $hour12 = intdiv($mins, 60);
                    $ampm = $hour12 >= 12 ? 'PM' : 'AM';
                    $h12 = $hour12 % 12;
                    if ($h12 === 0) {
                        $h12 = 12;
                    }
                    $lab = $h12 . ' ' . $ampm;
                    ?>
                    <div class="jr-cal__tick" style="top:<?php echo esc_attr((string) $top); ?>px">
                        <?php echo esc_html($lab); ?>
                    </div>
                <?php endfor; ?>
            </div>

            <div class="jr-cal__cols" style="height:<?php echo esc_attr((string) $altura); ?>px;--jr-cols:<?php echo esc_attr((string) count($columnas)); ?>">
                <?php foreach ($columnas as $col) : ?>
                    <div class="jr-cal__col<?php echo !empty($col['es_hoy']) ? ' is-today' : ''; ?>">
                        <?php for ($h = 0; $h < $horas; $h++) : ?>
                            <div class="jr-cal__slot" style="height:<?php echo esc_attr((string) (60 * $px_min)); ?>px"></div>
                        <?php endfor; ?>

                        <?php if ($now_line_top !== null && !empty($col['es_hoy'])) : ?>
                            <div class="jr-cal__now" style="top:<?php echo esc_attr((string) round($now_line_top, 1)); ?>px">
                                <span><?php echo esc_html($now_label); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php foreach ($por_fecha[$col['fecha']] ?? array() as $item) : ?>
                            <?php yuniorrojas_agenda_render_evento($item, $inicio_min, $px_min, $redirect); ?>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($barberos !== array()) : ?>
            <div class="jr-cal__legend">
                <span class="jr-cal__legend-title"><?php esc_html_e('Barberos', YUNIORROJAS_TEXT_DOMAIN); ?></span>
                <?php foreach ($barberos as $bid => $nombre) : ?>
                    <?php $c = yuniorrojas_agenda_color_barbero((int) $bid); ?>
                    <span class="jr-cal__legend-item" style="--jr-ev-bd:<?php echo esc_attr($c['border']); ?>;--jr-ev-bg:<?php echo esc_attr($c['bg']); ?>">
                        <?php echo esc_html($nombre); ?>
                    </span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

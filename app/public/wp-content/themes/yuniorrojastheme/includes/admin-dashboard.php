<?php
/**
 * Escritorio admin orientado a operación de barbería.
 * Quita widgets de blog/noticias WP y muestra agenda, pagos y atajos.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * ¿Usuario de equipo (ve el panel de operación)?
 */
function yuniorrojas_dashboard_puede_ver(): bool
{
    return current_user_can('edit_posts');
}

/**
 * Quita widgets por defecto (y de plugins) que no aportan a la operación.
 */
function yuniorrojas_dashboard_quitar_widgets(): void
{
    if (!yuniorrojas_dashboard_puede_ver()) {
        return;
    }

    $boxes = array(
        'dashboard_right_now',
        'dashboard_activity',
        'dashboard_quick_press',
        'dashboard_recent_drafts',
        'dashboard_recent_comments',
        'dashboard_incoming_links',
        'dashboard_plugins',
        'dashboard_primary',
        'dashboard_secondary',
        'dashboard_site_health',
        'dashboard_php_nag',
        'dashboard_browser_nag',
        // Plugins frecuentes en installs Local.
        'wp_mail_smtp_reports_widget_lite',
        'ai1wm_activity_log_widget',
        'rg_forms_dashboard',
        'wordfence_activity_report_widget',
        'jetpack_summary_widget',
        'tribe_dashboard_widget',
    );

    foreach ($boxes as $id) {
        remove_meta_box($id, 'dashboard', 'normal');
        remove_meta_box($id, 'dashboard', 'side');
        remove_meta_box($id, 'dashboard', 'column3');
        remove_meta_box($id, 'dashboard', 'column4');
    }

    remove_action('welcome_panel', 'wp_welcome_panel');
}
add_action('wp_dashboard_setup', 'yuniorrojas_dashboard_quitar_widgets', 20);

/**
 * Widget principal del Escritorio.
 */
function yuniorrojas_dashboard_registrar_widget(): void
{
    if (!yuniorrojas_dashboard_puede_ver()) {
        return;
    }

    wp_add_dashboard_widget(
        'yuniorrojas_ops_dashboard',
        __('Barbería Junior Rojas', YUNIORROJAS_TEXT_DOMAIN),
        'yuniorrojas_dashboard_render_widget',
        null,
        null,
        'normal',
        'high'
    );

    // Primera posición en la columna principal.
    global $wp_meta_boxes;
    if (!isset($wp_meta_boxes['dashboard']['normal']['core']['yuniorrojas_ops_dashboard'])) {
        return;
    }
    $widget = $wp_meta_boxes['dashboard']['normal']['core']['yuniorrojas_ops_dashboard'];
    unset($wp_meta_boxes['dashboard']['normal']['core']['yuniorrojas_ops_dashboard']);
    if (!isset($wp_meta_boxes['dashboard']['normal']['high']) || !is_array($wp_meta_boxes['dashboard']['normal']['high'])) {
        $wp_meta_boxes['dashboard']['normal']['high'] = array();
    }
    $wp_meta_boxes['dashboard']['normal']['high'] = array_merge(
        array('yuniorrojas_ops_dashboard' => $widget),
        $wp_meta_boxes['dashboard']['normal']['high']
    );
}
add_action('wp_dashboard_setup', 'yuniorrojas_dashboard_registrar_widget', 30);

/**
 * CSS del panel Escritorio: depende del dark theme para orden correcto en CSS cascade.
 */
function yuniorrojas_dashboard_assets(string $hook): void
{
    if ($hook !== 'index.php' || !yuniorrojas_dashboard_puede_ver()) {
        return;
    }

    $path = get_template_directory() . '/assets/admin/dashboard.css';
    $uri  = get_template_directory_uri() . '/assets/admin/dashboard.css';

    // Si dark está enqueued, cargar dashboard ANTES y dark después (via priority 999).
    $deps = array();
    wp_enqueue_style(
        'yuniorrojas-dashboard',
        $uri,
        $deps,
        file_exists($path) ? (string) filemtime($path) : '1.0.0'
    );

    // Re-encolar dark al final si modo oscuro.
    if (function_exists('yuniorrojas_admin_theme_is_dark') && yuniorrojas_admin_theme_is_dark()) {
        $dark_path = get_template_directory() . '/assets/admin/admin-theme-dark.css';
        $dark_uri  = get_template_directory_uri() . '/assets/admin/admin-theme-dark.css';
        wp_enqueue_style(
            'yuniorrojas-admin-theme-dark',
            $dark_uri,
            array('yuniorrojas-dashboard'),
            file_exists($dark_path) ? (string) filemtime($dark_path) : '1.0.0'
        );
    }
}
add_action('admin_enqueue_scripts', 'yuniorrojas_dashboard_assets');

/**
 * Datos operativos para el widget.
 *
 * @return array{
 *   hoy: string,
 *   semana_desde: string,
 *   semana_hasta: string,
 *   citas_hoy: list<array<string, mixed>>,
 *   n_pendiente: int,
 *   n_confirmada: int,
 *   n_otras_hoy: int,
 *   n_hoy: int,
 *   pagos: list<array<string, mixed>>,
 *   n_pagos: int,
 *   n_semana: int,
 *   ingresos_semana: float,
 *   culqi: array{estado: string, label: string, class: string, url: string}
 * }
 */
function yuniorrojas_dashboard_datos(): array
{
    $hoy = current_time('Y-m-d');

    if (function_exists('yuniorrojas_agenda_lunes_semana')) {
        $semana_desde = yuniorrojas_agenda_lunes_semana($hoy);
    } else {
        $semana_desde = $hoy;
    }

    $ts_desde = strtotime($semana_desde . ' 12:00:00');
    $semana_hasta = $ts_desde
        ? gmdate('Y-m-d', $ts_desde + (6 * DAY_IN_SECONDS))
        : $hoy;

    $citas_hoy = function_exists('yuniorrojas_agenda_reservas_dia')
        ? yuniorrojas_agenda_reservas_dia($hoy)
        : array();

    $n_pendiente  = 0;
    $n_confirmada = 0;
    $n_otras      = 0;
    foreach ($citas_hoy as $r) {
        $estado = sanitize_key((string) ($r['estado'] ?? 'confirmada'));
        if ($estado === 'pendiente') {
            $n_pendiente++;
        } elseif ($estado === 'confirmada') {
            $n_confirmada++;
        } else {
            $n_otras++;
        }
    }

    $pagos = function_exists('yuniorrojas_pagos_pendientes')
        ? yuniorrojas_pagos_pendientes()
        : array();

    $semana_items = function_exists('yuniorrojas_agenda_reservas_rango')
        ? yuniorrojas_agenda_reservas_rango($semana_desde, $semana_hasta)
        : array();

    $ingresos_semana = 0.0;
    if (function_exists('yuniorrojas_ingresos_agregar')) {
        $agg = yuniorrojas_ingresos_agregar(array(
            'desde' => $semana_desde,
            'hasta' => $hoy,
            'vista' => 'todas',
        ));
        $ingresos_semana = (float) ($agg['kpis']['total'] ?? 0);
    }

    $culqi_url = admin_url('edit.php?post_type=' . YUNIORROJAS_CPT_RESERVAS . '&page=yuniorrojas-pagos-settings');
    if (!function_exists('yuniorrojas_culqi_esta_configurado') || !yuniorrojas_culqi_esta_configurado()) {
        $culqi = array(
            'estado' => 'none',
            'label'  => __('Culqi no configurado', YUNIORROJAS_TEXT_DOMAIN),
            'class'  => 'is-warn',
            'url'    => $culqi_url,
        );
    } elseif (function_exists('yuniorrojas_culqi_es_test') && yuniorrojas_culqi_es_test()) {
        $culqi = array(
            'estado' => 'test',
            'label'  => __('Culqi en modo TEST', YUNIORROJAS_TEXT_DOMAIN),
            'class'  => 'is-warn',
            'url'    => $culqi_url,
        );
    } else {
        $culqi = array(
            'estado' => 'live',
            'label'  => __('Culqi LIVE listo', YUNIORROJAS_TEXT_DOMAIN),
            'class'  => 'is-ok',
            'url'    => $culqi_url,
        );
    }

    return array(
        'hoy'             => $hoy,
        'semana_desde'    => $semana_desde,
        'semana_hasta'    => $semana_hasta,
        'citas_hoy'       => $citas_hoy,
        'n_pendiente'     => $n_pendiente,
        'n_confirmada'    => $n_confirmada,
        'n_otras_hoy'     => $n_otras,
        'n_hoy'           => count($citas_hoy),
        'pagos'           => $pagos,
        'n_pagos'         => count($pagos),
        'n_semana'        => count($semana_items),
        'ingresos_semana' => $ingresos_semana,
        'culqi'           => $culqi,
    );
}

/**
 * Nombre de cliente legible.
 *
 * @param array<string, mixed> $reserva
 */
function yuniorrojas_dashboard_cliente_nombre(array $reserva): string
{
    $nombre = trim(
        (string) ($reserva['cliente_nombres'] ?? '') . ' ' . (string) ($reserva['cliente_apellidos'] ?? '')
    );
    if ($nombre !== '') {
        return $nombre;
    }
    $email = (string) ($reserva['cliente_email'] ?? '');
    return $email !== '' ? $email : '—';
}

/**
 * Render del widget.
 */
function yuniorrojas_dashboard_render_widget(): void
{
    if (!yuniorrojas_dashboard_puede_ver()) {
        return;
    }

    $d      = yuniorrojas_dashboard_datos();
    $hoy_fmt = wp_date('l j \d\e F Y', strtotime($d['hoy'] . ' 12:00:00'));
    $culqi  = $d['culqi'];

    $url_agenda   = admin_url('admin.php?page=yuniorrojas-agenda');
    $url_reservas = admin_url('edit.php?post_type=' . YUNIORROJAS_CPT_RESERVAS);
    $url_ingresos = admin_url('admin.php?page=yuniorrojas-ingresos');
    $url_clientes = admin_url('admin.php?page=yuniorrojas-clientes');
    $url_servicios = admin_url('edit.php?post_type=' . YUNIORROJAS_CPT_SERVICIOS);
    $url_pagos    = admin_url('admin.php?page=yuniorrojas-pagos');

    $monto_fmt = function_exists('yuniorrojas_ingresos_formato_soles')
        ? yuniorrojas_ingresos_formato_soles((float) $d['ingresos_semana'])
        : 'S/. ' . number_format((float) $d['ingresos_semana'], 2, '.', ',');

    $max_citas = 8;
    $max_pagos = 5;
    ?>
    <div class="jr-dash">
        <p class="jr-dash__intro">
            <?php
            echo esc_html(
                sprintf(
                    /* translators: %s: formatted date */
                    __('Operación · %s', YUNIORROJAS_TEXT_DOMAIN),
                    $hoy_fmt
                )
            );
            ?>
        </p>

        <div class="jr-dash__health" role="status">
            <p>
                <?php
                echo esc_html(
                    sprintf(
                        /* translators: 1: week appointments count, 2: amount formatted */
                        __('Esta semana: %1$d reservas · ingresos (lun–hoy) %2$s', YUNIORROJAS_TEXT_DOMAIN),
                        (int) $d['n_semana'],
                        $monto_fmt
                    )
                );
                ?>
            </p>
        </div>

        <?php if (current_user_can('manage_options')) : ?>
            <div class="jr-dash__banner jr-dash__banner--<?php echo esc_attr($culqi['class']); ?>">
                <strong><?php echo esc_html($culqi['label']); ?></strong>
                <?php if ($culqi['estado'] === 'test') : ?>
                    <span><?php esc_html_e('En dominio público usa llaves pk_live_ / sk_live_ antes de cobrar de verdad.', YUNIORROJAS_TEXT_DOMAIN); ?></span>
                <?php elseif ($culqi['estado'] === 'none') : ?>
                    <span><?php esc_html_e('Configura las llaves para aceptar tarjeta o Yape online.', YUNIORROJAS_TEXT_DOMAIN); ?></span>
                <?php else : ?>
                    <span><?php esc_html_e('Cobros online en producción.', YUNIORROJAS_TEXT_DOMAIN); ?></span>
                <?php endif; ?>
                <a class="button button-small" href="<?php echo esc_url($culqi['url']); ?>">
                    <?php esc_html_e('Ajustes Culqi', YUNIORROJAS_TEXT_DOMAIN); ?>
                </a>
            </div>
        <?php endif; ?>

        <nav class="jr-dash__shortcuts" aria-label="<?php esc_attr_e('Atajos de operación', YUNIORROJAS_TEXT_DOMAIN); ?>">
            <a class="jr-dash__chip" href="<?php echo esc_url($url_agenda); ?>"><?php esc_html_e('Agenda', YUNIORROJAS_TEXT_DOMAIN); ?></a>
            <a class="jr-dash__chip" href="<?php echo esc_url($url_reservas); ?>"><?php esc_html_e('Reservas', YUNIORROJAS_TEXT_DOMAIN); ?></a>
            <a class="jr-dash__chip" href="<?php echo esc_url($url_ingresos); ?>"><?php esc_html_e('Ingresos', YUNIORROJAS_TEXT_DOMAIN); ?></a>
            <a class="jr-dash__chip" href="<?php echo esc_url($url_clientes); ?>"><?php esc_html_e('Clientes', YUNIORROJAS_TEXT_DOMAIN); ?></a>
            <a class="jr-dash__chip" href="<?php echo esc_url($url_servicios); ?>"><?php esc_html_e('Servicios', YUNIORROJAS_TEXT_DOMAIN); ?></a>
            <a class="jr-dash__chip<?php echo $d['n_pagos'] > 0 ? ' is-alert' : ''; ?>" href="<?php echo esc_url($url_pagos); ?>">
                <?php
                echo esc_html(
                    $d['n_pagos'] > 0
                        ? sprintf(
                            /* translators: %d: pending payments */
                            __('Pagos (%d)', YUNIORROJAS_TEXT_DOMAIN),
                            (int) $d['n_pagos']
                        )
                        : __('Pagos', YUNIORROJAS_TEXT_DOMAIN)
                );
                ?>
            </a>
        </nav>

        <div class="jr-dash__grid">
            <section class="jr-dash__panel" aria-labelledby="jr-dash-hoy-title">
                <header class="jr-dash__panel-head">
                    <h3 id="jr-dash-hoy-title"><?php esc_html_e('Hoy', YUNIORROJAS_TEXT_DOMAIN); ?></h3>
                    <p class="jr-dash__counts">
                        <span class="jr-dash__pill jr-dash__pill--ok">
                            <?php
                            echo esc_html(
                                sprintf(
                                    /* translators: %d: confirmed count */
                                    __('%d confirmadas', YUNIORROJAS_TEXT_DOMAIN),
                                    (int) $d['n_confirmada']
                                )
                            );
                            ?>
                        </span>
                        <span class="jr-dash__pill jr-dash__pill--warn">
                            <?php
                            echo esc_html(
                                sprintf(
                                    /* translators: %d: pending count */
                                    __('%d pendientes', YUNIORROJAS_TEXT_DOMAIN),
                                    (int) $d['n_pendiente']
                                )
                            );
                            ?>
                        </span>
                        <span class="jr-dash__muted">
                            <?php
                            echo esc_html(
                                sprintf(
                                    /* translators: %d: total today */
                                    __('%d en total', YUNIORROJAS_TEXT_DOMAIN),
                                    (int) $d['n_hoy']
                                )
                            );
                            ?>
                        </span>
                    </p>
                </header>

                <?php if ($d['citas_hoy'] === array()) : ?>
                    <p class="jr-dash__empty"><?php esc_html_e('No hay citas para hoy.', YUNIORROJAS_TEXT_DOMAIN); ?></p>
                <?php else : ?>
                    <table class="jr-dash__table widefat striped">
                        <thead>
                            <tr>
                                <th scope="col"><?php esc_html_e('Hora', YUNIORROJAS_TEXT_DOMAIN); ?></th>
                                <th scope="col"><?php esc_html_e('Cliente', YUNIORROJAS_TEXT_DOMAIN); ?></th>
                                <th scope="col"><?php esc_html_e('Servicio', YUNIORROJAS_TEXT_DOMAIN); ?></th>
                                <th scope="col"><?php esc_html_e('Estado', YUNIORROJAS_TEXT_DOMAIN); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $i = 0;
                            foreach ($d['citas_hoy'] as $r) :
                                if ($i >= $max_citas) {
                                    break;
                                }
                                $i++;
                                $id    = (int) ($r['id'] ?? 0);
                                $hora  = (string) ($r['hora_label'] ?? $r['hora'] ?? '—');
                                $serv  = (string) ($r['servicio_nombre'] ?? '—');
                                $est   = sanitize_key((string) ($r['estado'] ?? 'confirmada'));
                                $label = function_exists('yuniorrojas_reserva_estado_label')
                                    ? yuniorrojas_reserva_estado_label($est)
                                    : $est;
                                $edit  = $id > 0 ? get_edit_post_link($id, 'raw') : '';
                                ?>
                                <tr>
                                    <td><strong><?php echo esc_html($hora); ?></strong></td>
                                    <td>
                                        <?php if (is_string($edit) && $edit !== '') : ?>
                                            <a href="<?php echo esc_url($edit); ?>"><?php echo esc_html(yuniorrojas_dashboard_cliente_nombre($r)); ?></a>
                                        <?php else : ?>
                                            <?php echo esc_html(yuniorrojas_dashboard_cliente_nombre($r)); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo esc_html($serv !== '' ? $serv : '—'); ?></td>
                                    <td>
                                        <span class="jr-dash__estado jr-dash__estado--<?php echo esc_attr($est); ?>">
                                            <?php echo esc_html($label); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if ((int) $d['n_hoy'] > $max_citas) : ?>
                        <p class="jr-dash__more">
                            <a href="<?php echo esc_url($url_agenda); ?>">
                                <?php esc_html_e('Ver toda la agenda de hoy →', YUNIORROJAS_TEXT_DOMAIN); ?>
                            </a>
                        </p>
                    <?php endif; ?>
                <?php endif; ?>
            </section>

            <section class="jr-dash__panel" aria-labelledby="jr-dash-pagos-title">
                <header class="jr-dash__panel-head">
                    <h3 id="jr-dash-pagos-title"><?php esc_html_e('Pagos por verificar', YUNIORROJAS_TEXT_DOMAIN); ?></h3>
                    <p class="jr-dash__muted">
                        <?php esc_html_e('Plin, Yape o transferencia pendientes de revisión manual.', YUNIORROJAS_TEXT_DOMAIN); ?>
                    </p>
                </header>

                <?php if ($d['pagos'] === array()) : ?>
                    <p class="jr-dash__empty jr-dash__empty--ok"><?php esc_html_e('Ningún pago pendiente de verificación.', YUNIORROJAS_TEXT_DOMAIN); ?></p>
                <?php else : ?>
                    <ul class="jr-dash__list">
                        <?php
                        $j = 0;
                        foreach ($d['pagos'] as $p) :
                            if ($j >= $max_pagos) {
                                break;
                            }
                            $j++;
                            $pid   = (int) ($p['id'] ?? 0);
                            $met   = function_exists('yuniorrojas_reserva_metodo_pago_label')
                                ? yuniorrojas_reserva_metodo_pago_label((string) ($p['metodo_pago'] ?? ''))
                                : (string) ($p['metodo_pago'] ?? '');
                            $fecha = (string) ($p['fecha'] ?? '');
                            $hora  = (string) ($p['hora_label'] ?? $p['hora'] ?? '');
                            $edit  = $pid > 0 ? get_edit_post_link($pid, 'raw') : '';
                            $cuándo = trim($fecha . ($hora !== '' ? ' · ' . $hora : ''));
                            ?>
                            <li>
                                <div>
                                    <strong><?php echo esc_html(yuniorrojas_dashboard_cliente_nombre($p)); ?></strong>
                                    <span class="jr-dash__muted">
                                        <?php echo esc_html($met !== '' ? $met : __('Pago manual', YUNIORROJAS_TEXT_DOMAIN)); ?>
                                        <?php if ($cuándo !== '') : ?>
                                            · <?php echo esc_html($cuándo); ?>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <?php if (is_string($edit) && $edit !== '') : ?>
                                    <a class="button button-small" href="<?php echo esc_url($edit); ?>"><?php esc_html_e('Revisar', YUNIORROJAS_TEXT_DOMAIN); ?></a>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if ((int) $d['n_pagos'] > $max_pagos) : ?>
                        <p class="jr-dash__more">
                            <a href="<?php echo esc_url($url_pagos); ?>">
                                <?php
                                echo esc_html(
                                    sprintf(
                                        /* translators: %d: pending total */
                                        __('Ver los %d pagos pendientes →', YUNIORROJAS_TEXT_DOMAIN),
                                        (int) $d['n_pagos']
                                    )
                                );
                                ?>
                            </a>
                        </p>
                    <?php else : ?>
                        <p class="jr-dash__more">
                            <a href="<?php echo esc_url($url_pagos); ?>"><?php esc_html_e('Cola de verificación →', YUNIORROJAS_TEXT_DOMAIN); ?></a>
                        </p>
                    <?php endif; ?>
                <?php endif; ?>
            </section>
        </div>
    </div>
    <?php
}

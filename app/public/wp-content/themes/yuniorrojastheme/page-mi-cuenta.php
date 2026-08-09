<?php
/**
 * Template Name: Mi Cuenta
 * Panel del cliente (datos reales desde reservas).
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!is_user_logged_in()) {
    wp_safe_redirect(yuniorrojas_url_login());
    exit;
}

if (!yuniorrojas_es_cliente()) {
    wp_safe_redirect(admin_url());
    exit;
}

$usuario       = wp_get_current_user();
$user_id       = (int) $usuario->ID;
$cuenta        = yuniorrojas_cuenta_datos_cliente($user_id);
$nombre        = trim((string) $usuario->display_name);
$nombre_corto  = $nombre !== '' ? $nombre : (string) $usuario->user_login;
$primer_nombre = explode(' ', $nombre_corto)[0] ?? $nombre_corto;
$nivel_label   = (string) ($cuenta['nivel']['label'] ?? 'Cliente Classic');
$avatar        = get_avatar_url($user_id, array('size' => 96));
$reservar_url  = yuniorrojas_url_reservar();
$logout_url    = wp_logout_url(yuniorrojas_url_servicios());
$logo_url      = yuniorrojas_logo_url();
$site_name     = (string) get_bloginfo('name');
$seccion       = isset($_GET['vista']) ? sanitize_key(wp_unslash((string) $_GET['vista'])) : 'dashboard';
$vistas_ok     = array('dashboard', 'citas', 'historial', 'preferencias');
if (!in_array($seccion, $vistas_ok, true)) {
    $seccion = 'dashboard';
}

$pref_notice   = '';
$pref_error    = '';

if (
    $seccion === 'preferencias'
    && isset($_SERVER['REQUEST_METHOD'])
    && strtoupper((string) $_SERVER['REQUEST_METHOD']) === 'POST'
    && isset($_POST['jr_pref_nonce'])
    && wp_verify_nonce(sanitize_text_field(wp_unslash((string) $_POST['jr_pref_nonce'])), 'jr_guardar_preferencias')
) {
    $result = yuniorrojas_actualizar_preferencias_cliente($user_id, array(
        'nombre'            => isset($_POST['nombre']) ? wp_unslash((string) $_POST['nombre']) : '',
        'email'             => isset($_POST['email']) ? wp_unslash((string) $_POST['email']) : '',
        'telefono'          => isset($_POST['telefono']) ? wp_unslash((string) $_POST['telefono']) : '',
        'notas_barbero'     => isset($_POST['notas_barbero']) ? wp_unslash((string) $_POST['notas_barbero']) : '',
        'estilo_id'         => isset($_POST['estilo_id']) ? wp_unslash((string) $_POST['estilo_id']) : '',
        'password_actual'   => isset($_POST['password_actual']) ? (string) $_POST['password_actual'] : '',
        'password_nueva'    => isset($_POST['password_nueva']) ? (string) $_POST['password_nueva'] : '',
        'password_confirm'  => isset($_POST['password_confirm']) ? (string) $_POST['password_confirm'] : '',
    ));

    if (is_wp_error($result)) {
        $pref_error = $result->get_error_message();
    } else {
        $pref_notice = 'Preferencias guardadas correctamente.';
        $usuario     = wp_get_current_user();
        $nombre      = trim((string) $usuario->display_name);
        $nombre_corto = $nombre !== '' ? $nombre : (string) $usuario->user_login;
        $cuenta      = yuniorrojas_cuenta_datos_cliente($user_id);
    }
}

$titulos_vista = array(
    'dashboard'    => 'Dashboard',
    'citas'        => 'Próximas citas',
    'historial'    => 'Historial de estilo',
    'preferencias' => 'Preferencias',
);
$titulo_activo = $titulos_vista[$seccion] ?? 'Mi Cuenta';

add_filter('pre_get_document_title', static function () use ($titulo_activo): string {
    $site = (string) get_bloginfo('name');
    return $site !== '' ? $titulo_activo . ' – ' . $site : $titulo_activo;
}, 20);

/**
 * @param array<string, mixed> $cita
 */
$render_cita = static function (array $cita) use ($reservar_url): void {
    $id          = (int) ($cita['id'] ?? 0);
    $servicio_id = (int) ($cita['servicio_id'] ?? 0);
    $barbero_id  = (int) ($cita['barbero_id'] ?? 0);
    $metodo      = sanitize_key((string) ($cita['metodo_pago'] ?? 'estudio'));
    if ($metodo === '') {
        $metodo = 'estudio';
    }
    $puede_cancelar = array_key_exists('puede_cancelar', $cita)
        ? (bool) $cita['puede_cancelar']
        : yuniorrojas_reserva_permite_cancelar_cliente($metodo);

    $reprog_args = array('paso' => 'cita');
    if ($servicio_id > 0) {
        $reprog_args['servicio'] = $servicio_id;
    }
    if ($barbero_id > 0) {
        $reprog_args['barbero'] = $barbero_id;
    }
    if ($id > 0) {
        $reprog_args['reprogramar'] = $id;
    }
    $reprog_url = yuniorrojas_url_reservar($reprog_args);
    ?>
    <article class="cliente-cita" data-cita-id="<?php echo esc_attr((string) $id); ?>">
        <div class="cliente-cita__fecha">
            <span class="cliente-cita__dia"><?php echo esc_html((string) ($cita['dia'] ?? '')); ?></span>
            <span class="cliente-cita__mes"><?php echo esc_html((string) ($cita['mes'] ?? '')); ?></span>
        </div>
        <div class="cliente-cita__body">
            <h3 class="cliente-cita__servicio"><?php echo esc_html((string) ($cita['servicio'] ?? '')); ?></h3>
            <ul class="cliente-cita__meta">
                <li>
                    <i class="ti ti-clock" aria-hidden="true"></i>
                    <?php echo esc_html((string) ($cita['hora'] ?? '')); ?>
                </li>
                <li>
                    <i class="ti ti-scissors" aria-hidden="true"></i>
                    <?php echo esc_html((string) ($cita['barbero'] ?? '')); ?>
                </li>
                <?php if (!empty($cita['estado_label'])) : ?>
                    <li>
                        <i class="ti ti-info-circle" aria-hidden="true"></i>
                        <span class="cliente-cita__estado cliente-cita__estado--<?php echo esc_attr(sanitize_html_class((string) ($cita['estado'] ?? ''))); ?>">
                            <?php echo esc_html((string) $cita['estado_label']); ?>
                        </span>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
        <div class="cliente-cita__actions">
            <?php if (!empty($cita['puede_reprogramar']) || !isset($cita['puede_reprogramar'])) : ?>
            <a class="cliente-cita__btn cliente-cita__btn--outline" href="<?php echo esc_url($reprog_url); ?>">
                Reprogramar
            </a>
            <?php endif; ?>
            <?php if ($id > 0) : ?>
                <button
                    type="button"
                    class="cliente-cita__btn<?php echo $puede_cancelar ? '' : ' is-disabled'; ?>"
                    data-cancelar-cita
                    data-reserva-id="<?php echo esc_attr((string) $id); ?>"
                    data-metodo-pago="<?php echo esc_attr($metodo); ?>"
                    data-puede-cancelar="<?php echo $puede_cancelar ? '1' : '0'; ?>"
                    data-servicio="<?php echo esc_attr((string) ($cita['servicio'] ?? '')); ?>"
                    data-fecha-label="<?php echo esc_attr(trim(($cita['dia'] ?? '') . ' ' . ($cita['mes'] ?? '') . ' · ' . ($cita['hora'] ?? ''))); ?>"
                    <?php echo $puede_cancelar ? '' : 'aria-disabled="true"'; ?>>
                    Cancelar
                </button>
            <?php endif; ?>
        </div>
    </article>
    <?php
};

get_header();
?>

<div class="cliente-dash" data-cliente-dash>
    <button type="button" class="cliente-dash__menu-btn" data-cliente-menu-toggle aria-expanded="false" aria-controls="cliente-sidebar">
        <i class="ti ti-menu-2" aria-hidden="true"></i>
        <span>Menú</span>
    </button>

    <div class="cliente-dash__overlay" data-cliente-menu-overlay hidden></div>

    <aside id="cliente-sidebar" class="cliente-dash__sidebar" data-cliente-sidebar data-open="false">
        <div class="cliente-dash__sidebar-top">
            <a class="cliente-dash__brand" href="<?php echo esc_url(home_url('/')); ?>">
                <img
                    src="<?php echo esc_url($logo_url); ?>"
                    alt="<?php echo esc_attr($site_name !== '' ? $site_name : 'Junior Rojas Barber Studio'); ?>"
                    width="240"
                    height="140"
                >
            </a>

            <div class="cliente-dash__profile">
                <img
                    class="cliente-dash__avatar"
                    src="<?php echo esc_url($avatar ?: get_template_directory_uri() . '/img/logo monograma.png'); ?>"
                    alt="<?php echo esc_attr($nombre_corto); ?>"
                    width="48"
                    height="48"
                >
                <div class="cliente-dash__profile-text">
                    <strong><?php echo esc_html($nombre_corto); ?></strong>
                    <span><?php echo esc_html($nivel_label); ?></span>
                </div>
            </div>

            <nav class="cliente-dash__nav" aria-label="Menú de cliente">
                <a
                    class="cliente-dash__nav-link<?php echo $seccion === 'dashboard' ? ' is-active' : ''; ?>"
                    href="<?php echo esc_url(add_query_arg('vista', 'dashboard', yuniorrojas_url_cuenta())); ?>">
                    <i class="ti ti-layout-dashboard" aria-hidden="true"></i>
                    Dashboard
                </a>
                <a
                    class="cliente-dash__nav-link<?php echo $seccion === 'citas' ? ' is-active' : ''; ?>"
                    href="<?php echo esc_url(add_query_arg('vista', 'citas', yuniorrojas_url_cuenta())); ?>">
                    <i class="ti ti-calendar-event" aria-hidden="true"></i>
                    Próximas citas
                </a>
                <a
                    class="cliente-dash__nav-link<?php echo $seccion === 'historial' ? ' is-active' : ''; ?>"
                    href="<?php echo esc_url(add_query_arg('vista', 'historial', yuniorrojas_url_cuenta())); ?>">
                    <i class="ti ti-history" aria-hidden="true"></i>
                    Historial
                </a>
                <a
                    class="cliente-dash__nav-link<?php echo $seccion === 'preferencias' ? ' is-active' : ''; ?>"
                    href="<?php echo esc_url(add_query_arg('vista', 'preferencias', yuniorrojas_url_cuenta())); ?>">
                    <i class="ti ti-scissors" aria-hidden="true"></i>
                    Preferencias
                </a>
            </nav>
        </div>

        <div class="cliente-dash__sidebar-footer">
            <a class="cliente-dash__nueva" href="<?php echo esc_url($reservar_url); ?>">
                Nueva reserva
            </a>
            <a class="cliente-dash__logout" href="<?php echo esc_url($logout_url); ?>">
                <i class="ti ti-logout" aria-hidden="true"></i>
                Cerrar sesión
            </a>
        </div>
    </aside>

    <main class="cliente-dash__main">
        <?php if ($seccion === 'dashboard') : ?>
            <header class="cliente-dash__welcome">
                <h1>Bienvenido, <?php echo esc_html($primer_nombre); ?>.</h1>
                <p>Tu próximo estilo te espera.</p>
            </header>

            <div class="cliente-dash__grid">
                <section class="cliente-dash__section" aria-labelledby="cliente-citas-title">
                    <div class="cliente-dash__section-head">
                        <h2 id="cliente-citas-title">Próximas citas</h2>
                        <a href="<?php echo esc_url(add_query_arg('vista', 'citas', yuniorrojas_url_cuenta())); ?>">Ver todas</a>
                    </div>

                    <?php if (empty($cuenta['proximas'])) : ?>
                        <p class="cliente-dash__empty-note">
                            Aún no tienes citas próximas.
                            <a href="<?php echo esc_url($reservar_url); ?>">Reservar ahora</a>
                        </p>
                    <?php else : ?>
                        <?php foreach (array_slice($cuenta['proximas'], 0, 3) as $cita) : ?>
                            <?php $render_cita($cita); ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </section>

                <aside class="cliente-nivel" aria-labelledby="cliente-nivel-title">
                    <div class="cliente-nivel__badge" aria-hidden="true">
                        <i class="ti ti-award"></i>
                    </div>
                    <h2 id="cliente-nivel-title" class="cliente-nivel__title"><?php echo esc_html((string) $cuenta['nivel']['nombre']); ?></h2>
                    <p class="cliente-nivel__desc"><?php echo esc_html((string) $cuenta['nivel']['descripcion']); ?></p>
                    <div
                        class="cliente-nivel__bar"
                        role="progressbar"
                        aria-valuemin="0"
                        aria-valuemax="100"
                        aria-valuenow="<?php echo esc_attr((string) $cuenta['nivel']['progreso']); ?>"
                        aria-label="Progreso hacia <?php echo esc_attr((string) $cuenta['nivel']['siguiente']); ?>">
                        <span style="width: <?php echo esc_attr((string) $cuenta['nivel']['progreso']); ?>%"></span>
                    </div>
                    <p class="cliente-nivel__hint">
                        <?php if ((int) $cuenta['nivel']['faltan'] > 0) : ?>
                            Faltan <?php echo esc_html((string) $cuenta['nivel']['faltan']); ?> visitas para
                            <?php echo esc_html((string) $cuenta['nivel']['siguiente']); ?>
                        <?php else : ?>
                            Has alcanzado el nivel máximo.
                        <?php endif; ?>
                    </p>
                    <?php if (!empty($cuenta['nivel']['beneficios']) && is_array($cuenta['nivel']['beneficios'])) : ?>
                        <ul class="cliente-nivel__beneficios">
                            <?php foreach ($cuenta['nivel']['beneficios'] as $beneficio) : ?>
                                <li><i class="ti ti-check" aria-hidden="true"></i> <?php echo esc_html((string) $beneficio); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </aside>
            </div>

            <section class="cliente-dash__section cliente-dash__section--historial" aria-labelledby="cliente-historial-preview-title">
                <div class="cliente-dash__section-head">
                    <h2 id="cliente-historial-preview-title">Historial de estilo</h2>
                    <a href="<?php echo esc_url(add_query_arg('vista', 'historial', yuniorrojas_url_cuenta())); ?>">Ver todas</a>
                </div>
                <?php if (empty($cuenta['historial'])) : ?>
                    <p class="cliente-dash__empty-note">Tu historial aparecerá aquí después de tu primera visita.</p>
                <?php else : ?>
                    <div class="cliente-historial cliente-historial--preview">
                        <?php foreach (array_slice($cuenta['historial'], 0, 2) as $item) : ?>
                            <article class="cliente-historial__card">
                                <div class="cliente-historial__media-wrap">
                                    <div
                                        class="cliente-historial__media"
                                        style="--cliente-historial-image:url('<?php echo esc_url($item['imagen']); ?>')"
                                        role="img"
                                        aria-label="<?php echo esc_attr($item['titulo']); ?>">
                                    </div>
                                    <span class="cliente-historial__badge"><?php echo esc_html($item['fecha']); ?></span>
                                    <?php if (!empty($item['tiene_foto'])) : ?>
                                        <span class="cliente-historial__badge cliente-historial__badge--foto">Foto del corte</span>
                                    <?php endif; ?>
                                </div>
                                <div class="cliente-historial__body">
                                    <h3><?php echo esc_html($item['titulo']); ?></h3>
                                    <p class="cliente-historial__barbero">
                                        <i class="ti ti-scissors" aria-hidden="true"></i>
                                        Barbero: <?php echo esc_html($item['barbero']); ?>
                                    </p>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

        <?php elseif ($seccion === 'citas') : ?>
            <section class="cliente-citas-page" aria-labelledby="cliente-citas-title">
                <header class="cliente-page-header">
                    <h1 id="cliente-citas-title">Próximas citas</h1>
                    <p>Revisa, reprograma o cancela tus reservas próximas.</p>
                </header>

                <?php if (empty($cuenta['proximas'])) : ?>
                    <p class="cliente-dash__empty-note">
                        No tienes citas programadas.
                        <a href="<?php echo esc_url($reservar_url); ?>">Crear una reserva</a>
                    </p>
                <?php else : ?>
                    <?php foreach ($cuenta['proximas'] as $cita) : ?>
                        <?php $render_cita($cita); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>

        <?php elseif ($seccion === 'historial') : ?>
            <?php
            $anios = array();
            $servicios_filtro = array();
            foreach ($cuenta['historial'] as $item) {
                $anio = (string) ($item['anio'] ?? '');
                $serv = (string) ($item['servicio'] ?? '');
                if ($anio !== '') {
                    $anios[$anio] = $anio;
                }
                if ($serv !== '') {
                    $servicios_filtro[$serv] = (string) ($item['titulo'] ?? $serv);
                }
            }
            krsort($anios);
            ?>
            <section class="cliente-historial-page" aria-labelledby="cliente-historial-title" data-cliente-historial>
                <header class="cliente-historial-page__header">
                    <div class="cliente-historial-page__intro">
                        <h1 id="cliente-historial-title">Historial de estilo</h1>
                        <p>Su legado de precisión y excelencia.</p>
                    </div>
                    <div class="cliente-historial-page__filters">
                        <label class="cliente-historial-page__filter">
                            <span class="visually-hidden">Filtrar por año</span>
                            <select data-historial-anio>
                                <option value="*">Todos los años</option>
                                <?php foreach ($anios as $anio) : ?>
                                    <option value="<?php echo esc_attr($anio); ?>"><?php echo esc_html($anio); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <i class="ti ti-chevron-down" aria-hidden="true"></i>
                        </label>
                        <label class="cliente-historial-page__filter">
                            <span class="visually-hidden">Filtrar por servicio</span>
                            <select data-historial-servicio>
                                <option value="*">Todos los servicios</option>
                                <?php foreach ($servicios_filtro as $slug => $label) : ?>
                                    <option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <i class="ti ti-chevron-down" aria-hidden="true"></i>
                        </label>
                    </div>
                </header>

                <?php if (empty($cuenta['historial'])) : ?>
                    <p class="cliente-dash__empty-note">Todavía no hay visitas registradas en tu historial.</p>
                <?php else : ?>
                    <div class="cliente-historial cliente-historial--page">
                        <?php foreach ($cuenta['historial'] as $item) : ?>
                            <?php
                            $cta_class = (($item['cta'] ?? '') === 'outline')
                                ? 'cliente-historial__cta cliente-historial__cta--outline'
                                : 'cliente-historial__cta';
                            $repeat_args = array();
                            if (!empty($item['servicio_id'])) {
                                $repeat_args['servicio'] = (int) $item['servicio_id'];
                            }
                            if (!empty($item['barbero_id'])) {
                                $repeat_args['barbero'] = (int) $item['barbero_id'];
                            }
                            $repeat_url = yuniorrojas_url_reservar($repeat_args);
                            ?>
                            <article
                                class="cliente-historial__card"
                                data-historial-card
                                data-anio="<?php echo esc_attr($item['anio']); ?>"
                                data-servicio="<?php echo esc_attr($item['servicio']); ?>">
                                <div class="cliente-historial__media-wrap">
                                    <div
                                        class="cliente-historial__media"
                                        style="--cliente-historial-image:url('<?php echo esc_url($item['imagen']); ?>')"
                                        role="img"
                                        aria-label="<?php echo esc_attr($item['titulo']); ?>">
                                    </div>
                                    <span class="cliente-historial__badge"><?php echo esc_html($item['fecha']); ?></span>
                                </div>
                                <div class="cliente-historial__body">
                                    <h2><?php echo esc_html($item['titulo']); ?></h2>
                                    <p class="cliente-historial__barbero">
                                        <i class="ti ti-scissors" aria-hidden="true"></i>
                                        Barbero: <?php echo esc_html($item['barbero']); ?>
                                    </p>
                                    <p class="cliente-historial__desc">
                                        <i class="ti ti-list-details" aria-hidden="true"></i>
                                        <span><?php echo esc_html($item['descripcion']); ?></span>
                                    </p>
                                    <a class="<?php echo esc_attr($cta_class); ?>" href="<?php echo esc_url($repeat_url); ?>">
                                        Repetir estilo
                                        <i class="ti ti-arrow-right" aria-hidden="true"></i>
                                    </a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

        <?php else : ?>
            <?php
            $telefono_pref = (string) get_user_meta($user_id, 'telefono', true);
            $notas_pref    = (string) ($cuenta['notas_barbero'] ?? '');
            $estilo_sel    = '';
            foreach (($cuenta['estilos'] ?? array()) as $estilo) {
                if (!empty($estilo['selected'])) {
                    $estilo_sel = (string) $estilo['id'];
                    break;
                }
            }
            ?>
            <section class="cliente-pref-page" aria-labelledby="cliente-pref-title" data-cliente-pref>
                <header class="cliente-pref-page__header">
                    <h1 id="cliente-pref-title">Preferencias</h1>
                    <p>
                        Gestione su información personal, credenciales de seguridad y preferencias estilísticas
                        para asegurar una experiencia personalizada y precisa en cada visita.
                    </p>
                </header>

                <?php if ($pref_notice !== '') : ?>
                    <p class="cliente-pref-alert cliente-pref-alert--ok" role="status"><?php echo esc_html($pref_notice); ?></p>
                <?php endif; ?>
                <?php if ($pref_error !== '') : ?>
                    <p class="cliente-pref-alert cliente-pref-alert--error" role="alert"><?php echo esc_html($pref_error); ?></p>
                <?php endif; ?>

                <div class="cliente-pref-page__grid">
                    <div class="cliente-pref-page__col">
                        <form id="cliente-pref-form" class="cliente-pref-form" method="post" action="" novalidate data-pref-form>
                            <?php wp_nonce_field('jr_guardar_preferencias', 'jr_pref_nonce'); ?>
                            <input type="hidden" name="estilo_id" value="<?php echo esc_attr($estilo_sel); ?>" data-pref-estilo-input>

                            <section class="cliente-pref-block" aria-labelledby="pref-personal-title">
                                <h2 id="pref-personal-title" class="cliente-pref-block__title">Información personal</h2>

                                <label class="cliente-pref-field">
                                    <span>Nombre completo</span>
                                    <input type="text" name="nombre" value="<?php echo esc_attr($nombre_corto); ?>" autocomplete="name" required>
                                </label>
                                <label class="cliente-pref-field">
                                    <span>Correo electrónico</span>
                                    <input type="email" name="email" value="<?php echo esc_attr((string) $usuario->user_email); ?>" autocomplete="email" required>
                                </label>
                                <label class="cliente-pref-field">
                                    <span>Teléfono / WhatsApp</span>
                                    <input type="tel" name="telefono" value="<?php echo esc_attr($telefono_pref); ?>" autocomplete="tel">
                                </label>
                            </section>

                            <section class="cliente-pref-block" aria-labelledby="pref-seguridad-title">
                                <h2 id="pref-seguridad-title" class="cliente-pref-block__title">
                                    Seguridad
                                    <i class="ti ti-lock" aria-hidden="true"></i>
                                </h2>

                                <label class="cliente-pref-field">
                                    <span>Contraseña actual</span>
                                    <input type="password" name="password_actual" value="" autocomplete="current-password">
                                </label>
                                <label class="cliente-pref-field">
                                    <span>Nueva contraseña</span>
                                    <input type="password" name="password_nueva" value="" autocomplete="new-password">
                                </label>
                                <label class="cliente-pref-field">
                                    <span>Confirmar contraseña</span>
                                    <input type="password" name="password_confirm" value="" autocomplete="new-password">
                                </label>
                            </section>

                            <button type="submit" class="cliente-pref-save">
                                Guardar cambios
                                <span class="cliente-pref-save__icon" aria-hidden="true">
                                    <i class="ti ti-check"></i>
                                </span>
                            </button>
                        </form>
                    </div>

                    <aside class="cliente-pref-page__col cliente-pref-page__col--estilo">
                        <section class="cliente-pref-block" aria-labelledby="pref-estilo-title">
                            <h2 id="pref-estilo-title" class="cliente-pref-block__title">Preferencias de estilo</h2>
                            <p class="cliente-pref-block__lead">
                                Seleccione sus cortes de referencia o deje notas para su próximo servicio.
                            </p>

                            <div class="cliente-pref-estilos" role="listbox" aria-label="Cortes de referencia" data-pref-estilos>
                                <?php if (empty($cuenta['estilos'])) : ?>
                                    <p class="cliente-pref-estilos__empty">
                                        Aún no hay cortes de referencia. Publica obras en Galería (categoría Cortes) para que aparezcan aquí.
                                    </p>
                                <?php else : ?>
                                    <?php foreach ($cuenta['estilos'] as $estilo) : ?>
                                        <button
                                            type="button"
                                            class="cliente-pref-estilo<?php echo !empty($estilo['selected']) ? ' is-selected' : ''; ?>"
                                            role="option"
                                            aria-selected="<?php echo !empty($estilo['selected']) ? 'true' : 'false'; ?>"
                                            data-estilo-id="<?php echo esc_attr((string) $estilo['id']); ?>">
                                            <span
                                                class="cliente-pref-estilo__media"
                                                style="--cliente-estilo-image:url('<?php echo esc_url((string) $estilo['imagen']); ?>')"
                                                aria-hidden="true"></span>
                                            <span class="cliente-pref-estilo__check" aria-hidden="true">
                                                <i class="ti ti-check"></i>
                                            </span>
                                            <span class="cliente-pref-estilo__name"><?php echo esc_html((string) $estilo['nombre']); ?></span>
                                        </button>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <label class="cliente-pref-notas">
                                <span>
                                    Notas para el maestro barbero
                                    <i class="ti ti-pencil" aria-hidden="true"></i>
                                </span>
                                <textarea
                                    name="notas_barbero"
                                    form="cliente-pref-form"
                                    rows="4"
                                    data-pref-notas
                                    placeholder="Ej: Mantener el volumen superior, desvanecido bajo, piel sensible en el cuello..."><?php echo esc_textarea($notas_pref); ?></textarea>
                            </label>
                        </section>
                    </aside>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <div
        class="cliente-cancel-modal"
        data-cliente-cancel-modal
        hidden
        role="dialog"
        aria-modal="true"
        aria-labelledby="cliente-cancel-title"
        aria-describedby="cliente-cancel-text">
        <div class="cliente-cancel-modal__backdrop" data-cliente-cancel-close aria-hidden="true"></div>
        <div class="cliente-cancel-modal__dialog">
            <div class="cliente-cancel-modal__icon" aria-hidden="true">
                <i class="ti ti-alert-triangle"></i>
            </div>
            <h2 id="cliente-cancel-title" class="cliente-cancel-modal__title">Cancelar cita</h2>
            <p id="cliente-cancel-text" class="cliente-cancel-modal__lead" data-cliente-cancel-lead>
                ¿Seguro que deseas cancelar esta cita?
            </p>
            <p class="cliente-cancel-modal__meta" data-cliente-cancel-meta hidden></p>

            <div class="cliente-cancel-modal__warning" data-cliente-cancel-warning>
                <strong>Importante</strong>
                <span data-cliente-cancel-warning-text>
                    Solo puedes cancelar reservas con pago en el estudio (efectivo).
                    Si pagaste con tarjeta, transferencia, Yape o Plin, la cita no se puede cancelar desde aquí.
                </span>
            </div>

            <div class="cliente-cancel-modal__actions">
                <button type="button" class="cliente-cancel-modal__btn cliente-cancel-modal__btn--ghost" data-cliente-cancel-close>
                    Mantener cita
                </button>
                <button type="button" class="cliente-cancel-modal__btn cliente-cancel-modal__btn--danger" data-cliente-cancel-confirm>
                    Sí, cancelar
                </button>
            </div>
        </div>
    </div>
</div>

<?php
get_footer();

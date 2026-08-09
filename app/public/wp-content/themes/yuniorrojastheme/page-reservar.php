<?php
/**
 * Template Name: Reservar cita
 * Diseño: Reserva → Cita → Datos → Checkout (UI)
 */

if (yuniorrojas_es_administrador()) {
    wp_safe_redirect(admin_url());
    exit;
}

// Reservas web solo con cuenta de cliente.
if (!is_user_logged_in() || !yuniorrojas_es_cliente()) {
    $dest = (isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '');
    $login = yuniorrojas_url_login();
    if ($dest !== '') {
        $login = add_query_arg('redirect_to', $dest, $login);
    }
    wp_safe_redirect($login);
    exit;
}

get_header();

$servicio_pre = isset($_GET['servicio']) ? absint(wp_unslash($_GET['servicio'])) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$barbero_pre  = isset($_GET['barbero']) ? absint(wp_unslash($_GET['barbero'])) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$paso_pre = isset($_GET['paso']) ? sanitize_key(wp_unslash($_GET['paso'])) : 'experiencia'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$fecha_pre = isset($_GET['fecha']) ? sanitize_text_field(wp_unslash((string) $_GET['fecha'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$hora_pre  = isset($_GET['hora']) ? sanitize_text_field(wp_unslash((string) $_GET['hora'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$reprogramar_id = isset($_GET['reprogramar']) ? absint(wp_unslash($_GET['reprogramar'])) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
if (!in_array($paso_pre, array('experiencia', 'cita', 'datos', 'checkout', 'confirmada'), true)) {
    $paso_pre = 'experiencia';
}
if ($fecha_pre !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_pre)) {
    $fecha_pre = '';
}

// Solo permitir reprogramar si la reserva pertenece al cliente logueado.
if ($reprogramar_id > 0) {
    $uid = is_user_logged_in() ? (int) get_current_user_id() : 0;
    if ($uid <= 0 || !yuniorrojas_usuario_posee_reserva($reprogramar_id, $uid)) {
        $reprogramar_id = 0;
    }
}

$servicios_q = new WP_Query(array(
    'post_type'      => YUNIORROJAS_CPT_SERVICIOS,
    'posts_per_page' => -1,
    'post_status'    => 'publish',
    'orderby'        => 'menu_order',
    'order'          => 'ASC',
));

$barberos_q = new WP_Query(array(
    'post_type'      => 'barberos',
    'posts_per_page' => -1,
    'post_status'    => 'publish',
    'orderby'        => 'menu_order',
    'order'          => 'ASC',
));

$servicios_ids = wp_list_pluck($servicios_q->posts, 'ID');
$barberos_ids  = wp_list_pluck($barberos_q->posts, 'ID');

if ($servicio_pre > 0 && !in_array($servicio_pre, $servicios_ids, true)) {
    $servicio_pre = 0;
}
if ($barbero_pre > 0 && !in_array($barbero_pre, $barberos_ids, true)) {
    $barbero_pre = 0;
}

if ($servicio_pre === 0 && !empty($servicios_ids)) {
    $servicio_pre = (int) $servicios_ids[0];
}
if ($barbero_pre === 0 && !empty($barberos_ids)) {
    $barbero_pre = (int) $barberos_ids[0];
}

$vista_cita              = $paso_pre === 'cita';
$vista_datos             = $paso_pre === 'datos';
$vista_checkout          = in_array($paso_pre, array('checkout', 'confirmada'), true);
$abrir_modal_confirmada  = $paso_pre === 'confirmada';

$contacto_reserva = yuniorrojas_contacto();
$medios_checkout  = function_exists('yuniorrojas_medios_pago_checkout')
    ? yuniorrojas_medios_pago_checkout()
    : array();
// Fallback mínimo si aún no hay seed.
if ($medios_checkout === array()) {
    $medios_checkout = array(
        array(
            'id' => 0, 'slug' => 'estudio', 'nombre' => 'Pago en estudio', 'tipo' => 'estudio',
            'icono' => 'ti ti-building-store', 'descripcion' => '', 'instrucciones' => 'Pagarás al llegar al estudio.',
            'numero' => '', 'numero_digits' => '', 'titular' => '', 'qr_url' => '',
            'banco_nombre' => '', 'banco_cuenta' => '', 'banco_cci' => '', 'banco_titular' => '',
            'requiere_codigo' => false, 'abre_culqi' => false, 'es_estudio' => true,
        ),
    );
}
$primer_medio     = $medios_checkout[0];
$culqi_ok         = function_exists('yuniorrojas_culqi_esta_configurado') && yuniorrojas_culqi_esta_configurado();
$culqi_test       = $culqi_ok && function_exists('yuniorrojas_culqi_es_test') && yuniorrojas_culqi_es_test();
$estudio_direccion = (string) ($contacto_reserva['direccion'] ?? 'Jr. Ayacucho N° 727 - Huánuco - Perú');
// WhatsApp del estudio (confirmación / contacto).
$wa_raw           = (string) ($contacto_reserva['whatsapp'] ?? '+51 999 999 999');
$yape_digits      = preg_replace('/\D+/', '', $wa_raw) ?: '51999999999';
$yape_numero      = $wa_raw;

/** Datos del cliente logueado para prellenar el paso de contacto. */
$cliente_prefill = array(
    'nombres'   => '',
    'apellidos' => '',
    'telefono'  => '',
    'email'     => '',
    'notas'     => '',
);

if (is_user_logged_in()) {
    $cliente_user = wp_get_current_user();
    $first        = trim((string) $cliente_user->first_name);
    $last         = trim((string) $cliente_user->last_name);
    $display      = trim((string) $cliente_user->display_name);

    // Si solo hay un nombre completo en first/display, separar nombre y apellido.
    if ($last === '') {
        $fuente = $first !== '' ? $first : $display;
        if ($fuente !== '') {
            $parts = preg_split('/\s+/', $fuente, 2);
            $first = (string) ($parts[0] ?? '');
            $last  = (string) ($parts[1] ?? '');
        }
    } elseif ($first === '' && $display !== '') {
        $first = $display;
    }

    $telefono = (string) get_user_meta((int) $cliente_user->ID, 'telefono', true);
    if ($telefono === '') {
        $telefono = (string) get_user_meta((int) $cliente_user->ID, 'whatsapp', true);
    }

    $cliente_prefill = array(
        'nombres'   => $first,
        'apellidos' => $last,
        'telefono'  => $telefono,
        'email'     => (string) $cliente_user->user_email,
        'notas'     => (string) get_user_meta((int) $cliente_user->ID, 'jr_notas_barbero', true),
    );
}
?>

<main
    class="page-reservar"
    data-reserva
    data-paso="<?php echo esc_attr($paso_pre); ?>"
    <?php if ($reprogramar_id > 0) : ?>data-reprogramar-id="<?php echo esc_attr((string) $reprogramar_id); ?>"<?php endif; ?>
    <?php if ($fecha_pre !== '') : ?>data-reserva-fecha="<?php echo esc_attr($fecha_pre); ?>"<?php endif; ?>
    <?php if ($hora_pre !== '') : ?>data-reserva-hora="<?php echo esc_attr($hora_pre); ?>"<?php endif; ?>
    <?php if ($cliente_prefill['nombres'] !== '') : ?>data-reserva-nombres="<?php echo esc_attr($cliente_prefill['nombres']); ?>"<?php endif; ?>
    <?php if ($cliente_prefill['apellidos'] !== '') : ?>data-reserva-apellidos="<?php echo esc_attr($cliente_prefill['apellidos']); ?>"<?php endif; ?>
    <?php if ($cliente_prefill['telefono'] !== '') : ?>data-reserva-telefono="<?php echo esc_attr($cliente_prefill['telefono']); ?>"<?php endif; ?>
    <?php if ($cliente_prefill['email'] !== '') : ?>data-reserva-email="<?php echo esc_attr($cliente_prefill['email']); ?>"<?php endif; ?>
>

    <div class="reservar-vista" data-reserva-vista="experiencia" <?php echo ($vista_cita || $vista_datos || $vista_checkout) ? 'hidden' : ''; ?>>

        <header class="reservar-hero">
            <div class="contenedor reservar-hero__inner">
                <h1 class="reservar-hero__title">Reserva tu experiencia</h1>
                <p class="reservar-hero__quote">“El estilo comienza con disciplina”</p>
            </div>
        </header>

        <section class="reservar seccion">
            <div class="contenedor reservar__layout">

                <div class="reservar__main">

                    <section class="reservar-step reservar-step--servicios" aria-labelledby="reservar-paso-servicio">
                        <h2 id="reservar-paso-servicio" class="reservar-step__title">
                            <span class="reservar-step__num" aria-hidden="true">01</span>
                            Selecciona el servicio
                        </h2>

                        <?php if ($servicios_q->have_posts()) : ?>
                            <ul class="reservar-servicios" role="listbox" aria-label="<?php esc_attr_e('Servicios disponibles', YUNIORROJAS_TEXT_DOMAIN); ?>">
                                <?php while ($servicios_q->have_posts()) : $servicios_q->the_post(); ?>
                                    <?php
                                    $sid      = (int) get_the_ID();
                                    $precio   = (string) yuniorrojas_field('precio', $sid, '');
                                    $duracion = (string) yuniorrojas_field('tiempo_de_servicio', $sid, '');
                                    $activo   = $sid === $servicio_pre;
                                    $thumb    = get_the_post_thumbnail_url($sid, 'large');
                                    ?>
                                    <li>
                                        <button
                                            type="button"
                                            class="reservar-servicio<?php echo $activo ? ' is-selected' : ''; ?>"
                                            role="option"
                                            aria-selected="<?php echo $activo ? 'true' : 'false'; ?>"
                                            data-reserva-servicio
                                            data-id="<?php echo esc_attr((string) $sid); ?>"
                                            data-nombre="<?php echo esc_attr(get_the_title()); ?>"
                                            data-precio="<?php echo esc_attr($precio); ?>"
                                            data-duracion="<?php echo esc_attr($duracion); ?>"
                                            <?php if ($thumb) : ?>
                                                style="--reservar-servicio-img:url('<?php echo esc_url($thumb); ?>')"
                                            <?php endif; ?>
                                        >
                                            <span class="reservar-servicio__overlay" aria-hidden="true"></span>
                                            <span class="reservar-servicio__meta">
                                                <span class="reservar-servicio__info">
                                                    <span class="reservar-servicio__name"><?php the_title(); ?></span>
                                                    <?php if ($duracion !== '') : ?>
                                                        <span class="reservar-servicio__duration">
                                                            <?php echo esc_html($duracion); ?> Minutos
                                                        </span>
                                                    <?php endif; ?>
                                                </span>
                                                <?php if ($precio !== '') : ?>
                                                    <span class="reservar-servicio__price">
                                                        S/. <?php echo esc_html($precio); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </span>
                                        </button>
                                    </li>
                                <?php endwhile; ?>
                                <?php wp_reset_postdata(); ?>
                            </ul>
                        <?php else : ?>
                            <p class="reservar__empty">Aún no hay servicios publicados.</p>
                        <?php endif; ?>
                    </section>

                    <section class="reservar-step reservar-step--barberos" aria-labelledby="reservar-paso-barbero">
                        <h2 id="reservar-paso-barbero" class="reservar-step__title">
                            <span class="reservar-step__num" aria-hidden="true">02</span>
                            Selecciona a tu barbero
                        </h2>

                        <?php if ($barberos_q->have_posts()) : ?>
                            <ul class="reservar-barberos" role="listbox" aria-label="<?php esc_attr_e('Barberos disponibles', YUNIORROJAS_TEXT_DOMAIN); ?>">
                                <?php while ($barberos_q->have_posts()) : $barberos_q->the_post(); ?>
                                    <?php
                                    $bid    = (int) get_the_ID();
                                    $cargo  = (string) yuniorrojas_field('cargo', $bid, 'Barbero');
                                    $activo = $bid === $barbero_pre;
                                    $perfil = yuniorrojas_imagen_perfil_barbero($bid);
                                    $foto   = $perfil > 0
                                        ? (string) wp_get_attachment_image_url($perfil, 'thumbnail')
                                        : (string) get_the_post_thumbnail_url($bid, 'thumbnail');
                                    $horario = yuniorrojas_obtener_horario_barbero($bid);
                                    $horario_json = wp_json_encode($horario, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                                    ?>
                                    <li>
                                        <button
                                            type="button"
                                            class="reservar-barbero<?php echo $activo ? ' is-selected' : ''; ?>"
                                            role="option"
                                            aria-selected="<?php echo $activo ? 'true' : 'false'; ?>"
                                            data-reserva-barbero
                                            data-id="<?php echo esc_attr((string) $bid); ?>"
                                            data-nombre="<?php echo esc_attr(get_the_title()); ?>"
                                            data-cargo="<?php echo esc_attr($cargo); ?>"
                                            data-foto="<?php echo esc_url($foto); ?>"
                                            data-horario="<?php echo esc_attr($horario_json ?: '{}'); ?>"
                                        >
                                            <span class="reservar-barbero__media">
                                                <?php
                                                if ($perfil > 0) {
                                                    echo wp_get_attachment_image($perfil, 'large', false, array(
                                                        'class' => 'reservar-barbero__img',
                                                        'alt'   => '',
                                                    ));
                                                } elseif (has_post_thumbnail()) {
                                                    the_post_thumbnail('large', array(
                                                        'class' => 'reservar-barbero__img',
                                                        'alt'   => '',
                                                    ));
                                                }
                                                ?>
                                            </span>
                                            <span class="reservar-barbero__body">
                                                <span class="reservar-barbero__name"><?php the_title(); ?></span>
                                                <span class="reservar-barbero__role"><?php echo esc_html($cargo); ?></span>
                                            </span>
                                        </button>
                                    </li>
                                <?php endwhile; ?>
                                <?php wp_reset_postdata(); ?>
                            </ul>
                        <?php else : ?>
                            <p class="reservar__empty">Aún no hay barberos publicados.</p>
                        <?php endif; ?>
                    </section>

                </div>

                <aside class="reservar-summary" aria-labelledby="reservar-summary-title">
                    <div class="reservar-summary__card">
                        <h2 id="reservar-summary-title" class="reservar-summary__title">Tu reserva</h2>

                        <dl class="reservar-summary__list">
                            <div class="reservar-summary__row">
                                <dt>Servicio</dt>
                                <dd>
                                    <span data-summary-servicio>—</span>
                                    <span class="reservar-summary__price" data-summary-precio></span>
                                </dd>
                            </div>
                            <div class="reservar-summary__row">
                                <dt>Barbero</dt>
                                <dd data-summary-barbero>—</dd>
                            </div>
                        </dl>

                        <div class="reservar-summary__total">
                            <span>Total</span>
                            <strong data-summary-total>S/. 0.00</strong>
                        </div>

                        <button
                            type="button"
                            class="btn btn--primary reservar-summary__cta"
                            data-reserva-continuar
                            disabled
                        >
                            Continuar reserva
                        </button>

                        <p class="reservar-summary__quote">“La disciplina es una decisión diaria”</p>
                    </div>
                </aside>

            </div>
        </section>

    </div>

    <div class="reservar-vista reservar-vista--cita" data-reserva-vista="cita" <?php echo $vista_cita ? '' : 'hidden'; ?>>
        <section class="reservar-cita seccion">
            <div class="contenedor reservar-cita__layout">

                <div class="reservar-cita__main">
                    <button type="button" class="reservar-cita__back" data-reserva-volver>
                        <span aria-hidden="true">←</span> Volver a reserva
                    </button>

                    <header class="reservar-cita__intro">
                        <h1 class="reservar-cita__title">
                            <span class="reservar-cita__num" aria-hidden="true">03</span>
                            Selecciona tu cita
                        </h1>
                        <p class="reservar-cita__lead">
                            Elige el día y la hora que mejor se adapte a tu agenda para tu experiencia de grooming premium.
                        </p>
                    </header>

                    <div class="reservar-calendar" data-reserva-calendario>
                        <div class="reservar-calendar__header">
                            <h2 class="reservar-calendar__month" data-cal-month>Octubre 2026</h2>
                            <div class="reservar-calendar__nav">
                                <button type="button" class="reservar-calendar__nav-btn" data-cal-prev aria-label="<?php esc_attr_e('Mes anterior', YUNIORROJAS_TEXT_DOMAIN); ?>">
                                    ‹
                                </button>
                                <button type="button" class="reservar-calendar__nav-btn" data-cal-next aria-label="<?php esc_attr_e('Mes siguiente', YUNIORROJAS_TEXT_DOMAIN); ?>">
                                    ›
                                </button>
                            </div>
                        </div>

                        <div class="reservar-calendar__weekdays" aria-hidden="true">
                            <span>Dom</span>
                            <span>Lun</span>
                            <span>Mar</span>
                            <span>Mié</span>
                            <span>Jue</span>
                            <span>Vie</span>
                            <span>Sáb</span>
                        </div>

                        <div class="reservar-calendar__grid" data-cal-grid role="grid" aria-label="<?php esc_attr_e('Calendario', YUNIORROJAS_TEXT_DOMAIN); ?>"></div>
                    </div>

                    <div class="reservar-horarios" data-reserva-horarios>
                        <h2 class="reservar-horarios__title" data-horarios-title>
                            Horarios disponibles
                        </h2>

                        <div class="reservar-horarios__group">
                            <h3 class="reservar-horarios__group-title">
                                <i class="ti ti-sun" aria-hidden="true"></i>
                                Mañana
                            </h3>
                            <div class="reservar-horarios__slots" data-slots-manana role="listbox" aria-label="<?php esc_attr_e('Horarios de mañana', YUNIORROJAS_TEXT_DOMAIN); ?>"></div>
                        </div>

                        <div class="reservar-horarios__group">
                            <h3 class="reservar-horarios__group-title">
                                <i class="ti ti-moon" aria-hidden="true"></i>
                                Tarde
                            </h3>
                            <div class="reservar-horarios__slots" data-slots-tarde role="listbox" aria-label="<?php esc_attr_e('Horarios de tarde', YUNIORROJAS_TEXT_DOMAIN); ?>"></div>
                        </div>

                        <div class="reservar-espera" data-lista-espera hidden>
                            <p class="reservar-espera__text">
                                No hay horarios libres este día. Te avisamos por email si se libera un hueco.
                            </p>
                            <button type="button" class="btn btn--outline" data-lista-espera-btn>
                                Avísame si hay hueco
                            </button>
                            <p class="reservar-espera__ok" data-lista-espera-ok hidden>Listo. Te avisaremos por email.</p>
                        </div>
                    </div>
                </div>

                <aside class="reservar-cita-summary" aria-labelledby="reservar-cita-summary-title">
                    <div class="reservar-cita-summary__card">
                        <h2 id="reservar-cita-summary-title" class="reservar-cita-summary__title">
                            Resumen de reserva
                        </h2>

                        <ul class="reservar-cita-summary__items">
                            <li class="reservar-cita-summary__item">
                                <span class="reservar-cita-summary__icon" aria-hidden="true">
                                    <i class="ti ti-scissors"></i>
                                </span>
                                <div class="reservar-cita-summary__body">
                                    <span class="reservar-cita-summary__label">Servicio</span>
                                    <span class="reservar-cita-summary__value" data-cita-servicio>—</span>
                                    <span class="reservar-cita-summary__meta" data-cita-precio></span>
                                </div>
                            </li>

                            <li class="reservar-cita-summary__item">
                                <span class="reservar-cita-summary__avatar" data-cita-avatar aria-hidden="true"></span>
                                <div class="reservar-cita-summary__body">
                                    <span class="reservar-cita-summary__label">Profesional</span>
                                    <span class="reservar-cita-summary__value" data-cita-barbero>—</span>
                                    <span class="reservar-cita-summary__meta" data-cita-cargo></span>
                                </div>
                            </li>

                            <li class="reservar-cita-summary__item reservar-cita-summary__item--fecha">
                                <span class="reservar-cita-summary__icon" aria-hidden="true">
                                    <i class="ti ti-calendar-event"></i>
                                </span>
                                <div class="reservar-cita-summary__body">
                                    <span class="reservar-cita-summary__label">Fecha y hora</span>
                                    <span class="reservar-cita-summary__value" data-cita-fecha>—</span>
                                    <span class="reservar-cita-summary__meta" data-cita-hora></span>
                                </div>
                            </li>
                        </ul>

                        <div class="reservar-cita-summary__pricing">
                            <div class="reservar-cita-summary__subtotal">
                                <span>Subtotal</span>
                                <span data-cita-subtotal>S/. 0.00</span>
                            </div>
                            <div class="reservar-cita-summary__total">
                                <span>Total estimado</span>
                                <strong data-cita-total>S/. 0.00</strong>
                            </div>
                            <p class="reservar-cita-summary__note">* El pago se realiza en el local</p>
                        </div>

                        <button
                            type="button"
                            class="btn btn--primary reservar-cita-summary__cta"
                            data-reserva-datos
                            disabled
                        >
                            Continuar a datos <span aria-hidden="true">→</span>
                        </button>
                    </div>
                </aside>

            </div>
        </section>
    </div>

    <div class="reservar-vista reservar-vista--datos" data-reserva-vista="datos" <?php echo $vista_datos ? '' : 'hidden'; ?>>
        <section class="reservar-datos seccion">
            <div class="contenedor">

                <div class="reservar-datos__top">
                    <button type="button" class="reservar-cita__back" data-reserva-volver-horario>
                        <span aria-hidden="true">←</span> Volver al horario
                    </button>

                    <ol class="reservar-datos__progress" aria-label="<?php esc_attr_e('Progreso de reserva', YUNIORROJAS_TEXT_DOMAIN); ?>">
                        <li class="reservar-datos__progress-step" aria-hidden="true"></li>
                        <li class="reservar-datos__progress-step" aria-hidden="true"></li>
                        <li class="reservar-datos__progress-step is-active" aria-current="step"></li>
                    </ol>
                </div>

                <div class="reservar-datos__layout">
                    <div class="reservar-datos__main">
                        <header class="reservar-datos__intro">
                            <h1 class="reservar-datos__title">
                                <span class="reservar-datos__num" aria-hidden="true">04</span>
                                Detalles de contacto
                            </h1>
                            <p class="reservar-datos__lead">
                                Estos datos vienen de tu cuenta y no se editan aquí.
                                Si necesitas actualizarlos, hazlo en
                                <a href="<?php echo esc_url(add_query_arg('vista', 'preferencias', yuniorrojas_url_cuenta())); ?>">Preferencias</a>.
                                Puedes agregar una nota especial para esta cita.
                            </p>
                        </header>

                        <form class="reservar-datos__form" data-reserva-form novalidate>
                            <?php $campos_bloqueados = is_user_logged_in(); ?>
                            <div class="reservar-datos__row">
                                <div class="reservar-datos__field">
                                    <label class="reservar-datos__label" for="reserva-nombres">Nombres</label>
                                    <input
                                        type="text"
                                        id="reserva-nombres"
                                        name="nombres"
                                        class="reservar-datos__input"
                                        autocomplete="given-name"
                                        required
                                        data-reserva-campo="nombres"
                                        value="<?php echo esc_attr($cliente_prefill['nombres']); ?>"
                                        <?php echo $campos_bloqueados ? 'readonly' : ''; ?>
                                    >
                                </div>
                                <div class="reservar-datos__field">
                                    <label class="reservar-datos__label" for="reserva-apellidos">Apellidos</label>
                                    <input
                                        type="text"
                                        id="reserva-apellidos"
                                        name="apellidos"
                                        class="reservar-datos__input"
                                        autocomplete="family-name"
                                        required
                                        data-reserva-campo="apellidos"
                                        value="<?php echo esc_attr($cliente_prefill['apellidos']); ?>"
                                        <?php echo $campos_bloqueados ? 'readonly' : ''; ?>
                                    >
                                </div>
                            </div>

                            <div class="reservar-datos__row">
                                <div class="reservar-datos__field">
                                    <label class="reservar-datos__label" for="reserva-telefono">Teléfono (WhatsApp)</label>
                                    <input
                                        type="tel"
                                        id="reserva-telefono"
                                        name="telefono"
                                        class="reservar-datos__input"
                                        autocomplete="tel"
                                        required
                                        data-reserva-campo="telefono"
                                        value="<?php echo esc_attr($cliente_prefill['telefono']); ?>"
                                        <?php echo $campos_bloqueados ? 'readonly' : ''; ?>
                                    >
                                </div>
                                <div class="reservar-datos__field">
                                    <label class="reservar-datos__label" for="reserva-email">Correo electrónico</label>
                                    <input
                                        type="email"
                                        id="reserva-email"
                                        name="email"
                                        class="reservar-datos__input"
                                        autocomplete="email"
                                        required
                                        data-reserva-campo="email"
                                        value="<?php echo esc_attr($cliente_prefill['email']); ?>"
                                        <?php echo $campos_bloqueados ? 'readonly' : ''; ?>
                                    >
                                </div>
                            </div>

                            <div class="reservar-datos__field">
                                <label class="reservar-datos__label" for="reserva-notas">
                                    Notas o requerimientos especiales
                                    <span class="reservar-datos__optional">(Opcional)</span>
                                </label>
                                <textarea
                                    id="reserva-notas"
                                    name="notas"
                                    class="reservar-datos__textarea"
                                    rows="5"
                                    placeholder="<?php esc_attr_e('¿Algún detalle que debamos saber antes de tu cita?', YUNIORROJAS_TEXT_DOMAIN); ?>"
                                    data-reserva-campo="notas"
                                ><?php echo esc_textarea($cliente_prefill['notas']); ?></textarea>
                            </div>
                        </form>
                    </div>

                    <aside class="reservar-datos-summary" aria-labelledby="reservar-datos-summary-title">
                        <div class="reservar-datos-summary__card">
                            <h2 id="reservar-datos-summary-title" class="reservar-datos-summary__title">
                                Resumen de cita
                            </h2>

                            <ul class="reservar-datos-summary__items">
                                <li class="reservar-datos-summary__item">
                                    <span class="reservar-datos-summary__icon" aria-hidden="true">
                                        <i class="ti ti-scissors"></i>
                                    </span>
                                    <div class="reservar-datos-summary__body">
                                        <span class="reservar-datos-summary__label">Servicio</span>
                                        <span class="reservar-datos-summary__value" data-datos-servicio>—</span>
                                        <span class="reservar-datos-summary__meta" data-datos-duracion></span>
                                    </div>
                                </li>
                                <li class="reservar-datos-summary__item">
                                    <span class="reservar-datos-summary__icon" aria-hidden="true">
                                        <i class="ti ti-user"></i>
                                    </span>
                                    <div class="reservar-datos-summary__body">
                                        <span class="reservar-datos-summary__label">Barbero</span>
                                        <span class="reservar-datos-summary__value" data-datos-barbero>—</span>
                                    </div>
                                </li>
                                <li class="reservar-datos-summary__item">
                                    <span class="reservar-datos-summary__icon" aria-hidden="true">
                                        <i class="ti ti-calendar-event"></i>
                                    </span>
                                    <div class="reservar-datos-summary__body">
                                        <span class="reservar-datos-summary__label">Fecha y hora</span>
                                        <span class="reservar-datos-summary__value" data-datos-fecha>—</span>
                                        <span class="reservar-datos-summary__meta" data-datos-hora></span>
                                    </div>
                                </li>
                                <li class="reservar-datos-summary__item">
                                    <span class="reservar-datos-summary__icon" aria-hidden="true">
                                        <i class="ti ti-id"></i>
                                    </span>
                                    <div class="reservar-datos-summary__body">
                                        <span class="reservar-datos-summary__label">Cliente</span>
                                        <span class="reservar-datos-summary__value" data-datos-cliente>—</span>
                                    </div>
                                </li>
                                <li class="reservar-datos-summary__item">
                                    <span class="reservar-datos-summary__icon" aria-hidden="true">
                                        <i class="ti ti-phone"></i>
                                    </span>
                                    <div class="reservar-datos-summary__body">
                                        <span class="reservar-datos-summary__label">Teléfono</span>
                                        <span class="reservar-datos-summary__value" data-datos-telefono>—</span>
                                    </div>
                                </li>
                                <li class="reservar-datos-summary__item">
                                    <span class="reservar-datos-summary__icon" aria-hidden="true">
                                        <i class="ti ti-mail"></i>
                                    </span>
                                    <div class="reservar-datos-summary__body">
                                        <span class="reservar-datos-summary__label">Correo</span>
                                        <span class="reservar-datos-summary__value" data-datos-email>—</span>
                                    </div>
                                </li>
                                <li class="reservar-datos-summary__item" data-datos-notas-item hidden>
                                    <span class="reservar-datos-summary__icon" aria-hidden="true">
                                        <i class="ti ti-notes"></i>
                                    </span>
                                    <div class="reservar-datos-summary__body">
                                        <span class="reservar-datos-summary__label">Notas</span>
                                        <span class="reservar-datos-summary__value reservar-datos-summary__value--notes" data-datos-notas></span>
                                    </div>
                                </li>
                            </ul>

                            <div class="reservar-datos-summary__total">
                                <span>Total estimado</span>
                                <strong data-datos-total>S/. 0.00</strong>
                            </div>

                            <button
                                type="button"
                                class="btn btn--primary reservar-datos-summary__cta"
                                data-reserva-ir-checkout
                                disabled
                            >
                                Ir a checkout <span aria-hidden="true">→</span>
                            </button>

                            <p class="reservar-datos-summary__note">El pago se realiza en el estudio.</p>
                        </div>
                    </aside>
                </div>

            </div>
        </section>
    </div>

    <div class="reservar-vista reservar-vista--checkout" data-reserva-vista="checkout" <?php echo $vista_checkout ? '' : 'hidden'; ?>>
        <section class="reservar-checkout seccion">
            <div class="contenedor reservar-checkout__layout">

                <div class="reservar-checkout__main">
                    <button type="button" class="reservar-cita__back" data-reserva-volver-datos>
                        <span aria-hidden="true">←</span> Volver a datos
                    </button>

                    <header class="reservar-checkout__intro">
                        <h1 class="reservar-checkout__title">
                            <span class="reservar-checkout__num" aria-hidden="true">05</span>
                            Finalizar reserva
                        </h1>
                    </header>

                    <?php require get_template_directory() . '/template-parts/checkout-medios.php'; ?>
                    <?php if (false) : // legado checkout estático (mantenido solo para no romper diffs) ?>
                    <div class="reservar-checkout__pago">
                        <h2 class="reservar-checkout__section-title">1. Método de pago</h2>

                        <div class="reservar-checkout__metodos" role="radiogroup" aria-label="<?php esc_attr_e('Método de pago', YUNIORROJAS_TEXT_DOMAIN); ?>" data-pago-metodos>
                            <button
                                type="button"
                                class="reservar-checkout__metodo is-selected"
                                role="radio"
                                aria-checked="true"
                                data-pago-metodo="tarjeta"
                            >
                                <i class="ti ti-credit-card" aria-hidden="true"></i>
                                <span>Tarjeta o Yape</span>
                            </button>
                            <button
                                type="button"
                                class="reservar-checkout__metodo"
                                role="radio"
                                aria-checked="false"
                                data-pago-metodo="plin"
                            >
                                <i class="ti ti-device-mobile" aria-hidden="true"></i>
                                <span>Plin</span>
                            </button>
                            <button
                                type="button"
                                class="reservar-checkout__metodo"
                                role="radio"
                                aria-checked="false"
                                data-pago-metodo="estudio"
                            >
                                <i class="ti ti-building-store" aria-hidden="true"></i>
                                <span>Pago en Estudio</span>
                            </button>
                        </div>
                    </div>

                    <div class="reservar-checkout__tarjeta" data-pago-panel="tarjeta">
                        <div class="reservar-checkout__tarjeta-card reservar-checkout__tarjeta-card--culqi">
                            <div class="reservar-checkout__tarjeta-head">
                                <h3 class="reservar-checkout__tarjeta-title">Paga online con Culqi</h3>
                                <i class="ti ti-shield-lock" aria-hidden="true"></i>
                            </div>

                            <?php if ($culqi_ok) : ?>
                                <p class="reservar-checkout__culqi-lead">
                                    Al pulsar <strong>Proceder al pago</strong> se abre el checkout seguro de Culqi.
                                    Ahí eliges <strong>tarjeta</strong> o <strong>Yape</strong> (con tu celular y código de aprobación).
                                    El cobro va a la cuenta del estudio en Culqi y la cita se confirma al momento.
                                </p>
                                <ul class="reservar-checkout__culqi-badges" aria-label="<?php esc_attr_e('Medios aceptados', YUNIORROJAS_TEXT_DOMAIN); ?>">
                                    <li>Visa</li>
                                    <li>Mastercard</li>
                                    <li>Amex</li>
                                    <li>Yape</li>
                                </ul>
                                <?php if ($culqi_test) : ?>
                                    <p class="reservar-checkout__test-badge" data-culqi-test-hint>
                                        <i class="ti ti-flask" aria-hidden="true"></i>
                                        <strong>Modo prueba Culqi</strong> — no uses datos reales.
                                        Tarjeta: 4111 1111 1111 1111 · 12/30 · CVV 123.
                                        Yape: celular <strong>900 000 001</strong> y cualquier código de 6 dígitos (ej. 123456).
                                    </p>
                                <?php endif; ?>
                            <?php else : ?>
                                <p class="reservar-checkout__culqi-lead reservar-checkout__culqi-lead--warn">
                                    Los pagos online (tarjeta / Yape) no están disponibles temporalmente.
                                    Elige <strong>Plin</strong> o <strong>Pago en Estudio</strong>.
                                </p>
                            <?php endif; ?>
                        </div>

                        <p class="reservar-checkout__secure">
                            <i class="ti ti-info-circle" aria-hidden="true"></i>
                            Procesado por Culqi (PCI DSS / Yape integrado). No guardamos datos de tarjeta ni códigos de aprobación en este sitio.
                        </p>
                    </div>

                    <div class="reservar-checkout__alt" data-pago-panel="plin" hidden>
                        <div class="reservar-yape">
                            <h3 class="reservar-yape__title">Paga con Plin</h3>
                            <p class="reservar-yape__lead">
                                Plin no cobra por pasarela: transfieres al número del estudio desde tu app
                                (Interbank, BBVA, Scotiabank u otros con Plin). Luego deja el código de operación para que verifiquemos.
                            </p>

                            <?php if ($yape_qr_url !== '') : ?>
                                <div class="reservar-yape__qr">
                                    <img
                                        src="<?php echo esc_url($yape_qr_url); ?>"
                                        alt="<?php esc_attr_e('Código QR Plin / celular del estudio', YUNIORROJAS_TEXT_DOMAIN); ?>"
                                        width="220"
                                        height="220"
                                        loading="lazy"
                                        decoding="async"
                                    >
                                </div>
                            <?php endif; ?>

                            <div class="reservar-yape__numero">
                                <span class="reservar-yape__label">Número Plin del estudio</span>
                                <div class="reservar-yape__numero-row">
                                    <strong data-yape-numero data-plin-numero><?php echo esc_html($yape_numero); ?></strong>
                                    <button
                                        type="button"
                                        class="reservar-yape__copiar"
                                        data-copiar-yape
                                        data-copiar-valor="<?php echo esc_attr((string) $yape_digits); ?>"
                                    >
                                        Copiar
                                    </button>
                                </div>
                                <?php if ($yape_titular !== '') : ?>
                                    <p class="reservar-yape__titular">
                                        Titular: <strong><?php echo esc_html($yape_titular); ?></strong>
                                    </p>
                                <?php endif; ?>
                            </div>

                            <?php if ($tiene_transfer) : ?>
                                <div class="reservar-yape__banco">
                                    <span class="reservar-yape__label">También puedes transferir por banca</span>
                                    <ul class="reservar-yape__banco-list">
                                        <?php if (!empty($pago_alt['banco_nombre'])) : ?>
                                            <li><span>Banco</span> <strong><?php echo esc_html((string) $pago_alt['banco_nombre']); ?></strong></li>
                                        <?php endif; ?>
                                        <?php if (!empty($pago_alt['banco_titular'])) : ?>
                                            <li><span>Titular</span> <strong><?php echo esc_html((string) $pago_alt['banco_titular']); ?></strong></li>
                                        <?php endif; ?>
                                        <?php if (!empty($pago_alt['banco_cuenta'])) : ?>
                                            <li>
                                                <span>Cuenta</span>
                                                <strong><?php echo esc_html((string) $pago_alt['banco_cuenta']); ?></strong>
                                                <button type="button" class="reservar-yape__copiar" data-copiar-yape
                                                    data-copiar-valor="<?php echo esc_attr(preg_replace('/\s+/', '', (string) $pago_alt['banco_cuenta'])); ?>">
                                                    Copiar
                                                </button>
                                            </li>
                                        <?php endif; ?>
                                        <?php if (!empty($pago_alt['banco_cci'])) : ?>
                                            <li>
                                                <span>CCI</span>
                                                <strong><?php echo esc_html((string) $pago_alt['banco_cci']); ?></strong>
                                                <button type="button" class="reservar-yape__copiar" data-copiar-yape
                                                    data-copiar-valor="<?php echo esc_attr(preg_replace('/\s+/', '', (string) $pago_alt['banco_cci'])); ?>">
                                                    Copiar
                                                </button>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <p class="reservar-yape__monto">
                                Monto a transferir:
                                <strong data-yape-monto data-plin-monto>S/. 0.00</strong>
                            </p>

                            <div class="reservar-yape__comprobante">
                                <label class="reservar-checkout__label" for="checkout-codigo-operacion">Código de operación Plin</label>
                                <input
                                    type="text"
                                    id="checkout-codigo-operacion"
                                    class="reservar-checkout__input"
                                    placeholder="Ej. 123456789"
                                    autocomplete="off"
                                    data-checkout-campo="codigo_operacion"
                                    data-yape-codigo
                                    data-plin-codigo
                                >
                                <p class="reservar-yape__hint">
                                    Abre Plin → Transfiere el monto exacto → pega el código de operación y confirma.
                                    La captura del pago la puedes subir después en <strong>Mi cuenta</strong>.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="reservar-checkout__alt" data-pago-panel="estudio" hidden>
                        <div class="reservar-checkout__alt-card">
                            <i class="ti ti-building-store" aria-hidden="true"></i>
                            <p>Reservarás tu cita ahora y realizarás el pago en efectivo o POS cuando llegues al estudio.</p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <aside class="reservar-checkout-summary" aria-labelledby="reservar-checkout-summary-title">
                    <div class="reservar-checkout-summary__card">
                        <h2 id="reservar-checkout-summary-title" class="reservar-checkout-summary__title">Resumen</h2>

                        <div class="reservar-checkout-summary__servicio">
                            <div class="reservar-checkout-summary__servicio-row">
                                <span data-checkout-servicio>—</span>
                                <strong data-checkout-precio>S/. 0.00</strong>
                            </div>
                            <p class="reservar-checkout-summary__meta" data-checkout-meta></p>
                        </div>

                        <div class="reservar-checkout-summary__barbero">
                            <span class="reservar-checkout-summary__avatar" data-checkout-avatar aria-hidden="true"></span>
                            <div>
                                <span class="reservar-checkout-summary__label">Barbero seleccionado</span>
                                <span class="reservar-checkout-summary__value" data-checkout-barbero>—</span>
                            </div>
                        </div>

                        <div class="reservar-checkout-summary__fecha">
                            <span>Fecha y hora</span>
                            <div class="reservar-checkout-summary__fecha-valor">
                                <strong data-checkout-fecha>—</strong>
                                <span data-checkout-hora></span>
                            </div>
                        </div>

                        <div class="reservar-checkout-summary__cliente">
                            <div class="reservar-checkout-summary__cliente-row">
                                <span class="reservar-checkout-summary__label">Cliente</span>
                                <strong class="reservar-checkout-summary__value" data-checkout-cliente>—</strong>
                            </div>
                            <div class="reservar-checkout-summary__cliente-row">
                                <span class="reservar-checkout-summary__label">Teléfono</span>
                                <span class="reservar-checkout-summary__value" data-checkout-telefono>—</span>
                            </div>
                            <div class="reservar-checkout-summary__cliente-row">
                                <span class="reservar-checkout-summary__label">Correo</span>
                                <span class="reservar-checkout-summary__value" data-checkout-email>—</span>
                            </div>
                        </div>

                        <?php
                        $productos_front = function_exists('yuniorrojas_productos_checkout_lista')
                            ? yuniorrojas_productos_checkout_lista()
                            : array();
                        if ($productos_front !== array()) :
                            ?>
                        <div class="reservar-checkout-summary__productos" data-checkout-productos>
                            <span class="reservar-checkout-summary__label"><?php esc_html_e('Productos (opcional)', YUNIORROJAS_TEXT_DOMAIN); ?></span>
                            <ul class="reservar-checkout-productos">
                                <?php foreach ($productos_front as $prod) : ?>
                                    <li class="reservar-checkout-productos__item">
                                        <label>
                                            <input
                                                type="checkbox"
                                                data-producto-id="<?php echo esc_attr((string) $prod['id']); ?>"
                                                data-producto-precio="<?php echo esc_attr((string) $prod['precio']); ?>"
                                                data-producto-check
                                            >
                                            <span><?php echo esc_html($prod['nombre']); ?></span>
                                            <em><?php echo esc_html($prod['precio_label']); ?></em>
                                        </label>
                                        <input
                                            type="number"
                                            min="1"
                                            max="10"
                                            value="1"
                                            data-producto-qty="<?php echo esc_attr((string) $prod['id']); ?>"
                                            class="reservar-checkout-productos__qty"
                                            aria-label="<?php esc_attr_e('Cantidad', YUNIORROJAS_TEXT_DOMAIN); ?>"
                                        >
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <div class="reservar-checkout-summary__line reservar-checkout-summary__line--prod">
                                <span><?php esc_html_e('Productos', YUNIORROJAS_TEXT_DOMAIN); ?></span>
                                <span data-checkout-productos-total>S/. 0.00</span>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="reservar-checkout-summary__pricing">
                            <div class="reservar-checkout-summary__line">
                                <span>Subtotal</span>
                                <span data-checkout-subtotal>S/. 0.00</span>
                            </div>
                            <div class="reservar-checkout-summary__line">
                                <span>Impuestos</span>
                                <span>Incluidos</span>
                            </div>
                        </div>

                        <div class="reservar-checkout-summary__total">
                            <span>Total final</span>
                            <strong data-checkout-total>S/. 0.00</strong>
                        </div>

                        <button
                            type="button"
                            class="btn btn--primary reservar-checkout-summary__cta"
                            data-reserva-proceder-pago
                        >
                            Proceder al pago <span aria-hidden="true">→</span>
                        </button>
                    </div>
                </aside>

            </div>
        </section>
    </div>

    <div
        class="reservar-pago-modal"
        data-reserva-pago-modal
        hidden
        role="alertdialog"
        aria-modal="true"
        aria-labelledby="reservar-pago-modal-title"
        aria-describedby="reservar-pago-modal-text"
    >
        <div class="reservar-pago-modal__backdrop" aria-hidden="true"></div>
        <div class="reservar-pago-modal__content">
            <div class="reservar-pago-modal__icon" aria-hidden="true">
                <i class="ti ti-hourglass"></i>
            </div>
            <h2 id="reservar-pago-modal-title" class="reservar-pago-modal__title">
                Validando tu transacción...
            </h2>
            <p id="reservar-pago-modal-text" class="reservar-pago-modal__text">
                Estamos asegurando tu cita de excelencia. Por favor, no cierres esta ventana.
            </p>
        </div>
    </div>

    <div
        class="reservar-error-modal"
        data-reserva-error-modal
        hidden
        role="alertdialog"
        aria-modal="true"
        aria-labelledby="reservar-error-modal-title"
        aria-describedby="reservar-error-modal-text"
    >
        <div class="reservar-error-modal__backdrop" aria-hidden="true"></div>
        <div class="reservar-error-modal__dialog">
            <div class="reservar-error-modal__header">
                <div class="reservar-error-modal__icon" aria-hidden="true">
                    <i class="ti ti-x"></i>
                </div>
                <h2 id="reservar-error-modal-title" class="reservar-error-modal__title">
                    Transacción no completada
                </h2>
                <p id="reservar-error-modal-text" class="reservar-error-modal__lead">
                    Lo sentimos, no pudimos procesar tu pago en este momento. Puede deberse a un error de red o fondos insuficientes.
                </p>
            </div>

            <div class="reservar-error-modal__details">
                <div class="reservar-error-modal__row">
                    <span class="reservar-error-modal__label">Servicio</span>
                    <strong class="reservar-error-modal__value reservar-error-modal__value--accent" data-error-servicio>—</strong>
                </div>
                <div class="reservar-error-modal__row">
                    <span class="reservar-error-modal__label">Barbero</span>
                    <strong class="reservar-error-modal__value" data-error-barbero>—</strong>
                </div>
                <div class="reservar-error-modal__row">
                    <span class="reservar-error-modal__label">Total</span>
                    <strong class="reservar-error-modal__value" data-error-total>—</strong>
                </div>
            </div>

            <div class="reservar-error-modal__actions">
                <button type="button" class="btn btn--primary reservar-error-modal__retry" data-error-reintentar>
                    Reintentar pago
                </button>
                <button type="button" class="btn btn--outline reservar-error-modal__change" data-error-cambiar-metodo>
                    Cambiar método de pago
                </button>
            </div>

            <p class="reservar-error-modal__help">
                Si el problema persiste, por favor contáctanos vía
                <a
                    class="reservar-error-modal__whatsapp"
                    href="https://wa.me/<?php echo esc_attr((string) $yape_digits); ?>"
                    target="_blank"
                    rel="noopener noreferrer"
                >WhatsApp</a>.
            </p>
        </div>
    </div>

    <div
        class="reservar-confirmada-modal"
        data-reserva-confirmada-modal
        <?php echo $abrir_modal_confirmada ? '' : 'hidden'; ?>
        role="dialog"
        aria-modal="true"
        aria-labelledby="reservar-confirmada-modal-title"
    >
        <div class="reservar-confirmada-modal__backdrop" aria-hidden="true"></div>
        <div class="reservar-confirmada-modal__dialog">
            <div class="reservar-confirmada-modal__panel">
                <div class="reservar-confirmada__voucher" data-reserva-voucher>
                    <div class="reservar-confirmada__brand">
                        <img
                            class="reservar-confirmada__logo"
                            src="<?php echo esc_url(get_template_directory_uri() . '/img/logo.png'); ?>"
                            alt="<?php echo esc_attr(get_bloginfo('name')); ?>"
                            width="120"
                            height="48"
                        >
                        <p class="reservar-confirmada__brand-name" data-confirm-brand>
                            <?php echo esc_html(get_bloginfo('name')); ?>
                        </p>
                    </div>

                    <div class="reservar-confirmada__header">
                        <div class="reservar-confirmada__icon" aria-hidden="true">
                            <i class="ti ti-check"></i>
                        </div>
                        <h2 id="reservar-confirmada-modal-title" class="reservar-confirmada__title">Reserva confirmada</h2>
                        <p class="reservar-confirmada__subtitle">
                            Te esperamos para tu próxima experiencia de excelencia.
                        </p>
                    </div>

                    <div class="reservar-confirmada__card">
                        <div class="reservar-confirmada__grid">
                            <div class="reservar-confirmada__row">
                                <div class="reservar-confirmada__item">
                                    <span class="reservar-confirmada__label">Cliente</span>
                                    <strong class="reservar-confirmada__value" data-confirm-cliente>—</strong>
                                </div>
                                <div class="reservar-confirmada__item">
                                    <span class="reservar-confirmada__label">Teléfono</span>
                                    <strong class="reservar-confirmada__value" data-confirm-telefono>—</strong>
                                </div>
                            </div>
                            <div class="reservar-confirmada__row">
                                <div class="reservar-confirmada__item">
                                    <span class="reservar-confirmada__label">Servicio</span>
                                    <strong class="reservar-confirmada__value" data-confirm-servicio>—</strong>
                                </div>
                                <div class="reservar-confirmada__item">
                                    <span class="reservar-confirmada__label">Barbero</span>
                                    <strong class="reservar-confirmada__value" data-confirm-barbero>—</strong>
                                </div>
                            </div>
                            <div class="reservar-confirmada__row">
                                <div class="reservar-confirmada__item">
                                    <span class="reservar-confirmada__label">Fecha</span>
                                    <strong class="reservar-confirmada__value" data-confirm-fecha>—</strong>
                                </div>
                                <div class="reservar-confirmada__item">
                                    <span class="reservar-confirmada__label">Hora</span>
                                    <strong class="reservar-confirmada__value" data-confirm-hora>—</strong>
                                </div>
                            </div>
                            <div class="reservar-confirmada__row reservar-confirmada__row--total">
                                <div class="reservar-confirmada__item reservar-confirmada__item--wide">
                                    <span class="reservar-confirmada__label">Total</span>
                                    <strong class="reservar-confirmada__value reservar-confirmada__value--precio" data-confirm-precio>—</strong>
                                </div>
                            </div>
                            <div class="reservar-confirmada__row reservar-confirmada__row--last">
                                <div class="reservar-confirmada__item reservar-confirmada__item--wide">
                                    <span class="reservar-confirmada__label">Ubicación</span>
                                    <strong class="reservar-confirmada__value reservar-confirmada__value--sm" data-confirm-ubicacion>
                                        <?php echo esc_html($estudio_direccion); ?>
                                    </strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="reservar-confirmada__footer">
                    <div class="reservar-confirmada__actions">
                        <button type="button" class="btn btn--outline reservar-confirmada__download" data-voucher-download>
                            <i class="ti ti-download" aria-hidden="true"></i>
                            Descargar
                        </button>
                        <button type="button" class="btn reservar-confirmada__whatsapp" data-share="whatsapp">
                            <i class="ti ti-brand-whatsapp" aria-hidden="true"></i>
                            WhatsApp
                        </button>
                        <button type="button" class="btn reservar-confirmada__continuar" data-voucher-continuar>
                            Ir a servicios
                            <i class="ti ti-arrow-right" aria-hidden="true"></i>
                        </button>
                    </div>
                    <p class="reservar-confirmada__hint" data-voucher-hint>
                        Puedes descargar o compartir el comprobante. En unos segundos te llevamos a Servicios.
                    </p>
                </div>
            </div>
        </div>
    </div>


</main>

<?php
get_footer();

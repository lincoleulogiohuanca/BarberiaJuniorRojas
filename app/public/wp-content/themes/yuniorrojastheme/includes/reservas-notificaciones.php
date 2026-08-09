<?php
/**
 * Notificaciones de reservas: email + enlaces WhatsApp + recordatorios.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Headers HTML para wp_mail.
 *
 * @return string[]
 */
function yuniorrojas_mail_headers_html(): array
{
    $from_name  = wp_specialchars_decode((string) get_bloginfo('name'), ENT_QUOTES);
    $from_email = (string) get_option('admin_email');

    return array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $from_name . ' <' . $from_email . '>',
    );
}

/**
 * Teléfono WhatsApp del estudio (solo dígitos con código país si aplica).
 */
function yuniorrojas_whatsapp_estudio_digits(): string
{
    $contacto = function_exists('yuniorrojas_contacto') ? yuniorrojas_contacto() : array();
    $raw      = (string) ($contacto['whatsapp'] ?? '');
    $digits   = preg_replace('/\D+/', '', $raw);
    return is_string($digits) ? $digits : '';
}

/**
 * URL wa.me con mensaje precargado.
 */
function yuniorrojas_whatsapp_url(string $phone_digits, string $mensaje): string
{
    $phone_digits = preg_replace('/\D+/', '', $phone_digits);
    if (!is_string($phone_digits) || $phone_digits === '') {
        return 'https://wa.me/?text=' . rawurlencode($mensaje);
    }

    return 'https://wa.me/' . $phone_digits . '?text=' . rawurlencode($mensaje);
}

/**
 * Resumen textual de una reserva para mensajes.
 */
function yuniorrojas_reserva_resumen_texto(array $reserva): string
{
    $servicio = (string) ($reserva['servicio_nombre'] ?? '');
    $barbero  = (string) ($reserva['barbero_nombre'] ?? '');
    $fecha    = (string) ($reserva['fecha'] ?? '');
    $hora     = (string) ($reserva['hora_label'] ?? $reserva['hora'] ?? '');
    $precio   = (string) ($reserva['precio'] ?? '0');

    $fecha_label = $fecha;
    $dt = DateTime::createFromFormat('Y-m-d', $fecha);
    if ($dt instanceof DateTime) {
        $fecha_label = $dt->format('d/m/Y');
    }

    return sprintf(
        "%s con %s\nFecha: %s · Hora: %s\nMonto: S/. %s",
        $servicio,
        $barbero,
        $fecha_label,
        $hora,
        $precio
    );
}

/**
 * Plantilla HTML simple de email.
 */
function yuniorrojas_email_wrap(string $titulo, string $cuerpo_html): string
{
    $site = esc_html((string) get_bloginfo('name'));

    return '<!DOCTYPE html><html><body style="margin:0;padding:24px;background:#111;color:#f5f5f5;font-family:Segoe UI,Arial,sans-serif;">'
        . '<div style="max-width:560px;margin:0 auto;background:#1c1b1b;border:1px solid #333;border-radius:12px;padding:28px;">'
        . '<p style="margin:0 0 8px;color:#c8a24a;font-size:12px;letter-spacing:.08em;text-transform:uppercase;">' . $site . '</p>'
        . '<h1 style="margin:0 0 16px;font-size:22px;color:#fff;">' . esc_html($titulo) . '</h1>'
        . '<div style="font-size:15px;line-height:1.55;color:#ddd;">' . $cuerpo_html . '</div>'
        . '</div></body></html>';
}

/**
 * Envía email de reserva (cliente y/o admin).
 *
 * @param 'creada'|'reprogramada'|'cancelada'|'recordatorio'|'pago_verificado' $evento
 */
function yuniorrojas_notificar_reserva(int $reserva_id, string $evento): void
{
    $reserva = yuniorrojas_obtener_reserva($reserva_id);
    if ($reserva === null) {
        return;
    }

    $evento = sanitize_key($evento);
    $cliente_email = sanitize_email((string) ($reserva['cliente_email'] ?? ''));
    $admin_email   = sanitize_email((string) get_option('admin_email'));
    $nombre        = trim((string) ($reserva['cliente_nombres'] ?? '') . ' ' . (string) ($reserva['cliente_apellidos'] ?? ''));
    $resumen       = nl2br(esc_html(yuniorrojas_reserva_resumen_texto($reserva)));
    $estado        = yuniorrojas_reserva_estado_label_cliente((string) ($reserva['estado'] ?? ''));
    $cuenta_url    = esc_url(yuniorrojas_url_cuenta());
    $wa_estudio    = yuniorrojas_whatsapp_estudio_digits();

    $titulos = array(
        'creada'          => 'Reserva registrada',
        'reprogramada'    => 'Reserva reprogramada',
        'cancelada'       => 'Reserva cancelada',
        'recordatorio'    => 'Recordatorio de tu cita',
        'pago_verificado' => 'Pago verificado',
    );
    $titulo = $titulos[$evento] ?? 'Actualización de reserva';

    $extra_cliente = '';
    if ($evento === 'creada' && (string) ($reserva['estado'] ?? '') === 'pendiente') {
        $extra_cliente = '<p style="margin:16px 0 0;color:#c8a24a;">Tu pago está <strong>pendiente de verificación</strong>. Te avisaremos cuando el estudio lo confirme.</p>';
    }
    if ($evento === 'pago_verificado') {
        $extra_cliente = '<p style="margin:16px 0 0;color:#c8a24a;">Tu pago fue verificado. Tu cita queda <strong>confirmada</strong>.</p>';
    }
    if ($evento === 'recordatorio') {
        $extra_cliente = '<p style="margin:16px 0 0;">Te esperamos mañana. Si no puedes asistir, avísanos con tiempo.</p>';
    }

    $cuerpo_cliente = '<p>Hola' . ($nombre !== '' ? ' ' . esc_html($nombre) : '') . ',</p>'
        . '<p>' . esc_html($titulo) . '.</p>'
        . '<p><strong>Detalle:</strong><br>' . $resumen . '</p>'
        . '<p>Estado: <strong>' . esc_html($estado) . '</strong></p>'
        . $extra_cliente
        . '<p style="margin:20px 0 0;"><a href="' . $cuenta_url . '" style="color:#c8a24a;">Ver en Mi cuenta</a></p>';

    if ($cliente_email !== '' && is_email($cliente_email)) {
        wp_mail(
            $cliente_email,
            '[' . get_bloginfo('name') . '] ' . $titulo,
            yuniorrojas_email_wrap($titulo, $cuerpo_cliente),
            yuniorrojas_mail_headers_html()
        );
    }

    if ($admin_email !== '' && is_email($admin_email) && in_array($evento, array('creada', 'reprogramada', 'cancelada'), true)) {
        $edit_link = esc_url(get_edit_post_link($reserva_id, 'raw') ?: admin_url('post.php?post=' . $reserva_id . '&action=edit'));
        $tel       = preg_replace('/\D+/', '', (string) ($reserva['cliente_telefono'] ?? ''));
        $wa_cliente = is_string($tel) && $tel !== ''
            ? yuniorrojas_whatsapp_url($tel, 'Hola ' . $nombre . ', te escribo por tu reserva en ' . get_bloginfo('name') . '.')
            : '';

        $cuerpo_admin = '<p>Evento: <strong>' . esc_html($evento) . '</strong></p>'
            . '<p>Cliente: ' . esc_html($nombre !== '' ? $nombre : '—') . '<br>Email: ' . esc_html($cliente_email) . '</p>'
            . '<p>' . $resumen . '</p>'
            . '<p>Estado: ' . esc_html($estado) . '</p>'
            . '<p><a href="' . $edit_link . '" style="color:#c8a24a;">Abrir reserva en el panel</a></p>';

        if ($wa_cliente !== '') {
            $cuerpo_admin .= '<p><a href="' . esc_url($wa_cliente) . '" style="color:#c8a24a;">WhatsApp al cliente</a></p>';
        }

        wp_mail(
            $admin_email,
            '[' . get_bloginfo('name') . '] ' . $titulo . ' #' . $reserva_id,
            yuniorrojas_email_wrap($titulo . ' (admin)', $cuerpo_admin),
            yuniorrojas_mail_headers_html()
        );
    }

    // Meta para UI: enlace WhatsApp al estudio con el detalle.
    if ($wa_estudio !== '') {
        $msg = "Hola, soy {$nombre}.\nMi reserva:\n" . yuniorrojas_reserva_resumen_texto($reserva);
        update_post_meta($reserva_id, yuniorrojas_reserva_meta_key('whatsapp_url'), yuniorrojas_whatsapp_url($wa_estudio, $msg));
    }
}

/**
 * Cron: recordatorios ~24h antes.
 */
function yuniorrojas_cron_recordatorios_reservas(): void
{
    $manana = gmdate('Y-m-d', strtotime(current_time('Y-m-d') . ' +1 day'));

    $ids = get_posts(array(
        'post_type'              => YUNIORROJAS_CPT_RESERVAS,
        'post_status'            => 'publish',
        'posts_per_page'         => 100,
        'fields'                 => 'ids',
        'no_found_rows'          => true,
        'meta_query'             => array(
            'relation' => 'AND',
            array(
                'key'   => yuniorrojas_reserva_meta_key('fecha'),
                'value' => $manana,
            ),
            array(
                'key'     => yuniorrojas_reserva_meta_key('estado'),
                'value'   => array('pendiente', 'confirmada'),
                'compare' => 'IN',
            ),
            array(
                'relation' => 'OR',
                array(
                    'key'     => yuniorrojas_reserva_meta_key('recordatorio_enviado'),
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key'   => yuniorrojas_reserva_meta_key('recordatorio_enviado'),
                    'value' => '1',
                    'compare'=> '!=',
                ),
            ),
        ),
    ));

    foreach ($ids as $id) {
        yuniorrojas_notificar_reserva((int) $id, 'recordatorio');
        update_post_meta((int) $id, yuniorrojas_reserva_meta_key('recordatorio_enviado'), '1');
    }
}
add_action('yuniorrojas_cron_recordatorios', 'yuniorrojas_cron_recordatorios_reservas');

/**
 * Programa el cron diario.
 */
function yuniorrojas_activar_cron_recordatorios(): void
{
    if (!wp_next_scheduled('yuniorrojas_cron_recordatorios')) {
        wp_schedule_event(time() + HOUR_IN_SECONDS, 'hourly', 'yuniorrojas_cron_recordatorios');
    }
}
add_action('init', 'yuniorrojas_activar_cron_recordatorios');

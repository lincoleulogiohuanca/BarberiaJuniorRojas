<?php
/**
 * Servicio de reservas — lógica de negocio (cliente).
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Estados válidos de una reserva.
 *
 * @return string[]
 */
function yuniorrojas_reserva_estados(): array
{
    return array('pendiente', 'confirmada', 'cancelada', 'completada', 'no_show');
}

/**
 * Prefijo de meta de reserva.
 */
function yuniorrojas_reserva_meta_key(string $campo): string
{
    return '_jr_reserva_' . $campo;
}

/**
 * Normaliza hora AM/PM o 24h a H:i (24h) para citas.
 * (No confundir con yuniorrojas_normalizar_hora del horario de barberos.)
 */
function yuniorrojas_parsear_hora_cita(string $hora): string
{
    $hora = trim($hora);
    if ($hora === '') {
        return '';
    }

    $dt = DateTime::createFromFormat('g:i A', strtoupper($hora));
    if ($dt instanceof DateTime) {
        return $dt->format('H:i');
    }

    $dt = DateTime::createFromFormat('H:i', $hora);
    if ($dt instanceof DateTime) {
        return $dt->format('H:i');
    }

    // Reutiliza normalizador 24h del metabox si está disponible.
    if (function_exists('yuniorrojas_normalizar_hora')) {
        $parsed = yuniorrojas_normalizar_hora($hora, '');
        if ($parsed !== '') {
            return $parsed;
        }
    }

    return '';
}

/**
 * Formatea hora 24h a etiqueta 12h.
 */
function yuniorrojas_formatear_hora_label(string $hora_24): string
{
    $dt = DateTime::createFromFormat('H:i', $hora_24);
    if (!$dt instanceof DateTime) {
        return $hora_24;
    }

    return $dt->format('g:i A');
}

/**
 * ¿El usuario es dueño de la reserva?
 */
function yuniorrojas_usuario_posee_reserva(int $reserva_id, int $user_id): bool
{
    if ($reserva_id <= 0 || $user_id <= 0) {
        return false;
    }

    $post = get_post($reserva_id);
    if (!$post instanceof WP_Post || $post->post_type !== YUNIORROJAS_CPT_RESERVAS) {
        return false;
    }

    $owner = (int) get_post_meta($reserva_id, yuniorrojas_reserva_meta_key('cliente_user_id'), true);
    if ($owner === $user_id) {
        return true;
    }

    $email = strtolower(trim((string) get_post_meta($reserva_id, yuniorrojas_reserva_meta_key('cliente_email'), true)));
    $user  = get_userdata($user_id);
    if ($user instanceof WP_User && $email !== '' && strtolower($user->user_email) === $email) {
        return true;
    }

    return false;
}

/**
 * Lee una reserva tipada.
 *
 * @return array<string, mixed>|null
 */
function yuniorrojas_obtener_reserva(int $reserva_id): ?array
{
    $post = get_post($reserva_id);
    if (!$post instanceof WP_Post || $post->post_type !== YUNIORROJAS_CPT_RESERVAS) {
        return null;
    }

    $meta = static function (string $key) use ($reserva_id): string {
        return (string) get_post_meta($reserva_id, yuniorrojas_reserva_meta_key($key), true);
    };

    $fecha = $meta('fecha');
    $hora  = $meta('hora');
    $dia   = '';
    $mes   = '';
    if ($fecha !== '') {
        $dt = DateTime::createFromFormat('Y-m-d', $fecha);
        if ($dt instanceof DateTime) {
            $meses = array('Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic');
            $dia   = $dt->format('d');
            $mes   = $meses[(int) $dt->format('n') - 1] ?? $dt->format('M');
        }
    }

    $hora_fin = '';
    $duracion = (int) $meta('duracion');
    if ($hora !== '' && $duracion > 0) {
        $inicio = DateTime::createFromFormat('Y-m-d H:i', $fecha . ' ' . $hora);
        if ($inicio instanceof DateTime) {
            $fin = clone $inicio;
            $fin->modify('+' . $duracion . ' minutes');
            $hora_fin = $fin->format('H:i');
        }
    }

    $hora_label = $meta('hora_label');
    if ($hora_label === '' && $hora !== '') {
        $hora_label = yuniorrojas_formatear_hora_label($hora);
        if ($hora_fin !== '') {
            $hora_label .= ' - ' . yuniorrojas_formatear_hora_label($hora_fin);
        }
    }

    $imagen_meta = $meta('imagen_resultado');
    $imagen_url  = '';
    if ($imagen_meta !== '') {
        if (ctype_digit($imagen_meta)) {
            $imagen_url = (string) wp_get_attachment_url((int) $imagen_meta);
        } else {
            $imagen_url = esc_url_raw($imagen_meta);
        }
    }

    return array(
        'id'               => $reserva_id,
        'titulo'           => $post->post_title,
        'estado'           => $meta('estado') !== '' ? $meta('estado') : 'confirmada',
        'cliente_user_id'  => (int) $meta('cliente_user_id'),
        'cliente_nombres'  => $meta('cliente_nombres'),
        'cliente_apellidos'=> $meta('cliente_apellidos'),
        'cliente_telefono' => $meta('cliente_telefono'),
        'cliente_email'    => $meta('cliente_email'),
        'cliente_notas'    => $meta('cliente_notas'),
        'servicio_id'      => (int) $meta('servicio_id'),
        'servicio_nombre'  => $meta('servicio_nombre'),
        'barbero_id'       => (int) $meta('barbero_id'),
        'barbero_nombre'   => $meta('barbero_nombre'),
        'fecha'            => $fecha,
        'hora'             => $hora,
        'hora_fin'         => $hora_fin,
        'hora_label'       => $hora_label,
        'dia'              => $dia,
        'mes'              => $mes,
        'precio'           => $meta('precio'),
        'duracion'         => $duracion,
        'metodo_pago'         => $meta('metodo_pago'),
        'descripcion'         => $meta('descripcion_estilo'),
        'imagen'              => $imagen_url,
        'origen'              => $meta('origen') !== '' ? $meta('origen') : (((int) $meta('cliente_user_id') > 0) ? 'web' : 'admin'),
        'notas_internas'      => $meta('notas_internas'),
        'codigo_operacion'    => $meta('codigo_operacion'),
        'comprobante_id'      => (int) $meta('comprobante_id'),
        'comprobante_url'     => ((int) $meta('comprobante_id') > 0) ? (string) wp_get_attachment_url((int) $meta('comprobante_id')) : '',
        'pago_verificado'     => $meta('pago_verificado') === '1',
        'culqi_charge_id'     => $meta('culqi_charge_id'),
        'pago_proveedor'      => $meta('pago_proveedor'),
        'whatsapp_url'        => $meta('whatsapp_url'),
        'estado_label'        => function_exists('yuniorrojas_reserva_estado_label_cliente')
            ? yuniorrojas_reserva_estado_label_cliente($meta('estado') !== '' ? $meta('estado') : 'confirmada')
            : ($meta('estado') !== '' ? $meta('estado') : 'confirmada'),
    );
}

/**
 * ¿Método de pago que se verifica manualmente (Plin, transferencia, etc.)?
 */
function yuniorrojas_reserva_es_pago_manual(string $metodo): bool
{
    $metodo = sanitize_key($metodo);

    return in_array($metodo, array('plin', 'yape', 'transferencia', 'manual'), true);
}

/**
 * El cliente puede adjuntar captura de transferencia a esta reserva.
 *
 * @param array<string, mixed> $reserva
 */
function yuniorrojas_cliente_puede_subir_comprobante(array $reserva): bool
{
    $estado = sanitize_key((string) ($reserva['estado'] ?? ''));
    if (!in_array($estado, array('pendiente', 'confirmada'), true)) {
        return false;
    }
    if (!empty($reserva['pago_verificado'])) {
        return false;
    }
    $metodo = sanitize_key((string) ($reserva['metodo_pago'] ?? ''));

    return yuniorrojas_reserva_es_pago_manual($metodo);
}

/**
 * Asocia comprobante (attachment) a una reserva del cliente logueado.
 *
 * @return array{ok:bool,comprobante_id:int,comprobante_url:string}|WP_Error
 */
function yuniorrojas_cliente_adjuntar_comprobante(int $reserva_id, int $user_id, int $attachment_id)
{
    $reserva_id    = absint($reserva_id);
    $user_id       = absint($user_id);
    $attachment_id = absint($attachment_id);

    if ($reserva_id <= 0 || $user_id <= 0 || $attachment_id <= 0) {
        return new WP_Error('comprobante', 'Datos de comprobante incompletos.', array('status' => 400));
    }

    $reserva = yuniorrojas_obtener_reserva($reserva_id);
    if (!is_array($reserva)) {
        return new WP_Error('reserva', 'Reserva no encontrada.', array('status' => 404));
    }

    if ((int) ($reserva['cliente_user_id'] ?? 0) !== $user_id) {
        return new WP_Error('permiso', 'No puedes modificar esta reserva.', array('status' => 403));
    }

    if (!yuniorrojas_cliente_puede_subir_comprobante($reserva)) {
        return new WP_Error(
            'comprobante',
            'Esta reserva no admite subida de comprobante (ya está verificada o no es pago manual).',
            array('status' => 400)
        );
    }

    if (get_post_type($attachment_id) !== 'attachment') {
        return new WP_Error('comprobante', 'Archivo no válido.', array('status' => 400));
    }

    $mime = (string) get_post_mime_type($attachment_id);
    if ($mime === '' || strpos($mime, 'image/') !== 0) {
        return new WP_Error('comprobante', 'El comprobante debe ser una imagen (JPG, PNG o WEBP).', array('status' => 400));
    }

    update_post_meta($reserva_id, yuniorrojas_reserva_meta_key('comprobante_id'), (string) $attachment_id);

    $url = (string) wp_get_attachment_url($attachment_id);

    return array(
        'ok'              => true,
        'comprobante_id'  => $attachment_id,
        'comprobante_url' => $url,
        'message'         => 'Comprobante subido. El estudio lo revisará para verificar tu pago.',
    );
}

/**
 * ¿La reserva pertenece a un cliente con cuenta WP?
 */
function yuniorrojas_reserva_es_cliente_registrado(array $reserva): bool
{
    return (int) ($reserva['cliente_user_id'] ?? 0) > 0;
}

/**
 * Crea o reprograma una reserva confirmada.
 *
 * @param array<string, mixed> $data
 * @return int|WP_Error
 */
function yuniorrojas_crear_reserva(array $data)
{
    $servicio_id = isset($data['servicio_id']) ? (int) $data['servicio_id'] : 0;
    $barbero_id  = isset($data['barbero_id']) ? (int) $data['barbero_id'] : 0;
    $fecha       = isset($data['fecha']) ? sanitize_text_field((string) $data['fecha']) : '';
    $hora_raw    = isset($data['hora']) ? sanitize_text_field((string) $data['hora']) : '';
    $hora        = yuniorrojas_parsear_hora_cita($hora_raw);

    $nombres   = isset($data['nombres']) ? sanitize_text_field((string) $data['nombres']) : '';
    $apellidos = isset($data['apellidos']) ? sanitize_text_field((string) $data['apellidos']) : '';
    $telefono  = isset($data['telefono']) ? sanitize_text_field((string) $data['telefono']) : '';
    $email     = isset($data['email']) ? sanitize_email((string) $data['email']) : '';
    $notas     = isset($data['notas']) ? sanitize_textarea_field((string) $data['notas']) : '';
    $metodo    = isset($data['metodo_pago']) ? sanitize_key((string) $data['metodo_pago']) : 'estudio';
    $medio_id  = isset($data['medio_pago_id']) ? absint($data['medio_pago_id']) : 0;
    $codigo_operacion = isset($data['codigo_operacion']) ? sanitize_text_field((string) $data['codigo_operacion']) : '';
    $comprobante_id   = isset($data['comprobante_id']) ? absint($data['comprobante_id']) : 0;
    $culqi_token      = isset($data['culqi_token']) ? sanitize_text_field((string) $data['culqi_token']) : '';
    $reprogramar_id = isset($data['reprogramar_id']) ? (int) $data['reprogramar_id'] : 0;
    $actual         = null;

    // Resolver medio dinámico (CPT) → tipo de cobro.
    $medio = null;
    if (function_exists('yuniorrojas_obtener_medio_pago') && $medio_id > 0) {
        $medio = yuniorrojas_obtener_medio_pago($medio_id);
    }
    if ($medio === null && function_exists('yuniorrojas_obtener_medio_pago_por_clave')) {
        $medio = yuniorrojas_obtener_medio_pago_por_clave($metodo);
    }

    $tipo_cobro = 'estudio';
    if (is_array($medio)) {
        $tipo_cobro = (string) ($medio['tipo'] ?? 'manual');
        $metodo     = (string) ($medio['slug'] ?? $metodo);
        if (!empty($medio['id'])) {
            $medio_id = (int) $medio['id'];
        }
    } else {
        // Fallback sin CPT.
        if ($metodo === 'efectivo' || $metodo === '') {
            $metodo     = 'estudio';
            $tipo_cobro = 'estudio';
        } elseif (in_array($metodo, array('tarjeta', 'culqi'), true)) {
            $metodo     = 'culqi';
            $tipo_cobro = 'culqi';
        } elseif (in_array($metodo, array('yape', 'plin', 'transferencia'), true)) {
            $metodo     = $metodo === 'yape' ? 'plin' : $metodo;
            $tipo_cobro = 'manual';
        } elseif ($metodo === 'estudio') {
            $tipo_cobro = 'estudio';
        } else {
            $tipo_cobro = 'manual';
        }
    }

    $user_id = is_user_logged_in() ? (int) get_current_user_id() : 0;
    if ($user_id <= 0) {
        return new WP_Error(
            'auth',
            'Debes iniciar sesión con tu cuenta de cliente para reservar.',
            array('status' => 401)
        );
    }

    if (!yuniorrojas_es_cliente(wp_get_current_user())) {
        return new WP_Error(
            'prohibido',
            'Solo los clientes pueden reservar desde la web.',
            array('status' => 403)
        );
    }

    // Preferir datos de la cuenta del cliente.
    $user = wp_get_current_user();
    $first = trim((string) $user->first_name);
    $last  = trim((string) $user->last_name);
    $display = trim((string) $user->display_name);
    if ($last === '') {
        $fuente = $first !== '' ? $first : $display;
        if ($fuente !== '') {
            $parts = preg_split('/\s+/', $fuente, 2);
            $first = (string) ($parts[0] ?? '');
            $last  = (string) ($parts[1] ?? '');
        }
    }
    if ($first !== '') {
        $nombres = $first;
    } elseif ($nombres === '' && $display !== '') {
        $nombres = $display;
    }
    if ($last !== '') {
        $apellidos = $last;
    }
    $email = (string) $user->user_email;
    $tel_meta = (string) get_user_meta($user_id, 'telefono', true);
    if ($tel_meta === '') {
        $tel_meta = (string) get_user_meta($user_id, 'whatsapp', true);
    }
    if ($tel_meta !== '') {
        $telefono = $tel_meta;
    }

    if ($servicio_id <= 0 || get_post_type($servicio_id) !== YUNIORROJAS_CPT_SERVICIOS) {
        return new WP_Error('servicio_invalido', 'Servicio no válido.', array('status' => 400));
    }
    if ($barbero_id <= 0 || get_post_type($barbero_id) !== 'barberos') {
        return new WP_Error('barbero_invalido', 'Barbero no válido.', array('status' => 400));
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        return new WP_Error('fecha_invalida', 'Fecha no válida.', array('status' => 400));
    }
    if ($hora === '') {
        return new WP_Error('hora_invalida', 'Hora no válida.', array('status' => 400));
    }
    if ($nombres === '' || $apellidos === '') {
        return new WP_Error('cliente_invalido', 'Completa tu nombre y apellidos en Preferencias antes de reservar.', array('status' => 400));
    }
    if ($email === '' || !is_email($email)) {
        return new WP_Error('email_invalido', 'Correo electrónico no válido.', array('status' => 400));
    }
    if ($telefono === '') {
        return new WP_Error('telefono_invalido', 'Agrega tu teléfono en Preferencias antes de reservar.', array('status' => 400));
    }

    // Reprogramar: actualizar la misma reserva (no crear otra).
    if ($reprogramar_id > 0) {
        if (!yuniorrojas_usuario_posee_reserva($reprogramar_id, $user_id)) {
            return new WP_Error('prohibido', 'No puedes reprogramar esta reserva.', array('status' => 403));
        }

        $actual = yuniorrojas_obtener_reserva($reprogramar_id);
        if ($actual === null) {
            return new WP_Error('no_encontrada', 'Reserva no encontrada.', array('status' => 404));
        }
        if (in_array((string) $actual['estado'], array('cancelada', 'completada', 'no_show'), true)) {
            return new WP_Error('estado_invalido', 'Esta reserva ya no se puede reprogramar.', array('status' => 400));
        }
    }

    $servicio_nombre = get_the_title($servicio_id);
    $barbero_nombre  = get_the_title($barbero_id);
    $precio          = (string) yuniorrojas_field('precio', $servicio_id, '0');
    $duracion        = (int) yuniorrojas_field('tiempo_de_servicio', $servicio_id, 60);
    if ($duracion <= 0) {
        $duracion = 60;
    }

    // Manual: exigir código de operación.
    if ($tipo_cobro === 'manual' && $reprogramar_id <= 0) {
        $codigo_operacion = trim($codigo_operacion);
        if (strlen($codigo_operacion) < 4) {
            return new WP_Error(
                'comprobante',
                'Ingresa el código de operación (mín. 4 caracteres).',
                array('status' => 400)
            );
        }
    }

    // Culqi: token obligatorio (salvo reprogramación sin nuevo cobro).
    if ($tipo_cobro === 'culqi' && $reprogramar_id <= 0) {
        if (!function_exists('yuniorrojas_culqi_esta_configurado') || !yuniorrojas_culqi_esta_configurado()) {
            return new WP_Error(
                'culqi_no_config',
                'Los pagos online (Culqi) no están disponibles ahora. Elige otro medio o pago en el estudio.',
                array('status' => 503)
            );
        }
        if ($culqi_token === '' || !preg_match('/^(tkn|ype|crd)_(test|live)_/', $culqi_token)) {
            return new WP_Error(
                'culqi_token',
                'Completa el pago seguro (tarjeta o Yape) antes de confirmar la reserva.',
                array('status' => 400)
            );
        }
    }

    $conflicto = yuniorrojas_reserva_existe_conflicto($barbero_id, $fecha, $hora, $reprogramar_id, $duracion);
    if ($conflicto) {
        return new WP_Error('horario_ocupado', 'Ese horario ya no está disponible.', array('status' => 409));
    }

    if (function_exists('yuniorrojas_fecha_bloqueada') && yuniorrojas_fecha_bloqueada($barbero_id, $fecha)) {
        return new WP_Error('agenda_bloqueada', 'El barbero no atiende esa fecha (feriado o bloqueo).', array('status' => 409));
    }

    // Cobro Culqi ANTES de crear la reserva.
    $culqi_charge_id = '';
    $culqi_outcome   = '';
    if ($tipo_cobro === 'culqi' && $reprogramar_id <= 0 && $culqi_token !== '') {
        $amount = function_exists('yuniorrojas_precio_a_centimos')
            ? yuniorrojas_precio_a_centimos($precio)
            : (int) round(((float) preg_replace('/[^\d.]/', '', $precio)) * 100);

        if ($amount < 100) {
            return new WP_Error('monto_invalido', 'El precio del servicio no es válido para cobro online.', array('status' => 400));
        }

        $cargo = yuniorrojas_culqi_crear_cargo(array(
            'amount'        => $amount,
            'currency_code' => 'PEN',
            'email'         => $email,
            'source_id'     => $culqi_token,
            'description'   => sprintf('Reserva %s', $servicio_nombre),
            'metadata'      => array(
                'servicio_id' => (string) $servicio_id,
                'barbero_id'  => (string) $barbero_id,
                'fecha'       => $fecha,
                'hora'        => $hora,
                'user_id'     => (string) $user_id,
                'medio'       => (string) $metodo,
            ),
        ));

        if (is_wp_error($cargo)) {
            return $cargo;
        }

        $culqi_charge_id = (string) ($cargo['id'] ?? '');
        $culqi_outcome   = (string) ($cargo['outcome']['type'] ?? 'venta_exitosa');
        if ($culqi_charge_id === '') {
            return new WP_Error('culqi_cargo', 'El pago no devolvió un ID de cargo. Inténtalo de nuevo.', array('status' => 502));
        }
    }

    // Estado según tipo de cobro.
    if ($tipo_cobro === 'estudio') {
        $estado_inicial  = 'confirmada';
        $pago_verificado = '1';
    } elseif ($tipo_cobro === 'culqi' && $culqi_charge_id !== '') {
        $estado_inicial  = 'confirmada';
        $pago_verificado = '1';
    } elseif ($tipo_cobro === 'culqi') {
        $estado_inicial  = 'pendiente';
        $pago_verificado = '0';
    } else {
        $estado_inicial  = 'pendiente';
        $pago_verificado = '0';
    }

    if ($reprogramar_id > 0) {
        $actual_estado = (string) ($actual['estado'] ?? 'confirmada');
        // Al reprogramar se mantiene el estado salvo cancelada.
        $estado_inicial = in_array($actual_estado, array('pendiente', 'confirmada'), true) ? $actual_estado : 'confirmada';
        $pago_verificado = get_post_meta($reprogramar_id, yuniorrojas_reserva_meta_key('pago_verificado'), true) === '1' ? '1' : $pago_verificado;
    }

    $titulo = sprintf(
        '%s — %s %s (%s %s)',
        $servicio_nombre,
        $nombres,
        $apellidos,
        $fecha,
        yuniorrojas_formatear_hora_label($hora)
    );

    if ($reprogramar_id > 0) {
        $updated = wp_update_post(
            array(
                'ID'         => $reprogramar_id,
                'post_title' => $titulo,
            ),
            true
        );
        if (is_wp_error($updated)) {
            return $updated;
        }
        $reserva_id = $reprogramar_id;
    } else {
        $reserva_id = wp_insert_post(
            array(
                'post_type'   => YUNIORROJAS_CPT_RESERVAS,
                'post_status' => 'publish',
                'post_author' => $user_id > 0 ? $user_id : 1,
                'post_title'  => $titulo,
            ),
            true
        );

        if (is_wp_error($reserva_id)) {
            return $reserva_id;
        }
    }

    $metas = array(
        'cliente_user_id'    => (string) $user_id,
        'cliente_nombres'    => $nombres,
        'cliente_apellidos'  => $apellidos,
        'cliente_telefono'   => $telefono,
        'cliente_email'      => $email,
        'cliente_notas'      => $notas,
        'servicio_id'        => (string) $servicio_id,
        'servicio_nombre'    => (string) $servicio_nombre,
        'barbero_id'         => (string) $barbero_id,
        'barbero_nombre'     => (string) $barbero_nombre,
        'fecha'              => $fecha,
        'hora'               => $hora,
        'hora_label'         => yuniorrojas_formatear_hora_label($hora),
        'precio'             => $precio,
        'duracion'           => (string) $duracion,
        'metodo_pago'        => $metodo,
        'medio_pago_id'      => $medio_id > 0 ? (string) $medio_id : '',
        'tipo_cobro'         => $tipo_cobro,
        'estado'             => $estado_inicial,
        'descripcion_estilo' => $notas,
        'origen'             => 'web',
        'pago_verificado'    => $pago_verificado,
    );

    if ($codigo_operacion !== '') {
        $metas['codigo_operacion'] = $codigo_operacion;
    }
    if ($comprobante_id > 0) {
        $metas['comprobante_id'] = (string) $comprobante_id;
    }
    if ($culqi_charge_id !== '') {
        $metas['culqi_charge_id']  = $culqi_charge_id;
        $metas['culqi_outcome']    = $culqi_outcome;
        $metas['pago_proveedor']   = 'culqi';
    }

    if ($reprogramar_id <= 0) {
        $metas['imagen_resultado'] = '';
    }

    foreach ($metas as $key => $value) {
        update_post_meta((int) $reserva_id, yuniorrojas_reserva_meta_key($key), $value);
    }

    if ($user_id > 0 && $telefono !== '') {
        update_user_meta($user_id, 'telefono', $telefono);
        update_user_meta($user_id, 'whatsapp', $telefono);
    }

    if (function_exists('yuniorrojas_notificar_reserva')) {
        yuniorrojas_notificar_reserva((int) $reserva_id, $reprogramar_id > 0 ? 'reprogramada' : 'creada');
    }

    if ($reprogramar_id <= 0 && function_exists('yuniorrojas_admin_notif_desde_reserva')) {
        yuniorrojas_admin_notif_desde_reserva((int) $reserva_id, 'creada');
    }

    return (int) $reserva_id;
}

/**
 * ¿Existe otra reserva activa que solape el slot (por duración)?
 */
function yuniorrojas_reserva_existe_conflicto(
    int $barbero_id,
    string $fecha,
    string $hora,
    int $exclude_id = 0,
    int $duracion = 60
): bool {
    $hora = yuniorrojas_parsear_hora_cita($hora);
    $inicio = function_exists('yuniorrojas_hhmm_a_minutos')
        ? yuniorrojas_hhmm_a_minutos($hora)
        : -1;

    if ($inicio < 0) {
        return false;
    }
    if ($duracion <= 0) {
        $duracion = 60;
    }

    if (!function_exists('yuniorrojas_reservas_activas_dia')) {
        // Fallback legacy (hora exacta) si el módulo no cargó.
        $args = array(
            'post_type'      => YUNIORROJAS_CPT_RESERVAS,
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_query'     => array(
                'relation' => 'AND',
                array(
                    'key'   => yuniorrojas_reserva_meta_key('barbero_id'),
                    'value' => (string) $barbero_id,
                ),
                array(
                    'key'   => yuniorrojas_reserva_meta_key('fecha'),
                    'value' => $fecha,
                ),
                array(
                    'key'   => yuniorrojas_reserva_meta_key('hora'),
                    'value' => $hora,
                ),
                array(
                    'key'     => yuniorrojas_reserva_meta_key('estado'),
                    'value'   => array('cancelada', 'no_show'),
                    'compare' => 'NOT IN',
                ),
            ),
        );
        if ($exclude_id > 0) {
            $args['post__not_in'] = array($exclude_id);
        }
        $q = new WP_Query($args);
        return $q->have_posts();
    }

    $ocupadas = yuniorrojas_reservas_activas_dia($barbero_id, $fecha, $exclude_id);
    return yuniorrojas_slot_choca_con_ocupadas($inicio, $duracion, $ocupadas);
}

/**
 * ¿El método de pago permite cancelación por el cliente?
 * Solo pago en estudio (efectivo) se puede cancelar.
 */
function yuniorrojas_reserva_permite_cancelar_cliente(string $metodo_pago): bool
{
    $metodo = sanitize_key($metodo_pago);
    if ($metodo === 'estudio' || $metodo === 'efectivo' || $metodo === '') {
        return true;
    }
    if (function_exists('yuniorrojas_obtener_medio_pago_por_clave')) {
        $medio = yuniorrojas_obtener_medio_pago_por_clave($metodo);
        if (is_array($medio) && ($medio['tipo'] ?? '') === 'estudio') {
            return true;
        }
    }
    return false;
}

/**
 * Cancela una reserva del cliente.
 *
 * @return true|WP_Error
 */
function yuniorrojas_cancelar_reserva(int $reserva_id, int $user_id)
{
    if (!yuniorrojas_usuario_posee_reserva($reserva_id, $user_id) && !user_can($user_id, 'manage_options')) {
        return new WP_Error('prohibido', 'No puedes cancelar esta reserva.', array('status' => 403));
    }

    $reserva = yuniorrojas_obtener_reserva($reserva_id);
    if ($reserva === null) {
        return new WP_Error('no_encontrada', 'Reserva no encontrada.', array('status' => 404));
    }

    if (in_array($reserva['estado'], array('cancelada', 'completada', 'no_show'), true)) {
        return new WP_Error('estado_invalido', 'Esta reserva ya no se puede cancelar.', array('status' => 400));
    }

    $metodo = (string) ($reserva['metodo_pago'] ?? 'estudio');
    if (!user_can($user_id, 'manage_options') && !yuniorrojas_reserva_permite_cancelar_cliente($metodo)) {
        return new WP_Error(
            'pago_no_reembolsable',
            'No puedes cancelar esta cita porque ya eligiste un pago digital (tarjeta, Yape o Plin). Solo las reservas con pago en el estudio se pueden cancelar.',
            array('status' => 403)
        );
    }

    update_post_meta($reserva_id, yuniorrojas_reserva_meta_key('estado'), 'cancelada');

    if (function_exists('yuniorrojas_notificar_reserva')) {
        yuniorrojas_notificar_reserva($reserva_id, 'cancelada');
    }

    $barbero_id = (int) ($reserva['barbero_id'] ?? 0);
    $fecha_cita = (string) ($reserva['fecha'] ?? '');
    if ($barbero_id > 0 && $fecha_cita !== '' && function_exists('yuniorrojas_lista_espera_avisar_hueco')) {
        yuniorrojas_lista_espera_avisar_hueco($barbero_id, $fecha_cita);
    }

    return true;
}

/**
 * Lista reservas del cliente.
 *
 * @param 'proximas'|'historial'|'todas' $tipo
 * @return array<int, array<string, mixed>>
 */
function yuniorrojas_reservas_cliente(int $user_id, string $tipo = 'todas'): array
{
    if ($user_id <= 0) {
        return array();
    }

    $user = get_userdata($user_id);
    $email = $user instanceof WP_User ? strtolower($user->user_email) : '';

    $meta_query = array(
        'relation' => 'OR',
        array(
            'key'   => yuniorrojas_reserva_meta_key('cliente_user_id'),
            'value' => (string) $user_id,
        ),
    );

    if ($email !== '') {
        $meta_query[] = array(
            'key'   => yuniorrojas_reserva_meta_key('cliente_email'),
            'value' => $email,
        );
    }

    $q = new WP_Query(array(
        'post_type'      => YUNIORROJAS_CPT_RESERVAS,
        'post_status'    => 'publish',
        'posts_per_page' => 50,
        'orderby'        => 'meta_value',
        'meta_key'       => yuniorrojas_reserva_meta_key('fecha'),
        'order'          => $tipo === 'historial' ? 'DESC' : 'ASC',
        'meta_query'     => $meta_query,
    ));

    $hoy = current_time('Y-m-d');
    $ahora = current_time('H:i');
    $items = array();

    foreach ($q->posts as $post) {
        if (!$post instanceof WP_Post) {
            continue;
        }
        $item = yuniorrojas_obtener_reserva((int) $post->ID);
        if ($item === null) {
            continue;
        }

        // Reclamar reserva huérfana por email.
        if ((int) $item['cliente_user_id'] === 0) {
            update_post_meta((int) $post->ID, yuniorrojas_reserva_meta_key('cliente_user_id'), (string) $user_id);
            $item['cliente_user_id'] = $user_id;
        }

        $estado = (string) $item['estado'];
        $fecha  = (string) $item['fecha'];
        $hora   = (string) $item['hora'];
        $es_pasada = ($fecha < $hoy) || ($fecha === $hoy && $hora !== '' && $hora < $ahora);

        if ($tipo === 'proximas') {
            if (in_array($estado, array('cancelada', 'completada', 'no_show'), true)) {
                continue;
            }
            if ($es_pasada) {
                continue;
            }
        }

        if ($tipo === 'historial') {
            if ($estado === 'cancelada') {
                // incluir canceladas recientes opcionalmente; las dejamos fuera del estilo
                continue;
            }
            if (!($estado === 'completada' || $es_pasada)) {
                continue;
            }
            // Autocompletar pasadas confirmadas
            if ($estado === 'confirmada' && $es_pasada) {
                update_post_meta((int) $post->ID, yuniorrojas_reserva_meta_key('estado'), 'completada');
                $item['estado'] = 'completada';
            }
        }

        $items[] = $item;
    }

    return $items;
}

/**
 * Nivel de cliente según visitas completadas.
 *
 * @return array{nombre:string,descripcion:string,progreso:int,faltan:int,siguiente:string,label:string}
 */
function yuniorrojas_nivel_cliente(int $user_id): array
{
    $completadas = 0;
    foreach (yuniorrojas_reservas_cliente($user_id, 'historial') as $item) {
        if (($item['estado'] ?? '') === 'completada') {
            $completadas++;
        }
    }

    $cfg   = function_exists('yuniorrojas_fidelidad_config') ? yuniorrojas_fidelidad_config() : array();
    $clave = function_exists('yuniorrojas_fidelidad_clave_nivel')
        ? yuniorrojas_fidelidad_clave_nivel($completadas)
        : ($completadas >= 12 ? 'platinum' : ($completadas >= 5 ? 'gold' : 'classic'));

    $beneficios = array();
    if (isset($cfg[$clave]['beneficios']) && is_array($cfg[$clave]['beneficios'])) {
        $beneficios = $cfg[$clave]['beneficios'];
    }

    $gold_min     = isset($cfg['gold']['min']) ? (int) $cfg['gold']['min'] : 5;
    $platinum_min = isset($cfg['platinum']['min']) ? (int) $cfg['platinum']['min'] : 12;
    if ($gold_min < 1) {
        $gold_min = 5;
    }
    if ($platinum_min <= $gold_min) {
        $platinum_min = $gold_min + 1;
    }

    if ($clave === 'platinum') {
        return array(
            'clave'       => 'platinum',
            'nombre'      => 'Miembro Platinum',
            'label'       => 'Cliente Platinum',
            'descripcion' => 'Máxima prioridad, beneficios exclusivos y atención preferente.',
            'progreso'    => 100,
            'faltan'      => 0,
            'siguiente'   => 'Platinum',
            'completadas' => $completadas,
            'beneficios'  => $beneficios !== array() ? $beneficios : array(
                'Todo lo de Gold',
                'Máxima prioridad en agenda',
                'Beneficios exclusivos en el estudio',
            ),
        );
    }

    if ($clave === 'gold') {
        $faltan = max(0, $platinum_min - $completadas);
        return array(
            'clave'       => 'gold',
            'nombre'      => 'Miembro Gold',
            'label'       => 'Cliente Gold',
            'descripcion' => 'Disfruta de bebidas premium y prioridad en reservas.',
            'progreso'    => (int) round(min(100, ($completadas / $platinum_min) * 100)),
            'faltan'      => $faltan,
            'siguiente'   => 'Platinum',
            'completadas' => $completadas,
            'beneficios'  => $beneficios !== array() ? $beneficios : array(
                'Todo lo de Classic',
                'Prioridad al reprogramar',
                'Bebida de cortesía',
            ),
        );
    }

    $faltan = max(0, $gold_min - $completadas);
    return array(
        'clave'       => 'classic',
        'nombre'      => 'Miembro Classic',
        'label'       => 'Cliente Classic',
        'descripcion' => 'Acumula visitas para desbloquear beneficios Gold.',
        'progreso'    => (int) round(min(100, ($completadas / max(1, $gold_min)) * 100)),
        'faltan'      => $faltan,
        'siguiente'   => 'Gold',
        'completadas' => $completadas,
        'beneficios'  => $beneficios !== array() ? $beneficios : array(
            'Reserva online 24/7',
            'Historial de cortes',
            'Preferencias de estilo',
        ),
    );
}

/**
 * Datos del panel cliente (reales + fallback vacío).
 *
 * @return array<string, mixed>
 */
function yuniorrojas_cuenta_datos_cliente(int $user_id): array
{
    $theme_uri = get_template_directory_uri();
    $proximas  = yuniorrojas_reservas_cliente($user_id, 'proximas');
    $historial = yuniorrojas_reservas_cliente($user_id, 'historial');
    $nivel     = yuniorrojas_nivel_cliente($user_id);

    $historial_ui = array();
    foreach ($historial as $item) {
        $imagen_raw = (string) ($item['imagen'] ?? '');
        $tiene_foto = $imagen_raw !== '' && filter_var($imagen_raw, FILTER_VALIDATE_URL);
        $imagen     = $tiene_foto ? $imagen_raw : ($theme_uri . '/img/logo.png');
        $historial_ui[] = array(
            'id'          => (int) $item['id'],
            'titulo'      => (string) $item['servicio_nombre'],
            'fecha'       => trim(($item['dia'] ?? '') . ' ' . ($item['mes'] ?? '') . ' ' . substr((string) $item['fecha'], 0, 4)),
            'anio'        => substr((string) $item['fecha'], 0, 4),
            'servicio'    => sanitize_title((string) $item['servicio_nombre']),
            'barbero'     => (string) $item['barbero_nombre'],
            'descripcion' => (string) ($item['descripcion'] !== '' ? $item['descripcion'] : 'Servicio completado en Junior Rojas Barber Studio.'),
            'imagen'      => $imagen,
            'tiene_foto'  => $tiene_foto,
            'cta'         => 'solid',
            'servicio_id' => (int) $item['servicio_id'],
            'barbero_id'  => (int) $item['barbero_id'],
        );
    }

    $proximas_ui = array();
    foreach ($proximas as $item) {
        $metodo = sanitize_key((string) ($item['metodo_pago'] ?? 'estudio'));
        $estado = (string) ($item['estado'] ?? 'confirmada');
        $comp_id = (int) ($item['comprobante_id'] ?? 0);
        $comp_url = (string) ($item['comprobante_url'] ?? '');
        $puede_comprobante = yuniorrojas_cliente_puede_subir_comprobante($item);
        $proximas_ui[] = array(
            'id'                     => (int) $item['id'],
            'dia'                    => (string) ($item['dia'] ?? ''),
            'mes'                    => (string) ($item['mes'] ?? ''),
            'servicio'               => (string) ($item['servicio_nombre'] ?? ''),
            'hora'                   => (string) ($item['hora_label'] ?? $item['hora'] ?? ''),
            'barbero'                => (string) ($item['barbero_nombre'] ?? ''),
            'precio'                 => (string) ($item['precio'] ?? ''),
            'servicio_id'            => (int) ($item['servicio_id'] ?? 0),
            'barbero_id'             => (int) ($item['barbero_id'] ?? 0),
            'estado'                 => $estado,
            'estado_label'           => function_exists('yuniorrojas_reserva_estado_label_cliente')
                ? yuniorrojas_reserva_estado_label_cliente($estado)
                : $estado,
            'metodo_pago'            => $metodo !== '' ? $metodo : 'estudio',
            'metodo_pago_label'      => function_exists('yuniorrojas_reserva_metodo_pago_label')
                ? yuniorrojas_reserva_metodo_pago_label($metodo !== '' ? $metodo : 'estudio')
                : $metodo,
            'codigo_operacion'       => (string) ($item['codigo_operacion'] ?? ''),
            'pago_verificado'        => !empty($item['pago_verificado']),
            'comprobante_id'         => $comp_id,
            'comprobante_url'        => $comp_url,
            'puede_subir_comprobante'=> $puede_comprobante,
            'puede_cancelar'         => yuniorrojas_reserva_permite_cancelar_cliente($metodo),
            'puede_reprogramar'      => in_array($estado, array('pendiente', 'confirmada'), true),
        );
    }

    $estilo_id = (int) get_user_meta($user_id, 'jr_estilo_referencia_id', true);
    if ($estilo_id <= 0) {
        // Compatibilidad: meta antigua guardaba slugs (pompadour/fade), no IDs de galería.
        $estilos_legacy = (array) get_user_meta($user_id, 'jr_estilos_preferidos', true);
        if ($estilos_legacy !== array() && isset($estilos_legacy[0]) && is_numeric($estilos_legacy[0])) {
            $estilo_id = (int) $estilos_legacy[0];
        }
    }

    $estilos = yuniorrojas_estilos_referencia_para_cliente();
    foreach ($estilos as $index => $estilo) {
        $estilos[$index]['selected'] = ((int) ($estilo['id'] ?? 0) === $estilo_id && $estilo_id > 0);
    }

    return array(
        'nivel'     => $nivel,
        'proximas'  => $proximas_ui,
        'historial' => $historial_ui,
        'estilos'   => $estilos,
        'notas_barbero' => (string) get_user_meta($user_id, 'jr_notas_barbero', true),
    );
}

/**
 * Actualiza preferencias del cliente.
 *
 * @param array<string, mixed> $data
 * @return true|WP_Error
 */
function yuniorrojas_actualizar_preferencias_cliente(int $user_id, array $data)
{
    if ($user_id <= 0) {
        return new WP_Error('auth', 'Debes iniciar sesión.', array('status' => 401));
    }

    $nombre   = isset($data['nombre']) ? sanitize_text_field((string) $data['nombre']) : '';
    $email    = isset($data['email']) ? sanitize_email((string) $data['email']) : '';
    $telefono = isset($data['telefono']) ? sanitize_text_field((string) $data['telefono']) : '';
    $notas    = isset($data['notas_barbero']) ? sanitize_textarea_field((string) $data['notas_barbero']) : '';
    $estilo_id = isset($data['estilo_id']) ? absint($data['estilo_id']) : 0;

    $pass_actual = isset($data['password_actual']) ? (string) $data['password_actual'] : '';
    $pass_nueva  = isset($data['password_nueva']) ? (string) $data['password_nueva'] : '';
    $pass_confirm= isset($data['password_confirm']) ? (string) $data['password_confirm'] : '';

    $user = get_userdata($user_id);
    if (!$user instanceof WP_User) {
        return new WP_Error('usuario', 'Usuario no válido.', array('status' => 400));
    }

    $update = array('ID' => $user_id);

    if ($nombre !== '') {
        $update['display_name'] = $nombre;
        $update['first_name']   = $nombre;
    }

    if ($email !== '') {
        if (!is_email($email)) {
            return new WP_Error('email', 'Correo no válido.', array('status' => 400));
        }
        $exists = email_exists($email);
        if ($exists && (int) $exists !== $user_id) {
            return new WP_Error('email', 'Ese correo ya está en uso.', array('status' => 400));
        }
        $update['user_email'] = $email;
    }

    if ($pass_nueva !== '' || $pass_confirm !== '' || $pass_actual !== '') {
        if ($pass_actual === '' || !wp_check_password($pass_actual, $user->user_pass, $user_id)) {
            return new WP_Error('password', 'La contraseña actual no es correcta.', array('status' => 400));
        }
        if (strlen($pass_nueva) < 8) {
            return new WP_Error('password', 'La nueva contraseña debe tener al menos 8 caracteres.', array('status' => 400));
        }
        if ($pass_nueva !== $pass_confirm) {
            return new WP_Error('password', 'Las contraseñas no coinciden.', array('status' => 400));
        }
        $update['user_pass'] = $pass_nueva;
    }

    $result = wp_update_user($update);
    if (is_wp_error($result)) {
        return $result;
    }

    if ($telefono !== '') {
        update_user_meta($user_id, 'telefono', $telefono);
        update_user_meta($user_id, 'whatsapp', $telefono);
    }

    update_user_meta($user_id, 'jr_notas_barbero', $notas);

    if ($estilo_id > 0) {
        if (!yuniorrojas_estilo_referencia_es_valido($estilo_id)) {
            return new WP_Error(
                'estilo',
                'El estilo de referencia seleccionado no es válido.',
                array('status' => 400)
            );
        }
        update_user_meta($user_id, 'jr_estilo_referencia_id', $estilo_id);
        delete_user_meta($user_id, 'jr_estilos_preferidos');
    }

    return true;
}

/**
 * Guarda una reserva desde el panel wp-admin.
 *
 * @param array<string, mixed> $data
 * @return true|WP_Error
 */
function yuniorrojas_admin_guardar_reserva(int $reserva_id, array $data)
{
    $actual = yuniorrojas_obtener_reserva($reserva_id);
    if ($actual === null) {
        return new WP_Error('no_encontrada', 'Reserva no encontrada.', array('status' => 404));
    }

    $nombres   = isset($data['cliente_nombres']) ? sanitize_text_field((string) $data['cliente_nombres']) : '';
    $apellidos = isset($data['cliente_apellidos']) ? sanitize_text_field((string) $data['cliente_apellidos']) : '';
    $telefono  = isset($data['cliente_telefono']) ? sanitize_text_field((string) $data['cliente_telefono']) : '';
    $email     = isset($data['cliente_email']) ? sanitize_email((string) $data['cliente_email']) : '';
    $notas     = isset($data['cliente_notas']) ? sanitize_textarea_field((string) $data['cliente_notas']) : '';
    $notas_internas = isset($data['notas_internas']) ? sanitize_textarea_field((string) $data['notas_internas']) : '';

    // Cliente registrado: ficha (nombre/tel/correo) no se sobrescribe aquí.
    // Las notas de la cita sí pueden actualizarse (web o llamada).
    if (yuniorrojas_reserva_es_cliente_registrado($actual)) {
        $nombres   = (string) ($actual['cliente_nombres'] ?? $nombres);
        $apellidos = (string) ($actual['cliente_apellidos'] ?? $apellidos);
        $telefono  = (string) ($actual['cliente_telefono'] ?? $telefono);
        $email     = (string) ($actual['cliente_email'] ?? $email);
    }

    $servicio_id = isset($data['servicio_id']) ? (int) $data['servicio_id'] : 0;
    $barbero_id  = isset($data['barbero_id']) ? (int) $data['barbero_id'] : 0;
    $fecha       = isset($data['fecha']) ? sanitize_text_field((string) $data['fecha']) : '';
    $hora_raw    = isset($data['hora']) ? sanitize_text_field((string) $data['hora']) : '';
    $hora        = yuniorrojas_parsear_hora_cita($hora_raw);
    if ($hora === '' && preg_match('/^\d{1,2}:\d{2}$/', $hora_raw)) {
        $hora = function_exists('yuniorrojas_normalizar_hora')
            ? yuniorrojas_normalizar_hora($hora_raw, '')
            : $hora_raw;
    }

    $estado   = isset($data['estado']) ? sanitize_key((string) $data['estado']) : (string) ($actual['estado'] ?? 'confirmada');
    $metodo   = isset($data['metodo_pago']) ? sanitize_key((string) $data['metodo_pago']) : (string) ($actual['metodo_pago'] ?? 'estudio');

    // Precio y duración siempre desde el CPT Servicio (igual que en la web).
    $precio   = '0';
    $duracion = 60;
    if ($servicio_id > 0 && get_post_type($servicio_id) === YUNIORROJAS_CPT_SERVICIOS) {
        $precio   = (string) yuniorrojas_field('precio', $servicio_id, '0');
        $duracion = (int) yuniorrojas_field('tiempo_de_servicio', $servicio_id, 60);
        if ($duracion <= 0) {
            $duracion = 60;
        }
    }

    if ($nombres === '' || $apellidos === '') {
        return new WP_Error('cliente', 'Nombre y apellidos son obligatorios.');
    }
    if ($email === '' || !is_email($email)) {
        return new WP_Error('email', 'Correo electrónico no válido.');
    }
    if ($telefono === '') {
        return new WP_Error('telefono', 'Teléfono obligatorio.');
    }
    if ($servicio_id <= 0 || get_post_type($servicio_id) !== YUNIORROJAS_CPT_SERVICIOS) {
        return new WP_Error('servicio', 'Servicio no válido.');
    }
    if ($barbero_id <= 0 || get_post_type($barbero_id) !== 'barberos') {
        return new WP_Error('barbero', 'Barbero no válido.');
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        return new WP_Error('fecha', 'Fecha no válida.');
    }
    if ($hora === '') {
        return new WP_Error('hora', 'Hora no válida.');
    }
    if (!in_array($estado, yuniorrojas_reserva_estados(), true)) {
        return new WP_Error('estado', 'Estado no válido.');
    }
    if (!in_array($metodo, array('estudio', 'efectivo', 'tarjeta', 'yape', 'plin', 'transferencia'), true)) {
        $metodo = 'estudio';
    }

    if (!in_array($estado, array('cancelada', 'no_show'), true)) {
        if (function_exists('yuniorrojas_fecha_bloqueada') && yuniorrojas_fecha_bloqueada($barbero_id, $fecha)) {
            return new WP_Error('bloqueo', 'Esa fecha está bloqueada para el barbero (feriado / no atiende).');
        }
        if (yuniorrojas_reserva_existe_conflicto($barbero_id, $fecha, $hora, $reserva_id, $duracion)) {
            return new WP_Error('conflicto', 'Ese horario ya está ocupado para el barbero seleccionado.');
        }
    }

    $codigo_operacion = isset($data['codigo_operacion']) ? sanitize_text_field((string) $data['codigo_operacion']) : '';
    $pago_verificado  = isset($data['pago_verificado']) && (string) $data['pago_verificado'] === '1' ? '1' : '0';
    $imagen_id        = isset($data['imagen_resultado_id']) ? absint($data['imagen_resultado_id']) : 0;
    $antes_verificado = !empty($actual['pago_verificado']);

    // Al verificar pago → confirmar cita.
    if ($pago_verificado === '1' && $estado === 'pendiente') {
        $estado = 'confirmada';
    }

    $servicio_nombre = get_the_title($servicio_id);
    $barbero_nombre  = get_the_title($barbero_id);
    $hora_label      = yuniorrojas_formatear_hora_label($hora);

    $titulo = sprintf(
        '%s — %s %s (%s %s)',
        $servicio_nombre,
        $nombres,
        $apellidos,
        $fecha,
        $hora_label
    );

    $updated = wp_update_post(
        array(
            'ID'         => $reserva_id,
            'post_title' => $titulo,
        ),
        true
    );
    if (is_wp_error($updated)) {
        return $updated;
    }

    $metas = array(
        'cliente_nombres'    => $nombres,
        'cliente_apellidos'  => $apellidos,
        'cliente_telefono'   => $telefono,
        'cliente_email'      => $email,
        'cliente_notas'      => $notas,
        'descripcion_estilo' => $notas,
        'notas_internas'     => $notas_internas,
        'servicio_id'        => (string) $servicio_id,
        'servicio_nombre'    => (string) $servicio_nombre,
        'barbero_id'         => (string) $barbero_id,
        'barbero_nombre'     => (string) $barbero_nombre,
        'fecha'              => $fecha,
        'hora'               => $hora,
        'hora_label'         => $hora_label,
        'precio'             => $precio,
        'duracion'           => (string) $duracion,
        'metodo_pago'        => $metodo,
        'estado'             => $estado,
        'codigo_operacion'   => $codigo_operacion,
        'pago_verificado'    => $pago_verificado,
        'imagen_resultado'   => $imagen_id > 0 ? (string) $imagen_id : '',
    );

    // Origen: manual si no hay cuenta vinculada.
    $origen_actual = (string) ($actual['origen'] ?? '');
    if ($origen_actual === '' && !yuniorrojas_reserva_es_cliente_registrado($actual)) {
        $metas['origen'] = 'admin';
    }

    foreach ($metas as $key => $value) {
        update_post_meta($reserva_id, yuniorrojas_reserva_meta_key($key), $value);
    }

    if ($pago_verificado === '1' && !$antes_verificado && function_exists('yuniorrojas_notificar_reserva')) {
        yuniorrojas_notificar_reserva($reserva_id, 'pago_verificado');
    }

    return true;
}

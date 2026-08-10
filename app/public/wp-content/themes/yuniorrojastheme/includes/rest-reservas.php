<?php
/**
 * REST: reservas del cliente.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @return void
 */
function yuniorrojas_registrar_rest_reservas(): void
{
    register_rest_route('yuniorrojas/v1', '/reservas', array(
        array(
            'methods'             => 'POST',
            'permission_callback' => static function (): bool {
                return is_user_logged_in() && yuniorrojas_es_cliente();
            },
            'callback'            => 'yuniorrojas_rest_crear_reserva',
            'args'                => array(
                'servicio_id' => array(
                    'required'          => true,
                    'type'              => 'integer',
                    'sanitize_callback' => 'absint',
                ),
                'barbero_id' => array(
                    'required'          => true,
                    'type'              => 'integer',
                    'sanitize_callback' => 'absint',
                ),
                'fecha' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'hora' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'nombres' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'apellidos' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'telefono' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'email' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_email',
                ),
                'notas' => array(
                    'required'          => false,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field',
                    'default'           => '',
                ),
                'metodo_pago' => array(
                    'required'          => false,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_key',
                    'default'           => 'tarjeta',
                ),
                'reprogramar_id' => array(
                    'required'          => false,
                    'type'              => 'integer',
                    'sanitize_callback' => 'absint',
                    'default'           => 0,
                ),
                'codigo_operacion' => array(
                    'required'          => false,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'default'           => '',
                ),
                'comprobante_id' => array(
                    'required'          => false,
                    'type'              => 'integer',
                    'sanitize_callback' => 'absint',
                    'default'           => 0,
                ),
                'culqi_token' => array(
                    'required'          => false,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'default'           => '',
                ),
                'medio_pago_id' => array(
                    'required'          => false,
                    'type'              => 'integer',
                    'sanitize_callback' => 'absint',
                    'default'           => 0,
                ),
            ),
        ),
        array(
            'methods'             => 'GET',
            'permission_callback' => static function (): bool {
                return is_user_logged_in();
            },
            'callback'            => 'yuniorrojas_rest_listar_mis_reservas',
            'args'                => array(
                'tipo' => array(
                    'required'          => false,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_key',
                    'default'           => 'proximas',
                ),
            ),
        ),
    ));

    register_rest_route('yuniorrojas/v1', '/reservas/(?P<id>\d+)/cancelar', array(
        'methods'             => 'POST',
        'permission_callback' => static function (): bool {
            return is_user_logged_in();
        },
        'callback'            => 'yuniorrojas_rest_cancelar_reserva',
        'args'                => array(
            'id' => array(
                'required'          => true,
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
            ),
        ),
    ));

    register_rest_route('yuniorrojas/v1', '/reservas/(?P<id>\d+)/comprobante', array(
        'methods'             => 'POST',
        'permission_callback' => static function (): bool {
            return is_user_logged_in() && function_exists('yuniorrojas_es_cliente') && yuniorrojas_es_cliente();
        },
        'callback'            => 'yuniorrojas_rest_subir_comprobante_reserva',
        'args'                => array(
            'id' => array(
                'required'          => true,
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
            ),
        ),
    ));

    register_rest_route('yuniorrojas/v1', '/cuenta/preferencias', array(
        'methods'             => 'POST',
        'permission_callback' => static function (): bool {
            return is_user_logged_in();
        },
        'callback'            => 'yuniorrojas_rest_guardar_preferencias',
    ));

    register_rest_route('yuniorrojas/v1', '/cuenta/avatar', array(
        array(
            'methods'             => 'POST',
            'permission_callback' => static function (): bool {
                return is_user_logged_in() && function_exists('yuniorrojas_es_cliente') && yuniorrojas_es_cliente();
            },
            'callback'            => 'yuniorrojas_rest_subir_avatar',
        ),
        array(
            'methods'             => 'DELETE',
            'permission_callback' => static function (): bool {
                return is_user_logged_in() && function_exists('yuniorrojas_es_cliente') && yuniorrojas_es_cliente();
            },
            'callback'            => 'yuniorrojas_rest_eliminar_avatar',
        ),
    ));

    register_rest_route('yuniorrojas/v1', '/disponibilidad', array(
        'methods'             => 'GET',
        'permission_callback' => '__return_true',
        'callback'            => 'yuniorrojas_rest_disponibilidad',
        'args'                => array(
            'barbero_id' => array(
                'required'          => true,
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
            ),
            'fecha' => array(
                'required'          => true,
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'servicio_id' => array(
                'required'          => false,
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
                'default'           => 0,
            ),
            'duracion' => array(
                'required'          => false,
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
                'default'           => 0,
            ),
            'exclude_id' => array(
                'required'          => false,
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
                'default'           => 0,
            ),
        ),
    ));

    register_rest_route('yuniorrojas/v1', '/lista-espera', array(
        'methods'             => 'POST',
        'permission_callback' => static function (): bool {
            return is_user_logged_in() && yuniorrojas_es_cliente();
        },
        'callback'            => 'yuniorrojas_rest_lista_espera',
    ));
}
add_action('rest_api_init', 'yuniorrojas_registrar_rest_reservas');

/**
 * Sube comprobante de pago (imagen) si viene en el request.
 *
 * @return int Attachment ID o 0.
 */
function yuniorrojas_rest_subir_comprobante_desde_request(WP_REST_Request $request): int
{
    $files = $request->get_file_params();
    if (!isset($files['comprobante']) || !is_array($files['comprobante'])) {
        return 0;
    }

    $file = $files['comprobante'];
    if (!isset($file['tmp_name']) || !is_uploaded_file((string) $file['tmp_name'])) {
        return 0;
    }

    $max = defined('YUNIORROJAS_COMPROBANTE_MAX_BYTES')
        ? (int) YUNIORROJAS_COMPROBANTE_MAX_BYTES
        : 4 * 1024 * 1024;
    $size = isset($file['size']) ? (int) $file['size'] : 0;
    if ($size <= 0 || $size > $max) {
        return 0;
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $overrides = array(
        'test_form'                => false,
        'mimes'                    => array(
            'jpg|jpeg|jpe' => 'image/jpeg',
            'png'          => 'image/png',
            'webp'         => 'image/webp',
            'gif'          => 'image/gif',
        ),
    );

    $upload = wp_handle_upload($file, $overrides);
    if (isset($upload['error']) || empty($upload['file'])) {
        return 0;
    }

    $attachment = array(
        'post_mime_type' => $upload['type'] ?? 'image/jpeg',
        'post_title'     => sanitize_file_name(basename((string) $upload['file'])),
        'post_content'   => '',
        'post_status'    => 'inherit',
    );

    $attach_id = wp_insert_attachment($attachment, $upload['file']);
    if (is_wp_error($attach_id) || !$attach_id) {
        return 0;
    }

    // Ownership: el uploader es el autor (evita reutilizar IDs ajenos).
    if (is_user_logged_in()) {
        wp_update_post(array(
            'ID'          => (int) $attach_id,
            'post_author' => (int) get_current_user_id(),
        ));
    }

    $meta = wp_generate_attachment_metadata((int) $attach_id, $upload['file']);
    if (is_array($meta)) {
        wp_update_attachment_metadata((int) $attach_id, $meta);
    }

    return (int) $attach_id;
}

/**
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function yuniorrojas_rest_crear_reserva(WP_REST_Request $request)
{
    if (function_exists('yuniorrojas_rate_limit')) {
        $rl = yuniorrojas_rate_limit('rest_reservas_post', 8, 10 * MINUTE_IN_SECONDS);
        if (is_wp_error($rl)) {
            return $rl;
        }
    }

    $reprogramar_id = (int) $request->get_param('reprogramar_id');
    $comprobante_id = yuniorrojas_rest_subir_comprobante_desde_request($request);
    if ($comprobante_id <= 0) {
        $comprobante_id = absint($request->get_param('comprobante_id'));
        if ($comprobante_id > 0 && function_exists('yuniorrojas_validar_attachment_cliente')) {
            $valid = yuniorrojas_validar_attachment_cliente($comprobante_id, (int) get_current_user_id());
            if (is_wp_error($valid)) {
                return $valid;
            }
        }
    }

    $productos_param = $request->get_param('productos');
    $productos       = array();
    if (is_array($productos_param)) {
        $productos = $productos_param;
    } elseif (is_string($productos_param) && $productos_param !== '') {
        $decoded = json_decode(wp_unslash($productos_param), true);
        if (is_array($decoded)) {
            $productos = $decoded;
        }
    }

    $result = yuniorrojas_crear_reserva(array(
        'servicio_id'      => (int) $request->get_param('servicio_id'),
        'barbero_id'       => (int) $request->get_param('barbero_id'),
        'fecha'            => (string) $request->get_param('fecha'),
        'hora'             => (string) $request->get_param('hora'),
        'nombres'          => (string) $request->get_param('nombres'),
        'apellidos'        => (string) $request->get_param('apellidos'),
        'telefono'         => (string) $request->get_param('telefono'),
        'email'            => (string) $request->get_param('email'),
        'notas'            => (string) $request->get_param('notas'),
        'metodo_pago'      => (string) $request->get_param('metodo_pago'),
        'reprogramar_id'   => $reprogramar_id,
        'codigo_operacion' => (string) $request->get_param('codigo_operacion'),
        'comprobante_id'   => $comprobante_id,
        'culqi_token'      => (string) $request->get_param('culqi_token'),
        'medio_pago_id'    => absint($request->get_param('medio_pago_id')),
        'productos'        => $productos,
    ));

    if (is_wp_error($result)) {
        return $result;
    }

    $reserva = yuniorrojas_obtener_reserva((int) $result);
    $estado  = is_array($reserva) ? (string) ($reserva['estado'] ?? '') : '';
    $metodo  = is_array($reserva) ? (string) ($reserva['metodo_pago'] ?? '') : '';
    $pagado  = is_array($reserva) && !empty($reserva['pago_verificado']);

    if ($reprogramar_id > 0) {
        $msg = 'Reserva reprogramada correctamente.';
    } elseif ($pagado && in_array($metodo, array('tarjeta', 'culqi'), true)) {
        $msg = 'Pago online (tarjeta / Yape) aprobado. Tu cita está confirmada.';
    } elseif (in_array($metodo, array('estudio', 'efectivo', ''), true)) {
        $msg = 'Cita confirmada. El pago se realiza al llegar al estudio.';
    } elseif ($estado === 'pendiente') {
        $msg = 'Reserva registrada. Tu pago está pendiente de verificación del estudio.';
    } else {
        $msg = 'Reserva confirmada correctamente.';
    }

    return new WP_REST_Response(array(
        'ok'           => true,
        'id'           => (int) $result,
        'reprogramada' => $reprogramar_id > 0,
        'reserva'      => $reserva,
        'message'      => $msg,
        'whatsapp_url' => is_array($reserva) ? (string) ($reserva['whatsapp_url'] ?? '') : '',
        'cuenta'       => is_user_logged_in() ? yuniorrojas_url_cuenta() : yuniorrojas_url_login(),
    ), $reprogramar_id > 0 ? 200 : 201);
}

/**
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function yuniorrojas_rest_listar_mis_reservas(WP_REST_Request $request): WP_REST_Response
{
    $tipo = (string) $request->get_param('tipo');
    if (!in_array($tipo, array('proximas', 'historial', 'todas'), true)) {
        $tipo = 'proximas';
    }

    $items = yuniorrojas_reservas_cliente((int) get_current_user_id(), $tipo);

    return new WP_REST_Response(array(
        'ok'    => true,
        'items' => $items,
    ), 200);
}

/**
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function yuniorrojas_rest_cancelar_reserva(WP_REST_Request $request)
{
    $id = (int) $request->get_param('id');
    $result = yuniorrojas_cancelar_reserva($id, (int) get_current_user_id());

    if (is_wp_error($result)) {
        return $result;
    }

    return new WP_REST_Response(array(
        'ok'      => true,
        'id'      => $id,
        'message' => 'Reserva cancelada.',
    ), 200);
}

/**
 * Cliente: sube captura de pago (Plin / transferencia) a una reserva propia.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function yuniorrojas_rest_subir_comprobante_reserva(WP_REST_Request $request)
{
    $reserva_id = (int) $request->get_param('id');
    $attach_id  = yuniorrojas_rest_subir_comprobante_desde_request($request);

    if ($attach_id <= 0) {
        return new WP_Error(
            'comprobante',
            'Selecciona una imagen del comprobante (JPG, PNG o WEBP, máx. 4 MB).',
            array('status' => 400)
        );
    }

    $result = yuniorrojas_cliente_adjuntar_comprobante(
        $reserva_id,
        (int) get_current_user_id(),
        $attach_id
    );

    if (is_wp_error($result)) {
        return $result;
    }

    return new WP_REST_Response($result, 200);
}

/**
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function yuniorrojas_rest_guardar_preferencias(WP_REST_Request $request)
{
    $params = $request->get_json_params();
    if (!is_array($params)) {
        $params = $request->get_params();
    }

    $result = yuniorrojas_actualizar_preferencias_cliente((int) get_current_user_id(), (array) $params);

    if (is_wp_error($result)) {
        return $result;
    }

    return new WP_REST_Response(array(
        'ok'      => true,
        'message' => 'Preferencias actualizadas.',
    ), 200);
}

/**
 * Sube foto de perfil del cliente (reemplaza y borra la anterior).
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function yuniorrojas_rest_subir_avatar(WP_REST_Request $request)
{
    $user_id = (int) get_current_user_id();
    $files   = $request->get_file_params();
    $file    = isset($files['avatar']) && is_array($files['avatar']) ? $files['avatar'] : null;

    if ($file === null || empty($file['tmp_name']) || !is_uploaded_file((string) $file['tmp_name'])) {
        return new WP_Error(
            'avatar',
            'Selecciona una imagen de perfil (JPG, PNG o WEBP).',
            array('status' => 400)
        );
    }

    $size = isset($file['size']) ? (int) $file['size'] : 0;
    if ($size > 4 * 1024 * 1024) {
        return new WP_Error('avatar', 'La imagen no debe superar 4 MB.', array('status' => 400));
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $overrides = array(
        'test_form' => false,
        'mimes'     => array(
            'jpg|jpeg|jpe' => 'image/jpeg',
            'png'          => 'image/png',
            'webp'         => 'image/webp',
        ),
    );

    $upload = wp_handle_upload($file, $overrides);
    if (isset($upload['error']) || empty($upload['file'])) {
        $msg = isset($upload['error']) ? (string) $upload['error'] : 'No se pudo subir la imagen.';
        return new WP_Error('avatar', $msg, array('status' => 400));
    }

    $attachment = array(
        'post_mime_type' => $upload['type'] ?? 'image/jpeg',
        'post_title'     => 'avatar-cliente-' . $user_id,
        'post_content'   => '',
        'post_status'    => 'inherit',
        'post_author'    => $user_id,
    );

    $attach_id = wp_insert_attachment($attachment, $upload['file']);
    if (is_wp_error($attach_id) || !$attach_id) {
        @unlink((string) $upload['file']);
        return new WP_Error('avatar', 'No se pudo guardar la imagen.', array('status' => 500));
    }

    $meta = wp_generate_attachment_metadata((int) $attach_id, $upload['file']);
    if (is_array($meta)) {
        wp_update_attachment_metadata((int) $attach_id, $meta);
    }

    $result = yuniorrojas_cliente_actualizar_avatar($user_id, (int) $attach_id);
    if (is_wp_error($result)) {
        // Limpia el archivo recién subido si no se asoció.
        wp_delete_attachment((int) $attach_id, true);
        return $result;
    }

    return new WP_REST_Response($result, 200);
}

/**
 * Elimina foto de perfil del cliente.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function yuniorrojas_rest_eliminar_avatar(WP_REST_Request $request)
{
    unset($request);
    $result = yuniorrojas_cliente_eliminar_avatar((int) get_current_user_id());
    if (is_wp_error($result)) {
        return $result;
    }

    return new WP_REST_Response($result, 200);
}

/**
 * Slots libres para un día.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function yuniorrojas_rest_disponibilidad(WP_REST_Request $request)
{
    $barbero_id  = (int) $request->get_param('barbero_id');
    $fecha       = (string) $request->get_param('fecha');
    $servicio_id = (int) $request->get_param('servicio_id');
    $duracion    = (int) $request->get_param('duracion');
    $exclude_id  = (int) $request->get_param('exclude_id');

    if ($barbero_id <= 0 || get_post_type($barbero_id) !== 'barberos') {
        return new WP_Error('barbero', 'Barbero no válido.', array('status' => 400));
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        return new WP_Error('fecha', 'Fecha no válida.', array('status' => 400));
    }

    if ($duracion <= 0 && $servicio_id > 0) {
        $duracion = (int) yuniorrojas_field('tiempo_de_servicio', $servicio_id, 60);
    }
    if ($duracion <= 0) {
        $duracion = 60;
    }

    $slots = yuniorrojas_calcular_slots_disponibles($barbero_id, $fecha, $duracion, $exclude_id);

    return new WP_REST_Response(array(
        'ok'    => true,
        'slots' => $slots,
    ), 200);
}

/**
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function yuniorrojas_rest_lista_espera(WP_REST_Request $request)
{
    $params = $request->get_json_params();
    if (!is_array($params)) {
        $params = $request->get_params();
    }

    $result = yuniorrojas_lista_espera_crear(array(
        'user_id'     => (int) get_current_user_id(),
        'barbero_id'  => (int) ($params['barbero_id'] ?? 0),
        'servicio_id' => (int) ($params['servicio_id'] ?? 0),
        'fecha'       => (string) ($params['fecha'] ?? ''),
        'telefono'    => (string) ($params['telefono'] ?? ''),
        'email'       => (string) ($params['email'] ?? ''),
    ));

    if (is_wp_error($result)) {
        return $result;
    }

    return new WP_REST_Response(array(
        'ok'      => true,
        'id'      => (int) $result,
        'message' => 'Te avisaremos por email cuando haya un hueco.',
    ), 201);
}


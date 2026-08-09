<?php
/**
 * Servicio: medios de pago dinámicos para reservas.
 *
 * CPT: jr_medio_pago
 * Tipos: culqi | manual | estudio
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Tipos de medio soportados.
 *
 * @return array<string, string>
 */
function yuniorrojas_medios_pago_tipos(): array
{
    return array(
        'culqi'   => __('Culqi (tarjeta / Yape online)', YUNIORROJAS_TEXT_DOMAIN),
        'manual'  => __('Transferencia manual (Plin, banco…)', YUNIORROJAS_TEXT_DOMAIN),
        'estudio' => __('Pago en el estudio', YUNIORROJAS_TEXT_DOMAIN),
    );
}

/**
 * Prefijo meta de medio.
 */
function yuniorrojas_medio_pago_meta_key(string $campo): string
{
    return '_jr_medio_' . $campo;
}

/**
 * @return array{
 *     id:int,slug:string,nombre:string,tipo:string,activo:bool,orden:int,
 *     icono:string,descripcion:string,instrucciones:string,
 *     numero:string,titular:string,qr_id:int,qr_url:string,
 *     banco_nombre:string,banco_cuenta:string,banco_cci:string,banco_titular:string,
 *     requiere_codigo:bool,abre_culqi:bool
 * }|null
 */
function yuniorrojas_obtener_medio_pago(int $id): ?array
{
    $post = get_post($id);
    if (!$post instanceof WP_Post || $post->post_type !== YUNIORROJAS_CPT_MEDIOS_PAGO) {
        return null;
    }

    return yuniorrojas_medio_pago_desde_post($post);
}

/**
 * Busca por slug o por tipos legacy (tarjeta, plin, estudio, yape).
 *
 * @return array<string, mixed>|null
 */
function yuniorrojas_obtener_medio_pago_por_clave(string $clave): ?array
{
    $clave = sanitize_key($clave);
    if ($clave === '') {
        return null;
    }

    // Compat: claves antiguas del checkout hardcodeado.
    $legacy = array(
        'tarjeta'       => 'culqi',
        'yape'          => 'plin',
        'transferencia' => 'plin',
        'efectivo'      => 'estudio',
    );
    if (isset($legacy[$clave])) {
        $clave = $legacy[$clave];
    }

    $posts = get_posts(array(
        'post_type'      => YUNIORROJAS_CPT_MEDIOS_PAGO,
        'post_status'    => array('publish', 'draft'),
        'name'           => $clave,
        'posts_per_page' => 1,
    ));
    if (!empty($posts[0]) && $posts[0] instanceof WP_Post) {
        return yuniorrojas_medio_pago_desde_post($posts[0]);
    }

    // Fallback: un medio del tipo indicado.
    if (in_array($clave, array('culqi', 'manual', 'estudio', 'plin'), true)) {
        $tipo_busqueda = $clave === 'plin' ? 'manual' : $clave;
        foreach (yuniorrojas_medios_pago_todos(true) as $medio) {
            if (($medio['tipo'] ?? '') === $tipo_busqueda) {
                return $medio;
            }
            if ($clave === 'plin' && ($medio['slug'] ?? '') === 'plin') {
                return $medio;
            }
        }
    }

    return null;
}

/**
 * @param WP_Post $post Post.
 * @return array<string, mixed>
 */
function yuniorrojas_medio_pago_desde_post(WP_Post $post): array
{
    $id   = (int) $post->ID;
    $meta = static function (string $key) use ($id): string {
        return (string) get_post_meta($id, yuniorrojas_medio_pago_meta_key($key), true);
    };

    $tipo = sanitize_key($meta('tipo'));
    if (!isset(yuniorrojas_medios_pago_tipos()[$tipo])) {
        $tipo = 'manual';
    }

    $qr_id  = absint($meta('qr_id'));
    $qr_url = $qr_id > 0 ? (string) wp_get_attachment_image_url($qr_id, 'medium') : '';
    $numero = trim($meta('numero'));
    $digits = preg_replace('/\D+/', '', $numero) ?: '';

    if ($qr_url === '' && $digits !== '' && $tipo === 'manual') {
        $qr_url = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&margin=8&data=' . rawurlencode($digits);
    }

    $icono = trim($meta('icono'));
    if ($icono === '') {
        $icono = $tipo === 'culqi' ? 'ti ti-credit-card' : ($tipo === 'estudio' ? 'ti ti-building-store' : 'ti ti-device-mobile');
    }

    $slug = $post->post_name !== '' ? $post->post_name : ('medio-' . $id);

    return array(
        'id'             => $id,
        'slug'           => $slug,
        'nombre'         => $post->post_title !== '' ? $post->post_title : __('Medio de pago', YUNIORROJAS_TEXT_DOMAIN),
        'tipo'           => $tipo,
        'activo'         => $post->post_status === 'publish',
        'orden'          => (int) $post->menu_order,
        'icono'          => $icono,
        'descripcion'    => $meta('descripcion'),
        'instrucciones'  => $meta('instrucciones'),
        'numero'         => $numero,
        'numero_digits'  => $digits,
        'titular'        => trim($meta('titular')),
        'qr_id'          => $qr_id,
        'qr_url'         => $qr_url,
        'banco_nombre'   => trim($meta('banco_nombre')),
        'banco_cuenta'   => trim($meta('banco_cuenta')),
        'banco_cci'      => trim($meta('banco_cci')),
        'banco_titular'  => trim($meta('banco_titular')),
        'requiere_codigo'=> $tipo === 'manual',
        'abre_culqi'     => $tipo === 'culqi',
        'es_estudio'     => $tipo === 'estudio',
    );
}

/**
 * @param bool $solo_activos Solo publicados.
 * @return list<array<string, mixed>>
 */
function yuniorrojas_medios_pago_todos(bool $solo_activos = false): array
{
    yuniorrojas_medios_pago_seed_si_vacio();

    $posts = get_posts(array(
        'post_type'      => YUNIORROJAS_CPT_MEDIOS_PAGO,
        'post_status'    => $solo_activos ? array('publish') : array('publish', 'draft'),
        'posts_per_page' => 50,
        'orderby'        => array(
            'menu_order' => 'ASC',
            'title'      => 'ASC',
        ),
    ));

    $out = array();
    foreach ($posts as $post) {
        if (!$post instanceof WP_Post) {
            continue;
        }
        $medio = yuniorrojas_medio_pago_desde_post($post);
        // Culqi solo si está configurado.
        if ($solo_activos && !empty($medio['abre_culqi']) && function_exists('yuniorrojas_culqi_esta_configurado') && !yuniorrojas_culqi_esta_configurado()) {
            continue;
        }
        $out[] = $medio;
    }

    return $out;
}

/**
 * Medios activos para el checkout público.
 *
 * @return list<array<string, mixed>>
 */
function yuniorrojas_medios_pago_checkout(): array
{
    return yuniorrojas_medios_pago_todos(true);
}

/**
 * Payload seguro para JS (sin secretos).
 *
 * @return list<array<string, mixed>>
 */
function yuniorrojas_medios_pago_checkout_js(): array
{
    $out = array();
    foreach (yuniorrojas_medios_pago_checkout() as $m) {
        $out[] = array(
            'id'              => (int) $m['id'],
            'slug'            => (string) $m['slug'],
            'nombre'          => (string) $m['nombre'],
            'tipo'            => (string) $m['tipo'],
            'abre_culqi'      => !empty($m['abre_culqi']),
            'requiere_codigo' => !empty($m['requiere_codigo']),
            'es_estudio'      => !empty($m['es_estudio']),
        );
    }

    return $out;
}

/**
 * Crea los medios por defecto una sola vez.
 */
function yuniorrojas_medios_pago_seed_si_vacio(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    if (!post_type_exists(YUNIORROJAS_CPT_MEDIOS_PAGO)) {
        return;
    }

    $exists = get_posts(array(
        'post_type'      => YUNIORROJAS_CPT_MEDIOS_PAGO,
        'post_status'    => array('publish', 'draft', 'trash'),
        'posts_per_page' => 1,
        'fields'         => 'ids',
    ));
    if (!empty($exists)) {
        return;
    }

    // Migrar número desde ajustes antiguos.
    $pago_settings = function_exists('yuniorrojas_pagos_settings') ? yuniorrojas_pagos_settings() : array();
    $contacto      = function_exists('yuniorrojas_contacto') ? yuniorrojas_contacto() : array();
    $numero        = trim((string) ($pago_settings['yape_numero'] ?? ''));
    if ($numero === '') {
        $numero = (string) ($contacto['whatsapp'] ?? '');
    }
    $titular = trim((string) ($pago_settings['yape_titular'] ?? ''));
    $qr_id   = absint($pago_settings['yape_qr_id'] ?? 0);

    $defaults = array(
        array(
            'title'   => 'Tarjeta o Yape',
            'slug'    => 'culqi',
            'orden'   => 10,
            'tipo'    => 'culqi',
            'icono'   => 'ti ti-credit-card',
            'desc'    => 'Paga online con Culqi: tarjeta o Yape. Confirmación automática.',
            'instr'   => 'Al continuar se abre el checkout seguro de Culqi. Elige tarjeta o Yape; la cita se confirma al cobro.',
        ),
        array(
            'title'   => 'Plin',
            'slug'    => 'plin',
            'orden'   => 20,
            'tipo'    => 'manual',
            'icono'   => 'ti ti-device-mobile',
            'desc'    => 'Transfiere con Plin al número del estudio y envía el código de operación.',
            'instr'   => 'Abre Plin, envía el monto exacto y regresa con el código de operación. El estudio verificará el pago.',
            'numero'  => $numero,
            'titular' => $titular,
            'qr_id'   => $qr_id,
            'banco_nombre'  => (string) ($pago_settings['banco_nombre'] ?? ''),
            'banco_cuenta'  => (string) ($pago_settings['banco_cuenta'] ?? ''),
            'banco_cci'     => (string) ($pago_settings['banco_cci'] ?? ''),
            'banco_titular' => (string) ($pago_settings['banco_titular'] ?? ''),
        ),
        array(
            'title' => 'Pago en estudio',
            'slug'  => 'estudio',
            'orden' => 30,
            'tipo'  => 'estudio',
            'icono' => 'ti ti-building-store',
            'desc'  => 'Reserva ahora y paga en el local (efectivo o POS).',
            'instr' => 'Tu cita se confirma al reservar. El pago lo realizas cuando llegues al estudio.',
        ),
    );

    foreach ($defaults as $row) {
        $id = wp_insert_post(array(
            'post_type'   => YUNIORROJAS_CPT_MEDIOS_PAGO,
            'post_status' => 'publish',
            'post_title'  => $row['title'],
            'post_name'   => $row['slug'],
            'menu_order'  => (int) $row['orden'],
        ), true);
        if (is_wp_error($id) || !$id) {
            continue;
        }
        update_post_meta((int) $id, yuniorrojas_medio_pago_meta_key('tipo'), $row['tipo']);
        update_post_meta((int) $id, yuniorrojas_medio_pago_meta_key('icono'), $row['icono']);
        update_post_meta((int) $id, yuniorrojas_medio_pago_meta_key('descripcion'), $row['desc']);
        update_post_meta((int) $id, yuniorrojas_medio_pago_meta_key('instrucciones'), $row['instr']);
        if (($row['tipo'] ?? '') === 'manual') {
            update_post_meta((int) $id, yuniorrojas_medio_pago_meta_key('numero'), (string) ($row['numero'] ?? ''));
            update_post_meta((int) $id, yuniorrojas_medio_pago_meta_key('titular'), (string) ($row['titular'] ?? ''));
            update_post_meta((int) $id, yuniorrojas_medio_pago_meta_key('qr_id'), (string) absint($row['qr_id'] ?? 0));
            update_post_meta((int) $id, yuniorrojas_medio_pago_meta_key('banco_nombre'), (string) ($row['banco_nombre'] ?? ''));
            update_post_meta((int) $id, yuniorrojas_medio_pago_meta_key('banco_cuenta'), (string) ($row['banco_cuenta'] ?? ''));
            update_post_meta((int) $id, yuniorrojas_medio_pago_meta_key('banco_cci'), (string) ($row['banco_cci'] ?? ''));
            update_post_meta((int) $id, yuniorrojas_medio_pago_meta_key('banco_titular'), (string) ($row['banco_titular'] ?? ''));
        }
    }

    update_option('yuniorrojas_medios_pago_seeded', '1', false);
}

/**
 * Mapa slug => etiqueta (incluye legacy) para admin.
 *
 * @return array<string, string>
 */
function yuniorrojas_medios_pago_labels_map(): array
{
    $map = array(
        'tarjeta'       => __('Tarjeta / Yape (Culqi)', YUNIORROJAS_TEXT_DOMAIN),
        'yape'          => __('Plin (manual)', YUNIORROJAS_TEXT_DOMAIN),
        'plin'          => __('Plin', YUNIORROJAS_TEXT_DOMAIN),
        'estudio'       => __('Pago en estudio', YUNIORROJAS_TEXT_DOMAIN),
        'efectivo'      => __('Efectivo', YUNIORROJAS_TEXT_DOMAIN),
        'transferencia' => __('Transferencia', YUNIORROJAS_TEXT_DOMAIN),
        'culqi'         => __('Tarjeta / Yape (Culqi)', YUNIORROJAS_TEXT_DOMAIN),
    );

    foreach (yuniorrojas_medios_pago_todos(false) as $m) {
        $slug = (string) ($m['slug'] ?? '');
        if ($slug !== '') {
            $map[$slug] = (string) ($m['nombre'] ?? $slug);
        }
    }

    return $map;
}

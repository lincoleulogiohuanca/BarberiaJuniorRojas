<?php
/**
 * Reseñas de servicios (estrellas + comentario) por clientes.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Prefijo meta de reseña.
 */
function yuniorrojas_resena_meta_key(string $campo): string
{
    return '_jr_resena_' . $campo;
}

/**
 * CPT de reseñas (visible en admin para moderar).
 */
function yuniorrojas_registrar_cpt_resenas(): void
{
    if (post_type_exists(YUNIORROJAS_CPT_RESENAS)) {
        return;
    }

    register_post_type(YUNIORROJAS_CPT_RESENAS, array(
        'labels' => array(
            'name'          => __('Reseñas de servicios', YUNIORROJAS_TEXT_DOMAIN),
            'singular_name' => __('Reseña', YUNIORROJAS_TEXT_DOMAIN),
            'add_new_item'  => __('Añadir reseña', YUNIORROJAS_TEXT_DOMAIN),
            'edit_item'     => __('Editar reseña', YUNIORROJAS_TEXT_DOMAIN),
            'search_items'  => __('Buscar reseñas', YUNIORROJAS_TEXT_DOMAIN),
            'not_found'     => __('No hay reseñas', YUNIORROJAS_TEXT_DOMAIN),
        ),
        'public'              => false,
        'show_ui'             => true,
        'show_in_menu'        => 'edit.php?post_type=' . YUNIORROJAS_CPT_SERVICIOS,
        'capability_type'     => 'post',
        'map_meta_cap'        => true,
        'supports'            => array('title', 'editor', 'author'),
        'has_archive'         => false,
        'exclude_from_search' => true,
        'publicly_queryable'  => false,
        'show_in_rest'        => false,
        'menu_icon'           => 'dashicons-star-filled',
    ));
}
add_action('init', 'yuniorrojas_registrar_cpt_resenas', 12);

/**
 * IDs de usuarios que dieron like a una reseña.
 *
 * @return array<int,int>
 */
function yuniorrojas_resena_likes_ids(int $resena_id): array
{
    $raw = get_post_meta($resena_id, yuniorrojas_resena_meta_key('likes'), true);
    if (!is_array($raw)) {
        return array();
    }

    $ids = array();
    foreach ($raw as $uid) {
        $uid = absint($uid);
        if ($uid > 0) {
            $ids[$uid] = $uid;
        }
    }

    return array_values($ids);
}

/**
 * @return array{
 *   rating:int,
 *   servicio_id:int,
 *   user_id:int,
 *   nombre:string,
 *   texto:string,
 *   fecha:string,
 *   id:int,
 *   likes:int,
 *   liked:bool,
 *   es_propia:bool,
 *   puede_like:bool
 * }|null
 */
function yuniorrojas_resena_formatear(int $resena_id, int $viewer_id = 0): ?array
{
    $post = get_post($resena_id);
    if (!$post instanceof WP_Post || $post->post_type !== YUNIORROJAS_CPT_RESENAS) {
        return null;
    }

    // En listados públicos solo se formatea published (except own pending via caller).
    // Aquí permitimos pending para formatear mi reseña en el formulario.

    $rating = (int) get_post_meta($resena_id, yuniorrojas_resena_meta_key('rating'), true);
    $rating = max(1, min(5, $rating > 0 ? $rating : 5));

    $nombre = trim((string) $post->post_title);
    if ($nombre === '') {
        $nombre = __('Cliente', YUNIORROJAS_TEXT_DOMAIN);
    }

    $author_id = (int) $post->post_author;
    $viewer_id = absint($viewer_id);
    $likes     = yuniorrojas_resena_likes_ids($resena_id);
    $liked     = $viewer_id > 0 && in_array($viewer_id, $likes, true);
    $es_propia = $viewer_id > 0 && $viewer_id === $author_id;

    return array(
        'id'          => $resena_id,
        'servicio_id' => (int) get_post_meta($resena_id, yuniorrojas_resena_meta_key('servicio_id'), true),
        'user_id'     => $author_id,
        'rating'      => $rating,
        'nombre'      => $nombre,
        'texto'       => (string) $post->post_content,
        'fecha'       => get_the_date('d M Y', $post),
        'status'      => (string) $post->post_status,
        'likes'       => count($likes),
        'liked'       => $liked,
        'es_propia'   => $es_propia,
        'puede_like'  => $viewer_id > 0 && !$es_propia && $post->post_status === 'publish',
    );
}

/**
 * Activa o desactiva like (no permite like en reseña propia).
 *
 * @return array{ok:bool,likes:int,liked:bool,resena:array}|WP_Error
 */
function yuniorrojas_resena_toggle_like(int $resena_id, int $user_id)
{
    $resena_id = absint($resena_id);
    $user_id   = absint($user_id);

    if ($user_id <= 0) {
        return new WP_Error('auth', 'Debes iniciar sesión para dar like.', array('status' => 401));
    }

    $post = get_post($resena_id);
    if (!$post instanceof WP_Post || $post->post_type !== YUNIORROJAS_CPT_RESENAS || $post->post_status !== 'publish') {
        return new WP_Error('resena', 'Reseña no encontrada.', array('status' => 404));
    }

    if ((int) $post->post_author === $user_id) {
        return new WP_Error('propia', 'No puedes dar like a tu propia reseña.', array('status' => 403));
    }

    $likes  = yuniorrojas_resena_likes_ids($resena_id);
    $set    = array_fill_keys($likes, true);
    $liked  = false;

    if (isset($set[$user_id])) {
        unset($set[$user_id]);
        $liked = false;
    } else {
        $set[$user_id] = true;
        $liked         = true;
    }

    $new_ids = array_map('intval', array_keys($set));
    update_post_meta($resena_id, yuniorrojas_resena_meta_key('likes'), $new_ids);

    $resena = yuniorrojas_resena_formatear($resena_id, $user_id);
    if ($resena === null) {
        return new WP_Error('resena', 'Reseña no encontrada.', array('status' => 404));
    }

    return array(
        'ok'     => true,
        'likes'  => (int) $resena['likes'],
        'liked'  => $liked,
        'resena' => $resena,
    );
}

/**
 * Lista reseñas publicadas de un servicio.
 *
 * @return array{items:array<int,array>,promedio:float,total:int,mi_resena:?array}
 */
function yuniorrojas_servicio_resenas(int $servicio_id, int $user_id = 0): array
{
    $servicio_id = absint($servicio_id);
    $empty       = array(
        'items'     => array(),
        'promedio'  => 0.0,
        'total'     => 0,
        'mi_resena' => null,
    );

    if ($servicio_id <= 0) {
        return $empty;
    }

    $q = new WP_Query(array(
        'post_type'              => YUNIORROJAS_CPT_RESENAS,
        'post_status'            => 'publish',
        'posts_per_page'         => 30,
        'orderby'                => 'date',
        'order'                  => 'DESC',
        'no_found_rows'          => false,
        'update_post_meta_cache' => true,
        'update_post_term_cache' => false,
        'meta_query'             => array(
            array(
                'key'   => yuniorrojas_resena_meta_key('servicio_id'),
                'value' => (string) $servicio_id,
            ),
        ),
    ));

    $items  = array();
    $suma   = 0;
    $mi     = null;
    $user_id = absint($user_id);

    foreach ($q->posts as $post) {
        if (!$post instanceof WP_Post) {
            continue;
        }
        $row = yuniorrojas_resena_formatear((int) $post->ID, $user_id);
        if ($row === null) {
            continue;
        }
        $items[] = $row;
        $suma   += (int) $row['rating'];
        if ($user_id > 0 && (int) $row['user_id'] === $user_id) {
            $mi = $row;
        }
    }

    $total = count($items);

    // Mi reseña puede estar pendiente de moderación (no sale en el listado público).
    if ($mi === null && $user_id > 0) {
        $own_id = yuniorrojas_servicio_resena_del_usuario($servicio_id, $user_id);
        if ($own_id > 0) {
            $own = yuniorrojas_resena_formatear($own_id, $user_id);
            if ($own !== null) {
                $mi = $own;
            }
        }
    }

    return array(
        'items'     => $items,
        'promedio'  => $total > 0 ? round($suma / $total, 1) : 0.0,
        'total'     => $total,
        'mi_resena' => $mi,
    );
}

/**
 * ID de reseña existente del usuario para un servicio (cualquier estado).
 */
function yuniorrojas_servicio_resena_del_usuario(int $servicio_id, int $user_id): int
{
    $servicio_id = absint($servicio_id);
    $user_id     = absint($user_id);
    if ($servicio_id <= 0 || $user_id <= 0) {
        return 0;
    }

    $q = new WP_Query(array(
        'post_type'              => YUNIORROJAS_CPT_RESENAS,
        'post_status'            => array('publish', 'pending', 'draft'),
        'posts_per_page'         => 1,
        'author'                 => $user_id,
        'fields'                 => 'ids',
        'no_found_rows'          => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
        'meta_query'             => array(
            array(
                'key'   => yuniorrojas_resena_meta_key('servicio_id'),
                'value' => (string) $servicio_id,
            ),
        ),
    ));

    return !empty($q->posts[0]) ? (int) $q->posts[0] : 0;
}

/**
 * Crea o actualiza reseña del cliente logueado.
 *
 * @return array{ok:bool,resena:array,promedio:float,total:int}|WP_Error
 */
function yuniorrojas_servicio_guardar_resena(int $servicio_id, int $user_id, int $rating, string $texto)
{
    $servicio_id = absint($servicio_id);
    $user_id     = absint($user_id);
    $rating      = max(1, min(5, absint($rating)));
    $texto       = trim(wp_strip_all_tags($texto));

    if ($servicio_id <= 0 || get_post_type($servicio_id) !== YUNIORROJAS_CPT_SERVICIOS) {
        return new WP_Error('servicio', 'Servicio no válido.', array('status' => 400));
    }
    if ($user_id <= 0) {
        return new WP_Error('auth', 'Debes iniciar sesión para dejar una reseña.', array('status' => 401));
    }
    if (!function_exists('yuniorrojas_es_cliente') || !yuniorrojas_es_cliente()) {
        return new WP_Error('permiso', 'Solo los clientes pueden dejar reseñas.', array('status' => 403));
    }
    $len = function_exists('mb_strlen') ? mb_strlen($texto) : strlen($texto);
    if ($texto === '' || $len < 8) {
        return new WP_Error('texto', 'Escribe un comentario de al menos 8 caracteres.', array('status' => 400));
    }
    if ($len > 800) {
        return new WP_Error('texto', 'El comentario no debe superar 800 caracteres.', array('status' => 400));
    }

    $user   = get_userdata($user_id);
    $nombre = $user instanceof WP_User ? trim((string) $user->display_name) : '';
    if ($nombre === '') {
        $nombre = __('Cliente', YUNIORROJAS_TEXT_DOMAIN);
    }

    $existente = yuniorrojas_servicio_resena_del_usuario($servicio_id, $user_id);
    $post_status = 'pending'; // Moderación: las nuevas quedan pendientes.
    if ($existente > 0) {
        $st = get_post_status($existente);
        // Si ya estaba publicada, los edites del autor se mantienen publicados.
        $post_status = ($st === 'publish') ? 'publish' : 'pending';
    }

    $postarr   = array(
        'post_type'    => YUNIORROJAS_CPT_RESENAS,
        'post_status'  => $post_status,
        'post_title'   => $nombre,
        'post_content' => $texto,
        'post_author'  => $user_id,
    );

    if ($existente > 0) {
        $postarr['ID'] = $existente;
        $result        = wp_update_post($postarr, true);
    } else {
        $result = wp_insert_post($postarr, true);
    }

    if (is_wp_error($result)) {
        return $result;
    }

    $resena_id = (int) $result;
    update_post_meta($resena_id, yuniorrojas_resena_meta_key('servicio_id'), (string) $servicio_id);
    update_post_meta($resena_id, yuniorrojas_resena_meta_key('rating'), (string) $rating);

    $resena = yuniorrojas_resena_formatear($resena_id, $user_id);
    $stats  = yuniorrojas_servicio_resenas($servicio_id, $user_id);

    $msg = $existente > 0
        ? ($post_status === 'publish'
            ? 'Reseña actualizada. Gracias por tu opinión.'
            : 'Reseña actualizada. Seguirá visible tras la moderación del estudio.')
        : 'Reseña enviada. El estudio la publicará tras revisarla.';

    return array(
        'ok'       => true,
        'message'  => $msg,
        'resena'   => $resena,
        'pending'  => $post_status !== 'publish',
        'promedio' => $stats['promedio'],
        'total'    => $stats['total'],
    );
}

/**
 * REST: reseñas de servicio + like.
 */
function yuniorrojas_registrar_rest_resenas(): void
{
    register_rest_route('yuniorrojas/v1', '/servicios/(?P<id>\d+)/resenas', array(
        array(
            'methods'             => 'GET',
            'permission_callback' => '__return_true',
            'callback'            => 'yuniorrojas_rest_listar_resenas_servicio',
            'args'                => array(
                'id' => array(
                    'required'          => true,
                    'type'              => 'integer',
                    'sanitize_callback' => 'absint',
                ),
            ),
        ),
        array(
            'methods'             => 'POST',
            'permission_callback' => static function (): bool {
                return is_user_logged_in();
            },
            'callback'            => 'yuniorrojas_rest_guardar_resena_servicio',
            'args'                => array(
                'id' => array(
                    'required'          => true,
                    'type'              => 'integer',
                    'sanitize_callback' => 'absint',
                ),
            ),
        ),
    ));

    register_rest_route('yuniorrojas/v1', '/resenas/(?P<id>\d+)/like', array(
        'methods'             => 'POST',
        'permission_callback' => static function (): bool {
            return is_user_logged_in();
        },
        'callback'            => 'yuniorrojas_rest_toggle_like_resena',
        'args'                => array(
            'id' => array(
                'required'          => true,
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
            ),
        ),
    ));
}
add_action('rest_api_init', 'yuniorrojas_registrar_rest_resenas');

/**
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function yuniorrojas_rest_listar_resenas_servicio(WP_REST_Request $request)
{
    $servicio_id = (int) $request->get_param('id');
    $user_id     = is_user_logged_in() ? (int) get_current_user_id() : 0;
    $data        = yuniorrojas_servicio_resenas($servicio_id, $user_id);

    return new WP_REST_Response($data, 200);
}

/**
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function yuniorrojas_rest_guardar_resena_servicio(WP_REST_Request $request)
{
    if (function_exists('yuniorrojas_rate_limit')) {
        $rl = yuniorrojas_rate_limit('rest_resena_post', 12, 15 * MINUTE_IN_SECONDS);
        if (is_wp_error($rl)) {
            return $rl;
        }
    }

    $params = $request->get_json_params();
    if (!is_array($params)) {
        $params = $request->get_params();
    }

    $result = yuniorrojas_servicio_guardar_resena(
        (int) $request->get_param('id'),
        (int) get_current_user_id(),
        isset($params['rating']) ? (int) $params['rating'] : 0,
        isset($params['texto']) ? (string) $params['texto'] : ''
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
function yuniorrojas_rest_toggle_like_resena(WP_REST_Request $request)
{
    $result = yuniorrojas_resena_toggle_like(
        (int) $request->get_param('id'),
        (int) get_current_user_id()
    );

    if (is_wp_error($result)) {
        return $result;
    }

    return new WP_REST_Response($result, 200);
}

/**
 * HTML del botón / contador de likes de una reseña.
 * Visible en todas las reseñas; en la propia solo contador (sin acción).
 *
 * @param array<string,mixed> $item Reseña formateada.
 */
function yuniorrojas_resena_like_html(array $item): string
{
    $id     = absint($item['id'] ?? 0);
    $likes  = max(0, (int) ($item['likes'] ?? 0));
    $liked  = !empty($item['liked']);
    $puede  = !empty($item['puede_like']);
    $propia = !empty($item['es_propia']);
    $icon   = $liked ? 'ti-heart-filled' : 'ti-heart';
    $count  = '<span class="servicio-resenas__like-count" data-like-count>' . esc_html((string) $likes) . '</span>';
    $label  = $liked
        ? __('Quitar me gusta', YUNIORROJAS_TEXT_DOMAIN)
        : __('Me gusta', YUNIORROJAS_TEXT_DOMAIN);

    // Otras reseñas + sesión: botón activo (toggle like).
    if ($puede && $id > 0) {
        $cls = 'servicio-resenas__like' . ($liked ? ' is-liked' : '');
        return '<button type="button" class="' . esc_attr($cls) . '"'
            . ' data-resena-like'
            . ' data-resena-id="' . esc_attr((string) $id) . '"'
            . ' aria-pressed="' . ($liked ? 'true' : 'false') . '"'
            . ' aria-label="' . esc_attr($label) . '"'
            . ' title="' . esc_attr($label) . '">'
            . '<i class="ti ' . esc_attr($icon) . '" aria-hidden="true"></i>'
            . $count
            . '</button>';
    }

    // Propia o visitante: icono + contador sin acción.
    if ($propia) {
        $title = __('Me gusta recibidos', YUNIORROJAS_TEXT_DOMAIN);
    } else {
        $title = __('Inicia sesión para dar me gusta', YUNIORROJAS_TEXT_DOMAIN);
    }
    $cls = 'servicio-resenas__like is-static' . ($likes > 0 ? ' is-liked' : '');
    $heart = $likes > 0 ? 'ti-heart-filled' : 'ti-heart';

    return '<span class="' . esc_attr($cls) . '" title="' . esc_attr($title) . '" aria-label="' . esc_attr($title) . '">'
        . '<i class="ti ' . esc_attr($heart) . '" aria-hidden="true"></i>'
        . $count
        . '</span>';
}

/**
 * Estrellas HTML (solo visual).
 */
function yuniorrojas_resena_estrellas_html(float $rating, bool $interactive = false): string
{
    $full = (int) floor($rating);
    $half = ($rating - $full) >= 0.5;
    $html = '<span class="servicio-resenas__stars' . ($interactive ? ' is-interactive' : '') . '"';
    if ($interactive) {
        $html .= ' data-resena-stars role="radiogroup" aria-label="' . esc_attr__('Calificación', YUNIORROJAS_TEXT_DOMAIN) . '"';
    }
    $html .= '>';

    for ($i = 1; $i <= 5; $i++) {
        $cls = 'servicio-resenas__star';
        if ($i <= $full) {
            $cls .= ' is-on';
        } elseif ($half && $i === $full + 1) {
            $cls .= ' is-half';
        }
        if ($interactive) {
            $html .= '<button type="button" class="' . esc_attr($cls) . '" data-star="' . $i . '" aria-label="' . esc_attr(sprintf(__('%d estrellas', YUNIORROJAS_TEXT_DOMAIN), $i)) . '">';
            $html .= '<i class="ti ti-star-filled" aria-hidden="true"></i></button>';
        } else {
            $html .= '<span class="' . esc_attr($cls) . '" aria-hidden="true"><i class="ti ti-star-filled"></i></span>';
        }
    }

    $html .= '</span>';

    return $html;
}

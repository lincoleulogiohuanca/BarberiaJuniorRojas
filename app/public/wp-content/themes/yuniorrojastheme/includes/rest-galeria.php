<?php
/**
 * REST: galería filtrada + paginación (máx. 10 por página).
 */

if (!defined('ABSPATH')) {
    exit;
}

function yuniorrojas_registrar_rest_galeria(): void
{
    register_rest_route('yuniorrojas/v1', '/galeria', array(
        'methods'             => 'GET',
        'permission_callback' => '__return_true',
        'args'                => array(
            'categoria' => array(
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_key',
                'default'           => '',
            ),
            'etiqueta' => array(
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_key',
                'default'           => '',
            ),
            'page' => array(
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
                'default'           => 1,
            ),
        ),
        'callback' => static function (WP_REST_Request $request): WP_REST_Response {
            $categoria = (string) $request->get_param('categoria');
            $etiqueta  = (string) $request->get_param('etiqueta');
            $page      = max(1, (int) $request->get_param('page'));

            if ($categoria === '*' || $categoria === 'todos') {
                $categoria = '';
            }
            if ($etiqueta === '*' || $etiqueta === 'todos') {
                $etiqueta = '';
            }

            ob_start();
            $meta = yuniorrojas_lista_galeria(
                10,
                $categoria !== '' ? $categoria : null,
                $etiqueta !== '' ? $etiqueta : null,
                $page
            );
            $html = (string) ob_get_clean();

            return new WP_REST_Response(array(
                'html'  => $html,
                'page'  => $meta['page'],
                'pages' => $meta['pages'],
                'total' => $meta['total'],
            ), 200);
        },
    ));
}
add_action('rest_api_init', 'yuniorrojas_registrar_rest_galeria');

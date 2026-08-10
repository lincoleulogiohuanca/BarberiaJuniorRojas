<?php
/**
 * Ajustes de contacto (nativo, sin ACF Pro).
 * Option: yuniorrojas_contacto_settings
 *
 * Horarios variables: el editor añade/elimina/reordena filas como en Procesos.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Iconos Tabler Brand permitidos para redes del footer.
 *
 * @return array<string,string> slug => etiqueta
 */
function yuniorrojas_redes_iconos_disponibles(): array
{
    return array(
        'instagram' => 'Instagram',
        'facebook'  => 'Facebook',
        'youtube'   => 'YouTube',
        'tiktok'    => 'TikTok',
        'whatsapp'  => 'WhatsApp',
        'x'         => 'X (Twitter)',
        'linkedin'  => 'LinkedIn',
        'telegram'  => 'Telegram',
        'threads'   => 'Threads',
        'pinterest' => 'Pinterest',
    );
}

/**
 * Defaults de marca para contacto.
 *
 * @return array{
 *     whatsapp:string,
 *     telefono:string,
 *     direccion:string,
 *     mapa_embed:string,
 *     mapa_lat:float,
 *     mapa_lng:float,
 *     mapa_zoom:int,
 *     horarios:array<int,array{dia:string,hora:string}>,
 *     redes:array<int,array{nombre:string,url:string,icono:string}>
 * }
 */
function yuniorrojas_contacto_defaults(): array
{
    return array(
        'whatsapp'    => '+51 999 999 999',
        'telefono'    => '+51 999 999 999',
        'direccion'   => 'Jr. Ayacucho N° 727 - Huánuco - Perú',
        'mapa_embed' => '',
        'mapa_lat'   => -9.9297,
        'mapa_lng'   => -76.2422,
        'mapa_zoom'  => 17,
        'horarios'    => array(
            array('dia' => 'Lun – Vie', 'hora' => '10:00 am – 9:00 pm'),
            array('dia' => 'Sábado', 'hora' => '9:00 am – 8:00 pm'),
            array('dia' => 'Domingo', 'hora' => 'Cerrado'),
        ),
        'redes'       => yuniorrojas_redes_defaults(),
    );
}

/**
 * Redes por defecto (migrables desde campos ACF antiguos).
 *
 * @return array<int, array{nombre:string,url:string,icono:string}>
 */
function yuniorrojas_redes_defaults(): array
{
    $candidatos = array(
        array(
            'nombre' => 'Instagram',
            'url'    => (string) yuniorrojas_field('instagram_url', 'option', ''),
            'icono'  => 'instagram',
        ),
        array(
            'nombre' => 'Facebook',
            'url'    => (string) yuniorrojas_field('facebook_url', 'option', ''),
            'icono'  => 'facebook',
        ),
        array(
            'nombre' => 'YouTube',
            'url'    => (string) yuniorrojas_field('youtube_url', 'option', ''),
            'icono'  => 'youtube',
        ),
        array(
            'nombre' => 'TikTok',
            'url'    => (string) yuniorrojas_field('tiktok_url', 'option', ''),
            'icono'  => 'tiktok',
        ),
        array(
            'nombre' => 'WhatsApp',
            'url'    => (string) yuniorrojas_field('whatsapp_url', 'option', ''),
            'icono'  => 'whatsapp',
        ),
    );

    $con_url = array_values(array_filter($candidatos, static function (array $red): bool {
        $url = trim($red['url']);
        return $url !== '' && $url !== '#';
    }));

    if (!empty($con_url)) {
        return $con_url;
    }

    return array(
        array('nombre' => 'Instagram', 'url' => '', 'icono' => 'instagram'),
        array('nombre' => 'Facebook', 'url' => '', 'icono' => 'facebook'),
        array('nombre' => 'YouTube', 'url' => '', 'icono' => 'youtube'),
        array('nombre' => 'TikTok', 'url' => '', 'icono' => 'tiktok'),
        array('nombre' => 'WhatsApp', 'url' => '', 'icono' => 'whatsapp'),
    );
}

/**
 * @param array<int, mixed> $redes
 * @return array<int, array{nombre:string,url:string,icono:string}>
 */
function yuniorrojas_normalizar_redes(array $redes): array
{
    $iconos  = yuniorrojas_redes_iconos_disponibles();
    $limpios = array();

    foreach ($redes as $fila) {
        if (!is_array($fila)) {
            continue;
        }

        $nombre = isset($fila['nombre']) ? sanitize_text_field((string) $fila['nombre']) : '';
        $url    = isset($fila['url']) ? esc_url_raw(trim((string) $fila['url'])) : '';
        $icono  = isset($fila['icono']) ? sanitize_key((string) $fila['icono']) : 'instagram';

        if (!isset($iconos[$icono])) {
            $icono = 'instagram';
        }

        if ($nombre === '' && $url === '') {
            continue;
        }

        if ($nombre === '') {
            $nombre = $iconos[$icono];
        }

        $limpios[] = array(
            'nombre' => $nombre,
            'url'    => $url,
            'icono'  => $icono,
        );
    }

    return array_values($limpios);
}

/**
 * Lee y normaliza la option de contacto.
 *
 * @return array{
 *     whatsapp:string,
 *     telefono:string,
 *     direccion:string,
 *     mapa_embed:string,
 *     mapa_lat:float,
 *     mapa_lng:float,
 *     mapa_zoom:int,
 *     horarios:array<int,array{dia:string,hora:string}>,
 *     redes:array<int,array{nombre:string,url:string,icono:string}>
 * }
 */
function yuniorrojas_obtener_contacto_settings(): array
{
    $defaults = yuniorrojas_contacto_defaults();
    $saved    = get_option('yuniorrojas_contacto_settings', array());

    if (!is_array($saved)) {
        $saved = array();
    }

    $horarios = isset($saved['horarios']) && is_array($saved['horarios'])
        ? yuniorrojas_normalizar_horarios($saved['horarios'])
        : $defaults['horarios'];

    if (empty($horarios)) {
        $horarios = $defaults['horarios'];
    }

    $redes = isset($saved['redes']) && is_array($saved['redes'])
        ? yuniorrojas_normalizar_redes($saved['redes'])
        : $defaults['redes'];

    if (empty($redes) && !array_key_exists('redes', $saved)) {
        $redes = $defaults['redes'];
    }

    return array(
        'whatsapp'    => (string) ($saved['whatsapp'] ?? $defaults['whatsapp']),
        'telefono'    => (string) ($saved['telefono'] ?? $defaults['telefono']),
        'direccion'   => (string) ($saved['direccion'] ?? $defaults['direccion']),
        'mapa_embed' => (string) ($saved['mapa_embed'] ?? $defaults['mapa_embed']),
        'mapa_lat'   => (float) ($saved['mapa_lat'] ?? $defaults['mapa_lat']),
        'mapa_lng'   => (float) ($saved['mapa_lng'] ?? $defaults['mapa_lng']),
        'mapa_zoom'  => (int) ($saved['mapa_zoom'] ?? $defaults['mapa_zoom']),
        'horarios'    => $horarios,
        'redes'       => $redes,
    );
}

/**
 * @param array<int, mixed> $horarios
 * @return array<int, array{dia:string,hora:string}>
 */
function yuniorrojas_normalizar_horarios(array $horarios): array
{
    $limpios = array();

    foreach ($horarios as $fila) {
        if (!is_array($fila)) {
            continue;
        }

        $dia  = isset($fila['dia']) ? sanitize_text_field((string) $fila['dia']) : '';
        $hora = isset($fila['hora']) ? sanitize_text_field((string) $fila['hora']) : '';

        if ($dia === '' && $hora === '') {
            continue;
        }

        $limpios[] = array(
            'dia'  => $dia,
            'hora' => $hora,
        );
    }

    return array_values($limpios);
}

function yuniorrojas_registrar_menu_contacto(): void
{
    add_menu_page(
        __('Contacto', YUNIORROJAS_TEXT_DOMAIN),
        __('Contacto', YUNIORROJAS_TEXT_DOMAIN),
        'manage_options',
        'yuniorrojas-contacto',
        'yuniorrojas_renderizar_pagina_contacto',
        'dashicons-phone',
        58
    );
}
add_action('admin_menu', 'yuniorrojas_registrar_menu_contacto');

function yuniorrojas_renderizar_pagina_contacto(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $settings = yuniorrojas_obtener_contacto_settings();
    $horarios = $settings['horarios'];
    $redes    = $settings['redes'];
    $total    = count($horarios);
    $total_redes = count($redes);
    ?>
    <div class="wrap yuniorrojas-contacto-settings">
        <h1><?php esc_html_e('Contacto', YUNIORROJAS_TEXT_DOMAIN); ?></h1>
        <p class="description">
            <?php esc_html_e('Estos datos alimentan la página de Contacto, horarios, mapa y las redes del footer.', YUNIORROJAS_TEXT_DOMAIN); ?>
        </p>

        <?php if (isset($_GET['updated']) && $_GET['updated'] === '1') : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
            <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Ajustes guardados.', YUNIORROJAS_TEXT_DOMAIN); ?></p></div>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="yuniorrojas_guardar_contacto">
            <?php wp_nonce_field('yuniorrojas_guardar_contacto', 'yuniorrojas_contacto_nonce'); ?>

            <h2 class="title"><?php esc_html_e('Datos de contacto', YUNIORROJAS_TEXT_DOMAIN); ?></h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="yuniorrojas_whatsapp"><?php esc_html_e('WhatsApp', YUNIORROJAS_TEXT_DOMAIN); ?></label></th>
                    <td>
                        <input type="text" class="regular-text" id="yuniorrojas_whatsapp" name="yuniorrojas_contacto[whatsapp]" value="<?php echo esc_attr($settings['whatsapp']); ?>" placeholder="+51 999 999 999">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="yuniorrojas_telefono"><?php esc_html_e('Teléfono', YUNIORROJAS_TEXT_DOMAIN); ?></label></th>
                    <td>
                        <input type="text" class="regular-text" id="yuniorrojas_telefono" name="yuniorrojas_contacto[telefono]" value="<?php echo esc_attr($settings['telefono']); ?>" placeholder="+51 999 999 999">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="yuniorrojas_direccion"><?php esc_html_e('Dirección / Ubicación', YUNIORROJAS_TEXT_DOMAIN); ?></label></th>
                    <td>
                        <input type="text" class="large-text" id="yuniorrojas_direccion" name="yuniorrojas_contacto[direccion]" value="<?php echo esc_attr($settings['direccion']); ?>" autocomplete="street-address" data-mapa-admin-direccion>
                        <p class="description" id="yuniorrojas-mapa-dir-status" data-mapa-admin-dir-status aria-live="polite">
                            <?php esc_html_e('Al escribir una dirección se mueve el pin; al mover el pin se actualiza este texto. Luego guarda.', YUNIORROJAS_TEXT_DOMAIN); ?>
                        </p>
                    </td>
                </tr>
            </table>

            <h2 class="title"><?php esc_html_e('Mapa', YUNIORROJAS_TEXT_DOMAIN); ?></h2>
            <p class="description yuniorrojas-mapa-picker-help">
                <?php esc_html_e('Arrastra el pin o haz clic en el mapa para ubicar el estudio. Al guardar, la página pública de Contacto usa estas coordenadas (si no hay iframe de embed).', YUNIORROJAS_TEXT_DOMAIN); ?>
            </p>
            <div class="yuniorrojas-mapa-picker" data-mapa-admin-picker>
                <div
                    id="yuniorrojas-mapa-admin"
                    class="yuniorrojas-mapa-picker__map"
                    role="application"
                    aria-label="<?php esc_attr_e('Mapa para ubicar el estudio', YUNIORROJAS_TEXT_DOMAIN); ?>"
                ></div>
                <p class="description yuniorrojas-mapa-picker__hint">
                    <?php esc_html_e('Pin ↔ dirección: arrastra el pin, o escribe la dirección y pulsa Enter / sal del campo. El zoom lo controlas con la rueda o ±.', YUNIORROJAS_TEXT_DOMAIN); ?>
                </p>
            </div>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="yuniorrojas_mapa_lat"><?php esc_html_e('Latitud', YUNIORROJAS_TEXT_DOMAIN); ?></label></th>
                    <td>
                        <input type="text" class="regular-text" id="yuniorrojas_mapa_lat" name="yuniorrojas_contacto[mapa_lat]" value="<?php echo esc_attr((string) $settings['mapa_lat']); ?>" inputmode="decimal" autocomplete="off" data-mapa-admin-lat>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="yuniorrojas_mapa_lng"><?php esc_html_e('Longitud', YUNIORROJAS_TEXT_DOMAIN); ?></label></th>
                    <td>
                        <input type="text" class="regular-text" id="yuniorrojas_mapa_lng" name="yuniorrojas_contacto[mapa_lng]" value="<?php echo esc_attr((string) $settings['mapa_lng']); ?>" inputmode="decimal" autocomplete="off" data-mapa-admin-lng>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="yuniorrojas_mapa_zoom"><?php esc_html_e('Zoom', YUNIORROJAS_TEXT_DOMAIN); ?></label></th>
                    <td>
                        <input type="number" min="1" max="19" class="small-text" id="yuniorrojas_mapa_zoom" name="yuniorrojas_contacto[mapa_zoom]" value="<?php echo esc_attr((string) $settings['mapa_zoom']); ?>" data-mapa-admin-zoom>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="yuniorrojas_mapa_embed"><?php esc_html_e('Embed iframe (opcional)', YUNIORROJAS_TEXT_DOMAIN); ?></label></th>
                    <td>
                        <textarea class="large-text code" rows="4" id="yuniorrojas_mapa_embed" name="yuniorrojas_contacto[mapa_embed]" placeholder="<?php esc_attr_e('Si lo llenas, reemplaza el mapa Leaflet por este iframe.', YUNIORROJAS_TEXT_DOMAIN); ?>"><?php echo esc_textarea($settings['mapa_embed']); ?></textarea>
                        <p class="description"><?php esc_html_e('Déjalo vacío para usar el mapa de abajo (Leaflet) en la web. Si pegas un iframe de Google Maps, ese tiene prioridad en Contacto.', YUNIORROJAS_TEXT_DOMAIN); ?></p>
                    </td>
                </tr>
            </table>

            <h2 class="title"><?php esc_html_e('Horarios de atención', YUNIORROJAS_TEXT_DOMAIN); ?></h2>
            <p class="yuniorrojas-horarios-help description">
                <?php esc_html_e('Añade tantas filas como necesites. Arrastra para reordenar. Solo se guardan las que tengan día u hora.', YUNIORROJAS_TEXT_DOMAIN); ?>
            </p>

            <p class="yuniorrojas-horarios-count" data-horarios-count>
                <?php
                printf(
                    /* translators: %d: number of schedule rows */
                    esc_html(_n('%d horario', '%d horarios', $total, YUNIORROJAS_TEXT_DOMAIN)),
                    $total
                );
                ?>
            </p>

            <div id="yuniorrojas-horarios" class="yuniorrojas-horarios" data-horarios-list>
                <?php if (empty($horarios)) : ?>
                    <p class="yuniorrojas-horarios-empty" data-horarios-empty>
                        <?php esc_html_e('Todavía no hay horarios. Pulsa “Añadir horario” para crear el primero.', YUNIORROJAS_TEXT_DOMAIN); ?>
                    </p>
                <?php endif; ?>

                <?php foreach ($horarios as $index => $horario) : ?>
                    <?php yuniorrojas_renderizar_fila_horario((int) $index, $horario); ?>
                <?php endforeach; ?>
            </div>

            <p class="yuniorrojas-horarios-actions">
                <button type="button" class="button button-secondary" id="yuniorrojas-agregar-horario">
                    + <?php esc_html_e('Añadir horario', YUNIORROJAS_TEXT_DOMAIN); ?>
                </button>
            </p>

            <h2 class="title"><?php esc_html_e('Redes sociales (footer)', YUNIORROJAS_TEXT_DOMAIN); ?></h2>
            <p class="yuniorrojas-redes-help description">
                <?php esc_html_e('Crea solo las redes que quieras mostrar. Arrastra para reordenar. Las que no tengan URL no aparecen en el footer.', YUNIORROJAS_TEXT_DOMAIN); ?>
            </p>

            <p class="yuniorrojas-redes-count" data-redes-count>
                <?php
                printf(
                    /* translators: %d: number of social networks */
                    esc_html(_n('%d red', '%d redes', $total_redes, YUNIORROJAS_TEXT_DOMAIN)),
                    $total_redes
                );
                ?>
            </p>

            <div id="yuniorrojas-redes" class="yuniorrojas-redes" data-redes-list>
                <?php if (empty($redes)) : ?>
                    <p class="yuniorrojas-redes-empty" data-redes-empty>
                        <?php esc_html_e('Todavía no hay redes. Pulsa “Añadir red” para crear la primera.', YUNIORROJAS_TEXT_DOMAIN); ?>
                    </p>
                <?php endif; ?>

                <?php foreach ($redes as $index => $red) : ?>
                    <?php yuniorrojas_renderizar_fila_red((int) $index, $red); ?>
                <?php endforeach; ?>
            </div>

            <p class="yuniorrojas-redes-actions">
                <button type="button" class="button button-secondary" id="yuniorrojas-agregar-red">
                    + <?php esc_html_e('Añadir red', YUNIORROJAS_TEXT_DOMAIN); ?>
                </button>
            </p>

            <?php submit_button(__('Guardar cambios', YUNIORROJAS_TEXT_DOMAIN)); ?>
        </form>
    </div>
    <?php
}

/**
 * @param array{dia?:string,hora?:string} $horario
 */
function yuniorrojas_renderizar_fila_horario(int $index, array $horario = array()): void
{
    $numero = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
    ?>
    <div class="yuniorrojas-horario" data-horario-item>
        <div class="yuniorrojas-horario__header">
            <span class="yuniorrojas-horario__handle" title="<?php esc_attr_e('Arrastrar para reordenar', YUNIORROJAS_TEXT_DOMAIN); ?>" aria-hidden="true">⋮⋮</span>
            <strong class="yuniorrojas-horario__numero" data-horario-numero>
                HORARIO <?php echo esc_html($numero); ?>
            </strong>
            <button type="button" class="button yuniorrojas-eliminar-horario">
                <?php esc_html_e('Eliminar', YUNIORROJAS_TEXT_DOMAIN); ?>
            </button>
        </div>
        <div class="yuniorrojas-horario__body">
            <p>
                <label><strong><?php esc_html_e('Día', YUNIORROJAS_TEXT_DOMAIN); ?></strong></label>
                <input
                    type="text"
                    class="widefat"
                    data-campo="dia"
                    name="yuniorrojas_contacto[horarios][<?php echo esc_attr((string) $index); ?>][dia]"
                    value="<?php echo esc_attr($horario['dia'] ?? ''); ?>"
                    placeholder="<?php esc_attr_e('Ejemplo: Lun – Vie', YUNIORROJAS_TEXT_DOMAIN); ?>"
                >
            </p>
            <p>
                <label><strong><?php esc_html_e('Hora', YUNIORROJAS_TEXT_DOMAIN); ?></strong></label>
                <input
                    type="text"
                    class="widefat"
                    data-campo="hora"
                    name="yuniorrojas_contacto[horarios][<?php echo esc_attr((string) $index); ?>][hora]"
                    value="<?php echo esc_attr($horario['hora'] ?? ''); ?>"
                    placeholder="<?php esc_attr_e('Ejemplo: 10:00 am – 9:00 pm', YUNIORROJAS_TEXT_DOMAIN); ?>"
                >
            </p>
        </div>
    </div>
    <?php
}

/**
 * @param array{nombre?:string,url?:string,icono?:string} $red
 */
function yuniorrojas_renderizar_fila_red(int $index, array $red = array()): void
{
    $numero = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
    $icono  = isset($red['icono']) ? sanitize_key((string) $red['icono']) : 'instagram';
    $iconos = yuniorrojas_redes_iconos_disponibles();
    if (!isset($iconos[$icono])) {
        $icono = 'instagram';
    }
    ?>
    <div class="yuniorrojas-red" data-red-item>
        <div class="yuniorrojas-red__header">
            <span class="yuniorrojas-red__handle" title="<?php esc_attr_e('Arrastrar para reordenar', YUNIORROJAS_TEXT_DOMAIN); ?>" aria-hidden="true">⋮⋮</span>
            <strong class="yuniorrojas-red__numero" data-red-numero>
                RED <?php echo esc_html($numero); ?>
            </strong>
            <button type="button" class="button yuniorrojas-eliminar-red">
                <?php esc_html_e('Eliminar', YUNIORROJAS_TEXT_DOMAIN); ?>
            </button>
        </div>
        <div class="yuniorrojas-red__body">
            <p>
                <label><strong><?php esc_html_e('Nombre', YUNIORROJAS_TEXT_DOMAIN); ?></strong></label>
                <input
                    type="text"
                    class="widefat"
                    data-campo="nombre"
                    name="yuniorrojas_contacto[redes][<?php echo esc_attr((string) $index); ?>][nombre]"
                    value="<?php echo esc_attr($red['nombre'] ?? ''); ?>"
                    placeholder="<?php esc_attr_e('Ejemplo: Instagram', YUNIORROJAS_TEXT_DOMAIN); ?>"
                >
            </p>
            <p>
                <label><strong><?php esc_html_e('Icono', YUNIORROJAS_TEXT_DOMAIN); ?></strong></label>
                <select
                    class="widefat"
                    data-campo="icono"
                    name="yuniorrojas_contacto[redes][<?php echo esc_attr((string) $index); ?>][icono]"
                >
                    <?php foreach ($iconos as $slug => $etiqueta) : ?>
                        <option value="<?php echo esc_attr($slug); ?>" <?php selected($icono, $slug); ?>>
                            <?php echo esc_html($etiqueta); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>
            <p class="yuniorrojas-red__url">
                <label><strong><?php esc_html_e('URL', YUNIORROJAS_TEXT_DOMAIN); ?></strong></label>
                <input
                    type="url"
                    class="widefat"
                    data-campo="url"
                    name="yuniorrojas_contacto[redes][<?php echo esc_attr((string) $index); ?>][url]"
                    value="<?php echo esc_attr($red['url'] ?? ''); ?>"
                    placeholder="https://"
                >
            </p>
        </div>
    </div>
    <?php
}

function yuniorrojas_guardar_contacto_settings(): void
{
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('No tienes permisos para guardar estos ajustes.', YUNIORROJAS_TEXT_DOMAIN));
    }

    if (!yuniorrojas_verificar_nonce('yuniorrojas_contacto_nonce', 'yuniorrojas_guardar_contacto')) {
        wp_die(esc_html__('Nonce inválido.', YUNIORROJAS_TEXT_DOMAIN));
    }

    $raw = isset($_POST['yuniorrojas_contacto']) && is_array($_POST['yuniorrojas_contacto'])
        ? wp_unslash($_POST['yuniorrojas_contacto'])
        : array();

    /** @var array<string, mixed> $raw */
    $defaults = yuniorrojas_contacto_defaults();
    $horarios = isset($raw['horarios']) && is_array($raw['horarios'])
        ? yuniorrojas_normalizar_horarios($raw['horarios'])
        : array();

    if (empty($horarios)) {
        $horarios = $defaults['horarios'];
    }

    $redes = isset($raw['redes']) && is_array($raw['redes'])
        ? yuniorrojas_normalizar_redes($raw['redes'])
        : array();

    $settings = array(
        'whatsapp'    => sanitize_text_field((string) ($raw['whatsapp'] ?? '')),
        'telefono'    => sanitize_text_field((string) ($raw['telefono'] ?? '')),
        'direccion'   => sanitize_text_field((string) ($raw['direccion'] ?? '')),
        'mapa_embed' => trim((string) ($raw['mapa_embed'] ?? '')),
        'mapa_lat'   => (float) ($raw['mapa_lat'] ?? $defaults['mapa_lat']),
        'mapa_lng'   => (float) ($raw['mapa_lng'] ?? $defaults['mapa_lng']),
        'mapa_zoom'  => max(1, min(19, (int) ($raw['mapa_zoom'] ?? $defaults['mapa_zoom']))),
        'horarios'    => $horarios,
        'redes'       => $redes,
    );

    // Permitir solo iframe básico en embed.
    if ($settings['mapa_embed'] !== '') {
        $settings['mapa_embed'] = wp_kses($settings['mapa_embed'], array(
            'iframe' => array(
                'src'             => true,
                'width'           => true,
                'height'          => true,
                'style'           => true,
                'allowfullscreen' => true,
                'loading'         => true,
                'referrerpolicy'  => true,
                'frameborder'     => true,
            ),
        ));
    }

    update_option('yuniorrojas_contacto_settings', $settings, false);

    wp_safe_redirect(add_query_arg(
        array(
            'page'    => 'yuniorrojas-contacto',
            'updated' => '1',
        ),
        admin_url('admin.php')
    ));
    exit;
}
add_action('admin_post_yuniorrojas_guardar_contacto', 'yuniorrojas_guardar_contacto_settings');

<?php
/**
 * Metabox: Horario de atención del barbero.
 * Meta: _yuniorrojas_horario_barbero
 *
 * @phpstan-type DiaHorario array{activo:bool,inicio:string,fin:string}
 * @phpstan-type HorarioBarbero array{intervalo:int,dias:array<string,DiaHorario>}
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Claves de días (date('w'): 0=Dom … 6=Sáb).
 *
 * @return array<string,string>
 */
function yuniorrojas_dias_semana_labels(): array
{
    return array(
        '0' => __('Domingo', YUNIORROJAS_TEXT_DOMAIN),
        '1' => __('Lunes', YUNIORROJAS_TEXT_DOMAIN),
        '2' => __('Martes', YUNIORROJAS_TEXT_DOMAIN),
        '3' => __('Miércoles', YUNIORROJAS_TEXT_DOMAIN),
        '4' => __('Jueves', YUNIORROJAS_TEXT_DOMAIN),
        '5' => __('Viernes', YUNIORROJAS_TEXT_DOMAIN),
        '6' => __('Sábado', YUNIORROJAS_TEXT_DOMAIN),
    );
}

/**
 * Horario por defecto (estudio típico).
 *
 * @return HorarioBarbero
 */
function yuniorrojas_horario_barbero_defaults(): array
{
    $dias = array();
    foreach (array_keys(yuniorrojas_dias_semana_labels()) as $key) {
        $es_domingo = $key === '0';
        $es_sabado  = $key === '6';

        $dias[$key] = array(
            'activo' => !$es_domingo,
            'inicio' => $es_sabado ? '09:00' : '10:00',
            'fin'    => $es_sabado ? '20:00' : '21:00',
        );

        if ($es_domingo) {
            $dias[$key]['inicio'] = '10:00';
            $dias[$key]['fin']    = '18:00';
        }
    }

    return array(
        'intervalo' => 30,
        'dias'      => $dias,
    );
}

/**
 * Normaliza HH:MM (acepta también HH:MM:SS del input time del navegador).
 */
function yuniorrojas_normalizar_hora(string $hora, string $fallback = '10:00'): string
{
    $hora = trim($hora);
    if (preg_match('/^([01]?\d|2[0-3]):([0-5]\d)(?::[0-5]\d)?$/', $hora, $m)) {
        return sprintf('%02d:%02d', (int) $m[1], (int) $m[2]);
    }

    return $fallback;
}

/**
 * @param mixed $raw
 * @return HorarioBarbero
 */
function yuniorrojas_normalizar_horario_barbero($raw): array
{
    $defaults = yuniorrojas_horario_barbero_defaults();
    $intervalo = isset($raw['intervalo']) ? (int) $raw['intervalo'] : $defaults['intervalo'];
    if (!in_array($intervalo, array(15, 30, 45, 60), true)) {
        $intervalo = 30;
    }

    $dias_raw = (isset($raw['dias']) && is_array($raw['dias'])) ? $raw['dias'] : array();
    $dias     = array();

    foreach ($defaults['dias'] as $key => $dia_default) {
        $item = isset($dias_raw[$key]) && is_array($dias_raw[$key]) ? $dias_raw[$key] : array();

        $activo = !empty($item['activo']);
        $inicio = yuniorrojas_normalizar_hora((string) ($item['inicio'] ?? $dia_default['inicio']), $dia_default['inicio']);
        $fin    = yuniorrojas_normalizar_hora((string) ($item['fin'] ?? $dia_default['fin']), $dia_default['fin']);

        // Si fin <= inicio, corrige con default.
        if ($fin <= $inicio) {
            $inicio = $dia_default['inicio'];
            $fin    = $dia_default['fin'];
        }

        $dias[$key] = array(
            'activo' => $activo,
            'inicio' => $inicio,
            'fin'    => $fin,
        );
    }

    return array(
        'intervalo' => $intervalo,
        'dias'      => $dias,
    );
}

/**
 * @return HorarioBarbero
 */
function yuniorrojas_obtener_horario_barbero(int $post_id = 0): array
{
    $post_id = $post_id > 0 ? $post_id : (int) get_the_ID();
    $meta    = get_post_meta($post_id, '_yuniorrojas_horario_barbero', true);

    if (is_array($meta) && !empty($meta)) {
        return yuniorrojas_normalizar_horario_barbero($meta);
    }

    return yuniorrojas_horario_barbero_defaults();
}

function yuniorrojas_agregar_metabox_horario_barbero(): void
{
    add_meta_box(
        'yuniorrojas_horario_barbero',
        __('Horario de atención', YUNIORROJAS_TEXT_DOMAIN),
        'yuniorrojas_renderizar_metabox_horario_barbero',
        'barberos',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'yuniorrojas_agregar_metabox_horario_barbero');

/**
 * @param WP_Post $post
 */
function yuniorrojas_renderizar_metabox_horario_barbero($post): void
{
    wp_nonce_field('yuniorrojas_guardar_horario_barbero', 'yuniorrojas_horario_barbero_nonce');

    $horario   = yuniorrojas_obtener_horario_barbero((int) $post->ID);
    $labels    = yuniorrojas_dias_semana_labels();
    $intervalo = (int) $horario['intervalo'];
    ?>
    <div id="yuniorrojas-horario-barbero" class="yuniorrojas-horario-barbero" data-horario-barbero>
        <p class="description yuniorrojas-horario-barbero__help">
            <?php esc_html_e('Marca los días que atiende y define desde qué hora hasta qué hora. El intervalo genera los turnos e incluye la hora de cierre (ej. Hasta 21:00 → último turno 9:00 PM).', YUNIORROJAS_TEXT_DOMAIN); ?>
        </p>

        <p class="yuniorrojas-horario-barbero__intervalo">
            <label for="yuniorrojas-horario-intervalo">
                <strong><?php esc_html_e('Intervalo entre citas', YUNIORROJAS_TEXT_DOMAIN); ?></strong>
            </label>
            <select id="yuniorrojas-horario-intervalo" name="yuniorrojas_horario_barbero[intervalo]">
                <?php foreach (array(15, 30, 45, 60) as $mins) : ?>
                    <option value="<?php echo esc_attr((string) $mins); ?>" <?php selected($intervalo, $mins); ?>>
                        <?php echo esc_html(sprintf(__('Cada %d minutos', YUNIORROJAS_TEXT_DOMAIN), $mins)); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>

        <table class="widefat striped yuniorrojas-horario-barbero__tabla">
            <thead>
                <tr>
                    <th><?php esc_html_e('Día', YUNIORROJAS_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Atiende', YUNIORROJAS_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Desde', YUNIORROJAS_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Hasta', YUNIORROJAS_TEXT_DOMAIN); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($labels as $key => $label) : ?>
                    <?php
                    $dia    = $horario['dias'][$key];
                    $activo = !empty($dia['activo']);
                    ?>
                    <tr class="yuniorrojas-horario-barbero__row<?php echo $activo ? '' : ' is-off'; ?>" data-horario-dia>
                        <td>
                            <strong><?php echo esc_html($label); ?></strong>
                        </td>
                        <td>
                            <label class="yuniorrojas-horario-barbero__check">
                                <input
                                    type="checkbox"
                                    name="yuniorrojas_horario_barbero[dias][<?php echo esc_attr($key); ?>][activo]"
                                    value="1"
                                    <?php checked($activo); ?>
                                    data-horario-activo
                                >
                                <?php esc_html_e('Sí', YUNIORROJAS_TEXT_DOMAIN); ?>
                            </label>
                        </td>
                        <td>
                            <input
                                type="time"
                                step="60"
                                name="yuniorrojas_horario_barbero[dias][<?php echo esc_attr($key); ?>][inicio]"
                                value="<?php echo esc_attr($dia['inicio']); ?>"
                                data-horario-inicio
                                <?php echo $activo ? '' : 'readonly'; ?>
                            >
                        </td>
                        <td>
                            <input
                                type="time"
                                step="60"
                                name="yuniorrojas_horario_barbero[dias][<?php echo esc_attr($key); ?>][fin]"
                                value="<?php echo esc_attr($dia['fin']); ?>"
                                data-horario-fin
                                <?php echo $activo ? '' : 'readonly'; ?>
                            >
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

function yuniorrojas_guardar_horario_barbero(int $post_id): void
{
    if (!yuniorrojas_verificar_nonce('yuniorrojas_horario_barbero_nonce', 'yuniorrojas_guardar_horario_barbero')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $raw = isset($_POST['yuniorrojas_horario_barbero']) && is_array($_POST['yuniorrojas_horario_barbero'])
        ? wp_unslash($_POST['yuniorrojas_horario_barbero'])
        : array();

    // Checkboxes no enviados = día inactivo.
    $defaults = yuniorrojas_horario_barbero_defaults();
    $dias_in  = (isset($raw['dias']) && is_array($raw['dias'])) ? $raw['dias'] : array();
    $dias_out = array();

    foreach (array_keys($defaults['dias']) as $key) {
        $item = isset($dias_in[$key]) && is_array($dias_in[$key]) ? $dias_in[$key] : array();
        $dias_out[$key] = array(
            'activo' => !empty($item['activo']),
            'inicio' => isset($item['inicio']) ? (string) $item['inicio'] : $defaults['dias'][$key]['inicio'],
            'fin'    => isset($item['fin']) ? (string) $item['fin'] : $defaults['dias'][$key]['fin'],
        );
    }

    $horario = yuniorrojas_normalizar_horario_barbero(array(
        'intervalo' => $raw['intervalo'] ?? 30,
        'dias'      => $dias_out,
    ));

    update_post_meta($post_id, '_yuniorrojas_horario_barbero', $horario);
}
add_action('save_post_barberos', 'yuniorrojas_guardar_horario_barbero');

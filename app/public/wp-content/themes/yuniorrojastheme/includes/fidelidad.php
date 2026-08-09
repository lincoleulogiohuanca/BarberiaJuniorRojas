<?php
/**
 * Fidelidad Classic / Gold / Platinum — beneficios y umbrales configurables.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @return array{
 *   classic:array{min:int,max:int,label:string,beneficios:string[]},
 *   gold:array{min:int,max:int,label:string,beneficios:string[]},
 *   platinum:array{min:int,max:int,label:string,beneficios:string[]}
 * }
 */
function yuniorrojas_fidelidad_defaults(): array
{
    return array(
        'classic' => array(
            'min'        => 0,
            'max'        => 4,
            'label'      => 'Classic',
            'beneficios' => array(
                'Reserva online 24/7',
                'Historial de cortes en tu cuenta',
                'Preferencias de estilo guardadas',
            ),
        ),
        'gold' => array(
            'min'        => 5,
            'max'        => 11,
            'label'      => 'Gold',
            'beneficios' => array(
                'Todo lo de Classic',
                'Prioridad al reprogramar',
                'Bebida de cortesía en el estudio',
                '5% de descuento en servicios seleccionados (en local)',
            ),
        ),
        'platinum' => array(
            'min'        => 12,
            'max'        => 9999,
            'label'      => 'Platinum',
            'beneficios' => array(
                'Todo lo de Gold',
                'Máxima prioridad en agenda',
                'Tratamiento premium de cortesía 1 vez al mes',
                '10% de descuento en servicios (en local)',
            ),
        ),
    );
}

/**
 * @return array<string, array{min:int,max:int,label:string,beneficios:string[]}>
 */
function yuniorrojas_fidelidad_config(): array
{
    $saved = get_option('yuniorrojas_fidelidad_settings', array());
    $base  = yuniorrojas_fidelidad_defaults();
    if (!is_array($saved)) {
        return $base;
    }

    foreach (array('classic', 'gold', 'platinum') as $key) {
        if (!isset($saved[$key]) || !is_array($saved[$key])) {
            continue;
        }
        if (isset($saved[$key]['beneficios']) && is_array($saved[$key]['beneficios'])) {
            $bens = array_values(array_filter(array_map('sanitize_text_field', $saved[$key]['beneficios'])));
            if ($bens !== array()) {
                $base[$key]['beneficios'] = $bens;
            }
        }
        if (isset($saved[$key]['label'])) {
            $label = sanitize_text_field((string) $saved[$key]['label']);
            if ($label !== '') {
                $base[$key]['label'] = $label;
            }
        }
        if (isset($saved[$key]['min'])) {
            $base[$key]['min'] = max(0, (int) $saved[$key]['min']);
        }
        if (isset($saved[$key]['max'])) {
            $base[$key]['max'] = max(0, (int) $saved[$key]['max']);
        }
    }

    // Normalizar cascada: classic.min=0, gold.min, platinum.min.
    $base['classic']['min'] = 0;
    if ($base['gold']['min'] < 1) {
        $base['gold']['min'] = 5;
    }
    if ($base['platinum']['min'] <= $base['gold']['min']) {
        $base['platinum']['min'] = $base['gold']['min'] + 1;
    }
    $base['classic']['max'] = max(0, $base['gold']['min'] - 1);
    $base['gold']['max'] = max($base['gold']['min'], $base['platinum']['min'] - 1);
    $base['platinum']['max'] = 9999;

    return $base;
}

/**
 * @return 'classic'|'gold'|'platinum'
 */
function yuniorrojas_fidelidad_clave_nivel(int $completadas): string
{
    $cfg = yuniorrojas_fidelidad_config();
    if ($completadas >= (int) $cfg['platinum']['min']) {
        return 'platinum';
    }
    if ($completadas >= (int) $cfg['gold']['min']) {
        return 'gold';
    }

    return 'classic';
}

/**
 * Página de ajustes de fidelidad (admin).
 */
function yuniorrojas_fidelidad_admin_menu(): void
{
    add_submenu_page(
        'edit.php?post_type=' . YUNIORROJAS_CPT_RESERVAS,
        __('Fidelidad', YUNIORROJAS_TEXT_DOMAIN),
        __('Fidelidad', YUNIORROJAS_TEXT_DOMAIN),
        'manage_options',
        'yuniorrojas-fidelidad',
        'yuniorrojas_fidelidad_admin_page'
    );
}
add_action('admin_menu', 'yuniorrojas_fidelidad_admin_menu');

/**
 * @return void
 */
function yuniorrojas_fidelidad_admin_page(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }

    if (
        isset($_POST['yuniorrojas_fidelidad_nonce'])
        && wp_verify_nonce(sanitize_text_field(wp_unslash((string) $_POST['yuniorrojas_fidelidad_nonce'])), 'yuniorrojas_fidelidad_save')
    ) {
        $cfg = yuniorrojas_fidelidad_defaults();
        $cfg['gold']['min'] = isset($_POST['gold_min']) ? max(1, absint($_POST['gold_min'])) : 5;
        $cfg['platinum']['min'] = isset($_POST['platinum_min']) ? max(2, absint($_POST['platinum_min'])) : 12;

        foreach (array('classic', 'gold', 'platinum') as $key) {
            $raw = isset($_POST['beneficios'][$key]) ? wp_unslash((string) $_POST['beneficios'][$key]) : '';
            $lines = preg_split('/\r\n|\r|\n/', $raw) ?: array();
            $bens  = array_values(array_filter(array_map('sanitize_text_field', $lines)));
            if ($bens !== array()) {
                $cfg[$key]['beneficios'] = $bens;
            }
            if (isset($_POST['labels'][$key])) {
                $label = sanitize_text_field(wp_unslash((string) $_POST['labels'][$key]));
                if ($label !== '') {
                    $cfg[$key]['label'] = $label;
                }
            }
        }

        // Guardar y dejar que config normalice max.
        update_option('yuniorrojas_fidelidad_settings', $cfg, false);
        echo '<div class="notice notice-success"><p>' . esc_html__('Fidelidad actualizada.', YUNIORROJAS_TEXT_DOMAIN) . '</p></div>';
    }

    $cfg = yuniorrojas_fidelidad_config();
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Fidelidad de clientes', YUNIORROJAS_TEXT_DOMAIN); ?></h1>
        <p><?php esc_html_e('Umbrales = visitas completadas. Los beneficios se muestran en Mi cuenta del cliente.', YUNIORROJAS_TEXT_DOMAIN); ?></p>
        <form method="post">
            <?php wp_nonce_field('yuniorrojas_fidelidad_save', 'yuniorrojas_fidelidad_nonce'); ?>

            <h2><?php esc_html_e('Umbrales', YUNIORROJAS_TEXT_DOMAIN); ?></h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th><?php esc_html_e('Desde cuántas visitas = Gold', YUNIORROJAS_TEXT_DOMAIN); ?></th>
                    <td>
                        <input type="number" min="1" name="gold_min" value="<?php echo esc_attr((string) $cfg['gold']['min']); ?>" class="small-text">
                        <p class="description"><?php esc_html_e('Classic: por debajo de este número.', YUNIORROJAS_TEXT_DOMAIN); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Desde cuántas visitas = Platinum', YUNIORROJAS_TEXT_DOMAIN); ?></th>
                    <td>
                        <input type="number" min="2" name="platinum_min" value="<?php echo esc_attr((string) $cfg['platinum']['min']); ?>" class="small-text">
                    </td>
                </tr>
            </table>

            <?php foreach ($cfg as $key => $nivel) : ?>
                <h2>
                    <input type="text" name="labels[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr((string) $nivel['label']); ?>" class="regular-text">
                    <span class="description">
                        (<?php echo esc_html((string) $nivel['min'] . '–' . ($nivel['max'] >= 9999 ? '∞' : (string) $nivel['max'])); ?> visitas)
                    </span>
                </h2>
                <textarea name="beneficios[<?php echo esc_attr($key); ?>]" rows="5" class="large-text"><?php
                    echo esc_textarea(implode("\n", $nivel['beneficios']));
                ?></textarea>
            <?php endforeach; ?>
            <p><button type="submit" class="button button-primary"><?php esc_html_e('Guardar', YUNIORROJAS_TEXT_DOMAIN); ?></button></p>
        </form>
    </div>
    <?php
}

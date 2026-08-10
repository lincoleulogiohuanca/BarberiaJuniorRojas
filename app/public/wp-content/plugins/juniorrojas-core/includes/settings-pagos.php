<?php
/**
 * Ajustes de pasarelas: llaves Culqi (editables en admin).
 * Los medios (Plin, estudio, etc.) se gestionan en el CPT Medios de pago.
 * Option: yuniorrojas_pagos_settings
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Menú Ajustes Culqi.
 */
function yuniorrojas_pagos_settings_menu(): void
{
    add_submenu_page(
        'edit.php?post_type=' . YUNIORROJAS_CPT_RESERVAS,
        __('Ajustes Culqi', YUNIORROJAS_TEXT_DOMAIN),
        __('Ajustes Culqi', YUNIORROJAS_TEXT_DOMAIN),
        'manage_options',
        'yuniorrojas-pagos-settings',
        'yuniorrojas_pagos_settings_render'
    );
}
add_action('admin_menu', 'yuniorrojas_pagos_settings_menu', 14);

/**
 * Guarda llaves Culqi (editables desde admin).
 */
function yuniorrojas_pagos_settings_save(): void
{
    if (!isset($_POST['yuniorrojas_pagos_settings_nonce'])) {
        return;
    }
    if (!current_user_can('manage_options')) {
        return;
    }
    if (!wp_verify_nonce(
        sanitize_text_field(wp_unslash((string) $_POST['yuniorrojas_pagos_settings_nonce'])),
        'yuniorrojas_pagos_settings_save'
    )) {
        return;
    }

    // Asegurar import previo antes de leer prev.
    if (function_exists('yuniorrojas_culqi_hidratar_llaves_si_vacio')) {
        yuniorrojas_culqi_hidratar_llaves_si_vacio();
    }

    $prev = yuniorrojas_pagos_settings();

    $public = isset($_POST['culqi_public_key'])
        ? sanitize_text_field(wp_unslash((string) $_POST['culqi_public_key']))
        : '';
    $public = trim($public);

    $secret_raw = isset($_POST['culqi_secret_key'])
        ? trim(wp_unslash((string) $_POST['culqi_secret_key']))
        : '';

    // Placeholder: no borrar la secreta si no la cambian.
    if ($secret_raw === '' || $secret_raw === '••••••••' || $secret_raw === '********') {
        $secret = trim((string) $prev['culqi_secret_key']);
    } else {
        $secret = sanitize_text_field($secret_raw);
    }

    if ($public !== '' && strpos($public, 'pk_') !== 0) {
        add_settings_error(
            'yuniorrojas_pagos',
            'public_format',
            __('La llave pública debe empezar con pk_test_ o pk_live_.', YUNIORROJAS_TEXT_DOMAIN),
            'error'
        );
        return;
    }
    if ($secret !== '' && strpos($secret, 'sk_') !== 0) {
        add_settings_error(
            'yuniorrojas_pagos',
            'secret_format',
            __('La llave privada debe empezar con sk_test_ o sk_live_.', YUNIORROJAS_TEXT_DOMAIN),
            'error'
        );
        return;
    }

    $data = array_merge($prev, array(
        'culqi_public_key'     => $public,
        'culqi_secret_key'     => $secret,
        'culqi_webhook_secret' => isset($_POST['culqi_webhook_secret'])
            ? sanitize_text_field(wp_unslash((string) $_POST['culqi_webhook_secret']))
            : (string) ($prev['culqi_webhook_secret'] ?? ''),
    ));

    // Placeholder: no borrar webhook secret.
    $wh_raw = isset($_POST['culqi_webhook_secret'])
        ? trim(wp_unslash((string) $_POST['culqi_webhook_secret']))
        : '';
    if ($wh_raw === '' || $wh_raw === '••••••••') {
        $data['culqi_webhook_secret'] = (string) ($prev['culqi_webhook_secret'] ?? '');
    } else {
        $data['culqi_webhook_secret'] = sanitize_text_field($wh_raw);
    }
    update_option('yuniorrojas_pagos_settings', $data, false);

    add_settings_error(
        'yuniorrojas_pagos',
        'saved',
        __('Llaves Culqi guardadas. Administra Plin y otros medios en Medios de pago.', YUNIORROJAS_TEXT_DOMAIN),
        'success'
    );
}
add_action('admin_init', 'yuniorrojas_pagos_settings_save');

/**
 * Página editable de llaves Culqi.
 */
function yuniorrojas_pagos_settings_render(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }

    // Asegura import desde wp-config si aún no hay option.
    if (function_exists('yuniorrojas_culqi_hidratar_llaves_si_vacio')) {
        yuniorrojas_culqi_hidratar_llaves_si_vacio();
    }

    settings_errors('yuniorrojas_pagos');

    $s           = yuniorrojas_pagos_settings();
    $public      = trim((string) $s['culqi_public_key']);
    $secret      = trim((string) $s['culqi_secret_key']);
    // Mostrar valor efectivo si la option está vacía pero hay fallback de config.
    if ($public === '' && function_exists('yuniorrojas_culqi_public_key')) {
        $public = yuniorrojas_culqi_public_key();
    }

    $configured  = yuniorrojas_culqi_esta_configurado();
    $is_test     = yuniorrojas_culqi_es_test();
    $public_show = yuniorrojas_culqi_public_key();
    $medios_url  = admin_url('edit.php?post_type=' . YUNIORROJAS_CPT_MEDIOS_PAGO);
    $has_wpconfig_pk = defined('YUNIORROJAS_CULQI_PUBLIC_KEY') && (string) YUNIORROJAS_CULQI_PUBLIC_KEY !== '';
    $has_wpconfig_sk = defined('YUNIORROJAS_CULQI_SECRET_KEY') && (string) YUNIORROJAS_CULQI_SECRET_KEY !== '';
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Ajustes Culqi', YUNIORROJAS_TEXT_DOMAIN); ?></h1>
        <p class="description">
            <?php esc_html_e('Edita las llaves de Culqi aquí. Los botones del checkout se gestionan en', YUNIORROJAS_TEXT_DOMAIN); ?>
            <a href="<?php echo esc_url($medios_url); ?>"><?php esc_html_e('Medios de pago', YUNIORROJAS_TEXT_DOMAIN); ?></a>.
        </p>

        <div class="notice notice-<?php echo $configured ? 'success' : 'warning'; ?> inline">
            <p>
                <?php if ($configured) : ?>
                    <strong><?php esc_html_e('Culqi activo', YUNIORROJAS_TEXT_DOMAIN); ?></strong>
                    — <?php echo $is_test
                        ? esc_html__('Modo prueba (test).', YUNIORROJAS_TEXT_DOMAIN)
                        : esc_html__('Modo producción (live).', YUNIORROJAS_TEXT_DOMAIN); ?>
                    <?php if ($public_show !== '') : ?>
                        <code><?php echo esc_html(substr($public_show, 0, 12) . '…' . substr($public_show, -4)); ?></code>
                    <?php endif; ?>
                <?php else : ?>
                    <strong><?php esc_html_e('Culqi no configurado', YUNIORROJAS_TEXT_DOMAIN); ?></strong>
                    — <?php esc_html_e('Pega tu llave pública (pk_…) y privada (sk_…) y guarda.', YUNIORROJAS_TEXT_DOMAIN); ?>
                <?php endif; ?>
            </p>
        </div>

        <?php if ($has_wpconfig_pk || $has_wpconfig_sk) : ?>
            <div class="notice notice-info inline">
                <p>
                    <?php esc_html_e('Hay constantes en wp-config.php como respaldo. Lo que guardes aquí tiene prioridad. Puedes quitar YUNIORROJAS_CULQI_* de wp-config cuando ya estén en este formulario.', YUNIORROJAS_TEXT_DOMAIN); ?>
                </p>
            </div>
        <?php endif; ?>

        <form method="post" action="">
            <?php wp_nonce_field('yuniorrojas_pagos_settings_save', 'yuniorrojas_pagos_settings_nonce'); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="culqi_public_key"><?php esc_html_e('Llave pública', YUNIORROJAS_TEXT_DOMAIN); ?></label></th>
                    <td>
                        <input type="text" class="regular-text code" name="culqi_public_key" id="culqi_public_key"
                            value="<?php echo esc_attr($public); ?>"
                            placeholder="pk_test_… o pk_live_…"
                            autocomplete="off"
                            spellcheck="false">
                        <p class="description"><?php esc_html_e('Visible en el navegador (checkout). Empieza con pk_test_ o pk_live_.', YUNIORROJAS_TEXT_DOMAIN); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="culqi_secret_key"><?php esc_html_e('Llave privada', YUNIORROJAS_TEXT_DOMAIN); ?></label></th>
                    <td>
                        <input type="password" class="regular-text code" name="culqi_secret_key" id="culqi_secret_key"
                            value="<?php echo $secret !== '' ? '••••••••' : ''; ?>"
                            placeholder="sk_test_… o sk_live_…"
                            autocomplete="new-password"
                            spellcheck="false">
                        <p class="description">
                            <?php esc_html_e('Solo servidor. Déjala en •••• para no cambiarla. Pega una nueva sk_… para reemplazarla.', YUNIORROJAS_TEXT_DOMAIN); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="culqi_webhook_secret"><?php esc_html_e('Secreto webhook', YUNIORROJAS_TEXT_DOMAIN); ?></label></th>
                    <td>
                        <?php
                        $wh = trim((string) ($s['culqi_webhook_secret'] ?? ''));
                        ?>
                        <input type="password" class="regular-text code" name="culqi_webhook_secret" id="culqi_webhook_secret"
                            value="<?php echo $wh !== '' ? '••••••••' : ''; ?>"
                            autocomplete="new-password"
                            spellcheck="false">
                        <p class="description">
                            <?php esc_html_e('Opcional. Misma cadena que configures en Culqi o YUNIORROJAS_CULQI_WEBHOOK_SECRET en wp-config.', YUNIORROJAS_TEXT_DOMAIN); ?>
                            <br>
                            <?php esc_html_e('URL del webhook:', YUNIORROJAS_TEXT_DOMAIN); ?>
                            <code><?php echo esc_html(rest_url('yuniorrojas/v1/culqi/webhook')); ?></code>
                        </p>
                    </td>
                </tr>
            </table>
            <?php submit_button(__('Guardar llaves Culqi', YUNIORROJAS_TEXT_DOMAIN)); ?>
        </form>
    </div>
    <?php
}

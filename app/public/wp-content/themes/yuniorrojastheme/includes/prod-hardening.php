<?php
/**
 * Endurecimiento de producción: rate limits, SSL, SMTP, locks de slot, backups UI.
 */

if (!defined('ABSPATH')) {
    exit;
}

/** Límite de subida de comprobante (bytes). */
if (!defined('YUNIORROJAS_COMPROBANTE_MAX_BYTES')) {
    define('YUNIORROJAS_COMPROBANTE_MAX_BYTES', 4 * 1024 * 1024);
}

/**
 * IP del cliente (proxy-aware básico).
 */
function yuniorrojas_cliente_ip(): string
{
    $keys = array('HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
    foreach ($keys as $key) {
        if (empty($_SERVER[$key])) {
            continue;
        }
        $raw = (string) wp_unslash($_SERVER[$key]);
        if (strpos($raw, ',') !== false) {
            $raw = trim(explode(',', $raw)[0]);
        }
        $ip = sanitize_text_field($raw);
        if ($ip !== '') {
            return $ip;
        }
    }
    return '0.0.0.0';
}

/**
 * Rate limit genérico por bucket + IP (y opcionalmente user).
 *
 * @return true|WP_Error
 */
function yuniorrojas_rate_limit(string $bucket, int $max_hits, int $window_seconds)
{
    $bucket = sanitize_key($bucket);
    if ($bucket === '' || $max_hits < 1 || $window_seconds < 1) {
        return true;
    }

    $uid = is_user_logged_in() ? (int) get_current_user_id() : 0;
    $key = 'jr_rl_' . md5($bucket . '|' . yuniorrojas_cliente_ip() . '|' . $uid);
    $hits = (int) get_transient($key);

    if ($hits >= $max_hits) {
        return new WP_Error(
            'rate_limit',
            'Demasiados intentos. Espera unos minutos e inténtalo de nuevo.',
            array('status' => 429)
        );
    }

    set_transient($key, $hits + 1, $window_seconds);
    return true;
}

/**
 * ¿Rate limit OK? Variante booleana para formularios clásicos.
 */
function yuniorrojas_rate_limit_ok(string $bucket, int $max_hits, int $window_seconds): bool
{
    return !is_wp_error(yuniorrojas_rate_limit($bucket, $max_hits, $window_seconds));
}

/**
 * Clave de option para lock de slot (único).
 */
function yuniorrojas_slot_lock_option_key(int $barbero_id, string $fecha, string $hora): string
{
    return 'jr_slot_lock_' . md5($barbero_id . '|' . $fecha . '|' . $hora);
}

/**
 * Adquiere lock atómico del horario (TTL ~90s).
 */
function yuniorrojas_slot_adquirir_lock(int $barbero_id, string $fecha, string $hora): bool
{
    $key = yuniorrojas_slot_lock_option_key($barbero_id, $fecha, $hora);
    $now = time();

    if (wp_using_ext_object_cache()) {
        return (bool) wp_cache_add($key, $now, 'jr_slot_locks', 90);
    }

    $ok = add_option($key, (string) $now, '', 'no');
    if ($ok) {
        return true;
    }

    $prev = (int) get_option($key, 0);
    if ($prev > 0 && ($now - $prev) > 90) {
        delete_option($key);
        return (bool) add_option($key, (string) $now, '', 'no');
    }

    return false;
}

/**
 * Libera lock de horario.
 */
function yuniorrojas_slot_liberar_lock(int $barbero_id, string $fecha, string $hora): void
{
    $key = yuniorrojas_slot_lock_option_key($barbero_id, $fecha, $hora);
    if (wp_using_ext_object_cache()) {
        wp_cache_delete($key, 'jr_slot_locks');
    }
    delete_option($key);
}

/**
 * Valor de una constante YUNIORROJAS_SMTP_* (wp-config / entorno), o vacío.
 */
function yuniorrojas_smtp_const(string $suffix): string
{
    $name = 'YUNIORROJAS_SMTP_' . strtoupper($suffix);
    if (!defined($name)) {
        return '';
    }
    $val = constant($name);
    if (is_bool($val)) {
        return $val ? '1' : '0';
    }
    return trim((string) $val);
}

/**
 * ¿Hay alguna constante SMTP definida en wp-config?
 */
function yuniorrojas_smtp_tiene_constantes_wpconfig(): bool
{
    $suffixes = array('ENABLED', 'HOST', 'PORT', 'USER', 'PASS', 'ENCRYPTION', 'FROM', 'FROM_NAME');
    foreach ($suffixes as $suffix) {
        if (yuniorrojas_smtp_const($suffix) !== '') {
            return true;
        }
    }
    return false;
}

/**
 * Si campos SMTP de la option están vacíos, importa desde YUNIORROJAS_SMTP_* en wp-config.
 * Prioridad final: lo guardado en admin > constantes (como Culqi).
 */
function yuniorrojas_smtp_hidratar_si_vacio(): void
{
    static $ran = false;
    if ($ran) {
        return;
    }
    $ran = true;

    $saved = get_option('yuniorrojas_prod_settings', array());
    if (!is_array($saved)) {
        $saved = array();
    }

    $changed = false;
    // PASS no se copia a BD: se lee en runtime desde YUNIORROJAS_SMTP_PASS (más seguro).
    $string_map = array(
        'smtp_host'      => 'HOST',
        'smtp_user'      => 'USER',
        'smtp_from'      => 'FROM',
        'smtp_from_name' => 'FROM_NAME',
    );

    foreach ($string_map as $key => $suffix) {
        $cur = trim((string) ($saved[$key] ?? ''));
        $from_const = yuniorrojas_smtp_const($suffix);
        if ($cur === '' && $from_const !== '') {
            $saved[$key] = $from_const;
            $changed     = true;
        }
    }

    $enc_const = strtolower(yuniorrojas_smtp_const('ENCRYPTION'));
    if (
        (!isset($saved['smtp_encryption']) || trim((string) $saved['smtp_encryption']) === '')
        && in_array($enc_const, array('none', 'ssl', 'tls'), true)
    ) {
        $saved['smtp_encryption'] = $enc_const;
        $changed                  = true;
    }

    $port_const = absint(yuniorrojas_smtp_const('PORT'));
    if ($port_const > 0 && !array_key_exists('smtp_port', $saved)) {
        $saved['smtp_port'] = $port_const;
        $changed            = true;
    }

    // ENABLED: solo si nunca se guardó la flag en BD.
    if (!array_key_exists('smtp_enabled', $saved)) {
        $en = yuniorrojas_smtp_const('ENABLED');
        if ($en !== '') {
            $saved['smtp_enabled'] = in_array(strtolower($en), array('1', 'true', 'yes', 'on'), true);
            $changed               = true;
        } elseif (trim((string) ($saved['smtp_host'] ?? '')) !== '') {
            // Deploy con HOST en wp-config: activar SMTP sin checkbox manual.
            $saved['smtp_enabled'] = true;
            $changed               = true;
        }
    }

    if ($changed) {
        update_option('yuniorrojas_prod_settings', $saved, false);
    }
}

/**
 * Settings de producción (SMTP + forzar HTTPS).
 *
 * SMTP (prioridad):
 * 1) Reservas → Producción (option yuniorrojas_prod_settings)
 * 2) Constantes YUNIORROJAS_SMTP_* en wp-config.php (fallback / seed)
 *
 * @return array{force_ssl:bool,smtp_enabled:bool,smtp_host:string,smtp_port:int,smtp_user:string,smtp_pass:string,smtp_encryption:string,smtp_from:string,smtp_from_name:string}
 */
function yuniorrojas_prod_settings(): array
{
    yuniorrojas_smtp_hidratar_si_vacio();

    $saved = get_option('yuniorrojas_prod_settings', array());
    if (!is_array($saved)) {
        $saved = array();
    }

    $host = trim((string) ($saved['smtp_host'] ?? ''));
    if ($host === '') {
        $host = yuniorrojas_smtp_const('HOST');
    }

    $user = trim((string) ($saved['smtp_user'] ?? ''));
    if ($user === '') {
        $user = yuniorrojas_smtp_const('USER');
    }

    $pass = (string) ($saved['smtp_pass'] ?? '');
    if ($pass === '') {
        $pass = yuniorrojas_smtp_const('PASS');
    }

    $from = sanitize_email((string) ($saved['smtp_from'] ?? ''));
    if ($from === '') {
        $from = sanitize_email(yuniorrojas_smtp_const('FROM'));
    }

    $from_name = sanitize_text_field((string) ($saved['smtp_from_name'] ?? ''));
    if ($from_name === '') {
        $from_name = sanitize_text_field(yuniorrojas_smtp_const('FROM_NAME'));
    }

    $enc = (string) ($saved['smtp_encryption'] ?? '');
    if (!in_array($enc, array('none', 'ssl', 'tls'), true)) {
        $enc_const = strtolower(yuniorrojas_smtp_const('ENCRYPTION'));
        $enc       = in_array($enc_const, array('none', 'ssl', 'tls'), true) ? $enc_const : 'tls';
    }

    $port = absint($saved['smtp_port'] ?? 0);
    if ($port < 1) {
        $port_const = absint(yuniorrojas_smtp_const('PORT'));
        $port       = $port_const > 0 ? $port_const : 587;
    }

    // Admin gana si ya guardó smtp_enabled; si no, constants / host como fallback.
    if (array_key_exists('smtp_enabled', $saved)) {
        $enabled = !empty($saved['smtp_enabled']);
    } else {
        $en = yuniorrojas_smtp_const('ENABLED');
        if ($en !== '') {
            $enabled = in_array(strtolower($en), array('1', 'true', 'yes', 'on'), true);
        } else {
            $enabled = $host !== '';
        }
    }

    return array(
        'force_ssl'       => !empty($saved['force_ssl']),
        'smtp_enabled'    => $enabled,
        'smtp_host'       => $host,
        'smtp_port'       => max(1, $port),
        'smtp_user'       => $user,
        'smtp_pass'       => $pass,
        'smtp_encryption' => $enc,
        'smtp_from'       => $from,
        'smtp_from_name'  => $from_name,
    );
}

/**
 * Menú Ajustes de producción.
 */
function yuniorrojas_prod_settings_menu(): void
{
    add_submenu_page(
        'edit.php?post_type=' . YUNIORROJAS_CPT_RESERVAS,
        __('Producción', YUNIORROJAS_TEXT_DOMAIN),
        __('Producción', YUNIORROJAS_TEXT_DOMAIN),
        'manage_options',
        'yuniorrojas-prod-settings',
        'yuniorrojas_prod_settings_render'
    );
}
add_action('admin_menu', 'yuniorrojas_prod_settings_menu', 30);

/**
 * Guarda ajustes de producción.
 */
function yuniorrojas_prod_settings_save(): void
{
    if (!isset($_POST['yuniorrojas_prod_settings_nonce'])) {
        return;
    }
    if (!current_user_can('manage_options')) {
        return;
    }
    if (!wp_verify_nonce(
        sanitize_text_field(wp_unslash((string) $_POST['yuniorrojas_prod_settings_nonce'])),
        'yuniorrojas_prod_settings_save'
    )) {
        return;
    }

    yuniorrojas_smtp_hidratar_si_vacio();

    $prev = yuniorrojas_prod_settings();
    $pass_raw = isset($_POST['smtp_pass']) ? (string) wp_unslash($_POST['smtp_pass']) : ''; // phpcs:ignore
    $pass = ($pass_raw === '' || $pass_raw === '••••••••')
        ? (string) (get_option('yuniorrojas_prod_settings', array())['smtp_pass'] ?? $prev['smtp_pass'])
        : sanitize_text_field($pass_raw);

    // Si la pass solo venía de wp-config y no de option, no persistir el secreto en BD al “no cambiar”.
    $saved_raw = get_option('yuniorrojas_prod_settings', array());
    if (!is_array($saved_raw)) {
        $saved_raw = array();
    }
    if (
        ($pass_raw === '' || $pass_raw === '••••••••')
        && trim((string) ($saved_raw['smtp_pass'] ?? '')) === ''
        && yuniorrojas_smtp_const('PASS') !== ''
    ) {
        $pass = '';
    }

    $data = array(
        'force_ssl'       => !empty($_POST['force_ssl']),
        'smtp_enabled'    => !empty($_POST['smtp_enabled']),
        'smtp_host'       => sanitize_text_field(wp_unslash((string) ($_POST['smtp_host'] ?? ''))),
        'smtp_port'       => max(1, absint($_POST['smtp_port'] ?? 587)),
        'smtp_user'       => sanitize_text_field(wp_unslash((string) ($_POST['smtp_user'] ?? ''))),
        'smtp_pass'       => $pass,
        'smtp_encryption' => sanitize_key((string) ($_POST['smtp_encryption'] ?? 'tls')),
        'smtp_from'       => sanitize_email(wp_unslash((string) ($_POST['smtp_from'] ?? ''))),
        'smtp_from_name'  => sanitize_text_field(wp_unslash((string) ($_POST['smtp_from_name'] ?? ''))),
    );

    if (!in_array($data['smtp_encryption'], array('none', 'ssl', 'tls'), true)) {
        $data['smtp_encryption'] = 'tls';
    }

    update_option('yuniorrojas_prod_settings', $data, false);
    add_settings_error('yuniorrojas_prod', 'saved', __('Ajustes de producción guardados.', YUNIORROJAS_TEXT_DOMAIN), 'success');
}
add_action('admin_init', 'yuniorrojas_prod_settings_save');

/**
 * Envía un correo de prueba con la config SMTP actual.
 */
function yuniorrojas_prod_smtp_test_send(): void
{
    if (!isset($_POST['yuniorrojas_smtp_test_nonce'])) {
        return;
    }
    if (!current_user_can('manage_options')) {
        return;
    }
    if (!wp_verify_nonce(
        sanitize_text_field(wp_unslash((string) $_POST['yuniorrojas_smtp_test_nonce'])),
        'yuniorrojas_smtp_test'
    )) {
        return;
    }

    $s = yuniorrojas_prod_settings();
    if (empty($s['smtp_enabled']) || $s['smtp_host'] === '') {
        add_settings_error(
            'yuniorrojas_prod',
            'smtp_test_off',
            __('Activa SMTP y define un host (panel o YUNIORROJAS_SMTP_HOST) antes de probar.', YUNIORROJAS_TEXT_DOMAIN),
            'error'
        );
        return;
    }

    $to = sanitize_email(wp_unslash((string) ($_POST['smtp_test_to'] ?? '')));
    if ($to === '' || !is_email($to)) {
        $to = (string) get_option('admin_email');
    }

    $subject = sprintf(
        /* translators: %s: site name */
        __('[%s] Prueba SMTP', YUNIORROJAS_TEXT_DOMAIN),
        wp_specialchars_decode((string) get_bloginfo('name'), ENT_QUOTES)
    );
    $body = sprintf(
        "Prueba SMTP OK.\n\nHost: %s\nPuerto: %d\nCifrado: %s\nUsuario: %s\nHora: %s\n",
        $s['smtp_host'],
        (int) $s['smtp_port'],
        $s['smtp_encryption'],
        $s['smtp_user'] !== '' ? $s['smtp_user'] : '(sin auth)',
        wp_date('Y-m-d H:i:s')
    );

    $sent = wp_mail($to, $subject, $body);
    if ($sent) {
        add_settings_error(
            'yuniorrojas_prod',
            'smtp_test_ok',
            sprintf(
                /* translators: %s: email */
                __('Correo de prueba enviado a %s. Revisa la bandeja (y spam).', YUNIORROJAS_TEXT_DOMAIN),
                $to
            ),
            'success'
        );
    } else {
        add_settings_error(
            'yuniorrojas_prod',
            'smtp_test_fail',
            __('No se pudo enviar el correo de prueba. Revisa host, puerto, credenciales y que el hosting permita SMTP saliente.', YUNIORROJAS_TEXT_DOMAIN),
            'error'
        );
    }
}
add_action('admin_init', 'yuniorrojas_prod_smtp_test_send');

/**
 * UI ajustes producción + checklist.
 */
function yuniorrojas_prod_settings_render(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }
    settings_errors('yuniorrojas_prod');
    $s = yuniorrojas_prod_settings();
    $culqi_ok = function_exists('yuniorrojas_culqi_esta_configurado') && yuniorrojas_culqi_esta_configurado();
    $culqi_test = function_exists('yuniorrojas_culqi_es_test') && yuniorrojas_culqi_es_test();
    $cron_next = wp_next_scheduled('yuniorrojas_cron_recordatorios');
    $has_smtp_const = yuniorrojas_smtp_tiene_constantes_wpconfig();
    $admin_email = (string) get_option('admin_email');
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Producción', YUNIORROJAS_TEXT_DOMAIN); ?></h1>
        <p class="description">
            <?php esc_html_e('HTTPS, SMTP y checklist de go-live. Las llaves Culqi se editan en Ajustes Culqi.', YUNIORROJAS_TEXT_DOMAIN); ?>
        </p>

        <h2><?php esc_html_e('Estado actual', YUNIORROJAS_TEXT_DOMAIN); ?></h2>
        <ul style="list-style:disc;margin-left:1.5em;">
            <li>SSL: <?php echo is_ssl() ? '✅ https activo' : '⚠ sitio servido por http'; ?></li>
            <li>Culqi: <?php
                if (!$culqi_ok) {
                    echo '⚠ no configurado';
                } elseif ($culqi_test) {
                    echo '⚠ modo TEST (usa pk_live_ / sk_live_ en producción)';
                } else {
                    echo '✅ live configurado';
                }
            ?></li>
            <li>SMTP: <?php
                if ($s['smtp_enabled'] && $s['smtp_host'] !== '') {
                    echo '✅ activo (<code>' . esc_html($s['smtp_host']) . ':' . (int) $s['smtp_port'] . '</code>)';
                } else {
                    echo '⚠ usando mail() del hosting';
                }
            ?></li>
            <li>Cron recordatorios: <?php
                echo $cron_next
                    ? '✅ programado (próximo: ' . esc_html(wp_date('d/m/Y H:i', $cron_next)) . ')'
                    : '⚠ no programado';
            ?></li>
            <li><?php esc_html_e('Backups: configura copias en el hosting (cPanel/Hostinger) o un plugin (UpdraftPlus). El tema no reemplaza backups del servidor.', YUNIORROJAS_TEXT_DOMAIN); ?></li>
        </ul>

        <h2><?php esc_html_e('Cron del sistema (recomendado)', YUNIORROJAS_TEXT_DOMAIN); ?></h2>
        <p><?php esc_html_e('En producción, desactiva el pseudo-cron de WP y usa un cron real:', YUNIORROJAS_TEXT_DOMAIN); ?></p>
        <ol>
            <li>En <code>wp-config.php</code>: <code>define('DISABLE_WP_CRON', true);</code></li>
            <li>Cron del servidor cada 5–15 min:
                <code>* * * * * curl -s <?php echo esc_html(site_url('wp-cron.php?doing_wp_cron')); ?> &gt;/dev/null 2&gt;&amp;1</code>
            </li>
        </ol>

        <?php if ($has_smtp_const) : ?>
            <div class="notice notice-info inline">
                <p>
                    <?php esc_html_e('Hay constantes YUNIORROJAS_SMTP_* en wp-config.php como respaldo. Lo que guardes aquí tiene prioridad. Puedes dejar solo wp-config (sin secretos en la BD) o copiar los valores al formulario y quitar las define después.', YUNIORROJAS_TEXT_DOMAIN); ?>
                </p>
            </div>
        <?php endif; ?>

        <form method="post" action="">
            <?php wp_nonce_field('yuniorrojas_prod_settings_save', 'yuniorrojas_prod_settings_nonce'); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e('Forzar HTTPS', YUNIORROJAS_TEXT_DOMAIN); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="force_ssl" value="1" <?php checked($s['force_ssl']); ?>>
                            <?php esc_html_e('Redirigir todo el front a https://', YUNIORROJAS_TEXT_DOMAIN); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('SMTP', YUNIORROJAS_TEXT_DOMAIN); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="smtp_enabled" value="1" <?php checked($s['smtp_enabled']); ?>>
                            <?php esc_html_e('Usar SMTP para wp_mail (confirmaciones y recordatorios)', YUNIORROJAS_TEXT_DOMAIN); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('Opcional en wp-config: YUNIORROJAS_SMTP_HOST, PORT, USER, PASS, ENCRYPTION, FROM, FROM_NAME, ENABLED.', YUNIORROJAS_TEXT_DOMAIN); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th><label for="smtp_host"><?php esc_html_e('Host SMTP', YUNIORROJAS_TEXT_DOMAIN); ?></label></th>
                    <td><input class="regular-text" type="text" id="smtp_host" name="smtp_host" value="<?php echo esc_attr($s['smtp_host']); ?>" placeholder="smtp.tuservidor.com" autocomplete="off"></td>
                </tr>
                <tr>
                    <th><label for="smtp_port"><?php esc_html_e('Puerto', YUNIORROJAS_TEXT_DOMAIN); ?></label></th>
                    <td><input class="small-text" type="number" id="smtp_port" name="smtp_port" value="<?php echo esc_attr((string) $s['smtp_port']); ?>" min="1"></td>
                </tr>
                <tr>
                    <th><label for="smtp_encryption"><?php esc_html_e('Cifrado', YUNIORROJAS_TEXT_DOMAIN); ?></label></th>
                    <td>
                        <select id="smtp_encryption" name="smtp_encryption">
                            <option value="tls" <?php selected($s['smtp_encryption'], 'tls'); ?>>TLS</option>
                            <option value="ssl" <?php selected($s['smtp_encryption'], 'ssl'); ?>>SSL</option>
                            <option value="none" <?php selected($s['smtp_encryption'], 'none'); ?>><?php esc_html_e('Ninguno', YUNIORROJAS_TEXT_DOMAIN); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="smtp_user"><?php esc_html_e('Usuario', YUNIORROJAS_TEXT_DOMAIN); ?></label></th>
                    <td><input class="regular-text" type="text" id="smtp_user" name="smtp_user" value="<?php echo esc_attr($s['smtp_user']); ?>" autocomplete="off"></td>
                </tr>
                <tr>
                    <th><label for="smtp_pass"><?php esc_html_e('Contraseña', YUNIORROJAS_TEXT_DOMAIN); ?></label></th>
                    <td>
                        <input class="regular-text" type="password" id="smtp_pass" name="smtp_pass" value="" placeholder="<?php echo $s['smtp_pass'] !== '' ? '••••••••' : ''; ?>" autocomplete="new-password">
                        <p class="description"><?php esc_html_e('Deja en blanco para no cambiar la guardada (o la de YUNIORROJAS_SMTP_PASS).', YUNIORROJAS_TEXT_DOMAIN); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="smtp_from"><?php esc_html_e('From email', YUNIORROJAS_TEXT_DOMAIN); ?></label></th>
                    <td><input class="regular-text" type="email" id="smtp_from" name="smtp_from" value="<?php echo esc_attr($s['smtp_from']); ?>" placeholder="noreply@tudominio.com"></td>
                </tr>
                <tr>
                    <th><label for="smtp_from_name"><?php esc_html_e('From nombre', YUNIORROJAS_TEXT_DOMAIN); ?></label></th>
                    <td><input class="regular-text" type="text" id="smtp_from_name" name="smtp_from_name" value="<?php echo esc_attr($s['smtp_from_name']); ?>"></td>
                </tr>
            </table>
            <?php submit_button(__('Guardar ajustes', YUNIORROJAS_TEXT_DOMAIN)); ?>
        </form>

        <h2><?php esc_html_e('Probar SMTP', YUNIORROJAS_TEXT_DOMAIN); ?></h2>
        <form method="post" action="">
            <?php wp_nonce_field('yuniorrojas_smtp_test', 'yuniorrojas_smtp_test_nonce'); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th><label for="smtp_test_to"><?php esc_html_e('Enviar a', YUNIORROJAS_TEXT_DOMAIN); ?></label></th>
                    <td>
                        <input class="regular-text" type="email" id="smtp_test_to" name="smtp_test_to" value="<?php echo esc_attr($admin_email); ?>">
                        <p class="description"><?php esc_html_e('Usa la config efectiva (panel + constantes wp-config).', YUNIORROJAS_TEXT_DOMAIN); ?></p>
                    </td>
                </tr>
            </table>
            <?php submit_button(__('Enviar correo de prueba', YUNIORROJAS_TEXT_DOMAIN), 'secondary'); ?>
        </form>

        <h2><?php esc_html_e('QA pagos (staging)', YUNIORROJAS_TEXT_DOMAIN); ?></h2>
        <ol>
            <li><?php esc_html_e('Culqi test: tarjeta + yape token, ver reserva confirmada y charge_id en admin.', YUNIORROJAS_TEXT_DOMAIN); ?></li>
            <li><?php esc_html_e('Simular fallo de BD/host: no debe quedar cobro huérfano (refund automático).', YUNIORROJAS_TEXT_DOMAIN); ?></li>
            <li><?php esc_html_e('Plin: código + comprobante; verificar y rechazar en admin (rechazo cancela y reembolsa Culqi si aplica).', YUNIORROJAS_TEXT_DOMAIN); ?></li>
            <li><?php esc_html_e('Pago en estudio: cancelación cliente OK; reprogramar mantiene estado.', YUNIORROJAS_TEXT_DOMAIN); ?></li>
            <li><?php esc_html_e('Doble clic en “Proceder al pago”: solo una reserva (rate limit + lock de slot).', YUNIORROJAS_TEXT_DOMAIN); ?></li>
        </ol>
    </div>
    <?php
}

/**
 * Redirección HTTPS del front.
 */
function yuniorrojas_force_ssl_redirect(): void
{
    if (is_admin() || wp_doing_cron() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
        return;
    }
    if (is_ssl()) {
        return;
    }
    $s = yuniorrojas_prod_settings();
    if (empty($s['force_ssl'])) {
        return;
    }
    if (empty($_SERVER['HTTP_HOST']) || empty($_SERVER['REQUEST_URI'])) {
        return;
    }
    $url = 'https://' . sanitize_text_field(wp_unslash((string) $_SERVER['HTTP_HOST']))
        . wp_unslash((string) $_SERVER['REQUEST_URI']);
    wp_redirect($url, 301);
    exit;
}
add_action('template_redirect', 'yuniorrojas_force_ssl_redirect', 1);

/**
 * Configura PHPMailer con SMTP del tema.
 *
 * @param PHPMailer\PHPMailer\PHPMailer $phpmailer Mailer.
 */
function yuniorrojas_phpmailer_smtp($phpmailer): void
{
    $s = yuniorrojas_prod_settings();
    if (empty($s['smtp_enabled']) || $s['smtp_host'] === '') {
        return;
    }

    $phpmailer->isSMTP();
    $phpmailer->Host       = $s['smtp_host'];
    $phpmailer->Port       = (int) $s['smtp_port'];
    $phpmailer->SMTPAuth   = $s['smtp_user'] !== '';
    $phpmailer->Username   = $s['smtp_user'];
    $phpmailer->Password   = $s['smtp_pass'];

    if ($s['smtp_encryption'] === 'ssl') {
        $phpmailer->SMTPSecure = 'ssl';
    } elseif ($s['smtp_encryption'] === 'tls') {
        $phpmailer->SMTPSecure = 'tls';
    } else {
        $phpmailer->SMTPSecure = '';
        $phpmailer->SMTPAutoTLS = false;
    }

    if ($s['smtp_from'] !== '' && is_email($s['smtp_from'])) {
        $phpmailer->From = $s['smtp_from'];
    }
    if ($s['smtp_from_name'] !== '') {
        $phpmailer->FromName = $s['smtp_from_name'];
    }
}
add_action('phpmailer_init', 'yuniorrojas_phpmailer_smtp');

/**
 * From filter cuando SMTP define remitente.
 *
 * @param string $from From.
 */
function yuniorrojas_mail_from(string $from): string
{
    $s = yuniorrojas_prod_settings();
    if (!empty($s['smtp_enabled']) && $s['smtp_from'] !== '' && is_email($s['smtp_from'])) {
        return $s['smtp_from'];
    }
    return $from;
}
add_filter('wp_mail_from', 'yuniorrojas_mail_from');

/**
 * @param string $name Name.
 */
function yuniorrojas_mail_from_name(string $name): string
{
    $s = yuniorrojas_prod_settings();
    if (!empty($s['smtp_enabled']) && $s['smtp_from_name'] !== '') {
        return $s['smtp_from_name'];
    }
    return $name;
}
add_filter('wp_mail_from_name', 'yuniorrojas_mail_from_name');

/**
 * Aviso admin si Culqi está en test en un host no local.
 */
function yuniorrojas_admin_notice_prod(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }
    $host = wp_parse_url(home_url(), PHP_URL_HOST);
    $host = is_string($host) ? strtolower($host) : '';
    $is_local = $host === 'localhost'
        || str_ends_with($host, '.local')
        || str_ends_with($host, '.localsite.io')
        || str_ends_with($host, '.test');

    if ($is_local) {
        return;
    }

    if (function_exists('yuniorrojas_culqi_esta_configurado')
        && yuniorrojas_culqi_esta_configurado()
        && function_exists('yuniorrojas_culqi_es_test')
        && yuniorrojas_culqi_es_test()
    ) {
        echo '<div class="notice notice-warning"><p><strong>Culqi en modo TEST</strong> en un dominio público. Configura llaves <code>pk_live_</code> / <code>sk_live_</code> en Reservas → Ajustes Culqi antes de cobrar de verdad.</p></div>';
    }

    if (!is_ssl()) {
        echo '<div class="notice notice-warning"><p><strong>HTTPS inactivo.</strong> Activa SSL en el hosting y/o marca “Forzar HTTPS” en Reservas → Producción.</p></div>';
    }
}
add_action('admin_notices', 'yuniorrojas_admin_notice_prod');

/**
 * Reprograma cron de recordatorios (timezone del sitio).
 */
function yuniorrojas_cron_recordatorios_ensure(): void
{
    if (!wp_next_scheduled('yuniorrojas_cron_recordatorios')) {
        // Primera ejecución en la próxima hora, cada hora (cubre citas ~24h).
        $start = strtotime(current_time('Y-m-d H:00:00')) + HOUR_IN_SECONDS;
        if ($start <= time()) {
            $start = time() + HOUR_IN_SECONDS;
        }
        wp_schedule_event($start, 'hourly', 'yuniorrojas_cron_recordatorios');
    }
}
add_action('init', 'yuniorrojas_cron_recordatorios_ensure', 20);

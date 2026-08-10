<?php
/**
 * Tema claro / oscuro para todo el panel de administración.
 * Preferencia por usuario + interruptor en la barra superior.
 */

if (!defined('ABSPATH')) {
    exit;
}

const YUNIORROJAS_ADMIN_THEME_META = 'yuniorrojas_admin_theme';

/**
 * Valor guardado: light | dark.
 */
function yuniorrojas_admin_theme_current(): string
{
    $user_id = get_current_user_id();
    if ($user_id < 1) {
        return 'light';
    }

    $stored = get_user_meta($user_id, YUNIORROJAS_ADMIN_THEME_META, true);
    if ($stored === 'dark' || $stored === 'light') {
        return $stored;
    }

    return 'light';
}

/**
 * ¿Modo oscuro activo?
 */
function yuniorrojas_admin_theme_is_dark(): bool
{
    return yuniorrojas_admin_theme_current() === 'dark';
}

/**
 * Clases en <body> del admin.
 *
 * @param string $classes Space-separated classes.
 */
function yuniorrojas_admin_theme_body_class(string $classes): string
{
    $classes .= yuniorrojas_admin_theme_is_dark()
        ? ' jr-admin-theme-dark'
        : ' jr-admin-theme-light';

    return trim($classes);
}
add_filter('admin_body_class', 'yuniorrojas_admin_theme_body_class');

/**
 * Interruptor en la admin bar (esquina superior derecha).
 */
function yuniorrojas_admin_theme_admin_bar(WP_Admin_Bar $bar): void
{
    if (!is_admin() || !is_user_logged_in()) {
        return;
    }

    $is_dark = yuniorrojas_admin_theme_is_dark();
    // lightbulb ≈ “pasar a claro”; admin-appearance ≈ “pasar a oscuro / aspecto”.
    $icon  = $is_dark ? 'dashicons-lightbulb' : 'dashicons-admin-appearance';
    $label = $is_dark
        ? __('Claro', YUNIORROJAS_TEXT_DOMAIN)
        : __('Oscuro', YUNIORROJAS_TEXT_DOMAIN);
    $title = $is_dark
        ? __('Cambiar a tema claro', YUNIORROJAS_TEXT_DOMAIN)
        : __('Cambiar a tema oscuro', YUNIORROJAS_TEXT_DOMAIN);

    $bar->add_node(
        array(
            'id'     => 'jr-admin-theme-toggle',
            'parent' => 'top-secondary',
            'title'  => '<span class="ab-icon dashicons ' . esc_attr($icon) . '" aria-hidden="true"></span>'
                . '<span class="ab-label">' . esc_html($label) . '</span>',
            'href'   => '#jr-admin-theme',
            'meta'   => array(
                'class' => 'jr-admin-theme-toggle-node',
                'title' => $title,
            ),
        )
    );
}
add_action('admin_bar_menu', 'yuniorrojas_admin_theme_admin_bar', 100);

/**
 * CSS dark + JS del interruptor (todas las pantallas de admin).
 * Prioridad alta: va al final para ganar a dashboard/agenda/ingresos/admin.css.
 */
function yuniorrojas_admin_theme_assets(): void
{
    if (!is_user_logged_in()) {
        return;
    }

    $theme_uri  = get_template_directory_uri();
    $theme_path = get_template_directory();
    $css_dark   = $theme_path . '/assets/admin/admin-theme-dark.css';
    $js_path    = $theme_path . '/assets/admin/admin-theme.js';

    // Depende de hojas del core que pintan blanco (Menús, Site Editor, etc.).
    $deps = array();
    global $pagenow;
    $is_site_editor = (is_string($pagenow) && $pagenow === 'site-editor.php');
    if ($is_site_editor) {
        foreach (array('wp-components', 'wp-edit-site') as $handle) {
            if (wp_style_is($handle, 'registered') || wp_style_is($handle, 'enqueued')) {
                $deps[] = $handle;
            }
        }
    }

    if (is_string($pagenow) && $pagenow === 'nav-menus.php') {
        foreach (array('nav-menus', 'edit') as $handle) {
            if (wp_style_is($handle, 'registered') || wp_style_is($handle, 'enqueued')) {
                $deps[] = $handle;
            }
        }
    }

    // All-in-One WP Migration (export/import/backups pintan #f9f9f9).
    foreach (array('ai1wm_export', 'ai1wm_import', 'ai1wm_backups', 'ai1wm_schedules', 'ai1wm_reset', 'ai1wm_servmask') as $handle) {
        if (wp_style_is($handle, 'registered') || wp_style_is($handle, 'enqueued')) {
            $deps[] = $handle;
        }
    }

    wp_enqueue_style(
        'yuniorrojas-admin-theme-dark',
        $theme_uri . '/assets/admin/admin-theme-dark.css',
        $deps,
        file_exists($css_dark) ? (string) filemtime($css_dark) : '1.0.0'
    );

    wp_enqueue_script(
        'yuniorrojas-admin-theme',
        $theme_uri . '/assets/admin/admin-theme.js',
        array(),
        file_exists($js_path) ? (string) filemtime($js_path) : '1.0.0',
        true
    );

    wp_localize_script(
        'yuniorrojas-admin-theme',
        'yuniorrojasAdminTheme',
        array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('yuniorrojas_admin_theme'),
            'theme'   => yuniorrojas_admin_theme_current(),
            'i18n'    => array(
                'toDark'      => __('Oscuro', YUNIORROJAS_TEXT_DOMAIN),
                'toLight'     => __('Claro', YUNIORROJAS_TEXT_DOMAIN),
                'titleDark'   => __('Cambiar a tema oscuro', YUNIORROJAS_TEXT_DOMAIN),
                'titleLight'  => __('Cambiar a tema claro', YUNIORROJAS_TEXT_DOMAIN),
            ),
        )
    );
}
add_action('admin_enqueue_scripts', 'yuniorrojas_admin_theme_assets', 100);
add_action('customize_controls_enqueue_scripts', 'yuniorrojas_admin_theme_assets', 100);

/**
 * Mueve la hoja dark al final del queue (gana a dashboard/agenda/plugins/customizer).
 */
function yuniorrojas_admin_theme_print_dark_last(): void
{
    if (!is_admin() || !is_user_logged_in() || !yuniorrojas_admin_theme_is_dark()) {
        return;
    }

    $handle = 'yuniorrojas-admin-theme-dark';
    if (!wp_style_is($handle, 'enqueued')) {
        return;
    }

    global $wp_styles;
    if (!($wp_styles instanceof WP_Styles)) {
        return;
    }

    $pos = array_search($handle, $wp_styles->queue, true);
    if ($pos === false) {
        return;
    }

    unset($wp_styles->queue[$pos]);
    $wp_styles->queue   = array_values($wp_styles->queue);
    $wp_styles->queue[] = $handle;
}
add_action('admin_enqueue_scripts', 'yuniorrojas_admin_theme_print_dark_last', 999);
add_action('customize_controls_enqueue_scripts', 'yuniorrojas_admin_theme_print_dark_last', 999);

/**
 * Editor de temas/plugins: CodeMirror (code-editor) suele cargar después;
 * declara dependencia e inline de refuerzo para el lienzo.
 *
 * @param string $hook_suffix Current admin page.
 */
function yuniorrojas_admin_theme_file_editor_dark(string $hook_suffix): void
{
    if (!yuniorrojas_admin_theme_is_dark()) {
        return;
    }

    $hooks = array('theme-editor.php', 'plugin-editor.php');
    if (!in_array($hook_suffix, $hooks, true)) {
        return;
    }

    if (!wp_style_is('yuniorrojas-admin-theme-dark', 'enqueued')) {
        return;
    }

    $styles = wp_styles();
    if ($styles instanceof WP_Styles && isset($styles->registered['yuniorrojas-admin-theme-dark'])) {
        foreach (array('code-editor', 'wp-codemirror') as $dep) {
            if (
                (wp_style_is($dep, 'registered') || wp_style_is($dep, 'enqueued'))
                && !in_array($dep, $styles->registered['yuniorrojas-admin-theme-dark']->deps, true)
            ) {
                $styles->registered['yuniorrojas-admin-theme-dark']->deps[] = $dep;
            }
        }
    }

    wp_add_inline_style(
        'yuniorrojas-admin-theme-dark',
        'body.jr-admin-theme-dark .CodeMirror,'
        . 'body.jr-admin-theme-dark .cm-s-default.CodeMirror,'
        . 'body.jr-admin-theme-dark .CodeMirror-scroll,'
        . 'body.jr-admin-theme-dark .CodeMirror-gutters,'
        . 'body.jr-admin-theme-dark #newcontent{'
        . 'background:#0d1117!important;background-color:#0d1117!important;color:#e6edf3!important;}'
    );
}
add_action('admin_enqueue_scripts', 'yuniorrojas_admin_theme_file_editor_dark', 1000);

/**
 * Guarda preferencia (AJAX).
 */
function yuniorrojas_admin_theme_ajax_save(): void
{
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'auth'), 403);
    }

    check_ajax_referer('yuniorrojas_admin_theme', 'nonce');

    $theme = isset($_POST['theme']) ? sanitize_key(wp_unslash((string) $_POST['theme'])) : '';
    if ($theme !== 'dark' && $theme !== 'light') {
        wp_send_json_error(array('message' => 'invalid'), 400);
    }

    update_user_meta(get_current_user_id(), YUNIORROJAS_ADMIN_THEME_META, $theme);

    wp_send_json_success(array('theme' => $theme));
}
add_action('wp_ajax_yuniorrojas_admin_theme_save', 'yuniorrojas_admin_theme_ajax_save');

/**
 * Contenido del iframe TinyMCE en modo oscuro (el CSS del admin no entra al iframe).
 *
 * @param array<string, mixed> $init TinyMCE init.
 * @return array<string, mixed>
 */
function yuniorrojas_admin_theme_tinymce_dark(array $init): array
{
    if (!is_admin() || !yuniorrojas_admin_theme_is_dark()) {
        return $init;
    }

    $content_style = 'body.mce-content-body{'
        . 'background:#12161d!important;'
        . 'color:#eef0f3!important;'
        . 'font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;'
        . 'font-size:14px;line-height:1.6;'
        . 'margin:12px;'
        . '}'
        . 'body.mce-content-body a{color:#7ec0ff!important;}'
        . 'body.mce-content-body h1,body.mce-content-body h2,body.mce-content-body h3,'
        . 'body.mce-content-body h4,body.mce-content-body h5,body.mce-content-body h6{'
        . 'color:#eef0f3!important;}'
        . 'body.mce-content-body blockquote{'
        . 'border-left:3px solid #d4b45a;color:#c9ced6;margin-left:0;padding-left:12px;}'
        . 'body.mce-content-body code,body.mce-content-body pre{'
        . 'background:#1c2129;color:#d4b45a;}'
        . 'body.mce-content-body table,body.mce-content-body td,body.mce-content-body th{'
        . 'border-color:#3a4250!important;color:#eef0f3!important;}'
        . 'body.mce-content-body hr{border-color:#3a4250!important;}'
        . 'body.mce-content-body img{max-width:100%;height:auto;}';

    if (!empty($init['content_style']) && is_string($init['content_style'])) {
        $init['content_style'] .= ' ' . $content_style;
    } else {
        $init['content_style'] = $content_style;
    }

    // Clase extra por si el skin necesita gancho.
    $body_class = isset($init['body_class']) ? (string) $init['body_class'] : '';
    $init['body_class'] = trim($body_class . ' jr-mce-dark');

    return $init;
}
add_filter('tiny_mce_before_init', 'yuniorrojas_admin_theme_tinymce_dark', 20);

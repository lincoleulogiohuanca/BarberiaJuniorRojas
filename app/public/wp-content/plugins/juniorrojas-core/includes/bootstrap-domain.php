<?php
/**
 * Carga el dominio desde el tema (una sola fuente de archivos de lógica)
 * y evita doble registro con el plugin legacy de CPTs.
 *
 * Arquitectura: el plugin es el entrypoint de dominio; el tema solo UI
 * cuando este plugin está activo.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Ruta al tema yuniorrojastheme (activo o en carpeta themes).
 */
function jr_core_theme_path(): string
{
    $theme = get_template_directory();
    if (is_string($theme) && is_dir($theme . '/includes')) {
        $slug = basename(str_replace('\\', '/', $theme));
        if ($slug === 'yuniorrojastheme') {
            return $theme;
        }
    }

    $fallback = WP_CONTENT_DIR . '/themes/yuniorrojastheme';
    return is_dir($fallback) ? $fallback : '';
}

/**
 * Marca al tema para no cargar includes de dominio de nuevo.
 */
function jr_core_mark_domain_loaded(): void
{
    if (!defined('YUNIORROJAS_DOMAIN_LOADED_BY_CORE')) {
        define('YUNIORROJAS_DOMAIN_LOADED_BY_CORE', true);
    }
}

/**
 * ¿El plugin legacy de servicios está activo? Lo desactivamos suavemente
 * (deja de registrar CPT si el tema/core ya lo hace).
 */
function jr_core_suppress_legacy_cpt_plugin(): void
{
    // El plugin legacy registra juniorojas_servicios en init:0.
    // El tema lo registra en init:20 solo si no existe. No hace falta
    // desactivar; solo documentamos unicidad. Aquí impedimos double-hook
    // si alguien incluye ambos loaders.
    remove_action('init', 'juniorrojas_servicios_post_type', 0);
}
add_action('plugins_loaded', 'jr_core_suppress_legacy_cpt_plugin', 20);

/**
 * Sync index tras guardar meta de reserva en admin o código.
 */
function jr_core_sync_on_meta_update($meta_id, $object_id, $meta_key, $_meta_value): void
{
    unset($meta_id, $_meta_value);
    if (!is_string($meta_key) || !str_starts_with($meta_key, '_jr_reserva_')) {
        return;
    }
    if (!function_exists('jr_db_sync_reserva_from_post')) {
        return;
    }
    $post_id = (int) $object_id;
    if ($post_id <= 0) {
        return;
    }
    // Differ a shutdown para batch de metas.
    static $pending = array();
    $pending[$post_id] = true;
    static $hooked = false;
    if ($hooked) {
        return;
    }
    $hooked = true;
    add_action('shutdown', static function () use (&$pending): void {
        foreach (array_keys($pending) as $id) {
            jr_db_sync_reserva_from_post((int) $id);
        }
        $pending = array();
    }, 20);
}
add_action('updated_post_meta', 'jr_core_sync_on_meta_update', 20, 4);
add_action('added_post_meta', 'jr_core_sync_on_meta_update', 20, 4);

/**
 * Admin: botón backfill manual en Reservas → Producción (si existe) o menú Tools.
 */
function jr_core_tools_menu(): void
{
    add_management_page(
        'JR DB Backfill',
        'JR DB Backfill',
        'manage_options',
        'jr-core-backfill',
        'jr_core_tools_render'
    );
}
add_action('admin_menu', 'jr_core_tools_menu');

/**
 * UI backfill.
 */
function jr_core_tools_render(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $msg = '';
    if (
        isset($_POST['jr_backfill_nonce'])
        && wp_verify_nonce(sanitize_text_field(wp_unslash((string) $_POST['jr_backfill_nonce'])), 'jr_backfill')
    ) {
        $n   = function_exists('jr_db_backfill_reservas') ? jr_db_backfill_reservas(2000) : 0;
        $msg = sprintf('Sincronizadas %d reservas a %s.', $n, jr_db_table('reservas'));
    }

    $ready = function_exists('jr_db_ready') && jr_db_ready();
    echo '<div class="wrap"><h1>Junior Rojas — índice de reservas</h1>';
    if ($msg !== '') {
        echo '<div class="notice notice-success"><p>' . esc_html($msg) . '</p></div>';
    }
    echo '<p>Estado tablas: ' . ($ready ? '<strong>OK</strong>' : '<strong>no instaladas</strong>') . '</p>';
    echo '<form method="post">';
    wp_nonce_field('jr_backfill', 'jr_backfill_nonce');
    submit_button('Reindexar reservas (backfill)');
    echo '</form></div>';
}

<?php
/**
 * Hooks de infraestructura del Core (sync índice, legacy CPT, tools).
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Evita doble registro del plugin legacy de CPTs.
 */
function jr_core_suppress_legacy_cpt_plugin(): void
{
    remove_action('init', 'juniorrojas_servicios_post_type', 0);
}
add_action('plugins_loaded', 'jr_core_suppress_legacy_cpt_plugin', 20);

/**
 * Sync index tras guardar meta de reserva.
 *
 * @param int|string $meta_id     Meta ID.
 * @param int        $object_id   Post ID.
 * @param string     $meta_key    Key.
 * @param mixed      $_meta_value Valor.
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
 * Herramientas → JR DB Backfill.
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
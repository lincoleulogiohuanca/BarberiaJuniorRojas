<?php
/**
 * Esquema e índices: reservas, locks de slot, idempotencia Culqi.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Nombre de tabla con prefijo.
 */
function jr_db_table(string $suffix): string
{
    global $wpdb;
    return $wpdb->prefix . 'jr_' . $suffix;
}

/**
 * Crea/actualiza tablas con dbDelta.
 */
function jr_db_install_schema(): void
{
    global $wpdb;

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $charset = $wpdb->get_charset_collate();
    $reservas = jr_db_table('reservas');
    $locks    = jr_db_table('slot_locks');
    $idem     = jr_db_table('culqi_idempotency');

    $sql_reservas = "CREATE TABLE {$reservas} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        post_id bigint(20) unsigned NOT NULL,
        cliente_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
        barbero_id bigint(20) unsigned NOT NULL DEFAULT 0,
        servicio_id bigint(20) unsigned NOT NULL DEFAULT 0,
        fecha date NOT NULL,
        hora char(5) NOT NULL,
        hora_fin char(5) NOT NULL DEFAULT '',
        duracion smallint(5) unsigned NOT NULL DEFAULT 60,
        estado varchar(20) NOT NULL DEFAULT 'confirmada',
        metodo_pago varchar(40) NOT NULL DEFAULT '',
        precio_centimos int(10) unsigned NOT NULL DEFAULT 0,
        descuento_pct tinyint(3) unsigned NOT NULL DEFAULT 0,
        pago_verificado tinyint(1) NOT NULL DEFAULT 0,
        culqi_charge_id varchar(64) NOT NULL DEFAULT '',
        culqi_idempotency_key varchar(64) NOT NULL DEFAULT '',
        created_at datetime NOT NULL,
        updated_at datetime NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY post_id (post_id),
        KEY barbero_fecha_estado (barbero_id, fecha, estado),
        KEY cliente_estado (cliente_user_id, estado),
        KEY charge_id (culqi_charge_id),
        KEY idempotency (culqi_idempotency_key)
    ) {$charset};";

    $sql_locks = "CREATE TABLE {$locks} (
        barbero_id bigint(20) unsigned NOT NULL,
        fecha date NOT NULL,
        hora char(5) NOT NULL,
        locked_at int(10) unsigned NOT NULL,
        lock_token varchar(32) NOT NULL DEFAULT '',
        PRIMARY KEY  (barbero_id, fecha, hora),
        KEY locked_at (locked_at)
    ) {$charset};";

    $sql_idem = "CREATE TABLE {$idem} (
        idempotency_key char(64) NOT NULL,
        charge_id varchar(64) NOT NULL DEFAULT '',
        response_json longtext NULL,
        status varchar(20) NOT NULL DEFAULT 'pending',
        user_id bigint(20) unsigned NOT NULL DEFAULT 0,
        created_at datetime NOT NULL,
        PRIMARY KEY  (idempotency_key),
        KEY charge_id (charge_id),
        KEY status_created (status, created_at)
    ) {$charset};";

    dbDelta($sql_reservas);
    dbDelta($sql_locks);
    dbDelta($sql_idem);
}

/**
 * ¿Tablas disponibles?
 */
function jr_db_ready(): bool
{
    global $wpdb;
    $table = jr_db_table('reservas');
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $found = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
    return $found === $table;
}

/**
 * Upsert fila indexada desde post de reserva.
 */
function jr_db_sync_reserva_from_post(int $post_id): bool
{
    global $wpdb;

    $post_id = absint($post_id);
    if ($post_id <= 0 || !jr_db_ready()) {
        return false;
    }

    $post = get_post($post_id);
    if (!$post instanceof WP_Post) {
        return false;
    }

    $cpt = defined('YUNIORROJAS_CPT_RESERVAS') ? YUNIORROJAS_CPT_RESERVAS : 'jr_reservas';
    if ($post->post_type !== $cpt) {
        return false;
    }

    $meta_key = static function (string $campo) use ($post_id): string {
        $prefix = function_exists('yuniorrojas_reserva_meta_key')
            ? yuniorrojas_reserva_meta_key($campo)
            : '_jr_reserva_' . $campo;
        return (string) get_post_meta($post_id, $prefix, true);
    };

    $fecha = $meta_key('fecha');
    $hora  = $meta_key('hora');
    if (function_exists('yuniorrojas_parsear_hora_cita')) {
        $hora = yuniorrojas_parsear_hora_cita($hora);
    }
    if ($fecha === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || $hora === '') {
        return false;
    }

    $duracion = (int) $meta_key('duracion');
    if ($duracion <= 0) {
        $duracion = 60;
    }

    $hora_fin = $meta_key('hora_fin');
    if ($hora_fin === '' && function_exists('yuniorrojas_hhmm_a_minutos') && function_exists('yuniorrojas_minutos_a_hhmm')) {
        $inicio = yuniorrojas_hhmm_a_minutos($hora);
        if ($inicio >= 0) {
            $hora_fin = yuniorrojas_minutos_a_hhmm($inicio + $duracion);
        }
    }

    $precio_raw = $meta_key('precio');
    $centimos   = function_exists('yuniorrojas_precio_a_centimos')
        ? yuniorrojas_precio_a_centimos($precio_raw)
        : 0;
    if ((int) $meta_key('culqi_amount_centimos') > 0) {
        $centimos = (int) $meta_key('culqi_amount_centimos');
    }

    $now = current_time('mysql');
    $row = array(
        'post_id'               => $post_id,
        'cliente_user_id'       => (int) $meta_key('cliente_user_id'),
        'barbero_id'            => (int) $meta_key('barbero_id'),
        'servicio_id'           => (int) $meta_key('servicio_id'),
        'fecha'                 => $fecha,
        'hora'                  => $hora,
        'hora_fin'              => $hora_fin,
        'duracion'              => $duracion,
        'estado'                => $meta_key('estado') !== '' ? $meta_key('estado') : 'confirmada',
        'metodo_pago'           => $meta_key('metodo_pago'),
        'precio_centimos'       => max(0, $centimos),
        'descuento_pct'         => max(0, min(100, (int) $meta_key('descuento_pct'))),
        'pago_verificado'       => $meta_key('pago_verificado') === '1' ? 1 : 0,
        'culqi_charge_id'       => $meta_key('culqi_charge_id'),
        'culqi_idempotency_key' => $meta_key('culqi_idempotency_key'),
        'updated_at'            => $now,
    );

    $table = jr_db_table('reservas');
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $exists = (int) $wpdb->get_var(
        $wpdb->prepare("SELECT id FROM {$table} WHERE post_id = %d", $post_id) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    );

    if ($exists > 0) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->update($table, $row, array('post_id' => $post_id));
        return true;
    }

    $row['created_at'] = $now;
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
    $wpdb->insert($table, $row);
    return (bool) $wpdb->insert_id;
}

/**
 * Reservas activas del día (indexadas) — sustituye meta_query pesada.
 *
 * @return list<array{id:int,hora:string,duracion:int,inicio:int,fin:int}>
 */
function jr_db_reservas_activas_dia(int $barbero_id, string $fecha, int $exclude_post_id = 0): array
{
    global $wpdb;

    if (!jr_db_ready() || $barbero_id <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        return array();
    }

    $table = jr_db_table('reservas');
    $sql   = "SELECT post_id, hora, duracion FROM {$table}
        WHERE barbero_id = %d AND fecha = %s
        AND estado NOT IN ('cancelada','no_show')";
    $args  = array($barbero_id, $fecha);

    if ($exclude_post_id > 0) {
        $sql   .= ' AND post_id <> %d';
        $args[] = $exclude_post_id;
    }

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
    $rows = $wpdb->get_results($wpdb->prepare($sql, $args), ARRAY_A);
    if (!is_array($rows)) {
        return array();
    }

    $out = array();
    foreach ($rows as $r) {
        $hora = (string) ($r['hora'] ?? '');
        if (function_exists('yuniorrojas_parsear_hora_cita')) {
            $hora = yuniorrojas_parsear_hora_cita($hora);
        }
        $inicio = function_exists('yuniorrojas_hhmm_a_minutos')
            ? yuniorrojas_hhmm_a_minutos($hora)
            : -1;
        if ($inicio < 0) {
            continue;
        }
        $duracion = (int) ($r['duracion'] ?? 60);
        if ($duracion <= 0) {
            $duracion = 60;
        }
        $out[] = array(
            'id'       => (int) ($r['post_id'] ?? 0),
            'hora'     => $hora,
            'duracion' => $duracion,
            'inicio'   => $inicio,
            'fin'      => $inicio + $duracion,
        );
    }

    return $out;
}

/**
 * Lock de slot vía PRIMARY KEY en BD (atómico sin object cache).
 */
function jr_db_slot_adquirir_lock(int $barbero_id, string $fecha, string $hora): bool
{
    global $wpdb;

    if (!jr_db_ready() || $barbero_id <= 0 || $fecha === '' || $hora === '') {
        return false;
    }

    $table = jr_db_table('slot_locks');
    $now   = time();
    $token = wp_generate_password(16, false, false);

    // Limpia locks expirados del slot.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$table} WHERE barbero_id = %d AND fecha = %s AND hora = %s AND locked_at < %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $barbero_id,
            $fecha,
            $hora,
            $now - 90
        )
    );

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
    $ok = $wpdb->query(
        $wpdb->prepare(
            "INSERT IGNORE INTO {$table} (barbero_id, fecha, hora, locked_at, lock_token) VALUES (%d, %s, %s, %d, %s)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $barbero_id,
            $fecha,
            $hora,
            $now,
            $token
        )
    );

    return $ok === 1;
}

/**
 * Libera lock de slot.
 */
function jr_db_slot_liberar_lock(int $barbero_id, string $fecha, string $hora): void
{
    global $wpdb;

    if (!jr_db_ready()) {
        return;
    }

    $table = jr_db_table('slot_locks');
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->delete(
        $table,
        array(
            'barbero_id' => $barbero_id,
            'fecha'      => $fecha,
            'hora'       => $hora,
        ),
        array('%d', '%s', '%s')
    );
}

/**
 * Guarda / reutiliza resultado de cargo por idempotency key.
 *
 * @return array<string,mixed>|null
 */
function jr_db_idempotency_get(string $key): ?array
{
    global $wpdb;

    $key = preg_replace('/[^a-f0-9]/', '', strtolower($key)) ?? '';
    if (strlen($key) < 16 || !jr_db_ready()) {
        return null;
    }

    $table = jr_db_table('culqi_idempotency');
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $row = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM {$table} WHERE idempotency_key = %s", $key), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        ARRAY_A
    );

    if (!is_array($row) || empty($row['response_json'])) {
        return null;
    }

    if (($row['status'] ?? '') !== 'succeeded') {
        return null;
    }

    $data = json_decode((string) $row['response_json'], true);
    return is_array($data) ? $data : null;
}

/**
 * Marca idempotency en curso (anti-doble request en paralelo).
 */
function jr_db_idempotency_begin(string $key, int $user_id): bool
{
    global $wpdb;

    $key = preg_replace('/[^a-f0-9]/', '', strtolower($key)) ?? '';
    if (strlen($key) < 16 || !jr_db_ready()) {
        return false;
    }

    $table = jr_db_table('culqi_idempotency');
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
    $ok = $wpdb->query(
        $wpdb->prepare(
            "INSERT IGNORE INTO {$table} (idempotency_key, charge_id, response_json, status, user_id, created_at)
            VALUES (%s, '', NULL, 'pending', %d, %s)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $key,
            $user_id,
            current_time('mysql')
        )
    );

    if ($ok === 1) {
        return true;
    }

    // Si ya succeeded, el caller usará get().
    // Si pending caducado (>3 min), reintentar.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $row = $wpdb->get_row(
        $wpdb->prepare("SELECT status, created_at FROM {$table} WHERE idempotency_key = %s", $key), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        ARRAY_A
    );
    if (!is_array($row)) {
        return false;
    }
    if (($row['status'] ?? '') === 'succeeded') {
        return false;
    }
    $created = strtotime((string) ($row['created_at'] ?? ''));
    if ($created && (time() - $created) > 180) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->update(
            $table,
            array(
                'status'     => 'pending',
                'created_at' => current_time('mysql'),
            ),
            array('idempotency_key' => $key)
        );
        return true;
    }

    return false;
}

/**
 * Persiste cargo exitoso.
 *
 * @param array<string,mixed> $charge
 */
function jr_db_idempotency_succeed(string $key, array $charge): void
{
    global $wpdb;

    $key = preg_replace('/[^a-f0-9]/', '', strtolower($key)) ?? '';
    if ($key === '' || !jr_db_ready()) {
        return;
    }

    $table = jr_db_table('culqi_idempotency');
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->update(
        $table,
        array(
            'charge_id'     => (string) ($charge['id'] ?? ''),
            'response_json' => wp_json_encode($charge),
            'status'        => 'succeeded',
        ),
        array('idempotency_key' => $key)
    );
}

/**
 * Marca fallo (permite reintento).
 */
function jr_db_idempotency_fail(string $key): void
{
    global $wpdb;

    $key = preg_replace('/[^a-f0-9]/', '', strtolower($key)) ?? '';
    if ($key === '' || !jr_db_ready()) {
        return;
    }

    $table = jr_db_table('culqi_idempotency');
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->update(
        $table,
        array('status' => 'failed'),
        array('idempotency_key' => $key)
    );
}

/**
 * Backfill de posts de reserva → tabla indexada.
 */
function jr_db_backfill_reservas(int $limit = 500): int
{
    if (!jr_db_ready()) {
        return 0;
    }

    $cpt = defined('YUNIORROJAS_CPT_RESERVAS') ? YUNIORROJAS_CPT_RESERVAS : 'jr_reservas';
    $ids = get_posts(array(
        'post_type'              => $cpt,
        'post_status'            => 'any',
        'posts_per_page'         => max(1, min(2000, $limit)),
        'fields'                 => 'ids',
        'no_found_rows'          => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    ));

    $n = 0;
    foreach ($ids as $id) {
        if (jr_db_sync_reserva_from_post((int) $id)) {
            $n++;
        }
    }

    return $n;
}

/**
 * Busca reserva post_id por charge Culqi.
 */
function jr_db_post_id_by_charge(string $charge_id): int
{
    global $wpdb;

    $charge_id = sanitize_text_field($charge_id);
    if ($charge_id === '' || !jr_db_ready()) {
        return 0;
    }

    $table = jr_db_table('reservas');
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $id = (int) $wpdb->get_var(
        $wpdb->prepare("SELECT post_id FROM {$table} WHERE culqi_charge_id = %s LIMIT 1", $charge_id) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    );

    return $id;
}

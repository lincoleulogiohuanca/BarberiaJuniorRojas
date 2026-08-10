<?php
/**
 * Disponibilidad de horarios — slots libres y conflictos por duración.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Convierte H:i a minutos desde medianoche.
 */
function yuniorrojas_hhmm_a_minutos(string $hora): int
{
    $hora = yuniorrojas_parsear_hora_cita($hora);
    if ($hora === '' || !preg_match('/^(\d{1,2}):(\d{2})$/', $hora, $m)) {
        return -1;
    }

    return ((int) $m[1] * 60) + (int) $m[2];
}

/**
 * Minutos → H:i.
 */
function yuniorrojas_minutos_a_hhmm(int $mins): string
{
    $mins = max(0, $mins % (24 * 60));
    return sprintf('%02d:%02d', intdiv($mins, 60), $mins % 60);
}

/**
 * ¿Dos intervalos [start, end) se solapan?
 */
function yuniorrojas_intervalos_solapan(int $a_start, int $a_end, int $b_start, int $b_end): bool
{
    if ($a_start < 0 || $a_end <= $a_start || $b_start < 0 || $b_end <= $b_start) {
        return false;
    }

    return $a_start < $b_end && $b_start < $a_end;
}

/**
 * Reservas activas de un barbero en una fecha (ocupan agenda).
 *
 * @return list<array{id:int,hora:string,duracion:int,inicio:int,fin:int}>
 */
function yuniorrojas_reservas_activas_dia(int $barbero_id, string $fecha, int $exclude_id = 0): array
{
    if ($barbero_id <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        return array();
    }

    $args = array(
        'post_type'              => YUNIORROJAS_CPT_RESERVAS,
        'post_status'            => 'publish',
        'posts_per_page'         => 200,
        'fields'                 => 'ids',
        'no_found_rows'          => true,
        'update_post_term_cache' => false,
        'meta_query'             => array(
            'relation' => 'AND',
            array(
                'key'   => yuniorrojas_reserva_meta_key('barbero_id'),
                'value' => (string) $barbero_id,
            ),
            array(
                'key'   => yuniorrojas_reserva_meta_key('fecha'),
                'value' => $fecha,
            ),
            array(
                'key'     => yuniorrojas_reserva_meta_key('estado'),
                'value'   => array('cancelada', 'no_show'),
                'compare' => 'NOT IN',
            ),
        ),
    );

    if ($exclude_id > 0) {
        $args['post__not_in'] = array($exclude_id);
    }

    // Índice SQL (plugin juniorrojas-core).
    if (function_exists('jr_db_ready') && jr_db_ready() && function_exists('jr_db_reservas_activas_dia')) {
        return jr_db_reservas_activas_dia($barbero_id, $fecha, $exclude_id);
    }

    $ids = get_posts($args);
    $out = array();

    foreach ($ids as $id) {
        $id = (int) $id;
        $hora = (string) get_post_meta($id, yuniorrojas_reserva_meta_key('hora'), true);
        $hora = yuniorrojas_parsear_hora_cita($hora);
        $inicio = yuniorrojas_hhmm_a_minutos($hora);
        if ($inicio < 0) {
            continue;
        }
        $duracion = (int) get_post_meta($id, yuniorrojas_reserva_meta_key('duracion'), true);
        if ($duracion <= 0) {
            $duracion = 60;
        }
        $out[] = array(
            'id'       => $id,
            'hora'     => $hora,
            'duracion' => $duracion,
            'inicio'   => $inicio,
            'fin'      => $inicio + $duracion,
        );
    }

    return $out;
}

/**
 * ¿El slot propuesto choca con alguna reserva activa?
 */
function yuniorrojas_slot_choca_con_ocupadas(
    int $inicio,
    int $duracion,
    array $ocupadas
): bool {
    $fin = $inicio + max(1, $duracion);
    foreach ($ocupadas as $bloque) {
        if (!is_array($bloque)) {
            continue;
        }
        $b_inicio = (int) ($bloque['inicio'] ?? -1);
        $b_fin    = (int) ($bloque['fin'] ?? -1);
        if (yuniorrojas_intervalos_solapan($inicio, $fin, $b_inicio, $b_fin)) {
            return true;
        }
    }

    return false;
}

/**
 * Calcula slots libres para barbero + fecha + duración del servicio.
 *
 * @return array{
 *   fecha:string,
 *   intervalo:int,
 *   duracion:int,
 *   manana:list<array{hora:string,label:string,libre:bool}>,
 *   tarde:list<array{hora:string,label:string,libre:bool}>,
 *   libres:int,
 *   total:int
 * }
 */
function yuniorrojas_calcular_slots_disponibles(
    int $barbero_id,
    string $fecha,
    int $duracion = 60,
    int $exclude_id = 0
): array {
    $empty = array(
        'fecha'     => $fecha,
        'intervalo' => 30,
        'duracion'  => $duracion,
        'manana'    => array(),
        'tarde'     => array(),
        'libres'    => 0,
        'total'     => 0,
    );

    if ($barbero_id <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        return $empty;
    }

    if ($duracion <= 0) {
        $duracion = 60;
    }

    // Bloqueo de agenda (feriado / no atiende).
    if (function_exists('yuniorrojas_fecha_bloqueada') && yuniorrojas_fecha_bloqueada($barbero_id, $fecha)) {
        return $empty;
    }

    $horario = function_exists('yuniorrojas_obtener_horario_barbero')
        ? yuniorrojas_obtener_horario_barbero($barbero_id)
        : array();

    if (!is_array($horario) || empty($horario['dias']) || !is_array($horario['dias'])) {
        return $empty;
    }

    $dt = DateTime::createFromFormat('Y-m-d', $fecha);
    if (!$dt instanceof DateTime) {
        return $empty;
    }

    $dow = (string) (int) $dt->format('w');
    $dia = $horario['dias'][$dow] ?? null;
    if (!is_array($dia) || empty($dia['activo'])) {
        return $empty;
    }

    $inicio_dia = yuniorrojas_hhmm_a_minutos((string) ($dia['inicio'] ?? ''));
    $fin_dia    = yuniorrojas_hhmm_a_minutos((string) ($dia['fin'] ?? ''));
    $intervalo  = (int) ($horario['intervalo'] ?? 30);
    if ($intervalo <= 0) {
        $intervalo = 30;
    }

    if ($inicio_dia < 0 || $fin_dia <= $inicio_dia) {
        return $empty;
    }

    $ocupadas = yuniorrojas_reservas_activas_dia($barbero_id, $fecha, $exclude_id);
    $hoy      = current_time('Y-m-d');
    $ahora    = current_time('H:i');
    $ahora_m  = yuniorrojas_hhmm_a_minutos($ahora);

    $manana = array();
    $tarde  = array();
    $libres = 0;
    $total  = 0;

    for ($mins = $inicio_dia; $mins + $duracion <= $fin_dia; $mins += $intervalo) {
        $total++;
        $libre = !yuniorrojas_slot_choca_con_ocupadas($mins, $duracion, $ocupadas);

        // Hoy: no ofrecer slots que ya pasaron (+15 min margen).
        if ($fecha === $hoy && $ahora_m >= 0 && $mins <= $ahora_m + 15) {
            $libre = false;
        }

        $hora  = yuniorrojas_minutos_a_hhmm($mins);
        $label = yuniorrojas_formatear_hora_label($hora);
        $slot  = array(
            'hora'  => $hora,
            'label' => $label,
            'libre' => $libre,
        );

        if ($libre) {
            $libres++;
        }

        if ($mins < 13 * 60) {
            $manana[] = $slot;
        } else {
            $tarde[] = $slot;
        }
    }

    return array(
        'fecha'     => $fecha,
        'intervalo' => $intervalo,
        'duracion'  => $duracion,
        'manana'    => $manana,
        'tarde'     => $tarde,
        'libres'    => $libres,
        'total'     => $total,
    );
}

/**
 * ¿Hay al menos un hueco libre ese día?
 */
function yuniorrojas_dia_tiene_slots_libres(
    int $barbero_id,
    string $fecha,
    int $duracion = 60,
    int $exclude_id = 0
): bool {
    $slots = yuniorrojas_calcular_slots_disponibles($barbero_id, $fecha, $duracion, $exclude_id);
    return (int) ($slots['libres'] ?? 0) > 0;
}

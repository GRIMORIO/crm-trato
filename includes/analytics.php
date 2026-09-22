<?php
/**
 * includes/analytics.php — Motor de analítica del pipeline de ventas (Fase 1).
 *
 * Funciones puras: reciben PDO + filtros, devuelven arrays. No hacen echo.
 * Solo LEE lo que ya existe: `deals`, `stages`, `deal_stage_history`, `accounts`,
 * `live_chats`. Degrada limpio si falta `deal_stage_history` o las columnas de
 * multi-pipeline / cierre.
 *
 * Todo el cálculo estadístico (media, mediana) se hace en PHP a partir de listas
 * pequeñas — así el módulo funciona igual en MariaDB local y en el hosting, sin
 * depender de `MEDIAN()` / funciones de ventana.
 */

/* ------------------------------------------------------------------ *
 *  Detección de esquema (cacheada)
 * ------------------------------------------------------------------ */

function an_col_exists(PDO $pdo, string $table, string $col): bool
{
    static $cache = [];
    $k = "$table.$col";
    if (!array_key_exists($k, $cache)) {
        $s = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns
                            WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?");
        $s->execute([$table, $col]);
        $cache[$k] = (bool) $s->fetchColumn();
    }
    return $cache[$k];
}

function an_table_exists(PDO $pdo, string $table): bool
{
    static $cache = [];
    if (!array_key_exists($table, $cache)) {
        $s = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables
                            WHERE table_schema = DATABASE() AND table_name = ?");
        $s->execute([$table]);
        $cache[$table] = (bool) $s->fetchColumn();
    }
    return $cache[$table];
}

function an_has_history(PDO $pdo): bool   { return an_table_exists($pdo, 'deal_stage_history'); }
function an_has_pipelines(PDO $pdo): bool { return an_col_exists($pdo, 'stages', 'pipeline_id') && an_table_exists($pdo, 'pipelines'); }
function an_has_closed_at(PDO $pdo): bool { return an_col_exists($pdo, 'deals', 'closed_at'); }
function an_has_win_prob(PDO $pdo): bool  { return an_col_exists($pdo, 'stages', 'win_probability'); }

/* ------------------------------------------------------------------ *
 *  Estadística
 * ------------------------------------------------------------------ */

function an_avg(array $nums)
{
    $nums = array_values(array_filter($nums, fn($n) => $n !== null));
    return $nums ? array_sum($nums) / count($nums) : null;
}

function an_median(array $nums)
{
    $nums = array_values(array_filter($nums, fn($n) => $n !== null));
    $n = count($nums);
    if ($n === 0) return null;
    sort($nums);
    $mid = intdiv($n, 2);
    return ($n % 2) ? $nums[$mid] : ($nums[$mid - 1] + $nums[$mid]) / 2;
}

/** Días (float) entre dos timestamps 'Y-m-d H:i:s'. Null si alguno falta o el orden es inválido. */
function an_days_between(?string $a, ?string $b)
{
    if (!$a || !$b) return null;
    $ta = strtotime($a);
    $tb = strtotime($b);
    if (!$ta || !$tb || $tb < $ta) return null;
    return ($tb - $ta) / 86400;
}

/* ------------------------------------------------------------------ *
 *  Metadatos del pipeline
 * ------------------------------------------------------------------ */

function an_pipelines(PDO $pdo): array
{
    if (!an_has_pipelines($pdo)) return [];
    return $pdo->query("SELECT id, name FROM pipelines ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
}

/** Etapas ordenadas por posición, opcionalmente de un solo pipeline. */
function an_stages(PDO $pdo, $pipeline_id = null): array
{
    $cols = "id, name, position" . (an_has_win_prob($pdo) ? ", win_probability" : "");
    $sql  = "SELECT $cols FROM stages";
    $args = [];
    if ($pipeline_id && an_has_pipelines($pdo)) {
        $sql .= " WHERE pipeline_id = ?";
        $args[] = $pipeline_id;
    }
    $sql .= " ORDER BY position, id";
    $st = $pdo->prepare($sql);
    $st->execute($args);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

/** Índice de la etapa a partir de la cual un negocio se considera "calificado" (para velocidad). */
function an_qualified_stage_index(array $stages, PDO $pdo): int
{
    // 1) Por nombre: la primera que hable de calificación / BANT / discovery.
    foreach ($stages as $i => $s) {
        if (preg_match('/calific|bant|meddic|discovery|demostra|muestra/i', $s['name'])) return $i;
    }
    // 2) Por probabilidad de cierre: la primera con win_probability >= 40.
    if (an_has_win_prob($pdo)) {
        foreach ($stages as $i => $s) {
            if (isset($s['win_probability']) && (int) $s['win_probability'] >= 40) return $i;
        }
    }
    // 3) Fallback: la mitad del pipeline.
    return (int) floor(max(0, count($stages) - 1) / 2);
}

/* ------------------------------------------------------------------ *
 *  Carga cruda de negocios + historial para el rango / pipeline
 * ------------------------------------------------------------------ */

function an_load_deals(PDO $pdo, $pipeline_id = null): array
{
    $stages   = an_stages($pdo, $pipeline_id);
    $stage_id_set = array_column($stages, 'id');
    $pos_by_id = [];
    foreach ($stages as $s) $pos_by_id[(int) $s['id']] = (int) $s['position'];

    $closed_expr = an_has_closed_at($pdo) ? "d.closed_at" : "NULL";
    $sql = "SELECT d.id, d.title, d.value, d.status, d.stage_id,
                   d.created_at, d.updated_at, $closed_expr AS closed_at,
                   s.name AS cur_stage, s.position AS cur_pos
            FROM deals d
            JOIN stages s ON s.id = d.stage_id";
    $args = [];
    if ($pipeline_id && an_has_pipelines($pdo)) {
        $sql .= " WHERE s.pipeline_id = ?";
        $args[] = $pipeline_id;
    }
    $st = $pdo->prepare($sql);
    $st->execute($args);
    $deals = $st->fetchAll(PDO::FETCH_ASSOC);

    // Historial por negocio (si existe la tabla), mapeando stage_id -> posición del pipeline.
    $history = [];
    if ($deals && an_has_history($pdo)) {
        $ids = array_column($deals, 'id');
        $in  = implode(',', array_fill(0, count($ids), '?'));
        $hst = $pdo->prepare("SELECT deal_id, stage_id, entered_at
                              FROM deal_stage_history
                              WHERE deal_id IN ($in)
                              ORDER BY deal_id, id");
        $hst->execute($ids);
        foreach ($hst->fetchAll(PDO::FETCH_ASSOC) as $h) {
            $sid = (int) $h['stage_id'];
            // Ignora filas de etapas que no pertenecen al pipeline filtrado.
            if ($pipeline_id && $stage_id_set && !in_array($sid, $stage_id_set)) continue;
            $history[(int) $h['deal_id']][] = [
                'stage_id'   => $sid,
                'pos'        => $pos_by_id[$sid] ?? null,
                'entered_at' => $h['entered_at'],
            ];
        }
    }

    return ['stages' => $stages, 'pos_by_id' => $pos_by_id, 'deals' => $deals, 'history' => $history];
}

/* ------------------------------------------------------------------ *
 *  Informe completo
 * ------------------------------------------------------------------ */

/**
 * @param string $from  'Y-m-d'
 * @param string $to    'Y-m-d' (inclusivo — internamente se usa < día siguiente)
 */
function an_report(PDO $pdo, string $from, string $to, $pipeline_id = null): array
{
    $from_dt = $from . ' 00:00:00';
    $to_dt   = date('Y-m-d 00:00:00', strtotime($to . ' +1 day'));
    $now     = date('Y-m-d H:i:s');

    $load    = an_load_deals($pdo, $pipeline_id);
    $stages  = $load['stages'];
    $deals   = $load['deals'];
    $history = $load['history'];
    $has_history = an_has_history($pdo) && !empty($history);

    $in_range = fn($ts) => $ts && $ts >= $from_dt && $ts < $to_dt;

    /* ---- Bloque 1: volumen -------------------------------------- */

    $volume = [
        'leads_created'    => 0,   // oportunidades creadas en el rango
        'accounts_created' => 0,
        'chats_captured'   => 0,
        'won_count' => 0, 'won_value' => 0.0,
        'lost_count' => 0, 'lost_value' => 0.0,
        'open_count' => 0, 'open_value' => 0.0,
    ];

    foreach ($deals as $d) {
        $closed = $d['closed_at'] ?: (($d['status'] !== 'Open') ? $d['updated_at'] : null);
        if ($in_range($d['created_at'])) $volume['leads_created']++;
        if ($d['status'] === 'Open') {
            $volume['open_count']++;
            $volume['open_value'] += (float) $d['value'];
        } elseif ($d['status'] === 'Won' && $in_range($closed)) {
            $volume['won_count']++;
            $volume['won_value'] += (float) $d['value'];
        } elseif ($d['status'] === 'Lost' && $in_range($closed)) {
            $volume['lost_count']++;
            $volume['lost_value'] += (float) $d['value'];
        }
    }

    try {
        $s = $pdo->prepare("SELECT COUNT(*) FROM accounts WHERE created_at >= ? AND created_at < ?");
        $s->execute([$from_dt, $to_dt]);
        $volume['accounts_created'] = (int) $s->fetchColumn();
    } catch (Exception $e) { /* sin columna created_at */ }

    try {
        $s = $pdo->prepare("SELECT COUNT(*) FROM live_chats
                            WHERE created_at >= ? AND created_at < ?
                              AND (lead_email IS NOT NULL AND lead_email <> ''
                                   OR lead_name IS NOT NULL AND lead_name <> '')");
        $s->execute([$from_dt, $to_dt]);
        $volume['chats_captured'] = (int) $s->fetchColumn();
    } catch (Exception $e) { /* sin tabla live_chats */ }

    // Ocupación actual + flujo (throughput) por etapa
    $stage_rows = [];
    foreach ($stages as $s) {
        $stage_rows[(int) $s['id']] = [
            'id' => (int) $s['id'], 'name' => $s['name'], 'position' => (int) $s['position'],
            'open_count' => 0, 'open_value' => 0.0, 'throughput' => 0,
        ];
    }
    foreach ($deals as $d) {
        $sid = (int) $d['stage_id'];
        if (isset($stage_rows[$sid]) && $d['status'] === 'Open') {
            $stage_rows[$sid]['open_count']++;
            $stage_rows[$sid]['open_value'] += (float) $d['value'];
        }
    }
    if ($has_history) {
        foreach ($history as $rows) {
            $seen = [];
            foreach ($rows as $r) {
                if (!$in_range($r['entered_at'])) continue;
                if (isset($stage_rows[$r['stage_id']]) && empty($seen[$r['stage_id']])) {
                    $stage_rows[$r['stage_id']]['throughput']++;
                    $seen[$r['stage_id']] = true;
                }
            }
        }
    }
    $stage_rows = array_values($stage_rows);

    /* ---- Bloque 2: conversión de cohorte ----------------------- */

    $cohort = array_values(array_filter($deals, fn($d) => $in_range($d['created_at'])));
    $n_stages = count($stages);
    $reached = array_fill(0, max(1, $n_stages), 0);

    foreach ($cohort as $d) {
        // Posición máxima alcanzada: por historial si existe, si no por etapa actual
        // (+ 1 si el negocio está cerrado ganado, para contarlo en la última etapa).
        $max_pos = (int) $d['cur_pos'];
        if ($has_history && !empty($history[(int) $d['id']])) {
            foreach ($history[(int) $d['id']] as $r) {
                if ($r['pos'] !== null) $max_pos = max($max_pos, $r['pos']);
            }
        }
        foreach ($stages as $i => $s) {
            if ((int) $s['position'] <= $max_pos) $reached[$i]++;
        }
    }

    $conv_rows = [];
    $cohort_size = count($cohort);
    foreach ($stages as $i => $s) {
        $prev = $i > 0 ? $reached[$i - 1] : $cohort_size;
        $conv_rows[] = [
            'name'       => $s['name'],
            'reached'    => $reached[$i],
            'pct_total'  => $cohort_size ? $reached[$i] / $cohort_size * 100 : null,
            'pct_step'   => $prev ? $reached[$i] / $prev * 100 : null,
        ];
    }

    // Etapa con mayor fuga (menor pct_step, ignorando la primera)
    $biggest_drop = null;
    foreach ($conv_rows as $i => $r) {
        if ($i === 0 || $r['pct_step'] === null) continue;
        if ($biggest_drop === null || $r['pct_step'] < $conv_rows[$biggest_drop]['pct_step']) {
            $biggest_drop = $i;
        }
    }

    // Desenlace de la cohorte
    $cohort_won = $cohort_lost = $cohort_open = 0;
    foreach ($cohort as $d) {
        if ($d['status'] === 'Won') $cohort_won++;
        elseif ($d['status'] === 'Lost') $cohort_lost++;
        else $cohort_open++;
    }
    $cohort_winrate = ($cohort_won + $cohort_lost) ? $cohort_won / ($cohort_won + $cohort_lost) * 100 : null;

    /* ---- Bloque 3: tiempo entre etapas ------------------------- */

    $stage_dur = [];   // stage_id => ['closed' => [días...], 'open' => [días...]]
    foreach ($stages as $s) $stage_dur[(int) $s['id']] = ['closed' => [], 'open' => []];

    $cycle_won = $cycle_lost = $first_touch = [];

    foreach ($deals as $d) {
        $did  = (int) $d['id'];
        $rows = $history[$did] ?? [];
        $closed_ts = $d['closed_at'] ?: (($d['status'] !== 'Open') ? $d['updated_at'] : null);

        // Ciclo de venta (cerrados dentro del rango)
        if ($d['status'] === 'Won' && $in_range($closed_ts)) {
            $cycle_won[] = an_days_between($d['created_at'], $closed_ts);
        } elseif ($d['status'] === 'Lost' && $in_range($closed_ts)) {
            $cycle_lost[] = an_days_between($d['created_at'], $closed_ts);
        }

        if (!$rows) continue;

        // Primer contacto: creación -> primera entrada a una etapa de posición > la mínima
        $min_pos = min(array_map(fn($r) => $r['pos'] ?? PHP_INT_MAX, $rows));
        foreach ($rows as $r) {
            if ($r['pos'] !== null && $r['pos'] > $min_pos) {
                $ft = an_days_between($d['created_at'], $r['entered_at']);
                if ($ft !== null && $in_range($r['entered_at'])) $first_touch[] = $ft;
                break;
            }
        }

        // Duración por etapa: intervalos consecutivos del historial
        $cnt = count($rows);
        for ($i = 0; $i < $cnt; $i++) {
            $sid   = $rows[$i]['stage_id'];
            $start = $rows[$i]['entered_at'];
            $end   = ($i + 1 < $cnt) ? $rows[$i + 1]['entered_at'] : $closed_ts;
            if (!isset($stage_dur[$sid])) continue;
            if ($end) {
                $dur = an_days_between($start, $end);
                if ($dur !== null && $in_range($end)) $stage_dur[$sid]['closed'][] = $dur;
            } elseif ($i + 1 === $cnt && $d['status'] === 'Open') {
                $dur = an_days_between($start, $now);
                if ($dur !== null) $stage_dur[$sid]['open'][] = $dur;
            }
        }
    }

    $timing_rows = [];
    foreach ($stages as $s) {
        $sid = (int) $s['id'];
        $c = $stage_dur[$sid]['closed'];
        $o = $stage_dur[$sid]['open'];
        $timing_rows[] = [
            'name'        => $s['name'],
            'avg_days'    => an_avg($c),
            'median_days' => an_median($c),
            'sample'      => count($c),
            'open_now'    => count($o),
            'open_avg'    => an_avg($o),
        ];
    }

    $timing = [
        'stages'         => $timing_rows,
        'cycle_won_avg'    => an_avg($cycle_won),
        'cycle_won_median' => an_median($cycle_won),
        'cycle_won_n'      => count($cycle_won),
        'cycle_lost_avg'    => an_avg($cycle_lost),
        'cycle_lost_median' => an_median($cycle_lost),
        'cycle_lost_n'      => count($cycle_lost),
        'first_touch_avg'    => an_avg($first_touch),
        'first_touch_median' => an_median($first_touch),
        'first_touch_n'      => count($first_touch),
    ];

    /* ---- Deals estancados (abiertos, sin movimiento) ----------- */

    // Mediana de días por etapa para el umbral dinámico
    $median_by_sid = [];
    foreach ($stages as $s) {
        $sid = (int) $s['id'];
        $median_by_sid[$sid] = an_median($stage_dur[$sid]['closed']);
    }

    $stalled = [];
    foreach ($deals as $d) {
        if ($d['status'] !== 'Open') continue;
        $did  = (int) $d['id'];
        $rows = $history[$did] ?? [];
        $last = $rows ? end($rows)['entered_at'] : $d['created_at'];
        $idle = an_days_between($last, $now);
        if ($idle === null) continue;
        $sid = (int) $d['stage_id'];
        $threshold = max(21, ($median_by_sid[$sid] ?? 0) * 1.5);
        if ($idle >= $threshold) {
            $stalled[] = [
                'id' => $did, 'title' => $d['title'], 'stage' => $d['cur_stage'],
                'value' => (float) $d['value'], 'idle_days' => $idle, 'threshold' => $threshold,
            ];
        }
    }
    usort($stalled, fn($a, $b) => $b['idle_days'] <=> $a['idle_days']);

    /* ---- Bloque 4: velocidad del pipeline ---------------------- */

    $q_idx = an_qualified_stage_index($stages, $pdo);
    $q_pos = $stages[$q_idx]['position'] ?? 0;
    $q_deals = array_values(array_filter($deals, fn($d) => $d['status'] === 'Open' && (int) $d['cur_pos'] >= $q_pos));
    $q_count = count($q_deals);
    $q_value = an_avg(array_map(fn($d) => (float) $d['value'], $q_deals));

    $period_won = $volume['won_count'];
    $period_lost = $volume['lost_count'];
    $win_rate = ($period_won + $period_lost) ? $period_won / ($period_won + $period_lost) : null;
    $cycle_days = $timing['cycle_won_median'] ?? $timing['cycle_won_avg'];
    // Un ciclo por debajo de 1 día casi siempre es dato de prueba/importado — no da una
    // velocidad significativa, se descarta para no reportar cifras infladas.
    if ($cycle_days !== null && $cycle_days < 1) $cycle_days = null;

    $velocity = [
        'qualified_open'   => $q_count,
        'qualified_stage'  => $stages[$q_idx]['name'] ?? '—',
        'avg_value'        => $q_value,
        'win_rate'         => $win_rate !== null ? $win_rate * 100 : null,
        'cycle_days'       => $cycle_days,
        'per_day'          => ($q_count && $q_value !== null && $win_rate !== null && $cycle_days)
                                ? ($q_count * $q_value * $win_rate) / $cycle_days : null,
    ];

    return [
        'meta' => [
            'from' => $from, 'to' => $to,
            'has_history' => $has_history,
            'n_deals' => count($deals),
        ],
        'volume'   => $volume,
        'stage_rows' => $stage_rows,
        'conversion' => [
            'cohort_size' => $cohort_size,
            'rows'        => $conv_rows,
            'biggest_drop'=> $biggest_drop,
            'won'  => $cohort_won, 'lost' => $cohort_lost, 'open' => $cohort_open,
            'win_rate' => $cohort_winrate,
        ],
        'timing'   => $timing,
        'stalled'  => $stalled,
        'velocity' => $velocity,
    ];
}

<?php
/**
 * analytics.php — Analítica del embudo de ventas (Fase 1).
 *
 * Tres bloques:
 *   1. Volumen histórico  — leads / oportunidades / cuentas y flujo por etapa.
 *   2. Conversión         — % de una cohorte que alcanza cada etapa (efectividad).
 *   3. Tiempo entre etapas — días por fase, ciclo de venta, deals estancados.
 *
 * Filtros por querystring: ?from=YYYY-MM-DD&to=YYYY-MM-DD&pipeline_id=N&preset=90d
 * Motor: includes/analytics.php (funciones puras). Gráficos: Chart.js (ya cargado
 * en header.php).
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_login();
// Fase 1: solo admin (la vista por zona/asesor llega en Fase 2).
if (function_exists('require_admin')) { require_admin(); }
require_once __DIR__ . '/includes/analytics.php';
require_once __DIR__ . '/includes/header.php';

/* ---- Filtros ------------------------------------------------------ */

$presets = [
    '30d'  => ['label' => 'Últimos 30 días',  'from' => date('Y-m-d', strtotime('-30 days'))],
    '90d'  => ['label' => 'Últimos 90 días',  'from' => date('Y-m-d', strtotime('-90 days'))],
    '180d' => ['label' => 'Últimos 6 meses',  'from' => date('Y-m-d', strtotime('-180 days'))],
    '365d' => ['label' => 'Últimos 12 meses', 'from' => date('Y-m-d', strtotime('-365 days'))],
    'ytd'  => ['label' => 'Este año (YTD)',   'from' => date('Y-01-01')],
];

$preset = $_GET['preset'] ?? '90d';
$today  = date('Y-m-d');

if ($preset === 'custom' && !empty($_GET['from']) && !empty($_GET['to'])) {
    $from = $_GET['from'];
    $to   = $_GET['to'];
} else {
    if (!isset($presets[$preset])) $preset = '90d';
    $from = $presets[$preset]['from'];
    $to   = $today;
}

$pipelines   = an_pipelines($pdo);
$pipeline_id = null;
if ($pipelines) {
    $pipeline_id = isset($_GET['pipeline_id']) ? (int) $_GET['pipeline_id'] : (int) $pipelines[0]['id'];
}

$R = an_report($pdo, $from, $to, $pipeline_id);

/* ---- Helpers de formato ----------------------------------------- */

function a_num($n, $dec = 0) { return $n === null ? '—' : number_format($n, $dec); }
function a_pct($n)           { return $n === null ? '—' : number_format($n, 1) . '%'; }
function a_days($n)          { return $n === null ? '—' : number_format($n, 1) . ' d'; }
function a_money($n)         { return '$' . number_format((float) $n, 0); }

$conv_labels = array_map(fn($r) => $r['name'], $R['conversion']['rows']);
$conv_reached = array_map(fn($r) => $r['reached'], $R['conversion']['rows']);
$dur_labels = array_map(fn($r) => $r['name'], $R['timing']['stages']);
$dur_avg = array_map(fn($r) => $r['avg_days'] === null ? null : round($r['avg_days'], 1), $R['timing']['stages']);
$flow_labels = array_map(fn($r) => $r['name'], $R['stage_rows']);
$flow_throughput = array_map(fn($r) => $r['throughput'], $R['stage_rows']);
$flow_open = array_map(fn($r) => $r['open_count'], $R['stage_rows']);
?>

<!-- ================= BARRA DE FILTROS ================= -->
<form method="get" class="card-section" style="margin-bottom:1.5rem; display:flex; flex-wrap:wrap; gap:1rem; align-items:flex-end;">
    <div class="form-group" style="margin-bottom:0;">
        <label for="preset">Rango</label>
        <select name="preset" id="preset" onchange="a_toggleCustom(this.value)">
            <?php foreach ($presets as $k => $p): ?>
                <option value="<?php echo $k; ?>" <?php echo $preset === $k ? 'selected' : ''; ?>><?php echo $p['label']; ?></option>
            <?php endforeach; ?>
            <option value="custom" <?php echo $preset === 'custom' ? 'selected' : ''; ?>>Personalizado…</option>
        </select>
    </div>
    <div class="form-group a-custom" style="margin-bottom:0; <?php echo $preset === 'custom' ? '' : 'display:none;'; ?>">
        <label for="from">Desde</label>
        <input type="date" name="from" id="from" value="<?php echo htmlspecialchars($from); ?>">
    </div>
    <div class="form-group a-custom" style="margin-bottom:0; <?php echo $preset === 'custom' ? '' : 'display:none;'; ?>">
        <label for="to">Hasta</label>
        <input type="date" name="to" id="to" value="<?php echo htmlspecialchars($to); ?>">
    </div>
    <?php if ($pipelines): ?>
    <div class="form-group" style="margin-bottom:0;">
        <label for="pipeline_id">Embudo</label>
        <select name="pipeline_id" id="pipeline_id">
            <?php foreach ($pipelines as $p): ?>
                <option value="<?php echo $p['id']; ?>" <?php echo $pipeline_id == $p['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($p['name']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>
    <button type="submit" class="btn-submit" style="width:auto; padding:0.6rem 1.4rem;">Aplicar</button>
    <span style="color:var(--text-muted); font-size:0.8rem; margin-left:auto;">
        <?php echo htmlspecialchars($from); ?> → <?php echo htmlspecialchars($to); ?> · <?php echo $R['meta']['n_deals']; ?> negocios en el embudo
    </span>
</form>

<?php if (!$R['meta']['has_history']): ?>
<div class="card-section" style="margin-bottom:1.5rem; border-color:rgba(245,158,11,0.3); background:rgba(245,158,11,0.05);">
    <strong style="color:#f59e0b;">⚠️ Sin historial de fases</strong>
    <p style="color:var(--text-muted); font-size:0.875rem; margin-top:0.5rem;">
        La tabla <code>deal_stage_history</code> no existe o está vacía. Se muestra solo la ocupación
        actual del embudo y los cierres del período. Corré <code>update_deal_stage_history.php</code>
        una vez para habilitar conversión de cohorte y tiempo entre etapas.
    </p>
</div>
<?php endif; ?>

<!-- ================= KPIs ================= -->
<div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:1rem; margin-bottom:2rem;">
    <?php
    $kpis = [
        ['Oportunidades creadas', a_num($R['volume']['leads_created']), 'var(--color-primary)'],
        ['Cuentas nuevas',        a_num($R['volume']['accounts_created']), 'var(--color-primary)'],
        ['Chats con contacto',    a_num($R['volume']['chats_captured']), 'var(--color-primary)'],
        ['Ganados',               a_money($R['volume']['won_value']) . ' · ' . $R['volume']['won_count'], 'var(--color-success)'],
        ['Perdidos',              a_money($R['volume']['lost_value']) . ' · ' . $R['volume']['lost_count'], 'var(--color-error)'],
        ['Win rate (período)',    a_pct($R['velocity']['win_rate']), '#fff'],
        ['Ciclo de venta (mediana)', a_days($R['timing']['cycle_won_median']), '#fff'],
        ['Velocidad del pipeline', $R['velocity']['per_day'] === null ? '—' : a_money($R['velocity']['per_day']) . '/día', 'var(--color-secondary)'],
    ];
    foreach ($kpis as [$label, $val, $color]): ?>
        <div class="card-section" style="margin-bottom:0; padding:1.1rem;">
            <span style="display:block; font-size:0.72rem; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.04em; margin-bottom:0.35rem;"><?php echo $label; ?></span>
            <span style="display:block; font-size:1.15rem; font-weight:800; color:<?php echo $color; ?>;"><?php echo $val; ?></span>
        </div>
    <?php endforeach; ?>
</div>

<!-- ================= BLOQUE 1: VOLUMEN / FLUJO ================= -->
<div class="card-section" style="margin-bottom:1.5rem;">
    <div class="section-header"><h2>1. Volumen por etapa — ocupación actual y flujo del período</h2></div>
    <div style="height:300px; position:relative;"><canvas id="flowChart"></canvas></div>
    <table style="width:100%; margin-top:1.5rem; font-size:0.85rem; border-collapse:collapse;">
        <thead><tr style="text-align:left; color:var(--text-muted); border-bottom:1px solid var(--border-color);">
            <th style="padding:0.5rem;">Etapa</th>
            <th style="padding:0.5rem; text-align:right;">Entraron (período)</th>
            <th style="padding:0.5rem; text-align:right;">Abiertos hoy</th>
            <th style="padding:0.5rem; text-align:right;">$ Abierto hoy</th>
        </tr></thead>
        <tbody>
        <?php foreach ($R['stage_rows'] as $r): ?>
            <tr style="border-bottom:1px solid rgba(255,255,255,0.04);">
                <td style="padding:0.5rem;"><?php echo htmlspecialchars($r['name']); ?></td>
                <td style="padding:0.5rem; text-align:right;"><?php echo $R['meta']['has_history'] ? a_num($r['throughput']) : '—'; ?></td>
                <td style="padding:0.5rem; text-align:right;"><?php echo a_num($r['open_count']); ?></td>
                <td style="padding:0.5rem; text-align:right;"><?php echo a_money($r['open_value']); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- ================= BLOQUE 2: CONVERSIÓN ================= -->
<div class="card-section" style="margin-bottom:1.5rem;">
    <div class="section-header"><h2>2. Conversión de la cohorte — <?php echo $R['conversion']['cohort_size']; ?> oportunidades creadas en el período</h2></div>
    <?php if (!$R['meta']['has_history']): ?>
        <p style="color:var(--text-muted); font-size:0.875rem;">Requiere historial de fases.</p>
    <?php elseif ($R['conversion']['cohort_size'] === 0): ?>
        <p style="color:var(--text-muted); font-size:0.875rem;">No se crearon oportunidades en este rango. Ampliá el período.</p>
    <?php else: ?>
        <div style="height:280px; position:relative;"><canvas id="convChart"></canvas></div>
        <table style="width:100%; margin-top:1.5rem; font-size:0.85rem; border-collapse:collapse;">
            <thead><tr style="text-align:left; color:var(--text-muted); border-bottom:1px solid var(--border-color);">
                <th style="padding:0.5rem;">Etapa</th>
                <th style="padding:0.5rem; text-align:right;">Alcanzaron</th>
                <th style="padding:0.5rem; text-align:right;">% del total</th>
                <th style="padding:0.5rem; text-align:right;">% vs etapa anterior</th>
            </tr></thead>
            <tbody>
            <?php foreach ($R['conversion']['rows'] as $i => $r): ?>
                <tr style="border-bottom:1px solid rgba(255,255,255,0.04); <?php echo $i === $R['conversion']['biggest_drop'] ? 'background:rgba(239,68,68,0.08);' : ''; ?>">
                    <td style="padding:0.5rem;">
                        <?php echo htmlspecialchars($r['name']); ?>
                        <?php if ($i === $R['conversion']['biggest_drop']): ?>
                            <span style="color:var(--color-error); font-size:0.72rem; font-weight:700;"> ← mayor fuga</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding:0.5rem; text-align:right;"><?php echo a_num($r['reached']); ?></td>
                    <td style="padding:0.5rem; text-align:right;"><?php echo a_pct($r['pct_total']); ?></td>
                    <td style="padding:0.5rem; text-align:right;"><?php echo $i === 0 ? '—' : a_pct($r['pct_step']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <p style="color:var(--text-muted); font-size:0.8rem; margin-top:1rem;">
            Desenlace de la cohorte: <strong style="color:var(--color-success);"><?php echo $R['conversion']['won']; ?></strong> ganadas ·
            <strong style="color:var(--color-error);"><?php echo $R['conversion']['lost']; ?></strong> perdidas ·
            <strong style="color:var(--color-primary);"><?php echo $R['conversion']['open']; ?></strong> abiertas ·
            win rate de la cohorte <strong><?php echo a_pct($R['conversion']['win_rate']); ?></strong>
            <?php if ($R['conversion']['open'] > 0): ?><span> (las abiertas aún pueden cambiarlo)</span><?php endif; ?>
        </p>
    <?php endif; ?>
</div>

<!-- ================= BLOQUE 3: TIEMPO ================= -->
<div class="card-section" style="margin-bottom:1.5rem;">
    <div class="section-header"><h2>3. Tiempo entre etapas y ciclo de venta</h2></div>
    <?php if (!$R['meta']['has_history']): ?>
        <p style="color:var(--text-muted); font-size:0.875rem;">Requiere historial de fases.</p>
    <?php else: ?>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:1rem; margin-bottom:1.5rem;">
            <div class="card-section" style="margin-bottom:0; padding:1rem;">
                <span style="font-size:0.72rem; color:var(--text-muted); text-transform:uppercase;">Ciclo de venta — Ganados</span>
                <div style="font-size:1.1rem; font-weight:800; color:var(--color-success); margin-top:0.3rem;">
                    <?php echo a_days($R['timing']['cycle_won_median']); ?> <span style="font-size:0.75rem; color:var(--text-muted); font-weight:500;">mediana</span>
                </div>
                <span style="font-size:0.75rem; color:var(--text-muted);">media <?php echo a_days($R['timing']['cycle_won_avg']); ?> · n=<?php echo $R['timing']['cycle_won_n']; ?></span>
            </div>
            <div class="card-section" style="margin-bottom:0; padding:1rem;">
                <span style="font-size:0.72rem; color:var(--text-muted); text-transform:uppercase;">Ciclo de venta — Perdidos</span>
                <div style="font-size:1.1rem; font-weight:800; color:var(--color-error); margin-top:0.3rem;">
                    <?php echo a_days($R['timing']['cycle_lost_median']); ?> <span style="font-size:0.75rem; color:var(--text-muted); font-weight:500;">mediana</span>
                </div>
                <span style="font-size:0.75rem; color:var(--text-muted);">media <?php echo a_days($R['timing']['cycle_lost_avg']); ?> · n=<?php echo $R['timing']['cycle_lost_n']; ?></span>
            </div>
            <div class="card-section" style="margin-bottom:0; padding:1rem;">
                <span style="font-size:0.72rem; color:var(--text-muted); text-transform:uppercase;">Primer contacto</span>
                <div style="font-size:1.1rem; font-weight:800; color:#fff; margin-top:0.3rem;">
                    <?php echo a_days($R['timing']['first_touch_median']); ?> <span style="font-size:0.75rem; color:var(--text-muted); font-weight:500;">mediana</span>
                </div>
                <span style="font-size:0.75rem; color:var(--text-muted);">creación → salida de la 1ª etapa · n=<?php echo $R['timing']['first_touch_n']; ?></span>
            </div>
        </div>
        <div style="height:280px; position:relative;"><canvas id="durChart"></canvas></div>
        <table style="width:100%; margin-top:1.5rem; font-size:0.85rem; border-collapse:collapse;">
            <thead><tr style="text-align:left; color:var(--text-muted); border-bottom:1px solid var(--border-color);">
                <th style="padding:0.5rem;">Etapa</th>
                <th style="padding:0.5rem; text-align:right;">Media</th>
                <th style="padding:0.5rem; text-align:right;">Mediana</th>
                <th style="padding:0.5rem; text-align:right;">n (cerrados)</th>
                <th style="padding:0.5rem; text-align:right;">Abiertos ahí ahora</th>
            </tr></thead>
            <tbody>
            <?php foreach ($R['timing']['stages'] as $r): ?>
                <tr style="border-bottom:1px solid rgba(255,255,255,0.04);">
                    <td style="padding:0.5rem;"><?php echo htmlspecialchars($r['name']); ?></td>
                    <td style="padding:0.5rem; text-align:right;"><?php echo a_days($r['avg_days']); ?></td>
                    <td style="padding:0.5rem; text-align:right;"><?php echo a_days($r['median_days']); ?></td>
                    <td style="padding:0.5rem; text-align:right;"><?php echo $r['sample']; ?></td>
                    <td style="padding:0.5rem; text-align:right;"><?php echo $r['open_now']; ?><?php echo $r['open_avg'] !== null ? ' (' . a_days($r['open_avg']) . ')' : ''; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- ================= DEALS ESTANCADOS ================= -->
<div class="card-section" style="margin-bottom:1.5rem;">
    <div class="section-header"><h2>Oportunidades estancadas — abiertas sin cambio de etapa</h2></div>
    <?php if (!$R['stalled']): ?>
        <p style="color:var(--text-muted); font-size:0.875rem;">Ninguna oportunidad abierta supera su umbral de inactividad. 👌</p>
    <?php else: ?>
        <table style="width:100%; font-size:0.85rem; border-collapse:collapse;">
            <thead><tr style="text-align:left; color:var(--text-muted); border-bottom:1px solid var(--border-color);">
                <th style="padding:0.5rem;">Oportunidad</th>
                <th style="padding:0.5rem;">Etapa actual</th>
                <th style="padding:0.5rem; text-align:right;">Días sin moverse</th>
                <th style="padding:0.5rem; text-align:right;">Umbral</th>
                <th style="padding:0.5rem; text-align:right;">Valor</th>
            </tr></thead>
            <tbody>
            <?php foreach ($R['stalled'] as $s): ?>
                <tr style="border-bottom:1px solid rgba(255,255,255,0.04);">
                    <td style="padding:0.5rem;"><a href="pipeline.php" style="color:var(--color-primary); text-decoration:none;"><?php echo htmlspecialchars($s['title']); ?></a></td>
                    <td style="padding:0.5rem;"><?php echo htmlspecialchars($s['stage']); ?></td>
                    <td style="padding:0.5rem; text-align:right; color:var(--color-error); font-weight:700;"><?php echo a_days($s['idle_days']); ?></td>
                    <td style="padding:0.5rem; text-align:right; color:var(--text-muted);"><?php echo a_days($s['threshold']); ?></td>
                    <td style="padding:0.5rem; text-align:right;"><?php echo a_money($s['value']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <p style="color:var(--text-muted); font-size:0.78rem; margin-top:0.75rem;">Umbral = máx(21 días, 1.5× la mediana de días de esa etapa).</p>
    <?php endif; ?>
</div>

<div class="card-section" style="font-size:0.8rem; color:var(--text-muted);">
    <strong>Cómo leer esto.</strong> La <em>cohorte</em> son las oportunidades <u>creadas</u> dentro del rango — su conversión y desenlace se siguen aunque cierren después.
    El <em>flujo por etapa</em> cuenta cuántos negocios <u>entraron</u> a esa fase en el rango (no los que están ahí hoy).
    Para negocios anteriores a la migración de <code>deal_stage_history</code>, los tiempos por fase son una estimación del backfill; exactos solo desde entonces.
</div>

<script>
function a_toggleCustom(v) {
    document.querySelectorAll('.a-custom').forEach(el => el.style.display = (v === 'custom') ? '' : 'none');
}
const A_GRID = 'rgba(255,255,255,0.05)', A_TICK = '#94a3b8';
const a_baseOpts = {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { labels: { color: A_TICK } } },
    scales: { x: { grid: { color: A_GRID }, ticks: { color: A_TICK } }, y: { grid: { color: A_GRID }, ticks: { color: A_TICK } } }
};

new Chart(document.getElementById('flowChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($flow_labels); ?>,
        datasets: [
            { label: 'Entraron (período)', data: <?php echo json_encode($flow_throughput); ?>, backgroundColor: 'rgba(6,182,212,0.5)', borderColor: '#06b6d4', borderWidth: 1, borderRadius: 5 },
            { label: 'Abiertos hoy', data: <?php echo json_encode($flow_open); ?>, backgroundColor: 'rgba(168,85,247,0.45)', borderColor: '#a855f7', borderWidth: 1, borderRadius: 5 }
        ]
    },
    options: a_baseOpts
});

<?php if ($R['meta']['has_history'] && $R['conversion']['cohort_size'] > 0): ?>
new Chart(document.getElementById('convChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($conv_labels); ?>,
        datasets: [{ label: 'Oportunidades que alcanzaron la etapa', data: <?php echo json_encode($conv_reached); ?>, backgroundColor: 'rgba(6,182,212,0.5)', borderColor: '#06b6d4', borderWidth: 1, borderRadius: 5 }]
    },
    options: { ...a_baseOpts, indexAxis: 'y', plugins: { legend: { display: false } } }
});
<?php endif; ?>

<?php if ($R['meta']['has_history']): ?>
new Chart(document.getElementById('durChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($dur_labels); ?>,
        datasets: [{ label: 'Días promedio en la etapa', data: <?php echo json_encode($dur_avg); ?>, backgroundColor: 'rgba(16,185,129,0.45)', borderColor: '#10b981', borderWidth: 1, borderRadius: 5 }]
    },
    options: { ...a_baseOpts, plugins: { legend: { display: false } } }
});
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

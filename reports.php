<?php
/**
 * reports.php — Informes analíticos y gráficos de rendimiento de ventas para TIPS CRM
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/header.php';

// Obtener grupo de visibilidad
$restriction = get_visibility_restriction();

// Meta de Ventas Mensuales (Establecida de forma dinámica en settings, por defecto $30,000)
$monthly_target = floatval($pdo->query("SELECT setting_value FROM crm_settings WHERE setting_key = 'sales_quota_target'")->fetchColumn() ?: 30000.00);

if ($restriction !== '') {
    // 1. Obtener conteo y suma por Estado de Deal (filtrado por asesor)
    $status_stmt = $pdo->prepare("
        SELECT status, COUNT(*) AS count, SUM(value) AS total_val 
        FROM deals 
        WHERE assigned_agent = ?
        GROUP BY status
    ");
    $status_stmt->execute([$restriction]);
    $status_data = $status_stmt->fetchAll();

    // 2. Obtener distribución por Etapa del Pipeline (filtrado por asesor)
    $stage_stmt = $pdo->prepare("
        SELECT s.name AS stage_name, COUNT(d.id) AS deal_count, IFNULL(SUM(d.value), 0) AS total_val
        FROM stages s
        LEFT JOIN deals d ON d.stage_id = s.id AND d.status = 'Open' AND d.assigned_agent = ?
        GROUP BY s.id
        ORDER BY s.position
    ");
    $stage_stmt->execute([$restriction]);
    $stage_dist = $stage_stmt->fetchAll();

    // Calcular valor ponderado de todos los deals abiertos (filtrado por asesor)
    $open_weighted_stmt = $pdo->prepare("
        SELECT SUM(d.value * (s.win_probability / 100)) AS weighted_total
        FROM deals d
        JOIN stages s ON d.stage_id = s.id
        WHERE d.status = 'Open' AND d.assigned_agent = ?
    ");
    $open_weighted_stmt->execute([$restriction]);
    $open_weighted_val = floatval($open_weighted_stmt->fetchColumn() ?: 0);
} else {
    // 1. Obtener conteo y suma por Estado de Deal (administrador)
    $status_data = $pdo->query("
        SELECT status, COUNT(*) AS count, SUM(value) AS total_val 
        FROM deals 
        GROUP BY status
    ")->fetchAll();

    // 2. Obtener distribución por Etapa del Pipeline (administrador)
    $stage_dist = $pdo->query("
        SELECT s.name AS stage_name, COUNT(d.id) AS deal_count, IFNULL(SUM(d.value), 0) AS total_val
        FROM stages s
        LEFT JOIN deals d ON d.stage_id = s.id AND d.status = 'Open'
        GROUP BY s.id
        ORDER BY s.position
    ")->fetchAll();

    // Calcular valor ponderado de todos los deals abiertos (administrador)
    $open_weighted_val = floatval($pdo->query("
        SELECT SUM(d.value * (s.win_probability / 100)) AS weighted_total
        FROM deals d
        JOIN stages s ON d.stage_id = s.id
        WHERE d.status = 'Open'
    ")->fetchColumn() ?: 0);
}

$won_count = 0; $won_val = 0;
$lost_count = 0; $lost_val = 0;
$open_count = 0; $open_val = 0;

foreach ($status_data as $sd) {
    if ($sd['status'] == 'Won') {
        $won_count = $sd['count'];
        $won_val = floatval($sd['total_val']);
    } elseif ($sd['status'] == 'Lost') {
        $lost_count = $sd['count'];
        $lost_val = floatval($sd['total_val']);
    } elseif ($sd['status'] == 'Open') {
        $open_count = $sd['count'];
        $open_val = floatval($sd['total_val']);
    }
}

$stage_names = [];
$stage_counts = [];
$stage_values = [];

foreach ($stage_dist as $row) {
    $stage_names[] = $row['stage_name'];
    $stage_counts[] = intval($row['deal_count']);
    $stage_values[] = floatval($row['total_val']);
}

$target_pct = min(100, round(($won_val / $monthly_target) * 100));

// Pronóstico Total (Ganadas + Ponderado de Abiertas)
$total_forecast_val = $won_val + $open_weighted_val;
$forecast_target_pct = min(100, round(($total_forecast_val / $monthly_target) * 100));
?>

<!-- Sección de Indicadores de Meta -->
<div style="display: grid; grid-template-columns: 1fr 1fr 1.2fr; gap: 1.5rem; margin-bottom: 2rem;">
    <!-- Meta de Ventas Mensual (Ventas Won Reales) -->
    <div class="card-section" style="margin-bottom:0; display:flex; flex-direction:column; justify-content:center;">
        <h3 style="font-size:0.9rem; color:var(--text-muted); margin-bottom:0.5rem; display:flex; align-items:center; gap:0.25rem;">
            <i data-lucide="check-circle" style="width:14px; height:14px; color:var(--color-success); display:inline-block;"></i> Ventas Cerradas (Won)
        </h3>
        <div style="font-size:1.6rem; font-weight:800; color:#fff; margin-bottom:0.25rem;">
            $<?php echo number_format($won_val, 2); ?>
            <span style="font-size:0.85rem; color:var(--text-dark); font-weight:500;">/ $<?php echo number_format($monthly_target, 0); ?></span>
        </div>
        <div style="width:100%; height:8px; background:rgba(255,255,255,0.05); border-radius:999px; margin: 0.75rem 0; overflow:hidden;">
            <div style="width: <?php echo $target_pct; ?>%; height:100%; background:linear-gradient(to right, var(--color-primary), var(--color-success)); border-radius:999px;"></div>
        </div>
        <div class="flex-between" style="font-size:0.75rem;">
            <span style="color:var(--text-muted);"><?php echo $target_pct; ?>% de la Meta</span>
            <span style="color:var(--color-success); font-weight:600;">Realizado</span>
        </div>
    </div>
    
    <!-- Pronóstico de Ventas Ponderado (Forecast) -->
    <div class="card-section" style="margin-bottom:0; display:flex; flex-direction:column; justify-content:center;">
        <h3 style="font-size:0.9rem; color:var(--text-muted); margin-bottom:0.5rem; display:flex; align-items:center; gap:0.25rem;">
            <i data-lucide="trending-up" style="width:14px; height:14px; color:var(--color-primary); display:inline-block;"></i> Pronóstico Ponderado
        </h3>
        <div style="font-size:1.6rem; font-weight:800; color:#fff; margin-bottom:0.25rem;">
            $<?php echo number_format($total_forecast_val, 2); ?>
            <span style="font-size:0.85rem; color:var(--text-dark); font-weight:500;">/ $<?php echo number_format($monthly_target, 0); ?></span>
        </div>
        <div style="width:100%; height:8px; background:rgba(255,255,255,0.05); border-radius:999px; margin: 0.75rem 0; overflow:hidden;">
            <div style="width: <?php echo $forecast_target_pct; ?>%; height:100%; background:linear-gradient(to right, var(--color-primary), #a855f7); border-radius:999px;"></div>
        </div>
        <div class="flex-between" style="font-size:0.75rem;">
            <span style="color:var(--text-muted);"><?php echo $forecast_target_pct; ?>% Proyectado</span>
            <span style="color:var(--color-primary); font-weight:600;">Won + Open Ponderado</span>
        </div>
    </div>
    
    <!-- Estadísticas Globales del Historial -->
    <div class="card-section" style="margin-bottom:0; display:flex; flex-direction:column; justify-content:center;">
        <h3 style="font-size:1rem; color:#fff; margin-bottom:0.75rem; border-bottom:1px dashed var(--border-color); padding-bottom:0.25rem;">Pipeline vs Cierres</h3>
        <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:0.5rem; text-align:center;">
            <div>
                <span style="display:block; font-size:1.1rem; font-weight:700; color:var(--color-success);">$<?php echo number_format($won_val, 0); ?></span>
                <span style="font-size:0.7rem; color:var(--text-muted);"><?php echo $won_count; ?> Ganadas</span>
            </div>
            <div>
                <span style="display:block; font-size:1.1rem; font-weight:700; color:var(--color-error);">$<?php echo number_format($lost_val, 0); ?></span>
                <span style="font-size:0.7rem; color:var(--text-muted);"><?php echo $lost_count; ?> Perdidas</span>
            </div>
            <div>
                <span style="display:block; font-size:1.1rem; font-weight:700; color:var(--color-primary);">$<?php echo number_format($open_val, 0); ?></span>
                <span style="font-size:0.7rem; color:var(--text-muted);"><?php echo $open_count; ?> Abiertas</span>
            </div>
        </div>
        <div style="font-size:0.7rem; color:var(--text-dark); margin-top:0.75rem; text-align:center;">
            Valor Ponderado de Abiertas: <strong>$<?php echo number_format($open_weighted_val, 2); ?></strong>
        </div>
    </div>
</div>

<!-- Rejilla de Gráficos -->
<div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 2rem; margin-top: 2rem;">
    
    <!-- GRÁFICO 1: DISTRIBUCIÓN POR ETAPA -->
    <div class="card-section" style="margin-bottom:0;">
        <div class="section-header">
            <h2>Valor de Ventas por Etapa ($)</h2>
        </div>
        <div style="height: 300px; position: relative;">
            <canvas id="stageChart"></canvas>
        </div>
    </div>
    
    <!-- GRÁFICO 2: RELACIÓN GANADAS VS PERDIDAS -->
    <div class="card-section" style="margin-bottom:0;">
        <div class="section-header">
            <h2>Tasa de Conversión (Historial)</h2>
        </div>
        <div style="height: 300px; position: relative; display:flex; justify-content:center; align-items:center;">
            <div style="width: 250px; height: 250px;">
                <canvas id="conversionChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
    // Configuración de Chart.js
    
    // Gráfico de Barras: Oportunidades por Etapa
    const stageCtx = document.getElementById('stageChart').getContext('2d');
    new Chart(stageCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($stage_names); ?>,
            datasets: [{
                label: 'Monto del Pipeline ($)',
                data: <?php echo json_encode($stage_values); ?>,
                backgroundColor: 'rgba(6, 182, 212, 0.45)',
                borderColor: '#06b6d4',
                borderWidth: 1,
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    grid: { color: 'rgba(255, 255, 255, 0.05)' },
                    ticks: { color: '#94a3b8' }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#94a3b8' }
                }
            }
        }
    });

    // Gráfico Doughnut: Conversión
    const convCtx = document.getElementById('conversionChart').getContext('2d');
    new Chart(convCtx, {
        type: 'doughnut',
        data: {
            labels: ['Ganadas', 'Perdidas', 'Abiertas'],
            datasets: [{
                data: [<?php echo "$won_count, $lost_count, $open_count"; ?>],
                backgroundColor: [
                    'rgba(16, 185, 129, 0.55)', // verde
                    'rgba(239, 68, 68, 0.55)',  // rojo
                    'rgba(6, 182, 212, 0.55)'   // cian
                ],
                borderColor: [
                    '#10b981',
                    '#ef4444',
                    '#06b6d4'
                ],
                borderWidth: 1.5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { color: '#94a3b8', font: { family: 'Outfit' } }
                }
            },
            cutout: '70%'
        }
    });
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>

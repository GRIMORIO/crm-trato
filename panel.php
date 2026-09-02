<?php
/**
 * panel.php — Dashboard Principal del CRM B2B TIPS (antes index.php; ahora index.php es la landing pública)
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/header.php';

// 1. Consultar Estadísticas Clave
$pipeline_val = $pdo->query("SELECT SUM(value) FROM deals WHERE status = 'Open'")->fetchColumn() ?: 0.00;
$won_val = $pdo->query("SELECT SUM(value) FROM deals WHERE status = 'Won'")->fetchColumn() ?: 0.00;
$open_deals_count = $pdo->query("SELECT COUNT(*) FROM deals WHERE status = 'Open'")->fetchColumn() ?: 0;

// Obtener meta de ventas mensual
$sales_quota_target = floatval($pdo->query("SELECT setting_value FROM crm_settings WHERE setting_key = 'sales_quota_target'")->fetchColumn() ?: 30000);
$quota_pct = min(100, round(($won_val / $sales_quota_target) * 100));

// Definir consejo contextual del Sales Coach según el progreso de la meta mensual
$coach_preamble = "";
if ($quota_pct < 40) {
    $coach_preamble = "El avance de la meta mensual está bajo (¡vamos al " . $quota_pct . "%!). Brian Tracy aconseja enfocarnos en la prospección y motivación:";
    $random_tip = $pdo->query("SELECT * FROM sales_tips WHERE category IN ('Motivation', 'Time_Management') ORDER BY RAND() LIMIT 1")->fetch(PDO::FETCH_ASSOC);
} elseif ($quota_pct >= 40 && $quota_pct < 75) {
    $coach_preamble = "Estamos a mitad de camino de la meta mensual (" . $quota_pct . "%). Es momento de acelerar el seguimiento con los clientes:";
    $random_tip = $pdo->query("SELECT * FROM sales_tips WHERE category = 'Follow-up' ORDER BY RAND() LIMIT 1")->fetch(PDO::FETCH_ASSOC);
} elseif ($quota_pct >= 40 && $quota_pct < 100) {
    // Si la meta está cerca
    $coach_preamble = "¡Excelente avance! Llevamos el " . $quota_pct . "% de la meta mensual. Brian Tracy aconseja cómo empujar el cierre:";
    $random_tip = $pdo->query("SELECT * FROM sales_tips WHERE category = 'Closing' ORDER BY RAND() LIMIT 1")->fetch(PDO::FETCH_ASSOC);
} else {
    $coach_preamble = "🏆 ¡Extraordinario! Meta de ventas del mes superada (estamos al " . $quota_pct . "%). Brian Tracy aconseja mantener el impulso:";
    $random_tip = $pdo->query("SELECT * FROM sales_tips WHERE category = 'Motivation' ORDER BY RAND() LIMIT 1")->fetch(PDO::FETCH_ASSOC);
}

// Fallback por si la base no retornase datos por filtro de categoría
if (!$random_tip) {
    $random_tip = $pdo->query("SELECT * FROM sales_tips ORDER BY RAND() LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $coach_preamble = "Brian Tracy (Sales Coach) nos aconseja:";
}

// Actividades urgentes (hoy y atrasadas)
$today_start = date('Y-m-d 00:00:00');
$today_end = date('Y-m-d 23:59:59');
$urgent_activities_count = $pdo->query("
    SELECT COUNT(*) FROM activities 
    WHERE status = 'Pending' AND due_date <= '$today_end'
")->fetchColumn() ?: 0;

// 2. Obtener Actividades Próximas Urgentes (detalladas)
$stmt_urgent_list = $pdo->query("
    SELECT act.*, d.title AS deal_title 
    FROM activities act
    LEFT JOIN deals d ON act.deal_id = d.id
    WHERE act.status = 'Pending' AND act.due_date <= '$today_end'
    ORDER BY act.due_date ASC
    LIMIT 5
");
$urgent_list = $stmt_urgent_list->fetchAll();

// 3. Obtener deals abiertos recientes
$stmt_recent_deals = $pdo->query("
    SELECT d.*, a.name AS account_name 
    FROM deals d
    LEFT JOIN accounts a ON d.account_id = a.id
    WHERE d.status = 'Open'
    ORDER BY d.created_at DESC
    LIMIT 5
");
$recent_deals = $stmt_recent_deals->fetchAll();
?>

<!-- BANNER SALES COACH: BRIAN TRACY (CONTRAÍBLE Y CONTEXTUAL) -->
<?php if ($random_tip): 
    $initials = 'BT';
    if ($random_tip['author'] === 'T. Harv Eker') {
        $initials = 'HE';
    }
?>
<!-- Estado Minimizado -->
<div id="coach-banner-collapsed" style="display: none; background: rgba(6, 182, 212, 0.05); border: 1px solid rgba(6, 182, 212, 0.15); border-radius: 8px; padding: 0.5rem 1rem; margin-bottom: 1.5rem; align-items: center; justify-content: space-between; gap: 1rem;">
    <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; color: var(--text-muted);">
        <span style="background: linear-gradient(135deg, #06b6d4, #a855f7); width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 800; font-size: 0.5rem; flex-shrink:0;" title="<?php echo htmlspecialchars($random_tip['author']); ?>"><?php echo $initials; ?></span>
        <span>💡 El <strong>Sales Coach (<?php echo htmlspecialchars($random_tip['author']); ?>)</strong> está listo para guiarte en tus metas del mes.</span>
    </div>
    <button onclick="toggleCoachBanner(false)" class="btn-action btn-sm" style="padding: 2px 8px; font-size: 0.7rem; border-color: rgba(6, 182, 212, 0.3); background: rgba(6, 182, 212, 0.1); color: var(--color-primary);">Expandir Coach</button>
</div>

<!-- Estado Maximizado -->
<div id="coach-banner-expanded" style="background: linear-gradient(135deg, rgba(6, 182, 212, 0.08), rgba(168, 85, 247, 0.08)); border: 1px solid rgba(6, 182, 212, 0.2); border-radius: 12px; padding: 1.25rem 1.5rem; margin-bottom: 2rem; display: flex; align-items: center; justify-content: space-between; gap: 1.5rem; position: relative;">
    <div style="display: flex; align-items: center; gap: 1.25rem; flex: 1;">
        <div style="background: linear-gradient(135deg, #06b6d4, #a855f7); width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 800; font-size: 1rem; box-shadow: 0 4px 10px rgba(6,182,212,0.3); flex-shrink: 0;" title="<?php echo htmlspecialchars($random_tip['author']); ?>">
            <?php echo $initials; ?>
        </div>
        <div>
            <div style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--color-primary); font-weight: 700; margin-bottom: 0.25rem; display: flex; align-items: center; gap: 0.25rem;">
                <i data-lucide="sparkles" style="width: 12px; height: 12px;"></i>
                <?php echo htmlspecialchars($coach_preamble); ?>
            </div>
            <p style="font-size: 0.95rem; color: #f1f5f9; font-style: italic; margin: 0; line-height: 1.4;">
                "<?php echo htmlspecialchars($random_tip['tip_text']); ?>"
            </p>
            <span style="font-size: 0.8rem; color: var(--text-muted); font-weight: 500; display: block; margin-top: 0.25rem;">— <?php echo htmlspecialchars($random_tip['author']); ?> (Categoría: <?php echo htmlspecialchars($random_tip['category']); ?>)</span>
        </div>
    </div>
    <div style="display: flex; flex-direction: column; gap: 0.4rem; align-items: flex-end; flex-shrink: 0;">
        <button onclick="window.location.reload();" class="btn-action btn-sm" style="width: 100%; padding: 5px 10px; font-size: 0.75rem; border-color: rgba(255,255,255,0.1); background: rgba(255,255,255,0.02); color: var(--text-muted);" title="Cargar otro consejo">
            <i data-lucide="refresh-cw" style="width: 11px; height: 11px; display: inline-block; vertical-align: middle; margin-right: 4px;"></i>
            Otro Consejo
        </button>
        <button onclick="toggleCoachBanner(true)" class="btn-action btn-sm" style="width: 100%; padding: 5px 10px; font-size: 0.75rem; border-color: rgba(239,68,68,0.15); background: rgba(239,68,68,0.05); color: #fca5a5;" title="Minimizar Coach">
            Minimizar Space
        </button>
    </div>
</div>
<?php endif; ?>

<!-- Rejilla de Indicadores (KPIs) -->
<div class="grid-stats">
    <div class="stat-card">
        <div class="stat-icon blue">
            <i data-lucide="line-chart"></i>
        </div>
        <div class="stat-info">
            <span class="val">$<?php echo number_format($pipeline_val, 2); ?></span>
            <span class="lbl">Valor del Pipeline</span>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon green">
            <i data-lucide="award"></i>
        </div>
        <div class="stat-info">
            <span class="val">$<?php echo number_format($won_val, 2); ?></span>
            <span class="lbl">Ventas Ganadas B2B</span>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon purple">
            <i data-lucide="folder-kanban"></i>
        </div>
        <div class="stat-info">
            <span class="val"><?php echo $open_deals_count; ?></span>
            <span class="lbl">Deals Activos</span>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon yellow">
            <i data-lucide="alarm-clock"></i>
        </div>
        <div class="stat-info">
            <span class="val"><?php echo $urgent_activities_count; ?></span>
            <span class="lbl">Actividades para Hoy</span>
        </div>
    </div>
</div>

<!-- METAS Y CUOTAS DE VENTAS PREMIUM -->
<div class="card-section" style="margin-top:2.5rem; background:linear-gradient(135deg, rgba(18,24,48,0.65), rgba(6,182,212,0.05)); border:1px solid rgba(6,182,212,0.15);">
    <div class="section-header" style="margin-bottom:1rem;">
        <h2 style="display:flex; align-items:center; gap:0.5rem; color:#fff; font-size:1.1rem; margin:0;">
            <i data-lucide="target" style="width:20px; height:20px; color:var(--color-primary); vertical-align:middle; display:inline-block;"></i>
            Meta de Ventas Mensual (Cuota de Cierre B2B)
        </h2>
        <form action="api.php?action=update_sales_quota" method="POST" style="display:flex; gap:0.5rem; align-items:center; margin-left:auto;">
            <label style="font-size:0.8rem; color:var(--text-muted);">Meta ($):</label>
            <input type="number" name="quota_target" value="<?php echo intval($sales_quota_target); ?>" style="width:90px; padding:0.25rem 0.5rem; font-size:0.8rem; background:rgba(0,0,0,0.3); border:1px solid var(--border-color); color:#fff; border-radius:4px;" required>
            <button type="submit" class="btn-action btn-sm" style="padding:0.25rem 0.6rem; font-size:0.75rem;">Ajustar</button>
        </form>
    </div>
    
    <div style="display:grid; grid-template-columns: 1.2fr 2fr 1.2fr; gap:2rem; align-items:center;">
        <div>
            <div style="font-size:0.8rem; color:var(--text-muted); margin-bottom:0.25rem;">Meta de Cierre</div>
            <div style="font-size:1.5rem; font-weight:700; color:#fff;">$<?php echo number_format($sales_quota_target, 2); ?></div>
        </div>
        <div>
            <div style="display:flex; justify-content:space-between; font-size:0.85rem; color:var(--text-muted); margin-bottom:0.5rem;">
                <span>Progreso Actual: <strong>$<?php echo number_format($won_val, 2); ?></strong></span>
                <span style="font-weight:700; color:#06b6d4;"><?php echo $quota_pct; ?>%</span>
            </div>
            <div style="width:100%; height:10px; background:rgba(255,255,255,0.05); border-radius:5px; overflow:hidden; border:1px solid rgba(255,255,255,0.02);">
                <div style="width:<?php echo $quota_pct; ?>%; height:100%; background:linear-gradient(90deg, var(--color-secondary), var(--color-primary)); border-radius:5px; box-shadow: 0 0 10px rgba(6,182,212,0.3);"></div>
            </div>
        </div>
        <div style="text-align:right;">
            <?php if ($quota_pct >= 100): ?>
                <span class="badge" style="background-color:rgba(16,185,129,0.15); color:var(--color-success); border:1px solid rgba(16,185,129,0.3); padding:6px 12px; font-size:0.8rem; font-weight:600; display:inline-block; border-radius:6px;">
                    🏆 ¡Meta Lograda!
                </span>
            <?php else: ?>
                <span style="font-size:0.8rem; color:var(--text-muted);">
                    Faltan <strong>$<?php echo number_format(max(0, $sales_quota_target - $won_val), 2); ?></strong> para el objetivo.
                </span>
            <?php endif; ?>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 2rem; margin-top: 2rem;">
    
    <!-- ACTIVIDADES URGENTES DEL DÍA -->
    <div class="card-section" style="margin-bottom:0;">
        <div class="section-header">
            <h2>Tareas y Seguimientos Urgentes (Hoy)</h2>
            <a href="activities.php" class="btn-sm btn-secondary" style="text-decoration:none; display:flex; align-items:center; gap:0.25rem;">
                <i data-lucide="eye" style="width:12px; height:12px;"></i> Ver Todas
            </a>
        </div>
        
        <div class="custom-table-container">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th style="width:40px;"></th>
                        <th>Tipo</th>
                        <th>Actividad</th>
                        <th>Oportunidad</th>
                        <th>Hora Límite</th>
                    </tr>
                </thead>
                <tbody id="dash-tasks-body">
                    <?php if (count($urgent_list) > 0): ?>
                        <?php foreach ($urgent_list as $act): 
                            $icon = 'phone';
                            if ($act['type'] == 'Email') $icon = 'mail';
                            elseif ($act['type'] == 'Meeting') $icon = 'users';
                            elseif ($act['type'] == 'Task') $icon = 'check-square';
                            
                            $is_overdue = strtotime($act['due_date']) < time();
                            $due_color = $is_overdue ? 'color: var(--color-error); font-weight:600;' : '';
                            ?>
                            <tr id="dash-activity-row-<?php echo $act['id']; ?>">
                                <td style="text-align:center;">
                                    <input type="checkbox" onchange="completeDashActivity(<?php echo $act['id']; ?>)" style="width:16px; height:16px; cursor:pointer; accent-color:var(--color-success);">
                                </td>
                                <td>
                                    <span class="act-icon act-<?php echo $act['type']; ?>" style="margin-right:0;">
                                        <i data-lucide="<?php echo $icon; ?>" style="width:12px; height:12px;"></i>
                                    </span>
                                </td>
                                <td style="color:#fff; font-weight:500; font-size:0.85rem;"><?php echo htmlspecialchars($act['subject']); ?></td>
                                <td style="font-size:0.85rem;"><?php echo htmlspecialchars($act['deal_title'] ?: 'General'); ?></td>
                                <td style="font-size:0.85rem; <?php echo $due_color; ?>">
                                    <?php echo date('h:i a', strtotime($act['due_date'])); ?>
                                    <?php if ($is_overdue): ?><span style="font-size:0.65rem; background:rgba(239,68,68,0.15); padding:1px 3px; border-radius:3px; margin-left:3px;">Atrasada</span><?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 2rem; color:var(--text-muted);">
                                <i data-lucide="check-circle-2" style="width:32px; height:32px; color:var(--color-success); margin-bottom:0.5rem; display:block; margin-left:auto; margin-right:auto;"></i>
                                ¡Todo al día! No tienes actividades urgentes pendientes.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- OPORTUNIDADES RECIENTES -->
    <div class="card-section" style="margin-bottom:0;">
        <div class="section-header">
            <h2>Oportunidades Añadidas Recientemente</h2>
            <a href="pipeline.php" class="btn-sm btn-secondary" style="text-decoration:none; display:flex; align-items:center; gap:0.25rem;">
                <i data-lucide="kanban-square" style="width:12px; height:12px;"></i> Ver Embudo
            </a>
        </div>
        
        <div class="custom-table-container">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Empresa</th>
                        <th>Valor ($)</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($recent_deals) > 0): ?>
                        <?php foreach ($recent_deals as $deal): ?>
                            <tr>
                                <td style="color:#fff; font-weight:500; font-size:0.85rem;"><?php echo htmlspecialchars($deal['title']); ?></td>
                                <td style="font-size:0.85rem;">
                                    <?php echo $deal['account_name'] ? htmlspecialchars($deal['account_name']) : '<span style="color:var(--text-dark);">Sin Empresa</span>'; ?>
                                </td>
                                <td style="font-weight:700; color:var(--color-primary); font-size:0.85rem;">$<?php echo number_format($deal['value'], 2); ?></td>
                                <td>
                                    <a href="pipeline.php" class="btn-sm btn-secondary" style="text-decoration:none; display:inline-block; border-radius:4px; font-size:0.75rem; padding: 2px 6px !important;">Gestionar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 2rem;">No hay oportunidades activas. Registra una nueva arriba a la derecha.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ACCIONES RÁPIDAS COMERCIALES -->
<div class="card-section" style="margin-top:2rem;">
    <div class="section-header" style="margin-bottom:1rem;">
        <h2>Acciones Rápidas del Consultor Comercial</h2>
    </div>
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:1rem;">
        <button class="btn-submit" onclick="openGlobalDealModal()" style="display:flex; align-items:center; justify-content:center; gap:0.5rem;">
            <i data-lucide="plus-circle"></i> Nueva Oportunidad
        </button>
        <a href="accounts.php" class="btn-submit" style="background:rgba(168, 85, 247, 0.2); border:1px solid var(--color-secondary); color:#fff; text-decoration:none; display:flex; align-items:center; justify-content:center; gap:0.5rem; text-align:center;">
            <i data-lucide="building-2"></i> Ir al Directorio B2B
        </a>
    </div>
</div>

<script>
    // AJAX para marcar actividad completada en Dashboard
    function completeDashActivity(activityId) {
        const row = document.getElementById('dash-activity-row-' + activityId);
        row.style.transform = 'translateX(10px)';
        row.style.opacity = '0';
        row.style.transition = '0.3s ease';
        
        setTimeout(() => {
            row.remove();
            // Recargar para actualizar los contadores
            location.reload();
        }, 300);

        fetch('api.php?action=complete_activity', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                activity_id: activityId,
                status: 'Completed'
            })
        });
    }

    function toggleCoachBanner(minimize) {
        const expanded = document.getElementById('coach-banner-expanded');
        const collapsed = document.getElementById('coach-banner-collapsed');
        if (!expanded || !collapsed) return;
        
        if (minimize) {
            expanded.style.display = 'none';
            collapsed.style.display = 'flex';
            localStorage.setItem('coach_banner_minimized', 'true');
        } else {
            expanded.style.display = 'flex';
            collapsed.style.display = 'none';
            localStorage.setItem('coach_banner_minimized', 'false');
        }
    }
    
    // Al cargar la página
    document.addEventListener('DOMContentLoaded', () => {
        const isMinimized = localStorage.getItem('coach_banner_minimized') === 'true';
        toggleCoachBanner(isMinimized);
    });
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>

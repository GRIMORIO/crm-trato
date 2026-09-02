<?php
/**
 * pipeline.php — Vista del Embudo de Ventas Kanban (Pipedrive clone)
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/header.php';

// Obtener todas las pipelines (embudos) registradas
$pipelines = $pdo->query("SELECT * FROM pipelines ORDER BY id")->fetchAll();
$custom_fields_def = $pdo->query("SELECT * FROM custom_field_definitions ORDER BY id")->fetchAll();

// Determinar pipeline activa
$active_pipeline_id = isset($_GET['pipeline_id']) ? intval($_GET['pipeline_id']) : ($pipelines[0]['id'] ?? 1);

// Obtener todas las etapas del embudo activo
$stages_stmt = $pdo->prepare("SELECT * FROM stages WHERE pipeline_id = ? ORDER BY position");
$stages_stmt->execute([$active_pipeline_id]);
$stages = $stages_stmt->fetchAll();
$last_stage_id = !empty($stages) ? (int) $stages[count($stages) - 1]['id'] : 0;

$accounts_opt = $pdo->query("SELECT id, name FROM accounts ORDER BY name")->fetchAll();
$contacts_opt = $pdo->query("SELECT id, first_name, last_name FROM contacts ORDER BY first_name")->fetchAll();

// Obtener framework de calificación activo
$qualification_framework = $pdo->query("SELECT setting_value FROM crm_settings WHERE setting_key = 'qualification_framework'")->fetchColumn() ?: 'BANT';

// Obtener ponderaciones según corresponda
if ($qualification_framework === 'BANT') {
    $scoring_budget = intval($pdo->query("SELECT setting_value FROM crm_settings WHERE setting_key = 'scoring_budget'")->fetchColumn() ?: 20);
    $scoring_authority = intval($pdo->query("SELECT setting_value FROM crm_settings WHERE setting_key = 'scoring_authority'")->fetchColumn() ?: 20);
    $scoring_need = intval($pdo->query("SELECT setting_value FROM crm_settings WHERE setting_key = 'scoring_need'")->fetchColumn() ?: 20);
    $scoring_timeline = intval($pdo->query("SELECT setting_value FROM crm_settings WHERE setting_key = 'scoring_timeline'")->fetchColumn() ?: 20);
} else {
    $scoring_meddic_metrics = intval($pdo->query("SELECT setting_value FROM crm_settings WHERE setting_key = 'scoring_meddic_metrics'")->fetchColumn() ?: 15);
    $scoring_meddic_buyer = intval($pdo->query("SELECT setting_value FROM crm_settings WHERE setting_key = 'scoring_meddic_buyer'")->fetchColumn() ?: 20);
    $scoring_meddic_criteria = intval($pdo->query("SELECT setting_value FROM crm_settings WHERE setting_key = 'scoring_meddic_criteria'")->fetchColumn() ?: 15);
    $scoring_meddic_process = intval($pdo->query("SELECT setting_value FROM crm_settings WHERE setting_key = 'scoring_meddic_process'")->fetchColumn() ?: 15);
    $scoring_meddic_pain = intval($pdo->query("SELECT setting_value FROM crm_settings WHERE setting_key = 'scoring_meddic_pain'")->fetchColumn() ?: 20);
    $scoring_meddic_champion = intval($pdo->query("SELECT setting_value FROM crm_settings WHERE setting_key = 'scoring_meddic_champion'")->fetchColumn() ?: 15);
}

// Obtener todos los deals abiertos que pertenecen al embudo activo
$restriction = get_visibility_restriction();

if ($restriction !== '') {
    $deals_stmt = $pdo->prepare("
        SELECT d.*, a.name AS account_name, a.city AS account_city, i.scoring_points AS industry_points
        FROM deals d
        JOIN stages s ON d.stage_id = s.id
        LEFT JOIN accounts a ON d.account_id = a.id
        LEFT JOIN industries i ON a.industry = i.name
        WHERE d.status = 'Open' AND s.pipeline_id = ? AND d.assigned_agent = ?
        ORDER BY d.created_at DESC
    ");
    $deals_stmt->execute([$active_pipeline_id, $restriction]);
} else {
    $deals_stmt = $pdo->prepare("
        SELECT d.*, a.name AS account_name, a.city AS account_city, i.scoring_points AS industry_points
        FROM deals d
        JOIN stages s ON d.stage_id = s.id
        LEFT JOIN accounts a ON d.account_id = a.id
        LEFT JOIN industries i ON a.industry = i.name
        WHERE d.status = 'Open' AND s.pipeline_id = ?
        ORDER BY d.created_at DESC
    ");
    $deals_stmt->execute([$active_pipeline_id]);
}
$deals = $deals_stmt->fetchAll();

// Agrupar deals por stage_id
$deals_by_stage = [];
foreach ($stages as $stage) {
    $deals_by_stage[$stage['id']] = [];
}
foreach ($deals as $deal) {
    $deals_by_stage[$deal['stage_id']][] = $deal;
}
?>

<div class="pipeline-container">
    <?php foreach ($stages as $stage): 
        $stage_id = $stage['id'];
        $stage_deals = $deals_by_stage[$stage_id];
        
        // Calcular total monetario de esta columna
        $col_total = 0;
        foreach ($stage_deals as $sd) {
            $col_total += $sd['value'];
        }
        ?>
        <div class="pipeline-column" 
             id="stage-col-<?php echo $stage_id; ?>" 
             data-stage-id="<?php echo $stage_id; ?>"
             ondragover="allowDrop(event)" 
             ondrop="drop(event, <?php echo $stage_id; ?>)">
            
            <div class="column-header">
                <h3 style="flex-grow: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; margin-right: 0.5rem;" title="<?php echo htmlspecialchars($stage['name']); ?>">
                    <?php echo htmlspecialchars($stage['name']); ?>
                </h3>
                <div style="display: flex; align-items: center; gap: 0.35rem; flex-shrink: 0;">
                    <button class="btn-move-stage" onclick="moveStage(event, <?php echo $stage_id; ?>, 'left')" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=0.4" style="background: none; border: none; padding: 2px; cursor: pointer; color: var(--text-muted); opacity: 0.4; transition: opacity 0.2s;" title="Mover etapa a la izquierda">
                        <i data-lucide="chevron-left" style="width: 14px; height: 14px;"></i>
                    </button>
                    <button class="btn-move-stage" onclick="moveStage(event, <?php echo $stage_id; ?>, 'right')" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=0.4" style="background: none; border: none; padding: 2px; cursor: pointer; color: var(--text-muted); opacity: 0.4; transition: opacity 0.2s;" title="Mover etapa a la derecha">
                        <i data-lucide="chevron-right" style="width: 14px; height: 14px;"></i>
                    </button>
                    <button class="btn-delete-stage" onclick="deleteStage(event, <?php echo $stage_id; ?>)" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=0.4" style="background: none; border: none; padding: 2px; cursor: pointer; color: var(--color-error); opacity: 0.4; transition: opacity 0.2s;" title="Eliminar Etapa">
                        <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
                    </button>
                    <span class="column-count" id="count-stage-<?php echo $stage_id; ?>">
                        <?php echo count($stage_deals); ?>
                    </span>
                </div>
            </div>
            
            <div class="column-value" id="val-stage-<?php echo $stage_id; ?>" data-value="<?php echo $col_total; ?>" style="margin-bottom: 0.15rem;">
                $<?php echo number_format($col_total, 2); ?>
            </div>
            <div style="font-size: 0.72rem; color: var(--text-dark); margin-bottom: 0.75rem; padding-right: 1rem; text-align: right; font-weight: 500;" id="weighted-val-stage-<?php echo $stage_id; ?>" data-probability="<?php echo $stage['win_probability']; ?>">
                Ponderado: <span style="color:#fff; font-weight:600;">$<?php echo number_format($col_total * ($stage['win_probability'] / 100), 2); ?></span> (<?php echo $stage['win_probability']; ?>%)
            </div>
            
            <div class="column-body">
                <?php foreach ($stage_deals as $deal): 
                    // 1. Lead Scoring Dinámico
                    $bant_score = ($deal['bant_budget'] ? $scoring_budget : 0) + 
                                   ($deal['bant_authority'] ? $scoring_authority : 0) + 
                                   ($deal['bant_need'] ? $scoring_need : 0) + 
                                   ($deal['bant_timeline'] ? $scoring_timeline : 0);
                    $ind_points = intval($deal['industry_points'] ?? 10);
                    $industry_score = min(20, round(($ind_points / 50) * 20));
                    $total_score = min(100, $bant_score + $industry_score);

                    // 2. Alerta de Leads Fríos (Rotten Deals)
                    $is_rotten = false;
                    $last_updated = strtotime($deal['updated_at']);
                    $days_inactive = floor((time() - $last_updated) / 86400);
                    if ($days_inactive >= 7) {
                        $is_rotten = true;
                    }
                    ?>
                    <div class="deal-card" 
                         id="deal-card-<?php echo $deal['id']; ?>" 
                         data-deal-id="<?php echo $deal['id']; ?>"
                         data-deal-value="<?php echo $deal['value']; ?>"
                         draggable="true" 
                         ondragstart="drag(event, <?php echo $deal['id']; ?>)"
                         onclick="openDealDetailModal(<?php echo $deal['id']; ?>)"
                         style="cursor: pointer; <?php echo $is_rotten ? 'border-color: rgba(239,68,68,0.5); box-shadow: 0 0 10px rgba(239,68,68,0.15);' : ''; ?>">
                        
                        <div class="flex-between">
                            <h4><?php echo htmlspecialchars($deal['title']); ?></h4>
                            <span class="deal-badge">$<?php echo number_format($deal['value'], 0); ?></span>
                        </div>
                        
                        <div class="deal-card-meta">
                            <span class="deal-company">
                                <i data-lucide="building" style="width:12px; height:12px; display:inline-block; vertical-align:middle; margin-right:2px;"></i>
                                <?php echo $deal['account_name'] ? htmlspecialchars($deal['account_name']) : 'Sin Empresa'; ?>
                            </span>
                            <span style="font-size:0.7rem; color:var(--text-dark);">
                                <?php echo date('d M', strtotime($deal['close_date'])); ?>
                            </span>
                        </div>

                        <!-- Indicadores de Calificación SOP -->
                        <div class="deal-bant-indicators" style="margin-top: 0.5rem; display: flex; gap: 0.25rem;">
                            <?php if ($qualification_framework === 'BANT'): ?>
                                <span class="bant-letter <?php echo $deal['bant_budget'] ? 'active' : ''; ?>" 
                                      onclick="toggleBant(event, <?php echo $deal['id']; ?>, 'budget')" 
                                      title="B — Presupuesto (Budget)">B</span>
                                <span class="bant-letter <?php echo $deal['bant_authority'] ? 'active' : ''; ?>" 
                                      onclick="toggleBant(event, <?php echo $deal['id']; ?>, 'authority')" 
                                      title="A — Autoridad (Authority)">A</span>
                                <span class="bant-letter <?php echo $deal['bant_need'] ? 'active' : ''; ?>" 
                                      onclick="toggleBant(event, <?php echo $deal['id']; ?>, 'need')" 
                                      title="N — Necesidad (Need)">N</span>
                                <span class="bant-letter <?php echo $deal['bant_timeline'] ? 'active' : ''; ?>" 
                                      onclick="toggleBant(event, <?php echo $deal['id']; ?>, 'timeline')" 
                                      title="T — Plazo (Timeline)">T</span>
                            <?php else: ?>
                                <span class="bant-letter <?php echo $deal['meddic_metrics'] ? 'active' : ''; ?>" 
                                      onclick="toggleMeddic(event, <?php echo $deal['id']; ?>, 'metrics')" 
                                      title="M — Métricas (Metrics)">M</span>
                                <span class="bant-letter <?php echo $deal['meddic_buyer'] ? 'active' : ''; ?>" 
                                      onclick="toggleMeddic(event, <?php echo $deal['id']; ?>, 'buyer')" 
                                      title="E — Comprador Económico (Economic Buyer)">E</span>
                                <span class="bant-letter <?php echo $deal['meddic_criteria'] ? 'active' : ''; ?>" 
                                      onclick="toggleMeddic(event, <?php echo $deal['id']; ?>, 'criteria')" 
                                      title="D — Criterio de Decisión (Decision Criteria)">D</span>
                                <span class="bant-letter <?php echo $deal['meddic_process'] ? 'active' : ''; ?>" 
                                      onclick="toggleMeddic(event, <?php echo $deal['id']; ?>, 'process')" 
                                      title="D — Proceso de Decisión (Decision Process)">D</span>
                                <span class="bant-letter <?php echo $deal['meddic_pain'] ? 'active' : ''; ?>" 
                                      onclick="toggleMeddic(event, <?php echo $deal['id']; ?>, 'pain')" 
                                      title="I — Identificar Dolor (Identify Pain)">I</span>
                                <span class="bant-letter <?php echo $deal['meddic_champion'] ? 'active' : ''; ?>" 
                                      onclick="toggleMeddic(event, <?php echo $deal['id']; ?>, 'champion')" 
                                      title="C — Campeón (Champion)">C</span>
                            <?php endif; ?>
                        </div>

                        <!-- Lead Scoring Dinámico -->
                        <div style="margin-top:0.5rem;" title="Score de Calificación: <?php echo $total_score; ?>%">
                            <div style="display:flex; justify-content:space-between; font-size:0.7rem; color:var(--text-muted); margin-bottom:0.15rem;">
                                <span>Calificación</span>
                                <span style="font-weight:600; color:#06b6d4; margin-left:auto;"><?php echo $total_score; ?>%</span>
                            </div>
                            <div style="width:100%; height:4px; background:rgba(255,255,255,0.05); border-radius:2px; overflow:hidden;">
                                <div style="width:<?php echo $total_score; ?>%; height:100%; background:linear-gradient(90deg, #a855f7, #06b6d4); border-radius:2px;"></div>
                            </div>
                        </div>

                        <!-- Asesor Asignado -->
                        <div style="display:flex; align-items:center; margin-top:0.5rem; font-size:0.7rem; color:var(--text-muted);">
                            <div style="background:rgba(6,182,212,0.15); color:var(--color-primary); width:18px; height:18px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:600; font-size:0.6rem; margin-right:4px;" title="Asesor: <?php echo htmlspecialchars($deal['assigned_agent']); ?>">
                                <?php 
                                    $name_parts = explode(' ', $deal['assigned_agent']);
                                    $initials = (isset($name_parts[0]) ? substr($name_parts[0], 0, 1) : '') . (isset($name_parts[1]) ? substr($name_parts[1], 0, 1) : '');
                                    echo htmlspecialchars($initials);
                                ?>
                            </div>
                            <span style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:140px;"><?php echo htmlspecialchars($deal['assigned_agent']); ?></span>
                        </div>

                        <?php if ($is_rotten): ?>
                            <div style="background:rgba(239,68,68,0.15); border:1px solid rgba(239,68,68,0.3); color:#fca5a5; font-size:0.65rem; font-weight:600; padding:2px 6px; border-radius:4px; margin-top:0.5rem; display:inline-block;">
                                ⚠️ Inactivo por <?php echo $days_inactive; ?> días
                            </div>
                        <?php endif; ?>

                        <!-- Acciones Rápidas (Ganar / Perder) -->
                        <div style="display: flex; gap: 0.5rem; margin-top: 0.75rem; border-top: 1px dashed rgba(255,255,255,0.03); padding-top: 0.5rem; justify-content: flex-end;">
                            <button class="btn-sm btn-secondary" onclick="markDeal(event, <?php echo $deal['id']; ?>, 'Won', <?php echo $stage_id; ?>)" title="Marcar como Ganada" style="border-radius: 4px; padding: 2px 6px !important;">
                                <i data-lucide="check" style="width:12px; height:12px; color: var(--color-success);"></i>
                            </button>
                            <button class="btn-sm btn-secondary" onclick="markDeal(event, <?php echo $deal['id']; ?>, 'Lost', <?php echo $stage_id; ?>)" title="Marcar como Perdida" style="border-radius: 4px; padding: 2px 6px !important;">
                                <i data-lucide="x" style="width:12px; height:12px; color: var(--color-error);"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Modal: Detalle de Oportunidad (BANT & Interacciones) -->
<div class="modal-overlay" id="deal-detail-modal">
    <div class="modal-card" style="max-width: 750px;">
        <div class="modal-title">
            <span id="detail-deal-title">Detalle del Trato</span>
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-right: 1.5rem; margin-left: auto;">
                <button class="btn-secondary btn-sm" id="btn-toggle-edit" onclick="toggleEditMode()" style="padding: 4px 8px !important; display: flex; align-items: center; gap: 4px; border-radius: 4px; font-weight: 500; cursor: pointer;">
                    <i data-lucide="edit-3" style="width: 12px; height: 12px;"></i>
                    <span id="btn-toggle-edit-text">Editar</span>
                </button>
            </div>
            <button class="modal-close" onclick="closeDealDetailModal()">&times;</button>
        </div>
        
        <!-- Vista de Solo Lectura (View Mode) -->
        <div id="deal-view-mode" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-top: 1rem;">
            <!-- Panel Izquierdo: Información y Acciones -->
            <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); padding: 1.25rem; border-radius: 10px; display: flex; flex-direction: column; gap: 1rem;">
                <h3 style="color:#fff; font-size:1rem; border-bottom:1px dashed var(--border-color); padding-bottom:0.25rem; font-weight:600;">Información General</h3>
                <div>
                    <p style="font-size:0.8rem; color:var(--text-dark);">Valor de la Oportunidad:</p>
                    <p id="detail-deal-value" style="font-size:1.4rem; font-weight:700; color:var(--color-primary); margin-top:0.25rem;">$0.00</p>
                </div>
                <div>
                    <p style="font-size:0.8rem; color:var(--text-dark);">Etapa Actual:</p>
                    <p id="detail-deal-stage" style="font-size:0.9rem; font-weight:600; color:#fff; margin-top:0.25rem;">Contacto Inicial</p>
                </div>
                <div>
                    <p style="font-size:0.8rem; color:var(--text-dark);">Empresa Cliente:</p>
                    <p id="detail-deal-account" style="font-size:0.9rem; font-weight:600; color:#fff; margin-top:0.25rem;">-</p>
                </div>
                <div>
                    <p style="font-size:0.8rem; color:var(--text-dark);">Contacto Principal:</p>
                    <p id="detail-deal-contact" style="font-size:0.9rem; font-weight:600; color:#fff; margin-top:0.25rem;">-</p>
                    <p id="detail-deal-job" style="font-size:0.8rem; color:var(--text-muted); margin-top:0.1rem;">-</p>
                </div>
                
                <!-- Campos Personalizados en Modo Vista -->
                <div id="detail-custom-fields-view" style="display:flex; flex-direction:column; gap:0.5rem; margin-top: 0.5rem; border-top: 1px dashed var(--border-color); padding-top: 0.75rem;">
                    <h3 style="color:#fff; font-size:1rem; font-weight:600; margin-bottom: 0.25rem;">Campos Personalizados</h3>
                    <div id="custom-fields-view-list" style="display:flex; flex-direction:column; gap:0.5rem; font-size:0.85rem; color:#cbd5e1;">
                        <!-- Se rellena vía AJAX -->
                    </div>
                </div>
                
                <h3 style="color:#fff; font-size:1rem; border-bottom:1px dashed var(--border-color); padding-bottom:0.25rem; font-weight:600; margin-top:0.5rem;">Canales de Interacción</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                    <a id="action-wa" href="#" target="_blank" class="btn-submit" style="text-align:center; text-decoration:none; background: linear-gradient(135deg, var(--color-success), #10b981); display:flex; align-items:center; justify-content:center; gap:0.25rem; font-size:0.85rem; padding:0.6rem;">
                        <i data-lucide="message-square" style="width:14px; height:14px;"></i> WhatsApp B2B
                    </a>
                    <a id="action-email" href="email_inbox.php" class="btn-submit" style="text-align:center; text-decoration:none; background: linear-gradient(135deg, var(--color-primary), var(--color-secondary)); display:flex; align-items:center; justify-content:center; gap:0.25rem; font-size:0.85rem; padding:0.6rem;">
                        <i data-lucide="mail" style="width:14px; height:14px;"></i> Enviar Correo
                    </a>
                </div>
            </div>
            
            <!-- Panel Derecho: Bitácora / Notas -->
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <h3 style="color:#fff; font-size:1rem; border-bottom:1px dashed var(--border-color); padding-bottom:0.25rem; font-weight:600;">Historial de Notas Comerciales</h3>
                
                <!-- Agregar nota form -->
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <textarea id="new-note-content" rows="2" placeholder="Escribe un comentario o actualización de la negociación..." style="font-size:0.85rem; padding:0.5rem;"></textarea>
                    <button class="btn-submit" onclick="submitDealNote()" style="padding:0.5rem; font-size:0.85rem; width:fit-content; align-self:flex-end;">Guardar Comentario</button>
                </div>
                
                <!-- Notas list -->
                <div id="detail-notes-list" style="flex-grow: 1; max-height: 250px; overflow-y: auto; display: flex; flex-direction: column; gap: 0.5rem; padding-right: 0.5rem;">
                    <!-- Se rellena vía AJAX -->
                </div>
            </div>
        </div>

        <!-- Formulario de Edición (Edit Mode) -->
        <form id="deal-edit-mode" style="display: none; flex-direction: column; gap: 1rem; margin-top: 1rem;" onsubmit="saveDealChanges(event)">
            <input type="hidden" id="edit-deal-id" name="deal_id">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label for="edit-deal-title-input">Título de la Oportunidad *</label>
                    <input type="text" id="edit-deal-title-input" name="title" required>
                </div>
                <div class="form-group">
                    <label for="edit-deal-value-input">Monto Estimado ($) *</label>
                    <input type="number" id="edit-deal-value-input" name="value" step="0.01" min="0" required>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label for="edit-deal-stage-input">Etapa del Embudo *</label>
                    <select id="edit-deal-stage-input" name="stage_id" required>
                        <?php foreach ($stages as $stg): ?>
                            <option value="<?php echo $stg['id']; ?>"><?php echo htmlspecialchars($stg['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit-deal-close-date-input">Fecha Estimada de Cierre</label>
                    <input type="date" id="edit-deal-close-date-input" name="close_date">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label for="edit-deal-account-input">Cuenta Comercial (Empresa B2B)</label>
                    <select id="edit-deal-account-input" name="account_id">
                        <option value="">-- Sin Empresa --</option>
                        <?php foreach ($accounts_opt as $acc): ?>
                            <option value="<?php echo $acc['id']; ?>"><?php echo htmlspecialchars($acc['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit-deal-contact-input">Persona de Contacto</label>
                    <select id="edit-deal-contact-input" name="contact_id">
                        <option value="">-- Sin Contacto --</option>
                        <?php foreach ($contacts_opt as $con): ?>
                            <option value="<?php echo $con['id']; ?>"><?php echo htmlspecialchars($con['first_name'] . ' ' . $con['last_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="edit-deal-agent-input">Asesor Asignado</label>
                <select id="edit-deal-agent-input" name="assigned_agent">
                    <option value="Andrés Herrera (GAM Norte)">Andrés Herrera (GAM Norte)</option>
                    <option value="Carlos Mendoza (Zona Costa)">Carlos Mendoza (Zona Costa)</option>
                    <option value="Sofía Castro (GAM Oriente)">Sofía Castro (GAM Oriente)</option>
                </select>
            </div>

            <!-- Campos Personalizados en Modo Edición -->
            <div id="detail-custom-fields-edit" style="display: flex; flex-direction: column; gap: 1rem; border-top: 1px dashed var(--border-color); padding-top: 1rem;">
                <h3 style="color:#fff; font-size:1rem; font-weight:600; margin:0;">Campos Personalizados</h3>
                <div id="custom-fields-edit-list" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <!-- Se rellena vía AJAX dinámicamente -->
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:0.5rem; margin-top:0.5rem; border-top:1px dashed var(--border-color); padding-top:1rem;">
                <button type="button" class="btn-secondary btn-submit" onclick="toggleEditMode()" style="width:auto; padding:0.6rem 1.2rem; cursor:pointer;">Cancelar</button>
                <button type="submit" class="btn-submit" style="width:auto; padding:0.6rem 1.2rem; cursor:pointer;">Guardar Cambios</button>
            </div>
        </form>
        
        <!-- SALES COACH DE BRIAN TRACY CONTEXTUAL -->
        <div id="modal-sales-coach-container" style="margin-top: 1.5rem; background: linear-gradient(135deg, rgba(6, 182, 212, 0.08), rgba(168, 85, 247, 0.08)); border: 1px solid rgba(6, 182, 212, 0.2); border-radius: 8px; padding: 1rem; display: flex; align-items: center; gap: 1rem;">
            <div style="background: linear-gradient(135deg, #06b6d4, #a855f7); width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 800; font-size: 0.75rem; flex-shrink:0;">
                BT
            </div>
            <div>
                <div style="font-size: 0.7rem; text-transform: uppercase; color: var(--color-primary); font-weight: 700; margin-bottom: 0.15rem; display:flex; align-items:center; gap:0.25rem;">
                    <i data-lucide="lightbulb" style="width:12px; height:12px;"></i>
                    Sales Coach de Brian Tracy
                </div>
                <p id="modal-sales-coach-text" style="font-size: 0.85rem; color: #cbd5e1; font-style: italic; margin: 0; line-height: 1.35;">
                    "Cargando consejo comercial..."
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Nueva Etapa del Embudo -->
<div class="modal-overlay" id="stage-modal">
    <div class="modal-card">
        <div class="modal-title">
            <span>Agregar Nueva Etapa al Embudo</span>
            <button class="modal-close" onclick="closeStageModal()">&times;</button>
        </div>
        <form action="api.php?action=create_stage" method="POST">
            <input type="hidden" name="pipeline_id" value="<?php echo $active_pipeline_id; ?>">
            <div class="form-group">
                <label for="stage_name">Nombre de la Etapa *</label>
                <input type="text" id="stage_name" name="name" placeholder="Ej. Presentación de Muestras..." required>
            </div>
            <div class="form-group">
                <label for="after_stage_id">Posición en el embudo</label>
                <select id="after_stage_id" name="after_stage_id">
                    <option value="0">Al inicio del embudo</option>
                    <?php foreach ($stages as $s): ?>
                        <option value="<?php echo (int) $s['id']; ?>" <?php echo ((int) $s['id'] === $last_stage_id) ? 'selected' : ''; ?>>
                            Después de: <?php echo htmlspecialchars($s['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn-submit">Crear Etapa</button>
        </form>
    </div>
</div>

<!-- Modal: Nuevo Embudo de Ventas -->
<div class="modal-overlay" id="pipeline-modal">
    <div class="modal-card">
        <div class="modal-title">
            <span>Crear Nuevo Embudo de Ventas</span>
            <button class="modal-close" onclick="closePipelineModal()">&times;</button>
        </div>
        <form action="api.php?action=create_pipeline" method="POST">
            <div class="form-group">
                <label for="pipeline_name">Nombre del Embudo *</label>
                <input type="text" id="pipeline_name" name="name" placeholder="Ej. Ventas Corporativas, Posventa..." required>
            </div>
            <button type="submit" class="btn-submit">Crear Embudo</button>
        </form>
    </div>
</div>

<!-- Modal: Administrar Campos Personalizados -->
<div class="modal-overlay" id="custom-fields-modal">
    <div class="modal-card" style="max-width: 600px;">
        <div class="modal-title">
            <span>Administrar Campos Personalizados</span>
            <button class="modal-close" onclick="closeCustomFieldsModal()">&times;</button>
        </div>
        
        <!-- Listado de Campos Existentes -->
        <div style="margin-bottom: 2rem;">
            <h4 style="color:#fff; border-bottom: 1px dashed var(--border-color); padding-bottom: 0.5rem; margin-bottom: 1rem;">Campos Activos</h4>
            <div style="max-height: 200px; overflow-y: auto; display: flex; flex-direction: column; gap: 0.5rem;">
                <?php if (empty($custom_fields_def)): ?>
                    <p style="font-size: 0.85rem; color: var(--text-dark); text-align: center;">No hay campos personalizados configurados.</p>
                <?php else: ?>
                    <?php foreach ($custom_fields_def as $cf): ?>
                        <div class="flex-between" style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); padding: 0.75rem 1rem; border-radius: 6px;">
                            <div>
                                <strong style="color: #fff; font-size: 0.9rem;"><?php echo htmlspecialchars($cf['name']); ?></strong>
                                <span style="display: block; font-size: 0.75rem; color: var(--text-dark); text-transform: uppercase; margin-top: 0.15rem;">
                                    Tipo: <?php echo htmlspecialchars($cf['field_type']); ?> 
                                    <?php if ($cf['options']): ?>
                                        (<?php echo htmlspecialchars($cf['options']); ?>)
                                    <?php endif; ?>
                                </span>
                            </div>
                            <button onclick="deleteCustomField(<?php echo $cf['id']; ?>)" style="background: none; border: none; color: var(--color-error); cursor: pointer; padding: 4px;" title="Eliminar Campo">
                                <i data-lucide="trash-2" style="width: 16px; height: 16px;"></i>
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Formulario para Nuevo Campo -->
        <form action="api.php?action=create_custom_field" method="POST">
            <h4 style="color:#fff; border-bottom: 1px dashed var(--border-color); padding-bottom: 0.5rem; margin-bottom: 1rem;">Crear Nuevo Campo</h4>
            <div class="form-group">
                <label for="cf_name">Nombre del Campo *</label>
                <input type="text" id="cf_name" name="name" placeholder="Ej. Origen del Lead, Canal de Venta..." required>
            </div>
            
            <div class="form-group">
                <label for="cf_type">Tipo de Campo *</label>
                <select id="cf_type" name="field_type" onchange="toggleOptionsInput(this.value)" required>
                    <option value="text">Texto Corto (Línea simple)</option>
                    <option value="number">Número</option>
                    <option value="date">Fecha</option>
                    <option value="select">Lista de Selección (Desplegable)</option>
                </select>
            </div>
            
            <div class="form-group" id="cf_options_group" style="display: none;">
                <label for="cf_options">Opciones de la Lista *</label>
                <input type="text" id="cf_options" name="options" placeholder="Opción 1, Opción 2, Opción 3 (Separadas por comas)">
                <small style="color: var(--text-dark); display: block; margin-top: 0.25rem; font-size: 0.75rem;">Ingrese las opciones separadas por comas.</small>
            </div>
            
            <button type="submit" class="btn-submit" style="margin-top: 1rem;">Agregar Campo Personalizado</button>
        </form>
    </div>
</div>

<script>
    // HTML5 Drag & Drop API
    function allowDrop(ev) {
        ev.preventDefault();
        const col = ev.currentTarget;
        col.classList.add('drag-over');
    }

    // Remover estilo al salir del drag
    document.querySelectorAll('.pipeline-column').forEach(col => {
        col.addEventListener('dragleave', (e) => {
            col.classList.remove('drag-over');
        });
    });

    function drag(ev, dealId) {
        ev.dataTransfer.setData("text/plain", dealId);
        // Guardar la columna de origen para re-calcular estadísticas
        const origColId = document.getElementById('deal-card-' + dealId).parentElement.parentElement.dataset.stageId;
        ev.dataTransfer.setData("source-stage-id", origColId);
    }

    function drop(ev, targetStageId) {
        ev.preventDefault();
        const col = ev.currentTarget;
        col.classList.remove('drag-over');
        
        const dealId = ev.dataTransfer.getData("text/plain");
        const sourceStageId = ev.dataTransfer.getData("source-stage-id");
        
        const card = document.getElementById('deal-card-' + dealId);
        const targetBody = col.querySelector('.column-body');
        
        // Evitar cambios redundantes
        if (sourceStageId == targetStageId) return;

        // Añadir tarjeta visualmente
        targetBody.appendChild(card);
        
        // Re-calcular estadísticas locales de columnas
        recalculateColStats(sourceStageId);
        recalculateColStats(targetStageId);

        // Notificar al backend vía Fetch
        fetch('api.php?action=update_deal_stage', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                deal_id: dealId,
                stage_id: targetStageId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                alert("Error al actualizar la etapa: " + data.error);
                location.reload(); // Recargar si falla
            }
        })
        .catch(err => {
            console.error(err);
            alert("Error en la conexión con el servidor.");
            location.reload();
        });
    }

    // Re-calcular montos y cantidades de deals en columnas locales
    function recalculateColStats(stageId) {
        const col = document.getElementById('stage-col-' + stageId);
        if (!col) return;
        
        const cards = col.querySelectorAll('.deal-card');
        const countBadge = document.getElementById('count-stage-' + stageId);
        const valueDisplay = document.getElementById('val-stage-' + stageId);
        const weightedDisplay = document.getElementById('weighted-val-stage-' + stageId);
        
        let count = cards.length;
        let total = 0;
        
        cards.forEach(card => {
            total += parseFloat(card.dataset.dealValue || 0);
        });
        
        countBadge.innerText = count;
        valueDisplay.innerText = '$' + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        valueDisplay.dataset.value = total;

        if (weightedDisplay) {
            const prob = parseInt(weightedDisplay.dataset.probability || 50);
            const weightedTotal = total * (prob / 100);
            weightedDisplay.innerHTML = `Ponderado: <span style="color:#fff; font-weight:600;">$` + weightedTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + `</span> (${prob}%)`;
        }
    }

    // Cambiar estado de la oportunidad a Ganado o Perdido en vivo
    function markDeal(ev, dealId, status, stageId) {
        ev.stopPropagation(); // Evitar abrir modal de detalle
        const card = document.getElementById('deal-card-' + dealId);
        
        // Agregar efecto visual
        if (status === 'Won') {
            card.style.border = '2px solid var(--color-success)';
            card.style.boxShadow = '0 0 15px rgba(16, 185, 129, 0.25)';
        } else {
            card.style.border = '2px solid var(--color-error)';
            card.style.boxShadow = '0 0 15px rgba(239, 68, 68, 0.25)';
        }
        
        card.style.transform = 'scale(0.9)';
        card.style.opacity = '0';
        
        setTimeout(() => {
            card.remove();
            recalculateColStats(stageId);
        }, 300);

        fetch('api.php?action=change_deal_status', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                deal_id: dealId,
                status: status
            })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                alert("Error al actualizar la oportunidad: " + data.error);
                location.reload();
            }
        })
        .catch(err => {
            console.error(err);
            location.reload();
        });
    }

    // Alternar calificación BANT vía AJAX
    function toggleBant(ev, dealId, criteria) {
        ev.stopPropagation(); // Evitar abrir modal de detalle
        const el = ev.currentTarget;
        
        fetch('api.php?action=toggle_deal_bant', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                deal_id: dealId,
                criteria: criteria
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (data.new_state == 1) {
                    el.classList.add('active');
                } else {
                    el.classList.remove('active');
                }
                setTimeout(() => location.reload(), 150);
            } else {
                alert("Error al calificar BANT: " + data.error);
            }
        })
        .catch(err => {
            console.error(err);
            alert("Error en la conexión al calificar BANT.");
        });
    }

    // Alternar calificación MEDDIC vía AJAX
    function toggleMeddic(ev, dealId, criteria) {
        ev.stopPropagation(); // Evitar abrir modal de detalle
        const el = ev.currentTarget;
        
        fetch('api.php?action=toggle_deal_meddic', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                deal_id: dealId,
                criteria: criteria
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (data.new_state == 1) {
                    el.classList.add('active');
                } else {
                    el.classList.remove('active');
                }
                setTimeout(() => location.reload(), 150);
            } else {
                alert("Error al calificar MEDDIC: " + data.error);
            }
        })
        .catch(err => {
            console.error(err);
            alert("Error en la conexión al calificar MEDDIC.");
        });
    }

    // Modal de Nueva Etapa
    const stageModal = document.getElementById('stage-modal');

    function openStageModal() {
        stageModal.style.display = 'flex';
        setTimeout(() => stageModal.classList.add('active'), 10);
    }

    function closeStageModal() {
        stageModal.classList.remove('active');
        setTimeout(() => stageModal.style.display = 'none', 250);
    }

    // Modal de Nuevo Embudo
    const pipelineModal = document.getElementById('pipeline-modal');

    function openPipelineModal() {
        pipelineModal.style.display = 'flex';
        setTimeout(() => pipelineModal.classList.add('active'), 10);
    }

    function closePipelineModal() {
        pipelineModal.classList.remove('active');
        setTimeout(() => pipelineModal.style.display = 'none', 250);
    }

    // Cambiar de Embudo
    function switchPipeline(pipelineId) {
        window.location.href = 'pipeline.php?pipeline_id=' + pipelineId;
    }

    // Suprimir Embudo (con todas sus etapas)
    function deletePipeline(pipelineId) {
        if (!confirm("¿Eliminar este embudo por completo? Se borrarán también todas sus etapas. Esta acción no se puede deshacer.")) return;
        fetch('api.php?action=delete_pipeline', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ pipeline_id: pipelineId })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.location.href = data.redirect || 'pipeline.php';
            } else {
                alert("Error al eliminar el embudo: " + data.error);
            }
        })
        .catch(err => {
            console.error(err);
            alert("Error en la conexión con el servidor.");
        });
    }

    // Eliminar Etapa
    function moveStage(event, stageId, direction) {
        event.stopPropagation();
        fetch('api.php?action=move_stage', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ stage_id: stageId, direction: direction })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (!data.noop) location.reload();
            } else {
                alert("Error al reordenar la etapa: " + data.error);
            }
        })
        .catch(err => {
            console.error(err);
            alert("Error en la conexión con el servidor.");
        });
    }

    function deleteStage(event, stageId) {
        event.stopPropagation(); // Evitar que el click se propague
        if (!confirm("¿Estás seguro de que deseas eliminar esta etapa del embudo?")) return;
        
        fetch('api.php?action=delete_stage', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ stage_id: stageId })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert("Error al eliminar la etapa: " + data.error);
            }
        })
        .catch(err => {
            console.error(err);
            alert("Error en la conexión con el servidor.");
        });
    }

    // Modal de Detalle de Deal
    const dealDetailModal = document.getElementById('deal-detail-modal');
    let currentActiveDealId = null;
    let isEditMode = false;

    function toggleEditMode() {
        isEditMode = !isEditMode;
        const viewDiv = document.getElementById('deal-view-mode');
        const editForm = document.getElementById('deal-edit-mode');
        const coachDiv = document.getElementById('modal-sales-coach-container');
        const btnText = document.getElementById('btn-toggle-edit-text');
        const btnIcon = document.querySelector('#btn-toggle-edit i');
        
        if (isEditMode) {
            viewDiv.style.display = 'none';
            editForm.style.display = 'flex';
            if (coachDiv) coachDiv.style.display = 'none';
            btnText.innerText = 'Ver Detalle';
            if (btnIcon) btnIcon.setAttribute('data-lucide', 'eye');
        } else {
            viewDiv.style.display = 'grid';
            editForm.style.display = 'none';
            if (coachDiv) coachDiv.style.display = 'flex';
            btnText.innerText = 'Editar';
            if (btnIcon) btnIcon.setAttribute('data-lucide', 'edit-3');
        }
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }

    function openDealDetailModal(dealId) {
        currentActiveDealId = dealId;
        
        // Forzar restauración a Modo Vista
        isEditMode = true;
        toggleEditMode();
        
        fetch(`api.php?action=get_deal_details&deal_id=${dealId}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const deal = data.deal;
                    
                    const activeFramework = '<?php echo $qualification_framework; ?>';
                    
                    // Actualizar recomendación contextual del Coach Brian Tracy
                    let coachTip = "Preguntar es la clave para cerrar. Haz preguntas abiertas y escucha el 70% del tiempo.";
                    if (activeFramework === 'BANT') {
                        if (parseInt(deal.bant_budget) === 0) {
                            coachTip = "Si el prospecto no cuenta con presupuesto calificado, no pierdas tu tiempo. Pregunta temprano para enfocar tus esfuerzos en clientes con capacidad de compra real.";
                        } else if (parseInt(deal.bant_authority) === 0) {
                            coachTip = "Asegúrate de hablar con el tomador de decisión final. Venderle a un asistente puede atrasar o enfriar el trato indefinidamente.";
                        } else if (parseInt(deal.bant_need) === 0) {
                            coachTip = "Conéctate con la necesidad de su negocio. Si tu cliente no siente una necesidad urgente de cambiar sus hornos, no tendrá prisa por comprar.";
                        } else if (parseInt(deal.bant_timeline) === 0) {
                            coachTip = "Establece un plazo claro para el cierre. Sin un marco de tiempo definido, el trato flotará sin rumbo en tu embudo.";
                        } else if (parseInt(deal.bant_budget) === 1 && parseInt(deal.bant_authority) === 1 && parseInt(deal.bant_need) === 1 && parseInt(deal.bant_timeline) === 1) {
                            coachTip = "¡Excelente calificación BANT! Tienes presupuesto, decisor, necesidad y plazo. Ahora concéntrate en demostrar la rentabilidad técnica de TIPS.";
                        }
                    } else {
                        if (parseInt(deal.meddic_metrics) === 0) {
                            coachTip = "Define métricas claras. Si no cuantificas el beneficio económico (ej: ahorro de tiempo/energía con el nuevo horno), el cliente no justificará el gasto.";
                        } else if (parseInt(deal.meddic_buyer) === 0) {
                            coachTip = "Identifica al Comprador Económico (Economic Buyer). Asegúrate de hablar con quien firma el cheque y aprueba el presupuesto de TIPS.";
                        } else if (parseInt(deal.meddic_criteria) === 0) {
                            coachTip = "Conoce los Criterios de Decisión. ¿Qué evalúa el cliente? ¿Precio, garantía, soporte técnico de TIPS o marca de batidoras?";
                        } else if (parseInt(deal.meddic_process) === 0) {
                            coachTip = "Estudia el Proceso de Decisión. ¿Cuáles son los pasos internos para autorizar la compra? ¿Requiere junta o aprobación de chef?";
                        } else if (parseInt(deal.meddic_pain) === 0) {
                            coachTip = "Identifica el Dolor (Pain). Si no hay un problema grave en su cocina actual (ej: batidoras lentas), no habrá urgencia de compra.";
                        } else if (parseInt(deal.meddic_champion) === 0) {
                            coachTip = "Encuentra a tu Campeón (Champion). Necesitas un aliado interno (ej: el repostero jefe) que defienda y promueva la compra de TIPS frente a la directiva.";
                        } else if (parseInt(deal.meddic_metrics) === 1 && parseInt(deal.meddic_buyer) === 1 && parseInt(deal.meddic_criteria) === 1 && parseInt(deal.meddic_process) === 1 && parseInt(deal.meddic_pain) === 1 && parseInt(deal.meddic_champion) === 1) {
                            coachTip = "¡Excelente calificación MEDDIC! Tienes métricas, decisor, criterios, proceso, dolor y campeón. El cierre del trato es inminente.";
                        }
                    }
                    document.getElementById('modal-sales-coach-text').innerText = `"${coachTip}"`;
                    
                    // Rellenar modo de vista
                    document.getElementById('detail-deal-title').innerText = deal.title;
                    document.getElementById('detail-deal-value').innerText = '$' + parseFloat(deal.value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    document.getElementById('detail-deal-stage').innerText = deal.stage_name;
                    document.getElementById('detail-deal-account').innerText = deal.account_name || 'Sin Empresa Vinculada';
                    
                    if (deal.first_name) {
                        document.getElementById('detail-deal-contact').innerText = deal.first_name + ' ' + deal.last_name;
                        document.getElementById('detail-deal-job').innerText = (deal.job_title || 'Contacto B2B') + ' | ' + (deal.contact_phone || deal.phone || 'Sin teléfono');
                    } else {
                        document.getElementById('detail-deal-contact').innerText = 'Sin Contacto Asociado';
                        document.getElementById('detail-deal-job').innerText = '';
                    }
                    
                    // Rellenar modo de edición
                    document.getElementById('edit-deal-id').value = deal.id;
                    document.getElementById('edit-deal-title-input').value = deal.title;
                    document.getElementById('edit-deal-value-input').value = deal.value;
                    document.getElementById('edit-deal-stage-input').value = deal.stage_id;
                    document.getElementById('edit-deal-close-date-input').value = deal.close_date ? deal.close_date.split(' ')[0] : '';
                    document.getElementById('edit-deal-account-input').value = deal.account_id || '';
                    document.getElementById('edit-deal-contact-input').value = deal.contact_id || '';
                    document.getElementById('edit-deal-agent-input').value = deal.assigned_agent || 'Andrés Herrera (GAM Norte)';
                    
                    // Rellenar campos personalizados en Modo Vista
                    const cfViewList = document.getElementById('custom-fields-view-list');
                    cfViewList.innerHTML = '';
                    if (data.custom_fields && data.custom_fields.length > 0) {
                        data.custom_fields.forEach(cf => {
                            const valText = cf.value ? escapeHtml(cf.value) : '<em style="color:var(--text-dark);">Sin especificar</em>';
                            cfViewList.innerHTML += `
                                <div style="display:flex; justify-content:space-between; border-bottom:1px solid rgba(255,255,255,0.02); padding: 0.35rem 0;">
                                    <span style="color:var(--text-dark);">${escapeHtml(cf.name)}:</span>
                                    <span style="color:#fff; font-weight:600;">${valText}</span>
                                </div>
                            `;
                        });
                    } else {
                        cfViewList.innerHTML = '<p style="font-size:0.75rem; color:var(--text-dark); text-align:center;">Sin campos configurados.</p>';
                    }

                    // Rellenar campos personalizados en Modo Edición
                    const cfEditList = document.getElementById('custom-fields-edit-list');
                    cfEditList.innerHTML = '';
                    if (data.custom_fields && data.custom_fields.length > 0) {
                        data.custom_fields.forEach(cf => {
                            let inputHtml = '';
                            const curVal = cf.value ? escapeHtml(cf.value) : '';
                            const fieldNameAttr = `data-field-id="${cf.id}"`;
                            
                            if (cf.field_type === 'select') {
                                const opts = cf.options ? cf.options.split(',') : [];
                                inputHtml = `<select ${fieldNameAttr} class="custom-field-input" style="width:100%; padding:0.5rem; background:var(--bg-secondary); border:1px solid var(--border-color); color:#fff; border-radius:6px;">`;
                                inputHtml += `<option value="">-- Seleccionar --</option>`;
                                opts.forEach(opt => {
                                    const optClean = opt.trim();
                                    const selected = optClean === curVal ? 'selected' : '';
                                    inputHtml += `<option value="${optClean}" ${selected}>${optClean}</option>`;
                                });
                                inputHtml += `</select>`;
                            } else if (cf.field_type === 'date') {
                                inputHtml = `<input type="date" ${fieldNameAttr} class="custom-field-input" value="${curVal}" style="width:100%; padding:0.5rem; background:var(--bg-secondary); border:1px solid var(--border-color); color:#fff; border-radius:6px;">`;
                            } else if (cf.field_type === 'number') {
                                inputHtml = `<input type="number" ${fieldNameAttr} class="custom-field-input" value="${curVal}" style="width:100%; padding:0.5rem; background:var(--bg-secondary); border:1px solid var(--border-color); color:#fff; border-radius:6px;">`;
                            } else {
                                inputHtml = `<input type="text" ${fieldNameAttr} class="custom-field-input" value="${curVal}" style="width:100%; padding:0.5rem; background:var(--bg-secondary); border:1px solid var(--border-color); color:#fff; border-radius:6px;">`;
                            }
                            
                            cfEditList.innerHTML += `
                                <div class="form-group" style="margin-bottom:0;">
                                    <label style="font-size:0.8rem; margin-bottom:0.25rem;">${escapeHtml(cf.name)}</label>
                                    ${inputHtml}
                                </div>
                            `;
                        });
                    } else {
                        cfEditList.innerHTML = '<p style="grid-column: 1 / -1; font-size:0.75rem; color:var(--text-dark); text-align:center;">No hay campos configurados.</p>';
                    }
                    
                    // Configurar botones de interacción
                    const waBtn = document.getElementById('action-wa');
                    if (deal.contact_phone || deal.account_phone) {
                        const rawPhone = deal.contact_phone || deal.account_phone;
                        const cleanPhone = rawPhone.replace(/-|\s/g, "");
                        const phoneCode = cleanPhone.length === 8 ? '506' + cleanPhone : cleanPhone;
                        waBtn.href = `https://wa.me/${phoneCode}?text=Hola%20${encodeURIComponent(deal.first_name || 'cliente')},%20un%20gusto%20saludarte%20de%20TIPS%20S.A.`;
                        waBtn.style.opacity = '1';
                        waBtn.style.pointerEvents = 'auto';
                    } else {
                        waBtn.style.opacity = '0.5';
                        waBtn.style.pointerEvents = 'none';
                    }
                    
                    document.getElementById('action-email').href = `email_inbox.php?deal_id=${deal.id}`;
                    
                    // Rellenar notas
                    const notesList = document.getElementById('detail-notes-list');
                    notesList.innerHTML = '';
                    document.getElementById('new-note-content').value = '';
                    
                    if (data.notes && data.notes.length > 0) {
                        data.notes.forEach(note => {
                            const date = new Date(note.created_at).toLocaleDateString('es-CR', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
                            notesList.innerHTML += `
                                <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); padding: 0.75rem; border-radius: 6px; font-size: 0.8rem; margin-bottom: 0.5rem;">
                                    <p style="color: #fff; line-height: 1.4; white-space: pre-wrap; margin:0;">${escapeHtml(note.content)}</p>
                                    <span style="display: block; font-size: 0.7rem; color: var(--text-dark); margin-top: 0.5rem; text-align: right;">${date}</span>
                                </div>
                            `;
                        });
                    } else {
                        notesList.innerHTML = '<p style="font-size:0.8rem; color:var(--text-dark); text-align:center; margin-top:1rem;">Sin notas o comentarios registrados.</p>';
                    }
                    
                    dealDetailModal.style.display = 'flex';
                    setTimeout(() => dealDetailModal.classList.add('active'), 10);
                    
                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                } else {
                    alert("Error al cargar detalles: " + data.error);
                }
            })
            .catch(err => {
                console.error(err);
                alert("Error de conexión al cargar la oportunidad.");
            });
    }

    function saveDealChanges(event) {
        event.preventDefault();
        
        const customFields = {};
        document.querySelectorAll('.custom-field-input').forEach(input => {
            const fieldId = input.dataset.fieldId;
            customFields[fieldId] = input.value;
        });

        const payload = {
            deal_id: document.getElementById('edit-deal-id').value,
            title: document.getElementById('edit-deal-title-input').value,
            value: document.getElementById('edit-deal-value-input').value,
            stage_id: document.getElementById('edit-deal-stage-input').value,
            close_date: document.getElementById('edit-deal-close-date-input').value,
            account_id: document.getElementById('edit-deal-account-input').value,
            contact_id: document.getElementById('edit-deal-contact-input').value,
            assigned_agent: document.getElementById('edit-deal-agent-input').value,
            custom_fields: customFields
        };
        
        fetch('api.php?action=update_deal', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert("Error al guardar cambios: " + data.error);
            }
        })
        .catch(err => {
            console.error(err);
            alert("Error en la conexión al actualizar el trato.");
        });
    }

    function closeDealDetailModal() {
        dealDetailModal.classList.remove('active');
        setTimeout(() => dealDetailModal.style.display = 'none', 250);
        currentActiveDealId = null;
    }

    function submitDealNote() {
        const txt = document.getElementById('new-note-content').value.trim();
        if (!txt) return;
        
        fetch('api.php?action=add_deal_note', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                deal_id: currentActiveDealId,
                content: txt
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                openDealDetailModal(currentActiveDealId);
            } else {
                alert("Error al guardar nota: " + data.error);
            }
        });
    }

    function escapeHtml(text) {
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // Modal de Campos Personalizados
    const customFieldsModal = document.getElementById('custom-fields-modal');

    function openCustomFieldsModal() {
        customFieldsModal.style.display = 'flex';
        setTimeout(() => customFieldsModal.classList.add('active'), 10);
    }

    function closeCustomFieldsModal() {
        customFieldsModal.classList.remove('active');
        setTimeout(() => customFieldsModal.style.display = 'none', 250);
    }

    function toggleOptionsInput(val) {
        const group = document.getElementById('cf_options_group');
        const input = document.getElementById('cf_options');
        if (val === 'select') {
            group.style.display = 'block';
            input.required = true;
        } else {
            group.style.display = 'none';
            input.required = false;
            input.value = '';
        }
    }

    function deleteCustomField(fieldId) {
        if (!confirm("¿Estás seguro de que deseas eliminar este campo personalizado? Se borrarán todos los valores registrados en los tratos.")) return;
        
        fetch('api.php?action=delete_custom_field', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ field_id: fieldId })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert("Error al eliminar el campo personalizado: " + data.error);
            }
        });
    }

    window.addEventListener('click', (e) => {
        if (e.target === stageModal) {
            closeStageModal();
        }
        if (e.target === pipelineModal) {
            closePipelineModal();
        }
        if (e.target === customFieldsModal) {
            closeCustomFieldsModal();
        }
        if (e.target === dealDetailModal) {
            closeDealDetailModal();
        }
    });
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>

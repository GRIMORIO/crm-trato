<?php
/**
 * settings.php — Panel de Configuración General, APIs y AI para TIPS CRM
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_login();

// Obtener todas las configuraciones actuales
$settings_rows = $pdo->query("SELECT setting_key, setting_value FROM crm_settings")->fetchAll(PDO::FETCH_ASSOC);
$settings = [];
foreach ($settings_rows as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Valores por defecto si no existen
$default_settings = [
    'crm_name' => 'TIPS CRM B2B',
    'sales_quota_target' => '30000',
    'notification_email' => 'alertas@tips.cr',
    'openai_api_key' => '',
    'openai_model' => 'gpt-4o-mini',
    'serpapi_key' => '',
    'scoring_budget' => '25',
    'scoring_authority' => '25',
    'scoring_need' => '25',
    'scoring_timeline' => '25',
    'smtp_host' => 'smtp.hostinger.com',
    'smtp_port' => '465',
    'smtp_secure' => 'ssl',
    'smtp_user' => 'hola@crmtrato.com',
    'smtp_pass' => '',
    'smtp_from_email' => 'hola@crmtrato.com',
    'smtp_from_name' => 'TIPS CRM'
];

foreach ($default_settings as $key => $def_val) {
    if (!isset($settings[$key])) {
        $settings[$key] = $def_val;
    }
}

$title = "Configuración del Sistema";
require_once __DIR__ . '/includes/header.php';
?>

<style>
.settings-container {
    display: grid;
    grid-template-columns: 240px 1fr;
    gap: 2rem;
    align-items: start;
}

.settings-nav {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    background: rgba(255, 255, 255, 0.02);
    border: 1px solid var(--border-color);
    padding: 1rem;
    border-radius: 10px;
}

.settings-nav-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    color: var(--text-muted);
    font-size: 0.9rem;
    font-weight: 500;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    border: none;
    background: none;
    text-align: left;
    width: 100%;
}

.settings-nav-item:hover, .settings-nav-item.active {
    color: #fff;
    background: rgba(6, 182, 212, 0.1);
}

.settings-nav-item.active {
    border-left: 3px solid var(--color-primary);
    border-radius: 0 8px 8px 0;
    padding-left: calc(1rem - 3px);
}

.settings-content-card {
    background: rgba(255, 255, 255, 0.02);
    border: 1px solid var(--border-color);
    padding: 2rem;
    border-radius: 10px;
}

.settings-section {
    display: none;
}

.settings-section.active {
    display: block;
}

.settings-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #fff;
    margin-bottom: 0.5rem;
    border-bottom: 1px dashed var(--border-color);
    padding-bottom: 0.75rem;
}

.settings-description {
    font-size: 0.85rem;
    color: var(--text-muted);
    margin-bottom: 1.5rem;
}

.settings-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

.settings-full-width {
    grid-column: 1 / -1;
}

.btn-save-settings {
    background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
    color: #fff;
    font-weight: 600;
    padding: 0.75rem 2rem;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: transform 0.2s ease;
    margin-top: 1.5rem;
    width: fit-content;
}

.btn-save-settings:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(6, 182, 212, 0.25);
}

.toast-alert {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: var(--color-success);
    color: #fff;
    padding: 1rem 1.5rem;
    border-radius: 8px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.3);
    z-index: 9999;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    transform: translateY(150%);
    transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.toast-alert.show {
    transform: translateY(0);
}

/* Switch toggle styling */
.slider-toggle-round {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(255, 255, 255, 0.1);
    transition: .3s;
    border-radius: 20px;
}

.slider-toggle-round:before {
    position: absolute;
    content: "";
    height: 14px;
    width: 14px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .3s;
    border-radius: 50%;
}

input:checked + .slider-toggle-round {
    background-color: var(--color-success);
}

input:checked + .slider-toggle-round:before {
    transform: translateX(20px);
}
</style>

<div class="settings-container">
    <!-- Navegación Lateral Interna -->
    <div class="settings-nav">
        <button class="settings-nav-item active" onclick="showSection('general')">
            <i data-lucide="sliders"></i>
            <span>General</span>
        </button>
        <button class="settings-nav-item" onclick="showSection('apis')">
            <i data-lucide="key-round"></i>
            <span>APIs e Integraciones</span>
        </button>
        <button class="settings-nav-item" onclick="showSection('smtp')">
            <i data-lucide="mail"></i>
            <span>Correo (SMTP)</span>
        </button>
        <button class="settings-nav-item" onclick="showSection('scoring')">
            <i data-lucide="gauge"></i>
            <span>Lead Scoring</span>
        </button>
        <button class="settings-nav-item" onclick="showSection('automations')">
            <i data-lucide="play-circle"></i>
            <span>Automatizaciones</span>
        </button>
    </div>

    <!-- Formulario y Contenido de Configuración -->
    <form id="settings-form" onsubmit="saveCRMConfigurations(event)">
        <div class="settings-content-card">
            
            <!-- SECCIÓN 1: GENERAL -->
            <div id="section-general" class="settings-section active">
                <h3 class="settings-title">Configuración General</h3>
                <p class="settings-description">Establece los parámetros globales de la aplicación y metas de ventas de TIPS S.A.</p>
                
                <div class="settings-grid">
                    <div class="form-group">
                        <label for="crm_name">Nombre del CRM *</label>
                        <input type="text" id="crm_name" name="crm_name" value="<?php echo htmlspecialchars($settings['crm_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="sales_quota_target">Meta de Cierre Mensual ($) *</label>
                        <input type="number" id="sales_quota_target" name="sales_quota_target" value="<?php echo htmlspecialchars($settings['sales_quota_target']); ?>" required>
                    </div>
                    <div class="form-group settings-full-width">
                        <label for="notification_email">Correo de Notificaciones Administrativas</label>
                        <input type="email" id="notification_email" name="notification_email" value="<?php echo htmlspecialchars($settings['notification_email']); ?>" placeholder="ejemplo@tips.cr">
                    </div>
                </div>
            </div>

            <!-- SECCIÓN 2: APIS E INTEGRACIONES -->
            <div id="section-apis" class="settings-section">
                <h3 class="settings-title">Credenciales de APIs y AI</h3>
                <p class="settings-description">Configura los tokens de acceso para conectar con motores de Inteligencia Artificial y búsquedas de internet.</p>
                
                <div class="settings-grid">
                    <div class="form-group settings-full-width">
                        <label for="openai_api_key">OpenAI API Key (Sales Coach & Chats)</label>
                        <input type="password" id="openai_api_key" name="openai_api_key" value="<?php echo htmlspecialchars($settings['openai_api_key']); ?>" placeholder="sk-proj-...">
                        <small style="color:var(--text-muted); font-size:0.75rem; display:block; margin-top:0.25rem;">Utilizada para generar sugerencias del Sales Coach de Brian Tracy y respuestas del chatbot de leads.</small>
                    </div>
                    <div class="form-group">
                        <label for="openai_model">Modelo de Lenguaje Predeterminado</label>
                        <select id="openai_model" name="openai_model">
                            <option value="gpt-4o-mini" <?php echo $settings['openai_model'] === 'gpt-4o-mini' ? 'selected' : ''; ?>>gpt-4o-mini (Recomendado - Rápido)</option>
                            <option value="gpt-4o" <?php echo $settings['openai_model'] === 'gpt-4o' ? 'selected' : ''; ?>>gpt-4o (Avanzado)</option>
                            <option value="gpt-3.5-turbo" <?php echo $settings['openai_model'] === 'gpt-3.5-turbo' ? 'selected' : ''; ?>>gpt-3.5-turbo (Legado)</option>
                        </select>
                    </div>
                    <div class="form-group settings-full-width" style="border-top: 1px dashed var(--border-color); padding-top: 1.5rem; margin-top: 0.5rem;">
                        <label for="serpapi_key">SerpApi Key (Google Search Integration)</label>
                        <input type="password" id="serpapi_key" name="serpapi_key" value="<?php echo htmlspecialchars($settings['serpapi_key']); ?>" placeholder="Ingrese su SerpApi Key...">
                        <small style="color:var(--text-muted); font-size:0.75rem; display:block; margin-top:0.25rem;">Permite al CRM buscar información externa de empresas y clientes en la web automáticamente.</small>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN: CORREO (SMTP) -->
            <div id="section-smtp" class="settings-section">
                <h3 class="settings-title">Correo Saliente (SMTP)</h3>
                <p class="settings-description">Buzón desde el que el CRM envía correos reales (recuperación de contraseña, notificaciones). Datos del buzón de Hostinger <code>hola@crmtrato.com</code>.</p>

                <div class="settings-grid">
                    <div class="form-group">
                        <label for="smtp_host">Servidor SMTP *</label>
                        <input type="text" id="smtp_host" name="smtp_host" value="<?php echo htmlspecialchars($settings['smtp_host']); ?>" placeholder="smtp.hostinger.com">
                    </div>
                    <div class="form-group">
                        <label for="smtp_port">Puerto *</label>
                        <input type="number" id="smtp_port" name="smtp_port" value="<?php echo htmlspecialchars($settings['smtp_port']); ?>" placeholder="465">
                    </div>
                    <div class="form-group">
                        <label for="smtp_secure">Seguridad *</label>
                        <select id="smtp_secure" name="smtp_secure">
                            <option value="ssl" <?php echo $settings['smtp_secure'] === 'ssl' ? 'selected' : ''; ?>>SSL (puerto 465)</option>
                            <option value="tls" <?php echo $settings['smtp_secure'] === 'tls' ? 'selected' : ''; ?>>STARTTLS (puerto 587)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="smtp_user">Usuario / dirección *</label>
                        <input type="text" id="smtp_user" name="smtp_user" value="<?php echo htmlspecialchars($settings['smtp_user']); ?>" placeholder="hola@crmtrato.com" autocomplete="off">
                    </div>
                    <div class="form-group settings-full-width">
                        <label for="smtp_pass">Contraseña del buzón *</label>
                        <input type="password" id="smtp_pass" name="smtp_pass" value="<?php echo htmlspecialchars($settings['smtp_pass']); ?>" placeholder="Contraseña del correo hola@crmtrato.com" autocomplete="new-password">
                        <small style="color:var(--text-muted); font-size:0.75rem; display:block; margin-top:0.25rem;">Se guarda en la base de datos del CRM. Dejar vacío desactiva el envío real de correo.</small>
                    </div>
                    <div class="form-group">
                        <label for="smtp_from_email">Remitente (From)</label>
                        <input type="email" id="smtp_from_email" name="smtp_from_email" value="<?php echo htmlspecialchars($settings['smtp_from_email']); ?>" placeholder="hola@crmtrato.com">
                    </div>
                    <div class="form-group">
                        <label for="smtp_from_name">Nombre del remitente</label>
                        <input type="text" id="smtp_from_name" name="smtp_from_name" value="<?php echo htmlspecialchars($settings['smtp_from_name']); ?>" placeholder="TIPS CRM">
                    </div>
                    <div class="form-group settings-full-width" style="border-top: 1px dashed var(--border-color); padding-top: 1.25rem; margin-top: 0.5rem;">
                        <label for="smtp_test_to">Enviar correo de prueba a</label>
                        <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
                            <input type="email" id="smtp_test_to" placeholder="tu-correo@ejemplo.com" style="flex:1; min-width:220px;">
                            <button type="button" class="btn-action btn-secondary" onclick="sendSmtpTest()" style="white-space:nowrap;">
                                <i data-lucide="send" style="width:14px; height:14px; display:inline-block; vertical-align:middle; margin-right:4px;"></i> Probar envío
                            </button>
                        </div>
                        <small id="smtp-test-result" style="font-size:0.78rem; display:block; margin-top:0.5rem;"></small>
                        <small style="color:var(--text-muted); font-size:0.72rem; display:block; margin-top:0.25rem;">Guarda primero los cambios; la prueba usa la configuración ya guardada.</small>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN 3: LEAD SCORING -->
            <div id="section-scoring" class="settings-section">
                <h3 class="settings-title">Criterio de Calificación de Oportunidades</h3>
                <p class="settings-description">Selecciona el marco metodológico de ventas (BANT o MEDDIC) y ajusta sus pesos de puntuación de Lead Scoring.</p>
                
                <div class="settings-grid" style="margin-bottom: 1.5rem;">
                    <div class="form-group settings-full-width">
                        <label for="qualification_framework">Marco de Calificación Activo SOP *</label>
                        <select id="qualification_framework" name="qualification_framework" onchange="toggleFrameworkInputs(this.value)">
                            <option value="BANT" <?php echo $settings['qualification_framework'] === 'BANT' ? 'selected' : ''; ?>>BANT (Budget, Authority, Need, Timeline) - Clásico</option>
                            <option value="MEDDIC" <?php echo $settings['qualification_framework'] === 'MEDDIC' ? 'selected' : ''; ?>>MEDDIC (Metrics, Economic Buyer, Decision Criteria/Process, Pain, Champion) - Corporativo</option>
                        </select>
                    </div>
                </div>

                <!-- Pesos BANT -->
                <div id="framework-bant-container" style="display: <?php echo $settings['qualification_framework'] === 'BANT' ? 'grid' : 'none'; ?>; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <h4 class="settings-full-width" style="color:var(--color-primary); font-size:0.95rem; font-weight:600; margin-bottom: -0.5rem;">Ponderaciones para BANT (Suma sugerida: 100%)</h4>
                    <div class="form-group">
                        <label for="scoring_budget">Puntos por Presupuesto (Budget) *</label>
                        <input type="number" id="scoring_budget" name="scoring_budget" value="<?php echo htmlspecialchars($settings['scoring_budget']); ?>" min="0" max="100">
                    </div>
                    <div class="form-group">
                        <label for="scoring_authority">Puntos por Autoridad (Authority) *</label>
                        <input type="number" id="scoring_authority" name="scoring_authority" value="<?php echo htmlspecialchars($settings['scoring_authority']); ?>" min="0" max="100">
                    </div>
                    <div class="form-group">
                        <label for="scoring_need">Puntos por Necesidad (Need) *</label>
                        <input type="number" id="scoring_need" name="scoring_need" value="<?php echo htmlspecialchars($settings['scoring_need']); ?>" min="0" max="100">
                    </div>
                    <div class="form-group">
                        <label for="scoring_timeline">Puntos por Plazo (Timeline) *</label>
                        <input type="number" id="scoring_timeline" name="scoring_timeline" value="<?php echo htmlspecialchars($settings['scoring_timeline']); ?>" min="0" max="100">
                    </div>
                </div>

                <!-- Pesos MEDDIC -->
                <div id="framework-meddic-container" style="display: <?php echo $settings['qualification_framework'] === 'MEDDIC' ? 'grid' : 'none'; ?>; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <h4 class="settings-full-width" style="color:var(--color-secondary); font-size:0.95rem; font-weight:600; margin-bottom: -0.5rem;">Ponderaciones para MEDDIC (Suma sugerida: 100%)</h4>
                    <div class="form-group">
                        <label for="scoring_meddic_metrics">Métricas (Metrics) *</label>
                        <input type="number" id="scoring_meddic_metrics" name="scoring_meddic_metrics" value="<?php echo htmlspecialchars($settings['scoring_meddic_metrics']); ?>" min="0" max="100">
                    </div>
                    <div class="form-group">
                        <label for="scoring_meddic_buyer">Comprador Económico (Economic Buyer) *</label>
                        <input type="number" id="scoring_meddic_buyer" name="scoring_meddic_buyer" value="<?php echo htmlspecialchars($settings['scoring_meddic_buyer']); ?>" min="0" max="100">
                    </div>
                    <div class="form-group">
                        <label for="scoring_meddic_criteria">Criterio de Decisión (Decision Criteria) *</label>
                        <input type="number" id="scoring_meddic_criteria" name="scoring_meddic_criteria" value="<?php echo htmlspecialchars($settings['scoring_meddic_criteria']); ?>" min="0" max="100">
                    </div>
                    <div class="form-group">
                        <label for="scoring_meddic_process">Proceso de Decisión (Decision Process) *</label>
                        <input type="number" id="scoring_meddic_process" name="scoring_meddic_process" value="<?php echo htmlspecialchars($settings['scoring_meddic_process']); ?>" min="0" max="100">
                    </div>
                    <div class="form-group">
                        <label for="scoring_meddic_pain">Dolor Identificado (Identify Pain) *</label>
                        <input type="number" id="scoring_meddic_pain" name="scoring_meddic_pain" value="<?php echo htmlspecialchars($settings['scoring_meddic_pain']); ?>" min="0" max="100">
                    </div>
                    <div class="form-group">
                        <label for="scoring_meddic_champion">Campeón (Champion) *</label>
                        <input type="number" id="scoring_meddic_champion" name="scoring_meddic_champion" value="<?php echo htmlspecialchars($settings['scoring_meddic_champion']); ?>" min="0" max="100">
                    </div>
                </div>
            </div>

            <!-- SECCIÓN 4: AUTOMATIZACIONES -->
            <div id="section-automations" class="settings-section">
                <h3 class="settings-title">Motor de Automatizaciones (Workflow SOP)</h3>
                <p class="settings-description">Define reglas para crear automáticamente tareas y actividades en el calendario cuando las oportunidades cambian de etapa.</p>
                
                <!-- Formulario de creación de regla -->
                <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem;">
                    <h4 style="color:#fff; font-size:1rem; margin-bottom:1rem; font-weight:600; display:flex; align-items:center; gap:0.5rem;">
                        <i data-lucide="plus-circle" style="color:var(--color-primary); width:18px; height:18px;"></i>
                        Nueva Regla de Automatización
                    </h4>
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1.25rem;">
                        <div class="form-group">
                            <label>Nombre de la Regla *</label>
                            <input type="text" id="auto_title" placeholder="Ej. Tarea de seguimiento de muestra">
                        </div>
                        <div class="form-group">
                            <label>Cuando un trato entra en la etapa *</label>
                            <select id="auto_trigger_value">
                                <option value="">-- Seleccionar Etapa --</option>
                                <?php
                                $stages_list = $pdo->query("SELECT s.id, s.name, p.name AS pipeline_name FROM stages s JOIN pipelines p ON s.pipeline_id = p.id ORDER BY p.id, s.position")->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($stages_list as $stg) {
                                    echo "<option value='{$stg['id']}'>" . htmlspecialchars($stg['pipeline_name'] . ' ➔ ' . $stg['name']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Acción: Crear Actividad Tipo</label>
                            <select id="auto_task_type">
                                <option value="Task">Tarea</option>
                                <option value="Call">Llamada</option>
                                <option value="Meeting">Reunión</option>
                                <option value="Technical_Visit">Visita Técnica</option>
                                <option value="Demo">Demostración</option>
                                <option value="Samples">Muestras</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Título de la Actividad *</label>
                            <input type="text" id="auto_task_title" placeholder="Ej. Entregar colorantes Enco al cliente">
                        </div>
                        <div class="form-group">
                            <label>Vencimiento (Días después del trigger) *</label>
                            <input type="number" id="auto_due_in_days" min="0" max="90" value="2">
                        </div>
                        <div class="form-group" style="display: flex; align-items: flex-end;">
                            <button type="button" onclick="submitNewAutomationRule()" class="btn-save-settings" style="width:100%; margin:0; padding:0.65rem; background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));">
                                <i data-lucide="play" style="width:16px; height:16px;"></i>
                                Activar Automatización
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tabla de reglas actuales -->
                <div class="custom-table-container">
                    <h4 style="color:#fff; font-size:1rem; margin-bottom:1rem; font-weight:600;">Reglas Activas en TIPS CRM</h4>
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Nombre de la Regla</th>
                                <th>Etapa Desencadenante (Trigger)</th>
                                <th>Acción Programada</th>
                                <th>Estado</th>
                                <th style="text-align: center;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="automations-list-tbody">
                            <?php
                            $automations_list = $pdo->query("SELECT * FROM `crm_automations` ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
                            if (empty($automations_list)):
                            ?>
                                <tr>
                                    <td colspan="5" style="text-align:center; color:var(--text-dark); padding: 2rem;">No hay reglas de automatización configuradas.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($automations_list as $auto): 
                                    $payload = json_decode($auto['action_payload'], true);
                                    
                                    // Obtener nombre de la etapa trigger
                                    $stg_name = "Cualquier etapa";
                                    if ($auto['trigger_value'] !== '*') {
                                        $stmt_stg = $pdo->prepare("SELECT s.name, p.name AS p_name FROM stages s JOIN pipelines p ON s.pipeline_id = p.id WHERE s.id = ?");
                                        $stmt_stg->execute([$auto['trigger_value']]);
                                        $stg_info = $stmt_stg->fetch(PDO::FETCH_ASSOC);
                                        if ($stg_info) {
                                            $stg_name = $stg_info['p_name'] . ' ➔ ' . $stg_info['name'];
                                        }
                                    }
                                    ?>
                                    <tr id="automation-row-<?php echo $auto['id']; ?>">
                                        <td><strong><?php echo htmlspecialchars($auto['title']); ?></strong></td>
                                        <td><span class="badge badge-open"><?php echo htmlspecialchars($stg_name); ?></span></td>
                                        <td>
                                            <span class="badge badge-pending"><?php echo htmlspecialchars($payload['type'] ?? 'Task'); ?></span>
                                            <span style="font-size:0.8rem; color:var(--text-muted); display:block; margin-top:2px;">
                                                "<?php echo htmlspecialchars($payload['title'] ?? ''); ?>" (vence en <?php echo intval($payload['due_in_days'] ?? 0); ?> días)
                                            </span>
                                        </td>
                                        <td>
                                            <label class="switch-toggle" style="display:inline-block; position:relative; width:40px; height:20px; vertical-align:middle;">
                                                <input type="checkbox" <?php echo $auto['is_active'] ? 'checked' : ''; ?> 
                                                       onchange="toggleAutomationState(<?php echo $auto['id']; ?>)"
                                                       style="opacity:0; width:0; height:0;">
                                                <span class="slider-toggle-round"></span>
                                            </label>
                                        </td>
                                        <td style="text-align: center;">
                                            <button type="button" onclick="deleteAutomationRule(<?php echo $auto['id']; ?>)" 
                                                    style="background:none; border:none; color:var(--color-error); cursor:pointer;" title="Eliminar regla">
                                                <i data-lucide="trash-2" style="width:16px; height:16px;"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Botón de Guardado Consolidado -->
            <button type="submit" class="btn-save-settings">
                <i data-lucide="save" style="width: 18px; height: 18px;"></i>
                Guardar Configuración
            </button>
        </div>
    </form>
</div>

<!-- Notificación Flotante (Toast) -->
<div id="toast-success" class="toast-alert">
    <i data-lucide="check-circle-2" style="width: 20px; height: 20px;"></i>
    <span id="toast-message">Configuración actualizada con éxito.</span>
</div>

<script>
    function showSection(sectionId) {
        // Ocultar todas las secciones
        document.querySelectorAll('.settings-section').forEach(sec => {
            sec.classList.remove('active');
        });
        
        // Desactivar botones de navegación
        document.querySelectorAll('.settings-nav-item').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Mostrar sección activa
        document.getElementById('section-' + sectionId).classList.add('active');
        
        // Activar botón correspondiente
        // Buscamos el botón por su llamada onclick
        const buttons = document.querySelectorAll('.settings-nav-item');
        buttons.forEach(btn => {
            if (btn.getAttribute('onclick').includes(sectionId)) {
                btn.classList.add('active');
            }
        });
        
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }

    function toggleFrameworkInputs(val) {
        const bantContainer = document.getElementById('framework-bant-container');
        const meddicContainer = document.getElementById('framework-meddic-container');
        if (val === 'BANT') {
            bantContainer.style.display = 'grid';
            meddicContainer.style.display = 'none';
        } else {
            bantContainer.style.display = 'none';
            meddicContainer.style.display = 'grid';
        }
    }

    function saveCRMConfigurations(event) {
        event.preventDefault();
        
        const form = document.getElementById('settings-form');
        const formData = new FormData(form);
        const payload = {};
        
        formData.forEach((value, key) => {
            payload[key] = value;
        });
        
        fetch('api.php?action=update_settings', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToast("✔️ ¡Configuraciones guardadas exitosamente!");
                // Opcional: Recargar después de un breve delay
                setTimeout(() => location.reload(), 1500);
            } else {
                alert("Error al guardar configuraciones: " + data.error);
            }
        })
        .catch(err => {
            console.error(err);
            alert("Error en la conexión con el servidor.");
        });
    }

    function showToast(msg) {
        const toast = document.getElementById('toast-success');
        document.getElementById('toast-message').innerText = msg;
        toast.classList.add('show');

        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }

    function sendSmtpTest() {
        const to = document.getElementById('smtp_test_to').value.trim();
        const result = document.getElementById('smtp-test-result');
        if (!to) {
            result.style.color = 'var(--color-error)';
            result.textContent = '❌ Indica un correo de destino.';
            return;
        }
        result.style.color = 'var(--text-muted)';
        result.textContent = '⏳ Enviando…';

        fetch('api.php?action=test_smtp', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ to: to })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                result.style.color = 'var(--color-success, #10b981)';
                result.textContent = '✔️ ' + (data.message || 'Correo de prueba enviado.');
            } else {
                result.style.color = 'var(--color-error)';
                result.textContent = '❌ ' + (data.error || 'No se pudo enviar.');
            }
        })
        .catch(() => {
            result.style.color = 'var(--color-error)';
            result.textContent = '❌ Error de red al enviar la prueba.';
        });
    }

    function submitNewAutomationRule() {
        const title = document.getElementById('auto_title').value.trim();
        const triggerValue = document.getElementById('auto_trigger_value').value;
        const taskType = document.getElementById('auto_task_type').value;
        const taskTitle = document.getElementById('auto_task_title').value.trim();
        const dueInDays = document.getElementById('auto_due_in_days').value;

        if (!title || !triggerValue || !taskTitle) {
            alert("Por favor rellene todos los campos obligatorios.");
            return;
        }

        const payload = {
            title: title,
            trigger_value: triggerValue,
            task_type: taskType,
            task_title: taskTitle,
            due_in_days: dueInDays
        };

        fetch('api.php?action=create_automation', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToast("✔️ Automatización creada.");
                setTimeout(() => {
                    window.location.hash = '#section-automations';
                    location.reload();
                }, 1000);
            } else {
                alert("Error al crear automatización: " + data.error);
            }
        })
        .catch(err => {
            console.error(err);
            alert("Error de conexión al guardar regla.");
        });
    }

    function toggleAutomationState(id) {
        fetch('api.php?action=toggle_automation', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: id })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToast("✔️ Estado de automatización actualizado.");
                setTimeout(() => {
                    window.location.hash = '#section-automations';
                    location.reload();
                }, 1000);
            } else {
                alert("Error al actualizar estado: " + data.error);
            }
        });
    }

    function deleteAutomationRule(id) {
        if (!confirm("¿Está seguro de que desea eliminar esta regla de automatización?")) {
            return;
        }

        fetch('api.php?action=delete_automation', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: id })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToast("✔️ Regla eliminada.");
                const row = document.getElementById('automation-row-' + id);
                if (row) {
                    row.remove();
                }
            } else {
                alert("Error al eliminar regla: " + data.error);
            }
        });
    }

    window.addEventListener('DOMContentLoaded', () => {
        // Cargar sección desde el hash si existe (ej. #section-automations)
        const hash = window.location.hash;
        if (hash && hash.startsWith('#section-')) {
            const secId = hash.replace('#section-', '');
            showSection(secId);
        }
    });
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>

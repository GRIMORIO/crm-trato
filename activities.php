<?php
/**
 * activities.php — Planificador y Agenda de Actividades para TIPS CRM
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/header.php';

// Obtener actividades y deals abiertos según el grupo de visibilidad
$restriction = get_visibility_restriction();

if ($restriction !== '') {
    $stmt_pending = $pdo->prepare("
        SELECT act.*, d.title AS deal_title 
        FROM activities act
        JOIN deals d ON act.deal_id = d.id
        WHERE act.status = 'Pending' AND d.assigned_agent = ?
        ORDER BY act.due_date ASC
    ");
    $stmt_pending->execute([$restriction]);
    $pending_activities = $stmt_pending->fetchAll();

    $stmt_completed = $pdo->prepare("
        SELECT act.*, d.title AS deal_title 
        FROM activities act
        JOIN deals d ON act.deal_id = d.id
        WHERE act.status = 'Completed' AND d.assigned_agent = ?
        ORDER BY act.due_date DESC
        LIMIT 20
    ");
    $stmt_completed->execute([$restriction]);
    $completed_activities = $stmt_completed->fetchAll();

    $stmt_deals = $pdo->prepare("SELECT id, title FROM deals WHERE status = 'Open' AND assigned_agent = ? ORDER BY title ASC");
    $stmt_deals->execute([$restriction]);
    $open_deals = $stmt_deals->fetchAll();
} else {
    $stmt_pending = $pdo->query("
        SELECT act.*, d.title AS deal_title 
        FROM activities act
        LEFT JOIN deals d ON act.deal_id = d.id
        WHERE act.status = 'Pending'
        ORDER BY act.due_date ASC
    ");
    $pending_activities = $stmt_pending->fetchAll();

    $stmt_completed = $pdo->query("
        SELECT act.*, d.title AS deal_title 
        FROM activities act
        LEFT JOIN deals d ON act.deal_id = d.id
        WHERE act.status = 'Completed'
        ORDER BY act.due_date DESC
        LIMIT 20
    ");
    $completed_activities = $stmt_completed->fetchAll();

    $open_deals = $pdo->query("SELECT id, title FROM deals WHERE status = 'Open' ORDER BY title ASC")->fetchAll();
}

// Construir eventos de FullCalendar
$events = [];
foreach ($pending_activities as $act) {
    $color = '#3b82f6'; // azul por defecto (Llamada, Tarea, etc.)
    if (strtotime($act['due_date']) < time()) {
        $color = '#ef4444'; // rojo para atrasados
    } elseif ($act['type'] == 'Meeting') {
        $color = '#8b5cf6'; // morado para reuniones
    } elseif ($act['type'] == 'Technical_Visit') {
        $color = '#10b981'; // verde para visitas técnicas
    } elseif ($act['type'] == 'Demo') {
        $color = '#ec4899'; // rosa para demos
    } elseif ($act['type'] == 'Samples') {
        $color = '#f59e0b'; // naranja para muestras
    }
    
    $events[] = [
        'id' => $act['id'],
        'title' => '[' . $act['type'] . '] ' . $act['subject'],
        'start' => $act['due_date'],
        'description' => $act['description'],
        'backgroundColor' => $color,
        'borderColor' => $color,
        'url' => $act['deal_id'] ? 'pipeline.php?deal_id=' . $act['deal_id'] : null,
        'extendedProps' => [
            'status' => $act['status'],
            'type' => $act['type'],
            'deal_title' => $act['deal_title'] ?: 'Seguimiento General'
        ]
    ];
}
foreach ($completed_activities as $act) {
    $events[] = [
        'id' => $act['id'],
        'title' => '✓ ' . $act['subject'],
        'start' => $act['due_date'],
        'description' => $act['description'],
        'backgroundColor' => 'rgba(100,116,139,0.12)',
        'borderColor' => 'rgba(100,116,139,0.25)',
        'textColor' => '#64748b',
        'extendedProps' => [
            'status' => $act['status'],
            'type' => $act['type'],
            'deal_title' => $act['deal_title'] ?: 'Seguimiento General'
        ]
    ];
}
?>

<!-- FullCalendar v6 CDN -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>

<style>
/* Estilos para el Calendario en Modo Oscuro */
.fc {
    color: #fff !important;
    font-family: 'Outfit', sans-serif !important;
}
.fc-theme-standard td, .fc-theme-standard th {
    border: 1px solid rgba(255, 255, 255, 0.08) !important;
}
.fc-theme-standard .fc-scrollgrid {
    border: 1px solid rgba(255, 255, 255, 0.08) !important;
}
.fc .fc-button-primary {
    background-color: var(--bg-secondary) !important;
    border-color: var(--border-color) !important;
    color: #fff !important;
    text-transform: capitalize;
}
.fc .fc-button-primary:hover {
    background-color: var(--color-primary) !important;
    border-color: var(--color-primary) !important;
}
.fc .fc-button-primary:disabled {
    background-color: rgba(255,255,255,0.05) !important;
    border-color: transparent !important;
    color: var(--text-dark) !important;
}
.fc .fc-daygrid-day.fc-day-today {
    background-color: rgba(6, 182, 212, 0.08) !important;
}
.fc .fc-col-header-cell-cushion {
    color: #94a3b8 !important;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.8rem;
    padding: 8px 0;
    text-decoration: none;
}
.fc .fc-daygrid-day-number {
    color: #fff !important;
    font-weight: 500;
    font-size: 0.9rem;
    padding: 8px !important;
    text-decoration: none;
}
.fc-event {
    cursor: pointer;
    padding: 3px 6px;
    border-radius: 4px;
    font-size: 0.78rem !important;
    font-weight: 500;
    margin: 1px 0;
    box-shadow: 0 4px 6px rgba(0,0,0,0.15);
    border: none !important;
}
.fc-event:hover {
    filter: brightness(1.2);
}
.fc .fc-toolbar-title {
    font-size: 1.25rem !important;
    font-weight: 700;
    color: #fff;
}
.fc .fc-list-event-title a {
    color: #fff !important;
    text-decoration: none;
}
.fc .fc-list-day-text, .fc .fc-list-day-side-text {
    color: var(--color-primary) !important;
    font-weight: 600;
}

/* Estilos para el Calendario en Modo Claro */
[data-theme="light"] .fc {
    color: var(--text-main) !important;
}
[data-theme="light"] .fc-theme-standard td,
[data-theme="light"] .fc-theme-standard th {
    border: 1px solid var(--border-color) !important;
}
[data-theme="light"] .fc-theme-standard .fc-scrollgrid {
    border: 1px solid var(--border-color) !important;
}
[data-theme="light"] .fc .fc-button-primary {
    background-color: var(--bg-secondary) !important;
    border-color: var(--border-color) !important;
    color: var(--text-main) !important;
}
[data-theme="light"] .fc .fc-button-primary:hover {
    background-color: var(--color-primary) !important;
    border-color: var(--color-primary) !important;
    color: #fff !important;
}
[data-theme="light"] .fc .fc-button-primary:disabled {
    background-color: rgba(15,23,42,0.05) !important;
    border-color: transparent !important;
    color: var(--text-dark) !important;
}
[data-theme="light"] .fc .fc-col-header-cell-cushion {
    color: var(--text-muted) !important;
}
[data-theme="light"] .fc .fc-daygrid-day-number {
    color: var(--text-main) !important;
}
[data-theme="light"] .fc .fc-toolbar-title {
    color: var(--text-main) !important;
}
[data-theme="light"] .fc .fc-list-event-title a {
    color: var(--text-main) !important;
}
</style>

<!-- Conmutador de Vistas -->
<div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-bottom: 1.5rem;">
    <button id="btn-view-list" class="btn-action" onclick="switchView('list')" style="padding: 0.5rem 1rem;">
        <i data-lucide="list" style="width:14px; height:14px; display:inline-block; vertical-align:middle; margin-right:4px;"></i> Vista Lista
    </button>
    <button id="btn-view-calendar" class="btn-action btn-secondary" onclick="switchView('calendar')" style="padding: 0.5rem 1rem;">
        <i data-lucide="calendar" style="width:14px; height:14px; display:inline-block; vertical-align:middle; margin-right:4px;"></i> Vista Calendario
    </button>
</div>

<!-- VISTA 1: LISTAS DE ACTIVIDADES -->
<div id="list-view-container" style="display: grid; grid-template-columns: 1fr; gap: 2rem;">
    
    <!-- PANEL DE ACTIVIDADES PENDIENTES -->
    <div class="card-section" style="margin-bottom:0;">
        <div class="section-header">
            <h2>Actividades de Seguimiento Pendientes</h2>
            <button class="btn-action btn-sm" onclick="openActivityModal()">
                <i data-lucide="plus-circle" style="width:14px; height:14px; display:inline-block; vertical-align:middle; margin-right:4px;"></i>
                Programar Tarea
            </button>
        </div>
        
        <div class="custom-table-container">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th style="width:40px;"></th>
                        <th>Tipo</th>
                        <th>Asunto / Actividad</th>
                        <th>Oportunidad Relacionada</th>
                        <th>Fecha Límite</th>
                        <th>Notas</th>
                    </tr>
                </thead>
                <tbody id="pending-tasks-body">
                    <?php if (count($pending_activities) > 0): ?>
                        <?php foreach ($pending_activities as $act): 
                            // Icono y etiqueta amigable según tipo
                            $icon = 'phone';
                            $friendly_type = 'Llamada';
                            if ($act['type'] == 'Email') { $icon = 'mail'; $friendly_type = 'Correo'; }
                            elseif ($act['type'] == 'Meeting') { $icon = 'users'; $friendly_type = 'Reunión'; }
                            elseif ($act['type'] == 'Task') { $icon = 'check-square'; $friendly_type = 'Tarea'; }
                            elseif ($act['type'] == 'Technical_Visit') { $icon = 'wrench'; $friendly_type = 'Visita Técnica'; }
                            elseif ($act['type'] == 'Demo') { $icon = 'play'; $friendly_type = 'Demostración'; }
                            elseif ($act['type'] == 'Samples') { $icon = 'package'; $friendly_type = 'Muestras'; }
                            
                            // Determinar si está atrasada
                            $is_overdue = strtotime($act['due_date']) < time();
                            $due_color = $is_overdue ? 'color: var(--color-error); font-weight: 600;' : '';
                            ?>
                            <tr id="activity-row-<?php echo $act['id']; ?>">
                                <td style="text-align:center;">
                                    <input type="checkbox" onchange="completeActivity(<?php echo $act['id']; ?>)" style="width:18px; height:18px; cursor:pointer; accent-color:var(--color-success);">
                                </td>
                                <td>
                                    <span class="act-icon act-<?php echo $act['type']; ?>">
                                        <i data-lucide="<?php echo $icon; ?>" style="width:14px; height:14px;"></i>
                                    </span>
                                    <span style="font-size:0.85rem; font-weight:600; text-transform:uppercase; color:var(--text-muted);"><?php echo $friendly_type; ?></span>
                                </td>
                                <td style="color:#fff; font-weight:500;"><?php echo htmlspecialchars($act['subject']); ?></td>
                                <td>
                                    <?php if ($act['deal_title']): ?>
                                        <i data-lucide="sparkles" style="width:12px; height:12px; display:inline-block; vertical-align:middle; margin-right:4px; color:var(--color-primary);"></i>
                                        <?php echo htmlspecialchars($act['deal_title']); ?>
                                    <?php else: ?>
                                        <span style="color:var(--text-dark);">Seguimiento General</span>
                                    <?php endif; ?>
                                </td>
                                <td style="<?php echo $due_color; ?>">
                                    <?php echo date('d M Y, h:i a', strtotime($act['due_date'])); ?>
                                    <?php if ($is_overdue): ?> <span style="font-size:0.75rem; text-transform:uppercase; background:rgba(239,68,68,0.15); padding:1px 4px; border-radius:4px; margin-left:4px;">Atrasado</span> <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($act['description']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr id="no-pending-row">
                            <td colspan="6" style="text-align: center; padding: 2rem;">🎉 ¡Excelente! No tienes actividades pendientes para hoy.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- PANEL DE ACTIVIDADES COMPLETADAS -->
    <div class="card-section" style="margin-bottom:0;">
        <div class="section-header">
            <h2>Historial de Actividades Completadas (Últimas 20)</h2>
        </div>
        
        <div class="custom-table-container">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Asunto / Actividad</th>
                        <th>Oportunidad Relacionada</th>
                        <th>Fecha Límite</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody id="completed-tasks-body">
                    <?php if (count($completed_activities) > 0): ?>
                        <?php foreach ($completed_activities as $act): 
                            $icon = 'phone';
                            $friendly_type = 'Llamada';
                            if ($act['type'] == 'Email') { $icon = 'mail'; $friendly_type = 'Correo'; }
                            elseif ($act['type'] == 'Meeting') { $icon = 'users'; $friendly_type = 'Reunión'; }
                            elseif ($act['type'] == 'Task') { $icon = 'check-square'; $friendly_type = 'Tarea'; }
                            elseif ($act['type'] == 'Technical_Visit') { $icon = 'wrench'; $friendly_type = 'Visita Técnica'; }
                            elseif ($act['type'] == 'Demo') { $icon = 'play'; $friendly_type = 'Demostración'; }
                            elseif ($act['type'] == 'Samples') { $icon = 'package'; $friendly_type = 'Muestras'; }
                            ?>
                            <tr>
                                <td>
                                    <span class="act-icon act-<?php echo $act['type']; ?>" title="<?php echo $friendly_type; ?>">
                                        <i data-lucide="<?php echo $icon; ?>" style="width:14px; height:14px;"></i>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($act['subject']); ?></td>
                                <td><?php echo $act['deal_title'] ? htmlspecialchars($act['deal_title']) : 'General'; ?></td>
                                <td><?php echo date('d M Y, h:i a', strtotime($act['due_date'])); ?></td>
                                <td><span class="badge badge-completed">Completado</span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr id="no-completed-row">
                            <td colspan="5" style="text-align: center; padding: 2rem;">No hay actividades completadas todavía en el registro.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- VISTA 2: CALENDARIO -->
<div id="calendar-view-container" class="card-section" style="display: none; padding: 1.5rem; margin-bottom: 0;">
    <div id="calendar"></div>
</div>

<!-- Sección de Sincronización Google Calendar -->
<div class="card-section" style="margin-top: 2rem; background: linear-gradient(135deg, rgba(59, 130, 246, 0.05), rgba(6, 182, 212, 0.05)); border: 1px solid rgba(59, 130, 246, 0.15); padding: 1.5rem; border-radius: 10px; margin-bottom: 0;">
    <div style="display: flex; gap: 1.5rem; align-items: center;">
        <div style="background: rgba(59, 130, 246, 0.15); width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #3b82f6; flex-shrink: 0;">
            <i data-lucide="refresh-cw" style="width: 24px; height: 24px;"></i>
        </div>
        <div style="flex-grow: 1;">
            <h3 style="color: #fff; font-size: 1rem; margin-bottom: 0.25rem;">Sincronizar con Google Calendar (iCal)</h3>
            <p style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 0.75rem;">
                Suscríbete a tu feed de actividades en Google Calendar, Outlook o Apple Calendar para visualizar todas tus tareas pendientes del CRM automáticamente.
            </p>
            <div style="display: flex; gap: 0.5rem; max-width: 600px;">
                <input type="text" id="ical-feed-url" readonly value="<?php 
                    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
                    $host = $_SERVER['HTTP_HOST'];
                    $expected_key = md5("tips_crm_key_salt_2026");
                    echo $protocol . $host . dirname($_SERVER['SCRIPT_NAME']) . "/ical.php?key=" . $expected_key;
                ?>" style="flex-grow: 1; padding: 0.5rem; font-size: 0.8rem; background: var(--bg-secondary); border: 1px solid var(--border-color); color: #cbd5e1; border-radius: 6px; outline: none;" onclick="this.select();">
                <button class="btn-action" onclick="copyIcalUrl()" style="white-space: nowrap; padding: 0.5rem 1rem; background: var(--color-primary); cursor: pointer;">
                    <i data-lucide="copy" style="width: 14px; height: 14px; display: inline-block; vertical-align: middle; margin-right: 4px;"></i> Copiar Enlace
                </button>
            </div>
            <small style="display: block; font-size: 0.75rem; color: var(--text-dark); margin-top: 0.5rem;">
                💡 <strong>¿Cómo agregar a Google Calendar?</strong> Abre Google Calendar → En el menú lateral haz clic en el botón <strong>"+"</strong> junto a <em>"Otros calendarios"</em> → Selecciona <strong>"Desde URL"</strong> y pega este enlace.
            </small>
        </div>
    </div>
</div>

<!-- Modal: Nueva Actividad -->
<div class="modal-overlay" id="activity-modal">
    <div class="modal-card">
        <div class="modal-title">
            <span>Programar Actividad</span>
            <button class="modal-close" onclick="closeActivityModal()">&times;</button>
        </div>
        <form action="api.php?action=create_activity" method="POST">
            <input type="hidden" name="redirect_uri" value="activities.php">
            
            <div class="form-group">
                <label for="act_deal">Oportunidad Relacionada</label>
                <select id="act_deal" name="deal_id">
                    <option value="">-- Seguimiento General (Ninguno) --</option>
                    <?php foreach ($open_deals as $d): ?>
                        <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['title']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="grid-form" style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
                <div class="form-group">
                    <label for="act_type">Tipo de Tarea *</label>
                    <select id="act_type" name="type" required>
                        <option value="Call">📞 Llamada</option>
                        <option value="Email">📧 Correo Electrónico</option>
                        <option value="Meeting">🤝 Reunión Presencial/Virtual</option>
                        <option value="Task">✔️ Tarea / Pendiente</option>
                        <option value="Technical_Visit">🔧 Visita Técnica (Revisión de Cocina/Hornos)</option>
                        <option value="Demo">🍽️ Demostración de Producto (KitchenAid/Enco)</option>
                        <option value="Samples">📦 Entrega de Muestras Comerciales</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="act_due">Fecha y Hora Límite *</label>
                    <input type="datetime-local" id="act_due" name="due_date" required value="<?php echo date('Y-m-d\HT:i', strtotime('+1 day 09:00:00')); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="act_subject">Asunto / Acción *</label>
                <input type="text" id="act_subject" name="subject" placeholder="Ej. Llamar a confirmar existencias de batidora KitchenAid" required>
            </div>
            
            <div class="form-group">
                <label for="act_desc">Descripción / Notas adicionales</label>
                <textarea id="act_desc" name="description" rows="3" placeholder="Detalles de lo conversado o notas de preparación..."></textarea>
            </div>
            
            <button type="submit" class="btn-submit">Programar Actividad</button>
        </form>
    </div>
</div>

<script>
    const activityModal = document.getElementById('activity-modal');

    function openActivityModal() {
        activityModal.style.display = 'flex';
        setTimeout(() => activityModal.classList.add('active'), 10);
    }
    
    function closeActivityModal() {
        activityModal.classList.remove('active');
        setTimeout(() => activityModal.style.display = 'none', 250);
    }

    window.addEventListener('click', (e) => {
        if (e.target === activityModal) closeActivityModal();
    });

    // AJAX para marcar actividad como completada
    function completeActivity(activityId) {
        const row = document.getElementById('activity-row-' + activityId);
        if (row) {
            row.style.transform = 'translateX(20px)';
            row.style.opacity = '0';
            row.style.transition = '0.3s ease';
            
            setTimeout(() => {
                row.remove();
                
                // Si no quedan tareas pendientes, mostrar mensaje de vacío
                const pendingBody = document.getElementById('pending-tasks-body');
                if (pendingBody && pendingBody.children.length === 0) {
                    pendingBody.innerHTML = `<tr id="no-pending-row"><td colspan="6" style="text-align: center; padding: 2rem;">🎉 ¡Excelente! No tienes actividades pendientes para hoy.</td></tr>`;
                }
            }, 300);
        }

        fetch('api.php?action=complete_activity', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                activity_id: activityId,
                status: 'Completed'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                setTimeout(() => location.reload(), 350);
            } else {
                alert("Error al completar la actividad: " + data.error);
                location.reload();
            }
        })
        .catch(err => {
            console.error(err);
            location.reload();
        });
    }

    // Inicialización de FullCalendar y Switcher
    let calendar = null;

    document.addEventListener('DOMContentLoaded', function() {
        const calendarEl = document.getElementById('calendar');
        const rawEvents = <?php echo json_encode($events); ?>;
        
        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'es',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
            },
            buttonText: {
                today: 'Hoy',
                month: 'Mes',
                week: 'Semana',
                day: 'Día',
                list: 'Agenda'
            },
            events: rawEvents,
            dateClick: function(info) {
                document.getElementById('act_due').value = info.dateStr + 'T09:00';
                openActivityModal();
            },
            eventClick: function(info) {
                if (info.event.url) {
                    info.jsEvent.preventDefault();
                    window.location.href = info.event.url;
                } else {
                    alert(info.event.title + "\n\nNotas: " + (info.event.extendedProps.description || 'Sin notas adicionales.') + "\nRelacionado con: " + info.event.extendedProps.deal_title);
                    info.jsEvent.preventDefault();
                }
            }
        });
        
        // Cargar vista guardada en LocalStorage
        const savedView = localStorage.getItem('activities_view') || 'list';
        switchView(savedView);
    });

    function switchView(view) {
        const listView = document.getElementById('list-view-container');
        const calendarView = document.getElementById('calendar-view-container');
        const btnList = document.getElementById('btn-view-list');
        const btnCalendar = document.getElementById('btn-view-calendar');
        
        if (view === 'calendar') {
            listView.style.display = 'none';
            calendarView.style.display = 'block';
            btnList.classList.add('btn-secondary');
            btnCalendar.classList.remove('btn-secondary');
            btnCalendar.style.background = 'var(--color-primary)';
            btnList.style.background = 'none';
            
            if (calendar) {
                setTimeout(() => calendar.render(), 10);
            }
        } else {
            listView.style.display = 'grid';
            calendarView.style.display = 'none';
            btnCalendar.classList.add('btn-secondary');
            btnList.classList.remove('btn-secondary');
            btnList.style.background = 'var(--color-primary)';
            btnCalendar.style.background = 'none';
        }
        
        localStorage.setItem('activities_view', view);
        
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }

    function copyIcalUrl() {
        const input = document.getElementById('ical-feed-url');
        input.select();
        document.execCommand('copy');
        alert("Enlace de suscripción iCal copiado al portapapeles. Ya puedes pegarlo en Google Calendar.");
    }
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>

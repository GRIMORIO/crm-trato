<?php
/**
 * api.php — API / Backend Controller para TIPS CRM
 * Gestiona todas las acciones de creación, actualización y sincronización.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_login(true);

header('Content-Type: application/json');

$action = isset($_GET['action']) ? $_GET['action'] : '';

function check_deal_permission(PDO $pdo, $deal_id) {
    $restriction = get_visibility_restriction();
    if ($restriction !== '') {
        $stmt = $pdo->prepare("SELECT `assigned_agent` FROM `deals` WHERE `id` = ?");
        $stmt->execute([$deal_id]);
        $agent = $stmt->fetchColumn();
        if ($agent && $agent !== $restriction) {
            throw new Exception("Acceso denegado: no tienes permisos sobre esta oportunidad comercial.");
        }
    }
}

/**
 * Registra la entrada de un negocio a una fase en `deal_stage_history`.
 * No hace nada si la última fila del historial ya es esa misma fase (evita duplicados
 * al guardar un negocio sin mover su etapa). Tolerante a que la tabla no exista todavía
 * (producción antes de correr update_deal_stage_history.php).
 */
function record_stage_change(PDO $pdo, $deal_id, $stage_id) {
    $deal_id = (int) $deal_id;
    $stage_id = (int) $stage_id;
    if (!$deal_id || !$stage_id) return;
    try {
        $last = $pdo->prepare("SELECT `stage_id` FROM `deal_stage_history` WHERE `deal_id` = ? ORDER BY `id` DESC LIMIT 1");
        $last->execute([$deal_id]);
        $prev = $last->fetchColumn();
        if ($prev !== false && (int) $prev === $stage_id) return;

        $by = function_exists('current_user_name') ? current_user_name() : null;
        $ins = $pdo->prepare("INSERT INTO `deal_stage_history` (`deal_id`, `stage_id`, `changed_by`) VALUES (?, ?, ?)");
        $ins->execute([$deal_id, $stage_id, $by]);
    } catch (Exception $e) {
        error_log("record_stage_change: " . $e->getMessage());
    }
}

/**
 * Sella (o limpia) `deals.closed_at` según el estado del negocio. Tolerante a que la
 * columna no exista todavía.
 */
function stamp_deal_closed_at(PDO $pdo, $deal_id, $status) {
    $deal_id = (int) $deal_id;
    if (!$deal_id) return;
    try {
        if ($status === 'Won' || $status === 'Lost') {
            $pdo->prepare("UPDATE `deals` SET `closed_at` = COALESCE(`closed_at`, NOW()) WHERE `id` = ?")->execute([$deal_id]);
        } else {
            $pdo->prepare("UPDATE `deals` SET `closed_at` = NULL WHERE `id` = ?")->execute([$deal_id]);
        }
    } catch (Exception $e) {
        error_log("stamp_deal_closed_at: " . $e->getMessage());
    }
}

// URL base del sitio (https://crmtrato.com en prod; host actual en local).
function crm_build_base_url() {
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    if ($host === 'localhost' || $host === '127.0.0.1' || strpos($host, ':') !== false) {
        return 'http://' . $host . rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/');
    }
    return 'https://crmtrato.com';
}

// Genera un token de 48 h y envía el correo de invitación / definición de contraseña.
function crm_send_invite(PDO $pdo, $username, $email, $full_name) {
    require_once __DIR__ . '/includes/smtp_mailer.php';

    $token = bin2hex(random_bytes(32));
    $pdo->prepare("DELETE FROM crm_password_resets WHERE username = ?")->execute([$username]);
    $pdo->prepare("INSERT INTO crm_password_resets (username, token, expires_at) VALUES (?, ?, ?)")
        ->execute([$username, $token, date('Y-m-d H:i:s', strtotime('+48 hours'))]);

    $base = crm_build_base_url();
    $link = $base . '/recover_password.php?token=' . $token;

    $subject = 'Acceso al CRM Trato — define tu contraseña';
    $body = "Hola" . ($full_name ? ' ' . $full_name : '') . ",\n\n"
          . "Se creó tu cuenta en el CRM Trato.\n\n"
          . "Usuario: {$username}\n\n"
          . "Definí tu contraseña con este enlace (válido 48 horas):\n{$link}\n\n"
          . "Luego iniciá sesión en: {$base}/login.php  (con el usuario, no el correo)\n\n"
          . "Saludos,\nCRM Trato";

    $err = '';
    $sent = smtp_is_configured() && smtp_send_mail($email, $subject, $body, $err);
    if (!$sent && !$err) $err = 'SMTP no está configurado.';
    return ['sent' => $sent, 'link' => $link, 'error' => $err];
}

// Capturar payloads JSON en peticiones Fetch
$input_raw = file_get_contents('php://input');
$input_data = json_decode($input_raw, true) ?: [];

try {
    switch ($action) {
        
        // ── OPORTUNIDADES (DEALS) ────────────────────────────────────────────────
        case 'create_deal':
            // Recibe POST estándar de formulario
            $title = isset($_POST['title']) ? trim($_POST['title']) : '';
            $value = isset($_POST['value']) ? floatval($_POST['value']) : 0.00;
            $stage_id = isset($_POST['stage_id']) ? intval($_POST['stage_id']) : 1;
            $account_id = !empty($_POST['account_id']) ? intval($_POST['account_id']) : null;
            $contact_id = !empty($_POST['contact_id']) ? intval($_POST['contact_id']) : null;
            $close_date = !empty($_POST['close_date']) ? $_POST['close_date'] : null;
            
            if (empty($title)) {
                throw new Exception("El título de la oportunidad es obligatorio.");
            }
            
            $assigned_agent = 'Andrés Herrera (GAM Norte)';
            if ($account_id) {
                $stmt_agent = $pdo->prepare("SELECT assigned_agent FROM accounts WHERE id = ?");
                $stmt_agent->execute([$account_id]);
                $assigned_agent = $stmt_agent->fetchColumn() ?: 'Andrés Herrera (GAM Norte)';
            }
            
            $stmt = $pdo->prepare("INSERT INTO `deals` (`title`, `value`, `stage_id`, `account_id`, `contact_id`, `status`, `close_date`, `assigned_agent`) VALUES (?, ?, ?, ?, ?, 'Open', ?, ?)");
            $stmt->execute([$title, $value, $stage_id, $account_id, $contact_id, $close_date, $assigned_agent]);

            // Registrar la fase inicial en el historial del embudo
            record_stage_change($pdo, $pdo->lastInsertId(), $stage_id);

            // Redirigir a la página de origen si existe, sino al pipeline
            $redirect_uri = isset($_POST['redirect_uri']) ? $_POST['redirect_uri'] : 'pipeline.php';
            header("Location: " . $redirect_uri);
            exit;
            
        case 'update_deal_stage':
            // Recibe JSON AJAX: { deal_id: X, stage_id: Y }
            $deal_id = isset($input_data['deal_id']) ? intval($input_data['deal_id']) : 0;
            $stage_id = isset($input_data['stage_id']) ? intval($input_data['stage_id']) : 0;
            
            if (!$deal_id || !$stage_id) {
                throw new Exception("Parámetros incorrectos.");
            }
            
            check_deal_permission($pdo, $deal_id);
            
            $stmt = $pdo->prepare("UPDATE `deals` SET `stage_id` = ? WHERE `id` = ?");
            $stmt->execute([$stage_id, $deal_id]);

            // Registrar el cambio de fase en el historial del embudo
            record_stage_change($pdo, $deal_id, $stage_id);

            // --- EJECUTAR MOTOR DE AUTOMATIZACIONES ---
            try {
                // Obtener datos del trato para conocer el asesor asignado
                $stmt_deal = $pdo->prepare("SELECT `title`, `assigned_agent` FROM `deals` WHERE `id` = ?");
                $stmt_deal->execute([$deal_id]);
                $deal_info = $stmt_deal->fetch(PDO::FETCH_ASSOC);
                
                if ($deal_info) {
                    $deal_title = $deal_info['title'];
                    $assigned_agent = $deal_info['assigned_agent'] ?: 'Andrés Herrera (GAM Norte)';
                    
                    // Buscar automatizaciones activas para el cambio de etapa a $stage_id
                    $stmt_auto = $pdo->prepare("SELECT * FROM `crm_automations` WHERE `trigger_event` = 'stage_change' AND (`trigger_value` = ? OR `trigger_value` = '*') AND `is_active` = 1");
                    $stmt_auto->execute([$stage_id]);
                    $active_rules = $stmt_auto->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach ($active_rules as $rule) {
                        $payload = json_decode($rule['action_payload'], true);
                        if ($rule['action_type'] === 'create_task' && $payload) {
                            $task_type = $payload['type'] ?: 'Task';
                            $due_in_days = intval($payload['due_in_days'] ?: 0);
                            $task_title = $payload['title'] ?: 'Tarea automática';
                            
                            // Calcular fecha de vencimiento
                            $due_date = date('Y-m-d H:i:s', strtotime("+$due_in_days days"));
                            
                            // Insertar actividad
                            $stmt_act = $pdo->prepare("INSERT INTO `activities` (`deal_id`, `type`, `subject`, `due_date`, `status`, `description`) VALUES (?, ?, ?, ?, 'Pending', ?)");
                            $stmt_act->execute([
                                $deal_id,
                                $task_type,
                                $task_title,
                                $due_date,
                                "Creado automáticamente por la regla: " . $rule['title']
                            ]);
                        }
                    }
                }
            } catch (Exception $auto_ex) {
                // Loguear error pero no interrumpir la actualización de la etapa
                error_log("Error ejecutando automatización: " . $auto_ex->getMessage());
            }
            
            echo json_encode(['success' => true, 'message' => 'Etapa de oportunidad actualizada.']);
            exit;

        case 'change_deal_status':
            // Recibe JSON AJAX: { deal_id: X, status: 'Won'|'Lost'|'Open' }
            $deal_id = isset($input_data['deal_id']) ? intval($input_data['deal_id']) : 0;
            $status = isset($input_data['status']) ? $input_data['status'] : '';
            
            if (!$deal_id || !in_array($status, ['Open', 'Won', 'Lost'])) {
                throw new Exception("Estado o ID de oportunidad inválido.");
            }
            
            check_deal_permission($pdo, $deal_id);
            
            $stmt = $pdo->prepare("UPDATE `deals` SET `status` = ? WHERE `id` = ?");
            $stmt->execute([$status, $deal_id]);

            // Sellar / limpiar la fecha de conversión para el timeline del embudo
            stamp_deal_closed_at($pdo, $deal_id, $status);

            echo json_encode(['success' => true, 'message' => "El estado del deal ha cambiado a $status."]);
            exit;

        // ── CUENTAS Y CONTACTOS (B2B) ────────────────────────────────────────────
        case 'create_account':
            $name = isset($_POST['name']) ? trim($_POST['name']) : '';
            $industry = isset($_POST['industry']) ? trim($_POST['industry']) : '';
            $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
            $email = isset($_POST['email']) ? trim($_POST['email']) : '';
            $address = isset($_POST['address']) ? trim($_POST['address']) : '';
            $city = isset($_POST['city']) ? trim($_POST['city']) : '';
            
            $assigned_agent = 'Andrés Herrera (GAM Norte)';
            if (preg_match('/Guanacaste|Puntarenas|Jacó|Garabito|Quepos|Liberia/i', $city) || preg_match('/Guanacaste|Puntarenas|Jacó|Garabito|Quepos|Liberia/i', $address)) {
                $assigned_agent = 'Carlos Mendoza (Zona Costa)';
            } elseif (preg_match('/San José|Cartago|Escazú|Santa Ana|Curridabat|Sabanilla|Pedro/i', $city) || preg_match('/San José|Cartago|Escazú|Santa Ana|Curridabat|Sabanilla|Pedro/i', $address)) {
                $assigned_agent = 'Sofía Castro (GAM Oriente)';
            }
            
            $stmt = $pdo->prepare("INSERT INTO `accounts` (`name`, `industry`, `phone`, `email`, `address`, `city`, `assigned_agent`) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $industry, $phone, $email, $address, $city, $assigned_agent]);
            
            header("Location: accounts.php");
            exit;

        case 'create_contact':
            $account_id = !empty($_POST['account_id']) ? intval($_POST['account_id']) : null;
            $first_name = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
            $last_name = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
            $job_title = isset($_POST['job_title']) ? trim($_POST['job_title']) : '';
            $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
            $email = isset($_POST['email']) ? trim($_POST['email']) : '';
            
            if (empty($first_name) || empty($last_name)) {
                throw new Exception("El nombre y el apellido son obligatorios.");
            }
            
            $stmt = $pdo->prepare("INSERT INTO `contacts` (`account_id`, `first_name`, `last_name`, `job_title`, `phone`, `email`) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$account_id, $first_name, $last_name, $job_title, $phone, $email]);
            
            header("Location: accounts.php");
            exit;

        // ── ACTIVIDADES ──────────────────────────────────────────────────────────
        case 'create_activity':
            $deal_id = !empty($_POST['deal_id']) ? intval($_POST['deal_id']) : null;
            $type = isset($_POST['type']) ? $_POST['type'] : 'Call';
            $subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
            $description = isset($_POST['description']) ? trim($_POST['description']) : '';
            $due_date = isset($_POST['due_date']) ? $_POST['due_date'] : '';
            
            if (empty($subject) || empty($due_date)) {
                throw new Exception("El asunto y la fecha límite son obligatorios.");
            }
            
            $stmt = $pdo->prepare("INSERT INTO `activities` (`deal_id`, `type`, `subject`, `description`, `due_date`, `status`) VALUES (?, ?, ?, ?, ?, 'Pending')");
            $stmt->execute([$deal_id, $type, $subject, $description, $due_date]);
            
            $redirect_uri = isset($_POST['redirect_uri']) ? $_POST['redirect_uri'] : 'activities.php';
            header("Location: " . $redirect_uri);
            exit;

        case 'complete_activity':
            $activity_id = isset($input_data['activity_id']) ? intval($input_data['activity_id']) : 0;
            $status = isset($input_data['status']) ? $input_data['status'] : 'Completed';
            
            if (!$activity_id) {
                throw new Exception("ID de actividad incorrecto.");
            }
            
            $stmt = $pdo->prepare("UPDATE `activities` SET `status` = ? WHERE `id` = ?");
            $stmt->execute([$status, $activity_id]);
            
            echo json_encode(['success' => true, 'message' => 'Actividad marcada como completada.']);
            exit;


        case 'import_prospect':
            $name = isset($input_data['name']) ? trim($input_data['name']) : '';
            $address = isset($input_data['address']) ? trim($input_data['address']) : '';
            $phone = isset($input_data['phone']) ? trim($input_data['phone']) : '';
            $email = isset($input_data['email']) ? trim($input_data['email']) : '';
            $city = isset($input_data['city']) ? trim($input_data['city']) : 'San José';
            $industry = isset($input_data['industry']) ? trim($input_data['industry']) : 'Pastelería';
            
            if (empty($name)) {
                throw new Exception("El nombre del prospecto es obligatorio.");
            }
            
            $assigned_agent = 'Andrés Herrera (GAM Norte)';
            if (preg_match('/Guanacaste|Puntarenas|Jacó|Garabito|Quepos|Liberia/i', $city) || preg_match('/Guanacaste|Puntarenas|Jacó|Garabito|Quepos|Liberia/i', $address)) {
                $assigned_agent = 'Carlos Mendoza (Zona Costa)';
            } elseif (preg_match('/San José|Cartago|Escazú|Santa Ana|Curridabat|Sabanilla|Pedro/i', $city) || preg_match('/San José|Cartago|Escazú|Santa Ana|Curridabat|Sabanilla|Pedro/i', $address)) {
                $assigned_agent = 'Sofía Castro (GAM Oriente)';
            }
            
            // Insertar Cuenta B2B
            $stmt = $pdo->prepare("INSERT INTO `accounts` (`name`, `industry`, `phone`, `email`, `address`, `city`, `assigned_agent`) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $industry, $phone, $email, $address, $city, $assigned_agent]);
            $account_id = $pdo->lastInsertId();
            
            // Crear contacto decisor por defecto
            $stmt = $pdo->prepare("INSERT INTO `contacts` (`account_id`, `first_name`, `last_name`, `job_title`, `phone`, `email`) VALUES (?, 'Encargado', 'de Compras', 'Comprador B2B', ?, ?)");
            $stmt->execute([$account_id, $phone, $email]);
            
            echo json_encode(['success' => true, 'message' => "Prospecto $name importado al CRM con éxito."]);
            exit;

        case 'toggle_deal_bant':
            $deal_id = isset($input_data['deal_id']) ? intval($input_data['deal_id']) : 0;
            $criteria = isset($input_data['criteria']) ? trim($input_data['criteria']) : '';
            
            // Validar que el criterio sea uno de los 4 de BANT
            $valid_criteria = ['budget', 'authority', 'need', 'timeline'];
            if (!$deal_id || !in_array($criteria, $valid_criteria)) {
                throw new Exception("Parámetros incorrectos o criterio BANT inválido.");
            }
            
            check_deal_permission($pdo, $deal_id);
            
            $column_name = "bant_" . $criteria;
            
            // Obtener estado actual
            $stmt = $pdo->prepare("SELECT `$column_name` FROM `deals` WHERE `id` = ?");
            $stmt->execute([$deal_id]);
            $current_state = $stmt->fetchColumn();
            
            // Alternar (0 -> 1, 1 -> 0)
            $new_state = ($current_state == 1) ? 0 : 1;
            
            $stmt_upd = $pdo->prepare("UPDATE `deals` SET `$column_name` = ? WHERE `id` = ?");
            $stmt_upd->execute([$new_state, $deal_id]);
            
            echo json_encode(['success' => true, 'new_state' => $new_state]);
            exit;

        case 'toggle_deal_meddic':
            $deal_id = isset($input_data['deal_id']) ? intval($input_data['deal_id']) : 0;
            $criteria = isset($input_data['criteria']) ? trim($input_data['criteria']) : '';
            
            $valid_criteria = ['metrics', 'buyer', 'criteria', 'process', 'pain', 'champion'];
            if (!$deal_id || !in_array($criteria, $valid_criteria)) {
                throw new Exception("Parámetros incorrectos o criterio MEDDIC inválido.");
            }
            
            check_deal_permission($pdo, $deal_id);
            
            $column_name = "meddic_" . $criteria;
            
            $stmt = $pdo->prepare("SELECT `$column_name` FROM `deals` WHERE `id` = ?");
            $stmt->execute([$deal_id]);
            $current_state = $stmt->fetchColumn();
            
            $new_state = ($current_state == 1) ? 0 : 1;
            
            $stmt_upd = $pdo->prepare("UPDATE `deals` SET `$column_name` = ? WHERE `id` = ?");
            $stmt_upd->execute([$new_state, $deal_id]);
            
            echo json_encode(['success' => true, 'new_state' => $new_state]);
            exit;

        case 'create_stage':
            $name = isset($_POST['name']) ? trim($_POST['name']) : '';
            $pipeline_id = isset($_POST['pipeline_id']) ? intval($_POST['pipeline_id']) : 1;
            if (empty($name)) {
                throw new Exception("El nombre de la etapa es obligatorio.");
            }

            // Posición de inserción según el selector del modal:
            //   after_stage_id ausente        -> al final (comportamiento histórico)
            //   after_stage_id = 0            -> al inicio del embudo
            //   after_stage_id = <id etapa>   -> justo después de esa etapa
            $new_pos = null;
            if (array_key_exists('after_stage_id', $_POST)) {
                $after_stage_id = intval($_POST['after_stage_id']);
                if ($after_stage_id > 0) {
                    $pos_stmt = $pdo->prepare("SELECT position FROM stages WHERE id = ? AND pipeline_id = ?");
                    $pos_stmt->execute([$after_stage_id, $pipeline_id]);
                    $after_pos = $pos_stmt->fetchColumn();
                    if ($after_pos !== false) {
                        $new_pos = (int) $after_pos + 1;
                    }
                } else {
                    $new_pos = 1;
                }
            }

            if ($new_pos === null) {
                // Al final
                $max_pos_stmt = $pdo->prepare("SELECT MAX(position) FROM stages WHERE pipeline_id = ?");
                $max_pos_stmt->execute([$pipeline_id]);
                $new_pos = ((int) $max_pos_stmt->fetchColumn() ?: 0) + 1;
            } else {
                // Hacer hueco: desplazar +1 todas las etapas desde la posición de inserción
                $shift_stmt = $pdo->prepare("UPDATE stages SET position = position + 1 WHERE pipeline_id = ? AND position >= ?");
                $shift_stmt->execute([$pipeline_id, $new_pos]);
            }

            $stmt = $pdo->prepare("INSERT INTO `stages` (`name`, `position`, `pipeline_id`) VALUES (?, ?, ?)");
            $stmt->execute([$name, $new_pos, $pipeline_id]);

            header("Location: pipeline.php?pipeline_id=" . $pipeline_id);
            exit;

        case 'move_stage':
            // Reordenar una etapa intercambiando su posición con la vecina.
            // JSON AJAX: { stage_id: X, direction: 'left' | 'right' }
            $stage_id = isset($input_data['stage_id']) ? intval($input_data['stage_id']) : 0;
            $direction = isset($input_data['direction']) ? $input_data['direction'] : '';
            if (!$stage_id || !in_array($direction, ['left', 'right'], true)) {
                throw new Exception("Parámetros inválidos para reordenar la etapa.");
            }

            $cur_stmt = $pdo->prepare("SELECT position, pipeline_id FROM stages WHERE id = ?");
            $cur_stmt->execute([$stage_id]);
            $cur = $cur_stmt->fetch(PDO::FETCH_ASSOC);
            if (!$cur) {
                throw new Exception("Etapa no encontrada.");
            }

            $op  = ($direction === 'left') ? '<' : '>';
            $ord = ($direction === 'left') ? 'DESC' : 'ASC';
            $nb_stmt = $pdo->prepare("SELECT id, position FROM stages WHERE pipeline_id = ? AND position $op ? ORDER BY position $ord LIMIT 1");
            $nb_stmt->execute([$cur['pipeline_id'], $cur['position']]);
            $neighbor = $nb_stmt->fetch(PDO::FETCH_ASSOC);

            if (!$neighbor) {
                echo json_encode(['success' => true, 'noop' => true, 'message' => 'La etapa ya está en el extremo.']);
                exit;
            }

            $pdo->beginTransaction();
            $swap = $pdo->prepare("UPDATE stages SET position = ? WHERE id = ?");
            $swap->execute([$neighbor['position'], $stage_id]);
            $swap->execute([$cur['position'], $neighbor['id']]);
            $pdo->commit();

            echo json_encode(['success' => true, 'message' => 'Etapa reordenada.']);
            exit;

        case 'create_pipeline':
            $name = isset($_POST['name']) ? trim($_POST['name']) : '';
            if (empty($name)) {
                throw new Exception("El nombre del embudo es obligatorio.");
            }
            
            $stmt = $pdo->prepare("INSERT INTO `pipelines` (`name`) VALUES (?)");
            $stmt->execute([$name]);
            $new_id = $pdo->lastInsertId();
            
            // Crear etapa inicial por defecto en el nuevo embudo
            $stmt_stage = $pdo->prepare("INSERT INTO `stages` (`name`, `position`, `pipeline_id`) VALUES (?, 1, ?)");
            $stmt_stage->execute(['Contacto Inicial', $new_id]);
            
            header("Location: pipeline.php?pipeline_id=" . $new_id);
            exit;

        case 'delete_pipeline':
            $pipeline_id = isset($input_data['pipeline_id']) ? intval($input_data['pipeline_id']) : 0;
            if (!$pipeline_id) {
                throw new Exception("ID de embudo obligatorio.");
            }

            // No permitir eliminar el último embudo
            $total_pipelines = (int) $pdo->query("SELECT COUNT(*) FROM pipelines")->fetchColumn();
            if ($total_pipelines <= 1) {
                throw new Exception("No se puede eliminar el único embudo. Crea otro embudo antes de eliminar este.");
            }

            // Bloquear si hay oportunidades en alguna etapa de este embudo
            $deal_check = $pdo->prepare("SELECT COUNT(*) FROM deals d JOIN stages s ON d.stage_id = s.id WHERE s.pipeline_id = ?");
            $deal_check->execute([$pipeline_id]);
            if ($deal_check->fetchColumn() > 0) {
                throw new Exception("No se puede eliminar el embudo porque contiene oportunidades. Muévelas o elimínalas antes de continuar.");
            }

            // Las etapas se borran en cascada (stages.pipeline_id ON DELETE CASCADE)
            $stmt_del = $pdo->prepare("DELETE FROM pipelines WHERE id = ?");
            $stmt_del->execute([$pipeline_id]);

            $next_id = (int) $pdo->query("SELECT id FROM pipelines ORDER BY id LIMIT 1")->fetchColumn();
            echo json_encode([
                'success' => true,
                'message' => 'Embudo eliminado con éxito.',
                'redirect' => 'pipeline.php?pipeline_id=' . $next_id
            ]);
            exit;

        case 'delete_stage':
            $stage_id = isset($input_data['stage_id']) ? intval($input_data['stage_id']) : (isset($_GET['stage_id']) ? intval($_GET['stage_id']) : 0);
            if (!$stage_id) {
                throw new Exception("ID de etapa obligatorio.");
            }
            
            // Verificar si hay oportunidades (deals) activas en esta etapa
            $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM deals WHERE stage_id = ?");
            $stmt_count->execute([$stage_id]);
            if ($stmt_count->fetchColumn() > 0) {
                throw new Exception("No se puede eliminar la etapa porque contiene tratos activos. Mueve o elimina las oportunidades antes de continuar.");
            }
            
            $stmt_del = $pdo->prepare("DELETE FROM stages WHERE id = ?");
            $stmt_del->execute([$stage_id]);
            
            echo json_encode(['success' => true, 'message' => 'Etapa eliminada con éxito.']);
            exit;

        case 'update_deal':
            $deal_id = isset($input_data['deal_id']) ? intval($input_data['deal_id']) : 0;
            $title = isset($input_data['title']) ? trim($input_data['title']) : '';
            $value = isset($input_data['value']) ? floatval($input_data['value']) : 0.00;
            $stage_id = isset($input_data['stage_id']) ? intval($input_data['stage_id']) : 0;
            $account_id = !empty($input_data['account_id']) ? intval($input_data['account_id']) : null;
            $contact_id = !empty($input_data['contact_id']) ? intval($input_data['contact_id']) : null;
            $close_date = !empty($input_data['close_date']) ? $input_data['close_date'] : null;
            $assigned_agent = isset($input_data['assigned_agent']) ? trim($input_data['assigned_agent']) : 'Andrés Herrera (GAM Norte)';

            if (!$deal_id) {
                throw new Exception("ID de trato obligatorio.");
            }
            if (empty($title)) {
                throw new Exception("El título de la oportunidad es obligatorio.");
            }
            if (!$stage_id) {
                throw new Exception("La etapa es obligatoria.");
            }
            
            check_deal_permission($pdo, $deal_id);

            $stmt = $pdo->prepare("UPDATE `deals` SET `title` = ?, `value` = ?, `stage_id` = ?, `account_id` = ?, `contact_id` = ?, `close_date` = ?, `assigned_agent` = ?, `updated_at` = CURRENT_TIMESTAMP WHERE `id` = ?");
            $stmt->execute([$title, $value, $stage_id, $account_id, $contact_id, $close_date, $assigned_agent, $deal_id]);

            // Registrar el cambio de fase si la etapa cambió (record_stage_change deduplica)
            record_stage_change($pdo, $deal_id, $stage_id);

            // Guardar valores de campos personalizados
            if (isset($input_data['custom_fields']) && is_array($input_data['custom_fields'])) {
                $stmt_custom = $pdo->prepare("INSERT INTO `deal_custom_values` (`deal_id`, `field_id`, `value`) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
                foreach ($input_data['custom_fields'] as $field_id => $val) {
                    $stmt_custom->execute([$deal_id, intval($field_id), trim($val)]);
                }
            }

            echo json_encode(['success' => true, 'message' => 'Oportunidad actualizada con éxito.']);
            exit;

        case 'get_deal_details':
            $deal_id = isset($_GET['deal_id']) ? intval($_GET['deal_id']) : 0;
            if (!$deal_id) {
                throw new Exception("ID de trato obligatorio.");
            }
            
            check_deal_permission($pdo, $deal_id);
            
            // Trato y relaciones
            $stmt = $pdo->prepare("
                SELECT d.*, s.name AS stage_name, a.name AS account_name, a.phone AS account_phone, a.email AS account_email,
                       c.first_name, c.last_name, c.job_title, c.phone AS contact_phone, c.email AS contact_email
                FROM deals d
                JOIN stages s ON d.stage_id = s.id
                LEFT JOIN accounts a ON d.account_id = a.id
                LEFT JOIN contacts c ON d.contact_id = c.id
                WHERE d.id = ?
            ");
            $stmt->execute([$deal_id]);
            $deal = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$deal) {
                throw new Exception("Trato no encontrado.");
            }
            
            // Notas
            $stmt_notes = $pdo->prepare("SELECT * FROM notes WHERE deal_id = ? ORDER BY created_at DESC");
            $stmt_notes->execute([$deal_id]);
            $notes = $stmt_notes->fetchAll(PDO::FETCH_ASSOC);
            
            // Actividades
            $stmt_act = $pdo->prepare("SELECT * FROM activities WHERE deal_id = ? ORDER BY due_date ASC");
            $stmt_act->execute([$deal_id]);
            $activities = $stmt_act->fetchAll(PDO::FETCH_ASSOC);

            // Campos personalizados con sus valores actuales
            $stmt_custom_vals = $pdo->prepare("
                SELECT fd.id, fd.name, fd.field_type, fd.options, cv.value
                FROM custom_field_definitions fd
                LEFT JOIN deal_custom_values cv ON fd.id = cv.field_id AND cv.deal_id = ?
                ORDER BY fd.id
            ");
            $stmt_custom_vals->execute([$deal_id]);
            $custom_fields = $stmt_custom_vals->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'deal' => $deal,
                'notes' => $notes,
                'activities' => $activities,
                'custom_fields' => $custom_fields
            ]);
            exit;

        case 'add_deal_note':
            $deal_id = isset($input_data['deal_id']) ? intval($input_data['deal_id']) : 0;
            $content = isset($input_data['content']) ? trim($input_data['content']) : '';
            
            if (!$deal_id || empty($content)) {
                throw new Exception("Parámetros incompletos para guardar nota.");
            }
            
            check_deal_permission($pdo, $deal_id);
            
            $stmt = $pdo->prepare("INSERT INTO `notes` (`deal_id`, `content`) VALUES (?, ?)");
            $stmt->execute([$deal_id, $content]);
            
            echo json_encode(['success' => true]);
            exit;

        case 'create_industry':
            $name = isset($_POST['name']) ? trim($_POST['name']) : '';
            $points = isset($_POST['scoring_points']) ? intval($_POST['scoring_points']) : 10;
            
            if (empty($name)) {
                throw new Exception("El nombre del sector es obligatorio.");
            }
            
            $stmt = $pdo->prepare("INSERT INTO `industries` (`name`, `scoring_points`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `scoring_points` = ?");
            $stmt->execute([$name, $points, $points]);
            
            header("Location: accounts.php");
            exit;
            
        case 'get_account_timeline':
            $account_id = isset($_GET['account_id']) ? intval($_GET['account_id']) : 0;
            if (!$account_id) {
                throw new Exception("ID de cuenta obligatorio.");
            }
            
            // 1. Obtener la cuenta
            $stmt_acc = $pdo->prepare("SELECT * FROM accounts WHERE id = ?");
            $stmt_acc->execute([$account_id]);
            $account = $stmt_acc->fetch(PDO::FETCH_ASSOC);
            if (!$account) {
                throw new Exception("Cuenta no encontrada.");
            }
            
            // 2. Obtener Notas de tratos asociados
            $stmt_notes = $pdo->prepare("
                SELECT n.*, d.title AS deal_title 
                FROM notes n 
                JOIN deals d ON n.deal_id = d.id 
                WHERE d.account_id = ? 
                ORDER BY n.created_at DESC
            ");
            $stmt_notes->execute([$account_id]);
            $notes = $stmt_notes->fetchAll(PDO::FETCH_ASSOC);
            
            // 3. Obtener Correos de tratos asociados
            $stmt_emails = $pdo->prepare("
                SELECT e.*, d.title AS deal_title 
                FROM emails e 
                JOIN deals d ON e.deal_id = d.id 
                WHERE d.account_id = ? 
                ORDER BY e.sent_date DESC
            ");
            $stmt_emails->execute([$account_id]);
            $emails = $stmt_emails->fetchAll(PDO::FETCH_ASSOC);
            
            // 4. Obtener Actividades de tratos asociados
            $stmt_act = $pdo->prepare("
                SELECT a.*, d.title AS deal_title 
                FROM activities a 
                JOIN deals d ON a.deal_id = d.id 
                WHERE d.account_id = ? 
                ORDER BY a.due_date ASC
            ");
            $stmt_act->execute([$account_id]);
            $activities = $stmt_act->fetchAll(PDO::FETCH_ASSOC);
            // 5. Obtener Facturas de la cuenta
            $stmt_inv = $pdo->prepare("SELECT * FROM invoices WHERE account_id = ? ORDER BY due_date DESC");
            $stmt_inv->execute([$account_id]);
            $invoices = $stmt_inv->fetchAll(PDO::FETCH_ASSOC);

            // 6. Obtener Documentos del expediente
            $stmt_docs = $pdo->prepare("SELECT * FROM account_documents WHERE account_id = ? ORDER BY uploaded_at DESC");
            $stmt_docs->execute([$account_id]);
            $documents = $stmt_docs->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'account' => $account,
                'notes' => $notes,
                'emails' => $emails,
                'activities' => $activities,
                'invoices' => $invoices,
                'documents' => $documents
            ]);
            exit;

        case 'get_account_detail':
            // Ficha completa de una empresa para account.php: negocios, historial de
            // fases del embudo, actividades, notas, correos, facturas y documentos.
            $account_id = isset($_GET['account_id']) ? intval($_GET['account_id']) : 0;
            if (!$account_id) {
                throw new Exception("ID de cuenta obligatorio.");
            }

            $stmt_acc = $pdo->prepare("SELECT * FROM accounts WHERE id = ?");
            $stmt_acc->execute([$account_id]);
            $account = $stmt_acc->fetch(PDO::FETCH_ASSOC);
            if (!$account) {
                throw new Exception("Cuenta no encontrada.");
            }

            // Visibilidad: un asesor solo ve las cuentas que tiene asignadas.
            $restriction = get_visibility_restriction();
            if ($restriction !== '' && $account['assigned_agent'] !== $restriction) {
                throw new Exception("Acceso denegado: esta empresa está asignada a otro asesor.");
            }

            // Contactos de la cuenta
            $stmt_con = $pdo->prepare("SELECT * FROM contacts WHERE account_id = ? ORDER BY first_name ASC");
            $stmt_con->execute([$account_id]);
            $contacts = $stmt_con->fetchAll(PDO::FETCH_ASSOC);

            // Negocios (deals) de la cuenta con su fase y embudo
            $stmt_deals = $pdo->prepare("
                SELECT d.*, s.name AS stage_name, s.position AS stage_position, s.pipeline_id,
                       p.name AS pipeline_name,
                       c.first_name, c.last_name, c.job_title,
                       c.phone AS contact_phone, c.email AS contact_email
                FROM deals d
                JOIN stages s ON d.stage_id = s.id
                JOIN pipelines p ON s.pipeline_id = p.id
                LEFT JOIN contacts c ON d.contact_id = c.id
                WHERE d.account_id = ?
                ORDER BY d.created_at DESC, d.id DESC
            ");
            $stmt_deals->execute([$account_id]);
            $deals = $stmt_deals->fetchAll(PDO::FETCH_ASSOC);

            // Historial de fases de esos negocios
            $stage_history = [];
            $deal_ids = array_map('intval', array_column($deals, 'id'));
            if ($deal_ids) {
                $ph = implode(',', array_fill(0, count($deal_ids), '?'));
                try {
                    $stmt_hist = $pdo->prepare("
                        SELECT h.deal_id, h.stage_id, h.entered_at, h.changed_by,
                               s.name AS stage_name, s.position
                        FROM deal_stage_history h
                        JOIN stages s ON h.stage_id = s.id
                        WHERE h.deal_id IN ($ph)
                        ORDER BY h.entered_at ASC, h.id ASC
                    ");
                    $stmt_hist->execute($deal_ids);
                    $stage_history = $stmt_hist->fetchAll(PDO::FETCH_ASSOC);
                } catch (Exception $e) {
                    $stage_history = []; // tabla aún no migrada
                }
            }

            // Ruta completa de fases por cada embudo involucrado
            $pipeline_stages = [];
            foreach (array_unique(array_map('intval', array_column($deals, 'pipeline_id'))) as $pid) {
                $st = $pdo->prepare("SELECT id, name, position FROM stages WHERE pipeline_id = ? ORDER BY position ASC");
                $st->execute([$pid]);
                $pipeline_stages[$pid] = $st->fetchAll(PDO::FETCH_ASSOC);
            }

            // Notas, correos y actividades de los negocios de la cuenta
            $stmt_notes = $pdo->prepare("
                SELECT n.*, d.title AS deal_title
                FROM notes n JOIN deals d ON n.deal_id = d.id
                WHERE d.account_id = ? ORDER BY n.created_at DESC
            ");
            $stmt_notes->execute([$account_id]);
            $notes = $stmt_notes->fetchAll(PDO::FETCH_ASSOC);

            $stmt_emails = $pdo->prepare("
                SELECT e.*, d.title AS deal_title
                FROM emails e JOIN deals d ON e.deal_id = d.id
                WHERE d.account_id = ? ORDER BY e.sent_date DESC
            ");
            $stmt_emails->execute([$account_id]);
            $emails = $stmt_emails->fetchAll(PDO::FETCH_ASSOC);

            $stmt_act = $pdo->prepare("
                SELECT a.*, d.title AS deal_title
                FROM activities a JOIN deals d ON a.deal_id = d.id
                WHERE d.account_id = ? ORDER BY a.due_date ASC
            ");
            $stmt_act->execute([$account_id]);
            $activities = $stmt_act->fetchAll(PDO::FETCH_ASSOC);

            // Facturas y documentos de la cuenta
            $stmt_inv = $pdo->prepare("SELECT * FROM invoices WHERE account_id = ? ORDER BY due_date DESC");
            $stmt_inv->execute([$account_id]);
            $invoices = $stmt_inv->fetchAll(PDO::FETCH_ASSOC);

            $stmt_docs = $pdo->prepare("SELECT * FROM account_documents WHERE account_id = ? ORDER BY uploaded_at DESC");
            $stmt_docs->execute([$account_id]);
            $documents = $stmt_docs->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'account' => $account,
                'contacts' => $contacts,
                'deals' => $deals,
                'stage_history' => $stage_history,
                'pipeline_stages' => $pipeline_stages,
                'notes' => $notes,
                'emails' => $emails,
                'activities' => $activities,
                'invoices' => $invoices,
                'documents' => $documents
            ]);
            exit;

        case 'upload_account_document':
            $account_id = isset($_POST['account_id']) ? intval($_POST['account_id']) : 0;
            $category = isset($_POST['category']) ? trim($_POST['category']) : 'Otro';
            
            if (!$account_id) {
                throw new Exception("ID de cuenta obligatorio.");
            }
            if (empty($_FILES['document_file']['name'])) {
                throw new Exception("Debes seleccionar un archivo para subir.");
            }
            
            $file_name = basename($_FILES['document_file']['name']);
            $file_name = preg_replace("/[^a-zA-Z0-9_\.-]/", "_", $file_name);
            
            $target_dir = __DIR__ . '/uploads/';
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $stored_name = time() . '_' . $file_name;
            $target_path = $target_dir . $stored_name;

            if (move_uploaded_file($_FILES['document_file']['tmp_name'], $target_path)) {
                // Guardar el nombre real en disco para que el enlace "Ver archivo" funcione.
                $stmt = $pdo->prepare("INSERT INTO `account_documents` (`account_id`, `filename`, `category`) VALUES (?, ?, ?)");
                $stmt->execute([$account_id, $stored_name, $category]);
            } else {
                throw new Exception("Error al guardar el archivo en el servidor.");
            }

            $redirect_uri = isset($_POST['redirect_uri']) ? $_POST['redirect_uri'] : 'accounts.php';
            header("Location: " . $redirect_uri);
            exit;
            
        case 'update_sales_quota':
            $target = isset($_POST['quota_target']) ? floatval($_POST['quota_target']) : 30000;
            if ($target <= 0) {
                throw new Exception("La meta de ventas debe ser mayor a cero.");
            }
            
            $stmt = $pdo->prepare("UPDATE `crm_settings` SET `setting_value` = ? WHERE `setting_key` = 'sales_quota_target'");
            $stmt->execute([$target]);

            header("Location: panel.php");
            exit;

        case 'change_password':
            $current_password = isset($input_data['current_password']) ? $input_data['current_password'] : '';
            $new_password = isset($input_data['new_password']) ? $input_data['new_password'] : '';

            if (empty($current_password) || empty($new_password)) {
                throw new Exception("Ambos campos de contraseña son obligatorios.");
            }
            if (strlen($new_password) < 8) {
                throw new Exception("La contraseña nueva debe tener al menos 8 caracteres.");
            }

            $stmt = $pdo->prepare("SELECT * FROM crm_users WHERE id = ?");
            $stmt->execute([$_SESSION['crm_user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($current_password, $user['password_hash'])) {
                throw new Exception("La contraseña actual no es correcta.");
            }

            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt_upd = $pdo->prepare("UPDATE crm_users SET password_hash = ? WHERE id = ?");
            $stmt_upd->execute([$new_hash, $user['id']]);

            echo json_encode(['success' => true, 'message' => 'Contraseña actualizada correctamente.']);
            exit;

        case 'update_profile':
            $full_name = isset($input_data['full_name']) ? trim($input_data['full_name']) : '';
            $email = isset($input_data['email']) ? trim($input_data['email']) : '';

            if (empty($full_name)) {
                throw new Exception("El nombre completo es obligatorio.");
            }
            if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("El correo electrónico no es válido.");
            }

            $stmt_upd = $pdo->prepare("UPDATE crm_users SET full_name = ?, email = ? WHERE id = ?");
            $stmt_upd->execute([$full_name, $email, $_SESSION['crm_user_id']]);

            // Actualizar la variable de sesión
            $_SESSION['crm_user_name'] = $full_name;

            echo json_encode(['success' => true, 'message' => 'Perfil actualizado correctamente.']);
            exit;

        case 'create_custom_field':
            $name = isset($_POST['name']) ? trim($_POST['name']) : '';
            $type = isset($_POST['field_type']) ? trim($_POST['field_type']) : 'text';
            $options = isset($_POST['options']) ? trim($_POST['options']) : null;
            
            if (empty($name)) {
                throw new Exception("El nombre del campo es obligatorio.");
            }
            
            $stmt = $pdo->prepare("INSERT INTO `custom_field_definitions` (`name`, `field_type`, `options`) VALUES (?, ?, ?)");
            $stmt->execute([$name, $type, $options]);
            
            header("Location: pipeline.php");
            exit;

        case 'delete_custom_field':
            $field_id = isset($input_data['field_id']) ? intval($input_data['field_id']) : 0;
            if (!$field_id) {
                throw new Exception("ID de campo obligatorio.");
            }
            
            $stmt = $pdo->prepare("DELETE FROM `custom_field_definitions` WHERE `id` = ?");
            $stmt->execute([$field_id]);
            
            echo json_encode(['success' => true]);
            exit;

        case 'create_automation':
            $title = isset($_POST['title']) ? trim($_POST['title']) : (isset($input_data['title']) ? trim($input_data['title']) : '');
            $trigger_value = isset($_POST['trigger_value']) ? trim($_POST['trigger_value']) : (isset($input_data['trigger_value']) ? trim($input_data['trigger_value']) : '');
            $task_type = isset($_POST['task_type']) ? trim($_POST['task_type']) : (isset($input_data['task_type']) ? trim($input_data['task_type']) : 'Task');
            $task_title = isset($_POST['task_title']) ? trim($_POST['task_title']) : (isset($input_data['task_title']) ? trim($input_data['task_title']) : 'Tarea Automática');
            $due_in_days = isset($_POST['due_in_days']) ? intval($_POST['due_in_days']) : (isset($input_data['due_in_days']) ? intval($input_data['due_in_days']) : 2);

            if (empty($title) || empty($trigger_value)) {
                throw new Exception("El título de la regla y la etapa son campos obligatorios.");
            }

            $action_payload = json_encode([
                'type' => $task_type,
                'due_in_days' => $due_in_days,
                'title' => $task_title
            ], JSON_UNESCAPED_UNICODE);

            $stmt = $pdo->prepare("INSERT INTO `crm_automations` (`title`, `trigger_event`, `trigger_value`, `action_type`, `action_payload`, `is_active`) VALUES (?, 'stage_change', ?, 'create_task', ?, 1)");
            $stmt->execute([$title, $trigger_value, $action_payload]);

            if (!empty($_POST)) {
                header("Location: settings.php#section-automations");
                exit;
            } else {
                echo json_encode(['success' => true, 'message' => 'Regla de automatización creada.']);
                exit;
            }

        case 'toggle_automation':
            $auto_id = isset($input_data['id']) ? intval($input_data['id']) : 0;
            if (!$auto_id) {
                throw new Exception("ID de automatización inválido.");
            }

            $stmt = $pdo->prepare("SELECT `is_active` FROM `crm_automations` WHERE `id` = ?");
            $stmt->execute([$auto_id]);
            $current = $stmt->fetchColumn();
            $new_state = ($current == 1) ? 0 : 1;

            $stmt_upd = $pdo->prepare("UPDATE `crm_automations` SET `is_active` = ? WHERE `id` = ?");
            $stmt_upd->execute([$new_state, $auto_id]);

            echo json_encode(['success' => true, 'new_state' => $new_state]);
            exit;

        case 'delete_automation':
            $auto_id = isset($input_data['id']) ? intval($input_data['id']) : 0;
            if (!$auto_id) {
                throw new Exception("ID de automatización inválido.");
            }

            $stmt = $pdo->prepare("DELETE FROM `crm_automations` WHERE `id` = ?");
            $stmt->execute([$auto_id]);

            echo json_encode(['success' => true]);
            exit;

        case 'test_smtp':
            $to = isset($input_data['to']) ? trim($input_data['to']) : '';
            if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Indica un correo de destino válido.");
            }

            require_once __DIR__ . '/includes/smtp_mailer.php';
            if (!smtp_is_configured()) {
                throw new Exception("SMTP no está configurado. Completa y guarda los datos del servidor de correo.");
            }

            $subject = "Prueba de correo — " . (defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'TIPS CRM');
            $body = "Este es un correo de prueba enviado desde la configuración SMTP del CRM.\n\n"
                  . "Servidor: " . SMTP_HOST . ":" . SMTP_PORT . " (" . SMTP_SECURE . ")\n"
                  . "Remitente: " . SMTP_FROM_EMAIL . "\n"
                  . "Fecha: " . date('Y-m-d H:i:s') . "\n\n"
                  . "Si recibiste este mensaje, la configuración de correo saliente funciona correctamente.";

            $smtp_error = '';
            if (smtp_send_mail($to, $subject, $body, $smtp_error)) {
                echo json_encode(['success' => true, 'message' => "Correo de prueba enviado a {$to}."]);
            } else {
                throw new Exception("Fallo el envío: " . $smtp_error);
            }
            exit;

        case 'update_settings':
            if (empty($input_data) && !empty($_POST)) {
                $settings_data = $_POST;
            } else {
                $settings_data = $input_data;
            }

            if (!empty($settings_data)) {
                $stmt = $pdo->prepare("INSERT INTO `crm_settings` (`setting_key`, `setting_value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`)");
                foreach ($settings_data as $key => $val) {
                    if ($key === 'action' || $key === 'redirect_uri') continue;
                    $stmt->execute([trim($key), trim($val)]);
                }
            }

            echo json_encode(['success' => true, 'message' => 'Configuración guardada correctamente.']);
            exit;

        // ── GESTIÓN DE USUARIOS (solo admin) ───────────────────────────────────
        case 'invite_user':
            require_admin(true);
            $email     = isset($input_data['email']) ? trim($input_data['email']) : '';
            $full_name = isset($input_data['full_name']) ? trim($input_data['full_name']) : '';
            $username  = isset($input_data['username']) ? trim($input_data['username']) : '';
            $role      = (isset($input_data['role']) && $input_data['role'] === 'admin') ? 'admin' : 'agent';

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("El correo electrónico no es válido.");
            }
            if ($full_name === '') {
                throw new Exception("El nombre completo es obligatorio.");
            }
            if ($username === '') {
                $username = strtolower(preg_replace('/[^a-z0-9._-]/i', '', explode('@', $email)[0]));
            }
            if ($username === '' || !preg_match('/^[a-z0-9._-]{2,100}$/i', $username)) {
                throw new Exception("Nombre de usuario inválido (2-100 caracteres: letras, números, . _ -).");
            }

            $dup = $pdo->prepare("SELECT id FROM crm_users WHERE username = ? OR email = ?");
            $dup->execute([$username, $email]);
            if ($dup->fetchColumn()) {
                throw new Exception("Ya existe un usuario con ese nombre de usuario o correo.");
            }

            $placeholder = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
            $ins = $pdo->prepare("INSERT INTO crm_users (username, password_hash, role, full_name, email) VALUES (?, ?, ?, ?, ?)");
            $ins->execute([$username, $placeholder, $role, $full_name, $email]);

            $inv = crm_send_invite($pdo, $username, $email, $full_name);
            echo json_encode([
                'success'    => true,
                'message'    => $inv['sent']
                    ? "Usuario creado. Invitación enviada a {$email}."
                    : "Usuario creado, pero el correo no se pudo enviar ({$inv['error']}). Pásale este enlace manualmente.",
                'mail_sent'  => $inv['sent'],
                'invite_link'=> $inv['link'],
            ]);
            exit;

        case 'resend_invite':
            require_admin(true);
            $user_id = isset($input_data['user_id']) ? intval($input_data['user_id']) : 0;
            $u = $pdo->prepare("SELECT username, email, full_name FROM crm_users WHERE id = ?");
            $u->execute([$user_id]);
            $usr = $u->fetch(PDO::FETCH_ASSOC);
            if (!$usr) throw new Exception("Usuario no encontrado.");
            if (empty($usr['email'])) throw new Exception("El usuario no tiene un correo configurado.");

            $inv = crm_send_invite($pdo, $usr['username'], $usr['email'], $usr['full_name']);
            echo json_encode([
                'success'     => true,
                'message'     => $inv['sent'] ? "Invitación reenviada a {$usr['email']}." : "No se pudo enviar el correo ({$inv['error']}).",
                'mail_sent'   => $inv['sent'],
                'invite_link' => $inv['link'],
            ]);
            exit;

        case 'update_user_role':
            require_admin(true);
            $user_id = isset($input_data['user_id']) ? intval($input_data['user_id']) : 0;
            $role    = (isset($input_data['role']) && $input_data['role'] === 'admin') ? 'admin' : 'agent';

            if ($user_id === (int) $_SESSION['crm_user_id']) {
                throw new Exception("No puedes cambiar tu propio rol.");
            }
            $target = $pdo->prepare("SELECT role FROM crm_users WHERE id = ?");
            $target->execute([$user_id]);
            $current_role = $target->fetchColumn();
            if ($current_role === false) throw new Exception("Usuario no encontrado.");

            if ($current_role === 'admin' && $role === 'agent') {
                $admins = (int) $pdo->query("SELECT COUNT(*) FROM crm_users WHERE role = 'admin'")->fetchColumn();
                if ($admins <= 1) throw new Exception("No puedes dejar el CRM sin ningún administrador.");
            }

            $pdo->prepare("UPDATE crm_users SET role = ? WHERE id = ?")->execute([$role, $user_id]);
            echo json_encode(['success' => true, 'message' => 'Rol actualizado.']);
            exit;

        case 'delete_user':
            require_admin(true);
            $user_id = isset($input_data['user_id']) ? intval($input_data['user_id']) : 0;
            if ($user_id === (int) $_SESSION['crm_user_id']) {
                throw new Exception("No puedes eliminar tu propia cuenta.");
            }
            $target = $pdo->prepare("SELECT username, role FROM crm_users WHERE id = ?");
            $target->execute([$user_id]);
            $usr = $target->fetch(PDO::FETCH_ASSOC);
            if (!$usr) throw new Exception("Usuario no encontrado.");

            if ($usr['role'] === 'admin') {
                $admins = (int) $pdo->query("SELECT COUNT(*) FROM crm_users WHERE role = 'admin'")->fetchColumn();
                if ($admins <= 1) throw new Exception("No puedes eliminar al único administrador.");
            }

            $pdo->prepare("DELETE FROM crm_password_resets WHERE username = ?")->execute([$usr['username']]);
            $pdo->prepare("DELETE FROM crm_users WHERE id = ?")->execute([$user_id]);
            echo json_encode(['success' => true, 'message' => 'Usuario eliminado.']);
            exit;

        default:
            throw new Exception("Acción no válida.");
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    exit;
}

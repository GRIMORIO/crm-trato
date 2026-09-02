<?php
/**
 * api_chat.php — API REST para Chat en Vivo con Integración IA (TIPS CRM)
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';

header('Content-Type: application/json');

$action = isset($_GET['action']) ? $_GET['action'] : '';

// Las acciones "admin_*" las usa chat_console.php (requiere sesión) — el resto las usa el
// widget público (api_chat.php + widget_chat.php), que debe seguir accesible sin login para
// que un visitante anónimo del sitio pueda chatear.
if (strpos($action, 'admin_') === 0) {
    require_login(true);
}

// Capturar payload JSON
$input_raw = file_get_contents('php://input');
$input_data = json_decode($input_raw, true) ?: [];

try {
    switch ($action) {
        
        // ── ACCIONES PARA EL CLIENTE (WIDGET DE CHAT) ─────────────────────────────
        case 'init_session':
            // Iniciar o recuperar sesión de chat
            $session_key = isset($input_data['session_key']) ? trim($input_data['session_key']) : '';
            if (empty($session_key)) {
                $session_key = bin2hex(random_bytes(16));
            }
            
            // Verificar si ya existe en la DB
            $stmt = $pdo->prepare("SELECT * FROM live_chats WHERE session_key = ?");
            $stmt->execute([$session_key]);
            $chat = $stmt->fetch();
            
            if (!$chat) {
                // Crear nueva sesión de chat
                $stmt = $pdo->prepare("INSERT INTO `live_chats` (`session_key`, `status`) VALUES (?, 'Active')");
                $stmt->execute([$session_key]);
                $chat_id = $pdo->lastInsertId();
                
                // Mensaje de bienvenida inicial automático
                $stmt_msg = $pdo->prepare("INSERT INTO `chat_messages` (`chat_id`, `sender`, `message`) VALUES (?, 'AI', '¡Hola! Bienvenido a TIPS B2B. ¿En qué podemos ayudarte hoy? (Pregúntame sobre colorantes Enco, batidoras o equipos comerciales)')");
                $stmt_msg->execute([$chat_id]);
            }
            
            echo json_encode([
                'success' => true,
                'session_key' => $session_key,
                'lead_name' => $chat['lead_name'] ?? '',
                'lead_email' => $chat['lead_email'] ?? ''
            ]);
            exit;
            
        case 'send_message':
            $session_key = isset($input_data['session_key']) ? trim($input_data['session_key']) : '';
            $message = isset($input_data['message']) ? trim($input_data['message']) : '';
            $name = isset($input_data['name']) ? trim($input_data['name']) : '';
            $email = isset($input_data['email']) ? trim($input_data['email']) : '';
            
            if (empty($session_key) || empty($message)) {
                throw new Exception("Parámetros incompletos.");
            }
            
            // Obtener chat
            $stmt = $pdo->prepare("SELECT * FROM live_chats WHERE session_key = ?");
            $stmt->execute([$session_key]);
            $chat = $stmt->fetch();
            if (!$chat) throw new Exception("Sesión de chat no encontrada.");
            
            $chat_id = $chat['id'];
            
            // Actualizar datos del lead si se envían
            if (!empty($name) || !empty($email)) {
                $stmt_upd = $pdo->prepare("UPDATE `live_chats` SET `lead_name` = ?, `lead_email` = ? WHERE `id` = ?");
                $stmt_upd->execute([
                    $name ?: $chat['lead_name'],
                    $email ?: $chat['lead_email'],
                    $chat_id
                ]);
            }
            
            // Insertar mensaje del lead
            $stmt_msg = $pdo->prepare("INSERT INTO `chat_messages` (`chat_id`, `sender`, `message`) VALUES (?, 'Lead', ?)");
            $stmt_msg->execute([$chat_id, $message]);
            
            // --- AUTOMATIZACIÓN IA (Fallback inmediato si no hay agente en línea) ---
            // Como simulación en vivo, la IA responde en 1 segundo.
            $ai_response = '';
            if (defined('OPENAI_API_KEY') && !empty(OPENAI_API_KEY)) {
                // Obtener historial reciente de mensajes
                $hist_stmt = $pdo->prepare("SELECT sender, message FROM chat_messages WHERE chat_id = ? ORDER BY created_at ASC LIMIT 10");
                $hist_stmt->execute([$chat_id]);
                $history = $hist_stmt->fetchAll();
                
                $messages_payload = [
                    ['role' => 'system', 'content' => "Eres un Asistente Virtual B2B de TIPS S.A. Costa Rica. Ayudas a panaderías, pastelerías y hoteles. Vendemos colorantes/fondant Enco, batidoras KitchenAid y hornos comerciales. Responde de forma muy concisa, profesional y con tono cálido costarricense. Si el usuario te pregunta detalles complejos de compras, solicítale su número de teléfono/correo para que un ejecutivo humano de TIPS le llame."]
                ];
                foreach ($history as $h) {
                    $role = ($h['sender'] === 'Lead') ? 'user' : 'assistant';
                    $messages_payload[] = ['role' => $role, 'content' => $h['message']];
                }
                
                // Llamar a OpenAI GPT-4o-mini
                $ch = curl_init('https://api.openai.com/v1/chat/completions');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                    'model' => 'gpt-4o-mini',
                    'messages' => $messages_payload,
                    'temperature' => 0.5
                ]));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . OPENAI_API_KEY
                ]);
                $response = curl_exec($ch);
                curl_close($ch);
                
                $res_data = json_decode($response, true);
                if (isset($res_data['choices'][0]['message']['content'])) {
                    $ai_response = trim($res_data['choices'][0]['message']['content']);
                }
            }
            
            // Fallback local si falla OpenAI
            if (empty($ai_response)) {
                $ai_response = "Muchas gracias por escribirnos. He tomado nota de tu consulta: \"{$message}\". Por favor indícanos tu número de WhatsApp o correo electrónico para que un asesor comercial de TIPS B2B te contacte de inmediato.";
            }
            
            // Insertar respuesta de la IA
            $stmt_ai = $pdo->prepare("INSERT INTO `chat_messages` (`chat_id`, `sender`, `message`) VALUES (?, 'AI', ?)");
            $stmt_ai->execute([$chat_id, $ai_response]);
            
            echo json_encode(['success' => true, 'ai_responded' => true]);
            exit;
            
        case 'get_messages':
            $session_key = isset($_GET['session_key']) ? trim($_GET['session_key']) : '';
            if (empty($session_key)) throw new Exception("Sesión inválida.");
            
            $stmt = $pdo->prepare("
                SELECT msg.* 
                FROM chat_messages msg
                INNER JOIN live_chats c ON msg.chat_id = c.id
                WHERE c.session_key = ?
                ORDER BY msg.created_at ASC
            ");
            $stmt->execute([$session_key]);
            $messages = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'messages' => $messages]);
            exit;

        // ── ACCIONES PARA EL ASESOR COMERCIAL (CONSOLA CRM) ──────────────────────
        case 'admin_get_chats':
            // Cargar chats activos
            $chats = $pdo->query("
                SELECT c.*, 
                       (SELECT message FROM chat_messages WHERE chat_id = c.id ORDER BY created_at DESC LIMIT 1) AS last_message,
                       (SELECT created_at FROM chat_messages WHERE chat_id = c.id ORDER BY created_at DESC LIMIT 1) AS last_message_time
                FROM live_chats c
                ORDER BY last_message_time DESC
            ")->fetchAll();
            
            echo json_encode(['success' => true, 'chats' => $chats]);
            exit;
            
        case 'admin_get_messages':
            $chat_id = isset($_GET['chat_id']) ? intval($_GET['chat_id']) : 0;
            if (!$chat_id) throw new Exception("Chat ID incorrecto.");
            
            $stmt = $pdo->prepare("SELECT * FROM chat_messages WHERE chat_id = ? ORDER BY created_at ASC");
            $stmt->execute([$chat_id]);
            $messages = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'messages' => $messages]);
            exit;
            
        case 'admin_send_message':
            $chat_id = isset($input_data['chat_id']) ? intval($input_data['chat_id']) : 0;
            $message = isset($input_data['message']) ? trim($input_data['message']) : '';
            
            if (!$chat_id || empty($message)) throw new Exception("Datos incompletos.");
            
            $stmt = $pdo->prepare("INSERT INTO `chat_messages` (`chat_id`, `sender`, `message`) VALUES (?, 'Agent', ?)");
            $stmt->execute([$chat_id, $message]);
            
            echo json_encode(['success' => true]);
            exit;

        default:
            throw new Exception("Acción no válida.");
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}

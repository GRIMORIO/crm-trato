<?php
/**
 * auth.php — Autenticación por sesión del CRM B2B TIPS.
 *
 * Necesario porque este CRM va a producción en internet (crmtrato.com) — sin esto,
 * cualquiera con la URL podría ver/editar cuentas reales, documentos y facturas de TIPS.
 *
 * ⚠️ Esto protege la sesión (cookie httponly, login con contraseña hasheada), pero NO reemplaza
 * HTTPS. El sitio debe correr bajo TLS real en producción — sin eso, la contraseña y la cookie
 * de sesión viajan en texto plano por la red.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax'
        // 'secure' => true, // activar cuando el sitio corra bajo HTTPS real
    ]);
    session_start();
}

function is_logged_in() {
    return isset($_SESSION['crm_user_id']);
}

function current_user_name() {
    return $_SESSION['crm_user_name'] ?? 'Usuario';
}

function is_admin() {
    return ($_SESSION['crm_user_role'] ?? 'agent') === 'admin';
}

// Exige rol admin. $json_response=true para endpoints fetch() (devuelve 403 JSON en vez de redirigir).
function require_admin($json_response = false) {
    require_login($json_response);
    if (!is_admin()) {
        if ($json_response) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Acción restringida a administradores.']);
            exit;
        }
        header('Location: panel.php');
        exit;
    }
}

// $json_response = true para endpoints llamados por fetch() (api.php, acciones admin_* de
// api_chat.php): devuelve 401 JSON en vez de redirigir, porque un redirect a login.php
// rompería el fetch().
function require_login($json_response = false) {
    if (!is_logged_in()) {
        if ($json_response) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Sesión expirada o no autenticada. Volvé a iniciar sesión.']);
            exit;
        }
        $redirect = urlencode($_SERVER['REQUEST_URI'] ?? 'panel.php');
        header('Location: login.php?redirect=' . $redirect);
        exit;
    }
}

function attempt_login(PDO $pdo, $username, $password) {
    $stmt = $pdo->prepare("SELECT * FROM crm_users WHERE username = ?");
    $stmt->execute([trim($username)]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    session_regenerate_id(true); // evita session fixation
    $_SESSION['crm_user_id'] = $user['id'];
    $_SESSION['crm_user_name'] = $user['full_name'] ?: $user['username'];
    $_SESSION['crm_user_role'] = $user['role'] ?? 'agent';
    $_SESSION['crm_assigned_agent_name'] = $user['assigned_agent_name'] ?? '';
    return true;
}

function get_visibility_restriction() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['crm_user_role']) && $_SESSION['crm_user_role'] === 'agent') {
        return $_SESSION['crm_assigned_agent_name'] ?? '';
    }
    return '';
}

function do_logout() {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

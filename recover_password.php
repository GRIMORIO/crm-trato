<?php
/**
 * recover_password.php — Recuperación de Contraseña para el CRM B2B TIPS
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/smtp_mailer.php';

// Redireccionar si ya está logueado
if (is_logged_in()) {
    header('Location: panel.php');
    exit;
}

$error = '';
$success = '';
$dev_reset_link = '';
$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$is_reset_flow = !empty($token);
$valid_token_user = null;

// Validar el token si estamos en el flujo de restablecimiento
if ($is_reset_flow) {
    $stmt = $pdo->prepare("SELECT * FROM crm_password_resets WHERE token = ? AND expires_at > NOW()");
    $stmt->execute([$token]);
    $reset_req = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reset_req) {
        // Redirigir a login con error si el token no sirve
        header('Location: login.php?msg=expired_token');
        exit;
    }
    $valid_token_user = $reset_req['username'];
}

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$is_reset_flow) {
        // Fase 1: Solicitar recuperación
        $username = isset($_POST['username']) ? trim($_POST['username']) : '';
        
        if (empty($username)) {
            $error = 'Por favor, introduce tu nombre de usuario.';
        } else {
            // Verificar si el usuario existe
            $stmt = $pdo->prepare("SELECT * FROM crm_users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                // Mensaje genérico por seguridad (evita la enumeración de usuarios)
                $success = 'Si el usuario existe y tiene un correo configurado, recibirá un enlace de recuperación.';
            } else {
                // Generar token único
                $raw_token = bin2hex(random_bytes(32));
                $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Borrar tokens anteriores de este usuario
                $stmt_del = $pdo->prepare("DELETE FROM crm_password_resets WHERE username = ?");
                $stmt_del->execute([$username]);
                
                // Insertar nuevo token
                $stmt_ins = $pdo->prepare("INSERT INTO crm_password_resets (username, token, expires_at) VALUES (?, ?, ?)");
                $stmt_ins->execute([$username, $raw_token, $expires_at]);
                
                // Construir enlace de recuperación
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
                $host = $_SERVER['HTTP_HOST'];
                // Obtener ruta del directorio actual
                $uri_path = preg_replace('/\/[^\/]*$/', '', $_SERVER['REQUEST_URI']);
                $reset_link = $protocol . $host . $uri_path . "/recover_password.php?token=" . $raw_token;
                
                $has_email = !empty($user['email']);
                $is_localhost = ($host === 'localhost' || $host === '127.0.0.1');
                
                if ($has_email && smtp_is_configured()) {
                    // Enviar correo real
                    $subject = "Recuperación de Contraseña — TIPS CRM";
                    $body = "Hola " . ($user['full_name'] ?: $username) . ",\n\n"
                          . "Hemos recibido una solicitud para restablecer tu contraseña en el TIPS CRM.\n"
                          . "Puedes hacerlo haciendo clic en el siguiente enlace (válido por 1 hora):\n\n"
                          . $reset_link . "\n\n"
                          . "Si no solicitaste este cambio, puedes ignorar este correo.\n\n"
                          . "Saludos,\n"
                          . "Departamento de E-commerce / Soporte TIPS";
                          
                    $smtp_error = '';
                    if (smtp_send_mail($user['email'], $subject, $body, $smtp_error)) {
                        $success = 'Se ha enviado un enlace de recuperación a tu correo electrónico registrado.';
                    } else {
                        $error = 'Error al enviar el correo: ' . $smtp_error;
                    }
                } else {
                    // Fallback
                    if ($is_localhost) {
                        $success = 'Modo Desarrollo detectado en Localhost.';
                        $dev_reset_link = $reset_link;
                    } else {
                        // En producción sin SMTP configurado, o usuario sin correo
                        if (!$has_email) {
                            $error = 'Este usuario no tiene un correo electrónico configurado en su perfil. Por favor, contacta al administrador.';
                        } else {
                            $error = 'El servidor de correo SMTP no está configurado en producción. Contacta al administrador para restablecer tu contraseña.';
                        }
                    }
                }
            }
        }
    } else {
        // Fase 2: Restablecer contraseña con el token
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
        
        if (empty($password) || empty($confirm_password)) {
            $error = 'Todos los campos son obligatorios.';
        } elseif (strlen($password) < 8) {
            $error = 'La contraseña debe tener al menos 8 caracteres.';
        } elseif ($password !== $confirm_password) {
            $error = 'Las contraseñas no coinciden.';
        } else {
            // Actualizar la contraseña del usuario
            $new_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt_upd = $pdo->prepare("UPDATE crm_users SET password_hash = ? WHERE username = ?");
            $stmt_upd->execute([$new_hash, $valid_token_user]);
            
            // Eliminar el token usado
            $stmt_del = $pdo->prepare("DELETE FROM crm_password_resets WHERE token = ?");
            $stmt_del->execute([$token]);
            
            // Redirigir a login con éxito
            header('Location: login.php?msg=reset_success');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
    <title>Recuperar Contraseña · TIPS CRM</title>
    <link rel="icon" type="image/svg+xml" href="assets/image/favicon.svg">
    <link rel="alternate icon" type="image/png" href="assets/image/log_azul.png">
    <link rel="apple-touch-icon" href="assets/image/log_azul.png">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo @filemtime(__DIR__ . '/assets/css/style.css'); ?>">
    <style>
        body { display: flex; align-items: center; justify-content: center; }
        .login-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 2.5rem;
            max-width: 400px;
            width: 100%;
            backdrop-filter: var(--glass-blur);
            box-shadow: 0 15px 40px rgba(0,0,0,0.5);
        }
        .login-logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-logo">
            <div class="logo-box">T</div>
            <h2 style="color:#fff; font-size:1.25rem; font-weight:700;">TIPS CRM</h2>
        </div>

        <h3 style="color:#fff; font-size:1.1rem; font-weight:600; margin-bottom:1rem;">
            <?php echo $is_reset_flow ? 'Crear Nueva Contraseña' : 'Recuperación de Contraseña'; ?>
        </h3>

        <?php if ($error): ?>
            <div style="background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.3); color:#fca5a5; padding:0.75rem; border-radius:6px; margin-bottom:1rem; font-size:0.85rem;">
                ❌ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div style="background:rgba(16,185,129,0.1); border:1px solid rgba(16,185,129,0.3); color:#a7f3d0; padding:0.75rem; border-radius:6px; margin-bottom:1rem; font-size:0.85rem;">
                ✔️ <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if ($dev_reset_link): ?>
            <div style="background:rgba(6,182,212,0.1); border:1px solid rgba(6,182,212,0.3); color:#a5f3fc; padding:0.75rem; border-radius:6px; margin-bottom:1.25rem; font-size:0.8rem; word-break: break-all;">
                ℹ️ <strong>Enlace generado (copia y pega en tu navegador):</strong><br>
                <a href="<?php echo $dev_reset_link; ?>" style="color:#22d3ee; text-decoration:underline; font-weight:600;"><?php echo $dev_reset_link; ?></a>
            </div>
        <?php endif; ?>

        <?php if (!$is_reset_flow): ?>
            <!-- Formulario de solicitud -->
            <form method="POST">
                <p style="color:var(--text-muted); font-size:0.85rem; margin-bottom:1.25rem; line-height:1.4;">
                    Introduce tu nombre de usuario. Si tienes un correo configurado, te enviaremos las instrucciones de restauración.
                </p>
                <div class="form-group">
                    <label for="username">Nombre de Usuario</label>
                    <input type="text" id="username" name="username" required autofocus placeholder="Ej. napoleon">
                </div>
                <button type="submit" class="btn-submit">Enviar Enlace</button>
                <div style="text-align: center; margin-top: 1.25rem;">
                    <a href="login.php" style="font-size:0.85rem; color:var(--text-muted); text-decoration:none; transition: var(--transition);" onmouseover="this.style.color='var(--color-primary)'" onmouseout="this.style.color='var(--text-muted)'">← Volver al login</a>
                </div>
            </form>
        <?php else: ?>
            <!-- Formulario de cambio de contraseña -->
            <form method="POST">
                <p style="color:var(--text-muted); font-size:0.85rem; margin-bottom:1.25rem; line-height:1.4;">
                    Estás restableciendo la contraseña del usuario: <strong style="color:#fff;"><?php echo htmlspecialchars($valid_token_user); ?></strong>.
                </p>
                <div class="form-group">
                    <label for="password">Nueva Contraseña (mínimo 8 caracteres)</label>
                    <input type="password" id="password" name="password" required autofocus placeholder="Mínimo 8 caracteres" minlength="8">
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirmar Nueva Contraseña</label>
                    <input type="password" id="confirm_password" name="confirm_password" required placeholder="Repite la contraseña" minlength="8">
                </div>
                <button type="submit" class="btn-submit">Restablecer Contraseña</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>

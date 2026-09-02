<?php
/**
 * login.php — Pantalla de acceso al CRM B2B TIPS
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';

$error = '';
$message = '';
$redirect_to = isset($_GET['redirect']) ? $_GET['redirect'] : 'panel.php';

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'reset_success') {
        $message = '¡Contraseña restablecida con éxito! Ya puedes iniciar sesión.';
    } elseif ($_GET['msg'] === 'expired_token') {
        $error = 'El enlace de recuperación es inválido o ha expirado.';
    }
}

if (is_logged_in()) {
    header('Location: ' . $redirect_to);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (attempt_login($pdo, $username, $password)) {
        $redirect_to = isset($_POST['redirect']) ? $_POST['redirect'] : 'panel.php';
        header('Location: ' . $redirect_to);
        exit;
    } else {
        $error = 'Usuario o contraseña incorrectos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
    <title>Iniciar Sesión · TIPS CRM</title>
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
        .password-wrapper {
            position: relative;
            display: flex;
            align-items: center;
            width: 100%;
        }
        .password-wrapper input {
            width: 100%;
            padding-right: 40px;
        }
        .password-toggle {
            position: absolute;
            right: 12px;
            background: transparent;
            border: none;
            color: var(--text-muted);
            font-size: 1.1rem;
            cursor: pointer;
            user-select: none;
            display: flex;
            align-items: center;
            justify-content: center;
            outline: none;
        }
        .password-toggle:hover {
            color: var(--color-primary);
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-logo">
            <div class="logo-box">T</div>
            <h2 style="color:#fff; font-size:1.25rem; font-weight:700;">TIPS CRM</h2>
        </div>

        <?php if ($error): ?>
            <div style="background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.3); color:#fca5a5; padding:0.75rem; border-radius:6px; margin-bottom:1rem; font-size:0.85rem;">
                ❌ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div style="background:rgba(16,185,129,0.1); border:1px solid rgba(16,185,129,0.3); color:#a7f3d0; padding:0.75rem; border-radius:6px; margin-bottom:1rem; font-size:0.85rem;">
                ✔️ <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect_to); ?>">
            <div class="form-group">
                <label for="username">Usuario</label>
                <input type="text" id="username" name="username" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Contraseña</label>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" required>
                    <button type="button" class="password-toggle" id="password-toggle-btn" onclick="togglePasswordVisibility()" tabindex="-1">👁️</button>
                </div>
            </div>
            <button type="submit" class="btn-submit">Iniciar Sesión</button>
            <div style="text-align: center; margin-top: 1.25rem;">
                <a href="recover_password.php" style="font-size:0.85rem; color:var(--text-muted); text-decoration:none; transition: var(--transition);" onmouseover="this.style.color='var(--color-primary)'" onmouseout="this.style.color='var(--text-muted)'">¿Olvidaste tu contraseña?</a>
            </div>
        </form>
    </div>
    <script>
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const toggleBtn = document.getElementById('password-toggle-btn');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleBtn.textContent = '🙈';
            } else {
                passwordInput.type = 'password';
                toggleBtn.textContent = '👁️';
            }
        }
    </script>
</body>
</html>

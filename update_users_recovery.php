<?php
/**
 * update_users_recovery.php — Actualización de Base de Datos para TIPS CRM: Recuperación y Cambio de Contraseña.
 */

require_once __DIR__ . '/config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>TIPS CRM — Actualización de Seguridad</title>
    <link href='https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap' rel='stylesheet'>
    <style>
        body {
            background-color: #070a13;
            color: #f1f5f9;
            font-family: 'Outfit', sans-serif;
            padding: 3rem 1rem;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .setup-card {
            background: rgba(18, 24, 48, 0.65);
            border: 1px solid rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(16px);
            border-radius: 16px;
            padding: 2.5rem;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }
        h1 {
            color: #06b6d4;
            font-size: 1.75rem;
            margin-bottom: 0.5rem;
            border-bottom: 2px dashed rgba(6, 182, 212, 0.2);
            padding-bottom: 0.5rem;
        }
        .step {
            margin: 1rem 0;
            padding-left: 1.5rem;
            border-left: 3px solid #a855f7;
            font-size: 0.95rem;
        }
        .step.success {
            border-left-color: #10b981;
        }
        .btn-go {
            display: inline-block;
            background: linear-gradient(135deg, #06b6d4, #a855f7);
            color: #fff;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 1.5rem;
            text-align: center;
            transition: 0.2s ease;
        }
        .btn-go:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(6, 182, 212, 0.3);
        }
    </style>
</head>
<body>
<div class='setup-card'>
    <h1>Actualización de Seguridad: Gestión de Usuarios</h1>
";

try {
    // 1. Crear tabla crm_users si no existe
    $pdo->exec("CREATE TABLE IF NOT EXISTS `crm_users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(100) UNIQUE NOT NULL,
        `password_hash` VARCHAR(255) NOT NULL,
        `full_name` VARCHAR(150) DEFAULT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    echo "<div class='step success'>✔️ Tabla `crm_users` verificada/creada.</div>";

    // 2. Verificar si la columna `email` existe en `crm_users`, si no, agregarla
    $check_email = $pdo->query("SHOW COLUMNS FROM `crm_users` LIKE 'email'")->fetch();
    if (!$check_email) {
        $pdo->exec("ALTER TABLE `crm_users` ADD COLUMN `email` VARCHAR(255) DEFAULT NULL AFTER `full_name`;");
        echo "<div class='step success'>✔️ Columna `email` agregada a la tabla `crm_users`.</div>";
    } else {
        echo "<div class='step success'>✔️ La columna `email` ya existe en `crm_users`.</div>";
    }

    // 3. Crear tabla crm_password_resets para los tokens de recuperación
    $pdo->exec("CREATE TABLE IF NOT EXISTS `crm_password_resets` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(100) NOT NULL,
        `token` VARCHAR(100) NOT NULL,
        `expires_at` DATETIME NOT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    echo "<div class='step success'>✔️ Tabla `crm_password_resets` creada/verificada.</div>";

    // 4. Sembrar usuario napoleon por defecto si la tabla está vacía o si napoleon no tiene correo
    $count = $pdo->query("SELECT COUNT(*) FROM `crm_users` WHERE `username` = 'napoleon'")->fetchColumn();
    if ($count == 0) {
        $temp_pass = 'tips2026';
        $hash = password_hash($temp_pass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO `crm_users` (`username`, `password_hash`, `full_name`, `email`) VALUES (?, ?, ?, ?)");
        $stmt->execute(['napoleon', $hash, 'Napoleón Contreras', 'napoleon@tipscr.com']);
        echo "<div class='step success'>✔️ Usuario `napoleon` creado por defecto. Contraseña inicial: <strong>{$temp_pass}</strong> (correo: napoleon@tipscr.com).</div>";
    } else {
        // Asegurar que tenga un correo de prueba si está vacío, para que funcione el flujo
        $email = $pdo->query("SELECT `email` FROM `crm_users` WHERE `username` = 'napoleon'")->fetchColumn();
        if (empty($email)) {
            $pdo->exec("UPDATE `crm_users` SET `email` = 'napoleon@tipscr.com' WHERE `username` = 'napoleon'");
            echo "<div class='step success'>✔️ Correo de usuario `napoleon` actualizado a napoleon@tipscr.com.</div>";
        }
    }

    echo "<p style='margin-top: 1.5rem; color: #10b981; font-weight: 600;'>¡Base de datos actualizada con éxito!</p>";
    echo "<a href='panel.php' class='btn-go'>Ir al Dashboard</a>";

} catch (Exception $e) {
    echo "<div class='step' style='border-left-color: #ef4444; color: #fca5a5;'>❌ Error durante la actualización: " . $e->getMessage() . "</div>";
}

echo "
</div>
</body>
</html>";

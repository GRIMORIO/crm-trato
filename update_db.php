<?php
/**
 * update_db.php — Actualización de Base de Datos para TIPS CRM (Fase 2)
 */

require_once __DIR__ . '/config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>TIPS CRM — Database Update</title>
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
            color: #a855f7;
            font-size: 1.75rem;
            margin-bottom: 0.5rem;
            border-bottom: 2px dashed rgba(168, 85, 247, 0.2);
            padding-bottom: 0.5rem;
        }
        .step {
            margin: 1rem 0;
            padding-left: 1.5rem;
            border-left: 3px solid #06b6d4;
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
    <h1>Actualización CRM: Tablas de la Fase 2</h1>
";

try {
    $sql_updates = [
        // 1. Tabla de Formularios Web
        "CREATE TABLE IF NOT EXISTS `web_forms` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `title` VARCHAR(255) NOT NULL,
            `fields` TEXT NOT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
        
        // 2. Tabla de Chats en Vivo
        "CREATE TABLE IF NOT EXISTS `live_chats` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `session_key` VARCHAR(100) UNIQUE NOT NULL,
            `lead_name` VARCHAR(255) DEFAULT NULL,
            `lead_email` VARCHAR(255) DEFAULT NULL,
            `status` ENUM('Active', 'Closed') DEFAULT 'Active',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
        
        // 3. Tabla de Mensajes de Chat
        "CREATE TABLE IF NOT EXISTS `chat_messages` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `chat_id` INT NOT NULL,
            `sender` ENUM('Lead', 'Agent', 'AI') NOT NULL,
            `message` TEXT NOT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`chat_id`) REFERENCES `live_chats`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
        
        // 4. Tabla de Correos
        "CREATE TABLE IF NOT EXISTS `emails` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `deal_id` INT DEFAULT NULL,
            `direction` ENUM('Sent', 'Received') NOT NULL,
            `sender` VARCHAR(255) NOT NULL,
            `recipient` VARCHAR(255) NOT NULL,
            `subject` VARCHAR(255) NOT NULL,
            `body` TEXT NOT NULL,
            `sent_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`deal_id`) REFERENCES `deals`(`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
        
        // 5. Tabla de Plantillas de WhatsApp
        "CREATE TABLE IF NOT EXISTS `whatsapp_templates` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `template_text` TEXT NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
    ];

    foreach ($sql_updates as $sql) {
        $pdo->exec($sql);
    }
    echo "<div class='step success'>✔️ Tablas creadas con éxito: web_forms, live_chats, chat_messages, emails y whatsapp_templates.</div>";

    // Insertar unas plantillas iniciales de WhatsApp y formularios
    $pdo->exec("INSERT INTO `whatsapp_templates` (`name`, `template_text`) VALUES 
        ('Contacto Inicial', 'Hola {{name}}, un gusto saludarte. Te escribo de TIPS S.A. sobre tu consulta por colorantes Enco. ¿Cómo podemos ayudarte hoy?'),
        ('Envío de Ficha / Cotización', 'Hola {{name}}. Te comparto la cotización del lote {{deal_title}} por un valor de {{value}}. Quedo atento a tus comentarios.'),
        ('Seguimiento Demo', 'Hola {{name}}. ¿Qué tal te pareció el rendimiento de la batidora KitchenAid? Contame si coordinamos la entrega del equipo.')
        ON DUPLICATE KEY UPDATE id=id;");
        
    $pdo->exec("INSERT INTO `web_forms` (`title`, `fields`) VALUES 
        ('Contacto Web Enco Alimentos', '[\"name\", \"email\", \"phone\", \"company\", \"message\"]')
        ON DUPLICATE KEY UPDATE id=id;");
        
    echo "<div class='step success'>✔️ Plantillas base de WhatsApp y formulario de captura insertados.</div>";

    echo "<p style='margin-top: 1.5rem; color: #10b981; font-weight: 600;'>¡Base de datos actualizada con éxito!</p>";
    echo "<a href='panel.php' class='btn-go'>Ir al Dashboard</a>";

} catch (Exception $e) {
    echo "<div class='step' style='border-left-color: #ef4444; color: #fca5a5;'>❌ Error durante la actualización: " . $e->getMessage() . "</div>";
}

echo "
</div>
</body>
</html>";

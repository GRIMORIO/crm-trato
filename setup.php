<?php
/**
 * setup.php — Instalador Consolidado de Base de Datos y Datos de Prueba Robustos para TIPS CRM
 */

require_once __DIR__ . '/config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>TIPS CRM — Instalador Consolidado</title>
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
            max-width: 650px;
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
    <h1>Instalación Completa del TIPS B2B CRM</h1>
";

try {
    // 1. Eliminar tablas antiguas para asegurar una carga limpia
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $tables_drop = [
        "DROP TABLE IF EXISTS `chat_messages`;",
        "DROP TABLE IF EXISTS `live_chats`;",
        "DROP TABLE IF EXISTS `web_forms`;",
        "DROP TABLE IF EXISTS `emails`;",
        "DROP TABLE IF EXISTS `email_templates`;",
        "DROP TABLE IF EXISTS `whatsapp_templates`;",
        "DROP TABLE IF EXISTS `activities`;",
        "DROP TABLE IF EXISTS `notes`;",
        "DROP TABLE IF EXISTS `deals`;",
        "DROP TABLE IF EXISTS `stages`;",
        "DROP TABLE IF EXISTS `contacts`;",
        "DROP TABLE IF EXISTS `accounts`;",
        "DROP TABLE IF EXISTS `industries`;",
        "DROP TABLE IF EXISTS `crm_settings`;",
        "DROP TABLE IF EXISTS `invoices`;",
        "DROP TABLE IF EXISTS `account_documents`;",
        "DROP TABLE IF EXISTS `sales_tips`;",
        "DROP TABLE IF EXISTS `crm_password_resets`;",
        "DROP TABLE IF EXISTS `pipelines`;",
        "DROP TABLE IF EXISTS `deal_custom_values`;",
        "DROP TABLE IF EXISTS `custom_field_definitions`;",
        "DROP TABLE IF EXISTS `crm_automations`;"
    ];
    foreach ($tables_drop as $sql) {
        $pdo->exec($sql);
    }
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    echo "<div class='step success'>✔️ Base de datos limpia (Tablas antiguas eliminadas).</div>";

    // 2. Crear Estructura de Tablas Consolidada
    $sql_tables = [
        // Sectores industriales personalizables
        "CREATE TABLE `industries` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) UNIQUE NOT NULL,
            `scoring_points` INT NOT NULL DEFAULT 10
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

        // Cuentas B2B
        "CREATE TABLE `accounts` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(255) NOT NULL,
            `industry` VARCHAR(100) DEFAULT NULL,
            `phone` VARCHAR(50) DEFAULT NULL,
            `email` VARCHAR(255) DEFAULT NULL,
            `address` TEXT DEFAULT NULL,
            `city` VARCHAR(100) DEFAULT NULL,
            `assigned_agent` VARCHAR(100) DEFAULT 'Andrés Herrera (GAM Norte)',
            `credit_limit` DECIMAL(12,2) DEFAULT 0.00,
            `credit_balance` DECIMAL(12,2) DEFAULT 0.00,
            `credit_terms` VARCHAR(100) DEFAULT 'Contado',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
        
        // Contactos asociados
        "CREATE TABLE `contacts` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `account_id` INT DEFAULT NULL,
            `first_name` VARCHAR(100) NOT NULL,
            `last_name` VARCHAR(100) NOT NULL,
            `job_title` VARCHAR(100) DEFAULT NULL,
            `phone` VARCHAR(50) DEFAULT NULL,
            `email` VARCHAR(255) DEFAULT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`account_id`) REFERENCES `accounts`(`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
        
        // Embudos de Venta (Pipelines)
        "CREATE TABLE `pipelines` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

        // Etapas de Venta
        "CREATE TABLE `stages` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `pipeline_id` INT DEFAULT NULL,
            `name` VARCHAR(100) NOT NULL,
            `position` INT NOT NULL,
            `win_probability` INT NOT NULL DEFAULT 50,
            FOREIGN KEY (`pipeline_id`) REFERENCES `pipelines`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
        
        // Oportunidades de Ventas (Deals) - Incorpora campos BANT
        "CREATE TABLE `deals` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `title` VARCHAR(255) NOT NULL,
            `value` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `stage_id` INT NOT NULL,
            `account_id` INT DEFAULT NULL,
            `contact_id` INT DEFAULT NULL,
            `status` ENUM('Open', 'Won', 'Lost') DEFAULT 'Open',
            `bant_budget` TINYINT DEFAULT 0,
            `bant_authority` TINYINT DEFAULT 0,
            `bant_need` TINYINT DEFAULT 0,
            `bant_timeline` TINYINT DEFAULT 0,
            `bant_notes` TEXT DEFAULT NULL,
            `meddic_metrics` TINYINT DEFAULT 0,
            `meddic_buyer` TINYINT DEFAULT 0,
            `meddic_criteria` TINYINT DEFAULT 0,
            `meddic_process` TINYINT DEFAULT 0,
            `meddic_pain` TINYINT DEFAULT 0,
            `meddic_champion` TINYINT DEFAULT 0,
            `close_date` DATE DEFAULT NULL,
            `assigned_agent` VARCHAR(100) DEFAULT 'Andrés Herrera (GAM Norte)',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`stage_id`) REFERENCES `stages`(`id`),
            FOREIGN KEY (`account_id`) REFERENCES `accounts`(`id`) ON DELETE SET NULL,
            FOREIGN KEY (`contact_id`) REFERENCES `contacts`(`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
        
        // Agenda de Actividades
        "CREATE TABLE `activities` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `deal_id` INT DEFAULT NULL,
            `type` ENUM('Call', 'Email', 'Meeting', 'Task', 'Technical_Visit', 'Demo', 'Samples') DEFAULT 'Call',
            `subject` VARCHAR(255) NOT NULL,
            `description` TEXT DEFAULT NULL,
            `due_date` DATETIME NOT NULL,
            `status` ENUM('Pending', 'Completed') DEFAULT 'Pending',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`deal_id`) REFERENCES `deals`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
        
        // Notas internas / Historial
        "CREATE TABLE `notes` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `deal_id` INT NOT NULL,
            `content` TEXT NOT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`deal_id`) REFERENCES `deals`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
        
        // Formularios Web de Leads (Fase 2)
        "CREATE TABLE `web_forms` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `title` VARCHAR(255) NOT NULL,
            `fields` TEXT NOT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
        
        // Sesiones de Chats (Fase 2)
        "CREATE TABLE `live_chats` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `session_key` VARCHAR(100) UNIQUE NOT NULL,
            `lead_name` VARCHAR(255) DEFAULT NULL,
            `lead_email` VARCHAR(255) DEFAULT NULL,
            `status` ENUM('Active', 'Closed') DEFAULT 'Active',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
        
        // Mensajes del Chat en Vivo (Fase 2)
        "CREATE TABLE `chat_messages` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `chat_id` INT NOT NULL,
            `sender` ENUM('Lead', 'Agent', 'AI') NOT NULL,
            `message` TEXT NOT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`chat_id`) REFERENCES `live_chats`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
        
        // Comunicaciones por Correo (Fase 2)
        "CREATE TABLE `emails` (
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
        
        // Plantillas de WhatsApp (Fase 2)
        "CREATE TABLE `whatsapp_templates` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `template_text` TEXT NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
        
        // Plantillas de Correo (Fase 2)
        "CREATE TABLE `email_templates` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `subject` VARCHAR(255) NOT NULL,
            `body` TEXT NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
        
        // Tabla de Ajustes / Configuraciones
        "CREATE TABLE `crm_settings` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `setting_key` VARCHAR(100) UNIQUE NOT NULL,
            `setting_value` VARCHAR(255) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

        // Historial de Facturas
        "CREATE TABLE `invoices` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `account_id` INT NOT NULL,
            `invoice_number` VARCHAR(100) UNIQUE NOT NULL,
            `amount` DECIMAL(12,2) NOT NULL,
            `due_date` DATE NOT NULL,
            `status` ENUM('Paid', 'Pending') DEFAULT 'Pending',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`account_id`) REFERENCES `accounts`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

        // Expediente de Documentos
        "CREATE TABLE `account_documents` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `account_id` INT NOT NULL,
            `filename` VARCHAR(255) NOT NULL,
            `category` VARCHAR(100) NOT NULL DEFAULT 'Otro',
            `uploaded_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`account_id`) REFERENCES `accounts`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

        // Consejos del Sales Coach de Brian Tracy
        "CREATE TABLE `sales_tips` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `category` VARCHAR(50) NOT NULL,
            `tip_text` TEXT NOT NULL,
            `author` VARCHAR(100) DEFAULT 'Brian Tracy'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

        // Usuarios del CRM (login) — NO se borra al reinstalar
        "CREATE TABLE IF NOT EXISTS `crm_users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `username` VARCHAR(100) UNIQUE NOT NULL,
            `password_hash` VARCHAR(255) NOT NULL,
            `role` ENUM('admin', 'agent') DEFAULT 'agent',
            `assigned_agent_name` VARCHAR(100) DEFAULT NULL,
            `full_name` VARCHAR(150) DEFAULT NULL,
            `email` VARCHAR(255) DEFAULT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

        // Restablecimiento de contraseña
        "CREATE TABLE IF NOT EXISTS `crm_password_resets` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `username` VARCHAR(100) NOT NULL,
            `token` VARCHAR(100) NOT NULL,
            `expires_at` DATETIME NOT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

        // Definición de Campos Personalizados
        "CREATE TABLE `custom_field_definitions` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `field_type` ENUM('text', 'number', 'date', 'select') NOT NULL,
            `options` TEXT DEFAULT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

        // Valores de Campos Personalizados
        "CREATE TABLE `deal_custom_values` (
            `deal_id` INT NOT NULL,
            `field_id` INT NOT NULL,
            `value` TEXT DEFAULT NULL,
            PRIMARY KEY (`deal_id`, `field_id`),
            FOREIGN KEY (`deal_id`) REFERENCES `deals`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`field_id`) REFERENCES `custom_field_definitions`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

        // Motor de Automatizaciones (Workflow Automation)
        "CREATE TABLE `crm_automations` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `title` VARCHAR(150) NOT NULL,
            `trigger_event` VARCHAR(50) NOT NULL DEFAULT 'stage_change',
            `trigger_value` VARCHAR(100) NOT NULL,
            `action_type` VARCHAR(50) NOT NULL DEFAULT 'create_task',
            `action_payload` TEXT NOT NULL,
            `is_active` TINYINT DEFAULT 1,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
    ];

    foreach ($sql_tables as $sql) {
        $pdo->exec($sql);
    }
    echo "<div class='step success'>✔️ Estructura de tablas consolidada (Fase 1, 2 y BANT) creada con éxito.</div>";

    // Sembrar usuarios (admin y agentes) si no existe ninguno
    if ($pdo->query("SELECT COUNT(*) FROM `crm_users` WHERE `username` = 'napoleon'")->fetchColumn() == 0) {
        $temp_password = 'tips2026';
        $hash = password_hash($temp_password, PASSWORD_DEFAULT);
        
        // Sembrar administrador
        $stmt_user = $pdo->prepare("INSERT INTO `crm_users` (`username`, `password_hash`, `role`, `assigned_agent_name`, `full_name`, `email`) VALUES (?, ?, 'admin', NULL, ?, ?)");
        $stmt_user->execute(['napoleon', $hash, 'Napoleón Contreras', 'napoleon@tipscr.com']);
        
        // Sembrar agentes
        $stmt_agent = $pdo->prepare("INSERT INTO `crm_users` (`username`, `password_hash`, `role`, `assigned_agent_name`, `full_name`, `email`) VALUES (?, ?, 'agent', ?, ?, ?)");
        
        $stmt_agent->execute(['andres', $hash, 'Andrés Herrera (GAM Norte)', 'Andrés Herrera', 'aherrera@tips.cr']);
        $stmt_agent->execute(['carlos', $hash, 'Carlos Mendoza (Zona Costa)', 'Carlos Mendoza', 'cmendoza@tips.cr']);
        $stmt_agent->execute(['sofia', $hash, 'Sofía Castro (GAM Oriente)', 'Sofía Castro', 'scastro@tips.cr']);
        
        echo "<div class='step success'>✔️ Usuario de acceso administrador (napoleon) y cuentas de asesores comerciales (andres, carlos, sofia) creadas con contraseña temporal común: <strong>{$temp_password}</strong>.</div>";
    }

    // Sembrar pipelines por defecto
    $pdo->exec("INSERT INTO `pipelines` (`id`, `name`) VALUES 
        (1, 'Embudo de Ventas Principal'),
        (2, 'Logística y Despacho')
    ");
    echo "<div class='step success'>✔️ Pipelines por defecto sembrados.</div>";

    // 3. Insertar Etapas del Embudo (Stages)
    $stages = [
        // Embudo 1: Ventas Principal
        ['Contacto Inicial', 1, 1, 10],
        ['Calificación BANT', 2, 1, 30],
        ['Demostración / Muestras', 3, 1, 50],
        ['Propuesta Comercial', 4, 1, 75],
        ['Negociación y Cierre', 5, 1, 90],
        // Embudo 2: Logística y Despacho
        ['Pedido Recibido', 1, 2, 20],
        ['Preparación en Bodega', 2, 2, 40],
        ['En Ruta de Entrega', 3, 2, 70],
        ['Entregado y Confirmado', 4, 2, 100]
    ];
    $stmt_stages = $pdo->prepare("INSERT INTO `stages` (`name`, `position`, `pipeline_id`, `win_probability`) VALUES (?, ?, ?, ?)");
    foreach ($stages as $stage) {
        $stmt_stages->execute($stage);
    }
    echo "<div class='step success'>✔️ Etapas del embudo configuradas para múltiples pipelines con probabilidades de cierre.</div>";

    // Sembrar algunos campos personalizados por defecto
    $stmt_custom = $pdo->prepare("INSERT INTO `custom_field_definitions` (`name`, `field_type`, `options`) VALUES (?, ?, ?)");
    $stmt_custom->execute(['Origen del Lead', 'select', 'Google Ads, Facebook Ads, WhatsApp, Formulario Web, Recomendación']);
    $stmt_custom->execute(['Fecha de Entrega Estimada', 'date', null]);
    $stmt_custom->execute(['Requerimientos Técnicos', 'text', null]);
    echo "<div class='step success'>✔️ Campos personalizados por defecto creados.</div>";

    // 4. Seeding de Sectores Industriales
    $default_sectors = [
        ['Pastelería', 30],
        ['Panificadora', 40],
        ['HORECA', 50],
        ['Cafetería', 20],
        ['Distribuidora B2B', 35],
        ['Supermercado', 45],
        ['Catering Service', 25],
        ['Academia / Educación', 15],
        ['Institucional', 30]
    ];
    $stmt_ind = $pdo->prepare("INSERT INTO `industries` (`name`, `scoring_points`) VALUES (?, ?)");
    foreach ($default_sectors as $sector) {
        $stmt_ind->execute($sector);
    }
    echo "<div class='step success'>✔️ Sectores industriales iniciales y ampliados insertados.</div>";

    // 5. Insertar Cuentas B2B Robustas (Empresas corporativas reales/franquicias de Costa Rica)
    $accounts = [
        ['Pastelería Spoon S.A. (Oficinas Centrales)', 'Pastelería', '2253-1515', 'proveedores@spoon.cr', 'San Pedro, 200m Sur de la Iglesia', 'San José'],
        ['Panadería y Repostería Musmanni (Franquicia Pavas)', 'Panificadora', '2231-1000', 'compras@musmannipavas.cr', 'Pavas, 400m Oeste de la Embajada Americana', 'San José'],
        ['Hotel Villa Caletas & Zephyr Palace Resort', 'HORECA', '2637-0600', 'fbmanager@villacaletas.com', 'Carretera a Jacó, Km 5', 'Puntarenas'],
        ['Café Britt Costa Rica S.A.', 'Cafetería', '2277-1600', 'adquisiciones@britt.com', 'Heredia Centro, 1km Norte de la Universidad Nacional', 'Heredia'],
        ['Cakeland Repostería Creativa', 'Pastelería', '2288-4455', 'info@cakeland.cr', 'Escazú, Centro Comercial Los Laureles', 'San José'],
        ['Panificadora Industrial El Pan Nuestro', 'Panificadora', '2441-9090', 'compras@elpannuestro.cr', 'El Coyol, Parque Industrial Logístico Bodega A4', 'Alajuela'],
        ['Pastelería Dulce Capricho (Mariana López)', 'Pastelería', '8811-2233', 'mariana@dulcecapricho.cr', 'Guadalupe, contiguo al Centro Comercial', 'San José']
    ];
    $stmt_acc = $pdo->prepare("INSERT INTO `accounts` (`name`, `industry`, `phone`, `email`, `address`, `city`) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($accounts as $acc) {
        $stmt_acc->execute($acc);
    }
    echo "<div class='step success'>✔️ Cuentas comerciales robustas insertadas.</div>";

    // 5. Insertar Contactos Decisores Vinculados
    $contacts = [
        [1, 'Sofía', 'Castro', 'Directora de Compras Corporativas', '2253-1515', 'scastro@spoon.cr'],
        [2, 'Gerardo', 'Solís', 'Gerente de Franquicia y Operaciones', '8700-1122', 'gsolis@musmannipavas.cr'],
        [3, 'Esteban', 'Guevara', 'Chef Pastelero Ejecutivo', '8333-4455', 'eguevara@villacaletas.com'],
        [4, 'Carolina', 'Monge', 'Jefa de Abastecimiento', '8444-9900', 'cmonge@britt.com'],
        [5, 'Rebeca', 'Madrigal', 'Encargada de Compras', '8999-1122', 'rebeca@cakeland.cr'],
        [6, 'Carlos', 'Villalobos', 'Gerente de Planta Industrial', '8765-4321', 'carlos@elpannuestro.cr'],
        [7, 'Mariana', 'López', 'Chef Propietaria', '8811-2233', 'mariana@dulcecapricho.cr']
    ];
    $stmt_con = $pdo->prepare("INSERT INTO `contacts` (`account_id`, `first_name`, `last_name`, `job_title`, `phone`, `email`) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($contacts as $con) {
        $stmt_con->execute($con);
    }
    echo "<div class='step success'>✔️ Contactos de decisión clave vinculados a sus cuentas corporativas.</div>";

    // 6. Insertar Oportunidades (Deals) con calificación BANT y MEDDIC detallada
    $deals = [
        // Suministro Anual Fondant Spoon
        ['Suministro Mensual de Fondant Enco (Contrato Anual)', 18400.00, 4, 1, 1, 'Open', 1, 1, 1, 0, 'Spoon requiere fondant resistente a climas húmedos en sucursales de Alajuela y Heredia.', 1, 1, 0, 0, 1, 1, date('Y-m-d', strtotime('+30 days')), 'Sofía Castro (GAM Oriente)'],
        // Batidoras KitchenAid Villa Caletas
        ['Equipamiento Batidoras Industriales KitchenAid 8Qt (Lote 5 uds)', 6250.00, 3, 3, 3, 'Open', 1, 0, 1, 1, 'Chef de repostería de Villa Caletas pre-aprobó la compra, requiere demo física.', 1, 0, 1, 0, 1, 1, date('Y-m-d', strtotime('+15 days')), 'Carlos Mendoza (Zona Costa)'],
        // Surtido Completo Colorantes Cakeland
        ['Lote Inicial Colorantes en Gel Enco (Lanzamiento de Temporada)', 1450.00, 5, 5, 5, 'Open', 1, 1, 1, 1, 'Apertura de nueva sucursal Cakeland. Requieren stock inicial inmediato.', 1, 1, 1, 1, 1, 1, date('Y-m-d', strtotime('+5 days')), 'Sofía Castro (GAM Oriente)'],
        // Proyecto Hornos Convección El Pan Nuestro
        ['Hornos de Convección Rotativos Unoox (Proyecto Nueva Planta)', 24500.00, 2, 6, 6, 'Open', 0, 1, 1, 0, 'Ingeniero de planta solicita planos técnicos. Falta aprobación presupuestaria.', 0, 1, 0, 0, 1, 0, date('Y-m-d', strtotime('+60 days')), 'Andrés Herrera (GAM Norte)'],
        // Utensilios Wilton Musmanni
        ['Pedido Wilton Utensilios de Decoración al por Mayor', 920.00, 1, 2, 2, 'Open', 0, 0, 0, 0, 'Primer contacto. Solicitan catálogo mayorista de moldes Wilton.', 0, 0, 0, 0, 0, 0, date('Y-m-d', strtotime('+25 days')), 'Andrés Herrera (GAM Norte)'],
        // Colorantes Dulce Capricho - Ganado
        ['Lote Colorantes Metálicos Enco (Promoción de Navidad)', 850.00, 5, 7, 7, 'Won', 1, 1, 1, 1, 'Cerrado y pagado. Despacho coordinado vía Correos de CR.', 1, 1, 1, 1, 1, 1, date('Y-m-d', strtotime('-5 days')), 'Carlos Mendoza (Zona Costa)'],
        // Despacho Batidoras Musmanni (Logística)
        ['Despacho Batidoras Musmanni', 6250.00, 6, 2, 2, 'Open', 1, 1, 1, 1, 'Orden de compra recibida. Pendiente de validación de crédito en bodega.', 1, 1, 1, 1, 1, 1, date('Y-m-d', strtotime('+4 days')), 'Carlos Mendoza (Zona Costa)'],
        // Envío Lote Colorantes Spoon (Logística)
        ['Envío Lote Colorantes Spoon', 1840.00, 7, 1, 1, 'Open', 1, 1, 1, 1, 'Lote preparado en palets de plástico en la bodega central de TIPS.', 1, 1, 1, 1, 1, 1, date('Y-m-d', strtotime('+2 days')), 'Sofía Castro (GAM Oriente)'],
        // Suministro Fondant Café Britt (Logística)
        ['Suministro Fondant Café Britt', 3450.00, 8, 4, 4, 'Open', 1, 1, 1, 1, 'En camión repartidor de TIPS ruta GAM Norte.', 1, 1, 1, 1, 1, 1, date('Y-m-d', strtotime('+1 days')), 'Andrés Herrera (GAM Norte)'],
        // Entrega Wilton Moldes Cakeland (Logística)
        ['Entrega Wilton Moldes Cakeland', 1200.00, 9, 5, 5, 'Won', 1, 1, 1, 1, 'Entregado y firmado por el encargado de repostería.', 1, 1, 1, 1, 1, 1, date('Y-m-d', strtotime('-2 days')), 'Sofía Castro (GAM Oriente)']
    ];
    
    $stmt_deal = $pdo->prepare("INSERT INTO `deals` 
        (`title`, `value`, `stage_id`, `account_id`, `contact_id`, `status`, `bant_budget`, `bant_authority`, `bant_need`, `bant_timeline`, `bant_notes`, `close_date`, `assigned_agent`,
         `meddic_metrics`, `meddic_buyer`, `meddic_criteria`, `meddic_process`, `meddic_pain`, `meddic_champion`) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($deals as $deal) {
        $stmt_deal->execute($deal);
    }
    echo "<div class='step success'>✔️ 10 Oportunidades comerciales para múltiples embudos y regiones inyectadas.</div>";

    // Sembrar valores de campos personalizados para los deals
    $stmt_cv = $pdo->prepare("INSERT INTO `deal_custom_values` (`deal_id`, `field_id`, `value`) VALUES (?, ?, ?)");
    $custom_vals = [
        [1, 1, 'WhatsApp'],
        [1, 2, date('Y-m-d', strtotime('+40 days'))],
        [1, 3, 'Fondant Enco de formulación ultra-resistente a la humedad'],
        [2, 1, 'Recomendación'],
        [2, 2, date('Y-m-d', strtotime('+20 days'))],
        [2, 3, 'Batidoras KitchenAid modelo comercial 8Qt de tazón elevable'],
        [3, 1, 'Google Ads'],
        [3, 2, date('Y-m-d', strtotime('+7 days'))],
        [3, 3, 'Kit completo de colorantes en gel y metálicos Enco'],
        [4, 1, 'Formulario Web'],
        [4, 2, date('Y-m-d', strtotime('+90 days'))],
        [4, 3, 'Hornos Unoox trifásicos rotativos'],
        [7, 1, 'Recomendación'],
        [7, 2, date('Y-m-d', strtotime('+5 days'))],
        [7, 3, 'Logística: Distribución a sucursales de Herradura'],
        [8, 1, 'WhatsApp'],
        [8, 2, date('Y-m-d', strtotime('+3 days'))],
        [8, 3, 'Logística: Fraccionado en cajas de 12 unidades'],
        [9, 1, 'Formulario Web'],
        [9, 2, date('Y-m-d', strtotime('+1 days'))],
        [9, 3, 'Logística: Entrega en Centro de Distribución Britt Heredia']
    ];
    foreach ($custom_vals as $cv) {
        $stmt_cv->execute($cv);
    }
    echo "<div class='step success'>✔️ Valores de campos personalizados de prueba vinculados.</div>";

    // 7. Insertar Notas Históricas robustas en las oportunidades (Notes log)
    $notes = [
        [1, "Reunión inicial con Licda. Sofía Castro de Spoon. Están buscando cambiar de proveedor de fondant debido a que el fondant actual se agrieta en sus locales costeros. Les interesa la resistencia de Enco a la humedad de Costa Rica."],
        [1, "Se enviaron muestras de 5kg de fondant blanco Enco a las cocinas centrales en San Pedro. El chef aprobó la elasticidad y el secado uniforme. Enviando propuesta de cotización anual."],
        [2, "El Chef Esteban Guevara indica que sus batidoras de 5Qt actuales sufren sobrecalentamiento. Requieren batidoras de 8Qt con protección térmica. Programada demostración física para el próximo martes."],
        [4, "Reunión en la planta de El Pan Nuestro en El Coyol. El Ing. Carlos Villalobos nos muestra la ampliación de la bodega. Los hornos Unoox de inyección de vapor directo son ideales. Quedaron de confirmar presupuesto con la junta directiva en Panamá."],
        [6, "Factura proforma #9928 firmada y aprobada por Mariana López de Dulce Capricho. Pago recibido por transferencia bancaria (BAC). Entrega completada."]
    ];
    $stmt_note = $pdo->prepare("INSERT INTO `notes` (`deal_id`, `content`) VALUES (?, ?)");
    foreach ($notes as $note) {
        $stmt_note->execute($note);
    }
    echo "<div class='step success'>✔️ Bitácoras e historial de notas comerciales cargadas en los deals.</div>";

    // 8. Insertar Actividades de la Agenda
    $activities = [
        [1, 'Email', 'Enviar acuerdo comercial y precios de escala para Spoon', 'Adjuntar el tarifario B2B y contrato marco de suministro.', date('Y-m-d H:i:s', strtotime('+1 day 09:00:00')), 'Pending'],
        [2, 'Meeting', 'Demo física de batidora KitchenAid 8Qt en Villa Caletas', 'Llevar insumos de repostería para batir en vivo en sus cocinas.', date('Y-m-d H:i:s', strtotime('+2 days 14:30:00')), 'Pending'],
        [3, 'Call', 'Confirmar hora de entrega de pedido Cakeland', 'Coordinar despacho directo con chofer de TIPS.', date('Y-m-d H:i:s', strtotime('today 11:00:00')), 'Pending'],
        [4, 'Call', 'Dar seguimiento al plano eléctrico de hornos Unoox', 'Consultar a Carlos si el electricista validó la acometida.', date('Y-m-d H:i:s', strtotime('+4 days 09:30:00')), 'Pending'],
        [5, 'Task', 'Revisar catálogo de moldes Wilton navideños', 'Confirmar stock disponible en bodega central antes de enviar propuesta.', date('Y-m-d H:i:s', strtotime('today 16:00:00')), 'Pending'],
        [6, 'Call', 'Seguimiento post-venta Dulce Capricho', 'Confirmar si los colorantes llegaron a tiempo y en buen estado.', date('Y-m-d H:i:s', strtotime('-2 days 10:00:00')), 'Completed'],
        [7, 'Task', 'Validación de crédito Musmanni', 'Revisar con contabilidad si se aprobó el crédito para despacho.', date('Y-m-d H:i:s', strtotime('+1 days 09:00:00')), 'Pending'],
        [8, 'Samples', 'Preparar muestras Spoon', 'Asegurar que el lote de fondant esté paletizado y listo.', date('Y-m-d H:i:s', strtotime('+1 days 11:00:00')), 'Pending'],
        [9, 'Technical_Visit', 'Entrega y acompañamiento Britt', 'Asistir en el centro de distribución de Britt Heredia.', date('Y-m-d H:i:s', strtotime('today 10:00:00')), 'Pending'],
        [10, 'Call', 'Confirmar recepción Cakeland', 'Llamar al cliente para verificar firma de recibido.', date('Y-m-d H:i:s', strtotime('-1 days 14:00:00')), 'Completed'],
        [1, 'Call', 'Llamada de seguimiento Spoon', 'Llamada rápida para ver avances.', date('Y-m-d H:i:s', strtotime('+3 days 10:00:00')), 'Pending'],
        [2, 'Call', 'Coordinar demo Villa Caletas', 'Llamar para definir hora exacta.', date('Y-m-d H:i:s', strtotime('+1 days 15:00:00')), 'Pending'],
        [3, 'Meeting', 'Reunión comercial Cakeland', 'Cerrar contrato de volumen.', date('Y-m-d H:i:s', strtotime('+4 days 11:00:00')), 'Pending'],
        [4, 'Email', 'Enviar especificaciones Unoox', 'Planos de acometida eléctrica.', date('Y-m-d H:i:s', strtotime('+2 days 09:00:00')), 'Pending'],
        [7, 'Call', 'Coordinar transporte Musmanni', 'Llamar al chofer para confirmar ruta Herradura.', date('Y-m-d H:i:s', strtotime('+2 days 10:00:00')), 'Pending']
    ];
    $stmt_act = $pdo->prepare("INSERT INTO `activities` (`deal_id`, `type`, `subject`, `description`, `due_date`, `status`) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($activities as $act) {
        $stmt_act->execute($act);
    }
    echo "<div class='step success'>✔️ Agenda de actividades y seguimientos comerciales programada.</div>";

    // 9. Plantillas de WhatsApp
    $pdo->exec("INSERT INTO `whatsapp_templates` (`name`, `template_text`) VALUES 
        ('Contacto Inicial B2B', 'Hola {{name}}, un gusto saludarte. Te escribo de TIPS S.A. sobre tu consulta por colorantes Enco. ¿Cómo podemos ayudarte hoy?'),
        ('Envío de Ficha / Cotización', 'Hola {{name}}. Te comparto la cotización del lote {{deal_title}} por un valor de {{value}}. Quedo atento a tus comentarios.'),
        ('Seguimiento Demo Batidora', 'Hola {{name}}. ¿Qué tal te pareció el rendimiento de la batidora KitchenAid? Contame si coordinamos la entrega del equipo.')
        ON DUPLICATE KEY UPDATE id=id;");
    echo "<div class='step success'>✔️ Plantillas rápidas de WhatsApp B2B creadas.</div>";

    // 10. Formularios Web de Leads
    $pdo->exec("INSERT INTO `web_forms` (`title`, `fields`) VALUES 
        ('Contacto Web Enco Alimentos', '[\"name\", \"email\", \"phone\", \"company\", \"message\"]')
        ON DUPLICATE KEY UPDATE id=id;");
    echo "<div class='step success'>✔️ Formulario de captura de leads web inicializado.</div>";

    // 11. Correos Electrónicos
    $emails = [
        [1, 'Sent', 'asesor@tipscr.com', 'scastro@spoon.cr', 'Cotización Muestras Fondant Enco — TIPS', "Estimada Sofía,\n\nAdjunto los detalles técnicos y la propuesta para el suministro del fondant Enco. Como acordamos, el precio por volumen aplica a partir de compras de 50 kg mensuales.\n\nSaludos,\nAsistente Comercial TIPS"],
        [1, 'Received', 'scastro@spoon.cr', 'asesor@tipscr.com', 'Re: Cotización Muestras Fondant Enco — TIPS', "Hola,\n\nRecibido. Las muestras de fondant se probaron en nuestra cocina central en San Pedro y el resultado de elasticidad en clima húmedo fue excelente. Estaré pasándolo a aprobación de junta directiva el viernes.\n\nAtentamente,\nSofía Castro - Compras Spoon"]
    ];
    $stmt_email = $pdo->prepare("INSERT INTO `emails` (`deal_id`, `direction`, `sender`, `recipient`, `subject`, `body`) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($emails as $em) {
        $stmt_email->execute($em);
    }
    echo "<div class='step success'>✔️ Historial de correspondencia por correo electrónico cargado.</div>";

    // 11.5 Plantillas de Correo B2B
    $email_tpls = [
        [
            "[Contacto Inicial] Presentación de TIPS y Catálogo Enco",
            "Catálogo de Insumos y Equipos Comerciales — TIPS Costa Rica",
            "Hola {{contact_name}},\n\nUn gusto saludarte de TIPS S.A. Adjunto a este correo encontrarás nuestro catálogo completo de colorantes y fondant Enco Alimentos, así como nuestra línea comercial KitchenAid.\n\nContame si tienes alguna duda sobre precios por volumen para {{company_name}}."
        ],
        [
            "[Calificación] Consultas de Requerimientos Técnicos",
            "Requerimientos Técnicos y Presupuesto — TIPS CRM",
            "Hola {{contact_name}},\n\nPara poder prepararte una propuesta óptima para tu negocio {{company_name}}, quisiéramos consultar:\n1. ¿Cuál es el plazo estimado de entrega que manejan?\n2. ¿Quién sería la persona encargada de firmar la orden de compra?\n\nQuedamos atentos para asesorarte."
        ],
        [
            "[Demostración] Coordinación de Pruebas Físicas",
            "Coordinación de Pruebas de Equipo en Cocina — TIPS",
            "Hola {{contact_name}},\n\nConfirmamos la preparación del equipo de demostración (batidora KitchenAid 8Qt Commercial) para realizar pruebas de batido pesado en tu local de {{company_name}}.\n\n¿Te queda bien coordinar la visita técnica para este próximo martes a las 14:00?"
        ],
        [
            "[Propuesta] Envío de Cotización Formal",
            "Cotización Comercial y Ficha Técnica — TIPS B2B",
            "Hola {{contact_name}},\n\nAdjunto comparto la cotización formal por un valor de {{deal_value}} correspondiente al equipamiento de {{company_name}}.\n\nContamos con opciones de crédito B2B BAC Credomatic y financiamiento TIPS previa aprobación."
        ],
        [
            "[Negociación/Cierre] Instrucciones de Pago y Coordinación",
            "Instrucciones de Pago y Coordinación de Logística — TIPS",
            "Hola {{contact_name}},\n\n¡Un gusto avanzar con tu compra para {{company_name}}! Adjunto encontrarás nuestras cuentas bancarias (BAC / BCR) para realizar la transferencia de {{deal_value}}.\n\nUna vez enviado el comprobante de pago, nuestro camión despachará tu pedido en un plazo máximo de 24 horas hábiles."
        ]
    ];
    $stmt_etpl = $pdo->prepare("INSERT INTO `email_templates` (`name`, `subject`, `body`) VALUES (?, ?, ?)");
    foreach ($email_tpls as $et) {
        $stmt_etpl->execute($et);
    }
    echo "<div class='step success'>✔️ Plantillas de correo B2B iniciales insertadas.</div>";

    // 12. Chats en Vivo
    $pdo->exec("INSERT INTO `live_chats` (`session_key`, `lead_name`, `lead_email`, `status`) VALUES 
        ('session_key_test_spoon', 'Clara Herrero', 'cherrero@pasteleriaraices.cr', 'Active')");
    $chat_id = $pdo->lastInsertId();
    
    $pdo->exec("INSERT INTO `chat_messages` (`chat_id`, `sender`, `message`) VALUES 
        ($chat_id, 'Lead', 'Hola, ¿tienen colorante Enco en gel rojo brillante en presentación de 250g?'),
        ($chat_id, 'AI', '¡Hola! Sí, claro. En TIPS Costa Rica distribuimos los colorantes Enco en gel. Tenemos la presentación de 250g en color rojo brillante ideal para repostería de alto volumen. ¿Te gustaría que un asesor comercial te envíe los precios B2B y coordine el despacho?')");
    echo "<div class='step success'>✔️ Sesión de Chat en Vivo y mensajes iniciales inyectados.</div>";

    // 13. Ajustes y metas del equipo
    $pdo->exec("INSERT INTO `crm_settings` (`setting_key`, `setting_value`) VALUES 
        ('sales_quota_target', '30000'),
        ('crm_name', 'TIPS CRM B2B'),
        ('notification_email', 'alertas@tips.cr'),
        ('openai_api_key', ''),
        ('openai_model', 'gpt-4o-mini'),
        ('serpapi_key', ''),
        ('scoring_budget', '25'),
        ('scoring_authority', '25'),
        ('scoring_need', '25'),
        ('scoring_timeline', '25'),
        ('qualification_framework', 'BANT'),
        ('scoring_meddic_metrics', '15'),
        ('scoring_meddic_buyer', '20'),
        ('scoring_meddic_criteria', '15'),
        ('scoring_meddic_process', '15'),
        ('scoring_meddic_pain', '20'),
        ('scoring_meddic_champion', '15')
        ON DUPLICATE KEY UPDATE `setting_value`=VALUES(`setting_value`);");
    echo "<div class='step success'>✔️ Ajustes por defecto del CRM y pesos de Lead Scoring establecidos.</div>";

    // 14. Enrutamiento automático de Leads (Semilla)
    $pdo->exec("UPDATE `accounts` SET `assigned_agent` = 'Sofía Castro (GAM Oriente)' WHERE `city` IN ('San José', 'Cartago');");
    $pdo->exec("UPDATE `accounts` SET `assigned_agent` = 'Carlos Mendoza (Zona Costa)' WHERE `city` IN ('Puntarenas', 'Guanacaste');");
    $pdo->exec("UPDATE `accounts` SET `assigned_agent` = 'Andrés Herrera (GAM Norte)' WHERE `city` IN ('Heredia', 'Alajuela', 'Limón');");
    $pdo->exec("UPDATE `deals` d JOIN `accounts` a ON d.account_id = a.id SET d.assigned_agent = a.assigned_agent;");
    
    // Semillar datos financieros adicionales
    $pdo->exec("UPDATE `accounts` SET `credit_limit` = 15000.00, `credit_balance` = 4500.00, `credit_terms` = 'Crédito 30 días (BAC)' WHERE `name` LIKE '%Spoon%';");
    $pdo->exec("UPDATE `accounts` SET `credit_limit` = 10000.00, `credit_balance` = 0.00, `credit_terms` = 'Crédito 15 días (BCR)' WHERE `name` LIKE '%Musmanni%';");
    $pdo->exec("UPDATE `accounts` SET `credit_limit` = 25000.00, `credit_balance` = 12300.00, `credit_terms` = 'TIPS Financiamiento Neto 30' WHERE `name` LIKE '%Villa Caletas%';");
    $pdo->exec("UPDATE `accounts` SET `credit_limit` = 5000.00, `credit_balance` = 0.00, `credit_terms` = 'Contado' WHERE `name` LIKE '%Britt%';");
    
    $pdo->exec("INSERT INTO `invoices` (`account_id`, `invoice_number`, `amount`, `due_date`, `status`) VALUES 
        (1, 'FACT-2026-001', 3500.00, '" . date('Y-m-d', strtotime('-45 days')) . "', 'Paid'),
        (1, 'FACT-2026-002', 4500.00, '" . date('Y-m-d', strtotime('+15 days')) . "', 'Pending'),
        (2, 'FACT-2026-003', 2800.00, '" . date('Y-m-d', strtotime('-10 days')) . "', 'Paid'),
        (3, 'FACT-2026-004', 8500.00, '" . date('Y-m-d', strtotime('-35 days')) . "', 'Paid'),
        (3, 'FACT-2026-005', 12300.00, '" . date('Y-m-d', strtotime('+5 days')) . "', 'Pending')
    ");
    
    $pdo->exec("INSERT INTO `account_documents` (`account_id`, `filename`, `category`) VALUES 
        (1, 'contrato_distribucion_spoon_2026.pdf', 'Contrato B2B'),
        (1, 'requisitos_tecnicos_acometida.pdf', 'Ficha Técnica'),
        (3, 'licitacion_compras_caletas.pdf', 'Contrato B2B'),
        (3, 'cotizacion_horno_unoox_firmada.pdf', 'Cotización')
    ");
    
    echo "<div class='step success'>✔️ Facturación, límites de crédito y expedientes digitales semillas inyectados.</div>";

    // 15. Inyectar Consejos del Sales Coach de Brian Tracy
    $stmt_tip = $pdo->prepare("INSERT INTO `sales_tips` (`category`, `tip_text`, `author`) VALUES (?, ?, ?)");
    $tips_seeds = [
        ['Motivation', 'El único límite para tus logros del mañana son tus dudas de hoy. Vende con absoluta confianza en tus productos.', 'Brian Tracy'],
        ['Motivation', 'Los vendedores exitosos están orientados a la acción. Cuando se les ocurre una gran idea, la ejecutan de inmediato.', 'Brian Tracy'],
        ['Motivation', 'Tu nivel de auto-estima es el determinante clave de tu rendimiento y eficacia en todas las áreas de tus ventas.', 'Brian Tracy'],
        ['Follow-up', 'El 80% de las ventas corporativas se cierran después del quinto seguimiento. La perseverancia distingue a los líderes de ventas.', 'Brian Tracy'],
        ['Follow-up', 'Nunca asumas que un silencio o postergación del cliente es un "No". Mantén una presencia constante y con valor agregado.', 'Brian Tracy'],
        ['Closing', 'No intentes vender el producto. Vende la solución al problema. Al chef no le importa la batidora, le importa la suavidad de su batido.', 'Brian Tracy'],
        ['Closing', 'Hacer preguntas inteligentes y escuchar con atención es el camino más directo para ganarse la confianza del comprador.', 'Brian Tracy'],
        ['BANT', 'Si el cliente no tiene un presupuesto calificado para pagar un equipo Unoox, no es un prospecto. Califica temprano.', 'Brian Tracy'],
        ['BANT', 'Identifica siempre al decisor final con autoridad de firma. No desgastes tu energía vendiéndole a quien no puede decidir.', 'Brian Tracy'],
        ['BANT', 'La necesidad lo es todo. Si tu cliente no ve un problema urgente en su cocina industrial, no habrá interés ni trato comercial.', 'Brian Tracy'],
        ['Time_Management', 'Dedica el 80% de tu jornada comercial a prospectar y presentar soluciones, y deja las tareas administrativas para el final.', 'Brian Tracy'],
        ['Time_Management', 'Planifica tu agenda y tu ruta de visitas técnicas la noche anterior. Iniciar el día con un plan claro multiplica tus cierres.', 'Brian Tracy'],
        
        // Semillas de T. Harv Eker ("Los Secretos de la Mente Millonaria")
        ['Motivation', 'La gente rica promueve su valor con pasión y entusiasmo. Si crees en tu solución de cocina TIPS, preséntala con orgullo.', 'T. Harv Eker'],
        ['Motivation', 'Tu patrón financiero determinará tu destino. Condiciona tu mente para ver abundancia y grandes tratos en cada negociación.', 'T. Harv Eker'],
        ['Motivation', 'Si quieres cambiar los frutos, primero tendrás que modificar las raíces. Trabaja en tu mentalidad antes de ir a vender.', 'T. Harv Eker'],
        ['Motivation', 'La queja es el mayor imán para los problemas. Enfócate en las oportunidades del mercado B2B, no en los obstáculos.', 'T. Harv Eker'],
        ['Closing', 'Los profesionales de alto nivel no temen a la venta ni a la promoción. Saben que vender es educar y servir al cliente.', 'T. Harv Eker'],
        ['Time_Management', 'La gente rica se enfoca en construir su patrimonio neto; los aficionados en sus ingresos por hora. Invierte tu tiempo en cuentas B2B recurrentes.', 'T. Harv Eker']
    ];
    foreach ($tips_seeds as $ts) {
        $stmt_tip->execute($ts);
    }
    echo "<div class='step success'>✔️ Consejos y guías estratégicas de Brian Tracy inyectados en la base de datos.</div>";

    // 16. Inyectar Automatizaciones por defecto
    $stage_samples_id = $pdo->query("SELECT id FROM stages WHERE name LIKE '%Muestras%' OR name LIKE '%Demostración%'")->fetchColumn() ?: 4;
    $stage_negotiation_id = $pdo->query("SELECT id FROM stages WHERE name LIKE '%Negociación%' OR name LIKE '%Propuesta%'")->fetchColumn() ?: 5;

    $stmt_auto = $pdo->prepare("INSERT INTO `crm_automations` (`title`, `trigger_event`, `trigger_value`, `action_type`, `action_payload`, `is_active`) VALUES (?, ?, ?, ?, ?, 1)");
    
    $stmt_auto->execute([
        'Seguimiento de Muestras Enco/KitchenAid',
        'stage_change',
        (string)$stage_samples_id,
        'create_task',
        json_encode([
            'type' => 'Samples',
            'due_in_days' => 2,
            'title' => 'Coordinar entrega de muestras Enco/KitchenAid al cliente'
        ], JSON_UNESCAPED_UNICODE)
    ]);
    
    $stmt_auto->execute([
        'Revisión de Propuesta Comercial TIPS',
        'stage_change',
        (string)$stage_negotiation_id,
        'create_task',
        json_encode([
            'type' => 'Meeting',
            'due_in_days' => 3,
            'title' => 'Revisar propuesta final con el decisor de compras'
        ], JSON_UNESCAPED_UNICODE)
    ]);
    
    echo "<div class='step success'>✔️ Reglas de automatización del flujo de ventas (SOP) inyectadas.</div>";

    echo "<p style='margin-top: 1.5rem; color: #10b981; font-weight: 600;'>¡CRM TIPS reinstalado y robustecido con características Premium B2B!</p>";
    echo "<a href='panel.php' class='btn-go'>Ir al Dashboard del CRM</a>";

} catch (Exception $e) {
    echo "<div class='step' style='border-left-color: #ef4444; color: #fca5a5;'>❌ Error durante la instalación: " . $e->getMessage() . "</div>";
}

echo "
</div>
</body>
</html>";

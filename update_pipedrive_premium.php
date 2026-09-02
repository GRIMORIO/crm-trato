<?php
/**
 * update_pipedrive_premium.php — Parche de base de datos para habilitar características Premium de Pipedrive
 */

require_once __DIR__ . '/config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>TIPS CRM — Pipedrive Premium Update</title>
    <link href='https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap' rel='stylesheet'>
    <style>
        body { background-color: #070a13; color: #f1f5f9; font-family: 'Outfit', sans-serif; padding: 3rem 1rem; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .setup-card { background: rgba(18, 24, 48, 0.65); border: 1px solid rgba(255, 255, 255, 0.08); backdrop-filter: blur(16px); border-radius: 16px; padding: 2.5rem; max-width: 600px; width: 100%; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5); }
        h1 { color: #06b6d4; font-size: 1.75rem; margin-bottom: 0.5rem; border-bottom: 2px dashed rgba(6, 182, 212, 0.2); padding-bottom: 0.5rem; }
        .step { margin: 1rem 0; padding-left: 1.5rem; border-left: 3px solid #06b6d4; font-size: 0.95rem; }
        .step.success { border-left-color: #10b981; }
        .btn-go { display: inline-block; background: linear-gradient(135deg, #06b6d4, #a855f7); color: #fff; padding: 0.75rem 1.5rem; border-radius: 8px; text-decoration: none; font-weight: 600; margin-top: 1.5rem; text-align: center; transition: 0.2s ease; }
        .btn-go:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(6, 182, 212, 0.3); }
    </style>
</head>
<body>
<div class='setup-card'>
    <h1>Actualización CRM: Módulo de Características Premium</h1>
";

try {
    // 1. Agregar columnas de Asignación de Asesores
    try {
        $pdo->exec("ALTER TABLE `deals` ADD COLUMN `assigned_agent` VARCHAR(100) DEFAULT 'Andrés Herrera (GAM Norte)';");
        $pdo->exec("ALTER TABLE `accounts` ADD COLUMN `assigned_agent` VARCHAR(100) DEFAULT 'Andrés Herrera (GAM Norte)';");
        echo "<div class='step success'>✔️ Columnas `assigned_agent` agregadas en tablas `deals` y `accounts`.</div>";
    } catch (Exception $e) {
        echo "<div class='step success'>✔️ Las columnas de asignación de asesores ya existían.</div>";
    }

    // 2. Crear Tabla de Ajustes / Configuraciones (Meta de Ventas)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `crm_settings` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `setting_key` VARCHAR(100) UNIQUE NOT NULL,
        `setting_value` VARCHAR(255) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    
    $pdo->exec("INSERT INTO `crm_settings` (`setting_key`, `setting_value`) VALUES ('sales_quota_target', '30000') ON DUPLICATE KEY UPDATE `setting_value`='30000';");
    echo "<div class='step success'>✔️ Tabla `crm_settings` inicializada y Meta de Ventas establecida en $30,000 USD.</div>";

    // 3. Distribución Geopolítica / Territorio semilla para los datos actuales
    $pdo->exec("UPDATE `accounts` SET `assigned_agent` = 'Sofía Castro (GAM Oriente)' WHERE `city` IN ('San José', 'Cartago');");
    $pdo->exec("UPDATE `accounts` SET `assigned_agent` = 'Carlos Mendoza (Zona Costa)' WHERE `city` IN ('Puntarenas', 'Guanacaste');");
    $pdo->exec("UPDATE `accounts` SET `assigned_agent` = 'Andrés Herrera (GAM Norte)' WHERE `city` IN ('Heredia', 'Alajuela', 'Limón');");
    
    // Sincronizar agentes en Deals basados en el agente de la Cuenta
    $pdo->exec("UPDATE `deals` d JOIN `accounts` a ON d.account_id = a.id SET d.assigned_agent = a.assigned_agent;");
    echo "<div class='step success'>✔️ Enrutamiento de leads semilla ejecutado (Asignación basada en provincia de Costa Rica).</div>";

    echo "<p style='margin-top: 1.5rem; color: #10b981; font-weight: 600;'>¡CRM robustecido con características Premium Pipedrive!</p>";
    echo "<a href='panel.php' class='btn-go'>Ir al Dashboard</a>";

} catch (Exception $e) {
    echo "<div class='step' style='border-left-color: #ef4444; color: #fca5a5;'>❌ Error durante la actualización: " . $e->getMessage() . "</div>";
}

echo "
</div>
</body>
</html>";

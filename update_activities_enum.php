<?php
/**
 * update_activities_enum.php — Parche de DB para soportar más tipos de actividades comerciales
 */

require_once __DIR__ . '/config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>TIPS CRM — Actividades Update</title>
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
    <h1>Actualización CRM: Tipos de Actividades Comerciales</h1>
";

try {
    // Modificar columna tipo de actividades
    $pdo->exec("ALTER TABLE `activities` MODIFY COLUMN `type` ENUM('Call', 'Email', 'Meeting', 'Task', 'Technical_Visit', 'Demo', 'Samples') DEFAULT 'Call';");
    echo "<div class='step success'>✔️ Columna `type` en tabla `activities` modificada para soportar: Visita Técnica, Demostración y Entrega de Muestras.</div>";

    // Agregar actividad de prueba
    $pdo->exec("INSERT INTO `activities` (`deal_id`, `type`, `subject`, `description`, `due_date`, `status`) VALUES 
        (2, 'Technical_Visit', 'Visita Técnica de revisión eléctrica Unoox', 'Validar acometida trifásica 220V en cocina central.', '" . date('Y-m-d H:i:s', strtotime('+3 days 10:00:00')) . "', 'Pending'),
        (1, 'Demo', 'Demostración de rendimiento Fondant Enco', 'Preparar pastel de prueba con Mariana para evaluar elasticidad.', '" . date('Y-m-d H:i:s', strtotime('+2 days 13:00:00')) . "', 'Pending')
    ");
    echo "<div class='step success'>✔️ Actividades semilla de Visita Técnica y Demostración insertadas.</div>";

    echo "<p style='margin-top: 1.5rem; color: #10b981; font-weight: 600;'>¡Actualización de Actividades completada!</p>";
    echo "<a href='activities.php' class='btn-go'>Ir al Programador de Actividades</a>";

} catch (Exception $e) {
    echo "<div class='step' style='border-left-color: #ef4444; color: #fca5a5;'>❌ Error durante la actualización: " . $e->getMessage() . "</div>";
}

echo "
</div>
</body>
</html>";

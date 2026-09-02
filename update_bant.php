<?php
/**
 * update_bant.php — Parche de Base de Datos para integrar BANT en TIPS CRM
 */

require_once __DIR__ . '/config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>TIPS CRM — BANT Update</title>
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
            color: #10b981;
            font-size: 1.75rem;
            margin-bottom: 0.5rem;
            border-bottom: 2px dashed rgba(16, 185, 129, 0.2);
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
    <h1>Actualización CRM: Calificación BANT</h1>
";

try {
    // 1. Agregar columnas a la tabla de deals si no existen
    $columns = [
        'bant_budget' => "TINYINT DEFAULT 0 AFTER `status`",
        'bant_authority' => "TINYINT DEFAULT 0 AFTER `bant_budget`",
        'bant_need' => "TINYINT DEFAULT 0 AFTER `bant_authority`",
        'bant_timeline' => "TINYINT DEFAULT 0 AFTER `bant_need`",
        'bant_notes' => "TEXT DEFAULT NULL AFTER `bant_timeline`"
    ];

    foreach ($columns as $col => $definition) {
        // Verificar si la columna existe antes de agregarla
        $check = $pdo->query("SHOW COLUMNS FROM `deals` LIKE '$col'")->fetch();
        if (!$check) {
            $pdo->exec("ALTER TABLE `deals` ADD `$col` $definition");
            echo "<div class='step success'>✔️ Columna `{$col}` agregada a la tabla `deals`.</div>";
        } else {
            echo "<div class='step'>ℹ️ Columna `{$col}` ya existía en la tabla `deals`.</div>";
        }
    }
    
    // 2. Calificar aleatoriamente algunos deals de prueba para que no aparezcan vacíos al inicio
    $pdo->exec("UPDATE `deals` SET `bant_budget` = 1, `bant_authority` = 1 WHERE `id` = 2"); // KitchenAid (calificado budget/authority)
    $pdo->exec("UPDATE `deals` SET `bant_need` = 1, `bant_timeline` = 1 WHERE `id` = 3"); // Suministro Enco (calificado need/timeline)
    $pdo->exec("UPDATE `deals` SET `bant_budget` = 1, `bant_authority` = 1, `bant_need` = 1, `bant_timeline` = 1 WHERE `id` = 6"); // Ganado (100% BANT)
    echo "<div class='step success'>✔️ Datos semilla de calificación BANT aplicados.</div>";

    echo "<p style='margin-top: 1.5rem; color: #10b981; font-weight: 600;'>¡Calificación BANT integrada con éxito en la base de datos!</p>";
    echo "<a href='pipeline.php' class='btn-go'>Ir al Embudo Kanban</a>";

} catch (Exception $e) {
    echo "<div class='step' style='border-left-color: #ef4444; color: #fca5a5;'>❌ Error durante la actualización: " . $e->getMessage() . "</div>";
}

echo "
</div>
</body>
</html>";

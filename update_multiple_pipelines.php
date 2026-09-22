<?php
/**
 * update_multiple_pipelines.php — Parche de Base de Datos para integrar Múltiples Pipelines (Pipelines) en TIPS CRM
 */

require_once __DIR__ . '/config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>TIPS CRM — Multiple Pipelines Update</title>
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
    <h1>Actualización CRM: Múltiples Pipelines de Venta</h1>
";

try {
    // 1. Crear tabla pipelines
    $pdo->exec("CREATE TABLE IF NOT EXISTS `pipelines` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    echo "<div class='step success'>✔️ Tabla `pipelines` creada o verificada.</div>";

    // 2. Sembrar el primer pipeline si está vacía la tabla
    $count_pipelines = $pdo->query("SELECT COUNT(*) FROM `pipelines`")->fetchColumn();
    if ($count_pipelines == 0) {
        $pdo->exec("INSERT INTO `pipelines` (`id`, `name`) VALUES (1, 'Pipeline de Ventas Principal');");
        echo "<div class='step success'>✔️ Primer pipeline 'Pipeline de Ventas Principal' sembrado (ID: 1).</div>";
    }

    // 3. Agregar columna `pipeline_id` a la tabla `stages` si no existe
    $check_column = $pdo->query("SHOW COLUMNS FROM `stages` LIKE 'pipeline_id'")->fetch();
    if (!$check_column) {
        // Deshabilitar constraints temporalmente
        $pdo->exec("ALTER TABLE `stages` ADD COLUMN `pipeline_id` INT DEFAULT NULL AFTER `id`;");
        $pdo->exec("UPDATE `stages` SET `pipeline_id` = 1 WHERE `pipeline_id` IS NULL;");
        $pdo->exec("ALTER TABLE `stages` ADD CONSTRAINT `fk_stages_pipeline` FOREIGN KEY (`pipeline_id`) REFERENCES `pipelines`(`id`) ON DELETE CASCADE;");
        echo "<div class='step success'>✔️ Columna `pipeline_id` y restricción FK agregadas a la tabla `stages`.</div>";
    } else {
        echo "<div class='step'>ℹ️ Columna `pipeline_id` ya existía en la tabla `stages`.</div>";
    }

    // 4. Crear un segundo pipeline de prueba "Logística y Despacho" si no existe
    $check_test_pipeline = $pdo->query("SELECT COUNT(*) FROM `pipelines` WHERE `id` = 2")->fetchColumn();
    if ($check_test_pipeline == 0) {
        $pdo->exec("INSERT INTO `pipelines` (`id`, `name`) VALUES (2, 'Logística y Despacho');");
        echo "<div class='step success'>✔️ Segundo pipeline 'Logística y Despacho' sembrado (ID: 2).</div>";

        // Insertar etapas para el segundo pipeline
        $stages_test = [
            ['Pedido Recibido', 1, 2],
            ['Preparación en Bodega', 2, 2],
            ['En Ruta de Entrega', 3, 2],
            ['Entregado y Confirmado', 4, 2]
        ];
        $stmt_stage = $pdo->prepare("INSERT INTO `stages` (`name`, `position`, `pipeline_id`) VALUES (?, ?, ?)");
        foreach ($stages_test as $stg) {
            $stmt_stage->execute($stg);
        }
        echo "<div class='step success'>✔️ Etapas de prueba sembradas para el segundo pipeline.</div>";
    } else {
        echo "<div class='step'>ℹ️ El segundo pipeline 'Logística y Despacho' ya existía.</div>";
    }

    echo "<p style='margin-top: 1.5rem; color: #10b981; font-weight: 600;'>¡Múltiples pipelines integrados con éxito en la base de datos!</p>";
    echo "<a href='pipeline.php' class='btn-go'>Ir al Pipeline Kanban</a>";

} catch (Exception $e) {
    echo "<div class='step' style='border-left-color: #ef4444; color: #fca5a5;'>❌ Error durante la actualización: " . $e->getMessage() . "</div>";
}

echo "
</div>
</body>
</html>";

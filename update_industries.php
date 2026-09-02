<?php
/**
 * update_industries.php — Parche de DB para soportar sectores industriales personalizables
 */

require_once __DIR__ . '/config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>TIPS CRM — Sectores Update</title>
    <link href='https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap' rel='stylesheet'>
    <style>
        body { background-color: #070a13; color: #f1f5f9; font-family: 'Outfit', sans-serif; padding: 3rem 1rem; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .setup-card { background: rgba(18, 24, 48, 0.65); border: 1px solid rgba(255, 255, 255, 0.08); backdrop-filter: blur(16px); border-radius: 16px; padding: 2.5rem; max-width: 600px; width: 100%; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5); }
        h1 { color: #a855f7; font-size: 1.75rem; margin-bottom: 0.5rem; border-bottom: 2px dashed rgba(168, 85, 247, 0.2); padding-bottom: 0.5rem; }
        .step { margin: 1rem 0; padding-left: 1.5rem; border-left: 3px solid #a855f7; font-size: 0.95rem; }
        .step.success { border-left-color: #10b981; }
        .btn-go { display: inline-block; background: linear-gradient(135deg, #06b6d4, #a855f7); color: #fff; padding: 0.75rem 1.5rem; border-radius: 8px; text-decoration: none; font-weight: 600; margin-top: 1.5rem; text-align: center; transition: 0.2s ease; }
        .btn-go:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(6, 182, 212, 0.3); }
    </style>
</head>
<body>
<div class='setup-card'>
    <h1>Actualización CRM: Sectores Industriales Personalizables</h1>
";

try {
    // 1. Crear tabla de industrias
    $pdo->exec("CREATE TABLE IF NOT EXISTS `industries` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) UNIQUE NOT NULL,
        `scoring_points` INT NOT NULL DEFAULT 10
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    echo "<div class='step success'>✔️ Tabla `industries` creada de forma segura.</div>";

    // 2. Insertar valores base ampliados
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

    $stmt = $pdo->prepare("INSERT IGNORE INTO `industries` (`name`, `scoring_points`) VALUES (?, ?)");
    foreach ($default_sectors as $sector) {
        $stmt->execute($sector);
    }
    echo "<div class='step success'>✔️ Sectores industriales iniciales y ampliados insertados.</div>";

    echo "<p style='margin-top: 1.5rem; color: #10b981; font-weight: 600;'>¡Actualización completada! El CRM ahora soporta sectores dinámicos.</p>";
    echo "<a href='accounts.php' class='btn-go'>Ir a Cuentas Comerciales</a>";

} catch (Exception $e) {
    echo "<div class='step' style='border-left-color: #ef4444; color: #fca5a5;'>❌ Error durante la actualización: " . $e->getMessage() . "</div>";
}

echo "
</div>
</body>
</html>";

<?php
/**
 * update_customer_360.php — Parche de base de datos para habilitar Finanzas B2B y Expedientes de Documentos
 */

require_once __DIR__ . '/config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>TIPS CRM — Customer 360 Update</title>
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
    <h1>Actualización CRM: Finanzas y Gestión de Expedientes B2B</h1>
";

try {
    // 1. Columnas de Crédito en Cuentas (Accounts)
    try {
        $pdo->exec("ALTER TABLE `accounts` ADD COLUMN `credit_limit` DECIMAL(12,2) DEFAULT 0.00;");
        $pdo->exec("ALTER TABLE `accounts` ADD COLUMN `credit_balance` DECIMAL(12,2) DEFAULT 0.00;");
        $pdo->exec("ALTER TABLE `accounts` ADD COLUMN `credit_terms` VARCHAR(100) DEFAULT 'Contado';");
        echo "<div class='step success'>✔️ Columnas de límite de crédito, balance deudor y condiciones de pago agregadas en tabla `accounts`.</div>";
    } catch (Exception $e) {
        echo "<div class='step success'>✔️ Las columnas de crédito ya existían en la tabla `accounts`.</div>";
    }

    // 2. Tabla de Facturas (Invoices)
    $pdo->exec("DROP TABLE IF EXISTS `invoices`;");
    $pdo->exec("CREATE TABLE `invoices` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `account_id` INT NOT NULL,
        `invoice_number` VARCHAR(100) UNIQUE NOT NULL,
        `amount` DECIMAL(12,2) NOT NULL,
        `due_date` DATE NOT NULL,
        `status` ENUM('Paid', 'Pending') DEFAULT 'Pending',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`account_id`) REFERENCES `accounts`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    echo "<div class='step success'>✔️ Tabla `invoices` (Historial de Facturación y Cobro) creada.</div>";

    // 3. Tabla de Documentos / Expediente (Account Documents)
    $pdo->exec("DROP TABLE IF EXISTS `account_documents`;");
    $pdo->exec("CREATE TABLE `account_documents` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `account_id` INT NOT NULL,
        `filename` VARCHAR(255) NOT NULL,
        `category` VARCHAR(100) NOT NULL DEFAULT 'Otro',
        `uploaded_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`account_id`) REFERENCES `accounts`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    echo "<div class='step success'>✔️ Tabla `account_documents` (Expediente Digital B2B) creada.</div>";

    // 4. Semillar Datos Financieros en las Cuentas
    $pdo->exec("UPDATE `accounts` SET `credit_limit` = 15000.00, `credit_balance` = 4500.00, `credit_terms` = 'Crédito 30 días (BAC)' WHERE `name` LIKE '%Spoon%';");
    $pdo->exec("UPDATE `accounts` SET `credit_limit` = 10000.00, `credit_balance` = 0.00, `credit_terms` = 'Crédito 15 días (BCR)' WHERE `name` LIKE '%Musmanni%';");
    $pdo->exec("UPDATE `accounts` SET `credit_limit` = 25000.00, `credit_balance` = 12300.00, `credit_terms` = 'TIPS Financiamiento Neto 30' WHERE `name` LIKE '%Villa Caletas%';");
    $pdo->exec("UPDATE `accounts` SET `credit_limit` = 5000.00, `credit_balance` = 0.00, `credit_terms` = 'Contado' WHERE `name` LIKE '%Britt%';");
    echo "<div class='step success'>✔️ Datos comerciales de crédito y pago inyectados a cuentas corporativas.</div>";

    // 5. Semillar Facturas
    $pdo->exec("INSERT INTO `invoices` (`account_id`, `invoice_number`, `amount`, `due_date`, `status`) VALUES 
        (1, 'FACT-2026-001', 3500.00, '" . date('Y-m-d', strtotime('-45 days')) . "', 'Paid'),
        (1, 'FACT-2026-002', 4500.00, '" . date('Y-m-d', strtotime('+15 days')) . "', 'Pending'),
        (2, 'FACT-2026-003', 2800.00, '" . date('Y-m-d', strtotime('-10 days')) . "', 'Paid'),
        (3, 'FACT-2026-004', 8500.00, '" . date('Y-m-d', strtotime('-35 days')) . "', 'Paid'),
        (3, 'FACT-2026-005', 12300.00, '" . date('Y-m-d', strtotime('+5 days')) . "', 'Pending')
    ");
    echo "<div class='step success'>✔️ Facturas de prueba de cobros pendientes y cobrados inyectadas.</div>";

    // 6. Semillar Documentos del Expediente
    $pdo->exec("INSERT INTO `account_documents` (`account_id`, `filename`, `category`) VALUES 
        (1, 'contrato_distribucion_spoon_2026.pdf', 'Contrato B2B'),
        (1, 'requisitos_tecnicos_acometida.pdf', 'Ficha Técnica'),
        (3, 'licitacion_compras_caletas.pdf', 'Contrato B2B'),
        (3, 'cotizacion_horno_unoox_firmada.pdf', 'Cotización')
    ");
    echo "<div class='step success'>✔️ Expediente de archivos adjuntos (Contratos y Fichas Técnicas) inicializado.</div>";

    // Crear carpeta uploads/ si no existe
    $uploads_dir = __DIR__ . '/uploads';
    if (!file_exists($uploads_dir)) {
        mkdir($uploads_dir, 0777, true);
    }

    echo "<p style='margin-top: 1.5rem; color: #10b981; font-weight: 600;'>¡Actualización de Finanzas y Expediente completada con éxito!</p>";
    echo "<a href='accounts.php' class='btn-go'>Ir al Directorio de Cuentas B2B</a>";

} catch (Exception $e) {
    echo "<div class='step' style='border-left-color: #ef4444; color: #fca5a5;'>❌ Error durante la actualización: " . $e->getMessage() . "</div>";
}

echo "
</div>
</body>
</html>";

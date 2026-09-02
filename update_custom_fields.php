<?php
require_once __DIR__ . '/config.php';

try {
    // 1. Crear tabla custom_field_definitions
    $pdo->exec("CREATE TABLE IF NOT EXISTS `custom_field_definitions` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `field_type` ENUM('text', 'number', 'date', 'select') NOT NULL,
        `options` TEXT DEFAULT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    echo "Tabla 'custom_field_definitions' creada o ya existente.\n";

    // 2. Crear tabla deal_custom_values
    $pdo->exec("CREATE TABLE IF NOT EXISTS `deal_custom_values` (
        `deal_id` INT NOT NULL,
        `field_id` INT NOT NULL,
        `value` TEXT DEFAULT NULL,
        PRIMARY KEY (`deal_id`, `field_id`),
        FOREIGN KEY (`deal_id`) REFERENCES `deals`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`field_id`) REFERENCES `custom_field_definitions`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    echo "Tabla 'deal_custom_values' creada o ya existente.\n";

    // 3. Sembrar algunos campos personalizados por defecto si la tabla está vacía
    $count = $pdo->query("SELECT COUNT(*) FROM `custom_field_definitions`")->fetchColumn();
    if ($count == 0) {
        $stmt = $pdo->prepare("INSERT INTO `custom_field_definitions` (`name`, `field_type`, `options`) VALUES (?, ?, ?)");
        $stmt->execute(['Origen del Lead', 'select', 'Google Ads, Facebook Ads, WhatsApp, Formulario Web, Recomendación']);
        $stmt->execute(['Fecha de Entrega Estimada', 'date', null]);
        $stmt->execute(['Requerimientos Técnicos', 'text', null]);
        echo "Campos personalizados por defecto sembrados.\n";
    }

    echo "MIGRACIÓN DE CAMPOS PERSONALIZADOS COMPLETADA CON ÉXITO\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

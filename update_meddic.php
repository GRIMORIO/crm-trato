<?php
require_once __DIR__ . '/config.php';

try {
    // 1. Alterar tabla deals para agregar columnas MEDDIC
    $cols = [
        'meddic_metrics' => "TINYINT DEFAULT 0 AFTER `bant_notes`",
        'meddic_buyer' => "TINYINT DEFAULT 0 AFTER `meddic_metrics`",
        'meddic_criteria' => "TINYINT DEFAULT 0 AFTER `meddic_buyer`",
        'meddic_process' => "TINYINT DEFAULT 0 AFTER `meddic_criteria`",
        'meddic_pain' => "TINYINT DEFAULT 0 AFTER `meddic_process`",
        'meddic_champion' => "TINYINT DEFAULT 0 AFTER `meddic_pain`"
    ];

    foreach ($cols as $col => $type) {
        // Verificar si la columna ya existe
        $check = $pdo->query("SHOW COLUMNS FROM `deals` LIKE '$col'")->fetch();
        if (!$check) {
            $pdo->exec("ALTER TABLE `deals` ADD `$col` $type");
            echo "Columna '$col' agregada a tabla 'deals'.\n";
        } else {
            echo "Columna '$col' ya existe.\n";
        }
    }

    // 2. Insertar configuraciones por defecto en crm_settings
    $settings = [
        'qualification_framework' => 'BANT',
        'scoring_meddic_metrics' => '15',
        'scoring_meddic_buyer' => '20',
        'scoring_meddic_criteria' => '15',
        'scoring_meddic_process' => '15',
        'scoring_meddic_pain' => '20',
        'scoring_meddic_champion' => '15'
    ];

    $stmt = $pdo->prepare("INSERT INTO `crm_settings` (`setting_key`, `setting_value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`");
    foreach ($settings as $key => $val) {
        $stmt->execute([$key, $val]);
        echo "Ajuste '$key' sembrado en crm_settings.\n";
    }

    echo "MIGRACIÓN DE MEDDIC COMPLETADA CON ÉXITO\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

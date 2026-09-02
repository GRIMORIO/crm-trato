<?php
require_once __DIR__ . '/config.php';

try {
    // 1. Crear tabla crm_automations
    $sql = "CREATE TABLE IF NOT EXISTS `crm_automations` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `title` VARCHAR(150) NOT NULL,
        `trigger_event` VARCHAR(50) NOT NULL DEFAULT 'stage_change',
        `trigger_value` VARCHAR(100) NOT NULL,
        `action_type` VARCHAR(50) NOT NULL DEFAULT 'create_task',
        `action_payload` TEXT NOT NULL,
        `is_active` TINYINT DEFAULT 1,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $pdo->exec($sql);
    echo "Tabla 'crm_automations' creada o ya existente.\n";

    // 2. Buscar IDs de etapas por nombre para sembrado dinámico
    $stage_samples_id = $pdo->query("SELECT id FROM stages WHERE name LIKE '%Muestras%' OR name LIKE '%Demostración%'")->fetchColumn() ?: 4;
    $stage_negotiation_id = $pdo->query("SELECT id FROM stages WHERE name LIKE '%Negociación%' OR name LIKE '%Propuesta%'")->fetchColumn() ?: 5;

    // Seeding de automatizaciones por defecto
    $automations = [
        [
            'title' => 'Seguimiento de Muestras Enco/KitchenAid',
            'trigger_event' => 'stage_change',
            'trigger_value' => (string)$stage_samples_id,
            'action_type' => 'create_task',
            'action_payload' => json_encode([
                'type' => 'Samples',
                'due_in_days' => 2,
                'title' => 'Coordinar entrega de muestras Enco/KitchenAid al cliente'
            ], JSON_UNESCAPED_UNICODE)
        ],
        [
            'title' => 'Revisión de Propuesta Comercial TIPS',
            'trigger_event' => 'stage_change',
            'trigger_value' => (string)$stage_negotiation_id,
            'action_type' => 'create_task',
            'action_payload' => json_encode([
                'type' => 'Meeting',
                'due_in_days' => 3,
                'title' => 'Revisar propuesta final con el decisor de compras'
            ], JSON_UNESCAPED_UNICODE)
        ]
    ];

    $stmt = $pdo->prepare("INSERT INTO `crm_automations` (`title`, `trigger_event`, `trigger_value`, `action_type`, `action_payload`, `is_active`) VALUES (?, ?, ?, ?, ?, 1)");
    
    // Solo sembrar si la tabla está vacía para no duplicar en ejecuciones consecutivas
    $count = $pdo->query("SELECT COUNT(*) FROM `crm_automations`")->fetchColumn();
    if ($count == 0) {
        foreach ($automations as $auto) {
            $stmt->execute([
                $auto['title'],
                $auto['trigger_event'],
                $auto['trigger_value'],
                $auto['action_type'],
                $auto['action_payload']
            ]);
            echo "Automatización '{$auto['title']}' sembrada.\n";
        }
    } else {
        echo "Tabla 'crm_automations' ya contiene datos, omitiendo siembra.\n";
    }

    echo "MIGRACIÓN DE AUTOMATIZACIONES COMPLETADA CON ÉXITO\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

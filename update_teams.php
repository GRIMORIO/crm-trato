<?php
require_once __DIR__ . '/config.php';

try {
    // 1. Agregar columnas a crm_users
    $cols = [
        'role' => "ENUM('admin', 'agent') DEFAULT 'agent' AFTER `password_hash`",
        'assigned_agent_name' => "VARCHAR(100) DEFAULT NULL AFTER `role`"
    ];

    foreach ($cols as $col => $type) {
        $check = $pdo->query("SHOW COLUMNS FROM `crm_users` LIKE '$col'")->fetch();
        if (!$check) {
            $pdo->exec("ALTER TABLE `crm_users` ADD `$col` $type");
            echo "Columna '$col' agregada a tabla 'crm_users'.\n";
        } else {
            echo "Columna '$col' ya existe.\n";
        }
    }

    // 2. Establecer rol de napoleon como admin
    $pdo->exec("UPDATE `crm_users` SET `role` = 'admin' WHERE `username` = 'napoleon'");
    echo "Usuario 'napoleon' actualizado a rol 'admin'.\n";

    // 3. Sembrar asesores
    $hash = password_hash('tips2026', PASSWORD_DEFAULT);
    $agents = [
        [
            'username' => 'andres',
            'full_name' => 'Andrés Herrera',
            'email' => 'aherrera@tips.cr',
            'role' => 'agent',
            'assigned_agent_name' => 'Andrés Herrera (GAM Norte)'
        ],
        [
            'username' => 'carlos',
            'full_name' => 'Carlos Mendoza',
            'email' => 'cmendoza@tips.cr',
            'role' => 'agent',
            'assigned_agent_name' => 'Carlos Mendoza (Zona Costa)'
        ],
        [
            'username' => 'sofia',
            'full_name' => 'Sofía Castro',
            'email' => 'scastro@tips.cr',
            'role' => 'agent',
            'assigned_agent_name' => 'Sofía Castro (GAM Oriente)'
        ]
    ];

    $stmt = $pdo->prepare("INSERT INTO `crm_users` (`username`, `password_hash`, `full_name`, `email`, `role`, `assigned_agent_name`) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `role` = VALUES(`role`), `assigned_agent_name` = VALUES(`assigned_agent_name`)");
    foreach ($agents as $agent) {
        $stmt->execute([
            $agent['username'],
            $hash,
            $agent['full_name'],
            $agent['email'],
            $agent['role'],
            $agent['assigned_agent_name']
        ]);
        echo "Asesor '{$agent['username']}' sembrado/actualizado.\n";
    }

    echo "MIGRACIÓN DE EQUIPOS Y VISIBILIDAD COMPLETADA CON ÉXITO\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

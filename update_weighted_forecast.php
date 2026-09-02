<?php
require_once __DIR__ . '/config.php';

try {
    // 1. Agregar columna win_probability si no existe
    $columns = $pdo->query("SHOW COLUMNS FROM stages LIKE 'win_probability'")->fetchAll();
    if (empty($columns)) {
        $pdo->exec("ALTER TABLE `stages` ADD COLUMN `win_probability` INT NOT NULL DEFAULT 50 AFTER `position`");
        echo "Columna 'win_probability' agregada correctamente a 'stages'.\n";
    } else {
        echo "La columna 'win_probability' ya existe en 'stages'.\n";
    }

    // 2. Establecer probabilidades por defecto para los nombres de etapas conocidos
    $probabilities = [
        'Contacto Inicial' => 10,
        'Calificación BANT' => 30,
        'Demostración / Muestras' => 50,
        'Propuesta Comercial' => 75,
        'Negociación y Cierre' => 90,
        'Pedido Recibido' => 20,
        'Preparación en Bodega' => 40,
        'En Ruta de Entrega' => 70,
        'Entregado y Confirmado' => 100
    ];

    $stmt = $pdo->prepare("UPDATE `stages` SET `win_probability` = ? WHERE `name` = ?");
    foreach ($probabilities as $name => $prob) {
        $stmt->execute([$prob, $name]);
    }
    echo "Probabilidades por defecto aplicadas con éxito.\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

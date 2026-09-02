<?php
/**
 * update_sales_coach.php — Parche de base de datos para habilitar el Módulo de Consejos Sales Coach de Brian Tracy
 */

require_once __DIR__ . '/config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>TIPS CRM — Brian Tracy Coach Update</title>
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
    <h1>Actualización CRM: Sales Coach de Brian Tracy</h1>
";

try {
    // 1. Crear Tabla
    $pdo->exec("DROP TABLE IF EXISTS `sales_tips`;");
    $pdo->exec("CREATE TABLE `sales_tips` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `category` VARCHAR(50) NOT NULL,
        `tip_text` TEXT NOT NULL,
        `author` VARCHAR(100) DEFAULT 'Brian Tracy'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    echo "<div class='step success'>✔️ Tabla `sales_tips` creada con éxito en la base de datos.</div>";

    // 2. Insertar Consejos Semilla
    $stmt = $pdo->prepare("INSERT INTO `sales_tips` (`category`, `tip_text`, `author`) VALUES (?, ?, ?)");
    
    $tips = [
        ['Motivation', 'El único límite para tus logros del mañana son tus dudas de hoy. Vende con absoluta confianza en tus productos.', 'Brian Tracy'],
        ['Motivation', 'Los vendedores exitosos están orientados a la acción. Cuando se les ocurre una gran idea, la ejecutan de inmediato.', 'Brian Tracy'],
        ['Motivation', 'Tu nivel de auto-estima es el determinante clave de tu rendimiento y eficacia en todas las áreas de tus ventas.', 'Brian Tracy'],
        ['Follow-up', 'El 80% de las ventas corporativas se cierran después del quinto seguimiento. La perseverancia distingue a los líderes de ventas.', 'Brian Tracy'],
        ['Follow-up', 'Nunca asumas que un silencio o postergación del cliente es un "No". Mantén una presencia constante y con valor agregado.', 'Brian Tracy'],
        ['Closing', 'No intentes vender el producto. Vende la solución al problema. Al chef no le importa la batidora, le importa la suavidad de su batido.', 'Brian Tracy'],
        ['Closing', 'Hacer preguntas inteligentes y escuchar con atención es el camino más directo para ganarse la confianza del comprador.', 'Brian Tracy'],
        ['BANT', 'Si el cliente no tiene un presupuesto calificado para pagar un equipo Unoox, no es un prospecto. Califica temprano.', 'Brian Tracy'],
        ['BANT', 'Identifica siempre al decisor final con autoridad de firma. No desgastes tu energía vendiéndole a quien no puede decidir.', 'Brian Tracy'],
        ['BANT', 'La necesidad lo es todo. Si tu cliente no ve un problema urgente en su cocina industrial, no habrá interés ni trato comercial.', 'Brian Tracy'],
        ['Time_Management', 'Dedica el 80% de tu jornada comercial a prospectar y presentar soluciones, y deja las tareas administrativas para el final.', 'Brian Tracy'],
        ['Time_Management', 'Planifica tu agenda y tu ruta de visitas técnicas la noche anterior. Iniciar el día con un plan claro multiplica tus cierres.', 'Brian Tracy'],
        
        // Semillas de T. Harv Eker ("Los Secretos de la Mente Millonaria")
        ['Motivation', 'La gente rica promueve su valor con pasión y entusiasmo. Si crees en tu solución de cocina TIPS, preséntala con orgullo.', 'T. Harv Eker'],
        ['Motivation', 'Tu patrón financiero determinará tu destino. Condiciona tu mente para ver abundancia y grandes tratos en cada negociación.', 'T. Harv Eker'],
        ['Motivation', 'Si quieres cambiar los frutos, primero tendrás que modificar las raíces. Trabaja en tu mentalidad antes de ir a vender.', 'T. Harv Eker'],
        ['Motivation', 'La queja es el mayor imán para los problemas. Enfócate en las oportunidades del mercado B2B, no en los obstáculos.', 'T. Harv Eker'],
        ['Closing', 'Los profesionales de alto nivel no temen a la venta ni a la promoción. Saben que vender es educar y servir al cliente.', 'T. Harv Eker'],
        ['Time_Management', 'La gente rica se enfoca en construir su patrimonio neto; los aficionados en sus ingresos por hora. Invierte tu tiempo en cuentas B2B recurrentes.', 'T. Harv Eker']
    ];

    foreach ($tips as $tip) {
        $stmt->execute($tip);
    }
    echo "<div class='step success'>✔️ 12 Consejos y metodologías estratégicas de ventas inyectadas con éxito.</div>";

    echo "<p style='margin-top: 1.5rem; color: #10b981; font-weight: 600;'>¡Actualización del Sales Coach de Brian Tracy finalizada!</p>";
    echo "<a href='panel.php' class='btn-go'>Ir al Dashboard</a>";

} catch (Exception $e) {
    echo "<div class='step' style='border-left-color: #ef4444; color: #fca5a5;'>❌ Error durante la actualización: " . $e->getMessage() . "</div>";
}

echo "
</div>
</body>
</html>";

<?php
/**
 * update_email_templates.php — Parche de DB para crear y sembrar plantillas de correo B2B
 */

require_once __DIR__ . '/config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>TIPS CRM — Email Templates Update</title>
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
    <h1>Actualización CRM: Plantillas de Correo Electrónico B2B</h1>
";

try {
    // 1. Crear Tabla
    $pdo->exec("DROP TABLE IF EXISTS `email_templates`;");
    $pdo->exec("CREATE TABLE `email_templates` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `subject` VARCHAR(255) NOT NULL,
        `body` TEXT NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    echo "<div class='step success'>✔️ Tabla `email_templates` creada con éxito.</div>";

    // 2. Insertar Plantillas
    $stmt = $pdo->prepare("INSERT INTO `email_templates` (`name`, `subject`, `body`) VALUES (?, ?, ?)");
    
    $templates = [
        [
            "[Contacto Inicial] Presentación de TIPS y Catálogo Enco",
            "Catálogo de Insumos y Equipos Comerciales — TIPS Costa Rica",
            "Hola {{contact_name}},\n\nUn gusto saludarte de TIPS S.A. Adjunto a este correo encontrarás nuestro catálogo completo de colorantes y fondant Enco Alimentos, así como nuestra línea comercial KitchenAid.\n\nContame si tienes alguna duda sobre precios por volumen para {{company_name}}."
        ],
        [
            "[Calificación] Consultas de Requerimientos Técnicos",
            "Requerimientos Técnicos y Presupuesto — TIPS CRM",
            "Hola {{contact_name}},\n\nPara poder prepararte una propuesta óptima para tu negocio {{company_name}}, quisiéramos consultar:\n1. ¿Cuál es el plazo estimado de entrega que manejan?\n2. ¿Quién sería la persona encargada de firmar la orden de compra?\n\nQuedamos atentos para asesorarte."
        ],
        [
            "[Demostración] Coordinación de Pruebas Físicas",
            "Coordinación de Pruebas de Equipo en Cocina — TIPS",
            "Hola {{contact_name}},\n\nConfirmamos la preparación del equipo de demostración (batidora KitchenAid 8Qt Commercial) para realizar pruebas de batido pesado en tu local de {{company_name}}.\n\n¿Te queda bien coordinar la visita técnica para este próximo martes a las 14:00?"
        ],
        [
            "[Propuesta] Envío de Cotización Formal",
            "Cotización Comercial y Ficha Técnica — TIPS B2B",
            "Hola {{contact_name}},\n\nAdjunto comparto la cotización formal por un valor de {{deal_value}} correspondiente al equipamiento de {{company_name}}.\n\nContamos con opciones de crédito B2B BAC Credomatic y financiamiento TIPS previa aprobación."
        ],
        [
            "[Negociación/Cierre] Instrucciones de Pago y Coordinación",
            "Instrucciones de Pago y Coordinación de Logística — TIPS",
            "Hola {{contact_name}},\n\n¡Un gusto avanzar con tu compra para {{company_name}}! Adjunto encontrarás nuestras cuentas bancarias (BAC / BCR) para realizar la transferencia de {{deal_value}}.\n\nUna vez enviado el comprobante de pago, nuestro camión despachará tu pedido en un plazo máximo de 24 horas hábiles."
        ]
    ];

    foreach ($templates as $t) {
        $stmt->execute($t);
    }
    echo "<div class='step success'>✔️ Semillas de las 5 plantillas comerciales vinculadas a fases del funnel inyectadas.</div>";

    echo "<p style='margin-top: 1.5rem; color: #10b981; font-weight: 600;'>¡Configuración completada!</p>";
    echo "<a href='email_inbox.php' class='btn-go'>Ir a la Bandeja de Correo</a>";

} catch (Exception $e) {
    echo "<div class='step' style='border-left-color: #ef4444; color: #fca5a5;'>❌ Error durante la actualización: " . $e->getMessage() . "</div>";
}

echo "
</div>
</body>
</html>";

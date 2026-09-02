<?php
require_once __DIR__ . '/config.php';

try {
    echo "INICIANDO INYECCIÓN DE DATOS DEMO ENRIQUECIDOS\n";

    // 1. Limpiar datos viejos para evitar duplicados
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("TRUNCATE TABLE `deal_custom_values`");
    $pdo->exec("TRUNCATE TABLE `activities`");
    $pdo->exec("TRUNCATE TABLE `notes`");
    $pdo->exec("TRUNCATE TABLE `deals`");
    $pdo->exec("TRUNCATE TABLE `custom_field_definitions`");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "  - Tablas de oportunidades limpiadas con éxito.\n";

    // 2. Insertar definiciones de campos personalizados
    $stmt_cf = $pdo->prepare("INSERT INTO `custom_field_definitions` (`id`, `name`, `field_type`, `options`) VALUES (?, ?, ?, ?)");
    $stmt_cf->execute([1, 'Origen del Lead', 'select', 'Google Ads, Facebook Ads, WhatsApp, Formulario Web, Recomendación']);
    $stmt_cf->execute([2, 'Fecha de Entrega Estimada', 'date', null]);
    $stmt_cf->execute([3, 'Requerimientos Técnicos', 'text', null]);
    echo "  - Campos personalizados recreados.\n";

    // 3. Obtener mapeo de etapas por nombre
    $stages_map = $pdo->query("SELECT name, id FROM stages")->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Validar que tengamos las etapas
    if (empty($stages_map)) {
        throw new Exception("Error: No se encontraron etapas en la base de datos. Ejecuta setup.php primero.");
    }

    // 4. Deals de Prueba
    $deals_data = [
        // EMBUDO 1: VENTAS PRINCIPAL
        [
            'title' => 'Contrato Anual Suministro Fondant Spoon',
            'value' => 18400.00,
            'stage_id' => $stages_map['Propuesta Comercial'],
            'account_id' => 1,
            'contact_id' => 1,
            'status' => 'Open',
            'assigned_agent' => 'Sofía Castro (GAM Oriente)',
            'close_date' => date('Y-m-d', strtotime('+30 days')),
            'bant_budget' => 1, 'bant_authority' => 1, 'bant_need' => 1, 'bant_timeline' => 0,
            'bant_notes' => 'Spoon requiere fondant resistente a climas húmedos en sucursales de Alajuela y Heredia.',
            'meddic_metrics' => 1, 'meddic_buyer' => 1, 'meddic_criteria' => 0, 'meddic_process' => 0, 'meddic_pain' => 1, 'meddic_champion' => 1,
            'custom_fields' => [
                1 => 'WhatsApp',
                2 => date('Y-m-d', strtotime('+40 days')),
                3 => 'Fondant Enco de formulación ultra-resistente a la humedad'
            ]
        ],
        [
            'title' => 'Lote Batidoras KitchenAid Villa Caletas',
            'value' => 6250.00,
            'stage_id' => $stages_map['Demostración / Muestras'],
            'account_id' => 3,
            'contact_id' => 3,
            'status' => 'Open',
            'assigned_agent' => 'Carlos Mendoza (Zona Costa)',
            'close_date' => date('Y-m-d', strtotime('+15 days')),
            'bant_budget' => 1, 'bant_authority' => 0, 'bant_need' => 1, 'bant_timeline' => 1,
            'bant_notes' => 'Chef de repostería de Villa Caletas pre-aprobó la compra, requiere demo física.',
            'meddic_metrics' => 1, 'meddic_buyer' => 0, 'meddic_criteria' => 1, 'meddic_process' => 0, 'meddic_pain' => 1, 'meddic_champion' => 1,
            'custom_fields' => [
                1 => 'Recomendación',
                2 => date('Y-m-d', strtotime('+20 days')),
                3 => 'Batidoras KitchenAid modelo comercial 8Qt de tazón elevable'
            ]
        ],
        [
            'title' => 'Surtido Completo Colorantes Cakeland',
            'value' => 1450.00,
            'stage_id' => $stages_map['Negociación y Cierre'],
            'account_id' => 5,
            'contact_id' => 5,
            'status' => 'Open',
            'assigned_agent' => 'Sofía Castro (GAM Oriente)',
            'close_date' => date('Y-m-d', strtotime('+5 days')),
            'bant_budget' => 1, 'bant_authority' => 1, 'bant_need' => 1, 'bant_timeline' => 1,
            'bant_notes' => 'Apertura de nueva sucursal Cakeland. Requieren stock inicial inmediato.',
            'meddic_metrics' => 1, 'meddic_buyer' => 1, 'meddic_criteria' => 1, 'meddic_process' => 1, 'meddic_pain' => 1, 'meddic_champion' => 1,
            'custom_fields' => [
                1 => 'Google Ads',
                2 => date('Y-m-d', strtotime('+7 days')),
                3 => 'Kit completo de colorantes en gel y metálicos Enco'
            ]
        ],
        [
            'title' => 'Proyecto Hornos Convección El Pan Nuestro',
            'value' => 24500.00,
            'stage_id' => $stages_map['Calificación BANT'],
            'account_id' => 6,
            'contact_id' => 6,
            'status' => 'Open',
            'assigned_agent' => 'Andrés Herrera (GAM Norte)',
            'close_date' => date('Y-m-d', strtotime('+60 days')),
            'bant_budget' => 0, 'bant_authority' => 1, 'bant_need' => 1, 'bant_timeline' => 0,
            'bant_notes' => 'Ingeniero de planta solicita planos técnicos. Falta aprobación presupuestaria.',
            'meddic_metrics' => 0, 'meddic_buyer' => 1, 'meddic_criteria' => 0, 'meddic_process' => 0, 'meddic_pain' => 1, 'meddic_champion' => 0,
            'custom_fields' => [
                1 => 'Formulario Web',
                2 => date('Y-m-d', strtotime('+90 days')),
                3 => 'Hornos Unoox trifásicos rotativos'
            ]
        ],
        [
            'title' => 'Compra Mayorista Utensilios Wilton Musmanni',
            'value' => 920.00,
            'stage_id' => $stages_map['Contacto Inicial'],
            'account_id' => 2,
            'contact_id' => 2,
            'status' => 'Open',
            'assigned_agent' => 'Andrés Herrera (GAM Norte)',
            'close_date' => date('Y-m-d', strtotime('+25 days')),
            'bant_budget' => 0, 'bant_authority' => 0, 'bant_need' => 0, 'bant_timeline' => 0,
            'bant_notes' => 'Primer contacto. Solicitan catálogo mayorista de moldes Wilton.',
            'meddic_metrics' => 0, 'meddic_buyer' => 0, 'meddic_criteria' => 0, 'meddic_process' => 0, 'meddic_pain' => 0, 'meddic_champion' => 0,
            'custom_fields' => [
                1 => 'Facebook Ads',
                2 => date('Y-m-d', strtotime('+30 days')),
                3 => 'Moldes navideños Wilton al por mayor'
            ]
        ],
        [
            'title' => 'Lote Navideño Colorantes Dulce Capricho',
            'value' => 850.00,
            'stage_id' => $stages_map['Negociación y Cierre'],
            'account_id' => 7,
            'contact_id' => 7,
            'status' => 'Won',
            'assigned_agent' => 'Carlos Mendoza (Zona Costa)',
            'close_date' => date('Y-m-d', strtotime('-5 days')),
            'bant_budget' => 1, 'bant_authority' => 1, 'bant_need' => 1, 'bant_timeline' => 1,
            'bant_notes' => 'Cerrado y pagado. Despacho coordinado vía Correos de CR.',
            'meddic_metrics' => 1, 'meddic_buyer' => 1, 'meddic_criteria' => 1, 'meddic_process' => 1, 'meddic_pain' => 1, 'meddic_champion' => 1,
            'custom_fields' => [
                1 => 'WhatsApp',
                2 => date('Y-m-d', strtotime('-5 days')),
                3 => 'Colorantes metálicos dorados y plateados Enco'
            ]
        ],

        // EMBUDO 2: LOGÍSTICA Y DESPACHO
        [
            'title' => 'Despacho Batidoras Musmanni',
            'value' => 6250.00,
            'stage_id' => $stages_map['Pedido Recibido'],
            'account_id' => 2,
            'contact_id' => 2,
            'status' => 'Open',
            'assigned_agent' => 'Carlos Mendoza (Zona Costa)',
            'close_date' => date('Y-m-d', strtotime('+4 days')),
            'bant_budget' => 1, 'bant_authority' => 1, 'bant_need' => 1, 'bant_timeline' => 1,
            'bant_notes' => 'Orden de compra recibida. Pendiente de validación de crédito en bodega.',
            'meddic_metrics' => 1, 'meddic_buyer' => 1, 'meddic_criteria' => 1, 'meddic_process' => 1, 'meddic_pain' => 1, 'meddic_champion' => 1,
            'custom_fields' => [
                1 => 'Recomendación',
                2 => date('Y-m-d', strtotime('+5 days')),
                3 => 'Logística: Distribución a sucursales de Herradura'
            ]
        ],
        [
            'title' => 'Envío Lote Colorantes Spoon',
            'value' => 1840.00,
            'stage_id' => $stages_map['Preparación en Bodega'],
            'account_id' => 1,
            'contact_id' => 1,
            'status' => 'Open',
            'assigned_agent' => 'Sofía Castro (GAM Oriente)',
            'close_date' => date('Y-m-d', strtotime('+2 days')),
            'bant_budget' => 1, 'bant_authority' => 1, 'bant_need' => 1, 'bant_timeline' => 1,
            'bant_notes' => 'Lote preparado en palets de plástico en la bodega central de TIPS.',
            'meddic_metrics' => 1, 'meddic_buyer' => 1, 'meddic_criteria' => 1, 'meddic_process' => 1, 'meddic_pain' => 1, 'meddic_champion' => 1,
            'custom_fields' => [
                1 => 'WhatsApp',
                2 => date('Y-m-d', strtotime('+3 days')),
                3 => 'Logística: Fraccionado en cajas de 12 unidades'
            ]
        ],
        [
            'title' => 'Suministro Fondant Café Britt',
            'value' => 3450.00,
            'stage_id' => $stages_map['En Ruta de Entrega'],
            'account_id' => 4,
            'contact_id' => 4,
            'status' => 'Open',
            'assigned_agent' => 'Andrés Herrera (GAM Norte)',
            'close_date' => date('Y-m-d', strtotime('+1 days')),
            'bant_budget' => 1, 'bant_authority' => 1, 'bant_need' => 1, 'bant_timeline' => 1,
            'bant_notes' => 'En camión repartidor de TIPS ruta GAM Norte.',
            'meddic_metrics' => 1, 'meddic_buyer' => 1, 'meddic_criteria' => 1, 'meddic_process' => 1, 'meddic_pain' => 1, 'meddic_champion' => 1,
            'custom_fields' => [
                1 => 'Formulario Web',
                2 => date('Y-m-d', strtotime('+1 days')),
                3 => 'Logística: Entrega en Centro de Distribución Britt Heredia'
            ]
        ],
        [
            'title' => 'Entrega Wilton Moldes Cakeland',
            'value' => 1200.00,
            'stage_id' => $stages_map['Entregado y Confirmado'],
            'account_id' => 5,
            'contact_id' => 5,
            'status' => 'Won',
            'assigned_agent' => 'Sofía Castro (GAM Oriente)',
            'close_date' => date('Y-m-d', strtotime('-2 days')),
            'bant_budget' => 1, 'bant_authority' => 1, 'bant_need' => 1, 'bant_timeline' => 1,
            'bant_notes' => 'Entregado y firmado por el encargado de repostería.',
            'meddic_metrics' => 1, 'meddic_buyer' => 1, 'meddic_criteria' => 1, 'meddic_process' => 1, 'meddic_pain' => 1, 'meddic_champion' => 1,
            'custom_fields' => [
                1 => 'Google Ads',
                2 => date('Y-m-d', strtotime('-2 days')),
                3 => 'Confirmado recibido conforme'
            ]
        ]
    ];

    $stmt_deal = $pdo->prepare("
        INSERT INTO `deals` 
        (`title`, `value`, `stage_id`, `account_id`, `contact_id`, `status`, `assigned_agent`, `close_date`, 
         `bant_budget`, `bant_authority`, `bant_need`, `bant_timeline`, `bant_notes`,
         `meddic_metrics`, `meddic_buyer`, `meddic_criteria`, `meddic_process`, `meddic_pain`, `meddic_champion`) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt_cv = $pdo->prepare("INSERT INTO `deal_custom_values` (`deal_id`, `field_id`, `value`) VALUES (?, ?, ?)");

    foreach ($deals_data as $deal) {
        $stmt_deal->execute([
            $deal['title'],
            $deal['value'],
            $deal['stage_id'],
            $deal['account_id'],
            $deal['contact_id'],
            $deal['status'],
            $deal['assigned_agent'],
            $deal['close_date'],
            $deal['bant_budget'],
            $deal['bant_authority'],
            $deal['bant_need'],
            $deal['bant_timeline'],
            $deal['bant_notes'],
            $deal['meddic_metrics'],
            $deal['meddic_buyer'],
            $deal['meddic_criteria'],
            $deal['meddic_process'],
            $deal['meddic_pain'],
            $deal['meddic_champion']
        ]);
        
        $deal_id = $pdo->lastInsertId();
        
        // Registrar campos personalizados
        foreach ($deal['custom_fields'] as $field_id => $val) {
            $stmt_cv->execute([$deal_id, $field_id, $val]);
        }
        
        // Sembrar 1 actividad por deal
        $due_date = date('Y-m-d H:i:s', strtotime($deal['close_date'] . ' -2 days 10:00:00'));
        $act_status = ($deal['status'] === 'Won') ? 'Completed' : 'Pending';
        
        $stmt_act = $pdo->prepare("INSERT INTO `activities` (`deal_id`, `type`, `subject`, `description`, `due_date`, `status`) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_act->execute([
            $deal_id,
            'Call',
            'Seguimiento: ' . $deal['title'],
            'Coordinar llamada con el cliente para revisar detalles comerciales y logística de entrega.',
            $due_date,
            $act_status
        ]);
    }

    echo "  - 10 tratos comerciales demo enriquecidos insertados.\n";
    echo "  - Actividades y campos personalizados dinámicos vinculados con éxito.\n";
    echo "DATOS DEMO ENRIQUECIDOS INYECTADOS CON ÉXITO\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

<?php
/**
 * ical.php — Feed de iCalendar para TIPS CRM
 */

require_once __DIR__ . '/config.php';

// Validar llave de seguridad
$key = isset($_GET['key']) ? $_GET['key'] : '';
$expected_key = md5("tips_crm_key_salt_2026");

if ($key !== $expected_key) {
    header('HTTP/1.0 403 Forbidden');
    echo "Acceso denegado.";
    exit;
}

// Configurar cabeceras de iCalendar
header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: inline; filename="activities.ics"');

// Obtener todas las actividades pendientes
$stmt = $pdo->query("
    SELECT act.*, d.title AS deal_title 
    FROM activities act
    LEFT JOIN deals d ON act.deal_id = d.id
    WHERE act.status = 'Pending'
    ORDER BY act.due_date ASC
");
$activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "BEGIN:VCALENDAR\r\n";
echo "VERSION:2.0\r\n";
echo "PRODID:-//TIPS S.A.//TIPS CRM iCalendar Feed//ES\r\n";
echo "CALSCALE:GREGORIAN\r\n";
echo "METHOD:PUBLISH\r\n";
echo "X-WR-CALNAME:Actividades TIPS CRM\r\n";
echo "X-WR-TIMEZONE:America/Costa_Rica\r\n";

foreach ($activities as $act) {
    // Formatear fechas para iCal (formato YYYYMMDDTHHMMSSZ)
    $due_time = strtotime($act['due_date']);
    $start_date = date('Ymd\THis', $due_time);
    // Asumir duración de 1 hora por defecto
    $end_date = date('Ymd\THis', $due_time + 3600);
    $created_date = date('Ymd\THis', strtotime($act['created_at']));
    
    $uid = "activity-" . $act['id'] . "@tips-crm.local";
    
    // Asunto amigable según tipo
    $subject = '[' . $act['type'] . '] ' . $act['subject'];
    if ($act['deal_title']) {
        $subject .= ' (Trato: ' . $act['deal_title'] . ')';
    }
    
    // Limpiar notas y saltos de línea para iCal
    $description = $act['description'] ?: 'Sin descripción adicional.';
    $description = str_replace(["\r", "\n"], ["", "\\n"], $description);
    
    echo "BEGIN:VEVENT\r\n";
    echo "UID:" . $uid . "\r\n";
    echo "DTSTAMP:" . $created_date . "\r\n";
    echo "DTSTART:" . $start_date . "\r\n";
    echo "DTEND:" . $end_date . "\r\n";
    echo "SUMMARY:" . escape_ical($subject) . "\r\n";
    echo "DESCRIPTION:" . escape_ical($description) . "\r\n";
    echo "STATUS:CONFIRMED\r\n";
    echo "END:VEVENT\r\n";
}

echo "END:VCALENDAR\r\n";

function escape_ical($text) {
    $text = str_replace('\\', '\\\\', $text);
    $text = str_replace(',', '\\,', $text);
    $text = str_replace(';', '\\;', $text);
    return $text;
}

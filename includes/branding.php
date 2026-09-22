<?php
/**
 * branding.php — Resuelve el nombre de marca y los logos personalizados del CRM.
 */
function get_brand_settings(PDO $pdo) {
    static $cached = null;
    if ($cached !== null) return $cached;

    $rows = [];
    try {
        $rows = $pdo->query("SELECT setting_key, setting_value FROM crm_settings WHERE setting_key IN ('crm_name', 'brand_logo_dark_path', 'brand_logo_light_path')")->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (Exception $e) {
        // crm_settings puede no existir en una instalación nueva; se usan los valores por defecto.
    }

    $cached = [
        'name'       => !empty($rows['crm_name']) ? $rows['crm_name'] : 'TIPS CRM',
        'logo_dark'  => !empty($rows['brand_logo_dark_path']) ? $rows['brand_logo_dark_path'] : 'assets/image/logo_blanco.png',
        'logo_light' => !empty($rows['brand_logo_light_path']) ? $rows['brand_logo_light_path'] : 'assets/image/log_azul.png',
    ];
    return $cached;
}

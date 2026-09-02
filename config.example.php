<?php
/**
 * config.example.php — Plantilla de configuración del CRM.
 *
 * Copiá este archivo a `config.php` y completá los valores reales.
 * `config.php` está en `.gitignore` y NUNCA debe commitearse (contiene secretos).
 *
 * El `.htaccess` del proyecto además bloquea el acceso HTTP directo a `config.php`.
 */

// ── Base de datos ────────────────────────────────────────────────────────────
// La base se crea sola si el usuario tiene permiso CREATE (ver bloque PDO abajo).
if (!defined('CRM_DB_HOST')) define('CRM_DB_HOST', '127.0.0.1');
if (!defined('CRM_DB_PORT')) define('CRM_DB_PORT', '3306');
if (!defined('CRM_DB_USER')) define('CRM_DB_USER', 'root');
if (!defined('CRM_DB_PASS')) define('CRM_DB_PASS', '');
if (!defined('CRM_DB_NAME')) define('CRM_DB_NAME', 'tips_crm');

// ── Integración con la tienda (PrestaShop) — opcional ────────────────────────
if (!defined('CRM_PS_SHOP_URL')) define('CRM_PS_SHOP_URL', 'https://tu-tienda.example');
if (!defined('CRM_PS_API_KEY')) define('CRM_PS_API_KEY', 'TU_WEBSERVICE_API_KEY');

// ── Conexión PDO ─────────────────────────────────────────────────────────────
try {
    // Conexión inicial al servidor para crear la base si no existe.
    $pdo_init = new PDO(
        "mysql:host=" . CRM_DB_HOST . ";port=" . CRM_DB_PORT . ";charset=utf8mb4",
        CRM_DB_USER,
        CRM_DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]
    );
    $pdo_init->exec("CREATE DATABASE IF NOT EXISTS `" . CRM_DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    $pdo_init = null;

    $pdo = new PDO(
        "mysql:host=" . CRM_DB_HOST . ";port=" . CRM_DB_PORT . ";dbname=" . CRM_DB_NAME . ";charset=utf8mb4",
        CRM_DB_USER,
        CRM_DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]
    );
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}

// ── Correo saliente (SMTP) ───────────────────────────────────────────────────
// Parametrizable desde Configuración → Correo (SMTP): las claves `smtp_*` viven
// en la tabla `crm_settings`. Estos valores son solo el fallback inicial.
$smtp_cfg = [];
try {
    $smtp_cfg = $pdo->query("SELECT setting_key, setting_value FROM crm_settings WHERE setting_key LIKE 'smtp\\_%'")
                    ->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
} catch (Exception $e) {
    // `crm_settings` puede no existir todavía en una instalación nueva.
}

if (!defined('SMTP_HOST'))       define('SMTP_HOST',       $smtp_cfg['smtp_host']       ?? 'smtp.example.com');
if (!defined('SMTP_PORT'))       define('SMTP_PORT',       (int) ($smtp_cfg['smtp_port'] ?? 465));
if (!defined('SMTP_SECURE'))     define('SMTP_SECURE',     $smtp_cfg['smtp_secure']     ?? 'ssl');
if (!defined('SMTP_USER'))       define('SMTP_USER',       $smtp_cfg['smtp_user']       ?? 'no-reply@example.com');
if (!defined('SMTP_PASS'))       define('SMTP_PASS',       $smtp_cfg['smtp_pass']       ?? 'TU_SMTP_PASSWORD');
if (!defined('SMTP_FROM_EMAIL')) define('SMTP_FROM_EMAIL', $smtp_cfg['smtp_from_email'] ?? SMTP_USER);
if (!defined('SMTP_FROM_NAME'))  define('SMTP_FROM_NAME',  $smtp_cfg['smtp_from_name']  ?? 'CRM');

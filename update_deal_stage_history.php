<?php
/**
 * update_deal_stage_history.php — Parche de BD para el timeline de embudo por negocio.
 *
 * Crea `deal_stage_history` (una fila por cada vez que un negocio entra a una fase) y
 * la columna `deals.closed_at` (momento de conversión Ganado/Perdido). Hace un backfill
 * APROXIMADO de los negocios existentes: reparte la entrada a cada fase entre
 * `created_at` y la fecha de cierre (o hoy). El tracking exacto fase-a-fase arranca
 * cuando `api.php` empieza a registrar cada cambio de etapa.
 *
 * Script de un solo uso — igual que el resto de update_*.php NO se sube a producción;
 * se corre una vez vía HTTPS contra la BD de prod y luego se stubbea a HTTP 410.
 */

require_once __DIR__ . '/config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>TIPS CRM — Historial de Fases del Embudo</title>
    <link href='https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap' rel='stylesheet'>
    <style>
        body { background-color: #070a13; color: #f1f5f9; font-family: 'Outfit', sans-serif; padding: 3rem 1rem; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .setup-card { background: rgba(18, 24, 48, 0.65); border: 1px solid rgba(255, 255, 255, 0.08); backdrop-filter: blur(16px); border-radius: 16px; padding: 2.5rem; max-width: 620px; width: 100%; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5); }
        h1 { color: #06b6d4; font-size: 1.6rem; margin-bottom: 0.5rem; border-bottom: 2px dashed rgba(6, 182, 212, 0.2); padding-bottom: 0.5rem; }
        .step { margin: 1rem 0; padding-left: 1.5rem; border-left: 3px solid #06b6d4; font-size: 0.95rem; }
        .step.success { border-left-color: #10b981; }
        .btn-go { display: inline-block; background: linear-gradient(135deg, #06b6d4, #a855f7); color: #fff; padding: 0.75rem 1.5rem; border-radius: 8px; text-decoration: none; font-weight: 600; margin-top: 1.5rem; }
        .btn-go:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(6, 182, 212, 0.3); }
    </style>
</head>
<body>
<div class='setup-card'>
    <h1>Actualización CRM: Timeline de Embudo por Negocio</h1>
";

try {
    // 1. Tabla de historial de fases
    $pdo->exec("CREATE TABLE IF NOT EXISTS `deal_stage_history` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `deal_id` INT NOT NULL,
        `stage_id` INT NOT NULL,
        `entered_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `changed_by` VARCHAR(150) DEFAULT NULL,
        FOREIGN KEY (`deal_id`) REFERENCES `deals`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`stage_id`) REFERENCES `stages`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    echo "<div class='step success'>✔️ Tabla <code>deal_stage_history</code> creada (o ya existía).</div>";

    // 2. Columna de cierre en deals
    try {
        $pdo->exec("ALTER TABLE `deals` ADD COLUMN `closed_at` DATETIME NULL DEFAULT NULL;");
        echo "<div class='step success'>✔️ Columna <code>deals.closed_at</code> agregada.</div>";
    } catch (Exception $e) {
        echo "<div class='step success'>✔️ La columna <code>deals.closed_at</code> ya existía.</div>";
    }

    // 3. Backfill aproximado — solo si la tabla está vacía (o con ?reset=1 para rehacerlo)
    if (isset($_GET['reset'])) {
        $pdo->exec("DELETE FROM `deal_stage_history`");
        $pdo->exec("UPDATE `deals` SET `closed_at` = NULL");
        echo "<div class='step'>♻️ <code>?reset=1</code>: historial vaciado y <code>closed_at</code> limpiado antes de rehacer el backfill.</div>";
    }
    $already = (int) $pdo->query("SELECT COUNT(*) FROM `deal_stage_history`")->fetchColumn();
    if ($already > 0) {
        echo "<div class='step'>ℹ️ <code>deal_stage_history</code> ya tiene {$already} registros — se omite el backfill para no duplicar (usa <code>?reset=1</code> para rehacerlo).</div>";
    } else {
        $deals = $pdo->query("
            SELECT d.id, d.stage_id, d.status, d.created_at, d.updated_at, d.close_date,
                   s.pipeline_id, s.position AS cur_pos
            FROM deals d JOIN stages s ON d.stage_id = s.id
        ")->fetchAll(PDO::FETCH_ASSOC);

        // entered_at se calcula relativo a `deals.created_at` (reloj de la BD) y nunca
        // supera NOW() — así evitamos mezclar el reloj de PHP con el de MySQL.
        $ins  = $pdo->prepare("INSERT INTO `deal_stage_history` (`deal_id`, `stage_id`, `entered_at`, `changed_by`)
                               VALUES (?, ?, LEAST(DATE_ADD(?, INTERVAL ? SECOND), NOW()), 'Migración')");
        $stmt_stages = $pdo->prepare("SELECT id FROM stages WHERE pipeline_id = ? AND position <= ? ORDER BY position");
        $upd_closed = $pdo->prepare("UPDATE `deals` SET `closed_at` = LEAST(DATE_ADD(`created_at`, INTERVAL ? SECOND), NOW()) WHERE `id` = ?");

        $rows_inserted = 0;
        $deals_closed = 0;

        foreach ($deals as $d) {
            $stmt_stages->execute([$d['pipeline_id'], $d['cur_pos']]);
            $path = $stmt_stages->fetchAll(PDO::FETCH_COLUMN);
            if (!$path) { $path = [(int) $d['stage_id']]; }

            $start = strtotime($d['created_at']) ?: time();
            $is_closed = in_array($d['status'], ['Won', 'Lost'], true);

            // Fin del recorrido: fecha de cierre si es coherente, si no la última modificación / hoy.
            $end = null;
            if ($is_closed && !empty($d['close_date'])) {
                $cd = strtotime($d['close_date'] . ' 17:00:00');
                if ($cd && $cd > $start) $end = $cd;
            }
            if ($end === null) {
                $end = $is_closed ? (strtotime($d['updated_at']) ?: time()) : time();
            }
            if ($end <= $start) {
                // Sin margen real: sintético, ~2 días por fase recorrida.
                $end = $start + max(count($path), 1) * 2 * 86400;
            }

            $span = max(0, $end - $start); // duración total del recorrido, en segundos
            $n = count($path);
            foreach ($path as $i => $sid) {
                // i=0 -> created_at (offset 0); resto repartido proporcionalmente.
                $offset = ($n <= 1) ? 0 : (int) round($span * ($i / $n));
                $ins->execute([$d['id'], $sid, $d['created_at'], $offset]);
                $rows_inserted++;
            }

            if ($is_closed) {
                $upd_closed->execute([$span, $d['id']]);
                $deals_closed++;
            }
        }

        echo "<div class='step success'>✔️ Backfill: {$rows_inserted} filas de historial en " . count($deals) . " negocios; {$deals_closed} marcados con <code>closed_at</code>.</div>";
    }

    echo "<p style='margin-top: 1.5rem; color: #10b981; font-weight: 600;'>¡Timeline de embudo listo!</p>";
    echo "<a href='accounts.php' class='btn-go'>Ir al Directorio de Empresas</a>";

} catch (Exception $e) {
    echo "<div class='step' style='border-left-color: #ef4444; color: #fca5a5;'>❌ Error durante la actualización: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "
</div>
</body>
</html>";

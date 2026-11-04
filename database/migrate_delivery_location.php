<?php
// CLI only. Compatible with MySQL and MariaDB, including repeated runs.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../config/database.php';
$pdo = db();
foreach (['shipping_latitude', 'shipping_longitude'] as $column) {
    $st = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = ?");
    $st->execute([$column]);
    if ((int)$st->fetchColumn() > 0) {
        echo $column . ': already present' . PHP_EOL;
        continue;
    }
    $pdo->exec('ALTER TABLE orders ADD COLUMN `' . $column . '` DECIMAL(10,7) NULL');
    echo $column . ': added' . PHP_EOL;
}

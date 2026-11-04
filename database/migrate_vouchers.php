<?php
// CLI only. Idempotent on MySQL and MariaDB. Never changes existing order totals.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../config/database.php';
$pdo = db();
$sql = file_get_contents(__DIR__ . '/Vouchers.sql');
$tableSql = strstr($sql, 'ALTER TABLE orders', true);
$tableSql = preg_replace('/^--.*$/m', '', $tableSql);
foreach (explode(';', $tableSql) as $statement) {
    if (trim($statement) !== '') $pdo->exec($statement);
}
foreach ([
    'subtotal' => 'DECIMAL(12,0) NULL',
    'discount_amount' => 'DECIMAL(12,0) NOT NULL DEFAULT 0',
    'voucher_code' => 'VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NULL',
] as $column => $definition) {
    $st = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = ?");
    $st->execute([$column]);
    if ((int)$st->fetchColumn() > 0) {
        echo $column . ': already present' . PHP_EOL;
    } else {
        $pdo->exec('ALTER TABLE orders ADD COLUMN `' . $column . '` ' . $definition);
        echo $column . ': added' . PHP_EOL;
    }
}
echo 'Voucher schema ready.' . PHP_EOL;

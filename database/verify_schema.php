<?php
// Run only against a disposable MariaDB instance:
// C:\xampp\php\php.exe verify_schema.php 33317
if (PHP_SAPI !== 'cli' || !isset($argv[1]) || !ctype_digit($argv[1])) {
    exit("CLI usage: php verify_schema.php <test-server-port>\n");
}
$pdo = new PDO('mysql:host=127.0.0.1;port=' . $argv[1] . ';charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);
$name = 'fishshop_schema_test_' . bin2hex(random_bytes(6));
$pdo->exec("CREATE DATABASE `$name`");
$passed = 0;
function reject(PDO $pdo, string $sql, int $expectedCode): void {
    global $passed;
    try {
        $pdo->exec($sql);
    } catch (PDOException $e) {
        if ((int)$e->errorInfo[1] !== $expectedCode) throw $e;
        $passed++;
        return;
    }
    throw new RuntimeException('Invalid data was accepted: ' . $sql);
}
try {
    $pdo->exec("USE `$name`");
    $pdo->exec(file_get_contents(__DIR__ . '/WebBanCaUpgrade.sql'));
    $pdo->exec("INSERT INTO categories (id, name) VALUES (1, 'Test')");
    $pdo->exec("INSERT INTO products (id, name, price, category_id, stock) VALUES (1, 'Test', 1000, 1, 10)");
    $pdo->exec("INSERT INTO cart (id, user_id) VALUES (1, 2)");
    $pdo->exec("INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (1, 1, 1)");
    $pdo->exec("INSERT INTO orders (id, user_id, total, shipping_address, phone) VALUES (1, 2, 1000, 'Test address', '0123456789')");
    $pdo->exec("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (1, 1, 1, 1000)");
    reject($pdo, 'UPDATE cart_items SET quantity = 0', 4025);
    reject($pdo, 'UPDATE order_items SET quantity = 0', 4025);
    reject($pdo, 'UPDATE order_items SET price = -1', 4025);
    reject($pdo, 'UPDATE products SET price = 0', 4025);
    reject($pdo, 'UPDATE products SET price = -1', 4025);
    reject($pdo, 'UPDATE orders SET total = -1', 4025);
    reject($pdo, 'INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (1, 1, 2, 1000)', 1062);
    reject($pdo, 'INSERT INTO rank_order_earnings (order_id, user_id) VALUES (1, 1)', 1452);
    $pdo->exec('INSERT INTO rank_order_earnings (order_id, user_id) VALUES (1, 2)');
    reject($pdo, 'UPDATE rank_order_earnings SET is_active = 2', 4025);
    reject($pdo, 'UPDATE users SET locked_by_admin_id = 999 WHERE id = 2', 1452);
    reject($pdo, "INSERT INTO user_account_lock_history (user_id, action, performed_by_admin_id) VALUES (999, 'lock', 1)", 1452);
    reject($pdo, "INSERT INTO user_account_lock_history (user_id, action, performed_by_admin_id) VALUES (2, 'lock', 999)", 1452);
    reject($pdo, 'UPDATE customer_ranks SET total_exp = 1 WHERE user_id = 2', 4025);
    reject($pdo, 'UPDATE customer_ranks SET spend_remainder = 1000 WHERE user_id = 2', 4025);
    reject($pdo, "UPDATE customer_ranks SET current_rank = 'bac' WHERE user_id = 2", 4025);

    // Exercise the real rank updater, including retries and reward reversal.
    require_once __DIR__ . '/../includes/functions.php';
    $pdo->exec('DELETE FROM rank_order_earnings');
    $pdo->exec('UPDATE order_items SET price = 100999');
    $pdo->exec("UPDATE orders SET total = 100999, status = 'completed', payment_status = 'paid'");
    foreach ([true, true, false] as $eligible) {
        $pdo->beginTransaction();
        if (!$eligible) $pdo->exec("UPDATE orders SET status = 'cancelled'");
        sync_order_rank($pdo, 1);
        $profile = $pdo->query('SELECT * FROM customer_ranks WHERE user_id = 2')->fetch(PDO::FETCH_ASSOC);
        if ((int)$profile['total_exp'] !== ($eligible ? 100 : 0)
            || (int)$profile['spend_remainder'] !== ($eligible ? 999 : 0)
            || $profile['current_rank'] !== ($eligible ? 'bac' : 'dong')) {
            throw new RuntimeException('Rank synchronization failed');
        }
        $pdo->commit();
        $passed++;
    }
    echo "PASS: import, valid inserts and $passed constraint/rank checks\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $pdo->exec("DROP DATABASE `$name`");
}

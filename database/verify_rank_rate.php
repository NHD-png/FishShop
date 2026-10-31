<?php
// CLI-only checks. Temporary tables isolate fixtures from actual shop data.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../includes/functions.php';
$pdo = db();
$pdo->exec('CREATE TEMPORARY TABLE orders (id INT PRIMARY KEY,user_id INT,total BIGINT,shipping_address TEXT,phone VARCHAR(20),status VARCHAR(30),payment_status VARCHAR(30)) ENGINE=InnoDB');
$pdo->exec('CREATE TEMPORARY TABLE order_items (order_id INT,product_id INT,quantity INT,price BIGINT) ENGINE=InnoDB');
$pdo->exec('CREATE TEMPORARY TABLE rank_order_earnings (order_id INT PRIMARY KEY,user_id INT,spend_amount BIGINT,is_active INT) ENGINE=InnoDB');
$pdo->exec('CREATE TEMPORARY TABLE customer_ranks (user_id INT PRIMARY KEY,total_exp BIGINT,total_eligible_spend BIGINT,spend_remainder INT,current_rank VARCHAR(24),last_rank_up_at DATETIME NULL, CHECK(total_exp = total_eligible_spend DIV 10000), CHECK(spend_remainder = total_eligible_spend MOD 10000)) ENGINE=InnoDB');
$pdo->exec("INSERT INTO orders (id,user_id,total,shipping_address,phone,status,payment_status) VALUES (1,1,25000,'Test','0123456789','pending','paid'),(2,1,5000,'Test','0123456789','completed','paid')");
$pdo->exec('INSERT INTO order_items (order_id,product_id,quantity,price) VALUES (1,1,1,25000),(2,1,1,5000)');
function checkRank(PDO $pdo, int $orderId, int $exp, int $remainder): void {
    $pdo->beginTransaction();
    sync_order_rank($pdo, $orderId);
    $row = $pdo->query('SELECT total_exp,spend_remainder FROM customer_ranks WHERE user_id=1')->fetch();
    if ((int)$row['total_exp'] !== $exp || (int)$row['spend_remainder'] !== $remainder) {
        $pdo->rollBack();
        throw new RuntimeException('Unexpected EXP or remainder: '.json_encode($row));
    }
    $pdo->commit();
}
checkRank($pdo, 1, 0, 0);
$pdo->exec("UPDATE orders SET status='completed', payment_status='unpaid' WHERE id=1");
checkRank($pdo, 1, 0, 0);
$pdo->exec("UPDATE orders SET payment_status='paid' WHERE id=1");
checkRank($pdo, 1, 2, 5000);
checkRank($pdo, 1, 2, 5000);
checkRank($pdo, 2, 3, 0);
$pdo->exec("UPDATE orders SET status='cancelled' WHERE id=1");
checkRank($pdo, 1, 0, 5000);
echo "PASS: pending/unpaid excluded; 25,000 = 2 EXP + 5,000 remainder; no duplicate; cumulative remainder; cancellation reversal.\n";

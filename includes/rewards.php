<?php
function reward_schema(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS prize_wallets (
        user_id INT UNSIGNED PRIMARY KEY, balance BIGINT UNSIGNED NOT NULL DEFAULT 0,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_prize_wallet_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS prize_rewards (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id INT UNSIGNED NOT NULL,
        reward_type ENUM('miss','coupon','fish') NOT NULL, discount_percent TINYINT UNSIGNED NOT NULL DEFAULT 0,
        product_id INT UNSIGNED NULL, status ENUM('available','used') NOT NULL DEFAULT 'available',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, used_at DATETIME NULL,
        INDEX(user_id,status), CONSTRAINT fk_prize_reward_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS prize_spins (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id INT UNSIGNED NOT NULL,
        reward_id BIGINT UNSIGNED NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX(user_id,created_at), CONSTRAINT fk_prize_spin_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE, CONSTRAINT fk_prize_spin_reward FOREIGN KEY (reward_id) REFERENCES prize_rewards(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS wallet_deposits (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id INT UNSIGNED NOT NULL,
        amount BIGINT UNSIGNED NOT NULL, reference_mode ENUM('with_code','no_code') NOT NULL DEFAULT 'no_code',
        request_type ENUM('payos','direct') NOT NULL DEFAULT 'direct',
        sender_name VARCHAR(100) NOT NULL DEFAULT '', status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
        payos_payment_link_id VARCHAR(80) NULL, payos_checkout_url VARCHAR(500) NULL, paid_at DATETIME NULL,
        payos_bank_bin VARCHAR(16) NULL, payos_account_number VARCHAR(40) NULL,
        payos_account_name VARCHAR(150) NULL, payos_transfer_description VARCHAR(100) NULL, payos_qr_code TEXT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, reviewed_at DATETIME NULL, reviewed_by INT UNSIGNED NULL,
        INDEX(status,created_at), INDEX(user_id,created_at),
        CONSTRAINT fk_wallet_deposit_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_wallet_deposit_reviewer FOREIGN KEY(reviewed_by) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $walletColumns = $pdo->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='wallet_deposits'")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('reference_mode', $walletColumns, true)) {
        $pdo->exec("ALTER TABLE wallet_deposits ADD COLUMN reference_mode ENUM('with_code','no_code') NOT NULL DEFAULT 'with_code' AFTER amount");
    }
    if (!in_array('sender_name', $walletColumns, true)) {
        $pdo->exec("ALTER TABLE wallet_deposits ADD COLUMN sender_name VARCHAR(100) NOT NULL DEFAULT '' AFTER reference_mode");
    }
    if (!in_array('payos_payment_link_id', $walletColumns, true)) {
        $pdo->exec("ALTER TABLE wallet_deposits ADD COLUMN payos_payment_link_id VARCHAR(80) NULL AFTER status");
    }
    if (!in_array('payos_checkout_url', $walletColumns, true)) {
        $pdo->exec("ALTER TABLE wallet_deposits ADD COLUMN payos_checkout_url VARCHAR(500) NULL AFTER payos_payment_link_id");
    }
    if (!in_array('paid_at', $walletColumns, true)) {
        $pdo->exec("ALTER TABLE wallet_deposits ADD COLUMN paid_at DATETIME NULL AFTER payos_checkout_url");
    }
    if (!in_array('payos_bank_bin', $walletColumns, true)) {
        $pdo->exec("ALTER TABLE wallet_deposits ADD COLUMN payos_bank_bin VARCHAR(16) NULL AFTER paid_at");
    }
    if (!in_array('payos_account_number', $walletColumns, true)) {
        $pdo->exec("ALTER TABLE wallet_deposits ADD COLUMN payos_account_number VARCHAR(40) NULL AFTER payos_bank_bin");
    }
    if (!in_array('payos_account_name', $walletColumns, true)) {
        $pdo->exec("ALTER TABLE wallet_deposits ADD COLUMN payos_account_name VARCHAR(150) NULL AFTER payos_account_number");
    }
    if (!in_array('payos_transfer_description', $walletColumns, true)) {
        $pdo->exec("ALTER TABLE wallet_deposits ADD COLUMN payos_transfer_description VARCHAR(100) NULL AFTER payos_account_name");
    }
    if (!in_array('payos_qr_code', $walletColumns, true)) {
        $pdo->exec("ALTER TABLE wallet_deposits ADD COLUMN payos_qr_code TEXT NULL AFTER payos_transfer_description");
    }
    if (!in_array('request_type', $walletColumns, true)) {
        $pdo->exec("ALTER TABLE wallet_deposits ADD COLUMN request_type ENUM('payos','direct') NOT NULL DEFAULT 'direct' AFTER reference_mode");
        $pdo->exec("UPDATE wallet_deposits SET request_type='payos' WHERE payos_payment_link_id IS NOT NULL");
    }
    $hasPayosLinkIndex = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='wallet_deposits' AND INDEX_NAME='uq_wallet_deposit_payos_link'")->fetchColumn();
    if (!$hasPayosLinkIndex) $pdo->exec("ALTER TABLE wallet_deposits ADD UNIQUE KEY uq_wallet_deposit_payos_link (payos_payment_link_id)");
}

function prize_wallet(PDO $pdo, int $userId): array {
    $pdo->prepare('INSERT INTO prize_wallets(user_id) VALUES(?) ON DUPLICATE KEY UPDATE user_id=VALUES(user_id)')->execute([$userId]);
    $st=$pdo->prepare('SELECT balance FROM prize_wallets WHERE user_id=?'); $st->execute([$userId]);
    return $st->fetch() ?: ['balance'=>0];
}

function prize_spin_odds(): array {
    // Server-owned fixed odds: customers cannot change their own winning chance.
    return ['miss_bp'=>9000,'coupon_bp'=>990,'fish_bp'=>10];
}

function prize_coupon_line_quote(int $price, int $quantity, int $availableCoupons): array {
    $units = max(0, min($quantity, $availableCoupons));
    $unitDiscount = intdiv(max(0, $price) * 5, 100);
    return ['units' => $units, 'unit_discount' => $unitDiscount, 'discount' => $units * $unitDiscount];
}

// Share the same reward preview between the cart and checkout. Rewards are
// checked again under row locks when the order is created.
function prize_cart_quote(PDO $pdo, int $userId, array $lines, array $selection): array {
    $quote = ['coupon_reward_id'=>0, 'coupon_product_id'=>0, 'coupon_units'=>0,
        'coupon_unit_discount'=>0, 'coupon_discount'=>0,
        'fish_reward_id'=>0, 'fish_product_id'=>0, 'fish_discount'=>0];
    if ($userId < 1 || !$lines) return $quote;
    $couponId = (int)($selection['prize_coupon_reward'] ?? 0);
    $couponProduct = (int)($selection['prize_coupon_product'] ?? 0);
    $fishId = (int)($selection['prize_fish_reward'] ?? 0);
    $fishProduct = (int)($selection['prize_fish_product'] ?? 0);
    if (!(($couponId > 0 && $couponProduct > 0) || ($fishId > 0 && $fishProduct > 0))) return $quote;
    reward_schema($pdo);
    if ($couponId > 0 && $couponProduct > 0) {
        $st = $pdo->prepare("SELECT id FROM prize_rewards WHERE user_id=? AND reward_type='coupon' AND discount_percent=5 AND status='available'");
        $st->execute([$userId]);
        $ids = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
        if (in_array($couponId, $ids, true)) {
            foreach ($lines as $line) {
                if ((int)$line['product_id'] !== $couponProduct) continue;
                $discount = prize_coupon_line_quote((int)$line['price'], (int)$line['quantity'], count($ids));
                if ($discount['discount'] > 0) {
                    $quote['coupon_reward_id'] = $couponId;
                    $quote['coupon_product_id'] = $couponProduct;
                    $quote['coupon_units'] = $discount['units'];
                    $quote['coupon_unit_discount'] = $discount['unit_discount'];
                    $quote['coupon_discount'] = $discount['discount'];
                }
                break;
            }
        }
    }
    if (!$quote['coupon_discount'] && $fishId > 0 && $fishProduct > 0) {
        $st = $pdo->prepare("SELECT id FROM prize_rewards WHERE id=? AND user_id=? AND reward_type='fish' AND status='available' AND product_id=?");
        $st->execute([$fishId, $userId, $fishProduct]);
        if ($st->fetchColumn()) {
            foreach ($lines as $line) {
                if ((int)$line['product_id'] !== $fishProduct || (int)$line['quantity'] < 1) continue;
                $quote['fish_reward_id'] = $fishId;
                $quote['fish_product_id'] = $fishProduct;
                $quote['fish_discount'] = (int)$line['price'];
                break;
            }
        }
    }
    return $quote;
}

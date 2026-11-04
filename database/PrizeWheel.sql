-- FishShop prize wheel schema. Safe to import more than once into WebBanCa.
CREATE TABLE IF NOT EXISTS prize_wallets (
  user_id INT UNSIGNED PRIMARY KEY,
  balance BIGINT UNSIGNED NOT NULL DEFAULT 0,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_prize_wallet_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS prize_rewards (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  reward_type ENUM('miss','coupon','fish') NOT NULL,
  discount_percent TINYINT UNSIGNED NOT NULL DEFAULT 0,
  product_id INT UNSIGNED NULL,
  status ENUM('available','used') NOT NULL DEFAULT 'available',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  used_at DATETIME NULL,
  INDEX idx_prize_reward_user_status (user_id,status),
  CONSTRAINT fk_prize_reward_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS prize_spins (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  reward_id BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_prize_spin_user_created (user_id,created_at),
  CONSTRAINT fk_prize_spin_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_prize_spin_reward FOREIGN KEY (reward_id) REFERENCES prize_rewards(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS wallet_deposits (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  amount BIGINT UNSIGNED NOT NULL,
  reference_mode ENUM('with_code','no_code') NOT NULL DEFAULT 'no_code',
  request_type ENUM('payos','direct') NOT NULL DEFAULT 'direct',
  sender_name VARCHAR(100) NOT NULL DEFAULT '',
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  payos_payment_link_id VARCHAR(80) NULL,
  payos_checkout_url VARCHAR(500) NULL,
  paid_at DATETIME NULL,
  payos_bank_bin VARCHAR(16) NULL,
  payos_account_number VARCHAR(40) NULL,
  payos_account_name VARCHAR(150) NULL,
  payos_transfer_description VARCHAR(100) NULL,
  payos_qr_code TEXT NULL,
  UNIQUE KEY uq_wallet_deposit_payos_link (payos_payment_link_id),
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  reviewed_at DATETIME NULL,
  reviewed_by INT UNSIGNED NULL,
  INDEX idx_wallet_deposit_status_created (status,created_at),
  INDEX idx_wallet_deposit_user_created (user_id,created_at),
  CONSTRAINT fk_wallet_deposit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_wallet_deposit_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- MariaDB/XAMPP: select WebBanCa before importing; safe to repeat.
-- Old orders retain NULL subtotal and no discount.
CREATE TABLE IF NOT EXISTS vouchers (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL UNIQUE,
  name VARCHAR(100) NOT NULL,
  discount_type ENUM('fixed','percent') NOT NULL,
  discount_value DECIMAL(12,0) UNSIGNED NOT NULL,
  min_order_amount DECIMAL(12,0) UNSIGNED NOT NULL DEFAULT 0,
  max_discount_amount DECIMAL(12,0) UNSIGNED NULL,
  starts_at DATETIME NULL,
  ends_at DATETIME NULL,
  usage_limit INT UNSIGNED NULL,
  per_user_limit INT UNSIGNED NOT NULL DEFAULT 1,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS voucher_usages (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  voucher_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  order_id INT UNSIGNED NOT NULL UNIQUE,
  discount_amount DECIMAL(12,0) UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_voucher_usage_user (voucher_id, user_id),
  CONSTRAINT fk_voucher_usage_voucher FOREIGN KEY (voucher_id) REFERENCES vouchers(id) ON UPDATE CASCADE,
  CONSTRAINT fk_voucher_usage_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_voucher_usage_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE orders
  ADD COLUMN IF NOT EXISTS subtotal DECIMAL(12,0) NULL,
  ADD COLUMN IF NOT EXISTS discount_amount DECIMAL(12,0) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS voucher_code VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NULL;

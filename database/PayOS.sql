-- Chọn database WebBanCa rồi chạy. Không xóa hoặc sửa đơn hàng cũ.
ALTER TABLE orders
  ADD COLUMN payment_method ENUM('cash','bank_transfer','payos') NOT NULL DEFAULT 'bank_transfer',
  ADD COLUMN payos_payment_link_id VARCHAR(80) NULL,
  ADD COLUMN payos_checkout_url VARCHAR(500) NULL,
  ADD COLUMN payos_paid_at DATETIME NULL;

-- Chọn database WebBanCa trước khi chạy. MariaDB/XAMPP: chạy lại an toàn.
-- Chỉ thêm cột, không sửa hoặc xóa đơn hàng cũ.
ALTER TABLE orders
  ADD COLUMN IF NOT EXISTS shipping_latitude DECIMAL(10,7) NULL,
  ADD COLUMN IF NOT EXISTS shipping_longitude DECIMAL(10,7) NULL;

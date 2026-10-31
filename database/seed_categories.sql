-- Chọn database WebBanCa trước khi nhập file trong phpMyAdmin.
-- Thêm 6 danh mục; chạy lại không tạo trùng hoặc sửa danh mục đã có.
-- Không thêm sản phẩm và không thay đổi dữ liệu khác.
SET NAMES utf8mb4;

INSERT INTO categories (name, description, status)
SELECT seed.name, seed.description, 'active'
FROM (
    SELECT 'Cá nước ngọt' AS name, 'Các loài cá sống trong môi trường nước ngọt.' AS description
    UNION ALL SELECT 'Cá nước mặn', 'Các loài cá sống trong môi trường nước mặn.'
    UNION ALL SELECT 'Cá cảnh', 'Các loài cá nuôi làm cảnh.'
    UNION ALL SELECT 'Sinh vật thần thoại', 'Các sinh vật trong thần thoại và truyền thuyết.'
    UNION ALL SELECT 'Sinh vật cổ đại', 'Các sinh vật cổ đại và tiền sử.'
    UNION ALL SELECT 'Khác', 'Các sản phẩm không thuộc những danh mục trên.'
) AS seed
WHERE NOT EXISTS (
    SELECT 1 FROM categories AS existing WHERE existing.name = seed.name
);

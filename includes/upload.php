<?php
// Tải ảnh lên an toàn. Trả về ['ok'=>bool, 'path'=>'uploads/...', 'error'=>'...']
function save_uploaded_image(array $file, string $subDir): array
{
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) return ['ok' => false, 'error' => 'none'];
    if ($file['error'] !== UPLOAD_ERR_OK) return ['ok' => false, 'error' => 'Tải ảnh thất bại (mã lỗi ' . (int)$file['error'] . ').'];
    if ($file['size'] > 2 * 1024 * 1024) return ['ok' => false, 'error' => 'Ảnh vượt quá 2MB.'];
    if (!is_uploaded_file($file['tmp_name'])) return ['ok' => false, 'error' => 'Tệp tải lên không hợp lệ.'];

    // Kiểm tra loại file THỰC TẾ, không tin tên file hay MIME do trình duyệt gửi.
    $info = @getimagesize($file['tmp_name']);
    $allowed = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    if ($info === false || !isset($allowed[$info[2]])) return ['ok' => false, 'error' => 'Chỉ chấp nhận ảnh JPG, PNG hoặc WEBP.'];

    $dir = __DIR__ . '/../uploads/' . $subDir;
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) return ['ok' => false, 'error' => 'Không tạo được thư mục lưu ảnh.'];

    $name = bin2hex(random_bytes(12)) . '.' . $allowed[$info[2]];   // tên ngẫu nhiên
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) return ['ok' => false, 'error' => 'Không lưu được ảnh vào thư mục uploads.'];
    return ['ok' => true, 'path' => 'uploads/' . $subDir . '/' . $name];
}
function delete_uploaded_image(?string $path): void
{
    if ($path && strpos($path, 'uploads/') === 0 && strpos($path, '..') === false) {
        $full = __DIR__ . '/../' . $path;
        if (is_file($full)) @unlink($full);
    }
}

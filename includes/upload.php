<?php
require_once __DIR__ . '/cloudinary.php';

// Trả về ['ok'=>bool, 'path'=>'https://res.cloudinary.com/...', 'error'=>'...'].
function save_uploaded_image(array $file, string $subDir): array
{
    if (!in_array($subDir, ['products', 'avatars'], true)) return ['ok' => false, 'error' => 'Loại ảnh không hợp lệ.'];
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) return ['ok' => false, 'error' => 'none'];
    if ($file['error'] !== UPLOAD_ERR_OK) return ['ok' => false, 'error' => 'Tải ảnh thất bại (mã lỗi ' . (int)$file['error'] . ').'];
    if (!isset($file['tmp_name']) || !is_string($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) return ['ok' => false, 'error' => 'Tệp tải lên không hợp lệ.'];
    $size = filesize($file['tmp_name']);
    if ($size === false || $size === 0) return ['ok' => false, 'error' => 'Tệp ảnh trống hoặc không đọc được.'];
    if ($size > 2 * 1024 * 1024) return ['ok' => false, 'error' => 'Ảnh vượt quá 2MB.'];

    // Kiểm tra loại file THỰC TẾ, không tin tên file hay MIME do trình duyệt gửi.
    $info = @getimagesize($file['tmp_name']);
    $allowed = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    if ($info === false || !isset($allowed[$info[2]])) return ['ok' => false, 'error' => 'Chỉ chấp nhận ảnh JPG, PNG hoặc WEBP.'];

    try {
        return ['ok' => true, 'path' => cloudinary_upload_image($file['tmp_name'], $subDir, $allowed[$info[2]])];
    } catch (RuntimeException $ex) {
        return ['ok' => false, 'error' => $ex->getMessage()];
    } catch (Throwable $ex) {
        error_log('FishShop image upload failed.');
        return ['ok' => false, 'error' => 'Không thể tải ảnh lên. Vui lòng thử lại sau.'];
    }
}
function delete_uploaded_image(?string $path): void
{
    if ($path && str_starts_with($path, 'https://res.cloudinary.com/')) {
        try { cloudinary_delete_image($path); }
        catch (Throwable $ex) { error_log('FishShop Cloudinary image cleanup failed.'); }
        return;
    }
    if ($path && strpos($path, 'uploads/') === 0 && strpos($path, '..') === false) {
        $full = __DIR__ . '/../' . $path;
        if (is_file($full)) @unlink($full);
    }
}

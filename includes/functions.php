<?php
// Hàm tiện ích dùng chung.
function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function money($v): string { return number_format((float)$v, 0, ',', '.') . ' ₫'; }
function url(string $path = ''): string { return BASE_URL . ltrim($path, '/'); }
function redirect(string $path): void { header('Location: ' . url($path)); exit; }

const ORDER_STATUS = ['pending'=>'Chờ xác nhận','confirmed'=>'Đã xác nhận','shipping'=>'Đang giao','completed'=>'Đã hoàn thành','cancelled'=>'Đã hủy'];
const PAYMENT_STATUS = ['unpaid'=>'Chưa thanh toán','pending_verification'=>'Chờ xác minh thanh toán','paid'=>'Đã thanh toán','rejected'=>'Thanh toán bị từ chối'];

// ----- CSRF -----
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
function csrf_ok(): bool {
    return isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$_POST['csrf']);
}

// ----- Thông báo flash -----
function flash(string $type, string $msg): void { $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg]; }
function take_flash(): array { $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f; }

// ----- Ảnh: nếu không có file thật thì dùng ảnh thay thế -----
function file_url(?string $rel, string $fallback = 'assets/images/no-image.svg'): string {
    $rel = (string)$rel;
    if ($rel !== '' && strpos($rel, '..') === false && is_file(__DIR__ . '/../' . $rel)) return url($rel);
    return url($fallback);
}

// ----- Phản hồi cho action: JSON (fetch) hoặc chuyển trang -----
function wants_json(): bool {
    return (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
        || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'fetch');
}
function respond(bool $ok, string $msg, string $backTo, array $extra = []): void {
    if (wants_json()) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array_merge(['ok' => $ok, 'message' => $msg], $extra), JSON_UNESCAPED_UNICODE);
        exit;
    }
    flash($ok ? 'success' : 'error', $msg);
    redirect($backTo);
}

// ----- Giỏ hàng -----
function get_cart_id(int $userId, bool $create = true): ?int {
    $st = db()->prepare('SELECT id FROM cart WHERE user_id = ?');
    $st->execute([$userId]);
    $id = $st->fetchColumn();
    if ($id) return (int)$id;
    if (!$create) return null;
    db()->prepare('INSERT INTO cart (user_id) VALUES (?)')->execute([$userId]);
    return (int)db()->lastInsertId();
}
function cart_count(int $userId): int {
    $st = db()->prepare('SELECT COALESCE(SUM(ci.quantity),0) FROM cart c JOIN cart_items ci ON ci.cart_id = c.id WHERE c.user_id = ?');
    $st->execute([$userId]);
    return (int)$st->fetchColumn();
}
// Lấy các dòng giỏ hàng cùng giá/tồn kho HIỆN TẠI từ database.
function cart_lines(int $userId): array {
    $st = db()->prepare(
        'SELECT ci.product_id, ci.quantity, p.name, p.price, p.image, p.stock, p.status
         FROM cart c JOIN cart_items ci ON ci.cart_id = c.id JOIN products p ON p.id = ci.product_id
         WHERE c.user_id = ? ORDER BY ci.id'
    );
    $st->execute([$userId]);
    return $st->fetchAll();
}

// Chỉ cho phép quay lại các trang nội bộ hợp lệ (chống chuyển hướng ra ngoài).
function safe_back(?string $b, string $default = 'cart.php'): string {
    $b = (string)$b;
    return preg_match('/^[a-z_]+\.php(\?[A-Za-z0-9_=&]*)?$/', $b) ? $b : $default;
}

// Đọc + kiểm tra dữ liệu biểu mẫu sản phẩm (dùng cho thêm/sửa). Trả về [dữ liệu, mảng lỗi].
function validate_product_post(): array {
    $d = [
        'name' => trim($_POST['name'] ?? ''),
        'price' => trim($_POST['price'] ?? ''),
        'category_id' => (int)($_POST['category_id'] ?? 0),
        'stock' => trim($_POST['stock'] ?? ''),
        'status' => $_POST['status'] ?? 'active',
        'description' => trim($_POST['description'] ?? ''),
    ];
    $err = [];
    if (!csrf_ok()) $err[] = 'Phiên làm việc không hợp lệ, vui lòng thử lại.';
    if ($d['name'] === '' || mb_strlen($d['name']) > 150) $err[] = 'Tên cá bắt buộc, tối đa 150 ký tự.';
    if (!ctype_digit($d['price']) || (int)$d['price'] < 1 || (int)$d['price'] > 999999999) $err[] = 'Giá phải là số nguyên dương (VNĐ).';
    if (!ctype_digit($d['stock']) || (int)$d['stock'] > 1000000) $err[] = 'Số lượng tồn kho phải là số nguyên từ 0 trở lên.';
    if (!in_array($d['status'], ['active', 'inactive'], true)) $err[] = 'Trạng thái không hợp lệ.';
    $st = db()->prepare('SELECT 1 FROM categories WHERE id = ?');
    $st->execute([$d['category_id']]);
    if (!$st->fetchColumn()) $err[] = 'Vui lòng chọn danh mục hợp lệ.';
    if (mb_strlen($d['description']) > 5000) $err[] = 'Mô tả tối đa 5000 ký tự.';
    return [$d, $err];
}

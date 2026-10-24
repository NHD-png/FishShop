<?php
// Phiên đăng nhập + kiểm tra quyền. Mọi trang/action đều require file này.
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'path' => '/']);
    session_start();
}

// Người dùng hiện tại, luôn đọc lại từ MySQL (không tin dữ liệu từ trình duyệt).
function current_user(): ?array {
    static $user = false;
    if ($user === false) {
        $user = null;
        if (!empty($_SESSION['user_id'])) {
            $st = db()->prepare('SELECT id, username, fullname, phone, address, avatar, role FROM users WHERE id = ?');
            $st->execute([(int)$_SESSION['user_id']]);
            $user = $st->fetch() ?: null;
            if ($user === null) unset($_SESSION['user_id']);
        }
    }
    return $user;
}
function isLoggedIn(): bool { return current_user() !== null; }
function isAdmin(): bool { $u = current_user(); return $u !== null && $u['role'] === 'admin'; }

function requireLogin(): array {
    $u = current_user();
    if ($u === null) {
        if (wants_json()) { http_response_code(401); header('Content-Type: application/json'); echo json_encode(['ok' => false, 'message' => 'Vui lòng đăng nhập.']); exit; }
        redirect('login.php');
    }
    return $u;
}
function requireAdmin(): array {
    $u = requireLogin();
    if ($u['role'] !== 'admin') { flash('error', 'Bạn không có quyền truy cập trang quản trị.'); redirect('index.php'); }
    return $u;
}
// Dùng cho action chỉ nhận POST + CSRF.
function requirePost(string $backTo): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect($backTo);
    if (!csrf_ok()) respond(false, 'Phiên làm việc không hợp lệ, vui lòng tải lại trang.', $backTo);
}

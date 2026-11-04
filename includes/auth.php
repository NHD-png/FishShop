<?php
// Phiên đăng nhập + kiểm tra quyền. Mọi trang/action đều require file này.
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    if (defined('FISHSHOP_ADMIN_PORTAL')) {
        session_name('FISHSHOP_ADMIN_SESSION');
    }
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'path' => '/',
    ]);
    session_start();
}

// Create an admin login in its own cookie/session without replacing the customer session.
function start_admin_session(int $userId): void {
    $restoreName = session_name();
    if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
    session_name('FISHSHOP_ADMIN_SESSION');
    session_start();
    session_regenerate_id(true);
    $_SESSION = ['user_id' => $userId];
    session_write_close();
    session_name($restoreName);
    session_start();
}

// Người dùng hiện tại, luôn đọc lại từ MySQL (không tin dữ liệu từ trình duyệt).
function current_user(): ?array {
    static $user = false;
    if ($user === false) {
        $user = null;
        if (!empty($_SESSION['user_id'])) {
            $st = db()->prepare(
                'SELECT id, username, fullname, phone, address, avatar, role, account_status FROM users WHERE id = ?'
            );
            $st->execute([(int)$_SESSION['user_id']]);
            $user = $st->fetch() ?: null;
            if ($user === null) {
                unset($_SESSION['user_id']);
            } elseif (($user['account_status'] ?? 'active') === 'locked') {
                unset($_SESSION['user_id']);
                $_SESSION['account_access_error'] = 'locked';
                $user = null;
            }
        }
    }
    return $user;
}
function isLoggedIn(): bool { return current_user() !== null; }
function isAdmin(): bool { $u = current_user(); return $u !== null && $u['role'] === 'admin'; }
function account_status_label(string $status): string {
    return $status === 'locked' ? 'Bị khóa' : 'Hoạt động';
}

function requireLogin(): array {
    $u = current_user();
    if ($u === null) {
        $locked = ($_SESSION['account_access_error'] ?? '') === 'locked';
        unset($_SESSION['account_access_error']);
        $message = $locked ? 'Tài khoản của bạn đang bị khóa. Vui lòng liên hệ quản trị viên.' : 'Vui lòng đăng nhập để tiếp tục.';
        if (!defined('FISHSHOP_ADMIN_PORTAL') && ($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
            $page = basename($_SERVER['SCRIPT_NAME'] ?? '');
            $target = $page;
            if (in_array($page, ['product_detail.php', 'purchase_history.php', 'payment.php'], true) && isset($_GET['id']) && is_scalar($_GET['id']) && ctype_digit((string)$_GET['id']) && (int)$_GET['id'] > 0) $target .= '?id=' . (int)$_GET['id'];
            if (safe_customer_return($target) !== null) $_SESSION['login_return_to'] = $target;
        }
        flash($locked ? 'error' : 'info', $message);
        if (wants_json()) { http_response_code(401); header('Content-Type: application/json'); echo json_encode(['ok' => false, 'message' => $message]); exit; }
        redirect(defined('FISHSHOP_ADMIN_PORTAL') ? 'admin/login.php' : 'login.php');
    }
    return $u;
}
function finish_customer_login(int $userId): string {
    merge_guest_cart(db(), $userId);
    session_regenerate_id(true);
    unset($_SESSION['csrf'], $_SESSION['account_access_error']);
    $_SESSION['user_id'] = $userId;
    return customer_login_destination();
}

function requireAdmin(): array {
    $u = requireLogin();
    if ($u['role'] !== 'admin') { flash('error', 'Bạn không có quyền truy cập trang quản trị.'); redirect('index.php'); }
    if (($u['account_status'] ?? 'active') !== 'active') {
        flash('error', 'Tài khoản quản trị của bạn đang bị khóa.');
        unset($_SESSION['user_id']);
        redirect(defined('FISHSHOP_ADMIN_PORTAL') ? 'admin/login.php' : 'login.php');
    }
    return $u;
}
// Dùng cho action chỉ nhận POST + CSRF.
function requirePost(string $backTo): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect($backTo);
    if (!csrf_ok()) respond(false, 'Phiên làm việc không hợp lệ, vui lòng tải lại trang.', $backTo);
}

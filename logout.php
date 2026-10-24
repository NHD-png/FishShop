<?php
require_once __DIR__ . '/includes/auth.php';
// Đăng xuất bằng POST + CSRF. Hủy hoàn toàn phiên cũ.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok()) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    session_start();
    flash('success', 'Bạn đã đăng xuất.');
    session_regenerate_id(true);
}
redirect('login.php');

<?php
require_once __DIR__ . '/bootstrap.php';
if (isLoggedIn() && isAdmin()) redirect('admin/index.php');
$error = '';
$username = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim(post_string('username'));
    $password = post_string('password');
    if (!csrf_ok()) $error = 'Phiên làm việc không hợp lệ, vui lòng thử lại.';
    elseif ($username === '' || $password === '') $error = 'Vui lòng nhập tên đăng nhập và mật khẩu.';
    else {
        $st = db()->prepare('SELECT id, password, account_status FROM users WHERE username = ? AND role = ?');
        $st->execute([$username, 'admin']);
        $admin = $st->fetch();
        if (!$admin || !password_verify($password, $admin['password'])) $error = 'Tên đăng nhập hoặc mật khẩu quản trị không đúng.';
        elseif (($admin['account_status'] ?? 'active') !== 'active') $error = 'Tài khoản quản trị đang bị khóa.';
        else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$admin['id'];
            redirect('admin/index.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Đăng nhập quản trị | Fish Shop</title>
<link rel="stylesheet" href="<?= url('assets/css/style.css') ?>?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
<link rel="stylesheet" href="<?= url('assets/css/reference-theme.css') ?>?v=<?= filemtime(__DIR__ . '/../assets/css/reference-theme.css') ?>">
</head><body class="admin-area" data-base="<?= e(BASE_URL) ?>" data-csrf="<?= e(csrf_token()) ?>">
<main class="wrap" style="flex:1;display:grid;place-items:center;padding-block:32px"><section class="auth card">
<h1>Đăng nhập quản trị</h1><p class="sub">Khu vực dành riêng cho quản trị viên Fish Shop.</p>
<?php if ($error): ?><div class="alert err" role="alert"><?= e($error) ?></div><?php endif; ?>
<form method="post" class="js-validate" novalidate><?= csrf_field() ?>
<div class="field"><label for="username">Tên đăng nhập quản trị</label><input id="username" name="username" type="text" value="<?= e($username) ?>" autocomplete="username" required autofocus></div>
<div class="field"><label for="password">Mật khẩu</label><div class="pw-wrap"><input id="password" name="password" type="password" autocomplete="current-password" required><button class="pw-toggle" type="button">Hiện</button></div></div>
<button class="btn block" type="submit">Đăng nhập quản trị</button></form>
<p class="switch"><a href="<?= url('index.php') ?>">Về cửa hàng khách hàng</a></p></section></main>
<script src="<?= url('assets/js/main.js') ?>"></script></body></html>
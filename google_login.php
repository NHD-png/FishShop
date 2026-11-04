<?php
require_once __DIR__ . '/includes/google_auth.php';
if (isLoggedIn()) redirect('index.php');
requirePost('login.php');
header('Cache-Control: no-store, private');
$c = google_config();
if (!function_exists('curl_init') || $c['client_id'] === '' || $c['client_secret'] === '') {
    flash('error', 'Đăng nhập Google chưa được cấu hình. Vui lòng đăng nhập bằng mật khẩu trong lúc chờ kích hoạt.');
    redirect('login.php');
}
session_regenerate_id(true);
$state = bin2hex(random_bytes(32));
$_SESSION['google_login'] = ['state' => $state, 'expires' => time() + 600];
header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
    'client_id' => $c['client_id'], 'redirect_uri' => $c['redirect_uri'], 'response_type' => 'code',
    'scope' => 'openid email', 'state' => $state, 'prompt' => 'select_account',
]));
exit;

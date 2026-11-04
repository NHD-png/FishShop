<?php
require_once __DIR__ . '/includes/google_auth.php';
header('Cache-Control: no-store, private');
header('Referrer-Policy: no-referrer');
if (isLoggedIn()) {
    if (isAdmin()) { header('Location: /Quantri/index.php'); exit; }
    redirect(customer_login_destination());
}
$pending = $_SESSION['google_login'] ?? null;
unset($_SESSION['google_login']);
try {
    if (!$pending || $pending['expires'] <= time() || !is_string($_GET['state'] ?? null)
        || !hash_equals($pending['state'], $_GET['state'])) throw new DomainException('Phiên đăng nhập Google đã hết hạn hoặc không hợp lệ. Vui lòng thử lại.');
    if (isset($_GET['error'])) throw new DomainException('Bạn đã hủy hoặc Google chưa cho phép đăng nhập.');
    if (!is_string($_GET['code'] ?? null) || $_GET['code'] === '') throw new RuntimeException('Missing code');
    $c = google_config();
    if ($c['client_id'] === '' || $c['client_secret'] === '') throw new RuntimeException('Missing configuration');
    $tokens = google_request('https://oauth2.googleapis.com/token', [
        'code' => $_GET['code'], 'client_id' => $c['client_id'], 'client_secret' => $c['client_secret'],
        'redirect_uri' => $c['redirect_uri'], 'grant_type' => 'authorization_code']);
    if (!is_string($tokens['access_token'] ?? null)) throw new RuntimeException('Missing access token');
    $info = google_request('https://openidconnect.googleapis.com/v1/userinfo', null, $tokens['access_token']);
    $id = google_account_id(db(), $info);
    $st = db()->prepare("SELECT id, role FROM users WHERE id = ? AND account_status = 'active'");
    $st->execute([$id]);
    $account = $st->fetch();
    if (!$account) throw new RuntimeException('Inactive account');
    if ($account['role'] === 'admin') {
        start_admin_session($id);
        header('Location: /Quantri/index.php');
        exit;
    }
    unset($_SESSION['csrf'], $_SESSION['password_recovery']);
    redirect(finish_customer_login($id));
} catch (DomainException $e) {
    flash('error', $e->getMessage());
} catch (Throwable $e) {
    error_log('FishShop: Google sign-in failed (' . get_class($e) . ').');
    flash('error', 'Không thể đăng nhập Google lúc này. Vui lòng thử lại hoặc dùng mật khẩu.');
}
redirect('login.php');

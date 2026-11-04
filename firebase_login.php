<?php
require_once __DIR__ . '/includes/firebase_auth.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
function firebase_reply(int $status, array $data): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') firebase_reply(405, ['ok' => false, 'message' => 'Yêu cầu không hợp lệ.']);
if (!csrf_ok()) firebase_reply(403, ['ok' => false, 'message' => 'Phiên làm việc hết hạn. Vui lòng tải lại trang.']);
if (isLoggedIn()) firebase_reply(200, ['ok' => true, 'redirect' => isAdmin() ? '/Quantri/index.php' : url(customer_login_destination())]);
if (time() - (int)($_SESSION['firebase_last_attempt'] ?? 0) < 2) firebase_reply(429, ['ok' => false, 'message' => 'Vui lòng chờ vài giây rồi thử lại.']);
$_SESSION['firebase_last_attempt'] = time();
try {
    $stage = 'firebase_token_verification';
    $info = verify_firebase_google_token(post_string('id_token'));
    // Google sign-in also creates a customer account on the first visit.
    $stage = 'fishshop_account_link';
    $id = google_account_id(db(), $info, true);
    $stage = 'account_status_check';
    $st = db()->prepare("SELECT id, role FROM users WHERE id = ? AND account_status = 'active'");
    $st->execute([$id]);
    $account = $st->fetch();
    if (!$account) throw new DomainException('Tài khoản đang bị khóa. Vui lòng liên hệ quản trị viên.');
    $stage = 'session_creation';
    if ($account['role'] === 'admin') {
        start_admin_session($id);
        firebase_reply(200, ['ok' => true, 'redirect' => '/Quantri/index.php']);
    }
    unset($_SESSION['csrf'], $_SESSION['password_recovery'], $_SESSION['google_login']);
    $stage = 'guest_cart_merge';
    $destination = finish_customer_login($id);
    firebase_reply(200, ['ok' => true, 'redirect' => url($destination)]);
} catch (DomainException $e) {
    firebase_reply(401, ['ok' => false, 'message' => $e->getMessage()]);
} catch (Throwable $e) {
    $reference = bin2hex(random_bytes(5));
    firebase_log_login_failure($stage ?? 'initialization', $e, $reference);
    firebase_reply(503, ['ok' => false, 'reference' => $reference,
        'message' => 'Máy chủ chưa hoàn tất đăng nhập Google. Vui lòng thử lại hoặc dùng mật khẩu. Mã hỗ trợ: ' . $reference . '.']);
}

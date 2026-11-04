<?php
// CLI only. Creates an isolated database, clones schema only, and removes it.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
ini_set('session.save_path', sys_get_temp_dir());
require_once __DIR__ . '/../includes/registration_otp.php';
$pdo = new PDO('mysql:host=127.0.0.1;port=3306;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false]);
$name = 'fishshop_otp_test_' . bin2hex(random_bytes(6));
$passed = 0;
function otp_check(bool $ok, string $message): void {
    global $passed;
    if (!$ok) throw new RuntimeException($message);
    $passed++;
}
function otp_reject(callable $fn, string $message): void {
    $denied = false;
    try { $fn(); } catch (DomainException $e) { $denied = true; }
    otp_check($denied, $message);
}
function otp_data(string $suffix): array {
    return ['username' => 'test_' . $suffix, 'email' => $suffix . '@example.com', 'fullname' => 'Test User',
        'phone' => '', 'address' => '', 'security_question' => 'first_school',
        'password_hash' => password_hash('TestPass!123', PASSWORD_DEFAULT), 'answer_hash' => password_hash('test school', PASSWORD_DEFAULT)];
}
$code = '';
$mailer = static function (string $email, string $value) use (&$code): void { $code = $value; };
$pdo->exec("CREATE DATABASE `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
try {
    $pdo->exec("USE `$name`");
    $pdo->exec('CREATE TABLE users LIKE WebBanCa.users');
    $pdo->exec('CREATE TABLE customer_ranks LIKE WebBanCa.customer_ranks');
    $pdo->exec(file_get_contents(__DIR__ . '/RegistrationOtp.sql'));
    $pdo->exec(file_get_contents(__DIR__ . '/RegistrationOtp.sql'));
    $pending = registration_issue($pdo, otp_data('first'), 'test-ip', $mailer);
    otp_check((int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0, 'No account before OTP');
    otp_check(preg_match('/^[0-9]{6}$/D', $code) === 1, 'Six digit OTP');
    $row = $pdo->query('SELECT * FROM registration_otps')->fetch();
    otp_check($row['code_hash'] !== $code && password_verify($code, $row['code_hash']), 'OTP stored hashed');
    otp_reject(fn() => registration_issue($pdo, otp_data('first'), 'new-ip', $mailer), 'Resend cooldown survives new IP/session');
    $wrong = $code === '999999' ? '888888' : '999999';
    otp_reject(fn() => registration_complete($pdo, $pending, $wrong), 'Wrong OTP rejected');
    $forged = $pending; $forged['token'] = bin2hex(random_bytes(32));
    otp_reject(fn() => registration_complete($pdo, $forged, $code), 'Other session rejected');
    $id = registration_complete($pdo, $pending, $code);
    $user = $pdo->query('SELECT * FROM users WHERE id = ' . $id)->fetch();
    otp_check($user['role'] === 'customer' && password_verify('TestPass!123', $user['password']), 'Verified account created');
    otp_check((int)$pdo->query('SELECT COUNT(*) FROM customer_ranks WHERE user_id = ' . $id)->fetchColumn() === 1, 'Rank profile created');
    otp_reject(fn() => registration_complete($pdo, $pending, $code), 'OTP replay rejected');
    $pending = registration_issue($pdo, otp_data('expiry'), 'ip-2', $mailer);
    $pdo->exec("UPDATE registration_otps SET expires_at = 0 WHERE email = 'expiry@example.com'");
    otp_reject(fn() => registration_complete($pdo, $pending, $code), 'Expired code rejected');
    $pending = registration_issue($pdo, otp_data('attempts'), 'ip-3', $mailer);
    $wrong = $code === '999999' ? '888888' : '999999';
    for ($i = 0; $i < 5; $i++) otp_reject(fn() => registration_complete($pdo, $pending, $wrong), 'Wrong attempt');
    otp_reject(fn() => registration_complete($pdo, $pending, $code), 'Five attempts exhausted');
    $pdo->exec('UPDATE registration_rate_limits SET last_at = 0');
    $old = $pending;
    $pending = registration_issue($pdo, otp_data('attempts'), 'ip-3', $mailer);
    otp_reject(fn() => registration_complete($pdo, $old, $code), 'Resend invalidates old challenge');
    otp_check(registration_complete($pdo, $pending, $code) > 0, 'New code succeeds after resend');
    otp_reject(fn() => registration_issue($pdo, otp_data('delivery'), 'ip-4', static function () { throw new RuntimeException('Test SMTP failure'); }), 'SMTP failure rejects signup');
    otp_check((int)$pdo->query("SELECT COUNT(*) FROM registration_otps WHERE email = 'delivery@example.com'")->fetchColumn() === 0, 'Failed delivery leaves no usable OTP');
    $pending = registration_issue($pdo, otp_data('collision'), 'ip-5', $mailer);
    $pdo->exec("UPDATE users SET username = 'test_collision' WHERE id = $id");
    otp_reject(fn() => registration_complete($pdo, $pending, $code), 'Duplicate username checked again at verification');
    for ($i = 0; $i < 5; $i++) {
        $pdo->exec('UPDATE registration_rate_limits SET last_at = 0');
        otp_check(registration_quota($pdo, 'quota-test', 5, 60), 'Within hourly limit');
    }
    otp_check(!registration_quota($pdo, 'quota-test', 5), 'Hourly limit enforced');
    echo "PASS: $passed registration OTP checks\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $pdo->exec("DROP DATABASE `$name`");
}

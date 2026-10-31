<?php
// Run against a disposable MariaDB server: php verify_login_methods.php 33318
if (PHP_SAPI !== 'cli' || !ctype_digit($argv[1] ?? '')) exit("Usage: php verify_login_methods.php <test-port>\n");
ini_set('session.save_path', sys_get_temp_dir());
require_once __DIR__ . '/../includes/login_methods.php';
require_once __DIR__ . '/../includes/password_email.php';
$pdo = new PDO('mysql:host=127.0.0.1;port=' . $argv[1], 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false]);
$name = 'fishshop_login_test_' . bin2hex(random_bytes(6));
$pdo->exec("CREATE DATABASE `$name`");
$passed = 0;
function check_login(bool $ok, string $message): void {
    global $passed;
    if (!$ok) throw new RuntimeException($message);
    $passed++;
}
function seed_code(PDO $pdo, int $expires = 300): void {
    $pdo->prepare('REPLACE INTO login_email_codes VALUES (2, ?, ?, ?, 0)')
        ->execute([password_hash('012345', PASSWORD_DEFAULT), hash('sha256', 'browser-a'), time() + $expires]);
}
try {
    $pdo->exec("USE `$name`");
    $pdo->exec(file_get_contents(__DIR__ . '/WebBanCaUpgrade.sql'));
    $pdo->exec(file_get_contents(__DIR__ . '/LoginMethods.sql'));
    $pdo->exec(file_get_contents(__DIR__ . '/LoginMethods.sql'));
    $pdo->exec("UPDATE users SET account_status = 'active' WHERE id = 2");
    check_login(login_rate_limit($pdo, 'send', 2, 3600), 'First request');
    check_login(login_rate_limit($pdo, 'send', 2, 3600), 'Second request');
    check_login(!login_rate_limit($pdo, 'send', 2, 3600), 'Rate limit');
    check_login(login_rate_limit($pdo, 'spaced', 5, 3600, 60), 'Initial send');
    check_login(!login_rate_limit($pdo, 'spaced', 5, 3600, 60), 'Resend cooldown');
    $pdo->exec('UPDATE login_rate_limits SET started_at = 0, last_at = 0');
    check_login(login_rate_limit($pdo, 'send', 2, 3600), 'Window reset');
    seed_code($pdo);
    check_login(!verify_login_code($pdo, 2, 'browser-b', '012345'), 'Browser binding');
    check_login(verify_login_code($pdo, 2, 'browser-a', '012345'), 'Leading zero code');
    check_login(!verify_login_code($pdo, 2, 'browser-a', '012345'), 'Replay denied');
    seed_code($pdo, -1);
    check_login(!verify_login_code($pdo, 2, 'browser-a', '012345'), 'Expiry');
    seed_code($pdo);
    for ($i = 0; $i < 5; $i++) check_login(!verify_login_code($pdo, 2, 'browser-a', '999999'), 'Wrong code');
    check_login(!verify_login_code($pdo, 2, 'browser-a', '012345'), 'Five attempts exhausted');
    seed_code($pdo);
    $pdo->exec("UPDATE users SET account_status = 'locked' WHERE id = 2");
    check_login(!verify_login_code($pdo, 2, 'browser-a', '012345'), 'Locked account');
    $pdo->exec("UPDATE users SET account_status = 'active', email = 'fishshop-test@gmail.com' WHERE id = 2");
    $info = ['sub' => '123456789', 'email' => 'fishshop-test@gmail.com', 'email_verified' => true];
    check_login(google_existing_user($pdo, $info) === 2, 'Google account match');
    $info['email'] = 'changed@gmail.com';
    check_login(google_existing_user($pdo, $info) === 2, 'Stable Google subject');
    foreach ([['sub' => '88', 'email' => 'a@example.com', 'email_verified' => true],
        ['sub' => '99', 'email' => 'fishshop-test@gmail.com', 'email_verified' => false],
        ['sub' => '77', 'email' => 'unknown@gmail.com', 'email_verified' => true]] as $bad) {
        $rejected = false;
        try { google_existing_user($pdo, $bad); } catch (RuntimeException $e) { $rejected = true; } catch (DomainException $e) { $rejected = true; }
        check_login($rejected, 'Invalid Google identity rejected');
    }
    $newInfo = ['sub' => '987654321', 'email' => 'new-user@gmail.com', 'email_verified' => true];
    $newId = google_existing_user($pdo, $newInfo, true);
    $newUser = $pdo->query('SELECT * FROM users WHERE id = ' . $newId)->fetch();
    check_login($newUser['role'] === 'customer' && $newUser['email'] === $newInfo['email'], 'Google signup');
    check_login((int)$pdo->query('SELECT COUNT(*) FROM customer_ranks WHERE user_id = ' . $newId)->fetchColumn() === 1, 'Signup rank profile');
    check_login(google_existing_user($pdo, $newInfo, true) === $newId, 'Repeated signup is idempotent');
    $seedReset = function () use ($pdo): array {
        $pdo->prepare('REPLACE INTO password_email_codes VALUES (2, ?, ?, ?, 0)')
            ->execute([password_hash('654321', PASSWORD_DEFAULT), hash('sha256', 'reset-browser'), time() + 300]);
        return ['step' => 'code', 'user_id' => 2, 'binding' => 'reset-browser', 'expires_at' => time() + 300];
    };
    $pending = $seedReset();
    check_login(!verify_login_code($pdo, 2, 'reset-browser', '654321'), 'Reset code cannot log in');
    $grant = grant_email_password_reset($pdo, $pending, '654321');
    check_login($grant['step'] === 'reset', 'OTP grants reset only');
    $denied = false;
    try { grant_email_password_reset($pdo, $pending, '654321'); } catch (DomainException $e) { $denied = true; }
    check_login($denied, 'Reset OTP cannot replay');
    apply_email_password_reset($pdo, $grant, 'NewTestPass!123');
    check_login(password_verify('NewTestPass!123', $pdo->query('SELECT password FROM users WHERE id = 2')->fetchColumn()), 'Password reset saved');
    $denied = false;
    try { apply_email_password_reset($pdo, $grant, 'OtherPass!123'); } catch (DomainException $e) { $denied = true; }
    check_login($denied, 'Reset grant cannot replay');
    $pending = $seedReset();
    $grant = grant_email_password_reset($pdo, $pending, '654321');
    $pdo->exec("UPDATE users SET account_status = 'locked' WHERE id = 2");
    $denied = false;
    try { apply_email_password_reset($pdo, $grant, 'OtherPass!123'); } catch (DomainException $e) { $denied = true; }
    check_login($denied, 'Lock after OTP blocks password reset');
    echo "PASS: migration imported twice; $passed authentication checks\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $pdo->exec("DROP DATABASE `$name`");
}

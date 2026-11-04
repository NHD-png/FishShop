<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
ini_set('session.save_path', sys_get_temp_dir());
require_once __DIR__ . '/../includes/firebase_auth.php';
$passed = 0;
function fb_check(bool $ok, string $name): void {
    global $passed;
    if (!$ok) throw new RuntimeException($name);
    $passed++;
}
function fb_reject(callable $fn, string $name): void {
    $denied = false;
    try { $fn(); } catch (DomainException $e) { $denied = true; }
    fb_check($denied, $name);
}
function fb_token(array $claims, string $alg = 'RS256'): string {
    $b64 = static fn($v) => rtrim(strtr(base64_encode(json_encode($v)), '+/', '-_'), '=');
    return $b64(['alg' => $alg, 'kid' => 'test']) . '.' . $b64($claims) . '.fakeSignature';
}
$claims = ['aud' => 'fishshop-de2f9', 'iss' => 'https://securetoken.google.com/fishshop-de2f9',
    'sub' => 'firebase-uid', 'iat' => time(), 'exp' => time() + 3600, 'auth_time' => time(),
    'email' => 'fishshop.test@gmail.com', 'email_verified' => true,
    'firebase' => ['sign_in_provider' => 'google.com', 'identities' => ['google.com' => ['123456789']]]];
$lookup = ['users' => [['localId' => 'firebase-uid', 'email' => $claims['email'], 'emailVerified' => true,
    'providerUserInfo' => [['providerId' => 'google.com', 'rawId' => '123456789']]]]];
fb_check(firebase_token_claims(fb_token($claims), 'fishshop-de2f9')['sub'] === 'firebase-uid', 'Preliminary claims');
fb_check(firebase_google_identity($claims, $lookup)['sub'] === '123456789', 'Stable Google identity');
foreach (['aud' => 'other-project', 'iss' => 'https://evil.test', 'exp' => time() - 1, 'auth_time' => time() - 700, 'sub' => ''] as $field => $value) {
    $bad = array_replace($claims, [$field => $value]);
    fb_reject(fn() => firebase_token_claims(fb_token($bad), 'fishshop-de2f9'), 'Reject ' . $field);
}
fb_reject(fn() => firebase_token_claims(fb_token($claims, 'none'), 'fishshop-de2f9'), 'Reject unsigned token');
$bad = $claims; $bad['firebase']['sign_in_provider'] = 'password';
fb_reject(fn() => firebase_token_claims(fb_token($bad), 'fishshop-de2f9'), 'Reject non-Google login');
foreach (['localId' => 'other-user', 'disabled' => true, 'emailVerified' => false, 'email' => 'other@gmail.com', 'providerUserInfo' => []] as $field => $value) {
    $badLookup = $lookup; $badLookup['users'][0][$field] = $value;
    fb_reject(fn() => firebase_google_identity($claims, $badLookup), 'Reject account ' . $field);
}
// Optional real Google rejection test: fake signatures must never authenticate.
if (in_array('--remote', $argv, true)) {
    fb_reject(fn() => verify_firebase_google_token(fb_token($claims)), 'Firebase rejects forged signature');
}
// Optional disposable MariaDB instance; never uses the production database.
if (isset($argv[1]) && ctype_digit($argv[1])) {
    $pdo = new PDO('mysql:host=127.0.0.1;port=' . $argv[1], 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $name = 'fishshop_firebase_test_' . bin2hex(random_bytes(5));
    $pdo->exec("CREATE DATABASE `$name`");
    try {
        $pdo->exec("USE `$name`");
        $pdo->exec("CREATE TABLE users (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, username VARCHAR(50) UNIQUE, password VARCHAR(255), fullname VARCHAR(100), email VARCHAR(254) UNIQUE, phone VARCHAR(20), address VARCHAR(255), role VARCHAR(20), account_status VARCHAR(20) DEFAULT 'active') ENGINE=InnoDB");
        $pdo->exec("CREATE TABLE customer_ranks (user_id INT UNSIGNED PRIMARY KEY, total_exp BIGINT, total_eligible_spend BIGINT, spend_remainder INT, current_rank VARCHAR(24)) ENGINE=InnoDB");
        $pdo->exec(file_get_contents(__DIR__ . '/FirebaseLogin.sql'));
        $pdo->exec(file_get_contents(__DIR__ . '/FirebaseLogin.sql'));
        $info = firebase_google_identity($claims, $lookup);
        fb_reject(fn() => google_account_id($pdo, $info), 'Login cannot silently register');
        $id = google_account_id($pdo, $info, true);
        fb_check(google_account_id($pdo, $info, true) === $id, 'Repeated signup');
        fb_check($pdo->query('SELECT role FROM users WHERE id = ' . $id)->fetchColumn() === 'customer', 'Customer role');
        fb_check((int)$pdo->query('SELECT COUNT(*) FROM customer_ranks')->fetchColumn() === 1, 'Rank created');
        $info['email'] = 'changed@gmail.com';
        fb_check(google_account_id($pdo, $info) === $id, 'Stable provider ID');
        $pdo->exec("UPDATE users SET account_status = 'locked' WHERE id = $id");
        fb_reject(fn() => google_account_id($pdo, $info, true), 'Locked account rejected');
        $external = ['sub' => '234567890', 'email' => 'student@example.edu', 'email_verified' => true, 'name' => 'Student'];
        $externalId = google_account_id($pdo, $external, true);
        fb_check($externalId !== $id, 'Non-Gmail Google account can register');
        fb_check(google_account_id($pdo, $external, true) === $externalId, 'Non-Gmail Google account can log in again');
        $otherIdentity = array_replace($external, ['sub' => '345678901']);
        fb_reject(fn() => google_account_id($pdo, $otherIdentity, true), 'Do not link existing external mailbox to different Google account');
        $unverified = array_replace($external, ['sub' => '456789012', 'email_verified' => false]);
        $denied = false;
        try { google_account_id($pdo, $unverified, true); } catch (RuntimeException $e) { $denied = true; }
        fb_check($denied, 'Unverified Google email still rejected');
        $pdo->exec("UPDATE users SET account_status = 'locked' WHERE id = $externalId");
        fb_reject(fn() => google_account_id($pdo, $external, true), 'Locked non-Gmail account rejected');
    } finally { if ($pdo->inTransaction()) $pdo->rollBack(); $pdo->exec("DROP DATABASE `$name`"); }
}
echo "PASS: $passed Firebase authentication checks\n";

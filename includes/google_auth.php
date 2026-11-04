<?php
require_once __DIR__ . '/auth.php';

function google_config(): array {
    return require __DIR__ . '/../config/google.php';
}
function google_request(string $url, ?array $form = null, ?string $token = null): array {
    $ch = curl_init($url);
    try {
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 20, CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2]);
        if ($form !== null) curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($form)]);
        if ($token !== null) curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
        $raw = curl_exec($ch);
        if ($raw === false || curl_getinfo($ch, CURLINFO_RESPONSE_CODE) !== 200) throw new RuntimeException('Google request failed');
        $data = json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($data)) throw new RuntimeException('Invalid response');
        return $data;
    } finally { curl_close($ch); }
}
function google_account_id(PDO $pdo, array $info, bool $register = false): int {
    $sub = $info['sub'] ?? null;
    $email = $info['email'] ?? null;
    if (!is_string($sub) || !preg_match('/^[0-9]{1,255}$/D', $sub)
        || !is_string($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)
        || ($info['email_verified'] ?? false) !== true) throw new RuntimeException('Unverified identity');
    $st = $pdo->prepare('SELECT u.id, u.account_status FROM login_google_accounts g JOIN users u ON u.id = g.user_id WHERE g.google_sub = ?');
    $st->execute([$sub]);
    if ($user = $st->fetch()) {
        if ($user['account_status'] !== 'active') throw new DomainException('Tài khoản đang bị khóa. Vui lòng liên hệ quản trị viên.');
        return (int)$user['id'];
    }
    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare('SELECT id, account_status FROM users WHERE email = ? FOR UPDATE');
        $st->execute([strtolower($email)]);
        $user = $st->fetch();
        if ($user && $user['account_status'] !== 'active') throw new DomainException('Tài khoản đang bị khóa. Vui lòng liên hệ quản trị viên.');
        // A verified Google account may use a non-Google mailbox. It can create
        // a new account, but must not take over an existing local account merely
        // because Google verified that external email at some point in the past.
        $googleMailbox = preg_match('/@(gmail\.com|googlemail\.com)$/iD', $email) === 1;
        if ($user && !$googleMailbox) throw new DomainException('Email này đã có tài khoản Fish Shop nhưng chưa liên kết Google. Vui lòng đăng nhập bằng mật khẩu của tài khoản đó.');
        if (!$user && !$register) throw new DomainException('Email này chưa có tài khoản Fish Shop. Vui lòng chọn Đăng ký bằng Google ở trang đăng ký.');
        if ($user) $id = (int)$user['id'];
        else {
            $fullname = mb_substr(trim($info['name'] ?? '') ?: explode('@', $email)[0], 0, 100);
            $pdo->prepare("INSERT INTO users (username, password, fullname, email, phone, address, role) VALUES (?, ?, ?, ?, '', '', 'customer')")
                ->execute(['google_' . bin2hex(random_bytes(10)), password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT), $fullname, strtolower($email)]);
            $id = (int)$pdo->lastInsertId();
            ensure_rank_profile($pdo, $id);
        }
        $pdo->prepare('INSERT INTO login_google_accounts (google_sub, user_id) VALUES (?, ?)')->execute([$sub, $id]);
        $pdo->commit();
        return $id;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

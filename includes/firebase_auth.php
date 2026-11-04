<?php
require_once __DIR__ . '/google_auth.php';
require_once __DIR__ . '/firebase_diagnostics.php';

// These are preliminary checks only. Trust claims ONLY after accounts:lookup
// accepts the exact same token over verified HTTPS and returns the same UID.
function firebase_token_claims(string $token, string $project): array {
    if (strlen($token) > 16384 || !preg_match('/^[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+$/D', $token)) {
        throw new DomainException('Token Firebase không hợp lệ. Vui lòng đăng nhập lại.');
    }
    $parts = explode('.', $token);
    $decode = static function (string $part): array {
        $raw = base64_decode(strtr($part, '-_', '+/') . str_repeat('=', (4 - strlen($part) % 4) % 4), true);
        $value = $raw === false ? null : json_decode($raw, true);
        if (!is_array($value)) throw new DomainException('Token Firebase không hợp lệ.');
        return $value;
    };
    $header = $decode($parts[0]);
    $claims = $decode($parts[1]);
    $now = time();
    if (($header['alg'] ?? '') !== 'RS256' || !is_string($header['kid'] ?? null)
        || ($claims['aud'] ?? '') !== $project || ($claims['iss'] ?? '') !== 'https://securetoken.google.com/' . $project
        || !is_string($claims['sub'] ?? null) || $claims['sub'] === '' || strlen($claims['sub']) > 128
        || !is_int($claims['exp'] ?? null) || $claims['exp'] <= $now
        || !is_int($claims['iat'] ?? null) || $claims['iat'] > $now + 30
        || !is_int($claims['auth_time'] ?? null) || $claims['auth_time'] > $now + 30
        || $claims['auth_time'] < $now - 600
        || ($claims['firebase']['sign_in_provider'] ?? '') !== 'google.com'
        || isset($claims['firebase']['tenant'])) {
        throw new DomainException('Phiên Google không hợp lệ hoặc đã hết hạn. Vui lòng chọn tài khoản lại.');
    }
    return $claims;
}

function firebase_google_identity(array $claims, array $lookup): array {
    $user = $lookup['users'][0] ?? [];
    if (($user['localId'] ?? null) !== $claims['sub'] || !empty($user['disabled'])
        || ($user['emailVerified'] ?? false) !== true || ($claims['email_verified'] ?? false) !== true
        || !is_string($user['email'] ?? null) || ($claims['email'] ?? '') !== $user['email']) {
        throw new DomainException('Tài khoản Google chưa xác minh hoặc đã bị vô hiệu hóa.');
    }
    foreach ($user['providerUserInfo'] ?? [] as $provider) {
        if (($provider['providerId'] ?? '') === 'google.com'
            && is_string($provider['rawId'] ?? null)
            && in_array($provider['rawId'], $claims['firebase']['identities']['google.com'] ?? [], true)) {
            return ['sub' => $provider['rawId'], 'email' => $user['email'], 'email_verified' => true,
                'name' => is_string($user['displayName'] ?? null) ? $user['displayName'] : ''];
        }
    }
    throw new DomainException('Không xác minh được tài khoản Google.');
}

function verify_firebase_google_token(string $token): array {
    $config = require __DIR__ . '/../config/firebase.php';
    $issues = firebase_environment_issues($config);
    if ($issues) throw new FirebaseBackendException('server_configuration_missing', ['missing' => $issues]);
    $claims = firebase_token_claims($token, $config['projectId']);
    $lookup = firebase_lookup_account($token, $config);
    return firebase_google_identity($claims, $lookup);
}

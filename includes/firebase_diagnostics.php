<?php
require_once __DIR__ . '/auth_log.php';
// No tokens, API keys, account details, request URLs or raw API error bodies in logs.
class FirebaseBackendException extends RuntimeException
{
    public function __construct(public readonly string $reason, public readonly array $diagnostics = [])
    {
        parent::__construct('Firebase backend unavailable');
    }
}

function firebase_environment_issues(?array $config = null): array
{
    $config = $config ?? require __DIR__ . '/../config/firebase.php';
    $issues = [];
    foreach (['apiKey' => 'FISHSHOP_FIREBASE_API_KEY', 'authDomain' => 'FISHSHOP_FIREBASE_AUTH_DOMAIN', 'projectId' => 'FISHSHOP_FIREBASE_PROJECT_ID'] as $key => $name) {
        if (!is_string($config[$key] ?? null) || trim($config[$key]) === '') $issues[] = $name . '_MISSING';
    }
    if (!function_exists('curl_init')) $issues[] = 'PHP_CURL_EXTENSION_MISSING';
    elseif (!in_array('https', curl_version()['protocols'], true)) $issues[] = 'CURL_HTTPS_PROTOCOL_MISSING';
    $ca = ini_get('curl.cainfo');
    if ($ca !== '' && !is_file($ca)) $issues[] = 'CURL_CA_FILE_MISSING';
    return $issues;
}

function firebase_transport_reason(int $code): string
{
    return match ($code) {5 => 'proxy_dns_failed', 6 => 'google_dns_failed', 7 => 'google_connection_failed', 28 => 'google_connection_timeout', 35 => 'google_tls_handshake_failed', 55 => 'google_send_failed', 56 => 'google_connection_reset_or_receive_failed', 60 => 'google_certificate_untrusted', 77 => 'curl_ca_file_invalid', default => 'google_transport_failed'};
}

function firebase_api_error_code(array $response): string
{
    foreach ($response['error']['details'] ?? [] as $detail) {
        if (is_array($detail) && is_string($detail['reason'] ?? null) && preg_match('/^[A-Z][A-Z0-9_]{2,79}$/D', $detail['reason'])) return $detail['reason'];
    }
    $message = $response['error']['message'] ?? '';
    if (is_string($message) && preg_match('/^([A-Z][A-Z0-9_]{2,79})(?:\s*:.*)?$/D', $message, $match)) return $match[1];
    return 'FIREBASE_HTTP_ERROR';
}

function firebase_lookup_account(string $token, array $config): array
{
    $issues = firebase_environment_issues($config);
    if ($issues) throw new FirebaseBackendException('server_configuration_missing', ['missing' => $issues]);
    $ch = curl_init('https://identitytoolkit.googleapis.com/v1/accounts:lookup?key=' . rawurlencode($config['apiKey']));
    if ($ch === false) throw new FirebaseBackendException('curl_initialization_failed');
    try {
        curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode(['idToken' => $token]),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_TIMEOUT => 20, CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2]);
        $raw = curl_exec($ch);
        if ($raw === false) {
            $code = curl_errno($ch);
            throw new FirebaseBackendException(firebase_transport_reason($code), ['curl_code' => $code]);
        }
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $lookup = json_decode($raw, true, 32);
        if (!is_array($lookup)) throw new FirebaseBackendException('firebase_invalid_response', ['http_status' => $status]);
        if ($status !== 200) {
            $apiError = firebase_api_error_code($lookup);
            if (in_array($status, [400, 401], true) && in_array($apiError, ['INVALID_ID_TOKEN', 'TOKEN_EXPIRED', 'INVALID_AUTH', 'CREDENTIAL_MISMATCH', 'USER_NOT_FOUND', 'USER_DISABLED'], true)) {
                throw new DomainException('Firebase từ chối phiên đăng nhập. Vui lòng chọn tài khoản lại.');
            }
            throw new FirebaseBackendException('firebase_api_rejected', ['http_status' => $status, 'firebase_error' => $apiError]);
        }
        return $lookup;
    } finally { curl_close($ch); }
}

function firebase_log_login_failure(string $stage, Throwable $error, string $reference): void
{
    $driver = $error instanceof PDOException ? (int)($error->errorInfo[1] ?? 0) : 0;
    $reason = $error instanceof FirebaseBackendException ? $error->reason : match ($driver) {
        1146 => 'database_table_missing', 1054 => 'database_column_missing', 1364 => 'database_required_column_has_no_default',
        1045 => 'database_access_denied', 1049 => 'database_not_found', 1205 => 'database_lock_timeout', 1213 => 'database_deadlock', default => 'backend_operation_failed'
    };
    $context = $error instanceof FirebaseBackendException ? array_intersect_key($error->diagnostics, array_flip(['missing', 'curl_code', 'http_status', 'firebase_error'])) : [];
    fishshop_auth_log('[FishShop Firebase] login_failed ' . json_encode($context + [
        'reference' => $reference, 'stage' => $stage, 'reason' => $reason, 'exception' => get_class($error),
        'db_code' => $driver,
    ] + fishshop_auth_runtime(), JSON_UNESCAPED_SLASHES));
}

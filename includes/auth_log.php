<?php
function fishshop_auth_log_path(): string
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'auth.log.php';
}

function fishshop_auth_runtime(): array
{
    $envPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
    $ca = (string)ini_get('curl.cainfo');
    return [
        'php_sapi' => PHP_SAPI,
        'php_ini' => php_ini_loaded_file() ?: 'none',
        'additional_ini' => php_ini_scanned_files() ?: 'none',
        'extension_dir' => (string)ini_get('extension_dir'),
        'curl_extension_loaded' => extension_loaded('curl'),
        'curl_function_available' => function_exists('curl_init'),
        'curl_cainfo' => $ca !== '' ? $ca : 'system_default',
        'curl_ca_file_exists' => $ca !== '' ? is_file($ca) : null,
        'env_file_exists' => is_file($envPath),
        'env_file_readable' => is_readable($envPath),
    ];
}

function fishshop_write_auth_log(string $path, string $message): bool
{
    $directory = dirname($path);
    if (!is_dir($directory) && !@mkdir($directory, 0770, true) && !is_dir($directory)) return false;
    $stream = @fopen($path, 'c+');
    if ($stream === false) return false;
    $locked = false;
    try {
        if (!@flock($stream, LOCK_EX)) return false;
        $locked = true;
        $size = fstat($stream)['size'] ?? 0;
        // This guard protects logs even if the web server ignores .htaccess.
        $guard = '<?php http_response_code(404); exit; ?>' . "\n";
        if ($size === 0 && @fwrite($stream, $guard) !== strlen($guard)) return false;
        if (@fseek($stream, 0, SEEK_END) !== 0) return false;
        $timestamp = (new DateTimeImmutable('now', new DateTimeZone('Asia/Ho_Chi_Minh')))->format('Y-m-d H:i:sP');
        $line = '[' . $timestamp . '] ' . substr(str_replace(["\r", "\n"], ' ', $message), 0, 8192) . "\n";
        return @fwrite($stream, $line) === strlen($line) && @fflush($stream);
    } finally {
        if ($locked) @flock($stream, LOCK_UN);
        fclose($stream);
    }
}

function fishshop_auth_log(string $message): bool
{
    $written = fishshop_write_auth_log(fishshop_auth_log_path(), $message);
    // Preserve normal PHP/Apache logging too, without allowing its path failure to corrupt JSON.
    @error_log($message);
    return $written;
}

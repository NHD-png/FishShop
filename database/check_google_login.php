<?php
// No account/database changes. Writes a diagnostic log; never signs in or prints credentials.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/firebase_diagnostics.php';
$config = require __DIR__ . '/../config/firebase.php';
$failures = 0;
function google_setup_result(bool $ok, string $label): void {
    global $failures;
    echo ($ok ? '[OK] ' : '[LOI] ') . $label . PHP_EOL;
    if (!$ok) $failures++;
}
echo 'PHP ini: ' . (php_ini_loaded_file() ?: 'none') . PHP_EOL;
echo 'Auth log: ' . fishshop_auth_log_path() . PHP_EOL;
google_setup_result(fishshop_auth_log('[FishShop Firebase] setup_check_started ' . json_encode(['php_sapi' => PHP_SAPI, 'php_ini' => php_ini_loaded_file() ?: 'none'])), 'Thu muc log FishShop ghi duoc');
google_setup_result(is_file(__DIR__ . '/../.env'), '.env nam cung thu muc voi login.php');
$issues = firebase_environment_issues($config);
foreach ($issues as $issue) google_setup_result(false, $issue);
if (!$issues) {
    google_setup_result(true, 'Firebase config, curl/HTTPS va duong dan CA');
    try {
        firebase_lookup_account('FISHSHOP_DIAGNOSTIC_INVALID_TOKEN', $config);
        google_setup_result(false, 'Firebase unexpectedly accepted invalid diagnostic token');
    } catch (DomainException $expected) {
        google_setup_result(true, 'PHP ket noi HTTPS den Firebase; token gia bi tu choi dung');
    } catch (FirebaseBackendException $error) {
        $details = $error->reason;
        foreach (['curl_code', 'http_status', 'firebase_error'] as $key) if (isset($error->diagnostics[$key])) $details .= '; ' . $key . '=' . $error->diagnostics[$key];
        google_setup_result(false, $details);
    }
}
try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    google_setup_result(true, 'Ket noi MySQL');
    $tables = [
        'users' => ['id','username','password','fullname','email','phone','address','avatar','role','account_status'],
        'login_google_accounts' => ['google_sub','user_id'],
        'customer_ranks' => ['user_id','total_exp','total_eligible_spend','spend_remainder','current_rank'],
        'cart' => ['id','user_id','updated_at'], 'cart_items' => ['cart_id','product_id','quantity'],
        'products' => ['id','category_id','stock','status'], 'categories' => ['id','status'],
    ];
    $st = $pdo->prepare('SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');
    foreach ($tables as $table => $columns) {
        $st->execute([$table]);
        $missing = array_values(array_diff($columns, $st->fetchAll(PDO::FETCH_COLUMN)));
        google_setup_result(!$missing, $table . ($missing ? ': thieu ' . implode(', ', $missing) : ': day du cot can thiet'));
    }
} catch (PDOException $error) {
    google_setup_result(false, 'Database, driver_code=' . (int)($error->errorInfo[1] ?? 0));
}
echo PHP_EOL . ($failures ? 'Gui cac dong [LOI] de xac dinh loi. Khong gui .env hoac token.' : 'Kiem tra Google backend dat. Dang nhap tai khoan that van can thu trong trinh duyet.') . PHP_EOL;
exit($failures ? 1 : 0);

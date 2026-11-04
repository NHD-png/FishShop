<?php
// Local diagnostics only: never prints credentials, tokens, email addresses or user data.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_log.php';
$mail = require __DIR__ . '/../config/mail.php';
$failures = 0;
function setup_check(bool $ok, string $label, string $fix = ''): void {
    global $failures;
    echo ($ok ? '[OK] ' : '[THIEU] ') . $label . PHP_EOL;
    if (!$ok) { $failures++; if ($fix !== '') echo '  ' . $fix . PHP_EOL; }
}
setup_check(is_file(__DIR__ . '/../.env'), 'File .env o thu muc goc FishShop', 'Chep .env cua may da cau hinh; file nay khong di theo GitHub.');
setup_check(function_exists('curl_init'), 'PHP extension curl', 'Bat extension=curl trong php.ini cua Apache, sau do khoi dong lai Apache.');
$protocols = function_exists('curl_version') ? curl_version()['protocols'] : [];
setup_check(in_array('smtps', $protocols, true), 'cURL ho tro SMTPS');
setup_check(filter_var($mail['gmail_address'], FILTER_VALIDATE_EMAIL) !== false, 'FISHSHOP_GMAIL_ADDRESS hop le', 'Dien Gmail gui thu cua cua hang vao .env.');
setup_check(trim($mail['gmail_app_password']) !== '', 'FISHSHOP_GMAIL_APP_PASSWORD da dien', 'Dien App Password cua Gmail gui thu; khong dung mat khau Gmail thuong.');
$ca = ini_get('curl.cainfo');
echo 'PHP ini: ' . (php_ini_loaded_file() ?: 'none') . PHP_EOL;
echo 'Auth log: ' . fishshop_auth_log_path() . PHP_EOL;
setup_check(fishshop_auth_log('[FishShop OTP] setup_check_started ' . json_encode(['php_sapi' => PHP_SAPI, 'php_ini' => php_ini_loaded_file() ?: 'none'])), 'Thu muc log FishShop ghi duoc', 'Kiem tra quyen ghi storage/logs trong thu muc FishShop.');
if ($ca !== '') setup_check(is_file($ca), 'File chung chi CA trong curl.cainfo ton tai', 'Sua duong dan curl.cainfo cho dung tren may nay; khong tat xac minh TLS.');
try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    setup_check(true, 'Ket noi database');
    $required = [
        'users' => ['id','username','password','fullname','email','phone','address','role','account_status'],
        'registration_otps' => ['email','challenge_hash','code_hash','expires_at','attempts'],
        'registration_rate_limits' => ['bucket','started_at','last_at','hits'],
        'customer_ranks' => ['user_id','total_exp','total_eligible_spend','spend_remainder','current_rank'],
        'login_google_accounts' => ['user_id'],
    ];
    $st = $pdo->prepare('SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
    foreach ($required as $table => $columns) {
        $st->execute([$table]);
        $actual = $st->fetchAll(PDO::FETCH_COLUMN);
        $missing = array_values(array_diff($columns, $actual));
        setup_check(!$missing, 'Bang ' . $table . ' va cac cot can thiet', $missing ? 'Thieu: ' . implode(', ', $missing) . '. Kiem tra lai file SQL da import.' : '');
    }
} catch (PDOException $ex) {
    setup_check(false, 'Ket noi/kiem tra database', 'Bat MySQL va kiem tra config/database.php; ma loi ' . (int)($ex->errorInfo[1] ?? 0) . '.');
}
echo PHP_EOL . ($failures ? 'Co ' . $failures . ' muc can sua. Chi gui cac dong THIEU; khong gui noi dung .env.' : 'Cau hinh va bang dang ky day du. Chua gui email that trong kiem tra nay.') . PHP_EOL;
exit($failures ? 1 : 0);

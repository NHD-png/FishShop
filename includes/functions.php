<?php
// Hàm tiện ích dùng chung.
function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function money($v): string { return number_format((float)$v, 0, ',', '.') . ' ₫'; }
function url(string $path = ''): string { return BASE_URL . ltrim($path, '/'); }
function redirect(string $path): void { header('Location: ' . url($path)); exit; }
function post_string(string $key): string { return isset($_POST[$key]) && is_string($_POST[$key]) ? $_POST[$key] : ''; }

const ORDER_STATUS = ['pending'=>'Chờ xác nhận','confirmed'=>'Đã xác nhận','shipping'=>'Đang giao','completed'=>'Đã hoàn thành','cancelled'=>'Đã hủy'];
const PAYMENT_STATUS = ['unpaid'=>'Chưa thanh toán','pending_verification'=>'Chờ xác minh thanh toán','paid'=>'Đã thanh toán','rejected'=>'Thanh toán bị từ chối'];
const SECURITY_QUESTIONS = [
    'first_school' => 'Tên trường tiểu học đầu tiên của bạn là gì?',
    'first_pet' => 'Tên thú cưng đầu tiên của bạn là gì?',
    'birth_city' => 'Bạn sinh ra ở thành phố nào?',
    'favorite_teacher' => 'Tên giáo viên bạn yêu quý nhất là gì?',
    'childhood_friend' => 'Tên người bạn thân thời thơ ấu của bạn là gì?',
];
const CUSTOMER_RANKS = [
    ['code' => 'dong', 'name' => 'Đồng', 'min' => 0],
    ['code' => 'bac', 'name' => 'Bạc', 'min' => 100],
    ['code' => 'vang', 'name' => 'Vàng', 'min' => 500],
    ['code' => 'bach_kim', 'name' => 'Bạch Kim', 'min' => 1000],
    ['code' => 'kim_cuong', 'name' => 'Kim Cương', 'min' => 3000],
    ['code' => 'tinh_anh', 'name' => 'Tinh Anh', 'min' => 10000],
    ['code' => 'cao_thu', 'name' => 'Cao Thủ', 'min' => 20000],
    ['code' => 'chien_tuong', 'name' => 'Chiến Tướng', 'min' => 50000],
    ['code' => 'thach_dau', 'name' => 'Thách Đấu', 'min' => 100000],
];

function customer_rank(int $exp): array {
    $current = CUSTOMER_RANKS[0];
    foreach (CUSTOMER_RANKS as $index => $rank) {
        if ($exp >= $rank['min']) $current = $rank;
        if ($rank['code'] === $current['code']) {
            $currentIndex = $index;
        }
    }
    return ['current' => $current, 'next' => CUSTOMER_RANKS[$currentIndex + 1] ?? null];
}

function normalize_security_answer(string $answer): string {
    $normalized = preg_replace('/\s+/u', ' ', trim($answer));
    if ($normalized === null) return '';
    return mb_strtolower($normalized, 'UTF-8');
}

function security_answer_errors(string $answer): array {
    $errors = [];
    if (mb_strlen($answer, 'UTF-8') < 2 || mb_strlen($answer, 'UTF-8') > 255) {
        $errors[] = 'Câu trả lời bảo mật từ 2 đến 255 ký tự.';
    }
    if (strlen($answer) > 72) $errors[] = 'Câu trả lời bảo mật không được vượt quá 72 byte.';
    return $errors;
}

function password_policy_errors(string $password): array {
    $errors = [];
    if (mb_strlen($password, 'UTF-8') < 8) $errors[] = 'Mật khẩu tối thiểu 8 ký tự.';
    if (!preg_match('/[A-Z]/', $password)) $errors[] = 'Mật khẩu phải có ít nhất một chữ hoa.';
    if (!preg_match('/[a-z]/', $password)) $errors[] = 'Mật khẩu phải có ít nhất một chữ thường.';
    if (!preg_match('/[0-9]/', $password)) $errors[] = 'Mật khẩu phải có ít nhất một chữ số.';
    if (!preg_match('/[^A-Za-z0-9]/', $password)) $errors[] = 'Mật khẩu phải có ít nhất một ký tự đặc biệt.';
    if (strlen($password) > 72) $errors[] = 'Mật khẩu không được vượt quá 72 byte.';
    return $errors;
}

function ensure_rank_profile(PDO $pdo, int $userId): void {
    $pdo->prepare(
        "INSERT INTO customer_ranks (user_id, total_exp, total_eligible_spend, spend_remainder, current_rank)
         VALUES (?, 0, 0, 0, 'dong')
         ON DUPLICATE KEY UPDATE user_id = VALUES(user_id)"
    )->execute([$userId]);
}

// Gọi bên trong transaction đang cập nhật trạng thái đơn hàng.
function sync_order_rank(PDO $pdo, int $orderId): array {
    $orderSt = $pdo->prepare('SELECT user_id, status, payment_status FROM orders WHERE id = ? FOR UPDATE');
    $orderSt->execute([$orderId]);
    $order = $orderSt->fetch();
    if (!$order) throw new RuntimeException('Không tìm thấy đơn hàng cần đồng bộ Rank.');

    $userId = (int)$order['user_id'];
    ensure_rank_profile($pdo, $userId);
    $rankSt = $pdo->prepare('SELECT total_exp, total_eligible_spend FROM customer_ranks WHERE user_id = ? FOR UPDATE');
    $rankSt->execute([$userId]);
    $profile = $rankSt->fetch();
    if (!$profile) throw new RuntimeException('Không thể khóa bản ghi Rank của khách hàng.');

    $ledgerSt = $pdo->prepare('SELECT spend_amount, is_active FROM rank_order_earnings WHERE order_id = ? FOR UPDATE');
    $ledgerSt->execute([$orderId]);
    $ledger = $ledgerSt->fetch();
    $eligible = $order['status'] === 'completed' && $order['payment_status'] === 'paid';
    $amount = 0;
    if ($eligible) {
        $amountSt = $pdo->prepare('SELECT COALESCE(SUM(price * quantity), 0) FROM order_items WHERE order_id = ?');
        $amountSt->execute([$orderId]);
        $amount = (int)$amountSt->fetchColumn();
    }

    $oldAmount = $ledger && (int)$ledger['is_active'] === 1 ? (int)$ledger['spend_amount'] : 0;
    $newAmount = $eligible ? $amount : 0;
    if (!$ledger && $eligible) {
        $pdo->prepare('INSERT INTO rank_order_earnings (order_id, user_id, spend_amount, is_active) VALUES (?, ?, ?, 1)')
            ->execute([$orderId, $userId, $amount]);
    } elseif ($ledger) {
        $pdo->prepare('UPDATE rank_order_earnings SET spend_amount = ?, is_active = ? WHERE order_id = ?')
            ->execute([$eligible ? $amount : (int)$ledger['spend_amount'], $eligible ? 1 : 0, $orderId]);
    }

    $totalSpend = (int)$profile['total_eligible_spend'] + $newAmount - $oldAmount;
    if ($totalSpend < 0) throw new RuntimeException('Tổng chi tiêu hợp lệ của Rank không thể âm.');
    $totalExp = intdiv($totalSpend, 1000);
    $rank = customer_rank($totalExp);
    $previousRank = customer_rank((int)$profile['total_exp'])['current'];
    $previousIndex = array_search($previousRank['code'], array_column(CUSTOMER_RANKS, 'code'), true);
    $currentIndex = array_search($rank['current']['code'], array_column(CUSTOMER_RANKS, 'code'), true);
    $rankedUp = $currentIndex > $previousIndex;

    $pdo->prepare(
        "UPDATE customer_ranks
         SET total_eligible_spend = ?, total_exp = ?, spend_remainder = ?, current_rank = ?,
             last_rank_up_at = CASE
                 WHEN ? = 1 THEN CURRENT_TIMESTAMP
                 WHEN ? = 1 THEN NULL
                 ELSE last_rank_up_at
             END
         WHERE user_id = ?"
    )->execute([
        $totalSpend,
        $totalExp,
        $totalSpend % 1000,
        $rank['current']['code'],
        $rankedUp ? 1 : 0,
        $currentIndex < $previousIndex ? 1 : 0,
        $userId,
    ]);

    return ['ranked_up' => $rankedUp, 'rank' => $rank['current']['name']];
}

// ----- CSRF -----
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
function csrf_ok(): bool {
    return isset($_POST['csrf'], $_SESSION['csrf'])
        && is_string($_POST['csrf'])
        && is_string($_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], $_POST['csrf']);
}

// ----- Thông báo flash -----
function flash(string $type, string $msg): void { $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg]; }
function take_flash(): array { $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f; }

// ----- Ảnh: nếu không có file thật thì dùng ảnh thay thế -----
function file_url(?string $rel, string $fallback = 'assets/images/no-image.svg'): string {
    $rel = (string)$rel;
    if ($rel !== '' && strpos($rel, '..') === false && is_file(__DIR__ . '/../' . $rel)) return url($rel);
    return url($fallback);
}

// ----- Phản hồi cho action: JSON (fetch) hoặc chuyển trang -----
function wants_json(): bool {
    return (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
        || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'fetch');
}
function respond(bool $ok, string $msg, string $backTo, array $extra = []): void {
    if (wants_json()) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array_merge(['ok' => $ok, 'message' => $msg], $extra), JSON_UNESCAPED_UNICODE);
        exit;
    }
    flash($ok ? 'success' : 'error', $msg);
    redirect($backTo);
}

// ----- Giỏ hàng -----
function get_cart_id(int $userId, bool $create = true): ?int {
    $st = db()->prepare('SELECT id FROM cart WHERE user_id = ?');
    $st->execute([$userId]);
    $id = $st->fetchColumn();
    if ($id) return (int)$id;
    if (!$create) return null;
    db()->prepare('INSERT INTO cart (user_id) VALUES (?)')->execute([$userId]);
    return (int)db()->lastInsertId();
}
function cart_count(int $userId): int {
    $st = db()->prepare('SELECT COALESCE(SUM(ci.quantity),0) FROM cart c JOIN cart_items ci ON ci.cart_id = c.id WHERE c.user_id = ?');
    $st->execute([$userId]);
    return (int)$st->fetchColumn();
}
// Lấy các dòng giỏ hàng cùng giá/tồn kho HIỆN TẠI từ database.
function cart_lines(int $userId): array {
    $st = db()->prepare(
        'SELECT ci.product_id, ci.quantity, p.name, p.price, p.image, p.stock, p.status
         FROM cart c JOIN cart_items ci ON ci.cart_id = c.id JOIN products p ON p.id = ci.product_id
         WHERE c.user_id = ? ORDER BY ci.id'
    );
    $st->execute([$userId]);
    return $st->fetchAll();
}

// Chỉ cho phép quay lại các trang nội bộ hợp lệ (chống chuyển hướng ra ngoài).
function safe_back(?string $b, string $default = 'cart.php'): string {
    $b = (string)$b;
    return preg_match('/^[a-z_]+\.php(\?[A-Za-z0-9_=&]*)?$/', $b) ? $b : $default;
}

// Đọc + kiểm tra dữ liệu biểu mẫu sản phẩm (dùng cho thêm/sửa). Trả về [dữ liệu, mảng lỗi].
function validate_product_post(): array {
    $d = [
        'name' => trim($_POST['name'] ?? ''),
        'price' => trim($_POST['price'] ?? ''),
        'category_id' => (int)($_POST['category_id'] ?? 0),
        'stock' => trim($_POST['stock'] ?? ''),
        'status' => $_POST['status'] ?? 'active',
        'description' => trim($_POST['description'] ?? ''),
    ];
    $err = [];
    if (!csrf_ok()) $err[] = 'Phiên làm việc không hợp lệ, vui lòng thử lại.';
    if ($d['name'] === '' || mb_strlen($d['name']) > 150) $err[] = 'Tên cá bắt buộc, tối đa 150 ký tự.';
    if (!ctype_digit($d['price']) || (int)$d['price'] < 1 || (int)$d['price'] > 999999999) $err[] = 'Giá phải là số nguyên dương (VNĐ).';
    if (!ctype_digit($d['stock']) || (int)$d['stock'] > 1000000) $err[] = 'Số lượng tồn kho phải là số nguyên từ 0 trở lên.';
    if (!in_array($d['status'], ['active', 'inactive'], true)) $err[] = 'Trạng thái không hợp lệ.';
    $st = db()->prepare('SELECT 1 FROM categories WHERE id = ?');
    $st->execute([$d['category_id']]);
    if (!$st->fetchColumn()) $err[] = 'Vui lòng chọn danh mục hợp lệ.';
    if (mb_strlen($d['description']) > 5000) $err[] = 'Mô tả tối đa 5000 ký tự.';
    return [$d, $err];
}

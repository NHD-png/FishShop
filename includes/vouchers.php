<?php
// Voucher prices are whole VND. Values from a browser are never trusted totals.
function voucher_normalize_code(string $code): string
{
    return strtoupper(trim($code));
}

function voucher_schema_ready(PDO $pdo): bool
{
    $tables = $pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('vouchers', 'voucher_usages')")->fetchColumn();
    $columns = $pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME IN ('subtotal', 'discount_amount', 'voucher_code')")->fetchColumn();
    return (int)$tables === 2 && (int)$columns === 3;
}

function voucher_discount(array $voucher, int $subtotal): int
{
    if ($subtotal <= 0) throw new DomainException('Giỏ hàng chưa có sản phẩm để áp dụng mã giảm giá.');
    $minimum = (int)$voucher['min_order_amount'];
    if ($subtotal < $minimum) throw new DomainException('Mã này áp dụng cho đơn từ ' . number_format($minimum, 0, ',', '.') . ' đ.');
    $value = (int)$voucher['discount_value'];
    if ($voucher['discount_type'] === 'percent') {
        if ($value < 1 || $value > 99) throw new DomainException('Mã giảm giá chưa được cấu hình hợp lệ.');
        $discount = intdiv($subtotal * $value, 100);
    } elseif ($voucher['discount_type'] === 'fixed' && $value > 0) {
        $discount = $value;
    } else {
        throw new DomainException('Mã giảm giá chưa được cấu hình hợp lệ.');
    }
    if ($voucher['max_discount_amount'] !== null) $discount = min($discount, (int)$voucher['max_discount_amount']);
    if ($discount < 1) throw new DomainException('Giá trị giỏ hàng chưa đủ để nhận ưu đãi của mã này.');
    if ($discount >= $subtotal) throw new DomainException('Tổng tiền sau giảm phải lớn hơn 0 đ. Vui lòng chọn mã khác hoặc thêm sản phẩm.');
    return $discount;
}

/** Counts paid and unpaid orders that have not been cancelled. */
function voucher_usage_counts(PDO $pdo, int $voucherId, bool $lock = false): array
{
    if ($lock && !$pdo->inTransaction()) throw new LogicException('Voucher locking requires an active transaction.');
    // Locking reads see current committed rows even after an earlier snapshot read.
    $sql = "SELECT vu.user_id FROM voucher_usages vu JOIN orders o ON o.id = vu.order_id WHERE vu.voucher_id = ? AND o.status <> 'cancelled'";
    if ($lock) $sql .= ' FOR UPDATE';
    $st = $pdo->prepare($sql);
    $st->execute([$voucherId]);
    $users = [];
    $total = 0;
    while (($userId = $st->fetchColumn()) !== false) {
        $id = (int)$userId;
        $users[$id] = ($users[$id] ?? 0) + 1;
        $total++;
    }
    return ['total' => $total, 'users' => $users];
}

function voucher_quote(PDO $pdo, string $code, int $userId, int $subtotal, bool $lock = false): array
{
    $code = voucher_normalize_code($code);
    $quote = ['voucher_id' => null, 'code' => '', 'discount' => 0, 'subtotal' => $subtotal, 'total' => $subtotal];
    if ($code === '') return $quote;
    if (!preg_match('/^[A-Z0-9_-]{3,32}$/D', $code)) throw new DomainException('Mã giảm giá gồm 3–32 ký tự: chữ, số, dấu gạch ngang hoặc gạch dưới.');
    if (!voucher_schema_ready($pdo)) throw new DomainException('Mã giảm giá chưa sẵn sàng. Vui lòng bỏ mã để tiếp tục đặt hàng.');
    if ($userId < 1) throw new DomainException('Vui lòng đăng nhập để dùng mã giảm giá.');
    if ($lock && !$pdo->inTransaction()) throw new LogicException('Voucher locking requires an active transaction.');
    $st = $pdo->prepare('SELECT * FROM vouchers WHERE code = ?' . ($lock ? ' FOR UPDATE' : ''));
    $st->execute([$code]);
    $voucher = $st->fetch(PDO::FETCH_ASSOC);
    if (!$voucher || !(int)$voucher['is_active']) throw new DomainException('Mã giảm giá không tồn tại hoặc đã ngừng áp dụng.');
    $zone = new DateTimeZone('Asia/Ho_Chi_Minh');
    $now = new DateTimeImmutable('now', $zone);
    if ($voucher['starts_at'] !== null && new DateTimeImmutable($voucher['starts_at'], $zone) > $now) throw new DomainException('Mã giảm giá chưa đến thời gian sử dụng.');
    if ($voucher['ends_at'] !== null && new DateTimeImmutable($voucher['ends_at'], $zone) < $now) throw new DomainException('Mã giảm giá đã hết hạn.');
    $discount = voucher_discount($voucher, $subtotal);
    $usage = voucher_usage_counts($pdo, (int)$voucher['id'], $lock);
    if ($voucher['usage_limit'] !== null && $usage['total'] >= (int)$voucher['usage_limit']) throw new DomainException('Mã giảm giá đã hết lượt sử dụng.');
    if (($usage['users'][$userId] ?? 0) >= (int)$voucher['per_user_limit']) throw new DomainException('Bạn đã dùng hết lượt của mã giảm giá này.');
    return ['voucher_id' => (int)$voucher['id'], 'code' => $voucher['code'], 'discount' => $discount, 'subtotal' => $subtotal, 'total' => $subtotal - $discount];
}

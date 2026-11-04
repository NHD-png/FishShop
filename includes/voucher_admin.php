<?php
require_once __DIR__ . '/vouchers.php';

function voucher_admin_defaults(): array
{
    return ['code' => '', 'name' => '', 'discount_type' => 'percent', 'discount_value' => 10,
        'min_order_amount' => 0, 'max_discount_amount' => null, 'starts_at' => null,
        'ends_at' => null, 'usage_limit' => null, 'per_user_limit' => 1, 'is_active' => 1];
}

function voucher_admin_validate(array $input): array
{
    $errors = [];
    $text = static fn(string $key): string => isset($input[$key]) && is_string($input[$key]) ? trim($input[$key]) : '';
    $data = voucher_admin_defaults();
    $data['code'] = voucher_normalize_code($text('code'));
    $data['name'] = $text('name');
    $data['discount_type'] = $text('discount_type');
    $data['is_active'] = $text('is_active') === '1' ? 1 : 0;
    if (!preg_match('/^[A-Z0-9_-]{3,32}$/D', $data['code'])) $errors[] = 'Mã gồm 3–32 ký tự chữ, số, dấu gạch ngang hoặc gạch dưới.';
    if (mb_strlen($data['name']) < 2 || mb_strlen($data['name']) > 100) $errors[] = 'Tên chương trình từ 2 đến 100 ký tự.';
    if (!in_array($data['discount_type'], ['fixed', 'percent'], true)) $errors[] = 'Loại giảm giá không hợp lệ.';
    $numbers = [
        'discount_value' => [1, $data['discount_type'] === 'percent' ? 99 : 9999999999, false, 'Giá trị giảm'],
        'min_order_amount' => [0, 9999999999, false, 'Đơn tối thiểu'],
        'max_discount_amount' => [1, 9999999999, true, 'Giảm tối đa'],
        'usage_limit' => [1, 1000000000, true, 'Tổng lượt dùng'],
        'per_user_limit' => [1, 1000000, false, 'Lượt dùng mỗi khách'],
    ];
    foreach ($numbers as $key => [$min, $max, $optional, $label]) {
        $value = $text($key);
        if ($optional && $value === '') { $data[$key] = null; continue; }
        if (!ctype_digit($value) || strlen($value) > 10 || (int)$value < $min || (int)$value > $max) {
            $errors[] = $label . ' phải là số nguyên từ ' . $min . ' đến ' . $max . '.';
        }
        $data[$key] = ctype_digit($value) && strlen($value) <= 10 ? (int)$value : 0;
    }
    foreach (['starts_at' => 'Ngày bắt đầu', 'ends_at' => 'Ngày kết thúc'] as $key => $label) {
        $value = $text($key);
        if ($value === '') { $data[$key] = null; continue; }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value, new DateTimeZone('Asia/Ho_Chi_Minh'));
        if (!$date || $date->format('Y-m-d\TH:i') !== $value) {
            $errors[] = $label . ' không hợp lệ.';
            $data[$key] = null;
        } else $data[$key] = $date->format('Y-m-d H:i:s');
    }
    if ($data['starts_at'] && $data['ends_at'] && $data['ends_at'] <= $data['starts_at']) $errors[] = 'Ngày kết thúc phải sau ngày bắt đầu.';
    return [$data, $errors];
}

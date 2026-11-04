<?php
/** Optional coordinate pair; never trust browser-hidden map fields. */
function shipping_location_from_values(mixed $latitude, mixed $longitude): ?array
{
    $empty = static fn(mixed $value): bool => $value === null || (is_string($value) && trim($value) === '');
    if ($empty($latitude) && $empty($longitude)) return null;
    $invalid = 'Điểm giao hàng không hợp lệ. Vui lòng chọn lại trên bản đồ hoặc bỏ ghim.';
    if ($empty($latitude) || $empty($longitude)) throw new DomainException($invalid);
    foreach ([$latitude, $longitude] as $value) {
        if ((!is_string($value) && !is_int($value) && !is_float($value)) || !is_numeric($value) || !is_finite((float)$value)) {
            throw new DomainException($invalid);
        }
    }
    $lat = (float)$latitude;
    $lng = (float)$longitude;
    if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) throw new DomainException($invalid);
    return ['latitude' => number_format($lat, 7, '.', ''), 'longitude' => number_format($lng, 7, '.', '')];
}

function shipping_location_schema_ready(PDO $pdo): bool
{
    $st = $pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME IN ('shipping_latitude', 'shipping_longitude')");
    return (int)$st->fetchColumn() === 2;
}

function shipping_location_url(array $order): ?string
{
    try {
        $point = shipping_location_from_values($order['shipping_latitude'] ?? null, $order['shipping_longitude'] ?? null);
    } catch (DomainException $ex) {
        return null;
    }
    if ($point === null) return null;
    return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($point['latitude'] . ',' . $point['longitude']);
}

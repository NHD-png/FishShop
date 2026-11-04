<?php
require_once __DIR__ . '/../includes/payos.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['ok' => false]);
    exit;
}

$user = requireLogin();
$id = (int)($_GET['id'] ?? 0);
$st = db()->prepare('SELECT id, payment_method, payment_status FROM orders WHERE id = ? AND user_id = ?');
$st->execute([$id, (int)$user['id']]);
$order = $st->fetch();
if (!$order) {
    http_response_code(404);
    echo json_encode(['ok' => false]);
    exit;
}

$paymentStatus = $order['payment_status'];
if ($order['payment_method'] === 'payos' && $paymentStatus !== 'paid') {
    $paymentStatus = payos_sync_order_status($id);
}

echo json_encode(['ok' => true, 'payment_status' => $paymentStatus], JSON_UNESCAPED_UNICODE);

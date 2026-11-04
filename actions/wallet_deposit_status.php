<?php
require_once __DIR__ . '/../includes/payos.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    http_response_code(405);
    echo json_encode(['ok'=>false]);
    exit;
}
$user = requireLogin();
$id = (int)($_GET['id'] ?? 0);
$st = db()->prepare('SELECT status FROM wallet_deposits WHERE id=? AND user_id=?');
$st->execute([$id,(int)$user['id']]);
$status = $st->fetchColumn();
if ($status === false) {
    http_response_code(404);
    echo json_encode(['ok'=>false]);
    exit;
}
if ($status === 'pending') $status = payos_sync_wallet_deposit_status($id,(int)$user['id']);
echo json_encode(['ok'=>true,'status'=>$status],JSON_UNESCAPED_UNICODE);

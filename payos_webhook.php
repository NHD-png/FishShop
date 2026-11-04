<?php
require_once __DIR__ . '/includes/payos.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
function payos_webhook_reply(int $status, array $data): void { http_response_code($status); echo json_encode($data); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !payos_ready()) payos_webhook_reply(400, ['ok' => false]);
$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload) || !is_array($payload['data'] ?? null)
    || !payos_valid_signature($payload['data'], (string)($payload['signature'] ?? ''), payos_config()['checksum_key'])) {
    payos_webhook_reply(400, ['ok' => false]);
}
$data = $payload['data'];
if (($payload['success'] ?? false) === true && ($payload['code'] ?? '') === '00'
    && ($data['code'] ?? '') === '00' && (int)($data['orderCode'] ?? 0) > 0) {
    $pdo = db();
    try {
        $depositResult = payos_confirm_wallet_deposit((string)($data['paymentLinkId'] ?? ''), (int)($data['amount'] ?? 0));
        if ($depositResult === true) payos_webhook_reply(200, ['ok' => true]);
        if ($depositResult === false) payos_webhook_reply(400, ['ok' => false]);
        $pdo->beginTransaction();
        $paymentLinkId = (string)($data['paymentLinkId'] ?? '');
        if ($paymentLinkId !== '') {
            $st = $pdo->prepare("SELECT id, total, status, payment_status FROM orders WHERE payos_payment_link_id = ? AND payment_method = 'payos' FOR UPDATE");
            $st->execute([$paymentLinkId]);
        } else {
            // Backward compatibility for callbacks from links created before
            // paymentLinkId-based retry handling was added.
            $st = $pdo->prepare("SELECT id, total, status, payment_status FROM orders WHERE id = ? AND payment_method = 'payos' FOR UPDATE");
            $st->execute([(int)$data['orderCode']]);
        }
        $order = $st->fetch();
        if (!$order || (int)$order['total'] !== (int)($data['amount'] ?? 0) || $order['status'] === 'cancelled') {
            $pdo->rollBack(); payos_webhook_reply(400, ['ok' => false]);
        }
        if ($order['payment_status'] !== 'paid') {
            $pdo->prepare("UPDATE orders SET payment_status = 'paid', payos_paid_at = UTC_TIMESTAMP() WHERE id = ? AND payment_status <> 'paid'")
                ->execute([(int)$order['id']]);
        }
        sync_order_rank($pdo, (int)$order['id']);
        $pdo->commit();
        payos_webhook_reply(200, ['ok' => true]);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('FishShop PayOS webhook processing failed (' . get_class($e) . ').');
        payos_webhook_reply(500, ['ok' => false]);
    }
}
payos_webhook_reply(200, ['ok' => true]);

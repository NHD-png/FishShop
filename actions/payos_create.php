<?php
require_once __DIR__ . '/../includes/payos.php';
$user = requireLogin();
requirePost('purchase_history.php');
$id = (int)post_string('order_id');
$st = db()->prepare("SELECT id FROM orders WHERE id = ? AND user_id = ? AND payment_method = 'payos' AND status <> 'cancelled'");
$st->execute([$id, (int)$user['id']]);
if (!$st->fetchColumn()) { flash('error', 'Không tìm thấy đơn cần thanh toán.'); redirect('purchase_history.php'); }
try {
    if (post_string('regenerate') === '1') payos_cancel_order_link($id);
    payos_create_order_link($id);
    redirect('payment.php?id=' . $id);
}
catch (Throwable $e) {
    error_log('FishShop PayOS checkout failed (' . get_class($e) . ').');
    flash('error', 'Chưa tạo được mã QR chuyển khoản. Vui lòng thử lại sau.');
    redirect('payment.php?id=' . $id);
}

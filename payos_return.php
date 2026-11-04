<?php
require_once __DIR__ . '/includes/auth.php';
$user = requireLogin();
$id = (int)($_GET['order'] ?? 0);
$st = db()->prepare('SELECT id, payment_status FROM orders WHERE id = ? AND user_id = ?');
$st->execute([$id, (int)$user['id']]);
if (!$st->fetch()) { flash('error', 'Không tìm thấy đơn hàng.'); redirect('purchase_history.php'); }
if (($_GET['result'] ?? '') === 'cancel') {
    flash('error', 'Bạn đã hủy thanh toán. Có thể tạo mã QR PayOS mới để thử lại.');
    redirect('payment.php?id=' . $id . '&cancel=true&status=CANCELLED');
}
flash('success', 'Đã quay lại từ PayOS. Trạng thái sẽ cập nhật sau khi PayOS xác nhận webhook.');
redirect('payment.php?id=' . $id);

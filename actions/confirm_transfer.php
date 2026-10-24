<?php
// Khách báo "tôi đã chuyển khoản": chỉ chuyển unpaid/rejected -> pending_verification.
// KHÔNG bao giờ đặt paid ở đây; chỉ quản trị viên xác nhận paid sau khi kiểm tra giao dịch.
require_once __DIR__ . '/../includes/auth.php';
$user = requireLogin();
$id = (int)($_POST['order_id'] ?? 0);
requirePost('purchase_history.php');

$st = db()->prepare("UPDATE orders SET payment_status = 'pending_verification'
                     WHERE id = ? AND user_id = ? AND status <> 'cancelled' AND payment_status IN ('unpaid','rejected')");
$st->execute([$id, (int)$user['id']]);
if ($st->rowCount() === 1) respond(true, 'Đã ghi nhận. Quản trị viên sẽ kiểm tra giao dịch và xác nhận thanh toán.', 'payment.php?id=' . $id);
respond(false, 'Không thể cập nhật trạng thái thanh toán của đơn này.', 'purchase_history.php');

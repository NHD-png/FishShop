<?php
require_once __DIR__ . '/bootstrap.php';
requireAdmin();
$orderId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$orderId) {
    http_response_code(400);
    exit('Mã đơn hàng không hợp lệ.');
}
header('Location: ' . BASE_URL . 'admin/export_order_pdf.php?id=' . $orderId, true, 302);
exit;

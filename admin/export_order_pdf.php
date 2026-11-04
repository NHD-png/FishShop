<?php
require_once __DIR__ . '/bootstrap.php';
requireAdmin();

$orderId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$orderId) {
    http_response_code(400);
    exit('Mã đơn hàng không hợp lệ.');
}

$st = db()->prepare('SELECT o.*, u.username, u.fullname FROM orders o JOIN users u ON u.id=o.user_id WHERE o.id=?');
$st->execute([$orderId]);
$order = $st->fetch();
if (!$order) {
    http_response_code(404);
    exit('Không tìm thấy đơn hàng.');
}

$st = db()->prepare('SELECT oi.quantity,oi.price,p.name FROM order_items oi JOIN products p ON p.id=oi.product_id WHERE oi.order_id=? ORDER BY oi.id');
$st->execute([$orderId]);
$items = $st->fetchAll();

$orderStatus = ORDER_STATUS[$order['status']] ?? (string)$order['status'];
$paymentStatus = PAYMENT_STATUS[$order['payment_status']] ?? (string)$order['payment_status'];
$createdAt = date('d/m/Y H:i', strtotime((string)$order['created_at']));
$documentTitle = 'FishShop-don-hang-' . (int)$order['id'];
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: private, no-store, max-age=0');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?=e($documentTitle)?></title>
<style>
@page { size:A4; margin:16mm; }
* { box-sizing:border-box; }
body { margin:0; background:#eef2f5; color:#17283a; font:14px/1.5 Arial,"Segoe UI",sans-serif; }
.toolbar { position:sticky; top:0; display:flex; justify-content:center; gap:10px; padding:12px; background:#102131; }
.toolbar button { border:0; border-radius:8px; padding:10px 18px; background:#5ee0c3; color:#092c28; font-weight:700; cursor:pointer; }
.toolbar a { padding:10px 18px; border:1px solid #5ee0c3; border-radius:8px; color:#5ee0c3; text-decoration:none; }
.paper { width:210mm; min-height:297mm; margin:22px auto; padding:16mm; background:#fff; box-shadow:0 4px 24px #14233324; }
.top { border-bottom:2px solid #39cbb1; padding-bottom:12px; margin-bottom:18px; }
.muted { color:#60758a; }
h1 { margin:0 0 4px; color:#0f766e; font-size:26px; }
h2 { margin:0 0 10px; color:#17344a; font-size:16px; }
p { margin:4px 0; }
table { width:100%; border-collapse:collapse; }
.meta { margin-bottom:20px; }
.meta td { width:50%; padding:10px; border:1px solid #d5e0e8; vertical-align:top; }
.items th { background:#17344a; color:#fff; text-align:left; }
.items th,.items td { border:1px solid #ccd8e2; padding:8px; }
.right { text-align:right!important; }
.total { color:#0f766e; font-weight:700; }
@media print { body { background:#fff; } .toolbar { display:none!important; } .paper { width:auto; min-height:0; margin:0; padding:0; box-shadow:none; } tr { break-inside:avoid; } }
@media screen and (max-width:700px) { .paper { width:100%; min-height:0; margin:0; padding:20px; } .meta td { display:block; width:100%; } }
</style>
</head>
<body>
<div class="toolbar"><button type="button" onclick="window.print()">Lưu hoặc in PDF</button><a href="<?=url('admin/orders.php?id='.(int)$order['id'])?>">Quay lại đơn hàng</a></div>
<main class="paper">
  <header class="top">
    <p class="muted">FISH SHOP · THẾ GIỚI CÁ CẢNH</p>
    <h1>Đơn hàng #<?= (int)$order['id'] ?></h1>
    <p class="muted">Ngày đặt: <?=e($createdAt)?></p>
  </header>
  <table class="meta"><tr>
    <td><h2>Khách hàng</h2>
      <p><strong>Tài khoản:</strong> <?=e($order['username'])?></p>
      <p><strong>Họ tên:</strong> <?=e($order['fullname'])?></p>
      <p><strong>Điện thoại:</strong> <?=e($order['phone'])?></p>
      <p><strong>Giao đến:</strong> <?=nl2br(e($order['shipping_address']))?></p>
    </td>
    <td><h2>Thông tin đơn</h2>
      <p><strong>Mã đơn:</strong> #<?= (int)$order['id'] ?></p>
      <p><strong>Trạng thái đơn:</strong> <?=e($orderStatus)?></p>
      <p><strong>Trạng thái thanh toán:</strong> <?=e($paymentStatus)?></p>
      <p><strong>Tổng tiền:</strong> <span class="total"><?=e(money($order['total']))?></span></p>
    </td>
  </tr></table>
  <h2>Sản phẩm</h2>
  <table class="items">
    <thead><tr><th>Sản phẩm</th><th class="right">Đơn giá</th><th class="right">Số lượng</th><th class="right">Thành tiền</th></tr></thead>
    <tbody>
    <?php foreach ($items as $item): ?>
      <tr><td><?=e($item['name'])?></td><td class="right"><?=e(money($item['price']))?></td><td class="right"><?= (int)$item['quantity'] ?></td><td class="right"><?=e(money((float)$item['price']*(int)$item['quantity']))?></td></tr>
    <?php endforeach; ?>
    <?php if ((int)($order['discount_amount'] ?? 0) > 0): ?>
      <tr><td colspan="3" class="right">Tạm tính</td><td class="right"><?=e(money($order['subtotal'] ?? ((int)$order['total']+(int)$order['discount_amount'])))?></td></tr>
      <tr><td colspan="3" class="right">Giảm giá · <?=e($order['voucher_code'])?></td><td class="right">−<?=e(money($order['discount_amount']))?></td></tr>
    <?php endif; ?>
      <tr><td colspan="3" class="right"><strong>Tổng cộng</strong></td><td class="right total"><?=e(money($order['total']))?></td></tr>
    </tbody>
  </table>
</main>
<script>window.addEventListener('load',()=>window.print());</script>
</body>
</html>

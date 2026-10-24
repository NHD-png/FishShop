<?php
require_once __DIR__ . '/includes/auth.php';
$user  = requireLogin();
$lines = cart_lines((int)$user['id']);
if (!$lines) { flash('error', 'Giỏ hàng đang trống.'); redirect('cart.php'); }

$total = 0;
foreach ($lines as $l) {
    if ($l['status'] !== 'active' || (int)$l['stock'] < (int)$l['quantity'] || (int)$l['quantity'] < 1) {
        flash('error', 'Giỏ hàng có sản phẩm không đủ hàng hoặc đã ngừng bán. Vui lòng kiểm tra lại.');
        redirect('cart.php');
    }
    $total += (int)$l['quantity'] * (float)$l['price'];
}

$pageTitle = 'Xác nhận mua hàng';
include __DIR__ . '/includes/header.php';
?>
<p class="crumb"><a href="<?= url('index.php') ?>">Trang chủ</a> / <a href="<?= url('cart.php') ?>">Giỏ hàng</a> / Xác nhận mua hàng</p>
<h1 style="font-size:28px">Xác nhận mua hàng</h1>
<div class="cart">
  <section class="card">
    <h2>Thông tin nhận hàng</h2>
    <form action="<?= url('actions/place_order.php') ?>" method="post" class="js-validate" novalidate>
      <?= csrf_field() ?>
      <div class="field"><label for="phone">Số điện thoại nhận hàng</label>
        <input id="phone" name="phone" type="text" inputmode="numeric" data-rule="phone" value="<?= e($user['phone']) ?>" required></div>
      <div class="field"><label for="shipping_address">Địa chỉ giao hàng</label>
        <textarea id="shipping_address" name="shipping_address" data-rule="address" required><?= e($user['address']) ?></textarea>
        <small>Kiểm tra kỹ địa chỉ và số điện thoại trước khi đặt.</small></div>
      <p class="hint" style="margin-bottom:16px">Sau khi đặt hàng, bạn sẽ nhận mã QR để chuyển khoản. Đơn hàng chỉ được xác nhận thanh toán sau khi quản trị viên kiểm tra giao dịch.</p>
      <button class="btn" type="submit">Đặt hàng</button>
      <a class="btn ghost" href="<?= url('cart.php') ?>">Quay lại giỏ hàng</a>
    </form>
  </section>
  <aside class="order-summary">
    <h2>Đơn hàng (<?= count($lines) ?> sản phẩm)</h2>
    <?php foreach ($lines as $l): ?>
      <div class="sum"><span><?= e($l['name']) ?> × <?= (int)$l['quantity'] ?></span><span><?= money((int)$l['quantity'] * (float)$l['price']) ?></span></div>
    <?php endforeach; ?>
    <div class="sum t"><span>Tổng cộng</span><span><?= money($total) ?></span></div>
    <p class="hint">Tổng tiền cuối cùng do máy chủ tính lại từ giá hiện tại khi bạn đặt hàng.</p>
  </aside>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

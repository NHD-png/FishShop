<?php
require_once __DIR__ . '/includes/auth.php';
$user = requireLogin();
$uid = (int)$user['id'];
$detailId = (int)($_GET['id'] ?? 0);

$order = null; $items = [];
if ($detailId > 0) {
    $st = db()->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ?');   // không xem được đơn của người khác
    $st->execute([$detailId, $uid]);
    $order = $st->fetch();
    if (!$order) { flash('error', 'Không tìm thấy đơn hàng.'); redirect('purchase_history.php'); }
    $st = db()->prepare('SELECT oi.quantity, oi.price, p.id AS pid, p.name, p.image FROM order_items oi JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ? ORDER BY oi.id');
    $st->execute([$detailId]);
    $items = $st->fetchAll();
} else {
    $st = db()->prepare('SELECT o.*, (SELECT COALESCE(SUM(quantity),0) FROM order_items WHERE order_id = o.id) AS qty FROM orders o WHERE o.user_id = ? ORDER BY o.id DESC');
    $st->execute([$uid]);
    $orders = $st->fetchAll();
}

$pageTitle = $order ? 'Đơn hàng #' . $order['id'] : 'Lịch sử mua hàng';
include __DIR__ . '/includes/header.php';
?>
<?php if ($order): ?>
  <p class="crumb"><a href="<?= url('index.php') ?>">Trang chủ</a> / <a href="<?= url('purchase_history.php') ?>">Lịch sử mua hàng</a> / Đơn #<?= (int)$order['id'] ?></p>
  <h1 style="font-size:28px;margin-bottom:18px">Đơn hàng #<?= (int)$order['id'] ?></h1>
  <div class="cart">
    <section class="card">
      <h2>Sản phẩm đã mua</h2>
      <?php foreach ($items as $it): ?>
        <div class="row">
          <div class="im"><img src="<?= e(file_url($it['image'])) ?>" alt="<?= e($it['name']) ?>"></div>
          <div><b><?= e($it['name']) ?></b><div class="rt"><?= money($it['price']) ?> × <?= (int)$it['quantity'] ?> <span class="mute">(giá tại thời điểm mua)</span></div></div>
          <div class="line-price"><?= money((float)$it['price'] * (int)$it['quantity']) ?></div>
        </div>
      <?php endforeach; ?>
    </section>
    <aside class="order-summary">
      <h2>Thông tin đơn</h2>
      <div class="sum"><span>Ngày đặt</span><span><?= e(date('d/m/Y H:i', strtotime($order['created_at']))) ?></span></div>
      <div class="sum"><span>Trạng thái đơn</span><span class="tag <?= e($order['status']) ?>"><?= e(ORDER_STATUS[$order['status']]) ?></span></div>
      <div class="sum"><span>Thanh toán</span><span class="tag <?= e($order['payment_status']) ?>"><?= e(PAYMENT_STATUS[$order['payment_status']]) ?></span></div>
      <div class="sum"><span>Điện thoại</span><span><?= e($order['phone']) ?></span></div>
      <div class="sum"><span>Giao đến</span><span style="text-align:right;white-space:normal"><?= e($order['shipping_address']) ?></span></div>
      <div class="sum t"><span>Tổng tiền</span><span><?= money($order['total']) ?></span></div>
      <?php if ($order['status'] !== 'cancelled' && $order['payment_status'] !== 'paid'): ?>
        <a class="btn block" href="<?= url('payment.php?id=' . (int)$order['id']) ?>">Thanh toán / xem mã QR</a>
      <?php endif; ?>
      <a class="btn ghost block" style="margin-top:10px" href="<?= url('purchase_history.php') ?>">Quay lại danh sách</a>
    </aside>
  </div>
<?php else: ?>
  <p class="crumb"><a href="<?= url('index.php') ?>">Trang chủ</a> / Lịch sử mua hàng</p>
  <h1 style="font-size:28px;margin-bottom:20px">Lịch sử mua hàng</h1>
  <?php if (!$orders): ?>
    <div class="empty"><h2>Bạn chưa có đơn hàng nào</h2><p>Đơn hàng sau khi đặt sẽ xuất hiện tại đây.</p><a class="btn" href="<?= url('index.php') ?>">Mua cá ngay</a></div>
  <?php else: ?>
    <div class="table-wrap"><table>
      <thead><tr><th>Mã đơn</th><th>Ngày đặt</th><th class="num">Số lượng</th><th class="num">Tổng tiền</th><th>Trạng thái đơn</th><th>Thanh toán</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($orders as $o): ?>
        <tr>
          <td>#<?= (int)$o['id'] ?></td>
          <td><?= e(date('d/m/Y H:i', strtotime($o['created_at']))) ?></td>
          <td class="num"><?= (int)$o['qty'] ?></td>
          <td class="num"><?= money($o['total']) ?></td>
          <td><span class="tag <?= e($o['status']) ?>"><?= e(ORDER_STATUS[$o['status']]) ?></span></td>
          <td><span class="tag <?= e($o['payment_status']) ?>"><?= e(PAYMENT_STATUS[$o['payment_status']]) ?></span></td>
          <td><a class="btn ghost sm" href="<?= url('purchase_history.php?id=' . (int)$o['id']) ?>">Chi tiết</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  <?php endif; ?>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>

<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
$detailId = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oid = (int)($_POST['order_id'] ?? 0);
    requirePost('admin/orders.php?id=' . $oid);
    $newStatus = $_POST['status'] ?? '';
    $newPay    = $_POST['payment_status'] ?? '';
    if (!isset(ORDER_STATUS[$newStatus]) || !isset(PAYMENT_STATUS[$newPay])) {
        flash('error', 'Trạng thái không hợp lệ.');
        redirect('admin/orders.php?id=' . $oid);
    }
    $pdo = db();
    try {
        $pdo->beginTransaction();
        $st = $pdo->prepare('SELECT status FROM orders WHERE id = ? FOR UPDATE');
        $st->execute([$oid]);
        $old = $st->fetchColumn();
        if ($old === false) { $pdo->rollBack(); flash('error', 'Không tìm thấy đơn hàng.'); redirect('admin/orders.php'); }
        if ($old === 'cancelled' && $newStatus !== 'cancelled') {
            $pdo->rollBack(); flash('error', 'Đơn đã hủy không thể mở lại (tồn kho đã được hoàn).'); redirect('admin/orders.php?id=' . $oid);
        }
        if ($old !== 'cancelled' && $newStatus === 'cancelled') {
            // Hủy đơn: hoàn lại tồn kho
            $pdo->prepare('UPDATE products p JOIN order_items oi ON oi.product_id = p.id SET p.stock = p.stock + oi.quantity WHERE oi.order_id = ?')->execute([$oid]);
        }
        $pdo->prepare('UPDATE orders SET status = ?, payment_status = ? WHERE id = ?')->execute([$newStatus, $newPay, $oid]);
        $pdo->commit();
        flash('success', 'Đã cập nhật đơn hàng #' . $oid . '.');
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        flash('error', 'Không cập nhật được đơn hàng.');
    }
    redirect('admin/orders.php?id=' . $oid);
}

$order = null; $items = [];
if ($detailId > 0) {
    $st = db()->prepare('SELECT o.*, u.username, u.fullname FROM orders o JOIN users u ON u.id = o.user_id WHERE o.id = ?');
    $st->execute([$detailId]);
    $order = $st->fetch();
    if (!$order) { flash('error', 'Không tìm thấy đơn hàng.'); redirect('admin/orders.php'); }
    $st = db()->prepare('SELECT oi.quantity, oi.price, p.name, p.image FROM order_items oi JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ? ORDER BY oi.id');
    $st->execute([$detailId]);
    $items = $st->fetchAll();
} else {
    $fs = $_GET['status'] ?? ''; $fp = $_GET['payment'] ?? '';
    $sql = 'SELECT o.*, u.fullname FROM orders o JOIN users u ON u.id = o.user_id WHERE 1=1';
    $args = [];
    if (isset(ORDER_STATUS[$fs])) { $sql .= ' AND o.status = ?'; $args[] = $fs; }
    if (isset(PAYMENT_STATUS[$fp])) { $sql .= ' AND o.payment_status = ?'; $args[] = $fp; }
    $st = db()->prepare($sql . ' ORDER BY o.id DESC');
    $st->execute($args);
    $orders = $st->fetchAll();
}

$pageTitle = $order ? 'Đơn #' . $order['id'] : 'Quản lý đơn hàng';
include __DIR__ . '/../includes/header.php';
?>
<section class="page-intro"><h1><?= $order ? 'Đơn hàng #' . (int)$order['id'] : 'Đơn hàng' ?></h1><p>Xác nhận thanh toán thủ công sau khi đã kiểm tra giao dịch thực tế trong tài khoản ngân hàng.</p></section>
<?php include __DIR__ . '/../includes/admin_nav.php'; ?>

<?php if ($order): ?>
<div class="cart">
  <section class="card">
    <h2>Sản phẩm</h2>
    <?php foreach ($items as $it): ?>
      <div class="row">
        <div class="im"><img src="<?= e(file_url($it['image'])) ?>" alt=""></div>
        <div><b><?= e($it['name']) ?></b><div class="rt"><?= money($it['price']) ?> × <?= (int)$it['quantity'] ?></div></div>
        <div class="line-price"><?= money((float)$it['price'] * (int)$it['quantity']) ?></div>
      </div>
    <?php endforeach; ?>
  </section>
  <aside class="order-summary">
    <h2>Khách hàng</h2>
    <div class="sum"><span>Tài khoản</span><span><?= e($order['username']) ?></span></div>
    <div class="sum"><span>Họ tên</span><span><?= e($order['fullname']) ?></span></div>
    <div class="sum"><span>Điện thoại</span><span><?= e($order['phone']) ?></span></div>
    <div class="sum"><span>Giao đến</span><span style="text-align:right;white-space:normal"><?= e($order['shipping_address']) ?></span></div>
    <div class="sum"><span>Ngày đặt</span><span><?= e(date('d/m/Y H:i', strtotime($order['created_at']))) ?></span></div>
    <div class="sum t"><span>Tổng tiền</span><span><?= money($order['total']) ?></span></div>
    <form method="post" data-confirm="Lưu thay đổi trạng thái đơn hàng?">
      <?= csrf_field() ?><input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
      <div class="field"><label for="status">Trạng thái đơn</label>
        <select id="status" name="status"><?php foreach (ORDER_STATUS as $k => $v): ?><option value="<?= $k ?>" <?= $order['status'] === $k ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="payment_status">Trạng thái thanh toán</label>
        <select id="payment_status" name="payment_status"><?php foreach (PAYMENT_STATUS as $k => $v): ?><option value="<?= $k ?>" <?= $order['payment_status'] === $k ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select>
        <small>Chỉ chọn “Đã thanh toán” khi tiền đã về tài khoản.</small></div>
      <button class="btn block" type="submit">Lưu thay đổi</button>
    </form>
    <a class="btn ghost block" style="margin-top:10px" href="<?= url('admin/orders.php') ?>">Quay lại danh sách</a>
  </aside>
</div>
<?php else: ?>
<form class="tool" method="get">
  <span><?= count($orders) ?> đơn hàng</span>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <select name="status" aria-label="Lọc trạng thái đơn" onchange="this.form.submit()"><option value="">Mọi trạng thái đơn</option>
      <?php foreach (ORDER_STATUS as $k => $v): ?><option value="<?= $k ?>" <?= ($fs === $k) ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select>
    <select name="payment" aria-label="Lọc thanh toán" onchange="this.form.submit()"><option value="">Mọi trạng thái thanh toán</option>
      <?php foreach (PAYMENT_STATUS as $k => $v): ?><option value="<?= $k ?>" <?= ($fp === $k) ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select>
    <noscript><button class="btn sm" type="submit">Lọc</button></noscript>
  </div>
</form>
<?php if (!$orders): ?>
  <div class="empty"><h2>Không có đơn hàng</h2><p>Chưa có đơn nào khớp bộ lọc.</p></div>
<?php else: ?>
<div class="table-wrap"><table>
  <thead><tr><th>Mã đơn</th><th>Khách hàng</th><th>Ngày đặt</th><th class="num">Tổng tiền</th><th>Đơn</th><th>Thanh toán</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($orders as $o): ?>
    <tr>
      <td>#<?= (int)$o['id'] ?></td><td><?= e($o['fullname']) ?></td>
      <td><?= e(date('d/m/Y H:i', strtotime($o['created_at']))) ?></td>
      <td class="num"><?= money($o['total']) ?></td>
      <td><span class="tag <?= e($o['status']) ?>"><?= e(ORDER_STATUS[$o['status']]) ?></span></td>
      <td><span class="tag <?= e($o['payment_status']) ?>"><?= e(PAYMENT_STATUS[$o['payment_status']]) ?></span></td>
      <td><a class="btn ghost sm" href="<?= url('admin/orders.php?id=' . (int)$o['id']) ?>">Chi tiết</a></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php endif; endif; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>

<?php
require_once __DIR__ . '/includes/auth.php';
$user = requireLogin();
$id = (int)($_GET['id'] ?? 0);

$st = db()->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ?');   // chỉ đơn của chính người dùng
$st->execute([$id, (int)$user['id']]);
$o = $st->fetch();
if (!$o) { flash('error', 'Không tìm thấy đơn hàng.'); redirect('purchase_history.php'); }

$qrFile = __DIR__ . '/payment/NapTienDi.jpg';
$hasQr = is_file($qrFile);

$pageTitle = 'Thanh toán đơn #' . $o['id'];
include __DIR__ . '/includes/header.php';
?>
<p class="crumb"><a href="<?= url('index.php') ?>">Trang chủ</a> / <a href="<?= url('purchase_history.php') ?>">Đơn của tôi</a> / Đơn #<?= (int)$o['id'] ?></p>
<h1 style="font-size:28px">Thanh toán đơn #<?= (int)$o['id'] ?></h1>
<div class="pay-box">
  <section class="card">
    <h2 style="font-size:18px;margin-bottom:14px">Thông tin đơn hàng</h2>
    <div class="sum"><span>Mã đơn hàng</span><span style="color:var(--ink)">#<?= (int)$o['id'] ?></span></div>
    <div class="sum"><span>Trạng thái đơn</span><span class="tag <?= e($o['status']) ?>"><?= e(ORDER_STATUS[$o['status']]) ?></span></div>
    <div class="sum"><span>Thanh toán</span><span class="tag <?= e($o['payment_status']) ?>"><?= e(PAYMENT_STATUS[$o['payment_status']]) ?></span></div>
    <div class="sum t"><span>Số tiền cần chuyển</span><span><?= money($o['total']) ?></span></div>
    <?php if ($o['status'] === 'cancelled'): ?>
      <div class="alert err">Đơn hàng đã bị hủy, không cần thanh toán.</div>
    <?php elseif ($o['payment_status'] === 'paid'): ?>
      <div class="alert ok">Quản trị viên đã xác nhận nhận được thanh toán cho đơn này.</div>
    <?php elseif ($o['payment_status'] === 'pending_verification'): ?>
      <div class="alert warn">Đơn đang chờ quản trị viên xác minh giao dịch của bạn.</div>
    <?php elseif ($o['payment_status'] === 'rejected'): ?>
      <div class="alert err">Thanh toán chưa được xác nhận. Vui lòng kiểm tra lại giao dịch và báo lại, hoặc liên hệ cửa hàng.</div>
    <?php else: ?>
      <div class="alert warn">Đơn hàng đang chờ thanh toán. Hệ thống chưa tự động nhận biết giao dịch do cần kết nối với ngân hàng hoặc cổng thanh toán.</div>
    <?php endif; ?>
    <div class="acts-row" style="margin-top:8px">
      <?php if ($o['status'] !== 'cancelled' && in_array($o['payment_status'], ['unpaid', 'rejected'], true)): ?>
        <form action="<?= url('actions/confirm_transfer.php') ?>" method="post" data-confirm="Bạn đã chuyển khoản đúng số tiền cho đơn này?">
          <?= csrf_field() ?><input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
          <button class="btn" type="submit">Tôi đã chuyển khoản</button>
        </form>
      <?php endif; ?>
      <a class="btn ghost" href="<?= url('purchase_history.php?id=' . (int)$o['id']) ?>">Xem chi tiết đơn</a>
      <a class="btn ghost" href="<?= url('purchase_history.php') ?>">Lịch sử mua hàng</a>
    </div>
  </section>
  <section class="card">
    <h2 style="font-size:18px;margin-bottom:14px">Quét mã QR để chuyển khoản</h2>
    <?php if ($hasQr): ?>
      <div class="qr"><img src="<?= url('payment/NapTienDi.jpg') ?>?v=<?= (int)filemtime($qrFile) ?>" alt="Mã QR thanh toán"></div>
    <?php else: ?>
      <div class="qr-missing">Chưa có ảnh QR thanh toán. Vui lòng bổ sung file payment/NapTienDi.jpg.</div>
    <?php endif; ?>
    <ol class="steps">
      <li>Mở ứng dụng ngân hàng và quét mã QR.</li>
      <li>Chuyển đúng số tiền <b style="color:var(--ink)"><?= money($o['total']) ?></b>.</li>
      <li>Ghi nội dung chuyển khoản: <b style="color:var(--ink)">DON <?= (int)$o['id'] ?></b>.</li>
      <li>Nhấn “Tôi đã chuyển khoản” để báo cho cửa hàng kiểm tra.</li>
    </ol>
  </section>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

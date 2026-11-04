<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/payos.php';
$user = requireLogin();
$id = (int)($_GET['id'] ?? 0);

$st = db()->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ?');   // chỉ đơn của chính người dùng
$st->execute([$id, (int)$user['id']]);
$o = $st->fetch();
if (!$o) { flash('error', 'Không tìm thấy đơn hàng.'); redirect('purchase_history.php'); }
if ($o['payment_status'] === 'paid') redirect('purchase_history.php?id=' . $id);

$qrFile = __DIR__ . '/payment/NapTienDi.jpg';
$hasQr = is_file($qrFile);
$payosWasCancelled = strtolower((string)($_GET['cancel'] ?? '')) === 'true'
    || strtoupper((string)($_GET['status'] ?? '')) === 'CANCELLED';

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
    <div class="sum"><span>Thanh toán</span><span id="payment-status-badge" class="tag <?= e($o['payment_status']) ?>"><?= e(PAYMENT_STATUS[$o['payment_status']]) ?></span></div>
    <div class="sum"><span>Phương thức</span><span><?= e(['cash' => 'Tiền mặt khi nhận', 'bank_transfer' => 'Chuyển khoản ngân hàng', 'payos' => 'Chuyển khoản ngân hàng'][($o['payment_method'] ?? 'bank_transfer')] ?? 'Chuyển khoản ngân hàng') ?></span></div>
    <?php $totalsOrder = $o; include __DIR__ . '/includes/order_totals.php'; ?>
    <div class="sum t"><span><?= ($o['payment_method'] ?? '') === 'cash' ? 'Tổng tiền đơn hàng' : 'Số tiền cần thanh toán' ?></span><span><?= money($o['total']) ?></span></div>
    <div id="payment-state-message">
    <?php if ($o['status'] === 'cancelled'): ?>
      <div class="alert err">Đơn hàng đã bị hủy, không cần thanh toán.</div>
    <?php elseif ($o['payment_status'] === 'paid'): ?>
      <div class="alert ok">Quản trị viên đã xác nhận nhận được thanh toán cho đơn này.</div>
    <?php elseif (($o['payment_method'] ?? '') === 'cash'): ?>
      <div class="alert warn">Bạn thanh toán tiền mặt khi nhận hàng. Cửa hàng sẽ xác nhận thanh toán sau khi thu tiền.</div>
    <?php elseif ($o['payment_status'] === 'pending_verification'): ?>
      <div class="alert warn">Đơn đang chờ quản trị viên xác minh giao dịch của bạn.</div>
    <?php elseif ($o['payment_status'] === 'rejected'): ?>
      <div class="alert err">Thanh toán chưa được xác nhận. Vui lòng kiểm tra lại giao dịch và báo lại, hoặc liên hệ cửa hàng.</div>
    <?php else: ?>
      <div class="alert warn"><?= ($o['payment_method'] ?? '') === 'payos' ? 'Đơn hàng đang chờ bạn chuyển khoản.' : 'Đơn hàng chờ chuyển khoản. Quản trị viên sẽ xác nhận sau khi kiểm tra giao dịch.' ?></div>
    <?php endif; ?>
    </div>
    <div class="acts-row" style="margin-top:8px">
      <?php if ($o['status'] !== 'cancelled' && ($o['payment_method'] ?? 'bank_transfer') === 'bank_transfer' && in_array($o['payment_status'], ['unpaid', 'rejected'], true)): ?>
        <form action="<?= url('actions/confirm_transfer.php') ?>" method="post" data-confirm="Bạn đã chuyển khoản đúng số tiền cho đơn này?">
          <?= csrf_field() ?><input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
          <button class="btn" type="submit">Tôi đã chuyển khoản</button>
        </form>
      <?php endif; ?>
      <a class="btn ghost" href="<?= url('purchase_history.php?id=' . (int)$o['id']) ?>">Xem chi tiết đơn</a>
      <a class="btn ghost" href="<?= url('purchase_history.php') ?>">Lịch sử mua hàng</a>
    </div>
  </section>
  <?php if (($o['payment_method'] ?? '') === 'payos' && $o['status'] !== 'cancelled' && $o['payment_status'] !== 'paid'): ?><section class="card">
    <h2 style="font-size:18px;margin-bottom:6px">Chuyển khoản ngân hàng</h2>
    <p class="hint" style="margin:0 0 14px">Đối chiếu số tiền và nội dung bên dưới trước khi xác nhận trong ứng dụng ngân hàng.</p>
    <div class="transfer-details">
      <div><span>Số tiền chuyển</span><strong><?= money($o['total']) ?></strong></div>
      <div><span>Nội dung chuyển khoản</span><strong><?= e('FS' . (int)$o['id']) ?></strong></div>
    </div>
    <?php if (!empty($o['payos_checkout_url']) && !$payosWasCancelled): ?>
      <?php $payosReturnUrl = payos_config()['base_url'] . '/payment.php?id=' . (int)$o['id']; ?>
      <div id="payos-checkout-embed" class="payos-embed" style="height:380px;min-height:380px"></div>
      <p id="payos-embed-note" class="payos-embed-note">Đang tải mã QR…</p>
      <div id="payment-success" class="payment-success hidden" role="status" aria-live="polite">
        <div class="payment-success-mark" aria-hidden="true">✓</div>
        <h3>Thanh toán thành công</h3>
        <p>Fish Shop đã xác nhận khoản thanh toán cho đơn hàng của bạn.</p>
        <p class="payment-countdown">Đang chuyển đến chi tiết đơn sau <strong id="payment-countdown">3</strong> giây</p>
        <a class="btn" href="<?= url('purchase_history.php?id=' . (int)$o['id']) ?>">Xem chi tiết đơn ngay</a>
      </div>
      <p id="payos-external-link" class="payos-embed-note"><a class="link-btn" href="<?= e($o['payos_checkout_url']) ?>" target="_blank" rel="noopener noreferrer">Mở trang thanh toán riêng nếu khung QR không tải</a></p>
      <form id="payos-regenerate-form" class="hidden" action="<?= url('actions/payos_create.php') ?>" method="post" style="margin-top:14px">
        <?= csrf_field() ?><input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>"><input type="hidden" name="regenerate" value="1">
        <button class="btn block" type="submit">Tạo mã QR mới</button>
      </form>
      <script src="https://cdn.payos.vn/payos-checkout/v1/stable/payos-initialize.js"></script>
      <script>
        (() => {
          const note = document.getElementById('payos-embed-note');
          const regenerate = document.getElementById('payos-regenerate-form');
          const statusUrl = <?= json_encode(url('actions/payment_status.php?id=' . (int)$o['id']), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
          const orderUrl = <?= json_encode(url('purchase_history.php?id=' . (int)$o['id']), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
          let redirecting = false;
          let checking = false;
          let attempts = 0;
          let stopped = false;
          let pollTimer = null;
          const maxAttempts = 36;

          function showConfirmedSuccess() {
            if (redirecting) return;
            redirecting = true;
            window.clearTimeout(pollTimer);
            document.getElementById('payos-checkout-embed').classList.add('hidden');
            note.classList.add('hidden');
            regenerate.classList.add('hidden');
            document.getElementById('payos-external-link').classList.add('hidden');
            document.getElementById('payment-success').classList.remove('hidden');
            const badge = document.getElementById('payment-status-badge');
            badge.className = 'tag paid';
            badge.textContent = 'Đã thanh toán';
            const message = document.getElementById('payment-state-message');
            message.className = 'alert ok';
            message.textContent = 'Đơn hàng đã được xác nhận thanh toán.';
            const counter = document.getElementById('payment-countdown');
            let seconds = 3;
            counter.textContent = String(seconds);
            const countdown = window.setInterval(() => {
              seconds -= 1;
              counter.textContent = String(seconds);
              if (seconds <= 0) {
                window.clearInterval(countdown);
                window.location.replace(orderUrl);
              }
            }, 1000);
          }

          async function checkPaymentStatus() {
            if (redirecting || checking || stopped) return;
            window.clearTimeout(pollTimer);
            checking = true;
            attempts += 1;
            const controller = new AbortController();
            const timeout = window.setTimeout(() => controller.abort(), 30000);
            try {
              const response = await fetch(statusUrl, {headers: {'Accept': 'application/json'}, credentials: 'same-origin', cache: 'no-store', signal: controller.signal});
              if (response.status === 401) {
                stopped = true;
                note.textContent = 'Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại để xem trạng thái đơn hàng.';
                return;
              }
              const result = await response.json();
              if (response.ok && result.ok === true && result.payment_status === 'paid') showConfirmedSuccess();
            } catch (error) {
              // A browser callback or temporary network failure never confirms payment.
            } finally {
              window.clearTimeout(timeout);
              checking = false;
              if (!redirecting && !stopped) {
                if (attempts < maxAttempts) pollTimer = window.setTimeout(checkPaymentStatus, 5000);
                else {
                  stopped = true;
                  note.textContent = 'Chưa nhận được xác nhận thanh toán. Bạn có thể mở chi tiết đơn hoặc tải lại trang để kiểm tra tiếp.';
                }
              }
            }
          }

          if (!window.PayOSCheckout) {
            note.textContent = 'Không tải được khung QR. Dùng liên kết mở trang thanh toán riêng bên dưới.';
          } else try {
            const checkout = window.PayOSCheckout.usePayOS({
              RETURN_URL: <?= json_encode($payosReturnUrl, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
              ELEMENT_ID: 'payos-checkout-embed',
              CHECKOUT_URL: <?= json_encode($o['payos_checkout_url'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
              embedded: true,
              onSuccess: () => {
                if (redirecting || stopped) return;
                note.textContent = 'Đã nhận tín hiệu từ ngân hàng. Đang xác minh thanh toán…';
                checkPaymentStatus();
              },
              onCancel: () => { if (!redirecting) { note.textContent = 'Đã hủy bước thanh toán. Bạn có thể tạo mã QR mới bên dưới.'; regenerate.classList.remove('hidden'); } },
              onExit: () => { if (!redirecting) { note.textContent = 'Bạn đã đóng khung thanh toán. Có thể tạo mã QR mới nếu muốn.'; regenerate.classList.remove('hidden'); } }
            });
            checkout.open();
            const fitFrame = () => document.querySelectorAll('#payos-checkout-embed iframe').forEach(frame => {
              frame.style.setProperty('height', '380px', 'important');
              frame.style.setProperty('min-height', '380px', 'important');
              frame.style.setProperty('width', '100%', 'important');
            });
            new MutationObserver(fitFrame).observe(document.getElementById('payos-checkout-embed'), {childList: true, subtree: true});
            window.setTimeout(fitFrame, 100);
            window.setTimeout(fitFrame, 500);
          } catch (error) { note.textContent = 'Không tải được khung QR. Dùng liên kết mở trang thanh toán riêng bên dưới.'; }
          checkPaymentStatus();
        })();
      </script>
    <?php elseif ($payosWasCancelled): ?>
      <div class="alert warn">Bạn đã hủy thanh toán. Tạo mã QR mới để tiếp tục cho đơn này.</div>
      <form action="<?= url('actions/payos_create.php') ?>" method="post" style="margin-top:14px">
        <?= csrf_field() ?><input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>"><input type="hidden" name="regenerate" value="1">
        <button class="btn block" type="submit">Tạo mã QR mới</button>
      </form>
    <?php else: ?>
      <div class="qr-missing">Chưa tạo mã QR cho đơn này.</div>
      <form action="<?= url('actions/payos_create.php') ?>" method="post" style="margin-top:14px">
        <?= csrf_field() ?><input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
        <button class="btn block" type="submit">Tạo mã QR chuyển khoản</button>
      </form>
    <?php endif; ?>
  </section>
  <?php elseif (($o['payment_method'] ?? 'bank_transfer') === 'bank_transfer' && $o['status'] !== 'cancelled' && $o['payment_status'] !== 'paid'): ?><section class="card">
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
  </section><?php endif; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

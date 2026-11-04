<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/maps.php';
require_once __DIR__ . '/includes/vouchers.php';
require_once __DIR__ . '/includes/rewards.php';
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

$subtotal = (int)$total;
$rewardQuote = prize_cart_quote(db(), (int)$user['id'], $lines, $_SESSION);
$prizeDiscount = $rewardQuote['coupon_discount'];
$prizeCouponUnits = $rewardQuote['coupon_units'];
$prizeProductId = $rewardQuote['coupon_product_id'];
$prizeRewardId = $rewardQuote['coupon_reward_id'];
$fishDiscount = $rewardQuote['fish_discount'];
$fishRewardId = $rewardQuote['fish_reward_id'];
$fishProductId = $rewardQuote['fish_product_id'];
if (!$prizeRewardId) unset($_SESSION['prize_coupon_reward'], $_SESSION['prize_coupon_product']);
$draft = ['phone' => (string)$user['phone'], 'shipping_address' => (string)$user['address'],
    'shipping_latitude' => '', 'shipping_longitude' => '', 'payment_method' => 'cash'];
if (is_array($_SESSION['checkout_draft'] ?? null)) {
    foreach ($draft as $key => $value) {
        if (is_string($_SESSION['checkout_draft'][$key] ?? null)) $draft[$key] = $_SESSION['checkout_draft'][$key];
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) { flash('error', 'Phiên làm việc hết hạn. Vui lòng thử lại.'); redirect('checkout.php'); }
    foreach (['phone' => 25, 'shipping_address' => 255, 'shipping_latitude' => 40, 'shipping_longitude' => 40, 'payment_method' => 20] as $key => $max) {
        if (isset($_POST[$key]) && is_string($_POST[$key])) $draft[$key] = mb_substr($_POST[$key], 0, $max);
    }
    $_SESSION['checkout_draft'] = $draft;
    $action = post_string('voucher_action');
    if ($action === 'remove') unset($_SESSION['checkout_voucher']);
    elseif ($action === 'apply') {
        $code = substr(voucher_normalize_code(post_string('voucher_code')), 0, 64);
        if ($code === '') unset($_SESSION['checkout_voucher']);
        else $_SESSION['checkout_voucher'] = $code;
    }
    redirect('checkout.php');
}
$selectedCode = is_string($_SESSION['checkout_voucher'] ?? null) ? $_SESSION['checkout_voucher'] : '';
$voucherReady = voucher_schema_ready(db());
$voucherError = '';
$discount = 0;
$total = max(1, $subtotal - $prizeDiscount - $fishDiscount);
try {
    $quote = voucher_quote(db(), $selectedCode, (int)$user['id'], $subtotal);
    $discount = $quote['discount'];
    $total = max(1, $quote['total'] - $prizeDiscount - $fishDiscount);
} catch (DomainException $ex) {
    $voucherError = $ex->getMessage();
}
$transferAllowed = $total >= 2000 && $total <= 500000000;
if (!in_array($draft['payment_method'], ['cash', 'payos'], true) || (!$transferAllowed && $draft['payment_method'] === 'payos')) $draft['payment_method'] = 'cash';
try { $draftPoint = shipping_location_from_values($draft['shipping_latitude'], $draft['shipping_longitude']); }
catch (DomainException $ex) { $draftPoint = null; $draft['shipping_latitude'] = ''; $draft['shipping_longitude'] = ''; }
$pageTitle = 'Xác nhận mua hàng';
$mapConfig = fishshop_maps_config();
$mapCenter = $draftPoint ?? fishshop_store_point($mapConfig);
include __DIR__ . '/includes/header.php';
?>
<p class="crumb"><a href="<?= url('index.php') ?>">Trang chủ</a> / <a href="<?= url('cart.php') ?>">Giỏ hàng</a> / Xác nhận mua hàng</p>
<h1 style="font-size:28px">Xác nhận mua hàng</h1>
<div class="cart">
  <section class="card">
    <h2>Thông tin nhận hàng</h2>
    <form id="checkout-shipping-form" action="<?= url('actions/place_order.php') ?>" method="post" class="js-validate" novalidate>
      <?= csrf_field() ?>
      <div class="field"><label for="phone">Số điện thoại nhận hàng</label>
        <input id="phone" name="phone" type="text" inputmode="numeric" data-rule="phone" value="<?= e($draft['phone']) ?>" required></div>
      <div class="field"><label for="shipping_address">Địa chỉ giao hàng</label>
        <textarea id="shipping_address" name="shipping_address" data-rule="address" required><?= e($draft['shipping_address']) ?></textarea>
        <small>Kiểm tra kỹ địa chỉ và số điện thoại trước khi đặt.</small></div>
      <section class="delivery-location" data-map-widget="delivery"
          data-latitude="<?= e($mapCenter['latitude'] ?? '16.0') ?>"
          data-longitude="<?= e($mapCenter['longitude'] ?? '107.0') ?>"
          data-zoom="<?= $draftPoint ? '16' : ($mapCenter ? '14' : '5') ?>" aria-labelledby="delivery-location-title">
        <div class="map-section-heading"><h3 id="delivery-location-title">Điểm giao hàng trên bản đồ</h3><span>Tùy chọn</span></div>
        <p class="map-hint">Chạm lên bản đồ hoặc kéo ghim đến nơi nhận hàng. Nhớ nhập đủ số nhà, tên đường ở phía trên.</p>
        <input type="hidden" name="shipping_latitude" id="shipping_latitude" value="<?= e($draft['shipping_latitude']) ?>">
        <input type="hidden" name="shipping_longitude" id="shipping_longitude" value="<?= e($draft['shipping_longitude']) ?>">
        <div class="fishshop-map delivery-map" data-map-canvas role="region" aria-label="Bản đồ chọn điểm giao hàng" tabindex="0"></div>
        <div class="map-actions">
          <button class="btn ghost sm" type="button" data-map-locate>Vị trí của tôi</button>
          <button class="btn ghost sm" type="button" data-map-center>Ghim tại tâm bản đồ</button>
          <button class="map-clear" type="button" data-map-clear disabled>Bỏ ghim</button>
        </div>
        <p class="map-selection" data-map-status role="status" aria-live="polite">Chưa chọn điểm giao hàng.</p>
        <p class="map-error" data-map-error role="status" hidden></p>
        <a class="map-selected-link" data-map-selected-link target="_blank" rel="noopener noreferrer" hidden>Xem điểm đã chọn</a>
        <noscript><p class="map-hint">Bật JavaScript để chọn điểm trên bản đồ. Bạn vẫn có thể đặt hàng bằng địa chỉ đã nhập.</p></noscript>
      </section>
      <fieldset class="field" style="border:0;padding:0;margin:0 0 16px">
        <legend style="font-size:13px;font-weight:600;margin-bottom:8px">Phương thức thanh toán</legend>
        <div class="payment-options">
          <label class="payment-option">
            <input type="radio" name="payment_method" value="cash" required <?= $draft['payment_method'] === 'cash' ? 'checked' : '' ?>>
            <span class="payment-option-copy"><b>Tiền mặt khi nhận hàng</b><small>Thanh toán trực tiếp cho nhân viên giao hàng.</small></span>
          </label>
          <label class="payment-option">
            <input type="radio" name="payment_method" value="payos" required <?= $draft['payment_method'] === 'payos' ? 'checked' : '' ?> <?= !$transferAllowed ? 'disabled' : '' ?>>
            <span class="payment-option-copy"><b>Chuyển khoản ngân hàng</b><small>Quét mã QR; số tiền và nội dung đơn hàng sẽ được điền sẵn, hệ thống tự xác nhận.</small></span>
            <span class="payment-badge">QR</span>
          </label>
        </div>
        <?php if (!$transferAllowed): ?><p class="hint">Chuyển khoản áp dụng cho số tiền từ 2.000 đ đến 500.000.000 đ. Bạn có thể chọn tiền mặt cho đơn này.</p><?php endif; ?>
      </fieldset>
      <input type="hidden" name="prize_reward_id" value="<?= $prizeRewardId ?>"><input type="hidden" name="prize_product_id" value="<?= $prizeProductId ?>"><input type="hidden" name="fish_reward_id" value="<?= $fishRewardId ?>"><input type="hidden" name="fish_product_id" value="<?= $fishProductId ?>">
      <button class="btn" type="submit" <?= $voucherError !== '' ? 'disabled' : '' ?>>Đặt hàng</button>
      <a class="btn ghost" href="<?= url('cart.php') ?>">Quay lại giỏ hàng</a>
    </form>
  </section>
  <aside class="order-summary">
    <h2>Đơn hàng (<?= count($lines) ?> sản phẩm)</h2>
    <?php foreach ($lines as $l): ?>
      <div class="sum"><span><?= e($l['name']) ?> × <?= (int)$l['quantity'] ?></span><span><?= money((int)$l['quantity'] * (float)$l['price']) ?></span></div>
    <?php endforeach; ?>
    <div class="voucher-box">
      <?php if ($voucherReady || $selectedCode !== ''): ?>
      <form method="post" action="<?= url('checkout.php') ?>" data-voucher-form>
        <?= csrf_field() ?><input type="hidden" name="voucher_action" value="apply">
        <?php foreach ($draft as $key => $value): ?><input type="hidden" name="<?= e($key) ?>" data-draft="<?= e($key) ?>" value="<?= e($value) ?>"><?php endforeach; ?>
        <label for="checkout-voucher">Mã giảm giá</label>
        <div class="voucher-input-row"><input id="checkout-voucher" name="voucher_code" type="text" value="<?= e($selectedCode) ?>" maxlength="32" placeholder="Nhập mã ưu đãi" autocomplete="off"><button class="btn ghost sm" type="submit">Áp dụng</button></div>
      </form>
      <?php if ($voucherError !== ''): ?><p class="voucher-message error" role="alert"><?= e($voucherError) ?> Hãy đổi mã hoặc bỏ mã để đặt hàng.</p>
      <?php elseif ($discount > 0): ?><p class="voucher-message">Đã áp dụng <?= e($selectedCode) ?>, giảm <?= money($discount) ?>.</p><?php endif; ?>
      <?php if ($selectedCode !== ''): ?><form method="post" action="<?= url('checkout.php') ?>" data-voucher-form><?= csrf_field() ?><input type="hidden" name="voucher_action" value="remove"><?php foreach ($draft as $key => $value): ?><input type="hidden" name="<?= e($key) ?>" data-draft="<?= e($key) ?>" value="<?= e($value) ?>"><?php endforeach; ?><button class="voucher-remove" type="submit">Bỏ mã giảm giá</button></form><?php endif; ?>
      <?php else: ?><p class="hint">Mã giảm giá hiện chưa được bật.</p><?php endif; ?>
    </div>
    <div class="sum"><span>Tạm tính</span><span><?= money($subtotal) ?></span></div>
    <?php if ($discount > 0): ?><div class="sum"><span>Giảm giá</span><span class="voucher-discount">−<?= money($discount) ?></span></div><?php endif; ?>
    <?php if ($prizeDiscount > 0): ?><div class="sum"><span>Phiếu vòng quay giảm 5% (<?= $prizeCouponUnits ?> sản phẩm)</span><span class="voucher-discount">−<?= money($prizeDiscount) ?></span></div><?php endif; ?>
    <?php if ($fishDiscount > 0): ?><div class="sum"><span>Cá thưởng</span><span class="voucher-discount">−<?= money($fishDiscount) ?></span></div><?php endif; ?>
    <div class="sum t"><span>Tổng cộng</span><span><?= money($total) ?></span></div>
    <p class="hint">Tổng tiền cuối cùng do máy chủ tính lại từ giá hiện tại khi bạn đặt hàng.</p>
  </aside>
</div>
<script src="<?= url('assets/js/vouchers.js') ?>?v=<?= filemtime(__DIR__ . '/assets/js/vouchers.js') ?>" defer></script>
<?php include __DIR__ . '/includes/footer.php'; ?>

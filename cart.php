<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/rewards.php';
$user = current_user();
$lines = current_cart_lines();

$subtotal = 0; $problem = false;
foreach ($lines as &$l) {
    $l['bad'] = '';
    if ($l['status'] !== 'active') $l['bad'] = 'Sản phẩm đã ngừng bán, hãy xóa khỏi giỏ.';
    elseif ((int)$l['stock'] < 1) $l['bad'] = 'Đã hết hàng, hãy xóa khỏi giỏ.';
    elseif ((int)$l['quantity'] > (int)$l['stock']) $l['bad'] = 'Kho chỉ còn ' . (int)$l['stock'] . ' sản phẩm, hãy giảm số lượng.';
    if ($l['bad']) $problem = true;
    $subtotal += (int)$l['quantity'] * (float)$l['price'];
}
unset($l);

$rewardQuote = prize_cart_quote(db(), (int)($user['id'] ?? 0), $lines, $_SESSION);
$rewardDiscount = $rewardQuote['coupon_discount'] + $rewardQuote['fish_discount'];
$total = $lines ? max(1, (int)$subtotal - $rewardDiscount) : 0;

$pageTitle = 'Giỏ hàng';
include __DIR__ . '/includes/header.php';
?>
<p class="crumb"><a href="<?= url('index.php') ?>">Trang chủ</a> / Giỏ hàng</p>
<h1 style="font-size:28px;margin-bottom:6px">Giỏ hàng</h1>

<?php if (!$lines): ?>
  <div class="empty" style="margin-top:22px">
    <h2>Giỏ hàng của bạn đang trống</h2>
    <p>Hãy chọn vài con cá tươi để bắt đầu.</p>
    <a class="btn" href="<?= url('index.php') ?>">Tiếp tục mua sắm</a>
  </div>
<?php else: ?>
<div class="cart">
  <section class="card" aria-label="Sản phẩm trong giỏ">
    <h2><?= count($lines) ?> sản phẩm</h2>
    <?php foreach ($lines as $l): ?>
      <div class="row">
        <a class="im" href="<?= url('product_detail.php?id=' . (int)$l['product_id']) ?>"><img src="<?= e(file_url($l['image'])) ?>" alt="<?= e($l['name']) ?>"></a>
        <div>
          <b><?= e($l['name']) ?></b>
          <div class="rt">Đơn giá: <?= money($l['price']) ?></div>
          <?php if ($l['bad']): ?><div class="rt bad"><?= e($l['bad']) ?></div><?php endif; ?>
        </div>
        <?php
          $lineDiscount = (int)$l['product_id'] === $rewardQuote['coupon_product_id'] ? $rewardQuote['coupon_discount'] : 0;
          if ((int)$l['product_id'] === $rewardQuote['fish_product_id']) $lineDiscount += $rewardQuote['fish_discount'];
          $lineSubtotal = (int)$l['quantity'] * (float)$l['price'];
        ?>
        <div class="line-price">
          <?php if ($lineDiscount > 0): ?><del class="rt"><?= money($lineSubtotal) ?></del><br><?php endif; ?>
          <?= money($lineSubtotal - $lineDiscount) ?>
          <?php if ((int)$l['product_id'] === $rewardQuote['coupon_product_id']): ?><div class="rt">Đã giảm 5% cho <?= $rewardQuote['coupon_units'] ?> sản phẩm</div><?php endif; ?>
        </div>
        <div class="ctrl">
          <form class="js-autosubmit" action="<?= url('actions/update_cart.php') ?>" method="post" style="display:flex;gap:8px;align-items:center">
            <?= csrf_field() ?>
            <input type="hidden" name="product_id" value="<?= (int)$l['product_id'] ?>">
            <div class="qty">
              <button type="button" data-step="-1" aria-label="Giảm">−</button>
              <input type="number" name="quantity" value="<?= (int)$l['quantity'] ?>" min="1" max="<?= max(1, (int)$l['stock']) ?>" aria-label="Số lượng <?= e($l['name']) ?>">
              <button type="button" data-step="1" aria-label="Tăng">+</button>
            </div>
            <button class="btn ghost sm noscript-btn" type="submit">Cập nhật</button>
          </form>
          <form action="<?= url('actions/remove_from_cart.php') ?>" method="post" data-confirm="Xóa “<?= e($l['name']) ?>” khỏi giỏ hàng?">
            <?= csrf_field() ?>
            <input type="hidden" name="product_id" value="<?= (int)$l['product_id'] ?>">
            <button class="link-btn" type="submit">Xóa</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </section>

  <aside class="order-summary">
    <h2>Tóm tắt đơn hàng</h2>
    <div class="sum"><span>Tạm tính</span><span><?= money($subtotal) ?></span></div>
    <?php if ($rewardQuote['coupon_discount'] > 0): ?><div class="sum"><span>Phiếu vòng quay giảm 5% (<?= $rewardQuote['coupon_units'] ?> sản phẩm)</span><span style="color:var(--teal)">−<?= money($rewardQuote['coupon_discount']) ?></span></div><?php endif; ?>
    <?php if ($rewardQuote['fish_discount'] > 0): ?><div class="sum"><span>Cá thưởng</span><span style="color:var(--teal)">−<?= money($rewardQuote['fish_discount']) ?></span></div><?php endif; ?>
    <div class="sum t"><span>Tổng cộng</span><span><?= money($total) ?></span></div>
    <?php if ($rewardDiscount > 0): ?><p class="hint">Đã tính phần thưởng đang chọn. Phiếu được trừ khỏi Kho khi đặt hàng thành công.</p><?php endif; ?>
    <?php if ($problem): ?>
      <div class="alert warn">Hãy xử lý các sản phẩm được báo đỏ trước khi mua hàng.</div>
      <span class="btn block disabled" aria-disabled="true">Tiến hành mua hàng</span>
    <?php else: ?>
      <a class="btn block" href="<?= url('checkout.php') ?>"><?= $user ? 'Tiến hành mua hàng' : 'Đăng nhập để đặt hàng' ?></a>
      <?php if (!$user): ?><p class="hint">Sản phẩm trong giỏ sẽ được giữ lại sau khi đăng nhập.</p><?php endif; ?>
    <?php endif; ?>
    <a class="btn ghost block" style="margin-top:10px" href="<?= url('index.php') ?>">Tiếp tục mua sắm</a>
  </aside>
</div>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>

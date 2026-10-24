<?php
require_once __DIR__ . '/includes/auth.php';
$user  = requireLogin();
$lines = cart_lines((int)$user['id']);

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
        <div class="line-price"><?= money((int)$l['quantity'] * (float)$l['price']) ?></div>
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
    <div class="sum"><span>Phí giao hàng</span><span>Xác nhận khi giao</span></div>
    <div class="sum t"><span>Tổng cộng</span><span><?= money($subtotal) ?></span></div>
    <?php if ($problem): ?>
      <div class="alert warn">Hãy xử lý các sản phẩm được báo đỏ trước khi mua hàng.</div>
      <span class="btn block disabled" aria-disabled="true">Tiến hành mua hàng</span>
    <?php else: ?>
      <a class="btn block" href="<?= url('checkout.php') ?>">Tiến hành mua hàng</a>
    <?php endif; ?>
    <a class="btn ghost block" style="margin-top:10px" href="<?= url('index.php') ?>">Tiếp tục mua sắm</a>
  </aside>
</div>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>

<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);
$st = db()->prepare("SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON c.id = p.category_id
                     WHERE p.id = ? AND p.status = 'active' AND c.status = 'active'");
$st->execute([$id]);
$p = $st->fetch();
if (!$p) { flash('error', 'Sản phẩm không tồn tại hoặc đã ngừng bán.'); redirect('index.php'); }

$out = (int)$p['stock'] <= 0;
$pageTitle = $p['name'];
include __DIR__ . '/includes/header.php';
?>
<p class="crumb"><a href="<?= url('index.php') ?>">Trang chủ</a> / <a href="<?= url('index.php?cat=' . (int)$p['category_id']) ?>"><?= e($p['category_name']) ?></a> / <?= e($p['name']) ?></p>
<section class="detail">
  <div class="big"><img src="<?= e(file_url($p['image'])) ?>" alt="<?= e($p['name']) ?>"></div>
  <div class="info">
    <span class="cat" style="color:var(--teal);font-size:13px"><?= e($p['category_name']) ?></span>
    <h1><?= e($p['name']) ?></h1>
    <div class="price"><?= money($p['price']) ?></div>
    <p class="desc"><?= $p['description'] !== null && trim($p['description']) !== '' ? e($p['description']) : 'Chưa có mô tả cho sản phẩm này.' ?></p>
    <p><?= $out ? '<span class="tag cancelled">Hết hàng</span>' : '<span class="tag active">Còn ' . (int)$p['stock'] . ' trong kho</span>' ?></p>
    <form class="js-add-cart buy-row" action="<?= url('actions/add_to_cart.php') ?>" method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
      <input type="hidden" name="back" value="product_detail.php?id=<?= (int)$p['id'] ?>">
      <div class="qty">
        <button type="button" data-step="-1" aria-label="Giảm">−</button>
        <input type="number" name="quantity" value="1" min="1" max="<?= max(1, (int)$p['stock']) ?>" aria-label="Số lượng" <?= $out ? 'disabled' : '' ?>>
        <button type="button" data-step="1" aria-label="Tăng">+</button>
      </div>
      <button class="btn" type="submit" <?= $out ? 'disabled' : '' ?>>Thêm vào giỏ</button>
      <a class="btn ghost" href="<?= url('cart.php') ?>">Xem giỏ hàng</a>
    </form>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>

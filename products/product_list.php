<?php
// Phần hiển thị lưới sản phẩm. Cần biến $products (mảng lấy từ database) trước khi include.
if (!$products): ?>
  <div class="empty">
    <h2>Chưa có sản phẩm phù hợp</h2>
    <p>Cửa hàng chưa có cá nào, hoặc không có kết quả cho bộ lọc hiện tại.</p>
    <a class="btn ghost" href="<?= url('index.php') ?>">Xem tất cả</a>
  </div>
<?php else: ?>
  <div class="product-list">
  <?php foreach ($products as $p): $out = (int)$p['stock'] <= 0; ?>
    <article class="product">
      <a class="im" href="<?= url('product_detail.php?id=' . (int)$p['id']) ?>">
        <img src="<?= e(file_url($p['image'])) ?>" alt="<?= e($p['name']) ?>" loading="lazy">
      </a>
      <div class="body">
        <span class="cat"><?= e($p['category_name']) ?></span>
        <h3><a href="<?= url('product_detail.php?id=' . (int)$p['id']) ?>"><?= e($p['name']) ?></a></h3>
        <span class="stock <?= $out ? 'out' : '' ?>"><?= $out ? 'Hết hàng' : 'Còn ' . (int)$p['stock'] ?></span>
        <div class="price"><?= money($p['price']) ?></div>
        <div class="acts">
          <a class="btn ghost sm" href="<?= url('product_detail.php?id=' . (int)$p['id']) ?>">Xem chi tiết</a>
          <form class="js-add-cart" action="<?= url('actions/add_to_cart.php') ?>" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
            <input type="hidden" name="quantity" value="1">
            <input type="hidden" name="back" value="index.php">
            <button class="btn sm block" type="submit" <?= $out ? 'disabled' : '' ?>>Thêm vào giỏ</button>
          </form>
        </div>
      </div>
    </article>
  <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php $cur = basename($_SERVER['SCRIPT_NAME']); ?>
<nav class="shop-views admin-tabs" aria-label="Quản trị">
  <?php
  $tabs = ['index.php' => 'Tổng quan', 'products.php' => 'Sản phẩm', 'categories.php' => 'Danh mục', 'orders.php' => 'Đơn hàng'];
  foreach ($tabs as $file => $label):
      $active = ($cur === $file) || ($file === 'products.php' && in_array($cur, ['add_product.php', 'edit_product.php'], true));
  ?>
    <a class="pill <?= $active ? 'is-active' : '' ?>" href="<?= url('admin/' . $file) ?>"><?= e($label) ?></a>
  <?php endforeach; ?>
</nav>

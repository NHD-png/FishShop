<?php
$pageTitle = $pageTitle ?? 'Quản trị';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> | Fish Shop · Quản trị</title>
<link rel="stylesheet" href="<?= url('assets/css/style.css') ?>?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
<link rel="stylesheet" href="<?= url('assets/css/reference-theme.css') ?>?v=<?= filemtime(__DIR__ . '/../assets/css/reference-theme.css') ?>">
<link rel="stylesheet" href="<?= url('assets/css/vouchers.css') ?>?v=<?= filemtime(__DIR__ . '/../assets/css/vouchers.css') ?>">
<link rel="stylesheet" href="<?= url('assets/css/slideshow.css') ?>">
</head>
<body class="admin-area" data-base="<?= e(BASE_URL) ?>" data-csrf="<?= e(csrf_token()) ?>">
<div id="toasts" aria-live="polite">
<?php foreach (take_flash() as $f): ?>
  <div class="toast <?= $f['type'] === 'success' ? 'ok' : 'err' ?>" role="status"><?= e($f['msg']) ?></div>
<?php endforeach; ?>
</div>
<div class="admin-shell">
  <aside class="admin-sidebar">
    <a class="admin-brand" href="<?= url('admin/index.php') ?>"><span class="admin-brand-mark">F</span><span>FISH SHOP<small>QUẢN TRỊ</small></span></a>
    <nav class="admin-side-nav" aria-label="Điều hướng quản trị">
      <a href="<?= url('admin/index.php') ?>" class="<?= basename($_SERVER['SCRIPT_NAME']) === 'index.php' ? 'active' : '' ?>">Tổng quan</a>
      <a href="<?= url('admin/products.php') ?>" class="<?= in_array(basename($_SERVER['SCRIPT_NAME']), ['products.php', 'add_product.php', 'edit_product.php'], true) ? 'active' : '' ?>">Sản phẩm</a>
      <a href="<?= url('admin/categories.php') ?>" class="<?= basename($_SERVER['SCRIPT_NAME']) === 'categories.php' ? 'active' : '' ?>">Danh mục</a>
      <a href="<?= url('admin/customers.php') ?>" class="<?= in_array(basename($_SERVER['SCRIPT_NAME']), ['customers.php', 'customer_detail.php'], true) ? 'active' : '' ?>">Khách hàng</a>
      <a href="<?= url('admin/orders.php') ?>" class="<?= basename($_SERVER['SCRIPT_NAME']) === 'orders.php' ? 'active' : '' ?>">Đơn hàng</a>
      <a href="<?= BASE_URL ?>admin/vouchers.php" class="<?= basename($_SERVER['SCRIPT_NAME']) === 'vouchers.php' ? 'active' : '' ?>">Mã giảm giá</a>
      <a href="<?= url('admin/wallet_deposits.php') ?>" class="<?= basename($_SERVER['SCRIPT_NAME']) === 'wallet_deposits.php' ? 'active' : '' ?>">Nạp ví</a>
    </nav>
    <div class="admin-side-bottom">
      <form action="<?= url('admin/logout.php') ?>" method="post"><?= csrf_field() ?><button type="submit">Đăng xuất</button></form>
    </div>
  </aside>
  <main class="admin-main wrap">

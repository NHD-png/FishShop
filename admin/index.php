<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
$one = fn(string $sql) => (int)db()->query($sql)->fetchColumn();
$stats = [
    'Sản phẩm đang bán' => $one("SELECT COUNT(*) FROM products WHERE status='active'"),
    'Đơn chờ xác nhận' => $one("SELECT COUNT(*) FROM orders WHERE status='pending'"),
    'Thanh toán chờ xác minh' => $one("SELECT COUNT(*) FROM orders WHERE payment_status='pending_verification' AND status<>'cancelled'"),
    'Khách hàng' => $one("SELECT COUNT(*) FROM users WHERE role='customer'"),
];
$pageTitle = 'Quản trị';
include __DIR__ . '/../includes/header.php';
?>
<section class="page-intro"><h1>Quản trị cửa hàng</h1><p>Quản lý sản phẩm, danh mục và đơn hàng.</p></section>
<?php include __DIR__ . '/../includes/admin_nav.php'; ?>
<div class="stat-grid">
  <?php foreach ($stats as $label => $n): ?><div class="stat"><b><?= $n ?></b><span><?= e($label) ?></span></div><?php endforeach; ?>
</div>
<div class="acts-row">
  <a class="btn" href="<?= url('admin/add_product.php') ?>">Thêm sản phẩm</a>
  <a class="btn ghost" href="<?= url('admin/orders.php?payment=pending_verification') ?>">Đơn chờ xác minh thanh toán</a>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>

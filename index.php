<?php
require_once __DIR__ . '/includes/auth.php';
$user = requireLogin();   // chưa đăng nhập -> chuyển về login.php

$q    = trim($_GET['q'] ?? '');
$cat  = (int)($_GET['cat'] ?? 0);
$sort = $_GET['sort'] ?? 'new';
$orderBy = ['new' => 'p.id DESC', 'price_asc' => 'p.price ASC', 'price_desc' => 'p.price DESC', 'name' => 'p.name ASC'];
if (!isset($orderBy[$sort])) $sort = 'new';

$cats = db()->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name")->fetchAll();

$sql = "SELECT p.id, p.name, p.price, p.image, p.stock, c.name AS category_name
        FROM products p JOIN categories c ON c.id = p.category_id
        WHERE p.status = 'active' AND c.status = 'active'";
$args = [];
if ($q !== '') { $sql .= ' AND p.name LIKE ?'; $args[] = '%' . addcslashes($q, '%_\\') . '%'; }
if ($cat > 0)  { $sql .= ' AND p.category_id = ?'; $args[] = $cat; }
$sql .= ' ORDER BY ' . $orderBy[$sort];
$st = db()->prepare($sql);
$st->execute($args);
$products = $st->fetchAll();

$pageTitle = 'Trang chủ';
include __DIR__ . '/includes/header.php';
?>
<section class="page-intro">
  <h1>Xin chào, <?= e($user['fullname']) ?></h1>
  <p>Chọn cá tươi theo danh mục, thêm vào giỏ và đặt hàng trong vài bước.</p>
</section>

<nav class="shop-views" aria-label="Danh mục cá">
  <?php $keep = http_build_query(array_filter(['q' => $q, 'sort' => $sort !== 'new' ? $sort : ''])); ?>
  <a class="pill <?= $cat === 0 ? 'is-active' : '' ?>" href="<?= url('index.php' . ($keep ? '?' . $keep : '')) ?>">Tất cả</a>
  <?php foreach ($cats as $c): ?>
    <a class="pill <?= $cat === (int)$c['id'] ? 'is-active' : '' ?>" href="<?= url('index.php?' . http_build_query(['cat' => $c['id']] + array_filter(['q' => $q, 'sort' => $sort !== 'new' ? $sort : '']))) ?>"><?= e($c['name']) ?></a>
  <?php endforeach; ?>
</nav>

<div class="tool">
  <span><?= count($products) ?> sản phẩm<?= $q !== '' ? ' cho “' . e($q) . '”' : '' ?></span>
  <form method="get" action="<?= url('index.php') ?>">
    <?php if ($q !== ''): ?><input type="hidden" name="q" value="<?= e($q) ?>"><?php endif; ?>
    <?php if ($cat): ?><input type="hidden" name="cat" value="<?= $cat ?>"><?php endif; ?>
    <label for="sort" class="mute">Sắp xếp</label>
    <select id="sort" name="sort" onchange="this.form.submit()">
      <option value="new" <?= $sort === 'new' ? 'selected' : '' ?>>Mới nhất</option>
      <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Giá tăng dần</option>
      <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Giá giảm dần</option>
      <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Tên A-Z</option>
    </select>
    <noscript><button class="btn sm" type="submit">Áp dụng</button></noscript>
  </form>
</div>

<?php include __DIR__ . '/products/product_list.php'; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>

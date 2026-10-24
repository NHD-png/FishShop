<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requirePost('admin/products.php');
    $id = (int)($_POST['id'] ?? 0);
    $to = ($_POST['to'] ?? '') === 'active' ? 'active' : 'inactive';
    db()->prepare('UPDATE products SET status = ? WHERE id = ?')->execute([$to, $id]);
    flash('success', $to === 'active' ? 'Đã mở bán lại sản phẩm.' : 'Đã ngừng bán sản phẩm.');
    redirect('admin/products.php');
}
$products = db()->query('SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON c.id = p.category_id ORDER BY p.id DESC')->fetchAll();

$pageTitle = 'Quản lý sản phẩm';
include __DIR__ . '/../includes/header.php';
?>
<section class="page-intro"><h1>Sản phẩm</h1><p>Thêm, sửa và ngừng bán cá. Sản phẩm đã có đơn hàng không bị xóa để giữ lịch sử mua.</p></section>
<?php include __DIR__ . '/../includes/admin_nav.php'; ?>
<p style="margin-bottom:16px"><a class="btn" href="<?= url('admin/add_product.php') ?>">Thêm sản phẩm</a></p>
<?php if (!$products): ?>
  <div class="empty"><h2>Chưa có sản phẩm</h2><p>Hãy tạo danh mục, sau đó thêm sản phẩm đầu tiên.</p><a class="btn" href="<?= url('admin/add_product.php') ?>">Thêm sản phẩm</a></div>
<?php else: ?>
<div class="table-wrap"><table>
  <thead><tr><th>Ảnh</th><th>Tên</th><th>Danh mục</th><th class="num">Giá</th><th class="num">Tồn kho</th><th>Trạng thái</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($products as $p): ?>
    <tr>
      <td><img class="thumb" src="<?= e(file_url($p['image'])) ?>" alt=""></td>
      <td><?= e($p['name']) ?></td>
      <td><?= e($p['category_name']) ?></td>
      <td class="num"><?= money($p['price']) ?></td>
      <td class="num"><?= (int)$p['stock'] ?></td>
      <td><span class="tag <?= e($p['status']) ?>"><?= $p['status'] === 'active' ? 'Đang bán' : 'Ngừng bán' ?></span></td>
      <td><div class="acts-row">
        <a class="btn ghost sm" href="<?= url('admin/edit_product.php?id=' . (int)$p['id']) ?>">Sửa</a>
        <form method="post" data-confirm="<?= $p['status'] === 'active' ? 'Ngừng bán sản phẩm này?' : 'Mở bán lại sản phẩm này?' ?>">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
          <input type="hidden" name="to" value="<?= $p['status'] === 'active' ? 'inactive' : 'active' ?>">
          <button class="btn sm <?= $p['status'] === 'active' ? 'danger' : 'ghost' ?>" type="submit"><?= $p['status'] === 'active' ? 'Ngừng bán' : 'Mở bán' ?></button>
        </form>
      </div></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php endif; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>

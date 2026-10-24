<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requirePost('admin/categories.php');
    $id     = (int)($_POST['id'] ?? 0);
    $name   = trim($_POST['name'] ?? '');
    $desc   = trim($_POST['description'] ?? '');
    $status = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
    if ($name === '' || mb_strlen($name) > 100) {
        flash('error', 'Tên danh mục bắt buộc, tối đa 100 ký tự.');
    } else {
        try {
            if ($id > 0) {
                db()->prepare('UPDATE categories SET name=?, description=?, status=? WHERE id=?')->execute([$name, $desc, $status, $id]);
                flash('success', 'Đã cập nhật danh mục.');
            } else {
                db()->prepare('INSERT INTO categories (name, description, status) VALUES (?,?,?)')->execute([$name, $desc, $status]);
                flash('success', 'Đã thêm danh mục “' . $name . '”.');
            }
        } catch (PDOException $ex) {
            flash('error', $ex->getCode() === '23000' ? 'Tên danh mục đã tồn tại.' : 'Không lưu được danh mục.');
        }
    }
    redirect('admin/categories.php');
}
$cats = db()->query('SELECT c.*, (SELECT COUNT(*) FROM products WHERE category_id = c.id) AS n FROM categories c ORDER BY c.name')->fetchAll();

$pageTitle = 'Quản lý danh mục';
include __DIR__ . '/../includes/header.php';
?>
<section class="page-intro"><h1>Danh mục cá</h1><p>Danh mục bị ẩn sẽ không hiện trên website cùng các sản phẩm của nó.</p></section>
<?php include __DIR__ . '/../includes/admin_nav.php'; ?>
<form method="post" class="card form-card" style="margin-bottom:24px">
  <h2 style="font-size:18px;margin-bottom:14px">Thêm danh mục</h2>
  <?= csrf_field() ?><input type="hidden" name="id" value="0">
  <div class="two">
    <div class="field"><label for="new-name">Tên danh mục</label><input id="new-name" name="name" type="text" maxlength="100" required></div>
    <div class="field"><label for="new-status">Trạng thái</label><select id="new-status" name="status"><option value="active">Hiển thị</option><option value="inactive">Ẩn</option></select></div>
  </div>
  <div class="field"><label for="new-desc">Mô tả</label><input id="new-desc" name="description" type="text"></div>
  <button class="btn" type="submit">Thêm danh mục</button>
</form>

<?php if (!$cats): ?>
  <div class="empty"><h2>Chưa có danh mục</h2><p>Hãy thêm danh mục đầu tiên (ví dụ: cá nước ngọt, cá biển).</p></div>
<?php else: ?>
<div class="table-wrap"><table>
  <thead><tr><th>Tên</th><th>Mô tả</th><th>Trạng thái</th><th class="num">Sản phẩm</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($cats as $c): $f = 'cat-' . (int)$c['id']; ?>
    <tr>
      <td><input form="<?= $f ?>" name="name" type="text" value="<?= e($c['name']) ?>" maxlength="100" required aria-label="Tên danh mục"></td>
      <td><input form="<?= $f ?>" name="description" type="text" value="<?= e($c['description']) ?>" aria-label="Mô tả"></td>
      <td><select form="<?= $f ?>" name="status" aria-label="Trạng thái">
        <option value="active" <?= $c['status'] === 'active' ? 'selected' : '' ?>>Hiển thị</option>
        <option value="inactive" <?= $c['status'] === 'inactive' ? 'selected' : '' ?>>Ẩn</option></select></td>
      <td class="num"><?= (int)$c['n'] ?></td>
      <td>
        <form id="<?= $f ?>" method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><button class="btn ghost sm" type="submit">Lưu</button></form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php endif; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>

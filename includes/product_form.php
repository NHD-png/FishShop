<?php
// Biểu mẫu dùng chung cho thêm/sửa sản phẩm. Cần: $product (mảng), $cats, $errors, $submitLabel
?>
<?php if ($errors): ?><div class="alert err" role="alert"><?php foreach ($errors as $er): ?><div><?= e($er) ?></div><?php endforeach; ?></div><?php endif; ?>
<form method="post" enctype="multipart/form-data" class="card form-card">
  <?= csrf_field() ?>
  <img id="img-preview" class="preview" src="<?= e(file_url($product['image'] ?? null)) ?>" alt="Ảnh sản phẩm">
  <div class="field"><label for="image">Ảnh sản phẩm</label>
    <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp" data-preview="img-preview">
    <small>JPG, PNG hoặc WEBP, tối đa 2MB. PHP tự lưu vào uploads/products/ và ghi đường dẫn vào database.</small></div>
  <div class="field"><label for="name">Tên cá</label><input id="name" name="name" type="text" maxlength="150" value="<?= e($product['name']) ?>" required></div>
  <div class="two">
    <div class="field"><label for="price">Giá bán (₫)</label><input id="price" name="price" type="number" min="1" step="1" value="<?= e($product['price']) ?>" required></div>
    <div class="field"><label for="stock">Số lượng tồn kho</label><input id="stock" name="stock" type="number" min="0" step="1" value="<?= e($product['stock']) ?>" required></div>
  </div>
  <div class="two">
    <div class="field"><label for="category_id">Danh mục</label>
      <select id="category_id" name="category_id" required>
        <option value="">— Chọn danh mục —</option>
        <?php foreach ($cats as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= (int)$product['category_id'] === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?><?= $c['status'] === 'inactive' ? ' (đã ẩn)' : '' ?></option>
        <?php endforeach; ?>
      </select>
      <?php if (!$cats): ?><small>Chưa có danh mục. <a href="<?= url('admin/categories.php') ?>" style="color:var(--teal)">Tạo danh mục trước</a>.</small><?php endif; ?></div>
    <div class="field"><label for="status">Trạng thái</label>
      <select id="status" name="status">
        <option value="active" <?= $product['status'] === 'active' ? 'selected' : '' ?>>Đang bán</option>
        <option value="inactive" <?= $product['status'] === 'inactive' ? 'selected' : '' ?>>Ngừng bán</option>
      </select></div>
  </div>
  <div class="field"><label for="description">Mô tả</label><textarea id="description" name="description" rows="5"><?= e($product['description']) ?></textarea></div>
  <button class="btn" type="submit"><?= e($submitLabel) ?></button>
  <a class="btn ghost" href="<?= url('admin/products.php') ?>">Hủy</a>
</form>

<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/upload.php';
requireAdmin();

$cats = db()->query('SELECT id, name, status FROM categories ORDER BY name')->fetchAll();
$product = ['name' => '', 'price' => '', 'stock' => '0', 'category_id' => 0, 'status' => 'active', 'description' => '', 'image' => null];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    [$d, $errors] = validate_product_post();
    $product = array_merge($product, $d);
    $imagePath = null;
    if (!$errors) {
        $up = save_uploaded_image($_FILES['image'] ?? ['error' => UPLOAD_ERR_NO_FILE], 'products');
        if ($up['ok']) $imagePath = $up['path'];
        elseif ($up['error'] !== 'none') $errors[] = $up['error'];
    }
    if (!$errors) {
        db()->prepare('INSERT INTO products (name, price, image, description, category_id, stock, status) VALUES (?,?,?,?,?,?,?)')
            ->execute([$d['name'], (int)$d['price'], $imagePath, $d['description'], $d['category_id'], (int)$d['stock'], $d['status']]);
        flash('success', 'Đã thêm sản phẩm “' . $d['name'] . '”.');
        redirect('admin/products.php');
    }
}
$pageTitle = 'Thêm sản phẩm'; $submitLabel = 'Lưu sản phẩm';
include __DIR__ . '/../includes/header.php';
?>
<section class="page-intro"><h1>Thêm sản phẩm</h1><p>Nhập thông tin, chọn ảnh và nhấn lưu. Không cần sửa file HTML.</p></section>
<?php include __DIR__ . '/../includes/admin_nav.php'; ?>
<?php include __DIR__ . '/../includes/product_form.php'; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>

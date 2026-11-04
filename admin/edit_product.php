<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../includes/upload.php';
requireAdmin();

$id = (int)($_GET['id'] ?? 0);
$st = db()->prepare('SELECT * FROM products WHERE id = ?');
$st->execute([$id]);
$product = $st->fetch();
if (!$product) { flash('error', 'Không tìm thấy sản phẩm.'); redirect('admin/products.php'); }

$cats = db()->query('SELECT id, name, status FROM categories ORDER BY name')->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    [$d, $errors] = validate_product_post();
    $oldImage = $product['image'];
    $product = array_merge($product, $d);
    $newImage = null;
    if (!$errors) {
        $up = save_uploaded_image($_FILES['image'] ?? ['error' => UPLOAD_ERR_NO_FILE], 'products');
        if ($up['ok']) $newImage = $up['path'];
        elseif ($up['error'] !== 'none') $errors[] = $up['error'];
    }
    if (!$errors) {
        $image = $newImage ?? $oldImage;
        try {
            db()->prepare('UPDATE products SET name=?, price=?, image=?, description=?, category_id=?, stock=?, status=? WHERE id=?')
                ->execute([$d['name'], (int)$d['price'], $image, $d['description'], $d['category_id'], (int)$d['stock'], $d['status'], $id]);
        } catch (Throwable $ex) {
            delete_uploaded_image($newImage);
            error_log('FishShop could not update product.');
            $errors[] = 'Không lưu được sản phẩm. Vui lòng thử lại.';
        }
        if (!$errors) {
            if ($newImage) delete_uploaded_image($oldImage);
            flash('success', 'Đã cập nhật sản phẩm.');
            redirect('admin/products.php');
        }
    }
    $product['image'] = $oldImage;
}
$pageTitle = 'Sửa sản phẩm'; $submitLabel = 'Lưu thay đổi';
include __DIR__ . '/_header.php';
?>
<section class="page-intro"><h1>Sửa sản phẩm</h1><p>Chỉnh thông tin hoặc chọn ảnh mới để thay ảnh hiện tại.</p></section>
<?php include __DIR__ . '/../includes/product_form.php'; ?>
<?php include __DIR__ . '/_footer.php'; ?>

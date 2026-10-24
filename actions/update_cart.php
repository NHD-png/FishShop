<?php
require_once __DIR__ . '/../includes/auth.php';
$user = requireLogin();
requirePost('cart.php');

$productId = (int)($_POST['product_id'] ?? 0);
$qty       = (int)($_POST['quantity'] ?? 0);
if ($productId <= 0 || $qty < 1 || $qty > 999) respond(false, 'Số lượng phải từ 1 trở lên. Dùng nút Xóa để bỏ sản phẩm.', 'cart.php');

$cartId = get_cart_id((int)$user['id'], false);
if (!$cartId) respond(false, 'Giỏ hàng trống.', 'cart.php');

$st = db()->prepare("SELECT stock, status FROM products WHERE id = ?");
$st->execute([$productId]);
$p = $st->fetch();
if (!$p || $p['status'] !== 'active') respond(false, 'Sản phẩm không còn được bán.', 'cart.php');
if ($qty > (int)$p['stock']) respond(false, 'Kho chỉ còn ' . (int)$p['stock'] . ' sản phẩm.', 'cart.php');

$st = db()->prepare('UPDATE cart_items SET quantity = ? WHERE cart_id = ? AND product_id = ?');
$st->execute([$qty, $cartId, $productId]);
respond(true, 'Đã cập nhật số lượng.', 'cart.php');

<?php
require_once __DIR__ . '/../includes/auth.php';
$user = requireLogin();
requirePost('cart.php');

$productId = (int)($_POST['product_id'] ?? 0);
$cartId = get_cart_id((int)$user['id'], false);
if ($cartId && $productId > 0) {
    db()->prepare('DELETE FROM cart_items WHERE cart_id = ? AND product_id = ?')->execute([$cartId, $productId]);
}
respond(true, 'Đã xóa sản phẩm khỏi giỏ hàng.', 'cart.php');

<?php
require_once __DIR__ . '/../includes/auth.php';
$user = current_user();
requirePost('cart.php');

$productId = (int)($_POST['product_id'] ?? 0);
if (!$user) {
    $items = guest_cart_items();
    unset($items[$productId]);
    $_SESSION['guest_cart'] = $items;
    respond(true, 'Đã xóa sản phẩm khỏi giỏ hàng.', 'cart.php');
}
$cartId = get_cart_id((int)$user['id'], false);
if ($cartId && $productId > 0) {
    db()->prepare('DELETE FROM cart_items WHERE cart_id = ? AND product_id = ?')->execute([$cartId, $productId]);
}
respond(true, 'Đã xóa sản phẩm khỏi giỏ hàng.', 'cart.php');

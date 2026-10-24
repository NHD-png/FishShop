<?php
require_once __DIR__ . '/../includes/auth.php';
$user = requireLogin();
$back = safe_back($_POST['back'] ?? '', 'index.php');
requirePost($back);

$productId = (int)($_POST['product_id'] ?? 0);
$qty       = (int)($_POST['quantity'] ?? 1);
if ($productId <= 0 || $qty < 1 || $qty > 999) respond(false, 'Số lượng không hợp lệ.', $back);

$pdo = db();
try {
    $pdo->beginTransaction();
    // Khóa dòng sản phẩm để kiểm tra tồn kho chính xác
    $st = $pdo->prepare("SELECT p.id, p.name, p.stock FROM products p JOIN categories c ON c.id = p.category_id
                         WHERE p.id = ? AND p.status = 'active' AND c.status = 'active' FOR UPDATE");
    $st->execute([$productId]);
    $p = $st->fetch();
    if (!$p) { $pdo->rollBack(); respond(false, 'Sản phẩm không còn được bán.', $back); }

    $cartId = get_cart_id((int)$user['id']);
    $st = $pdo->prepare('SELECT id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ?');
    $st->execute([$cartId, $productId]);
    $item = $st->fetch();
    $newQty = $qty + ($item ? (int)$item['quantity'] : 0);

    if ((int)$p['stock'] < 1) { $pdo->rollBack(); respond(false, 'Sản phẩm đã hết hàng.', $back); }
    if ($newQty > (int)$p['stock']) {
        $pdo->rollBack();
        respond(false, 'Kho chỉ còn ' . (int)$p['stock'] . ' sản phẩm (trong giỏ đã có ' . ($item ? (int)$item['quantity'] : 0) . ').', $back);
    }
    if ($item) {
        $pdo->prepare('UPDATE cart_items SET quantity = ? WHERE id = ?')->execute([$newQty, $item['id']]);
    } else {
        $pdo->prepare('INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?,?,?)')->execute([$cartId, $productId, $newQty]);
    }
    $pdo->prepare('UPDATE cart SET updated_at = NOW() WHERE id = ?')->execute([$cartId]);
    $pdo->commit();
} catch (Throwable $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    respond(false, 'Không thể thêm vào giỏ hàng, vui lòng thử lại.', $back);
}
respond(true, 'Đã thêm “' . $p['name'] . '” vào giỏ hàng.', $back, ['cartCount' => cart_count((int)$user['id'])]);

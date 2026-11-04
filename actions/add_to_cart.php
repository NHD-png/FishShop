<?php
require_once __DIR__ . '/../includes/auth.php';
$user = current_user();
$back = safe_back($_POST['back'] ?? '', 'index.php');
requirePost($back);

$productId = (int)($_POST['product_id'] ?? 0);
$qty       = (int)($_POST['quantity'] ?? 1);
if ($productId <= 0 || $qty < 1 || $qty > 999) respond(false, 'Số lượng không hợp lệ.', $back);

$pdo = db();
if (!$user) {
    $st = $pdo->prepare("SELECT p.id, p.name, p.stock FROM products p JOIN categories c ON c.id = p.category_id
        WHERE p.id = ? AND p.status = 'active' AND c.status = 'active'");
    $st->execute([$productId]);
    $p = $st->fetch();
    if (!$p) respond(false, 'Sản phẩm không còn được bán.', $back);
    $items = guest_cart_items();
    if (!isset($items[$productId]) && count($items) >= 100) respond(false, 'Giỏ hàng tối đa 100 sản phẩm khác nhau.', $back);
    $newQty = ($items[$productId] ?? 0) + $qty;
    if ((int)$p['stock'] < 1) respond(false, 'Sản phẩm đã hết hàng.', $back);
    if ($newQty > min(999, (int)$p['stock'])) respond(false, 'Số lượng trong giỏ vượt quá tồn kho hoặc giới hạn 999 sản phẩm.', $back);
    $items[$productId] = $newQty;
    $_SESSION['guest_cart'] = $items;
    respond(true, 'Đã thêm “' . $p['name'] . '” vào giỏ hàng.', $back, ['cartCount' => current_cart_count()]);
}
try {
    $pdo->beginTransaction();
    $cartId = get_cart_id((int)$user['id']);
    $cartLock = $pdo->prepare('SELECT id FROM cart WHERE id = ? FOR UPDATE');
    $cartLock->execute([$cartId]);
    // Khóa dòng sản phẩm để kiểm tra tồn kho chính xác
    $st = $pdo->prepare("SELECT p.id, p.name, p.stock FROM products p JOIN categories c ON c.id = p.category_id
                         WHERE p.id = ? AND p.status = 'active' AND c.status = 'active' FOR UPDATE");
    $st->execute([$productId]);
    $p = $st->fetch();
    if (!$p) { $pdo->rollBack(); respond(false, 'Sản phẩm không còn được bán.', $back); }

    $st = $pdo->prepare('SELECT id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ?');
    $st->execute([$cartId, $productId]);
    $item = $st->fetch();
    $newQty = $qty + ($item ? (int)$item['quantity'] : 0);

    if ((int)$p['stock'] < 1) { $pdo->rollBack(); respond(false, 'Sản phẩm đã hết hàng.', $back); }
    if ($newQty > min(999, (int)$p['stock'])) {
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

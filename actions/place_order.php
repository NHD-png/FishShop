<?php
require_once __DIR__ . '/../includes/auth.php';
$user = requireLogin();
requirePost('checkout.php');

$address = trim($_POST['shipping_address'] ?? '');
$phone   = trim($_POST['phone'] ?? '');
if (mb_strlen($address) < 5 || mb_strlen($address) > 255) respond(false, 'Vui lòng nhập địa chỉ giao hàng (5-255 ký tự).', 'checkout.php');
if (!preg_match('/^[0-9]{9,11}$/', $phone)) respond(false, 'Số điện thoại gồm 9-11 chữ số.', 'checkout.php');

$pdo = db();
try {
    $pdo->beginTransaction();

    $cartId = get_cart_id((int)$user['id'], false);
    if (!$cartId) { $pdo->rollBack(); respond(false, 'Giỏ hàng đang trống.', 'cart.php'); }

    $st = $pdo->prepare('SELECT product_id, quantity FROM cart_items WHERE cart_id = ? ORDER BY id');
    $st->execute([$cartId]);
    $items = $st->fetchAll();
    if (!$items) { $pdo->rollBack(); respond(false, 'Giỏ hàng đang trống.', 'cart.php'); }

    // Đọc giá + tồn kho HIỆN TẠI từ database (khóa dòng), tính tổng ở PHP
    $total = 0;
    $lines = [];
    $sel = $pdo->prepare("SELECT p.id, p.name, p.price, p.stock, p.status, c.status AS cat_status
                          FROM products p JOIN categories c ON c.id = p.category_id WHERE p.id = ? FOR UPDATE");
    foreach ($items as $it) {
        $sel->execute([(int)$it['product_id']]);
        $p = $sel->fetch();
        $q = (int)$it['quantity'];
        if (!$p || $p['status'] !== 'active' || $p['cat_status'] !== 'active') {
            $pdo->rollBack(); respond(false, 'Có sản phẩm đã ngừng bán. Vui lòng kiểm tra lại giỏ hàng.', 'cart.php');
        }
        if ($q < 1 || $q > (int)$p['stock']) {
            $pdo->rollBack(); respond(false, '“' . $p['name'] . '” chỉ còn ' . (int)$p['stock'] . ' sản phẩm. Vui lòng điều chỉnh giỏ hàng.', 'cart.php');
        }
        $lines[] = ['id' => (int)$p['id'], 'qty' => $q, 'price' => $p['price']];
        $total += $q * (float)$p['price'];
    }

    $pdo->prepare("INSERT INTO orders (user_id, total, shipping_address, phone, status, payment_status) VALUES (?,?,?,?, 'pending', 'unpaid')")
        ->execute([(int)$user['id'], $total, $address, $phone]);
    $orderId = (int)$pdo->lastInsertId();

    $ins = $pdo->prepare('INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?,?,?,?)');
    $dec = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?');
    foreach ($lines as $l) {
        $ins->execute([$orderId, $l['id'], $l['qty'], $l['price']]);   // lưu giá tại thời điểm mua
        $dec->execute([$l['qty'], $l['id'], $l['qty']]);
        if ($dec->rowCount() !== 1) throw new RuntimeException('Tồn kho không đủ');
    }
    $pdo->prepare('DELETE FROM cart_items WHERE cart_id = ?')->execute([$cartId]);   // chỉ xóa giỏ sau khi đơn đã tạo
    $pdo->commit();
} catch (Throwable $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    respond(false, 'Đặt hàng thất bại, giỏ hàng của bạn vẫn được giữ nguyên. Vui lòng thử lại.', 'checkout.php');
}
// Lưu địa chỉ/số điện thoại để lần sau tự điền sẵn
$pdo->prepare('UPDATE users SET address = ?, phone = ? WHERE id = ?')->execute([$address, $phone, (int)$user['id']]);
flash('success', 'Đã tạo đơn hàng #' . $orderId . '. Vui lòng chuyển khoản theo mã QR bên dưới.');
redirect('payment.php?id=' . $orderId);

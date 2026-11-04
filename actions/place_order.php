<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/payos.php';
require_once __DIR__ . '/../includes/location.php';
require_once __DIR__ . '/../includes/vouchers.php';
require_once __DIR__ . '/../includes/rewards.php';
$user = requireLogin();
requirePost('checkout.php');

$address = trim(post_string('shipping_address'));
$phone   = trim(post_string('phone'));
$paymentMethod = post_string('payment_method');
if (!in_array($paymentMethod, ['cash', 'bank_transfer', 'payos'], true)) respond(false, 'Vui lòng chọn phương thức thanh toán.', 'checkout.php');
if (mb_strlen($address) < 5 || mb_strlen($address) > 255) respond(false, 'Vui lòng nhập địa chỉ giao hàng (5-255 ký tự).', 'checkout.php');
if (!preg_match('/^[0-9]{9,11}$/', $phone)) respond(false, 'Số điện thoại gồm 9-11 chữ số.', 'checkout.php');
try {
    $shippingLocation = shipping_location_from_values($_POST['shipping_latitude'] ?? null, $_POST['shipping_longitude'] ?? null);
} catch (DomainException $ex) {
    respond(false, $ex->getMessage(), 'checkout.php');
}

$pdo = db();
try {
    $hasVoucherColumns = voucher_schema_ready($pdo);
    reward_schema($pdo);
    $hasLocationColumns = shipping_location_schema_ready($pdo);
    if ($shippingLocation !== null && !$hasLocationColumns) {
        respond(false, 'Chưa lưu được điểm giao hàng lúc này. Vui lòng bỏ ghim và nhập đầy đủ địa chỉ để đặt hàng.', 'checkout.php');
    }
    $pdo->beginTransaction();

    // Serialize checkout for the same account across sessions/devices.
    $cartSt = $pdo->prepare('SELECT id FROM cart WHERE user_id = ? FOR UPDATE');
    $cartSt->execute([(int)$user['id']]);
    $cartId = $cartSt->fetchColumn();
    if (!$cartId) { $pdo->rollBack(); respond(false, 'Giỏ hàng đang trống.', 'cart.php'); }

    $st = $pdo->prepare('SELECT product_id, quantity FROM cart_items WHERE cart_id = ? ORDER BY id FOR UPDATE');
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

    $subtotal = (int)$total;
    $prizeRewardId = (int)($_POST['prize_reward_id'] ?? 0);
    $prizeProductId = (int)($_POST['prize_product_id'] ?? 0);
    $prizeDiscount = 0;
    $prizeCouponUnits = 0;
    $prizeUnitDiscount = 0;
    $prizeCouponIds = [];
    if ($prizeRewardId > 0 && $prizeProductId > 0) {
        $couponSt = $pdo->prepare("SELECT id FROM prize_rewards WHERE user_id=? AND reward_type='coupon' AND discount_percent=5 AND status='available' ORDER BY id FOR UPDATE");
        $couponSt->execute([(int)$user['id']]);
        $availableCouponIds = array_map('intval', $couponSt->fetchAll(PDO::FETCH_COLUMN));
        if (!in_array($prizeRewardId, $availableCouponIds, true)) throw new DomainException('Phiếu thưởng đã được sử dụng hoặc không hợp lệ.');
        foreach ($lines as $line) {
            if ($line['id'] !== $prizeProductId) continue;
            $couponQuote = prize_coupon_line_quote((int)$line['price'], (int)$line['qty'], count($availableCouponIds));
            $prizeCouponUnits = $couponQuote['units'];
            $prizeUnitDiscount = $couponQuote['unit_discount'];
            $prizeDiscount = $couponQuote['discount'];
            break;
        }
        if ($prizeCouponUnits < 1 || $prizeUnitDiscount < 1) throw new DomainException('Hãy giữ sản phẩm đã chọn trong giỏ để sử dụng phiếu thưởng.');
        $prizeCouponIds = [$prizeRewardId];
        foreach ($availableCouponIds as $couponId) {
            if ($couponId === $prizeRewardId || count($prizeCouponIds) >= $prizeCouponUnits) continue;
            $prizeCouponIds[] = $couponId;
        }
    }
    $fishRewardId = (int)($_POST['fish_reward_id'] ?? 0);
    $fishProductId = (int)($_POST['fish_product_id'] ?? 0);
    $fishDiscount = 0;
    if ($prizeDiscount === 0 && $fishRewardId > 0 && $fishProductId > 0) {
        $fishSt = $pdo->prepare("SELECT id FROM prize_rewards WHERE id=? AND user_id=? AND reward_type='fish' AND status='available' AND product_id=? FOR UPDATE");
        $fishSt->execute([$fishRewardId, (int)$user['id'], $fishProductId]);
        if (!$fishSt->fetchColumn()) throw new DomainException('Phần thưởng cá đã được sử dụng hoặc không hợp lệ.');
        foreach ($lines as $line) {
            if ($line['id'] === $fishProductId) { $fishDiscount = (int)$line['price']; break; }
        }
        if ($fishDiscount < 1) throw new DomainException('Hãy giữ cá thưởng trong giỏ để hoàn tất giao hàng.');
    }
    $selectedCode = isset($_SESSION['checkout_voucher']) && is_string($_SESSION['checkout_voucher']) ? $_SESSION['checkout_voucher'] : '';
    $voucher = voucher_quote($pdo, $selectedCode, (int)$user['id'], $subtotal, true);
    $total = max(1, $voucher['total'] - $prizeDiscount - $fishDiscount);
    if ($paymentMethod === 'payos' && ($total < 2000 || $total > 500000000)) {
        throw new DomainException('Chuyển khoản QR áp dụng cho số tiền từ 2.000 đ đến 500.000.000 đ. Vui lòng chọn tiền mặt hoặc thay đổi giỏ hàng/mã giảm giá.');
    }

    $orderSql = "INSERT INTO orders (user_id, total, shipping_address, phone, status, payment_status, payment_method";
    $orderArgs = [(int)$user['id'], $total, $address, $phone, $paymentMethod];
    if ($hasLocationColumns) {
        $orderSql .= ', shipping_latitude, shipping_longitude';
        $orderArgs[] = $shippingLocation['latitude'] ?? null;
        $orderArgs[] = $shippingLocation['longitude'] ?? null;
    }
    if ($hasVoucherColumns) {
        $orderSql .= ', subtotal, discount_amount, voucher_code';
        $orderArgs[] = $subtotal;
        $orderArgs[] = $voucher['discount'] + $prizeDiscount + $fishDiscount;
        $orderArgs[] = $voucher['code'] !== '' ? $voucher['code'] : null;
    }
    $orderSql .= ") VALUES (?,?,?,?, 'pending', 'unpaid', ?" . ($hasLocationColumns ? ',?,?' : '') . ($hasVoucherColumns ? ',?,?,?' : '') . ')';
    $pdo->prepare($orderSql)->execute($orderArgs);
    $orderId = (int)$pdo->lastInsertId();

    if ($voucher['voucher_id'] !== null) {
        $pdo->prepare('INSERT INTO voucher_usages (voucher_id, user_id, order_id, discount_amount) VALUES (?,?,?,?)')
            ->execute([$voucher['voucher_id'], (int)$user['id'], $orderId, $voucher['discount']]);
    }
    if ($prizeCouponIds) {
        $markCoupon = $pdo->prepare("UPDATE prize_rewards SET status='used',used_at=NOW() WHERE id=? AND user_id=? AND status='available'");
        foreach ($prizeCouponIds as $couponId) $markCoupon->execute([$couponId, (int)$user['id']]);
    }
    if ($fishDiscount > 0) {
        $pdo->prepare("UPDATE prize_rewards SET status='used',used_at=NOW() WHERE id=? AND user_id=? AND status='available'")->execute([$fishRewardId, (int)$user['id']]);
    }

    $ins = $pdo->prepare('INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?,?,?,?)');
    $dec = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?');
    foreach ($lines as $l) {
        $remainingQty = (int)$l['qty'];
        $unitPrice = (int)$l['price'];
        if ($fishDiscount > 0 && $l['id'] === $fishProductId) {
            $ins->execute([$orderId, $l['id'], 1, 0]);
            $remainingQty--;
        }
        if ($prizeCouponUnits > 0 && $l['id'] === $prizeProductId) {
            if ($remainingQty < $prizeCouponUnits) throw new DomainException('Số lượng sản phẩm trong giỏ đã thay đổi. Vui lòng tải lại trang thanh toán.');
            $ins->execute([$orderId, $l['id'], $prizeCouponUnits, max(0, $unitPrice - $prizeUnitDiscount)]);
            $remainingQty -= $prizeCouponUnits;
        }
        if ($remainingQty > 0) $ins->execute([$orderId, $l['id'], $remainingQty, $unitPrice]);
        $dec->execute([$l['qty'], $l['id'], $l['qty']]);
        if ($dec->rowCount() !== 1) throw new RuntimeException('Tồn kho không đủ');
    }
    $pdo->prepare('DELETE FROM cart_items WHERE cart_id = ?')->execute([$cartId]);   // chỉ xóa giỏ sau khi đơn đã tạo
    $pdo->commit();
    unset($_SESSION['checkout_voucher'], $_SESSION['checkout_draft'], $_SESSION['prize_coupon_reward'], $_SESSION['prize_coupon_product'], $_SESSION['prize_fish_reward'], $_SESSION['prize_fish_product']);
} catch (DomainException $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    respond(false, $ex->getMessage(), 'checkout.php');
} catch (Throwable $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('FishShop place order failed (' . get_class($ex) . ', code ' . (string)$ex->getCode() . ').');
    respond(false, 'Đặt hàng thất bại, giỏ hàng của bạn vẫn được giữ nguyên. Vui lòng thử lại.', 'checkout.php');
}
// Lưu địa chỉ/số điện thoại để lần sau tự điền sẵn
try {
    $pdo->prepare('UPDATE users SET address = ?, phone = ? WHERE id = ?')->execute([$address, $phone, (int)$user['id']]);
} catch (Throwable $ex) {
    error_log('FishShop order saved; could not remember profile delivery details.');
}
flash('success', 'Đã tạo đơn hàng #' . $orderId . '.');
if ($paymentMethod === 'payos') {
    try {
        payos_create_order_link($orderId);
    } catch (Throwable $ex) {
        error_log('FishShop PayOS checkout after order failed (' . get_class($ex) . ', code ' . (string)$ex->getCode() . ').');
        flash('error', 'Đơn đã được tạo. Chưa mở được mã QR chuyển khoản; vào đơn hàng và bấm “Mở mã QR chuyển khoản” để thử lại.');
    }
}
redirect('payment.php?id=' . $orderId);

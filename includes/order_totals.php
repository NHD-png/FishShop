<?php
// Optional voucher breakdown; caller renders the final total separately.
$orderDiscount = max(0, (int)($totalsOrder['discount_amount'] ?? 0));
if ($orderDiscount > 0):
$orderSubtotal = (int)($totalsOrder['subtotal'] ?? ((int)$totalsOrder['total'] + $orderDiscount));
?>
<div class="sum"><span>Tạm tính</span><span><?= money($orderSubtotal) ?></span></div>
<div class="sum"><span>Giảm giá<?= !empty($totalsOrder['voucher_code']) ? ' · ' . e($totalsOrder['voucher_code']) : '' ?></span><span style="color:var(--teal)">−<?= money($orderDiscount) ?></span></div>
<?php endif; ?>

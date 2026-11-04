<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../includes/voucher_admin.php';
requireAdmin();
$voucherAdminUrl = BASE_URL . 'admin/vouchers.php';
$ready = voucher_schema_ready(db());
$editId = max(0, (int)($_GET['id'] ?? 0));
$form = voucher_admin_defaults();
$errors = [];
$existing = null;
if ($ready && $editId) {
    $st = db()->prepare('SELECT * FROM vouchers WHERE id = ?');
    $st->execute([$editId]);
    $existing = $st->fetch();
    if ($existing) $form = $existing;
    else { flash('error', 'Không tìm thấy mã giảm giá.'); header('Location: ' . $voucherAdminUrl); exit; }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) $errors[] = 'Phiên làm việc hết hạn. Vui lòng tải lại trang.';
    elseif (!$ready) $errors[] = 'Chưa khởi tạo dữ liệu mã giảm giá trên máy chủ.';
    elseif (post_string('action') === 'toggle') {
        $id = (int)post_string('voucher_id');
        db()->prepare('UPDATE vouchers SET is_active = 1 - is_active WHERE id = ?')->execute([$id]);
        flash('success', 'Đã đổi trạng thái mã giảm giá.');
        header('Location: ' . $voucherAdminUrl); exit;
    } elseif (post_string('action') === 'save') {
        [$form, $errors] = voucher_admin_validate($_POST);
        if ($existing && $form['code'] !== $existing['code']) $errors[] = 'Mã đã tạo không thể đổi tên. Hãy tạo mã mới nếu cần.';
        if (!$errors) {
            $columns = ['code', 'name', 'discount_type', 'discount_value', 'min_order_amount', 'max_discount_amount', 'starts_at', 'ends_at', 'usage_limit', 'per_user_limit', 'is_active'];
            $values = array_map(static fn($key) => $form[$key], $columns);
            try {
                if ($existing) {
                    $sql = 'UPDATE vouchers SET ' . implode(', ', array_map(static fn($column) => $column . ' = ?', $columns)) . ' WHERE id = ?';
                    $values[] = $editId;
                } else $sql = 'INSERT INTO vouchers (' . implode(',', $columns) . ') VALUES (' . implode(',', array_fill(0, count($columns), '?')) . ')';
                db()->prepare($sql)->execute($values);
                flash('success', $existing ? 'Đã cập nhật mã giảm giá.' : 'Đã tạo mã giảm giá.');
                header('Location: ' . $voucherAdminUrl); exit;
            } catch (PDOException $ex) {
                $errors[] = (int)($ex->errorInfo[1] ?? 0) === 1062 ? 'Mã giảm giá này đã tồn tại.' : 'Chưa lưu được mã giảm giá. Vui lòng thử lại.';
            }
        }
    } else $errors[] = 'Thao tác không hợp lệ.';
}
$vouchers = $ready ? db()->query("SELECT v.*, (SELECT COUNT(*) FROM voucher_usages vu JOIN orders o ON o.id = vu.order_id WHERE vu.voucher_id = v.id AND o.status <> 'cancelled') AS used_count FROM vouchers v ORDER BY v.id DESC LIMIT 200")->fetchAll() : [];
$pageTitle = 'Mã giảm giá';
include __DIR__ . '/_header.php';
?>
<section class="page-intro"><h1>Mã giảm giá</h1><p>Tạo ưu đãi, đặt thời hạn và giới hạn lượt sử dụng. Mỗi đơn áp dụng một mã.</p></section>
<?php if (!$ready): ?><div class="alert warn">Dữ liệu mã giảm giá chưa được khởi tạo. Chạy database/migrate_vouchers.php bằng PHP để cập nhật database.</div><?php endif; ?>
<?php if ($errors): ?><div class="alert err" role="alert"><?php foreach ($errors as $error): ?><div><?= e($error) ?></div><?php endforeach; ?></div><?php endif; ?>
<section class="card voucher-admin-card">
  <div class="voucher-card-heading"><h2><?= $existing ? 'Chỉnh sửa ' . e($existing['code']) : 'Tạo mã mới' ?></h2><?php if ($existing): ?><a href="<?= e($voucherAdminUrl) ?>" class="btn ghost sm">Tạo mã khác</a><?php endif; ?></div>
  <form method="post">
    <?= csrf_field() ?><input type="hidden" name="action" value="save">
    <fieldset <?= !$ready ? 'disabled' : '' ?> style="border:0;padding:0">
      <div class="two">
        <div class="field"><label for="voucher-code">Mã ưu đãi</label><input id="voucher-code" name="code" type="text" value="<?= e($form['code']) ?>" minlength="3" maxlength="32" pattern="[A-Za-z0-9_-]{3,32}" required <?= $existing ? 'readonly' : '' ?> placeholder="VD: FISH10"></div>
        <div class="field"><label for="voucher-name">Tên chương trình</label><input id="voucher-name" name="name" type="text" maxlength="100" value="<?= e($form['name']) ?>" required placeholder="Ưu đãi khách hàng mới"></div>
      </div>
      <div class="two">
        <div class="field"><label for="voucher-type">Loại giảm</label><select id="voucher-type" name="discount_type"><option value="percent" <?= $form['discount_type'] === 'percent' ? 'selected' : '' ?>>Theo phần trăm (%)</option><option value="fixed" <?= $form['discount_type'] === 'fixed' ? 'selected' : '' ?>>Số tiền cố định (đ)</option></select></div>
        <div class="field"><label for="voucher-value">Giá trị giảm (% hoặc đ)</label><input id="voucher-value" name="discount_value" type="number" min="1" max="9999999999" step="1" value="<?= (int)$form['discount_value'] ?>" required><small>Phần trăm: từ 1 đến 99. Tiền cố định: nhập số đồng.</small></div>
      </div>
      <div class="two">
        <div class="field"><label for="voucher-min">Giá trị đơn tối thiểu (đ)</label><input id="voucher-min" name="min_order_amount" type="number" min="0" max="9999999999" step="1" value="<?= (int)$form['min_order_amount'] ?>" required></div>
        <div class="field"><label for="voucher-max">Số tiền giảm tối đa (đ)</label><input id="voucher-max" name="max_discount_amount" type="number" min="1" max="9999999999" step="1" value="<?= e($form['max_discount_amount'] ?? '') ?>"><small>Để trống nếu không giới hạn.</small></div>
      </div>
      <div class="two">
        <div class="field"><label for="voucher-start">Bắt đầu</label><input id="voucher-start" name="starts_at" type="datetime-local" value="<?= e($form['starts_at'] ? str_replace(' ', 'T', substr($form['starts_at'], 0, 16)) : '') ?>"><small>Giờ Việt Nam; để trống để áp dụng ngay.</small></div>
        <div class="field"><label for="voucher-end">Kết thúc</label><input id="voucher-end" name="ends_at" type="datetime-local" value="<?= e($form['ends_at'] ? str_replace(' ', 'T', substr($form['ends_at'], 0, 16)) : '') ?>"><small>Để trống nếu không có hạn kết thúc.</small></div>
      </div>
      <div class="two">
        <div class="field"><label for="voucher-limit">Tổng lượt dùng</label><input id="voucher-limit" name="usage_limit" type="number" min="1" max="1000000000" step="1" value="<?= e($form['usage_limit'] ?? '') ?>"><small>Để trống nếu không giới hạn.</small></div>
        <div class="field"><label for="voucher-per-user">Lượt dùng mỗi khách</label><input id="voucher-per-user" name="per_user_limit" type="number" min="1" max="1000000" step="1" value="<?= (int)$form['per_user_limit'] ?>" required></div>
      </div>
      <label class="voucher-active"><input type="checkbox" name="is_active" value="1" <?= (int)$form['is_active'] === 1 ? 'checked' : '' ?>> Bật mã giảm giá</label>
      <button class="btn" type="submit"><?= $existing ? 'Lưu thay đổi' : 'Tạo mã giảm giá' ?></button>
    </fieldset>
  </form>
</section>
<h2 class="voucher-list-title">Mã đã tạo</h2>
<?php if (!$vouchers): ?><div class="empty"><p>Chưa có mã giảm giá nào.</p></div><?php else: ?>
<div class="table-wrap"><table><thead><tr><th>Mã / chương trình</th><th>Ưu đãi</th><th>Điều kiện</th><th>Lượt dùng</th><th>Thời hạn</th><th>Trạng thái</th><th>Thao tác</th></tr></thead><tbody>
<?php $voucherNow = (new DateTimeImmutable('now', new DateTimeZone('Asia/Ho_Chi_Minh')))->format('Y-m-d H:i:s'); foreach ($vouchers as $voucher): ?>
  <tr><td><strong><?= e($voucher['code']) ?></strong><br><span class="mute"><?= e($voucher['name']) ?></span></td>
  <td><?= $voucher['discount_type'] === 'percent' ? (int)$voucher['discount_value'] . '%' : money($voucher['discount_value']) ?><?php if ($voucher['max_discount_amount']): ?><br><small>Tối đa <?= money($voucher['max_discount_amount']) ?></small><?php endif; ?></td>
  <td>Đơn từ <?= money($voucher['min_order_amount']) ?><br><small><?= (int)$voucher['per_user_limit'] ?> lần/khách</small></td>
  <td><?= (int)$voucher['used_count'] ?> / <?= $voucher['usage_limit'] === null ? 'Không giới hạn' : (int)$voucher['usage_limit'] ?></td>
  <td><?= $voucher['starts_at'] ? e(substr($voucher['starts_at'], 0, 16)) : 'Áp dụng ngay' ?><br><?= $voucher['ends_at'] ? e(substr($voucher['ends_at'], 0, 16)) : 'Không hạn kết thúc' ?></td>
  <td><?= !(int)$voucher['is_active'] ? 'Đang tắt' : ($voucher['ends_at'] && $voucher['ends_at'] <= $voucherNow ? 'Hết hạn' : ($voucher['starts_at'] && $voucher['starts_at'] > $voucherNow ? 'Sắp bắt đầu' : 'Đang bật')) ?></td>
  <td><div class="voucher-table-actions"><a class="btn ghost sm" href="<?= e($voucherAdminUrl . '?id=' . (int)$voucher['id']) ?>">Sửa</a><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="voucher_id" value="<?= (int)$voucher['id'] ?>"><button class="btn ghost sm" type="submit"><?= (int)$voucher['is_active'] ? 'Tắt' : 'Bật' ?></button></form></div></td></tr>
<?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
<?php include __DIR__ . '/_footer.php'; ?>

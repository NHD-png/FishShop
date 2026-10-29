<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { flash('error', 'Khách hàng không hợp lệ.'); redirect('admin/customers.php'); }

$customerSt = db()->prepare(
    'SELECT u.*, COALESCE(cr.total_exp, 0) AS total_exp, COALESCE(cr.total_eligible_spend, 0) AS total_eligible_spend,
            COALESCE(cr.spend_remainder, 0) AS spend_remainder, COALESCE(cr.current_rank, "dong") AS current_rank,
            COALESCE(cr.last_rank_up_at, NULL) AS last_rank_up_at
     FROM users u
     LEFT JOIN customer_ranks cr ON cr.user_id = u.id
     WHERE u.id = ? AND u.role = ?'
);
$customerSt->execute([$id, 'customer']);
$customer = $customerSt->fetch();
if (!$customer) { flash('error', 'Không tìm thấy khách hàng.'); redirect('admin/customers.php'); }

$ordersSt = db()->prepare('SELECT id, created_at, total, status, payment_status FROM orders WHERE user_id = ? ORDER BY id DESC');
$ordersSt->execute([$id]);
$orders = $ordersSt->fetchAll();

$totalOrders = count($orders);
$totalSpend = 0;
foreach ($orders as $order) {
    if ($order['status'] === 'completed' && $order['payment_status'] === 'paid') {
        $totalSpend += (float)$order['total'];
    }
}

$rankSummary = null;
if (!empty($customer['total_exp']) || !empty($customer['current_rank'])) {
    $rankSummary = customer_rank((int)$customer['total_exp']);
}

$historySt = db()->prepare('SELECT h.*, a.fullname AS admin_name FROM user_account_lock_history h LEFT JOIN users a ON a.id = h.performed_by_admin_id WHERE h.user_id = ? ORDER BY h.created_at DESC LIMIT 20');
$historySt->execute([$id]);
$history = $historySt->fetchAll();

$pageTitle = 'Chi tiết khách hàng';
include __DIR__ . '/../includes/header.php';
?>
<section class="page-intro">
  <h1>Khách hàng: <?= e($customer['username']) ?></h1>
  <p>Thông tin tài khoản, lịch sử đặt hàng và trạng thái khóa tài khoản.</p>
</section>
<?php include __DIR__ . '/../includes/admin_nav.php'; ?>

<div class="cart">
  <section class="card">
    <h2>Thông tin tài khoản</h2>
    <div class="sum"><span>Username</span><span><?= e($customer['username']) ?></span></div>
    <div class="sum"><span>Họ tên</span><span><?= e($customer['fullname']) ?></span></div>
    <div class="sum"><span>Email</span><span><?= e($customer['email'] ?: 'Chưa cập nhật') ?></span></div>
    <div class="sum"><span>Số điện thoại</span><span><?= e($customer['phone'] ?: 'Chưa cập nhật') ?></span></div>
    <div class="sum"><span>Ngày đăng ký</span><span><?= e(date('d/m/Y H:i', strtotime($customer['created_at']))) ?></span></div>
    <div class="sum"><span>Trạng thái</span><span><?= e(account_status_label((string)($customer['account_status'] ?? 'active'))) ?></span></div>
    <?php if (($customer['account_status'] ?? 'active') === 'locked'): ?>
      <div class="sum"><span>Lý do khóa</span><span><?= e($customer['locked_reason'] ?: 'Không có dữ liệu') ?></span></div>
      <?php if (!empty($customer['locked_note'])): ?><div class="sum"><span>Ghi chú</span><span style="text-align:right;white-space:normal"><?= e($customer['locked_note']) ?></span></div><?php endif; ?>
      <div class="sum"><span>Khóa lúc</span><span><?= e($customer['locked_at'] ? date('d/m/Y H:i', strtotime($customer['locked_at'])) : '—') ?></span></div>
    <?php endif; ?>
  </section>

  <aside class="order-summary">
    <h2>Thống kê</h2>
    <div class="sum"><span>Tổng đơn hàng</span><span><?= (int)$totalOrders ?></span></div>
    <div class="sum"><span>Tổng chi tiêu hợp lệ</span><span><?= money($totalSpend) ?></span></div>
    <?php if ($rankSummary): ?>
      <div class="sum"><span>Rank hiện tại</span><span><?= e($rankSummary['current']['name']) ?></span></div>
      <div class="sum"><span>EXP</span><span><?= number_format((int)$customer['total_exp'], 0, ',', '.') ?> EXP</span></div>
    <?php endif; ?>
    <div style="margin-top:18px;">
      <?php if (($customer['account_status'] ?? 'active') === 'active'): ?>
        <form method="post" action="<?= url('actions/lock_user.php') ?>" data-confirm="Khóa tài khoản <?= e($customer['username']) ?>?">
          <?= csrf_field() ?>
          <input type="hidden" name="user_id" value="<?= (int)$customer['id'] ?>">
          <div class="field">
            <label for="reason">Lý do khóa</label>
            <select id="reason" name="reason" required>
              <option value="">-- Chọn lý do --</option>
              <option value="payment_fraud">Có dấu hiệu gian lận thanh toán</option>
              <option value="order_fraud">Có dấu hiệu gian lận đơn hàng</option>
              <option value="abuse">Lạm dụng hệ thống hoặc vi phạm quy định</option>
              <option value="other">Lý do khác</option>
            </select>
          </div>
          <div class="field">
            <label for="note">Ghi chú bổ sung</label>
            <textarea id="note" name="note" placeholder="Nhập mô tả chi tiết nếu cần..."></textarea>
          </div>
          <button class="btn danger block" type="submit">Khóa tài khoản</button>
        </form>
      <?php else: ?>
        <form method="post" action="<?= url('actions/unlock_user.php') ?>" data-confirm="Mở khóa tài khoản <?= e($customer['username']) ?>?">
          <?= csrf_field() ?>
          <input type="hidden" name="user_id" value="<?= (int)$customer['id'] ?>">
          <button class="btn block" type="submit">Mở khóa tài khoản</button>
        </form>
      <?php endif; ?>
      <a class="btn ghost block" style="margin-top:12px" href="<?= url('admin/customers.php') ?>">Quay lại danh sách</a>
    </div>
  </aside>
</div>

<?php if ($orders): ?>
  <section class="card" style="margin-top:24px;">
    <h2>Lịch sử đơn hàng</h2>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Mã đơn</th>
            <th>Ngày tạo</th>
            <th class="num">Tổng tiền</th>
            <th>Trạng thái đơn</th>
            <th>Thanh toán</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($orders as $order): ?>
            <tr>
              <td>#<?= (int)$order['id'] ?></td>
              <td><?= e(date('d/m/Y H:i', strtotime($order['created_at']))) ?></td>
              <td class="num"><?= money((float)$order['total']) ?></td>
              <td><span class="tag <?= e($order['status']) ?>"><?= e(ORDER_STATUS[$order['status']]) ?></span></td>
              <td><span class="tag <?= e($order['payment_status']) ?>"><?= e(PAYMENT_STATUS[$order['payment_status']]) ?></span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
<?php else: ?>
  <div class="empty" style="margin-top:24px;">
    <h2>Chưa có đơn hàng</h2>
    <p>Khách hàng này chưa đặt đơn nào trên hệ thống.</p>
  </div>
<?php endif; ?>

<?php if ($history): ?>
  <section class="card" style="margin-top:24px;">
    <h2>Lịch sử khóa/mở khóa</h2>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Thời gian</th>
            <th>Hành động</th>
            <th>Lý do</th>
            <th>Người thực hiện</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($history as $log): ?>
            <tr>
              <td><?= e(date('d/m/Y H:i', strtotime($log['created_at']))) ?></td>
              <td><?= e($log['action'] === 'lock' ? 'Khóa' : 'Mở khóa') ?></td>
              <td><?= e($log['reason'] ?: '—') ?></td>
              <td><?= e($log['admin_name'] ?: '—') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>

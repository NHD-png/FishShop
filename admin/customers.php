<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$search = trim((string)($_GET['q'] ?? ''));
$status = $_GET['status'] ?? 'all';
$allowedStatus = ['all', 'active', 'locked'];
if (!in_array($status, $allowedStatus, true)) $status = 'all';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;

$baseSql = "FROM users u LEFT JOIN orders o ON o.user_id = u.id WHERE u.role = 'customer'";
$args = [];
if ($search !== '') {
    $like = '%' . addcslashes($search, '%_\\') . '%';
    $baseSql .= ' AND (u.username LIKE ? OR u.fullname LIKE ?)';
    $args[] = $like;
    $args[] = $like;
}
if ($status === 'active') {
    $baseSql .= ' AND u.account_status = ?';
    $args[] = 'active';
} elseif ($status === 'locked') {
    $baseSql .= ' AND u.account_status = ?';
    $args[] = 'locked';
}

$countSql = 'SELECT COUNT(DISTINCT u.id) ' . $baseSql;
$countSt = db()->prepare($countSql);
$countSt->execute($args);
$total = (int)$countSt->fetchColumn();
$pageCount = max(1, (int)ceil($total / $perPage));
if ($page > $pageCount) $page = $pageCount;
$offset = ($page - 1) * $perPage;

$listSql = "SELECT u.id, u.username, u.fullname, u.email, u.phone, u.created_at, u.account_status,
            COUNT(DISTINCT o.id) AS total_orders,
            COALESCE(SUM(CASE WHEN o.status = 'completed' AND o.payment_status = 'paid' THEN o.total ELSE 0 END), 0) AS total_spend
            " . $baseSql . "
            GROUP BY u.id, u.username, u.fullname, u.email, u.phone, u.created_at, u.account_status
            ORDER BY u.created_at DESC
            LIMIT ? OFFSET ?";

$listSt = db()->prepare($listSql);
$listArgs = $args;
$listArgs[] = $perPage;
$listArgs[] = $offset;
$listSt->execute($listArgs);
$customers = $listSt->fetchAll();

$pageTitle = 'Quản lý khách hàng';
include __DIR__ . '/../includes/header.php';
?>
<section class="page-intro">
  <h1>Quản lý khách hàng</h1>
  <p>Danh sách khách hàng, thống kê đơn hàng và trạng thái tài khoản.</p>
</section>
<?php include __DIR__ . '/../includes/admin_nav.php'; ?>

<div class="tool" style="margin-top:8px;">
  <span><?= e($total) ?> khách hàng</span>
  <form method="get" action="<?= url('admin/customers.php') ?>" style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;">
    <input type="search" name="q" value="<?= e($search) ?>" placeholder="Tìm theo username hoặc họ tên" aria-label="Tìm kiếm khách hàng">
    <select name="status" aria-label="Lọc trạng thái tài khoản">
      <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>Tất cả</option>
      <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Hoạt động</option>
      <option value="locked" <?= $status === 'locked' ? 'selected' : '' ?>>Bị khóa</option>
    </select>
    <button class="btn sm" type="submit">Lọc</button>
  </form>
</div>

<?php if (!$customers): ?>
  <div class="empty">
    <h2>Không tìm thấy khách hàng</h2>
    <p>Không có khách hàng nào phù hợp với điều kiện tìm kiếm hoặc bộ lọc hiện tại.</p>
  </div>
<?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Username</th>
          <th>Họ tên</th>
          <th>Email</th>
          <th>Số điện thoại</th>
          <th>Ngày đăng ký</th>
          <th class="num">Số đơn</th>
          <th class="num">Chi tiêu</th>
          <th>Trạng thái</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($customers as $c): ?>
          <tr>
            <td><strong><?= e($c['username']) ?></strong></td>
            <td><?= e($c['fullname']) ?></td>
            <td><?= e($c['email'] ?: '—') ?></td>
            <td><?= e($c['phone'] ?: '—') ?></td>
            <td><?= e(date('d/m/Y', strtotime($c['created_at']))) ?></td>
            <td class="num"><?= (int)$c['total_orders'] ?></td>
            <td class="num"><?= money((float)($c['total_spend'] ?? 0)) ?></td>
            <td>
              <span class="tag <?= e($c['account_status'] === 'locked' ? 'cancelled' : 'completed') ?>">
                <?= e(account_status_label((string)($c['account_status'] ?? 'active'))) ?>
              </span>
            </td>
            <td>
              <div style="display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end;">
                <a class="btn ghost sm" href="<?= url('admin/customer_detail.php?id=' . (int)$c['id']) ?>">Chi tiết</a>
                <?php if (($c['account_status'] ?? 'active') === 'active'): ?>
                  <form method="post" action="<?= url('actions/lock_user.php') ?>" data-confirm="Khóa tài khoản <?= e($c['username']) ?>?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="user_id" value="<?= (int)$c['id'] ?>">
                    <button class="btn danger sm" type="submit">Khóa</button>
                  </form>
                <?php else: ?>
                  <form method="post" action="<?= url('actions/unlock_user.php') ?>" data-confirm="Mở khóa tài khoản <?= e($c['username']) ?>?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="user_id" value="<?= (int)$c['id'] ?>">
                    <button class="btn ghost sm" type="submit">Mở khóa</button>
                  </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($pageCount > 1): ?>
    <div class="tool" style="justify-content:center;border-bottom:0;padding-bottom:0;margin-top:20px;">
      <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
        <?php if ($page > 1): ?>
          <a class="btn ghost sm" href="<?= url('admin/customers.php?' . http_build_query(['q' => $search, 'status' => $status, 'page' => $page - 1])) ?>">Trước</a>
        <?php endif; ?>
        <span class="mute">Trang <?= (int)$page ?> / <?= (int)$pageCount ?></span>
        <?php if ($page < $pageCount): ?>
          <a class="btn ghost sm" href="<?= url('admin/customers.php?' . http_build_query(['q' => $search, 'status' => $status, 'page' => $page + 1])) ?>">Sau</a>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
<?php endif; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>

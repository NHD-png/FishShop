<?php
require_once __DIR__ . '/../includes/auth.php';
$admin = requireAdmin();
requirePost('admin/customers.php');

$userId = (int)($_POST['user_id'] ?? 0);
if ($userId <= 0) {
    flash('error', 'Khách hàng không hợp lệ.');
    redirect('admin/customers.php');
}

$pdo = db();
$targetSt = $pdo->prepare('SELECT id, account_status FROM users WHERE id = ?');
$targetSt->execute([$userId]);
$target = $targetSt->fetch();
if (!$target) {
    flash('error', 'Không tìm thấy khách hàng cần mở khóa.');
    redirect('admin/customers.php');
}
if ($target['account_status'] === 'active') {
    flash('info', 'Tài khoản này hiện đang hoạt động.');
    redirect('admin/customer_detail.php?id=' . $userId);
}

try {
    $pdo->beginTransaction();
    $pdo->prepare('UPDATE users SET account_status = ?, locked_reason = NULL, locked_note = NULL, locked_at = NULL, locked_by_admin_id = NULL WHERE id = ?')
        ->execute(['active', $userId]);
    $pdo->prepare('INSERT INTO user_account_lock_history (user_id, action, reason, note, performed_by_admin_id) VALUES (?, ?, NULL, NULL, ?)')
        ->execute([$userId, 'unlock', (int)$admin['id']]);
    $pdo->commit();
    flash('success', 'Đã mở khóa tài khoản thành công.');
} catch (Throwable $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    flash('error', 'Không thể mở khóa tài khoản. Vui lòng thử lại.');
}
redirect('admin/customer_detail.php?id=' . $userId);

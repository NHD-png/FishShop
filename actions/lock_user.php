<?php
require_once __DIR__ . '/../includes/auth.php';
$admin = requireAdmin();
requirePost('admin/customers.php');

$userId = (int)($_POST['user_id'] ?? 0);
$reason = trim((string)($_POST['reason'] ?? ''));
$note = trim((string)($_POST['note'] ?? ''));

$reasonMap = [
    'payment_fraud' => 'Có dấu hiệu gian lận thanh toán',
    'order_fraud' => 'Có dấu hiệu gian lận đơn hàng',
    'abuse' => 'Lạm dụng hệ thống hoặc vi phạm quy định',
    'other' => 'Lý do khác',
];

if ($userId <= 0) {
    flash('error', 'Khách hàng không hợp lệ.');
    redirect('admin/customers.php');
}
if (!isset($reasonMap[$reason])) {
    flash('error', 'Vui lòng chọn lý do khóa hợp lệ.');
    redirect('admin/customer_detail.php?id=' . $userId);
}
if ($reason === 'other' && $note === '') {
    flash('error', 'Vui lòng nhập mô tả cụ thể khi chọn “Lý do khác”.');
    redirect('admin/customer_detail.php?id=' . $userId);
}

$pdo = db();
$targetSt = $pdo->prepare('SELECT id, role, account_status FROM users WHERE id = ?');
$targetSt->execute([$userId]);
$target = $targetSt->fetch();
if (!$target) {
    flash('error', 'Không tìm thấy khách hàng cần khóa.');
    redirect('admin/customers.php');
}
if ($target['account_status'] === 'locked') {
    flash('info', 'Tài khoản này đã bị khóa trước đó.');
    redirect('admin/customer_detail.php?id=' . $userId);
}
if ($target['role'] === 'admin') {
    $adminCountSt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE role = ? AND account_status = ?');
    $adminCountSt->execute(['admin', 'active']);
    $activeAdminCount = (int)$adminCountSt->fetchColumn();
    if ($activeAdminCount <= 1) {
        flash('error', 'Không thể khóa tài khoản Admin cuối cùng trong hệ thống.');
        redirect('admin/customer_detail.php?id=' . $userId);
    }
}

$saveReason = $reasonMap[$reason];
$saveNote = $reason === 'other' ? $note : '';

try {
    $pdo->beginTransaction();
    $pdo->prepare('UPDATE users SET account_status = ?, locked_reason = ?, locked_note = ?, locked_at = NOW(), locked_by_admin_id = ? WHERE id = ?')
        ->execute(['locked', $saveReason, $saveNote, (int)$admin['id'], $userId]);
    $pdo->prepare('INSERT INTO user_account_lock_history (user_id, action, reason, note, performed_by_admin_id) VALUES (?, ?, ?, ?, ?)')
        ->execute([$userId, 'lock', $saveReason, $saveNote, (int)$admin['id']]);
    $pdo->commit();
    flash('success', 'Đã khóa tài khoản thành công.');
} catch (Throwable $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    flash('error', 'Không thể khóa tài khoản. Vui lòng thử lại.');
}
redirect('admin/customer_detail.php?id=' . $userId);

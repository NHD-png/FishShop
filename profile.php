<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/upload.php';
$user = requireLogin();
$uid = (int)$user['id'];
ensure_rank_profile(db(), $uid);
$rankSt = db()->prepare('SELECT total_exp, total_eligible_spend, spend_remainder, current_rank, last_rank_up_at FROM customer_ranks WHERE user_id = ?');
$rankSt->execute([$uid]);
$rankProfile = $rankSt->fetch();
$rankInfo = customer_rank((int)$rankProfile['total_exp']);
$nextRank = $rankInfo['next'];
$rankProgress = $nextRank
    ? min(100, max(0, ((int)$rankProfile['total_exp'] - $rankInfo['current']['min']) / ($nextRank['min'] - $rankInfo['current']['min']) * 100))
    : 100;
$justRankedUp = $rankProfile['last_rank_up_at']
    && time() - strtotime($rankProfile['last_rank_up_at']) <= 86400
    && $rankProfile['current_rank'] === $rankInfo['current']['code'];

$errors = [];
$securityErrors = [];
$securitySt = db()->prepare('SELECT security_question FROM users WHERE id = ?');
$securitySt->execute([$uid]);
$savedSecurityQuestion = (string)($securitySt->fetchColumn() ?: '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_type'] ?? '') === 'security') {
    $question = trim(post_string('security_question'));
    $answer = normalize_security_answer(post_string('security_answer'));
    $currentPassword = post_string('current_password');
    if (!csrf_ok()) $securityErrors[] = 'Phiên làm việc không hợp lệ, vui lòng thử lại.';
    if (!array_key_exists($question, SECURITY_QUESTIONS)) $securityErrors[] = 'Vui lòng chọn câu hỏi bảo mật.';
    $securityErrors = array_merge($securityErrors, security_answer_errors($answer));
    $passwordSt = db()->prepare('SELECT password FROM users WHERE id = ?');
    $passwordSt->execute([$uid]);
    $passwordHash = $passwordSt->fetchColumn();
    if (!$passwordHash || !password_verify($currentPassword, $passwordHash)) $securityErrors[] = 'Mật khẩu hiện tại không chính xác.';

    if (!$securityErrors) {
        db()->prepare('UPDATE users SET security_question = ?, security_answer_hash = ? WHERE id = ?')
            ->execute([$question, password_hash($answer, PASSWORD_DEFAULT), $uid]);
        flash('success', 'Đã cập nhật câu hỏi bảo mật.');
        redirect('profile.php');
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim(post_string('fullname'));
    $phone    = trim(post_string('phone'));
    $address  = trim(post_string('address'));
    if (!csrf_ok()) $errors[] = 'Phiên làm việc không hợp lệ, vui lòng thử lại.';
    if (mb_strlen($fullname) < 2 || mb_strlen($fullname) > 100) $errors[] = 'Họ tên từ 2 đến 100 ký tự.';
    if (!preg_match('/^[0-9]{9,11}$/', $phone)) $errors[] = 'Số điện thoại gồm 9-11 chữ số.';
    if (mb_strlen($address) > 255) $errors[] = 'Địa chỉ tối đa 255 ký tự.';

    $newAvatar = null;
    if (!$errors) {
        $up = save_uploaded_image($_FILES['avatar'] ?? ['error' => UPLOAD_ERR_NO_FILE], 'avatars');
        if ($up['ok']) $newAvatar = $up['path'];
        elseif ($up['error'] !== 'none') $errors[] = $up['error'];
    }
    if (!$errors) {
        if ($newAvatar) {
            db()->prepare('UPDATE users SET fullname=?, phone=?, address=?, avatar=? WHERE id=?')->execute([$fullname, $phone, $address, $newAvatar, $user['id']]);
            delete_uploaded_image($user['avatar']);
        } else {
            db()->prepare('UPDATE users SET fullname=?, phone=?, address=? WHERE id=?')->execute([$fullname, $phone, $address, $user['id']]);
        }
        flash('success', 'Đã cập nhật thông tin cá nhân.');
        redirect('profile.php');
    }
    $user['fullname'] = $fullname; $user['phone'] = $phone; $user['address'] = $address;
}
$pageTitle = 'Thông tin cá nhân';
include __DIR__ . '/includes/header.php';
?>
<p class="crumb"><a href="<?= url('index.php') ?>">Trang chủ</a> / Thông tin cá nhân</p>
<h1 style="font-size:28px">Thông tin cá nhân</h1>
<div class="profile">
  <section class="card">
    <?php if ($errors): ?><div class="alert err" role="alert"><?php foreach ($errors as $er): ?><div><?= e($er) ?></div><?php endforeach; ?></div><?php endif; ?>
    <form method="post" enctype="multipart/form-data" class="js-validate" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="form_type" value="profile">
      <img id="avatar-preview" class="preview" src="<?= e(file_url($user['avatar'])) ?>" alt="Ảnh đại diện">
      <div class="field"><label for="avatar">Ảnh đại diện</label>
        <input id="avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp" data-preview="avatar-preview">
        <small>JPG, PNG hoặc WEBP, tối đa 2MB. Bỏ trống nếu không đổi.</small></div>
      <div class="field"><label for="fullname">Họ tên</label><input id="fullname" name="fullname" type="text" data-rule="fullname" value="<?= e($user['fullname']) ?>" required></div>
      <div class="field"><label for="phone">Số điện thoại</label><input id="phone" name="phone" type="text" inputmode="numeric" data-rule="phone" value="<?= e($user['phone']) ?>" required></div>
      <div class="field"><label for="address">Địa chỉ</label><textarea id="address" name="address"><?= e($user['address']) ?></textarea></div>
      <button class="btn" type="submit">Lưu thay đổi</button>
    </form>
  </section>
  <section class="card">
    <h2 style="font-size:18px">Câu hỏi bảo mật</h2>
    <?php if ($securityErrors): ?><div class="alert err" role="alert"><?php foreach ($securityErrors as $er): ?><div><?= e($er) ?></div><?php endforeach; ?></div><?php endif; ?>
    <p class="hint" style="margin:8px 0 16px">Dùng để khôi phục mật khẩu. Câu trả lời được chuẩn hóa và chỉ lưu dưới dạng mã băm.</p>
    <form method="post" class="js-validate" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="form_type" value="security">
      <div class="field"><label for="security_question">Câu hỏi bảo mật</label>
        <select id="security_question" name="security_question" data-rule="security_question" required>
          <option value="">-- Chọn câu hỏi --</option>
          <?php foreach (SECURITY_QUESTIONS as $key => $question): ?>
            <option value="<?= e($key) ?>" <?= $savedSecurityQuestion === $key ? 'selected' : '' ?>><?= e($question) ?></option>
          <?php endforeach; ?>
        </select></div>
      <div class="field"><label for="security_answer">Câu trả lời mới</label>
        <input id="security_answer" name="security_answer" type="text" data-rule="security_answer" autocomplete="off" maxlength="72" required>
        <small>Tối đa 72 byte; không phân biệt chữ hoa/thường và gộp khoảng trắng.</small></div>
      <div class="field"><label for="current_password">Mật khẩu hiện tại để xác nhận</label>
        <input id="current_password" name="current_password" type="password" autocomplete="current-password" required></div>
      <button class="btn" type="submit">Lưu câu hỏi bảo mật</button>
    </form>
  </section>
  <section class="card">
    <h2 style="font-size:18px">Hạng khách hàng</h2>
    <?php if ($justRankedUp): ?><div class="alert ok rank-notice">Chúc mừng! Bạn vừa đạt Rank <?= e($rankInfo['current']['name']) ?>.</div><?php endif; ?>
    <div class="rank-heading">
      <span class="rank-badge"><?= e($rankInfo['current']['name']) ?></span>
      <strong><?= number_format((int)$rankProfile['total_exp'], 0, ',', '.') ?> EXP</strong>
    </div>
    <?php if ($nextRank): ?>
      <div class="rank-meta">
        <span>Rank tiếp theo: <b><?= e($nextRank['name']) ?></b> — <?= number_format($nextRank['min'], 0, ',', '.') ?> EXP</span>
        <span>Còn <?= number_format(max(0, $nextRank['min'] - (int)$rankProfile['total_exp']), 0, ',', '.') ?> EXP</span>
      </div>
      <div class="rank-progress" role="progressbar" aria-label="Tiến trình lên Rank <?= e($nextRank['name']) ?>" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= (int)round($rankProgress) ?>">
        <span style="width:<?= e(number_format($rankProgress, 2, '.', '')) ?>%"></span>
      </div>
    <?php else: ?>
      <p class="rank-meta">Bạn đã đạt Rank cao nhất: Thách Đấu.</p>
    <?php endif; ?>
    <p class="rank-footnote">Tích lũy <?= number_format((int)$rankProfile['spend_remainder'], 0, ',', '.') ?> ₫ / 1.000 ₫ cho EXP kế tiếp.</p>
  </section>
  <section class="card">
    <h2 style="font-size:18px">Tài khoản</h2>
    <dl class="info-list">
      <dt>Tên đăng nhập</dt><dd><?= e($user['username']) ?></dd>
      <dt>Vai trò</dt><dd><?= $user['role'] === 'admin' ? 'Quản trị viên' : 'Khách hàng' ?></dd>
    </dl>
    <p style="margin-top:20px"><a class="btn ghost" href="<?= url('purchase_history.php') ?>">Lịch sử mua hàng</a></p>
  </section>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

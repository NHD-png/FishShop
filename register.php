<?php
require_once __DIR__ . '/includes/auth.php';
if (isLoggedIn()) redirect('index.php');

$errors = [];
$old = ['username' => '', 'fullname' => '', 'phone' => '', 'address' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($old as $k => $_) $old[$k] = trim($_POST[$k] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $confirm  = (string)($_POST['confirm'] ?? '');

    if (!csrf_ok()) $errors[] = 'Phiên làm việc không hợp lệ, vui lòng thử lại.';
    if (!preg_match('/^[A-Za-z0-9_]{3,30}$/', $old['username'])) $errors[] = 'Tên đăng nhập 3-30 ký tự: chữ, số hoặc dấu gạch dưới.';
    if (strlen($password) < 6) $errors[] = 'Mật khẩu tối thiểu 6 ký tự.';
    if ($password !== $confirm) $errors[] = 'Mật khẩu nhập lại không khớp.';
    if (mb_strlen($old['fullname']) < 2 || mb_strlen($old['fullname']) > 100) $errors[] = 'Họ tên từ 2 đến 100 ký tự.';
    if (!preg_match('/^[0-9]{9,11}$/', $old['phone'])) $errors[] = 'Số điện thoại gồm 9-11 chữ số.';
    if (mb_strlen($old['address']) > 255) $errors[] = 'Địa chỉ tối đa 255 ký tự.';

    if (!$errors) {
        $st = db()->prepare('SELECT 1 FROM users WHERE username = ?');
        $st->execute([$old['username']]);
        if ($st->fetchColumn()) {
            $errors[] = 'Tên đăng nhập đã tồn tại, hãy chọn tên khác.';
        } else {
            try {
                // role luôn là customer; quản trị viên phải được cấp trong phpMyAdmin (xem README)
                db()->prepare('INSERT INTO users (username, password, fullname, phone, address, role) VALUES (?,?,?,?,?, "customer")')
                    ->execute([$old['username'], password_hash($password, PASSWORD_DEFAULT), $old['fullname'], $old['phone'], $old['address']]);
                flash('success', 'Đăng ký thành công. Hãy đăng nhập.');
                redirect('login.php');
            } catch (PDOException $ex) {
                $errors[] = ($ex->getCode() === '23000') ? 'Tên đăng nhập đã tồn tại, hãy chọn tên khác.' : 'Không thể tạo tài khoản, vui lòng thử lại.';
            }
        }
    }
}
$pageTitle = 'Đăng ký'; $minimal = true;
include __DIR__ . '/includes/header.php';
?>
<section class="auth card">
  <div class="logo-img"><img src="<?= url('assets/images/logo.png') ?>" alt="WebBanCa"></div>
  <h1>Tạo tài khoản</h1>
  <p class="sub">Chỉ mất một phút để bắt đầu mua cá.</p>
  <?php if ($errors): ?><div class="alert err" role="alert"><?php foreach ($errors as $er): ?><div><?= e($er) ?></div><?php endforeach; ?></div><?php endif; ?>
  <form method="post" class="js-validate" novalidate>
    <?= csrf_field() ?>
    <div class="field"><label for="username">Tên đăng nhập</label>
      <input id="username" name="username" type="text" data-rule="username" value="<?= e($old['username']) ?>" autocomplete="username" required></div>
    <div class="field"><label for="fullname">Họ tên</label>
      <input id="fullname" name="fullname" type="text" data-rule="fullname" value="<?= e($old['fullname']) ?>" autocomplete="name" required></div>
    <div class="field"><label for="phone">Số điện thoại</label>
      <input id="phone" name="phone" type="text" inputmode="numeric" data-rule="phone" value="<?= e($old['phone']) ?>" autocomplete="tel" required></div>
    <div class="field"><label for="address">Địa chỉ <span class="mute">(có thể bổ sung sau)</span></label>
      <input id="address" name="address" type="text" value="<?= e($old['address']) ?>" autocomplete="street-address"></div>
    <div class="field"><label for="password">Mật khẩu</label>
      <div class="pw-wrap"><input id="password" name="password" type="password" data-rule="password" autocomplete="new-password" required><button class="pw-toggle" type="button">Hiện</button></div></div>
    <div class="field"><label for="confirm">Nhập lại mật khẩu</label>
      <input id="confirm" name="confirm" type="password" data-rule="password" data-match="password" autocomplete="new-password" required></div>
    <button class="btn block" type="submit">Đăng ký</button>
  </form>
  <p class="switch">Đã có tài khoản? <a href="<?= url('login.php') ?>">Đăng nhập</a></p>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>

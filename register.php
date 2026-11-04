<?php
require_once __DIR__ . '/includes/registration_otp.php';
if (isLoggedIn()) redirect('index.php');
header('Cache-Control: no-store, private');

$errors = [];
$old = ['username' => '', 'fullname' => '', 'email' => '', 'phone' => '', 'address' => ''];
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && isset($_SESSION['registration_old'])) {
    $old = array_replace($old, array_intersect_key($_SESSION['registration_old'], $old));
    unset($_SESSION['registration_old']);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($old as $k => $_) $old[$k] = trim(post_string($k));
    $password = post_string('password');
    $confirm  = post_string('confirm');

    if (!csrf_ok()) $errors[] = 'Phiên làm việc không hợp lệ, vui lòng thử lại.';
    if (!preg_match('/^[A-Za-z0-9_]{3,30}$/', $old['username'])) $errors[] = 'Tên đăng nhập 3-30 ký tự: chữ, số hoặc dấu gạch dưới.';
    $errors = array_merge($errors, password_policy_errors($password));
    if ($password !== $confirm) $errors[] = 'Mật khẩu nhập lại không khớp.';
    if (mb_strlen($old['fullname']) < 2 || mb_strlen($old['fullname']) > 100) $errors[] = 'Họ tên từ 2 đến 100 ký tự.';
    $old['email'] = mb_strtolower($old['email'], 'UTF-8');
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL) || strlen($old['email']) > 254) $errors[] = 'Email không hợp lệ.';
    if ($old['phone'] !== '' && !preg_match('/^[0-9]{9,11}$/', $old['phone'])) $errors[] = 'Số điện thoại gồm 9-11 chữ số.';
    if (mb_strlen($old['address']) > 255) $errors[] = 'Địa chỉ tối đa 255 ký tự.';

    if (!$errors) {
      try {
        $st = db()->prepare('SELECT username, email FROM users WHERE username = ? OR email = ?');
        $st->execute([$old['username'], $old['email']]);
        $existing = $st->fetch();
        if ($existing && strcasecmp($existing['username'], $old['username']) === 0) {
            $errors[] = 'Tên đăng nhập đã tồn tại, hãy chọn tên khác.';
        } elseif ($existing) {
            $errors[] = 'Email đã được sử dụng, vui lòng dùng email khác.';
        } else {
                $data = $old;
                $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
                $pending = registration_issue(db(), $data, $_SERVER['REMOTE_ADDR'] ?? 'unknown');
                session_regenerate_id(true);
                $_SESSION['registration_pending'] = $pending;
                redirect('register_verify.php');
        }
      } catch (DomainException $ex) {
          $errors[] = $ex->getMessage();
      } catch (Throwable $ex) {
          registration_log_exception('issue_registration', $ex);
          $errors[] = 'Chưa thể gửi mã xác minh. Vui lòng thử lại sau.';
      }
    }
}
$pageTitle = 'Đăng ký'; $minimal = true;
include __DIR__ . '/includes/header.php';
?>
<section class="auth card">
  <h1>Tạo tài khoản</h1>
  <p class="sub">Đăng ký bằng email cần xác minh mã OTP trước khi tạo tài khoản.</p>
  <form method="post" action="<?= url('firebase_login.php') ?>" data-firebase-login>
    <?= csrf_field() ?><input type="hidden" name="intent" value="register">
    <button class="btn block google-signin" type="submit">Đăng ký bằng Google</button>
  </form>
  <?php include __DIR__ . '/includes/firebase_scripts.php'; ?>
  <div class="auth-divider"><span>hoặc đăng ký bằng email</span></div>
  <?php if (!registration_mail_ready()): ?><div class="alert warn">Đăng ký qua email chưa sẵn sàng gửi mã OTP. Bạn có thể đăng ký bằng Google.</div><?php endif; ?>
  <?php if (isset($_SESSION['registration_pending'])): ?><p class="switch"><a href="<?= url('register_verify.php') ?>">Tiếp tục xác minh mã đã gửi</a></p><?php endif; ?>
  <?php if ($errors): ?><div class="alert err" role="alert"><?php foreach ($errors as $er): ?><div><?= e($er) ?></div><?php endforeach; ?></div><?php endif; ?>
  <form method="post" class="js-validate" novalidate>
    <?= csrf_field() ?>
    <div class="field"><label for="username">Tên đăng nhập</label>
      <input id="username" name="username" type="text" data-rule="username" value="<?= e($old['username']) ?>" autocomplete="username" required></div>
    <div class="field"><label for="fullname">Họ tên</label>
      <input id="fullname" name="fullname" type="text" data-rule="fullname" value="<?= e($old['fullname']) ?>" autocomplete="name" required></div>
    <div class="field"><label for="email">Email</label>
      <input id="email" name="email" type="email" data-rule="email" value="<?= e($old['email']) ?>" autocomplete="email" maxlength="254" required></div>
    <div class="field"><label for="phone">Số điện thoại <span class="mute">(không bắt buộc)</span></label>
      <input id="phone" name="phone" type="text" inputmode="numeric" data-rule="phone" value="<?= e($old['phone']) ?>" autocomplete="tel"></div>
    <div class="field"><label for="address">Địa chỉ <span class="mute">(có thể bổ sung sau)</span></label>
      <input id="address" name="address" type="text" value="<?= e($old['address']) ?>" autocomplete="street-address"></div>
    <div class="field"><label for="password">Mật khẩu</label>
      <div class="pw-wrap"><input id="password" name="password" type="password" data-rule="password" autocomplete="new-password" required><button class="pw-toggle" type="button">Hiện</button></div>
      <small>Ít nhất 8 ký tự, gồm chữ hoa, chữ thường, số và ký tự đặc biệt; tối đa 72 byte.</small></div>
    <div class="field"><label for="confirm">Nhập lại mật khẩu</label>
      <input id="confirm" name="confirm" type="password" data-rule="password" data-match="password" autocomplete="new-password" required></div>
    <button class="btn block" type="submit" <?= !registration_mail_ready() ? 'disabled' : '' ?>>Gửi mã xác minh đăng ký</button>
  </form>
  <p class="switch">Đã có tài khoản? <a href="<?= url('login.php') ?>">Đăng nhập</a></p>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>


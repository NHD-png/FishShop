<?php
require_once __DIR__ . '/includes/auth.php';
if (isset($_GET['next']) && safe_customer_return($_GET['next']) !== null) $_SESSION['login_return_to'] = safe_customer_return($_GET['next']);
if (isLoggedIn()) redirect(isAdmin() ? 'admin/index.php' : customer_login_destination());

$error = '';
$username = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim(post_string('username'));
    $password = post_string('password');
    if (!csrf_ok()) {
        $error = 'Phiên làm việc không hợp lệ, vui lòng thử lại.';
    } elseif ($username === '' || $password === '') {
        $error = 'Vui lòng nhập tên đăng nhập và mật khẩu.';
    } else {
        $st = db()->prepare('SELECT id, password, account_status, role FROM users WHERE username = ?');
        $st->execute([$username]);
        $row = $st->fetch();
        if ($row && password_verify($password, $row['password']) && $row['role'] === 'admin') {
            $error = 'Tên đăng nhập hoặc mật khẩu không đúng.';
        } elseif ($row && password_verify($password, $row['password'])) {
            if (($row['account_status'] ?? 'active') === 'locked') {
                $error = 'Tài khoản này đang bị khóa. Vui lòng liên hệ Quản trị viên để được hỗ trợ.';
            } else {
                try {
                    redirect(finish_customer_login((int)$row['id']));
                } catch (Throwable $ex) {
                    error_log('FishShop customer login cart merge failed.');
                    $error = 'Chưa thể khôi phục giỏ hàng. Vui lòng thử đăng nhập lại.';
                }
            }
        } else {
            $error = 'Tên đăng nhập hoặc mật khẩu không đúng.';
        }
    }
}
$pageTitle = 'Đăng nhập'; $minimal = true;
include __DIR__ . '/includes/header.php';
?>
<section class="auth card">
  <h1>Đăng nhập</h1>
  <p class="sub">Chào mừng bạn trở lại Fish Shop.</p>
  <?php if ($error): ?><div class="alert err" role="alert"><?= e($error) ?></div><?php endif; ?>
  <form method="post" class="js-validate" novalidate>
    <?= csrf_field() ?>
    <div class="field">
      <label for="username">Tên đăng nhập</label>
      <input id="username" name="username" type="text" value="<?= e($username) ?>" autocomplete="username" required autofocus>
    </div>
    <div class="field">
      <label for="password">Mật khẩu</label>
      <div class="pw-wrap">
        <input id="password" name="password" type="password" autocomplete="current-password" required>
        <button class="pw-toggle" type="button">Hiện</button>
      </div>
    </div>
    <button class="btn block" type="submit">Đăng nhập</button>
  </form>
  <div class="auth-divider"><span>hoặc</span></div>
  <form method="post" action="<?= url('firebase_login.php') ?>" data-firebase-login>
    <?= csrf_field() ?>
    <button class="btn block google-signin" type="submit">
      <svg width="20" height="20" viewBox="0 0 48 48" aria-hidden="true" focusable="false">
        <path fill="#4285F4" d="M43.6 24.5c0-1.4-.1-2.8-.4-4.1H24v7.8h11a9.4 9.4 0 0 1-4.1 6.2v5h6.6c3.9-3.6 6.1-8.8 6.1-14.9z"/>
        <path fill="#34A853" d="M24 44c5.5 0 10.1-1.8 13.5-4.9l-6.6-5c-1.8 1.2-4.1 1.9-6.9 1.9-5.3 0-9.8-3.6-11.4-8.4H5.8v5.2A20 20 0 0 0 24 44z"/>
        <path fill="#FBBC05" d="M12.6 27.6a12 12 0 0 1 0-7.2v-5.2H5.8a20 20 0 0 0 0 17.6z"/>
        <path fill="#EA4335" d="M24 12c3 0 5.6 1 7.7 3l5.8-5.8A19.3 19.3 0 0 0 24 4 20 20 0 0 0 5.8 15.2l6.8 5.2C14.2 15.6 18.7 12 24 12z"/>
      </svg>
      Đăng nhập bằng Google
    </button>
  </form>
  <?php include __DIR__ . '/includes/firebase_scripts.php'; ?>
  <p class="hint">Đăng nhập Google lần đầu sẽ tạo tài khoản khách hàng.</p>
  <p class="switch"><a href="<?= url('forgot_password.php') ?>">Quên mật khẩu?</a></p>
  <p class="switch">Chưa có tài khoản? <a href="<?= url('register.php') ?>">Đăng ký</a></p>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>

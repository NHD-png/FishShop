<?php
require_once __DIR__ . '/includes/auth.php';
if (isLoggedIn()) redirect('index.php');   // đã đăng nhập thì không ở lại trang này (tránh vòng lặp)

$error = '';
$username = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    if (!csrf_ok()) {
        $error = 'Phiên làm việc không hợp lệ, vui lòng thử lại.';
    } elseif ($username === '' || $password === '') {
        $error = 'Vui lòng nhập tên đăng nhập và mật khẩu.';
    } else {
        $st = db()->prepare('SELECT id, password FROM users WHERE username = ?');
        $st->execute([$username]);
        $row = $st->fetch();
        if ($row && password_verify($password, $row['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$row['id'];
            redirect('index.php');
        }
        $error = 'Tên đăng nhập hoặc mật khẩu không đúng.';
    }
}
$pageTitle = 'Đăng nhập'; $minimal = true;
include __DIR__ . '/includes/header.php';
?>
<section class="auth card">
  <div class="logo-img"><img src="<?= url('assets/images/logo.png') ?>" alt="WebBanCa"></div>
  <h1>Đăng nhập</h1>
  <p class="sub">Đăng nhập để xem và mua cá tươi.</p>
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
  <p class="switch">Chưa có tài khoản? <a href="<?= url('register.php') ?>">Đăng ký</a></p>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>

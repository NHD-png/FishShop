<?php
require_once __DIR__ . '/includes/registration_otp.php';
if (isLoggedIn()) redirect('index.php');
header('Cache-Control: no-store, private');
$pending = $_SESSION['registration_pending'] ?? null;
if (!$pending || time() - (int)($pending['created_at'] ?? 0) > 1800) {
    unset($_SESSION['registration_pending']);
    flash('error', 'Vui lòng nhập lại thông tin đăng ký.');
    redirect('register.php');
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) $error = 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang.';
    else try {
        $action = post_string('action');
        if ($action === 'edit') {
            db()->prepare('DELETE FROM registration_otps WHERE challenge_hash = ?')->execute([hash('sha256', $pending['token'])]);
            $_SESSION['registration_old'] = array_intersect_key($pending['data'], array_flip(['username','fullname','email','phone','address']));
            unset($_SESSION['registration_pending']);
            redirect('register.php');
        } elseif ($action === 'resend') {
            $pending = registration_issue(db(), $pending['data'], $_SERVER['REMOTE_ADDR'] ?? 'unknown');
            $_SESSION['registration_pending'] = $pending;
            flash('success', 'Đã gửi mã mới. Vui lòng kiểm tra hộp thư và thư rác.');
            redirect('register_verify.php');
        } elseif ($action === 'verify') {
            registration_complete(db(), $pending, trim(post_string('code')));
            unset($_SESSION['registration_pending'], $_SESSION['registration_old'], $_SESSION['csrf']);
            session_regenerate_id(true);
            flash('success', 'Đã xác minh email và tạo tài khoản. Hãy đăng nhập.');
            redirect('login.php');
        } else throw new DomainException('Yêu cầu không hợp lệ.');
    } catch (DomainException $e) { $error = $e->getMessage(); }
    catch (PDOException $e) {
        registration_log_exception('verify_registration', $e);
        $error = $e->getCode() === '23000' ? 'Tên đăng nhập hoặc email đã được sử dụng. Vui lòng sửa thông tin hoặc đăng nhập.' : 'Chưa thể xác minh đăng ký. Vui lòng thử lại sau.';
    } catch (Throwable $e) { registration_log_exception('verify_registration', $e); $error = 'Chưa thể xác minh đăng ký. Vui lòng thử lại sau.'; }
}
$pageTitle = 'Xác minh đăng ký'; $minimal = true;
include __DIR__ . '/includes/header.php';
?>
<section class="auth card">
  <h1>Xác minh email</h1>
  <p class="sub">Nhập mã 6 số đã gửi tới <strong><?= e($pending['data']['email']) ?></strong> để hoàn tất đăng ký.</p>
  <?php if ($error): ?><div class="alert err" role="alert"><?= e($error) ?></div><?php endif; ?>
  <?php if ($pending['expires_at'] <= time()): ?><p class="alert warn">Mã đã hết hạn. Vui lòng yêu cầu mã mới.</p><?php endif; ?>
  <form method="post">
    <?= csrf_field() ?><input type="hidden" name="action" value="verify">
    <div class="field"><label for="code">Mã OTP</label><input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" minlength="6" maxlength="6" required autofocus></div>
    <button class="btn block" type="submit">Xác minh và tạo tài khoản</button>
  </form>
  <p class="hint">Mã có hiệu lực 5 phút. Nhập sai tối đa 5 lần. Kiểm tra thư rác nếu chưa thấy email.</p>
  <form method="post" style="margin-top:16px">
    <?= csrf_field() ?><input type="hidden" name="action" value="resend">
    <button class="btn ghost block" type="submit">Gửi lại mã OTP</button>
    <small>Gửi lại sau ít nhất 60 giây, tối đa 5 mã mỗi giờ. Mã mới thay thế mã cũ.</small>
  </form>
  <form method="post" style="margin-top:12px">
    <?= csrf_field() ?><input type="hidden" name="action" value="edit">
    <button class="btn ghost block" type="submit">Sửa thông tin đăng ký</button>
  </form>
  <p class="switch"><a href="<?= url('login.php') ?>">Quay lại đăng nhập</a></p>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>

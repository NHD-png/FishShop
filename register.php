<?php
require_once __DIR__ . '/includes/auth.php';
if (isLoggedIn()) redirect('index.php');

$errors = [];
$old = ['username' => '', 'fullname' => '', 'email' => '', 'phone' => '', 'address' => '', 'security_question' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($old as $k => $_) $old[$k] = trim(post_string($k));
    $password = post_string('password');
    $confirm  = post_string('confirm');
    $securityAnswer = normalize_security_answer(post_string('security_answer'));

    if (!csrf_ok()) $errors[] = 'Phiên làm việc không hợp lệ, vui lòng thử lại.';
    if (!preg_match('/^[A-Za-z0-9_]{3,30}$/', $old['username'])) $errors[] = 'Tên đăng nhập 3-30 ký tự: chữ, số hoặc dấu gạch dưới.';
    $errors = array_merge($errors, password_policy_errors($password));
    if ($password !== $confirm) $errors[] = 'Mật khẩu nhập lại không khớp.';
    if (mb_strlen($old['fullname']) < 2 || mb_strlen($old['fullname']) > 100) $errors[] = 'Họ tên từ 2 đến 100 ký tự.';
    $old['email'] = mb_strtolower($old['email'], 'UTF-8');
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL) || strlen($old['email']) > 254) $errors[] = 'Email không hợp lệ.';
    if ($old['phone'] !== '' && !preg_match('/^[0-9]{9,11}$/', $old['phone'])) $errors[] = 'Số điện thoại gồm 9-11 chữ số.';
    if (mb_strlen($old['address']) > 255) $errors[] = 'Địa chỉ tối đa 255 ký tự.';
    if (!array_key_exists($old['security_question'], SECURITY_QUESTIONS)) $errors[] = 'Vui lòng chọn câu hỏi bảo mật.';
    $errors = array_merge($errors, security_answer_errors($securityAnswer));

    if (!$errors) {
        $st = db()->prepare('SELECT username, email FROM users WHERE username = ? OR email = ?');
        $st->execute([$old['username'], $old['email']]);
        $existing = $st->fetch();
        if ($existing && strcasecmp($existing['username'], $old['username']) === 0) {
            $errors[] = 'Tên đăng nhập đã tồn tại, hãy chọn tên khác.';
        } elseif ($existing) {
            $errors[] = 'Email đã được sử dụng, vui lòng dùng email khác.';
        } else {
            $pdo = db();
            try {
                // role luôn là customer; quản trị viên phải được cấp trong phpMyAdmin (xem README)
                $pdo->beginTransaction();
                $pdo->prepare(
                    'INSERT INTO users (username, password, fullname, email, phone, address, security_question, security_answer_hash, role)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, "customer")'
                )->execute([
                    $old['username'],
                    password_hash($password, PASSWORD_DEFAULT),
                    $old['fullname'],
                    $old['email'],
                    $old['phone'],
                    $old['address'],
                    $old['security_question'],
                    password_hash($securityAnswer, PASSWORD_DEFAULT),
                ]);
                $userId = (int)$pdo->lastInsertId();
                $pdo->prepare("INSERT INTO customer_ranks (user_id, total_exp, total_eligible_spend, spend_remainder, current_rank) VALUES (?, 0, 0, 0, 'dong')")
                    ->execute([$userId]);
                $pdo->commit();
                flash('success', 'Đăng ký thành công. Hãy đăng nhập.');
                redirect('login.php');
            } catch (PDOException $ex) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $errors[] = 'Không thể tạo tài khoản, vui lòng thử lại.';
                if ($ex->getCode() === '23000') {
                    $check = $pdo->prepare('SELECT username, email FROM users WHERE username = ? OR email = ?');
                    $check->execute([$old['username'], $old['email']]);
                    $duplicate = $check->fetch();
                    if ($duplicate && strcasecmp($duplicate['username'], $old['username']) === 0) {
                        $errors[count($errors) - 1] = 'Tên đăng nhập đã tồn tại, hãy chọn tên khác.';
                    } elseif ($duplicate) {
                        $errors[count($errors) - 1] = 'Email đã được sử dụng, vui lòng dùng email khác.';
                    }
                }
            } catch (Throwable $ex) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $errors[] = 'Không thể tạo tài khoản, vui lòng thử lại.';
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
    <div class="field"><label for="email">Email</label>
      <input id="email" name="email" type="email" data-rule="email" value="<?= e($old['email']) ?>" autocomplete="email" maxlength="254" required></div>
    <div class="field"><label for="phone">Số điện thoại <span class="mute">(không bắt buộc)</span></label>
      <input id="phone" name="phone" type="text" inputmode="numeric" data-rule="phone" value="<?= e($old['phone']) ?>" autocomplete="tel"></div>
    <div class="field"><label for="address">Địa chỉ <span class="mute">(có thể bổ sung sau)</span></label>
      <input id="address" name="address" type="text" value="<?= e($old['address']) ?>" autocomplete="street-address"></div>
    <div class="field"><label for="security_question">Câu hỏi bảo mật</label>
      <select id="security_question" name="security_question" data-rule="security_question" required>
        <option value="">-- Chọn câu hỏi --</option>
        <?php foreach (SECURITY_QUESTIONS as $key => $question): ?>
          <option value="<?= e($key) ?>" <?= $old['security_question'] === $key ? 'selected' : '' ?>><?= e($question) ?></option>
        <?php endforeach; ?>
      </select></div>
    <div class="field"><label for="security_answer">Câu trả lời bảo mật</label>
      <input id="security_answer" name="security_answer" type="text" autocomplete="off" maxlength="72" required>
      <small>Không phân biệt chữ hoa/thường; khoảng trắng liên tiếp được gộp. Tối đa 72 byte.</small></div>
    <div class="field"><label for="password">Mật khẩu</label>
      <div class="pw-wrap"><input id="password" name="password" type="password" data-rule="password" autocomplete="new-password" required><button class="pw-toggle" type="button">Hiện</button></div>
      <small>Ít nhất 8 ký tự, gồm chữ hoa, chữ thường, số và ký tự đặc biệt; tối đa 72 byte.</small></div>
    <div class="field"><label for="confirm">Nhập lại mật khẩu</label>
      <input id="confirm" name="confirm" type="password" data-rule="password" data-match="password" autocomplete="new-password" required></div>
    <button class="btn block" type="submit">Đăng ký</button>
  </form>
  <p class="switch">Đã có tài khoản? <a href="<?= url('login.php') ?>">Đăng nhập</a></p>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>

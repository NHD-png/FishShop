<?php
require_once __DIR__ . '/includes/auth.php';
if (isLoggedIn()) redirect('index.php');
header('Cache-Control: no-store, private');
header('Pragma: no-cache');

$errors = [];
$action = post_string('action');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) {
        $errors[] = 'Phiên làm việc không hợp lệ, vui lòng thử lại.';
    } elseif ($action === 'start') {
        unset($_SESSION['password_recovery']);
        $username = trim(post_string('username'));
        if ($username === '' || mb_strlen($username) > 50) {
            $errors[] = 'Vui lòng nhập tên đăng nhập hợp lệ.';
        } else {
            $st = db()->prepare('SELECT id, security_question, security_answer_hash FROM users WHERE username = ?');
            $st->execute([$username]);
            $user = $st->fetch();
            if (
                !$user
                || !isset(SECURITY_QUESTIONS[$user['security_question'] ?? ''])
                || empty($user['security_answer_hash'])
            ) {
                $errors[] = 'Không tìm thấy tài khoản hoặc tài khoản chưa thiết lập câu hỏi bảo mật.';
            } else {
                session_regenerate_id(true);
                $_SESSION['password_recovery'] = [
                    'step' => 'answer',
                    'user_id' => (int)$user['id'],
                    'expires_at' => time() + 900,
                ];
                redirect('forgot_password.php');
            }
        }
    } elseif ($action === 'answer') {
        $recovery = $_SESSION['password_recovery'] ?? null;
        if (
            !is_array($recovery)
            || ($recovery['step'] ?? '') !== 'answer'
            || empty($recovery['user_id'])
            || (int)($recovery['expires_at'] ?? 0) <= time()
        ) {
            unset($_SESSION['password_recovery']);
            $errors[] = 'Vui lòng bắt đầu lại quy trình khôi phục tài khoản.';
        } else {
            $userId = (int)$recovery['user_id'];
            $st = db()->prepare('SELECT security_question, security_answer_hash FROM users WHERE id = ?');
            $st->execute([$userId]);
            $user = $st->fetch();
            if (
                !$user
                || !isset(SECURITY_QUESTIONS[$user['security_question'] ?? ''])
                || empty($user['security_answer_hash'])
            ) {
                unset($_SESSION['password_recovery']);
                $errors[] = 'Tài khoản không còn đủ thông tin để khôi phục. Vui lòng liên hệ Admin.';
            } else {
                $pdo = db();
                try {
                    $pdo->beginTransaction();
                    $pdo->prepare(
                        'INSERT INTO password_reset_attempts (user_id) VALUES (?)
                         ON DUPLICATE KEY UPDATE user_id = VALUES(user_id)'
                    )->execute([$userId]);
                    $attemptSt = $pdo->prepare(
                        'SELECT failed_attempts, locked_until > UTC_TIMESTAMP() AS is_locked, locked_until
                         FROM password_reset_attempts WHERE user_id = ? FOR UPDATE'
                    );
                    $attemptSt->execute([$userId]);
                    $attempt = $attemptSt->fetch();

                    if ((int)$attempt['is_locked'] === 1) {
                        $pdo->rollBack();
                        $errors[] = 'Bạn đã thử sai quá nhiều lần. Vui lòng thử lại sau 15 phút.';
                    } else {
                        $failedAttempts = (int)$attempt['failed_attempts'];
                        if ($attempt['locked_until'] !== null) {
                            $failedAttempts = 0;
                            $pdo->prepare(
                                'UPDATE password_reset_attempts SET failed_attempts = 0, locked_until = NULL WHERE user_id = ?'
                            )->execute([$userId]);
                        }

                        $answer = normalize_security_answer(post_string('security_answer'));
                        $answerValid = !security_answer_errors($answer);
                        if (!$answerValid || !password_verify($answer, $user['security_answer_hash'])) {
                            $failedAttempts++;
                            $locked = $failedAttempts >= 5 ? 1 : 0;
                            $pdo->prepare(
                                'UPDATE password_reset_attempts
                                 SET failed_attempts = ?,
                                     locked_until = CASE WHEN ? = 1 THEN DATE_ADD(UTC_TIMESTAMP(), INTERVAL 15 MINUTE) ELSE NULL END
                                 WHERE user_id = ?'
                            )->execute([$failedAttempts, $locked, $userId]);
                            $pdo->commit();
                            $errors[] = $locked
                                ? 'Bạn đã thử sai quá nhiều lần. Vui lòng thử lại sau 15 phút.'
                                : 'Câu trả lời bảo mật không chính xác.';
                        } else {
                            $token = bin2hex(random_bytes(32));
                            $expiresAt = gmdate('Y-m-d H:i:s', time() + 900);
                            $pdo->prepare(
                                'INSERT INTO password_reset_tokens (user_id, token_hash, expires_at)
                                 VALUES (?, ?, ?)
                                 ON DUPLICATE KEY UPDATE token_hash = VALUES(token_hash), expires_at = VALUES(expires_at)'
                            )->execute([$userId, hash('sha256', $token), $expiresAt]);
                            $pdo->prepare('DELETE FROM password_reset_attempts WHERE user_id = ?')->execute([$userId]);
                            $pdo->commit();

                            session_regenerate_id(true);
                            $_SESSION['password_recovery'] = [
                                'step' => 'reset',
                                'user_id' => $userId,
                                'token' => $token,
                                'expires_at' => time() + 900,
                            ];
                            redirect('forgot_password.php');
                        }
                    }
                } catch (PDOException $ex) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    $errors[] = 'Không thể xác minh câu trả lời lúc này. Vui lòng thử lại sau.';
                }
            }
        }
    } elseif ($action === 'reset') {
        $recovery = $_SESSION['password_recovery'] ?? null;
        if (
            !is_array($recovery)
            || ($recovery['step'] ?? '') !== 'reset'
            || empty($recovery['user_id'])
            || empty($recovery['token'])
            || !isset($recovery['expires_at'])
            || (int)$recovery['expires_at'] <= time()
            || !preg_match('/^[a-f0-9]{64}$/', (string)$recovery['token'])
        ) {
            unset($_SESSION['password_recovery']);
            $errors[] = 'Quyền đặt lại mật khẩu đã hết hạn hoặc không hợp lệ. Vui lòng bắt đầu lại.';
        } else {
            $password = post_string('password');
            $confirm = post_string('confirm');
            $errors = array_merge($errors, password_policy_errors($password));
            if ($password !== $confirm) $errors[] = 'Mật khẩu nhập lại không khớp.';

            if (!$errors) {
                $pdo = db();
                try {
                    $pdo->beginTransaction();
                    $tokenSt = $pdo->prepare(
                        'SELECT token_hash FROM password_reset_tokens
                         WHERE user_id = ? AND token_hash = ? AND expires_at > UTC_TIMESTAMP()
                         FOR UPDATE'
                    );
                    $tokenSt->execute([
                        (int)$recovery['user_id'],
                        hash('sha256', (string)$recovery['token']),
                    ]);
                    if (!$tokenSt->fetch()) {
                        $pdo->rollBack();
                        unset($_SESSION['password_recovery']);
                        $errors[] = 'Quyền đặt lại mật khẩu đã hết hạn hoặc đã được sử dụng. Vui lòng bắt đầu lại.';
                    } else {
                        $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')
                            ->execute([password_hash($password, PASSWORD_DEFAULT), (int)$recovery['user_id']]);
                        $pdo->prepare('DELETE FROM password_reset_tokens WHERE user_id = ?')
                            ->execute([(int)$recovery['user_id']]);
                        $pdo->prepare('DELETE FROM password_reset_attempts WHERE user_id = ?')
                            ->execute([(int)$recovery['user_id']]);
                        $pdo->commit();

                        unset($_SESSION['password_recovery']);
                        session_regenerate_id(true);
                        flash('success', 'Mật khẩu đã được đổi thành công. Hãy đăng nhập bằng mật khẩu mới.');
                        redirect('login.php');
                    }
                } catch (PDOException $ex) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    $errors[] = 'Không thể cập nhật mật khẩu lúc này. Vui lòng thử lại.';
                }
            }
        }
    } else {
        $errors[] = 'Yêu cầu không hợp lệ. Vui lòng thử lại.';
    }
}

$recovery = $_SESSION['password_recovery'] ?? null;
$step = is_array($recovery) ? (string)($recovery['step'] ?? '') : '';
$question = null;
if ($step === 'reset' && (int)($recovery['expires_at'] ?? 0) <= time()) {
    unset($_SESSION['password_recovery']);
    $recovery = null;
    $step = '';
    $errors[] = 'Quyền đặt lại mật khẩu đã hết hạn. Vui lòng bắt đầu lại.';
}
if ($step === 'answer' && (int)($recovery['expires_at'] ?? 0) <= time()) {
    unset($_SESSION['password_recovery']);
    $recovery = null;
    $step = '';
    $errors[] = 'Phiên xác minh đã hết hạn. Vui lòng bắt đầu lại.';
}
if ($step === 'answer' && !empty($recovery['user_id'])) {
    $questionSt = db()->prepare('SELECT security_question FROM users WHERE id = ?');
    $questionSt->execute([(int)$recovery['user_id']]);
    $questionId = $questionSt->fetchColumn();
    if (isset(SECURITY_QUESTIONS[$questionId])) {
        $question = SECURITY_QUESTIONS[$questionId];
    } else {
        unset($_SESSION['password_recovery']);
        $recovery = null;
        $step = '';
        $errors[] = 'Tài khoản không còn đủ thông tin để khôi phục. Vui lòng liên hệ Admin.';
    }
}
$adminEmail = trim(ADMIN_CONTACT_EMAIL);
$adminPhone = trim(ADMIN_CONTACT_PHONE);
$pageTitle = 'Quên mật khẩu'; $minimal = true;
include __DIR__ . '/includes/header.php';
?>
<section class="auth card">
  <h1>Khôi phục mật khẩu</h1>
  <p class="sub">Không sử dụng email hoặc OTP tự động.</p>
  <?php if ($errors): ?><div class="alert err" role="alert"><?php foreach ($errors as $error): ?><div><?= e($error) ?></div><?php endforeach; ?></div><?php endif; ?>

  <?php if ($step === 'answer' && $question !== null): ?>
    <p class="hint" style="margin-bottom:14px">Câu hỏi bảo mật: <strong><?= e($question) ?></strong></p>
    <form method="post" class="js-validate" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="answer">
      <div class="field"><label for="security_answer">Câu trả lời</label>
        <input id="security_answer" name="security_answer" type="text" data-rule="security_answer" autocomplete="off" maxlength="72" required>
        <small>Tối đa 72 byte; không phân biệt chữ hoa/thường và gộp khoảng trắng.</small></div>
      <button class="btn block" type="submit">Xác minh câu trả lời</button>
    </form>
  <?php elseif ($step === 'reset'): ?>
    <p class="hint" style="margin-bottom:14px">Quyền đặt lại chỉ dùng một lần và hết hạn sau 15 phút.</p>
    <form method="post" class="js-validate" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="reset">
      <div class="field"><label for="password">Mật khẩu mới</label>
        <div class="pw-wrap"><input id="password" name="password" type="password" data-rule="password" autocomplete="new-password" required><button class="pw-toggle" type="button">Hiện</button></div>
        <small>Ít nhất 8 ký tự, gồm chữ hoa, chữ thường, số và ký tự đặc biệt; tối đa 72 byte.</small></div>
      <div class="field"><label for="confirm">Nhập lại mật khẩu mới</label>
        <input id="confirm" name="confirm" type="password" data-rule="password" data-match="password" autocomplete="new-password" required></div>
      <button class="btn block" type="submit">Đặt lại mật khẩu</button>
    </form>
  <?php else: ?>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="start">
      <div class="field"><label for="username">Tên đăng nhập</label>
        <input id="username" name="username" type="text" autocomplete="username" maxlength="50" required></div>
      <button class="btn block" type="submit">Tiếp tục</button>
    </form>
  <?php endif; ?>

  <div class="alert warn" style="margin-top:20px">
    <strong>Bạn không thể khôi phục tài khoản bằng câu hỏi bảo mật? Vui lòng liên hệ Admin để được hỗ trợ.</strong>
    <p>Admin chỉ hỗ trợ sau khi xác minh bạn là chủ tài khoản theo quy trình phù hợp. Website không tự động gửi thông báo và không tự đổi mật khẩu theo yêu cầu.</p>
    <?php if ($adminEmail !== '' || $adminPhone !== ''): ?>
      <?php if ($adminEmail !== ''): ?><p>Email liên hệ: <a href="mailto:<?= e($adminEmail) ?>"><?= e($adminEmail) ?></a></p><?php endif; ?>
      <?php if ($adminPhone !== ''): ?><p>Điện thoại: <a href="tel:<?= e($adminPhone) ?>"><?= e($adminPhone) ?></a></p><?php endif; ?>
    <?php else: ?>
      <p>Chủ website chưa cấu hình thông tin liên hệ. Có thể điền ADMIN_CONTACT_EMAIL hoặc ADMIN_CONTACT_PHONE trong config/database.php.</p>
    <?php endif; ?>
  </div>
  <p class="switch"><a href="<?= url('login.php') ?>">Quay lại đăng nhập</a></p>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>

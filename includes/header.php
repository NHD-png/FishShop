<?php
// Biến dùng: $pageTitle (tiêu đề), $bodyClass (class cho <body>), $minimal (true = không hiện thanh điều hướng)
$pageTitle = $pageTitle ?? 'WebBanCa';
$bodyClass = $bodyClass ?? '';
$minimal   = $minimal ?? false;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> | WebBanCa</title>
<link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body class="<?= e($bodyClass) ?>" data-base="<?= e(BASE_URL) ?>" data-csrf="<?= e(csrf_token()) ?>">
<?php if (!$minimal) include __DIR__ . '/navbar.php'; ?>
<div id="toasts" aria-live="polite">
<?php foreach (take_flash() as $f): ?>
  <div class="toast <?= $f['type'] === 'success' ? 'ok' : 'err' ?>" role="status"><?= e($f['msg']) ?></div>
<?php endforeach; ?>
</div>
<main class="wrap">

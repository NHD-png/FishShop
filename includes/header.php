<?php
// Biến dùng: $pageTitle (tiêu đề), $bodyClass (class cho <body>), $minimal (true = không hiện thanh điều hướng)
$pageTitle = $pageTitle ?? 'Fish Shop';
$bodyClass = $bodyClass ?? '';
$minimal   = $minimal ?? false;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> | Fish Shop</title>
<link rel="stylesheet" href="<?= url('assets/css/style.css') ?>?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
<link rel="stylesheet" href="<?= url('assets/css/reference-theme.css') ?>?v=<?= filemtime(__DIR__ . '/../assets/css/reference-theme.css') ?>">
<link rel="stylesheet" href="<?= url('assets/css/maps.css') ?>?v=<?= filemtime(__DIR__ . '/../assets/css/maps.css') ?>">
<link rel="stylesheet" href="<?= url('assets/css/vouchers.css') ?>?v=<?= filemtime(__DIR__ . '/../assets/css/vouchers.css') ?>">
<link rel="stylesheet" href="<?= url('assets/css/slideshow.css') ?>">
<?php foreach ($pageStyles ?? [] as $stylesheet): ?>
<link rel="stylesheet" href="<?= e(url($stylesheet)) ?>?v=<?= filemtime(__DIR__ . '/../' . $stylesheet) ?>">
<?php endforeach; ?>
</head>
<body class="<?= e($bodyClass) ?>" data-base="<?= e(BASE_URL) ?>" data-csrf="<?= e(csrf_token()) ?>">
<?php include __DIR__ . '/navbar.php'; ?>
<div id="toasts" aria-live="polite">
<?php foreach (take_flash() as $f): ?>
  <div class="toast <?= $f['type'] === 'success' ? 'ok' : ($f['type'] === 'info' ? 'info' : 'err') ?>" role="status"><?= e($f['msg']) ?></div>
<?php endforeach; ?>
</div>
<main class="wrap">

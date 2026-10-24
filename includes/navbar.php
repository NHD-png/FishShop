<?php
$u = current_user();
$q = $_GET['q'] ?? '';
?>
<header class="topbar">
  <div class="wrap bar">
    <a class="brand" href="<?= url('index.php') ?>">
      <img src="<?= url('assets/images/logo.png') ?>" alt="" width="40" height="40">
      <span>WebBanCa</span>
    </a>
    <button class="menu-btn" type="button" aria-label="Mở menu" aria-expanded="false" aria-controls="nav-main">☰</button>
    <nav id="nav-main" class="nav">
      <form class="nav-search" action="<?= url('index.php') ?>" method="get" role="search">
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Tìm cá..." aria-label="Tìm cá">
        <button class="btn sm" type="submit">Tìm</button>
      </form>
      <a href="<?= url('index.php') ?>">Trang chủ</a>
      <a href="<?= url('purchase_history.php') ?>">Đơn của tôi</a>
      <?php if ($u && $u['role'] === 'admin'): ?><a href="<?= url('admin/index.php') ?>">Quản trị</a><?php endif; ?>
      <a class="cart-link" href="<?= url('cart.php') ?>">Giỏ hàng <span id="cart-badge" class="badge-n"><?= $u ? cart_count((int)$u['id']) : 0 ?></span></a>
      <?php if ($u): ?>
        <a class="user-chip" href="<?= url('profile.php') ?>">
          <img src="<?= e(file_url($u['avatar'], 'assets/images/no-image.svg')) ?>" alt="" width="28" height="28">
          <?= e($u['fullname']) ?>
        </a>
        <form action="<?= url('logout.php') ?>" method="post"><?= csrf_field() ?><button class="btn ghost sm" type="submit">Đăng xuất</button></form>
      <?php else: ?>
        <a class="btn sm" href="<?= url('login.php') ?>">Đăng nhập</a>
      <?php endif; ?>
    </nav>
  </div>
</header>

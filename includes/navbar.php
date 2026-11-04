<?php
$u = current_user();
$searchText = is_string($_GET['q'] ?? null) ? $_GET['q'] : '';
$currentPage = basename($_SERVER['SCRIPT_NAME']);
$navCategories = isset($cats) && is_array($cats) ? $cats : db()->query("SELECT id,name FROM categories WHERE status='active' ORDER BY name")->fetchAll();
$catalogActive = in_array($currentPage, ['products.php', 'product_detail.php'], true);
$navCategoryId = $currentPage === 'products.php' ? (int)($cat ?? 0) : ($currentPage === 'product_detail.php' ? (int)($p['category_id'] ?? 0) : -1);
?>
<header class="top">
  <div class="wrap bar">
    <a class="logo" href="<?= url('index.php') ?>"><img class="brand-avatar" src="<?= url('assets/images/fish-shop-logo.jpg') ?>" alt="" width="44" height="44"><span>Fish Shop<small>THẾ GIỚI CÁ CẢNH</small></span></a>
    <form class="header-search" action="<?= url($currentPage === 'products.php' ? 'products.php' : 'index.php') ?>" method="get" role="search">
      <input class="search" type="search" name="q" value="<?= e($searchText) ?>" placeholder="Bạn đang tìm cá gì?" aria-label="Tìm sản phẩm">
      <button type="submit" aria-label="Tìm kiếm"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/></svg></button>
    </form>
    <a class="header-support" href="#ho-tro"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 3h4l2 5-3 2c2 3 3 4 6 6l2-3 5 2v4c0 2-2 2-3 2C10 20 4 14 3 6c0-1 0-3 2-3Z"/></svg><span>Hỗ trợ khách hàng<strong><?= e(ADMIN_CONTACT_PHONE) ?></strong></span></a>
    <div class="header-actions">
      <a class="header-action" data-nav="cart" href="<?= url('cart.php') ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3h2l3 12h11l2-8H6"/><circle cx="9" cy="20" r="1"/><circle cx="18" cy="20" r="1"/></svg><span>Giỏ hàng</span><b id="cart-badge" class="badge-n"><?= current_cart_count() ?></b></a>
      <?php if ($u): ?>
        <a class="account-avatar" href="<?= url('profile.php') ?>" aria-label="Thông tin tài khoản">
          <img src="<?= e(file_url($u['avatar'] ?? null, 'assets/images/avatar-default.svg')) ?>" alt="Ảnh đại diện của <?= e($u['fullname']) ?>" width="40" height="40">
        </a>
      <?php endif; ?>
      <div class="account-links">
      <?php if ($u): ?>
        <a href="<?= url('profile.php') ?>"><?= e($u['fullname']) ?></a>
        <form action="<?= url('logout.php') ?>" method="post"><?= csrf_field() ?><button class="link-btn" type="submit">Đăng xuất</button></form>
      <?php else: ?>
        <a href="<?= url('login.php') ?>">Đăng nhập</a><a href="<?= url('register.php') ?>">Đăng ký tài khoản</a>
      <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="navigation"><div class="wrap">
    <button class="menu-toggle menu-btn" type="button" aria-expanded="false" aria-controls="nav-main">☰ Danh mục</button>
    <nav id="nav-main" class="main-nav" aria-label="Điều hướng chính">
      <a class="<?= $currentPage === 'index.php' ? 'on' : '' ?>" href="<?= url('index.php') ?>">Trang chủ</a>
      <details class="product-menu" data-product-menu>
        <summary class="<?=$catalogActive ? 'on' : ''?>">Danh sách sản phẩm <span class="menu-chevron" aria-hidden="true"></span></summary>
        <nav class="product-dropdown" aria-label="Danh mục sản phẩm">
          <a class="<?=$catalogActive && $navCategoryId === 0 ? 'on' : ''?>" href="<?=url('products.php')?>">Tất cả</a>
          <?php foreach ($navCategories as $navCategory): ?>
            <a class="<?=$navCategoryId === (int)$navCategory['id'] ? 'on' : ''?>" href="<?=url('products.php?cat='.(int)$navCategory['id'])?>"><?=e($navCategory['name'])?></a>
          <?php endforeach; ?>
        </nav>
      </details>
      <a class="<?= $currentPage === 'purchase_history.php' ? 'on' : '' ?>" href="<?= url('purchase_history.php') ?>">Đơn của tôi</a>
      <a class="<?= $currentPage === 'wheel.php' ? 'on' : '' ?>" href="<?= url('wheel.php') ?>">Vòng quay</a>
      <a class="<?= $currentPage === 'rewards.php' ? 'on' : '' ?>" href="<?= url('rewards.php') ?>">Kho quà</a>
      <?php if ($u): ?><a class="<?= $currentPage === 'wallet.php' ? 'on' : '' ?>" href="<?= url('wallet.php') ?>">Nạp ví</a><?php endif; ?>
      <a class="<?= $currentPage === 'profile.php' ? 'on' : '' ?>" href="<?= url('profile.php') ?>">Tài khoản</a>
      <a class="<?= $currentPage === 'support.php' ? 'on' : '' ?>" href="<?= url('support.php') ?>">Hỗ trợ</a>
    </nav>
  </div></div>
</header>

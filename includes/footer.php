</main>
<?php
require_once __DIR__ . '/maps.php';
$footerMapConfig = fishshop_maps_config();
$storePoint = fishshop_store_point($footerMapConfig);
?>
<footer class="footer home-footer">
  <?php if (empty($minimal) && $storePoint !== null): ?>
  <section class="wrap store-location" data-map-widget="store"
      data-latitude="<?= e($storePoint['latitude']) ?>" data-longitude="<?= e($storePoint['longitude']) ?>"
      data-label="<?= e($footerMapConfig['store_location_name']) ?>" data-zoom="16" aria-labelledby="store-map-title">
    <div class="store-location-copy">
      <p class="map-eyebrow">TÌM ĐẾN CỬA HÀNG</p>
      <h2 id="store-map-title">Ghé thăm Fish Shop</h2>
      <p class="store-location-name"><?= e($footerMapConfig['store_location_name']) ?></p>
      <p class="store-location-address"><?= e($footerMapConfig['store_address']) ?></p>
      <a class="btn ghost" href="<?= e(fishshop_store_directions_url($footerMapConfig)) ?>" target="_blank" rel="noopener noreferrer">Chỉ đường đến cửa hàng</a>
    </div>
    <div class="store-map-wrap">
      <div class="fishshop-map store-map" data-map-canvas role="region" aria-label="Bản đồ vị trí cửa hàng" tabindex="0"></div>
      <p class="map-error" data-map-error role="status" hidden></p>
      <noscript><p class="map-hint">Dùng nút chỉ đường để mở vị trí trong Google Maps.</p></noscript>
    </div>
  </section>
  <?php endif; ?>
  <div class="wrap footer-grid" id="ho-tro">
    <div class="footer-brand"><a class="logo" href="<?= url('index.php') ?>"><img class="brand-avatar" src="<?= url('assets/images/fish-shop-logo.jpg') ?>" alt="" width="44" height="44">Fish Shop</a><p>Một góc thiên nhiên. Một chút bình yên.<br>Khám phá những sắc màu của thế giới cá cảnh.</p></div>
    <nav aria-label="Cửa hàng"><h3>Cửa hàng</h3><a href="<?= url('index.php') ?>">Trang chủ</a><a href="<?= url('products.php') ?>">Danh sách sản phẩm</a><a href="<?= url('cart.php') ?>">Giỏ hàng</a></nav>
    <nav aria-label="Tài khoản"><h3>Tài khoản</h3><a href="<?= url('profile.php') ?>">Thông tin cá nhân</a><a href="<?= url('purchase_history.php') ?>">Đơn của tôi</a><a href="<?= url('forgot_password.php') ?>">Khôi phục mật khẩu</a></nav>
    <section class="footer-support" aria-label="Hỗ trợ khách hàng">
      <div class="footer-support-grid">
        <nav class="footer-support-links" aria-label="Hỗ trợ bạn">
          <h3>Hỗ trợ bạn</h3>
          <a href="<?= url('support.php#faq') ?>">Câu hỏi thường gặp</a>
          <a href="<?= url('cart.php') ?>">Giỏ hàng của bạn</a>
          <a href="<?= url('products.php') ?>">Danh sách sản phẩm</a>
        </nav>
        <div class="footer-contact" aria-label="Liên hệ và chính sách">
          <h3>Liên hệ &amp; chính sách</h3>
          <div class="footer-hotline">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 3h4l2 5-3 2c2 3 3 4 6 6l2-3 5 2v4c0 2-2 2-3 2C10 20 4 14 3 6c0-1 0-3 2-3Z"/></svg>
            <div><span>Hotline</span><strong><a href="tel:<?= e(ADMIN_CONTACT_PHONE) ?>"><?= e(ADMIN_CONTACT_PHONE) ?></a></strong></div>
          </div>
          <p class="footer-policy">Giao cá sống trong 24 giờ nội thành Vĩnh Long</p>
          <p class="footer-policy">Đổi trả trong 48 giờ nếu cá chết do vận chuyển</p>
          <?php if (ADMIN_CONTACT_EMAIL !== ''): ?><p class="footer-email">Email: <a href="mailto:<?= e(ADMIN_CONTACT_EMAIL) ?>"><?= e(ADMIN_CONTACT_EMAIL) ?></a></p><?php endif; ?>
        </div>
      </div>
    </section>
  </div>
</footer>
<script src="<?= url('assets/js/main.js') ?>"></script>
<script type="application/json" id="fishshop-map-config"><?= json_encode(['tileUrl' => $footerMapConfig['tile_url']], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<script src="<?= url('assets/js/maps.js') ?>?v=<?= filemtime(__DIR__ . '/../assets/js/maps.js') ?>" defer></script>
</body>
</html>

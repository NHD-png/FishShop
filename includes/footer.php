</main>
<footer class="footer home-footer" id="ho-tro">
  <div class="wrap footer-grid">
    <div class="footer-brand"><a class="logo" href="<?= url('index.php') ?>"><img class="brand-avatar" src="<?= url('assets/images/fish-shop-logo.jpg') ?>" alt="" width="44" height="44">Fish Shop</a><p>Một góc thiên nhiên. Một chút bình yên.<br>Khám phá những sắc màu của thế giới cá cảnh.</p></div>
    <nav aria-label="Cửa hàng"><h3>CỬA HÀNG</h3><a href="<?= url('index.php') ?>">Trang chủ</a><a href="<?= url('index.php#san-pham') ?>">Danh sách sản phẩm</a><a href="<?= url('cart.php') ?>">Giỏ hàng</a></nav>
    <nav aria-label="Tài khoản"><h3>TÀI KHOẢN</h3><a href="<?= url('profile.php') ?>">Thông tin cá nhân</a><a href="<?= url('purchase_history.php') ?>">Đơn của tôi</a><a href="<?= url('forgot_password.php') ?>">Khôi phục mật khẩu</a></nav>
    <div><h3>HỖ TRỢ KHÁCH HÀNG</h3><p>Theo dõi đơn mua và thông tin thanh toán trong mục Đơn của tôi.</p><?php if (ADMIN_CONTACT_PHONE !== ''): ?><p>Điện thoại: <?= e(ADMIN_CONTACT_PHONE) ?></p><?php endif; ?><?php if (ADMIN_CONTACT_EMAIL !== ''): ?><p>Email: <?= e(ADMIN_CONTACT_EMAIL) ?></p><?php endif; ?></div>
  </div>
</footer>
<script src="<?= url('assets/js/main.js') ?>"></script>
</body>
</html>

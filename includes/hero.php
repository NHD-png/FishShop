<?php
// Banner presentation only; no product records are created or changed.
$heroImages = [
    ['dolia.jpg', 'Dolia — Liên Quân Mobile'],
    ['gamebanana.jpg', 'Ảnh từ GameBanana'],
    ['jotaro.jpg', 'Jotaro và cá heo'],
    ['ca-vien.jpg', 'Cá viên chiên'],
    ['tahm-kench.jpg', 'Tahm Kench — Liên Minh Huyền Thoại'],
];
?>
<section class="hero slideshow" id="slideshow" aria-label="Banner cửa hàng" aria-roledescription="trình chiếu">
  <div class="hero-slides">
    <?php foreach ($heroImages as $i => [$image, $description]): ?>
    <div class="hero-slide <?= $i === 0 ? 'is-active' : '' ?>" role="group" aria-roledescription="slide" aria-label="<?= $i + 1 ?> / 5" aria-hidden="<?= $i === 0 ? 'false' : 'true' ?>" <?= $i === 0 ? '' : 'inert' ?>>
      <img class="slide-image" src="<?= url('assets/images/slides/' . $image) ?>" alt="<?= e($description) ?>" <?= $i === 0 ? 'fetchpriority="high"' : '' ?>>
      <div class="hero-content">
        <p class="hero-label">THẾ GIỚI CÁ CẢNH</p>
        <?php if ($i === 0): ?><h1 class="slide-title">Một góc thiên nhiên.<br>Một chút bình yên.</h1><?php else: ?><h2 class="slide-title">Một góc thiên nhiên.<br>Một chút bình yên.</h2><?php endif; ?>
        <p>Khám phá những sắc màu sống động và tìm loài cá bạn yêu thích cho không gian của mình.</p>
        <a class="btn" href="#san-pham">Khám phá sản phẩm <span aria-hidden="true">→</span></a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <div class="slide-controls"><div class="slide-dots" aria-label="Chọn ảnh banner">
    <?php foreach ($heroImages as $i => $image): ?><button type="button" class="slide-dot <?= $i === 0 ? 'is-active' : '' ?>" aria-label="Xem ảnh <?= $i + 1 ?>" aria-pressed="<?= $i === 0 ? 'true' : 'false' ?>"><?= sprintf('%02d', $i + 1) ?></button><?php endforeach; ?>
  </div></div>
  <p id="slide-status" class="slide-status" aria-live="polite"></p>
</section>
<script src="<?= url('assets/js/slideshow.js') ?>" defer></script>

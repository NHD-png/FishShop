<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Hỗ trợ khách hàng';
$pageStyles = ['assets/css/support.css'];
include __DIR__ . '/includes/header.php';
?>
<section class="page-intro support-intro">
  <h1>Hỗ trợ khách hàng</h1>
  <p>Thông tin đặt hàng, chăm sóc cá và chính sách giao nhận của Fish Shop.</p>
</section>

<div class="support-sections">
  <section class="card support-card" id="faq" aria-labelledby="faq-title">
    <h2 id="faq-title">Câu hỏi thường gặp</h2>
    <details>
      <summary>Làm sao để đặt hàng?</summary>
      <p>Chọn sản phẩm bạn cần, thêm vào giỏ hàng, rồi điền thông tin nhận hàng ở bước thanh toán.</p>
    </details>
    <details>
      <summary>Có thể xem trạng thái đơn hàng ở đâu?</summary>
      <p>Mở mục “Đơn của tôi” trên thanh điều hướng để xem thông tin và trạng thái các đơn đã đặt.</p>
    </details>
    <details>
      <summary>Nếu cá gặp vấn đề sau khi giao thì làm sao?</summary>
      <p>Liên hệ hotline trong vòng 48 giờ kể từ khi nhận hàng nếu cá chết do vận chuyển để được hỗ trợ đổi trả.</p>
    </details>
  </section>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

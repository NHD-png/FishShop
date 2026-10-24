WEBBANCA - HƯỚNG DẪN CÀI ĐẶT VÀ CHẠY
=====================================

1. CÀI ĐẶT
----------
1) Cài XAMPP (Windows), mở XAMPP Control Panel và Start "Apache" và "MySQL".
2) Chép nguyên thư mục WebBanCa vào:  C:\xampp\htdocs\WebBanCa\
3) Mở http://localhost/phpmyadmin > tab "Nhập" (Import) > Chọn tệp database\WebBanCa.sql > Thực hiện.
   (File tự tạo database WebBanCa và 7 bảng; KHÔNG có dữ liệu mẫu.)
4) Kiểm tra config\database.php: mặc định root, mật khẩu rỗng (đúng với XAMPP).
   Nếu bạn đặt thư mục tên khác, đổi BASE_URL trong file này.
5) Mở http://localhost/WebBanCa/  (chưa đăng nhập sẽ tự chuyển sang trang đăng nhập).

2. TẠO TÀI KHOẢN QUẢN TRỊ (an toàn, không có mật khẩu mặc định)
-----------------------------------------------------------------
1) Vào http://localhost/WebBanCa/register.php và đăng ký một tài khoản bình thường.
2) Mở phpMyAdmin > database WebBanCa > bảng users > chọn "Sửa" ở dòng tài khoản đó.
3) Đổi cột role từ customer thành admin > Thực hiện.
4) Đăng xuất rồi đăng nhập lại: trên thanh điều hướng sẽ có mục "Quản trị".
(Không có trang nào cho phép tự nâng quyền; role chỉ đổi được trong database.)

3. ẢNH QR THANH TOÁN
--------------------
Đặt ảnh QR thật của bạn tại:  C:\xampp\htdocs\WebBanCa\payment\NapTienDi.jpg
(đúng tên, đúng chữ hoa/thường). Chưa có file thì trang thanh toán hiện thông báo:
"Chưa có ảnh QR thanh toán. Vui lòng bổ sung file payment/NapTienDi.jpg."
Thư mục uploads\products và uploads\avatars đã có sẵn; PHP tự lưu ảnh vào đó.

4. SƠ ĐỒ QUAN HỆ CSDL
---------------------
users 1 --- 1 cart 1 --- n cart_items n --- 1 products n --- 1 categories
users 1 --- n orders 1 --- n order_items n --- 1 products
- cart.user_id UNIQUE: mỗi người dùng một giỏ hàng.
- cart_items UNIQUE(cart_id, product_id): không trùng dòng sản phẩm trong giỏ.
- order_items.price: giá tại thời điểm mua.
- order_items.product_id và orders.user_id dùng ON DELETE RESTRICT: không xóa được
  sản phẩm/người dùng đã có đơn, nên lịch sử mua hàng không bị mất. Sản phẩm được "Ngừng bán" thay vì xóa.

5. LUỒNG HOẠT ĐỘNG
------------------
- Chưa đăng nhập: chỉ vào được login.php và register.php, mọi trang khác chuyển về login.php.
- Thêm cá vào giỏ (bảng cart, cart_items) > Giỏ hàng > Xác nhận mua hàng (địa chỉ, SĐT)
  > actions/place_order.php (transaction: kiểm tra tồn kho, tính tổng ở PHP, tạo orders + order_items,
  trừ kho, xóa giỏ) > payment.php hiện mã đơn, tổng tiền, QR.
- Trạng thái thanh toán ban đầu: unpaid. Khách bấm "Tôi đã chuyển khoản" > pending_verification.
  CHỈ quản trị viên (admin/orders.php) mới đặt được paid.
- Hủy đơn trong trang quản trị sẽ hoàn lại tồn kho.

6. KIỂM TRA TỪNG CHỨC NĂNG
--------------------------
[ ] Mở /WebBanCa/ khi chưa đăng nhập: bị chuyển về trang đăng nhập.
[ ] Đăng ký: tên trùng báo lỗi; mật khẩu trong bảng users là chuỗi băm ($2y$...).
[ ] Đăng nhập sai: báo lỗi; đúng: vào trang chủ, thấy tên + nút Đăng xuất.
[ ] Đăng xuất rồi bấm nút Back của trình duyệt, tải lại: vẫn bị chuyển về đăng nhập.
[ ] Admin: Danh mục > thêm 1 danh mục; Sản phẩm > Thêm sản phẩm kèm ảnh; kiểm tra file xuất hiện
    trong uploads\products và cột image trong database là uploads/products/xxxx.jpg.
[ ] Trang chủ: sản phẩm hiện đúng; thử tìm kiếm, lọc danh mục, sắp xếp.
[ ] Thêm vào giỏ (từ trang chủ và trang chi tiết); số lượng vượt tồn kho bị từ chối.
[ ] Đăng xuất, đăng nhập lại: giỏ hàng vẫn còn.
[ ] Tăng/giảm/xóa trong giỏ; tổng tiền đúng.
[ ] Đặt hàng: xuất hiện trang thanh toán; tồn kho giảm; giỏ trống; đơn có trong "Đơn của tôi".
[ ] Đặt file payment\NapTienDi.jpg: ảnh QR hiển thị.
[ ] Đăng nhập tài khoản khác, mở purchase_history.php?id=<mã đơn người khác>: bị chặn.
[ ] Tài khoản customer mở /WebBanCa/admin/: bị chuyển về trang chủ kèm thông báo.
[ ] Admin xác nhận "Đã thanh toán" trong admin/orders.php; khách thấy trạng thái cập nhật.

7. LỖI THƯỜNG GẶP
-----------------
- "Không kết nối được MySQL": chưa Start MySQL, chưa nhập SQL, hoặc sai thông tin trong config\database.php.
- Trang trắng / lỗi 404: sai vị trí thư mục (phải là htdocs\WebBanCa) hoặc sai BASE_URL.
- CSS không áp dụng: BASE_URL sai so với tên thư mục thật.
- Tải ảnh không được: ảnh > 2MB, không phải JPG/PNG/WEBP, hoặc thư mục uploads bị khóa ghi.
  Nếu ảnh quá lớn so với giới hạn của PHP, tăng upload_max_filesize và post_max_size trong php.ini rồi restart Apache.
- Không thấy mục Quản trị: tài khoản chưa đổi role thành admin (xem mục 2) hoặc chưa đăng nhập lại.
- Tên bảng/cột lỗi chữ hoa/thường: trên Windows không phân biệt, nhưng nên giữ đúng như file SQL.

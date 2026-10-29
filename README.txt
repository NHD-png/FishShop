WEBBANCA - HƯỚNG DẪN CÀI ĐẶT VÀ CHẠY
=====================================

1. CÀI ĐẶT
----------
1) Cài XAMPP (Windows), mở XAMPP Control Panel và Start "Apache" và "MySQL".
2) Chép nguyên thư mục WebBanCa vào:  C:\xampp\htdocs\WebBanCa\
3) Với cài đặt mới, tạo/chọn database WebBanCa trong phpMyAdmin rồi nhập database\webbanca.sql.
   Đây là SQL dump có schema và dữ liệu; không nhập đè lên database website đang sử dụng.
   Nếu website đã có dữ liệu, sao lưu trước và chỉ chạy migration còn thiếu, không nhập lại dump.
4) Sao lưu database; chọn database đang dùng và nhập database\account_recovery_migration.sql
   một lần để bật email và khôi phục mật khẩu (xem mục 8).
5) Kiểm tra config\database.php: mặc định root, mật khẩu rỗng (đúng với XAMPP).
   Nếu bạn đặt thư mục tên khác, đổi BASE_URL trong file này.
6) Mở http://localhost/WebBanCa/  (chưa đăng nhập sẽ tự chuyển sang trang đăng nhập).

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
users 1 --- 1 customer_ranks; users 1 --- n rank_order_earnings n --- 1 orders
- cart.user_id UNIQUE: mỗi người dùng một giỏ hàng.
- cart_items UNIQUE(cart_id, product_id): không trùng dòng sản phẩm trong giỏ.
- order_items.price: giá tại thời điểm mua.
- customer_ranks.user_id UNIQUE: mỗi tài khoản có tối đa một hồ sơ Rank; tổng chi tiêu hợp lệ
  giữ cả phần dư dưới 1.000 đồng để cộng EXP chính xác qua nhiều đơn.
- rank_order_earnings.order_id PRIMARY KEY: một đơn chỉ được cộng một lần; khoản điểm bị vô hiệu
  khi đơn không còn vừa completed vừa paid.
- order_items.product_id và orders.user_id dùng ON DELETE RESTRICT: không xóa được
  sản phẩm/người dùng đã có đơn, nên lịch sử mua hàng không bị mất. Sản phẩm được "Ngừng bán" thay vì xóa.

5. LUỒNG HOẠT ĐỘNG
------------------
- Chưa đăng nhập: vào được login.php, register.php và forgot_password.php; các trang cần tài khoản khác chuyển về login.php.
- Thêm cá vào giỏ (bảng cart, cart_items) > Giỏ hàng > Xác nhận mua hàng (địa chỉ, SĐT)
  > actions/place_order.php (transaction: kiểm tra tồn kho, tính tổng ở PHP, tạo orders + order_items,
  trừ kho, xóa giỏ) > payment.php hiện mã đơn, tổng tiền, QR.
- Trạng thái thanh toán ban đầu: unpaid. Khách bấm "Tôi đã chuyển khoản" > pending_verification.
  CHỈ quản trị viên (admin/orders.php) mới đặt được paid.
- Hủy đơn trong trang quản trị sẽ hoàn lại tồn kho.
- Rank chỉ nhận EXP khi admin chọn cả "Đã hoàn thành" và "Đã thanh toán"; đơn cũ thỏa điều kiện
  được backfill khi chạy rank_migration.sql. Hiện website chưa có trạng thái/luồng hoàn tiền riêng;
  khi đơn bị hủy hoặc chuyển khỏi trạng thái hợp lệ, điểm của đơn đó được điều chỉnh lại.

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
[ ] Admin đặt đơn sang "Đã hoàn thành" đồng thời giữ "Đã thanh toán"; hồ sơ khách nhận EXP đúng theo
    tổng order_items (100.000 đồng = 100 EXP), gồm phần dư tích lũy giữa các đơn nhỏ.
[ ] Thử tổng 500 EXP, 20.000 EXP và 100.000 EXP để kiểm tra các mốc Vàng, Cao Thủ, Thách Đấu;
    lưu lại trạng thái đơn lần nữa không cộng điểm trùng.
[ ] Đơn pending/unpaid hoặc cancelled không được tính; hủy đơn đủ điều kiện sẽ rút khoản điểm đó.

7. LỖI THƯỜNG GẶP
-----------------
- "Không kết nối được MySQL": chưa Start MySQL, chưa nhập SQL, hoặc sai thông tin trong config\database.php.
- Trang trắng / lỗi 404: sai vị trí thư mục (phải là htdocs\WebBanCa) hoặc sai BASE_URL.
- CSS không áp dụng: BASE_URL sai so với tên thư mục thật.
- Tải ảnh không được: ảnh > 2MB, không phải JPG/PNG/WEBP, hoặc thư mục uploads bị khóa ghi.
  Nếu ảnh quá lớn so với giới hạn của PHP, tăng upload_max_filesize và post_max_size trong php.ini rồi restart Apache.
- Không thấy mục Quản trị: tài khoản chưa đổi role thành admin (xem mục 2) hoặc chưa đăng nhập lại.
- Tên bảng/cột lỗi chữ hoa/thường: trên Windows không phân biệt, nhưng nên giữ đúng như file SQL.

8. NÂNG CẤP EMAIL VÀ KHÔI PHỤC MẬT KHẨU
---------------------------------------
Migration nằm ở database\account_recovery_migration.sql. Trước khi chạy:
1) Sao lưu database webbanca/WebBanCa trong phpMyAdmin (tab Export).
2) Chọn đúng database đang được cấu hình trong config\database.php.
3) Chọn tab Import, nhập account_recovery_migration.sql và chạy một lần.
Migration chỉ thêm cột email/câu hỏi bảo mật và hai bảng phục vụ giới hạn thử,
token đặt lại; không xóa hoặc sửa tài khoản, đơn hàng, giỏ hàng, sản phẩm, Rank hay EXP.
Không chạy lại migration sau khi đã thêm các cột/bảng.

Email là bắt buộc với tài khoản đăng ký mới; tài khoản hiện hữu được giữ email NULL
và vẫn đăng nhập như trước. Sau khi đăng nhập, tài khoản cũ có thể thiết lập câu hỏi
bảo mật ở trang Thông tin cá nhân bằng cách nhập mật khẩu hiện tại. Câu trả lời được
chuẩn hóa và chỉ lưu dưới dạng password_hash(); câu trả lời tối đa 72 byte để tránh
bị cắt cụt bởi thuật toán băm mật khẩu mặc định của PHP. Mật khẩu cũng giới hạn 72 byte
vì XAMPP PHP 8.0 đang dùng bcrypt làm PASSWORD_DEFAULT.

Quên mật khẩu dùng câu hỏi bảo mật, không gửi email/OTP. Sau 5 câu trả lời sai,
tài khoản bị giới hạn thử trong 15 phút. Quyền đặt lại được lưu phía máy chủ, hết hạn
sau 15 phút và chỉ dùng một lần. Để hiển thị thông tin hỗ trợ Admin, chủ website tự
điền ADMIN_CONTACT_EMAIL và/hoặc ADMIN_CONTACT_PHONE trong config\database.php.
Website không gửi thông báo tự động và Admin chỉ hỗ trợ sau khi xác minh người yêu cầu
là chủ tài khoản theo quy trình phù hợp.

Kiểm tra sau khi chạy migration:
[ ] Đăng ký với mật khẩu hợp lệ; thử mật khẩu yếu, xác nhận sai, username/email trùng.
[ ] Đăng ký để trống điện thoại/địa chỉ; kiểm tra security_answer_hash là chuỗi băm,
    không phải câu trả lời gốc.
[ ] Với tài khoản đã đăng nhập, đặt câu hỏi bảo mật trong Thông tin cá nhân; thử sai
    mật khẩu hiện tại để xác nhận không thể thay đổi câu trả lời.
[ ] Quên mật khẩu: thử username có câu hỏi, đáp án sai 5 lần rồi kiểm tra bị khóa
    15 phút; truy cập trực tiếp forgot_password.php?action=reset khi chưa xác minh.
[ ] Trả lời đúng, đặt mật khẩu mới sai chính sách/xác nhận không khớp, rồi đặt hợp lệ;
    đăng nhập bằng mật khẩu mới và thử sử dụng quyền reset lần nữa.
[ ] Đăng nhập Admin hiện tại; kiểm tra giỏ hàng, đơn hàng, Rank và EXP không đổi.

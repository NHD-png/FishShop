FISH SHOP - CAI DAT VA KIEM TRA
=============================

1. Bat Apache va MySQL trong XAMPP (PHP 8.2, curl, pdo_mysql, mbstring).
2. Dat code tai C:\xampp\htdocs\FishShop. Neu dung URL /Quantri thi giu thu muc
   C:\xampp\htdocs\Quantri chuyen tiep ve code admin trong FishShop.
3. Cai moi: tao/chon database WebBanCa va import database\WebBanCa.sql.
   Khong import de dump vao database dang co du lieu. Sao luu truoc khi nang cap.
4. Chinh ket noi va BASE_URL trong config\database.php neu khac mac dinh.
5. Chep .env rieng sang may moi hoac tao tu .env.example va dien cau hinh that.
   .env khong di theo GitHub; SQL khong chua cau hinh Gmail/Firebase/Cloudinary.
   Ten cac bien can dien da nam trong .env.example. Khong push .env len Git.
6. Mo http://localhost/FishShop/ de xem hang; chi dang nhap khi dat hang/xem
   tai khoan. Quan tri dang nhap tai http://localhost/Quantri/login.php.

DATABASE CU
-----------
Neu thieu tinh nang, chay migration con thieu tu thu muc goc FishShop:
C:\xampp\php\php.exe database\migrate_delivery_location.php
C:\xampp\php\php.exe database\migrate_vouchers.php
OTP va Google: cac file database\RegistrationOtp.sql, database\FirebaseLogin.sql
chi tao bang con thieu, khong xoa tai khoan. Ma giam gia duoc tao trong quan tri.

KIEM TRA TREN CHINH MAY BI LOI
----------------------------
OTP/cau hinh/database:
C:\xampp\php\php.exe C:\xampp\htdocs\FishShop\database\check_setup.php

Google (kiem tra HTTPS bang token gia, khong dang nhap tai khoan that):
C:\xampp\php\php.exe C:\xampp\htdocs\FishShop\database\check_google_login.php

LOG
---
Tim [FishShop OTP] hoac [FishShop Firebase] trong log rieng tu tao:
C:\xampp\htdocs\FishShop\storage\logs\auth.log.php
File log bi chan truy cap web va bi Git bo qua. Doc bang PowerShell/Notepad.
Lenh kiem tra tu tao file log va bao thu muc co ghi duoc hay khong.
Dong loi cung duoc gui toi PHP/Apache error_log neu duong dan do hoat dong.
Chi gui dong loi lien quan; khong gui .env, API key, mat khau hoac token.
Khong tat xac minh TLS de bo qua loi chung chi.

<?php
// Cấu hình kết nối MySQL (PDO). Chỉnh các hằng số bên dưới nếu máy bạn khác mặc định của XAMPP.
const DB_HOST = 'localhost';
const DB_NAME = 'WebBanCa';
const DB_USER = 'root';   // XAMPP mặc định
const DB_PASS = '';       // XAMPP mặc định: để trống

// Đường dẫn gốc của website (kết thúc bằng dấu /). Đổi nếu đặt thư mục tên khác.
const BASE_URL = '/FishShop/';

// Điền thông tin liên hệ thật của Admin; để trống nếu chưa cấu hình.
const ADMIN_CONTACT_EMAIL = '';
const ADMIN_CONTACT_PHONE = '';

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            http_response_code(500);
            exit('Không kết nối được MySQL. Hãy bật MySQL trong XAMPP, nhập file database/WebBanCa.sql và kiểm tra config/database.php.');
        }
    }
    return $pdo;
}

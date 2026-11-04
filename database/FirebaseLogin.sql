-- Chọn WebBanCa rồi nhập file này. Không xóa hoặc thay đổi tài khoản cũ.
-- Dùng Google provider ID, không nhầm Firebase UID với Google subject ID.
CREATE TABLE IF NOT EXISTS login_google_accounts (
    google_sub VARCHAR(255) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    CONSTRAINT fk_login_google_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

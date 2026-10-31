-- Chọn database WebBanCa trước khi chạy. Có thể chạy lại an toàn.
CREATE TABLE IF NOT EXISTS password_email_codes (
    user_id INT UNSIGNED PRIMARY KEY,
    code_hash VARCHAR(255) NOT NULL,
    binding CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    expires_at BIGINT NOT NULL,
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT fk_password_code_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS login_rate_limits (
    bucket CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    started_at BIGINT NOT NULL,
    last_at BIGINT NOT NULL,
    hits INT UNSIGNED NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS login_email_codes (
    user_id INT UNSIGNED PRIMARY KEY,
    code_hash VARCHAR(255) NOT NULL,
    binding CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    expires_at BIGINT NOT NULL,
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT fk_login_code_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS login_google_accounts (
    google_sub VARCHAR(255) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    CONSTRAINT fk_login_google_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

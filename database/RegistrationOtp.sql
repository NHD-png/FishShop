-- Chọn database WebBanCa trước khi nhập. Không thay đổi tài khoản cũ.
CREATE TABLE IF NOT EXISTS registration_otps (
    email VARCHAR(254) NOT NULL PRIMARY KEY,
    challenge_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL UNIQUE,
    code_hash VARCHAR(255) NOT NULL,
    expires_at BIGINT NOT NULL,
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS registration_rate_limits (
    bucket CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    started_at BIGINT NOT NULL,
    last_at BIGINT NOT NULL,
    hits INT UNSIGNED NOT NULL
) ENGINE=InnoDB;

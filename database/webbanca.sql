-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th10 04, 2026 lúc 03:01 PM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `webbanca`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `cart`
--

CREATE TABLE `cart` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `created_at`, `updated_at`) VALUES
(1, 1, '2026-10-31 07:40:29', '2026-10-31 07:43:27'),
(2, 4, '2026-10-31 14:14:40', '2026-11-04 13:56:31');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `cart_items`
--

CREATE TABLE `cart_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `cart_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL DEFAULT 1
) ;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `categories`
--

CREATE TABLE `categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `status`) VALUES
(1, 'Cá nước ngọt', 'Các loài cá sống trong môi trường nước ngọt.', 'active'),
(2, 'Cá nước mặn', 'Các loài cá sống trong môi trường nước mặn.', 'active'),
(3, 'Cá cảnh', 'Các loài cá nuôi làm cảnh.', 'active'),
(4, 'Sinh vật thần thoại', 'Các sinh vật trong thần thoại và truyền thuyết.', 'active'),
(5, 'Sinh vật cổ đại', 'Các sinh vật cổ đại và tiền sử.', 'active'),
(6, 'Khác', 'Các sản phẩm không thuộc những danh mục trên.', 'active'),
(7, 'Thức ăn cho cá', 'Cung cấp thức ăn cá', 'active'),
(8, 'Hồ thủy sinh', 'Hồ nuôi cá', 'active');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `customer_ranks`
--

CREATE TABLE `customer_ranks` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `total_exp` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `total_eligible_spend` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `spend_remainder` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `current_rank` varchar(24) NOT NULL DEFAULT 'dong',
  `last_rank_up_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Đang đổ dữ liệu cho bảng `customer_ranks`
--

INSERT INTO `customer_ranks` (`id`, `user_id`, `total_exp`, `total_eligible_spend`, `spend_remainder`, `current_rank`, `last_rank_up_at`, `created_at`, `updated_at`) VALUES
(1, 1, 20, 200000, 0, 'dong', NULL, '2026-10-28 15:40:49', '2026-10-31 07:53:50'),
(2, 2, 0, 0, 0, 'dong', NULL, '2026-10-29 15:28:40', '2026-10-29 15:28:40'),
(39, 4, 2, 20000, 0, 'dong', NULL, '2026-10-31 12:40:08', '2026-11-01 04:08:41'),
(42, 6, 0, 0, 0, 'dong', NULL, '2026-10-31 13:28:57', '2026-10-31 13:28:57');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `login_google_accounts`
--

CREATE TABLE `login_google_accounts` (
  `google_sub` varchar(255) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `login_google_accounts`
--

INSERT INTO `login_google_accounts` (`google_sub`, `user_id`) VALUES
('107194758364497775773', 4),
('117511879778188129089', 6);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `orders`
--

CREATE TABLE `orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `total` decimal(14,0) NOT NULL,
  `shipping_address` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','confirmed','shipping','completed','cancelled') NOT NULL DEFAULT 'pending',
  `payment_status` enum('unpaid','pending_verification','paid','rejected') NOT NULL DEFAULT 'unpaid',
  `payment_method` enum('cash','bank_transfer','payos') NOT NULL DEFAULT 'bank_transfer',
  `payos_payment_link_id` varchar(80) DEFAULT NULL,
  `payos_checkout_url` varchar(500) DEFAULT NULL,
  `payos_paid_at` datetime DEFAULT NULL,
  `shipping_latitude` decimal(10,7) DEFAULT NULL,
  `shipping_longitude` decimal(10,7) DEFAULT NULL,
  `subtotal` decimal(12,0) DEFAULT NULL,
  `discount_amount` decimal(12,0) NOT NULL DEFAULT 0,
  `voucher_code` varchar(32) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL
) ;

--
-- Đang đổ dữ liệu cho bảng `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total`, `shipping_address`, `phone`, `created_at`, `status`, `payment_status`, `payment_method`, `payos_payment_link_id`, `payos_checkout_url`, `payos_paid_at`, `shipping_latitude`, `shipping_longitude`, `subtotal`, `discount_amount`, `voucher_code`) VALUES
(1, 1, 100000, 'Duy_Vip_Pro', '00000000000', '2026-10-31 07:40:42', 'completed', 'paid', 'bank_transfer', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(2, 1, 100000, 'Duy_Vip_Pro', '00000000000', '2026-10-31 07:41:54', 'cancelled', 'paid', 'bank_transfer', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(3, 1, 100000, 'Duy_Vip_Pro', '00000000000', '2026-10-31 07:43:33', 'completed', 'paid', 'bank_transfer', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(4, 4, 10000, 'vvvvvvvv', '00000000000', '2026-10-31 14:14:56', 'pending', 'unpaid', 'bank_transfer', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(5, 4, 10000, 'vvvvvvvv', '00000000000', '2026-10-31 14:33:06', 'pending', 'unpaid', 'cash', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(6, 4, 10000, 'vvvvvvvv', '00000000000', '2026-10-31 14:38:51', 'pending', 'unpaid', 'payos', '2e9707a8a76f4c1395dda83976db6b01', 'https://pay.payos.vn/web/2e9707a8a76f4c1395dda83976db6b01', NULL, NULL, NULL, NULL, 0, NULL),
(7, 4, 10000, 'vvvvvvvv', '00000000000', '2026-10-31 14:39:27', 'pending', 'unpaid', 'payos', '2ec809e24a754fa1bc4939254059eae3', 'https://pay.payos.vn/web/2ec809e24a754fa1bc4939254059eae3', NULL, NULL, NULL, NULL, 0, NULL),
(8, 4, 10000, 'vvvvvvvv', '00000000000', '2026-10-31 14:41:16', 'pending', 'unpaid', 'payos', '2a0a364b9ffd4dc786d2c529b8e95046', 'https://pay.payos.vn/web/2a0a364b9ffd4dc786d2c529b8e95046', NULL, NULL, NULL, NULL, 0, NULL),
(9, 4, 10000, 'vvvvvvvv', '00000000000', '2026-10-31 14:43:10', 'pending', 'unpaid', 'payos', '2c42a11c494d4a58b3877081357002dd', 'https://pay.payos.vn/web/2c42a11c494d4a58b3877081357002dd', NULL, NULL, NULL, NULL, 0, NULL),
(10, 4, 10000, 'vvvvvvvv', '00000000000', '2026-10-31 14:45:07', 'pending', 'unpaid', 'payos', '9b96a5c7e315464d9fa18c8041605570', 'https://pay.payos.vn/web/9b96a5c7e315464d9fa18c8041605570', NULL, NULL, NULL, NULL, 0, NULL),
(11, 4, 10000, 'vvvvvvvv', '00000000000', '2026-10-31 14:46:38', 'pending', 'unpaid', 'payos', '46e50909eaa749cb83f18a48cd7084d1', 'https://pay.payos.vn/web/46e50909eaa749cb83f18a48cd7084d1', NULL, NULL, NULL, NULL, 0, NULL),
(12, 4, 10000, 'vvvvvvvv', '00000000000', '2026-10-31 14:48:27', 'pending', 'unpaid', 'payos', '127e867d1b144f57bd1a0b07b9e15b59', 'https://pay.payos.vn/web/127e867d1b144f57bd1a0b07b9e15b59', NULL, NULL, NULL, NULL, 0, NULL),
(13, 4, 10000, 'vvvvvvvv', '00000000000', '2026-10-31 14:50:08', 'pending', 'unpaid', 'payos', 'a9e0b8fc95cd4024ba6a41c09d0ae80d', 'https://pay.payos.vn/web/a9e0b8fc95cd4024ba6a41c09d0ae80d', NULL, NULL, NULL, NULL, 0, NULL),
(14, 4, 10000, 'vvvvvvvv', '00000000000', '2026-10-31 14:54:16', 'pending', 'unpaid', 'payos', 'e2f8512d897848e4a46a347cae5ac86e', 'https://pay.payos.vn/web/e2f8512d897848e4a46a347cae5ac86e', NULL, NULL, NULL, NULL, 0, NULL),
(15, 4, 10000, 'vvvvvvvv', '00000000000', '2026-10-31 14:57:49', 'pending', 'paid', 'payos', '7006517537964fc785cee2d12bcaa3c9', 'https://pay.payos.vn/web/7006517537964fc785cee2d12bcaa3c9', '2026-10-31 14:58:09', NULL, NULL, NULL, 0, NULL),
(16, 4, 10000, 'vvvvvvvv', '00000000000', '2026-11-01 02:25:47', 'completed', 'paid', 'cash', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(17, 4, 10000, 'vvvvvvvv', '00000000000', '2026-11-01 02:42:25', 'completed', 'paid', 'payos', '139ef555db6941d8bb60b1e7116aa1f1', 'https://pay.payos.vn/web/139ef555db6941d8bb60b1e7116aa1f1', NULL, NULL, NULL, NULL, 0, NULL),
(18, 4, 10000, 'vvvvvvvv', '00000000000', '2026-11-01 11:22:16', 'pending', 'paid', 'payos', 'c4a6339d20c34f9d88896bdbbd17fce5', 'https://pay.payos.vn/web/c4a6339d20c34f9d88896bdbbd17fce5', '2026-11-01 11:23:04', NULL, NULL, NULL, 0, NULL),
(19, 4, 30000, 'vvvvvvvv', '00000000000', '2026-11-01 11:23:28', 'pending', 'unpaid', 'payos', '6d46c76b97944c388d7ca56af14c160a', 'https://pay.payos.vn/web/6d46c76b97944c388d7ca56af14c160a', NULL, NULL, NULL, NULL, 0, NULL),
(20, 4, 20000, 'vvvvvvvv', '00000000000', '2026-11-01 11:23:37', 'pending', 'unpaid', 'payos', '6d8b378ea3964e3787ac90fd325b6d91', 'https://pay.payos.vn/web/6d8b378ea3964e3787ac90fd325b6d91', NULL, NULL, NULL, NULL, 0, NULL),
(21, 4, 40000, 'vvvvvvvv', '00000000000', '2026-11-02 06:06:51', 'pending', 'unpaid', 'payos', '01cad361ee1641b9856f7037c9d97fdb', 'https://pay.payos.vn/web/01cad361ee1641b9856f7037c9d97fdb', NULL, 10.2500645, 105.9620527, 40000, 0, NULL),
(22, 4, 10000, 'vvvvvvvv', '00000000000', '2026-11-02 06:07:15', 'pending', 'unpaid', 'cash', NULL, NULL, NULL, NULL, NULL, 10000, 0, NULL),
(23, 4, 10000, 'vvvvvvvv', '00000000000', '2026-11-02 06:49:14', 'pending', 'unpaid', 'payos', '4b10257a52c949c3a243b6a321816cb2', 'https://pay.payos.vn/web/4b10257a52c949c3a243b6a321816cb2', NULL, NULL, NULL, 10000, 0, NULL),
(24, 4, 10000, 'vvvvvvvv', '00000000000', '2026-11-02 23:51:40', 'pending', 'unpaid', 'payos', '5b889fb7c3b54478999e6aa15f992295', 'https://pay.payos.vn/web/5b889fb7c3b54478999e6aa15f992295', NULL, 10.2585931, 105.9860772, 10000, 0, NULL),
(25, 4, 10000, 'vvvvvvvv', '00000000000', '2026-11-03 01:08:19', 'pending', 'unpaid', 'payos', 'b48065c3cf5d4a3587afc5c9f0ee8ad5', 'https://pay.payos.vn/web/b48065c3cf5d4a3587afc5c9f0ee8ad5', NULL, NULL, NULL, 10000, 0, NULL),
(26, 4, 10000, 'vvvvvvvv', '00000000000', '2026-11-04 10:07:13', 'pending', 'unpaid', 'payos', '6993bd0b867b47a49db8dc3b1e18c638', 'https://pay.payos.vn/web/6993bd0b867b47a49db8dc3b1e18c638', NULL, NULL, NULL, 10000, 0, NULL),
(27, 4, 10000, 'vvvvvvvv', '00000000000', '2026-11-04 10:31:11', 'pending', 'unpaid', 'payos', 'c00e52ece3fa4570b7ac735aadf36113', 'https://pay.payos.vn/web/c00e52ece3fa4570b7ac735aadf36113', NULL, NULL, NULL, 10000, 0, NULL),
(28, 4, 27100, 'vvvvvvvv', '00000000000', '2026-11-04 13:47:09', 'pending', 'unpaid', 'payos', 'e28b208ce8d44246aed92a1ab0886580', 'https://pay.payos.vn/web/e28b208ce8d44246aed92a1ab0886580', NULL, NULL, NULL, 28000, 900, NULL),
(29, 4, 59900, 'vvvvvvvv', '00000000000', '2026-11-04 13:57:02', 'pending', 'unpaid', 'payos', '120b9e590fa3401ca103ee2067c11e02', 'https://pay.payos.vn/web/120b9e590fa3401ca103ee2067c11e02', NULL, NULL, NULL, 60000, 100, 'NGU');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `order_items`
--

CREATE TABLE `order_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL,
  `price` decimal(12,0) NOT NULL
) ;

--
-- Đang đổ dữ liệu cho bảng `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(1, 1, 1, 1, 100000),
(2, 2, 1, 1, 100000),
(3, 3, 1, 1, 100000),
(4, 4, 1, 1, 10000),
(5, 5, 1, 1, 10000),
(6, 6, 1, 1, 10000),
(7, 7, 1, 1, 10000),
(8, 8, 1, 1, 10000),
(9, 9, 1, 1, 10000),
(10, 10, 1, 1, 10000),
(11, 11, 1, 1, 10000),
(12, 12, 1, 1, 10000),
(13, 13, 1, 1, 10000),
(14, 14, 1, 1, 10000),
(15, 15, 1, 1, 10000),
(16, 16, 1, 1, 10000),
(17, 17, 1, 1, 10000),
(18, 18, 1, 1, 10000),
(19, 19, 1, 3, 10000),
(20, 20, 1, 2, 10000),
(21, 21, 1, 4, 10000),
(22, 22, 1, 1, 10000),
(23, 23, 1, 1, 10000),
(24, 24, 1, 1, 10000),
(25, 25, 1, 1, 10000),
(26, 26, 1, 1, 10000),
(27, 27, 1, 1, 10000),
(28, 28, 1, 1, 10000),
(29, 28, 12, 1, 17100),
(30, 29, 16, 1, 60000);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `password_reset_attempts`
--

CREATE TABLE `password_reset_attempts` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `failed_attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `token_hash` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `prize_rewards`
--

CREATE TABLE `prize_rewards` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `reward_type` enum('miss','coupon','fish') NOT NULL,
  `discount_percent` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `product_id` int(10) UNSIGNED DEFAULT NULL,
  `status` enum('available','used') NOT NULL DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `used_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `prize_rewards`
--

INSERT INTO `prize_rewards` (`id`, `user_id`, `reward_type`, `discount_percent`, `product_id`, `status`, `created_at`, `used_at`) VALUES
(1, 4, 'coupon', 5, NULL, 'used', '2026-11-04 11:00:49', '2026-11-04 20:47:09'),
(2, 4, 'coupon', 5, NULL, 'available', '2026-11-04 13:45:50', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `prize_spins`
--

CREATE TABLE `prize_spins` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `reward_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `prize_spins`
--

INSERT INTO `prize_spins` (`id`, `user_id`, `reward_id`, `created_at`) VALUES
(1, 4, NULL, '2026-11-04 10:54:51'),
(2, 4, NULL, '2026-11-04 10:55:00'),
(3, 4, NULL, '2026-11-04 10:55:00'),
(4, 4, NULL, '2026-11-04 10:55:00'),
(5, 4, NULL, '2026-11-04 10:55:00'),
(6, 4, NULL, '2026-11-04 10:55:00'),
(7, 4, NULL, '2026-11-04 10:58:32'),
(8, 4, NULL, '2026-11-04 10:58:38'),
(9, 4, NULL, '2026-11-04 10:58:38'),
(10, 4, NULL, '2026-11-04 10:58:38'),
(11, 4, NULL, '2026-11-04 10:58:38'),
(12, 4, NULL, '2026-11-04 10:58:38'),
(13, 4, NULL, '2026-11-04 10:58:52'),
(14, 4, NULL, '2026-11-04 10:58:52'),
(15, 4, NULL, '2026-11-04 10:58:52'),
(16, 4, NULL, '2026-11-04 10:58:52'),
(17, 4, NULL, '2026-11-04 10:58:52'),
(18, 4, NULL, '2026-11-04 10:58:52'),
(19, 4, NULL, '2026-11-04 10:58:52'),
(20, 4, NULL, '2026-11-04 10:58:52'),
(21, 4, NULL, '2026-11-04 10:58:52'),
(22, 4, NULL, '2026-11-04 10:58:52'),
(23, 4, NULL, '2026-11-04 11:00:49'),
(24, 4, NULL, '2026-11-04 11:00:49'),
(25, 4, 1, '2026-11-04 11:00:49'),
(26, 4, NULL, '2026-11-04 11:00:49'),
(27, 4, NULL, '2026-11-04 11:00:49'),
(28, 4, NULL, '2026-11-04 11:00:49'),
(29, 4, NULL, '2026-11-04 11:00:49'),
(30, 4, NULL, '2026-11-04 11:00:49'),
(31, 4, NULL, '2026-11-04 11:00:49'),
(32, 4, NULL, '2026-11-04 11:00:49'),
(33, 4, NULL, '2026-11-04 13:45:47'),
(34, 4, NULL, '2026-11-04 13:45:50'),
(35, 4, NULL, '2026-11-04 13:45:50'),
(36, 4, NULL, '2026-11-04 13:45:50'),
(37, 4, NULL, '2026-11-04 13:45:50'),
(38, 4, NULL, '2026-11-04 13:45:50'),
(39, 4, NULL, '2026-11-04 13:45:50'),
(40, 4, NULL, '2026-11-04 13:45:50'),
(41, 4, NULL, '2026-11-04 13:45:50'),
(42, 4, 2, '2026-11-04 13:45:50'),
(43, 4, NULL, '2026-11-04 13:45:50');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `prize_wallets`
--

CREATE TABLE `prize_wallets` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `balance` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `prize_wallets`
--

INSERT INTO `prize_wallets` (`user_id`, `balance`, `updated_at`) VALUES
(4, 805000, '2026-11-04 13:45:50');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `products`
--

CREATE TABLE `products` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `price` decimal(12,0) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(10) UNSIGNED NOT NULL,
  `stock` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Đang đổ dữ liệu cho bảng `products`
--

INSERT INTO `products` (`id`, `name`, `price`, `image`, `description`, `category_id`, `stock`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Hồ Thái Khang', 10000, 'https://res.cloudinary.com/xhuozpn6/image/upload/v1793599752/fishshop/products/b8ad44e761f6357a0407ffbb.jpg', 'Còn sót lại trong kho 1 con', 6, 1, 'active', '2026-10-31 07:40:13', '2026-11-04 13:54:50'),
(2, 'Cá Lóc', 40000, 'https://res.cloudinary.com/xhuozpn6/image/upload/v1793790604/fishshop/products/3c2f9fcfcf58e0a3ecd9c1ee.webp', 'ngon lắm mua đi', 1, 100, 'active', '2026-11-04 11:10:05', '2026-11-04 11:10:05'),
(3, 'Aquaman', 99999999, 'https://res.cloudinary.com/xhuozpn6/image/upload/v1793791612/fishshop/products/57559a8bd4d8daa6bc4ccd5c.webp', 'Mới bắt', 4, 1, 'active', '2026-11-04 11:26:54', '2026-11-04 13:36:35'),
(4, 'Nguyễn Thành Luân', 15000, 'https://res.cloudinary.com/xhuozpn6/image/upload/v1793791721/fishshop/products/ffc2b7fa50d2c4f3289a7fc6.jpg', 'trong kho còn dư 1 con', 6, 1, 'active', '2026-11-04 11:28:43', '2026-11-04 11:28:43'),
(5, 'Cá Mập', 900000, 'https://res.cloudinary.com/xhuozpn6/image/upload/v1793791816/fishshop/products/f12f7eff16e3d9bfc549cd24.webp', 'Sản phẩm nhân giống mới nhất', 3, 25, 'active', '2026-11-04 11:30:17', '2026-11-04 11:30:17'),
(6, 'Cá Heo', 990090, 'https://res.cloudinary.com/xhuozpn6/image/upload/v1793791872/fishshop/products/91d9c193a3d9f6a8b9feec94.jpg', 'Cá siêu thân thiện', 2, 50, 'active', '2026-11-04 11:31:13', '2026-11-04 11:31:13'),
(7, 'Piraha', 500000, 'https://res.cloudinary.com/xhuozpn6/image/upload/v1793791960/fishshop/products/fe7f8c6025d21f7f50620678.jpg', 'Cá siêu cấp thân thiện', 1, 134, 'active', '2026-11-04 11:32:41', '2026-11-04 11:32:41'),
(8, 'Cá Vàng', 9999999, 'https://res.cloudinary.com/xhuozpn6/image/upload/v1793792034/fishshop/products/3dc19db36dcc70d9c8d52a2b.jpg', 'Cá bằng vàng nguyên chất', 3, 150, 'active', '2026-11-04 11:33:55', '2026-11-04 11:33:55'),
(9, 'Cá bảy màu', 7000, 'https://res.cloudinary.com/xhuozpn6/image/upload/v1793798742/fishshop/products/968c9be21ac8161cc22609f4.jpg', 'Cá cảnh', 3, 169, 'active', '2026-11-04 13:25:43', '2026-11-04 13:25:43'),
(10, 'Cá mập đầu búa', 99999999, 'https://res.cloudinary.com/xhuozpn6/image/upload/v1793798812/fishshop/products/431428c85faabc38da514355.jpg', 'Cá To', 2, 13, 'active', '2026-11-04 13:26:53', '2026-11-04 13:26:53'),
(11, 'Cá nóc', 567000, 'https://res.cloudinary.com/xhuozpn6/image/upload/v1793798861/fishshop/products/c44bb59593b4eeb48e91ae5c.webp', 'Cá có độc', 2, 67, 'active', '2026-11-04 13:27:42', '2026-11-04 13:27:42'),
(12, 'Cá nục', 18000, 'https://res.cloudinary.com/xhuozpn6/image/upload/v1793798896/fishshop/products/8a7d0a9730f3d11438160de9.jpg', 'ăn rất', 1, 91, 'active', '2026-11-04 13:28:17', '2026-11-04 13:47:09'),
(13, 'Cápybara', 98000, 'https://res.cloudinary.com/xhuozpn6/image/upload/v1793798932/fishshop/products/36b1be4e2f4c13ad55db346f.jpg', 'Cá lạ', 3, 67, 'active', '2026-11-04 13:28:54', '2026-11-04 13:28:54'),
(14, 'Cá sấu', 78500000, 'https://res.cloudinary.com/xhuozpn6/image/upload/v1793799117/fishshop/products/9c9b6a24ca1421e463c7e112.webp', 'Cá rất sấu', 1, 26, 'active', '2026-11-04 13:31:58', '2026-11-04 13:31:58'),
(15, 'Cá leo cây', 23000, 'https://res.cloudinary.com/xhuozpn6/image/upload/v1793799177/fishshop/products/b066eb5dfdb9a34fde9a3c3b.jpg', 'Rất nhớt', 1, 67, 'active', '2026-11-04 13:32:58', '2026-11-04 13:32:58'),
(16, 'Cá viên chiên', 60000, 'https://res.cloudinary.com/xhuozpn6/image/upload/v1793799229/fishshop/products/f239690907e0d5f95d4d5a4d.webp', 'Ăn rất ngon', 6, 15, 'active', '2026-11-04 13:33:50', '2026-11-04 13:57:02'),
(17, 'Cá voi trắng', 60000, 'https://res.cloudinary.com/xhuozpn6/image/upload/v1793799297/fishshop/products/a9390bb9150f89ea1642ef64.png', 'Cute', 2, 53, 'active', '2026-11-04 13:34:58', '2026-11-04 13:34:58'),
(18, '(cờ) Cá Ngựa', 120000, 'https://res.cloudinary.com/xhuozpn6/image/upload/v1793799346/fishshop/products/13964022a5725ffb4f3c5c36.jpg', 'Cá không ăn được', 6, 20, 'active', '2026-11-04 13:35:47', '2026-11-04 13:35:47'),
(19, 'Tiên Cá', 999999999, 'https://res.cloudinary.com/xhuozpn6/image/upload/v1793799406/fishshop/products/1a5c051cf7d4412aec1f038f.jpg', '...', 4, 1, 'active', '2026-11-04 13:36:20', '2026-11-04 13:36:48'),
(20, '(gà) Vảy Cá', 500000, 'https://res.cloudinary.com/xhuozpn6/image/upload/v1793799468/fishshop/products/af7245d6f9fd6a54ab91a46d.jpg', 'Cá trên cạn', 6, 12, 'active', '2026-11-04 13:37:50', '2026-11-04 13:37:50'),
(21, 'Leviathan', 999999999, 'https://res.cloudinary.com/xhuozpn6/image/upload/v1793799504/fishshop/products/fef7916b7f9ce5111153751c.webp', 'Cá hiếm', 5, 1, 'active', '2026-11-04 13:38:25', '2026-11-04 13:38:25'),
(22, 'Mosasaurus', 999999999, 'https://res.cloudinary.com/xhuozpn6/image/upload/v1793799537/fishshop/products/ed9847dde2debf87f315dd3a.jpg', 'Cá siêu hiếm', 5, 1, 'active', '2026-11-04 13:38:58', '2026-11-04 13:38:58'),
(23, 'Messie', 989999999, 'https://res.cloudinary.com/xhuozpn6/image/upload/v1793799574/fishshop/products/1c3650b968660b30504313a5.jpg', 'Cá siêu hiếm', 5, 1, 'active', '2026-11-04 13:39:35', '2026-11-04 13:39:35'),
(24, 'Rái cá', 6666666, 'https://res.cloudinary.com/xhuozpn6/image/upload/v1793799630/fishshop/products/f84f11995cfb3ab984e3caa9.webp', 'Siêu cute', 6, 123, 'active', '2026-11-04 13:40:31', '2026-11-04 13:40:31'),
(25, 'Hồ cỏ', 467000, 'https://res.cloudinary.com/xhuozpn6/image/upload/v1793800089/fishshop/products/c8a8473d848e0faae6b6fda9.webp', 'Hồ kiểng', 8, 23, 'active', '2026-11-04 13:48:10', '2026-11-04 13:48:10'),
(26, 'Thức ăn cá lia thia', 48, 'https://res.cloudinary.com/xhuozpn6/image/upload/v1793800154/fishshop/products/5caed2a9f0f168e402316ffa.jpg', 'Túi 3.6kg', 7, 36, 'active', '2026-11-04 13:49:15', '2026-11-04 13:49:15'),
(27, 'Hồ thủy sinh mini', 213000, 'https://res.cloudinary.com/xhuozpn6/image/upload/v1793800199/fishshop/products/780954f610f8db73b74a6bc3.jpg', 'Hồ mini', 8, 45, 'active', '2026-11-04 13:50:00', '2026-11-04 13:50:00'),
(28, 'Cobo 3 túi thức ăn cá lia thia', 197000, 'https://res.cloudinary.com/xhuozpn6/image/upload/v1793800321/fishshop/products/b0baec18a1da7721be02aa17.jpg', 'Cobo', 7, 233, 'active', '2026-11-04 13:52:02', '2026-11-04 13:52:02'),
(29, 'Hồ thủy sinh Cây', 786000, 'https://res.cloudinary.com/xhuozpn6/image/upload/v1793800464/fishshop/products/60d5713159b9215aa322dd05.jpg', 'Hồ cảnh 2.0', 8, 23, 'active', '2026-11-04 13:54:25', '2026-11-04 13:54:25'),
(30, 'Hồ thủy sinh cỡ lớn', 900060, 'https://res.cloudinary.com/xhuozpn6/image/upload/v1793800680/fishshop/products/fc674f698ded5744916f9a95.webp', 'Hồ cảnh lớn', 8, 12, 'active', '2026-11-04 13:58:01', '2026-11-04 13:58:01'),
(31, 'Hồ thủy sinh trống', 150000, 'https://res.cloudinary.com/xhuozpn6/image/upload/v1793800762/fishshop/products/83939b39867d020ae131db68.webp', 'hồ lẻ loi', 8, 123, 'active', '2026-11-04 13:59:23', '2026-11-04 13:59:23'),
(32, 'Thức ăn cá nước mặn', 150000, 'https://res.cloudinary.com/xhuozpn6/image/upload/v1793800803/fishshop/products/f0a9ae1d4741b2a656a23c22.jpg', 'thức ăn cá', 7, 23, 'active', '2026-11-04 14:00:05', '2026-11-04 14:00:05');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `rank_order_earnings`
--

CREATE TABLE `rank_order_earnings` (
  `order_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `spend_amount` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `is_active` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Đang đổ dữ liệu cho bảng `rank_order_earnings`
--

INSERT INTO `rank_order_earnings` (`order_id`, `user_id`, `spend_amount`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 100000, 1, '2026-10-31 07:52:35', '2026-10-31 07:52:35'),
(3, 1, 100000, 1, '2026-10-31 07:53:50', '2026-10-31 07:53:50'),
(16, 4, 10000, 1, '2026-11-01 04:08:41', '2026-11-01 04:08:41'),
(17, 4, 10000, 1, '2026-11-01 03:55:14', '2026-11-01 03:55:14');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `registration_otps`
--

CREATE TABLE `registration_otps` (
  `email` varchar(254) NOT NULL,
  `challenge_hash` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `code_hash` varchar(255) NOT NULL,
  `expires_at` bigint(20) NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `registration_otps`
--

INSERT INTO `registration_otps` (`email`, `challenge_hash`, `code_hash`, `expires_at`, `attempts`) VALUES
('hocngu135@gmail.com', 'caf3131dc4fba02de98f99ce732f8a7a4d982ce8fe53e6ad2743c6d909a1e5a1', '$2y$10$CwBfP8a63K6wZfgN74qGVelrSf6KJMZX85vSzcgR87R.HpHncbHx6', 1793548349, 0);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `registration_rate_limits`
--

CREATE TABLE `registration_rate_limits` (
  `bucket` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `started_at` bigint(20) NOT NULL,
  `last_at` bigint(20) NOT NULL,
  `hits` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `registration_rate_limits`
--

INSERT INTO `registration_rate_limits` (`bucket`, `started_at`, `last_at`, `hits`) VALUES
('227a139b2d0f158050b5542327a01af02caf27028d0e765c5c3b769a71257468', 1793547958, 1793548049, 3),
('66f4b9da0c04325045048a2509a968c4bb036561045697c8c9ec1225d1acdb36', 1793547958, 1793548049, 2),
('6c26baaaddd5dde701629f6a50b49fc5cc9ff2fc28b97bdfdb13280c12e80f0b', 1793452897, 1793453319, 3);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL COMMENT 'Chuỗi băm từ password_hash()',
  `fullname` varchar(100) NOT NULL,
  `email` varchar(254) DEFAULT NULL,
  `security_question` varchar(40) DEFAULT NULL,
  `security_answer_hash` varchar(255) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `address` varchar(255) NOT NULL DEFAULT '',
  `avatar` varchar(255) DEFAULT NULL,
  `role` enum('customer','admin') NOT NULL DEFAULT 'customer',
  `account_status` enum('active','locked') NOT NULL DEFAULT 'active',
  `locked_reason` varchar(64) DEFAULT NULL,
  `locked_note` text DEFAULT NULL,
  `locked_at` datetime DEFAULT NULL,
  `locked_by_admin_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `fullname`, `email`, `security_question`, `security_answer_hash`, `phone`, `address`, `avatar`, `role`, `account_status`, `locked_reason`, `locked_note`, `locked_at`, `locked_by_admin_id`, `created_at`) VALUES
(1, 'DuySieuCapDeptrai', '$2y$10$rmtLXlTfWhkJaossU2UODe58JNKrp5QPrz/RTWHx8UmqYv5HTI1ui', 'Nguyễn Hoàng Duy', NULL, 'favorite_teacher', '$2y$10$GztX4qcjTgIGXhh5hdP.AuIlLRiew5K3Zc50fa8F2PuwbVyVIRBGW', '00000000000', 'Duy_Vip_Pro', 'uploads/avatars/17e1fff455cfa2afc05d6c9f.webp', 'admin', 'active', NULL, NULL, NULL, NULL, '2026-10-28 15:40:49'),
(2, 'KhangGay', '$2y$10$m8hgdOa2MtkUSe9eKunTIedwpo.v9TnKwf9axNi6DQw.Nx9Ovtou2', 'Hồ Thái Khang', 'fff2@gmail.com', 'favorite_teacher', '$2y$10$pjYo4IU3fiwjQLnp.CLO6OSNIenwzGfHGN.PtJyimhXpOld3o6aNC', '', 'Địa Ngục', NULL, 'customer', 'locked', 'Có dấu hiệu gian lận đơn hàng', '', '2026-10-29 23:09:52', 1, '2026-10-29 15:28:40'),
(4, 'google_1864f1a90d104e39dd75', '$2y$10$3pLBzvC1UudBL7nt8OVTWOBEkx8FKgq295cWUQnz72vp8WzaFR3Ai', 'Nguyễn Phạm Nhựt Trường', '24004051@st.vlute.edu.vn', NULL, NULL, '00000000000', 'vvvvvvvv', 'uploads/avatars/cfff77e5dc8306217b024bdc.png', 'customer', 'active', NULL, NULL, NULL, NULL, '2026-10-31 12:40:08'),
(6, 'ngu', '$2y$10$jEpsfwyl2SNpqGSDiOSq1.Xlj33XPooQg4lpxuzzfXOtQTpqFwEI6', 'Học Ngu', 'nhutnm2306@gmail.com', NULL, NULL, '', '', NULL, 'customer', 'active', NULL, NULL, NULL, NULL, '2026-10-31 13:28:57');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `user_account_lock_history`
--

CREATE TABLE `user_account_lock_history` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `action` enum('lock','unlock') NOT NULL,
  `reason` varchar(64) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `performed_by_admin_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `user_account_lock_history`
--

INSERT INTO `user_account_lock_history` (`id`, `user_id`, `action`, `reason`, `note`, `performed_by_admin_id`, `created_at`) VALUES
(1, 2, 'lock', 'Có dấu hiệu gian lận đơn hàng', '', 1, '2026-10-29 16:09:52');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `vouchers`
--

CREATE TABLE `vouchers` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `name` varchar(100) NOT NULL,
  `discount_type` enum('fixed','percent') NOT NULL,
  `discount_value` decimal(12,0) UNSIGNED NOT NULL,
  `min_order_amount` decimal(12,0) UNSIGNED NOT NULL DEFAULT 0,
  `max_discount_amount` decimal(12,0) UNSIGNED DEFAULT NULL,
  `starts_at` datetime DEFAULT NULL,
  `ends_at` datetime DEFAULT NULL,
  `usage_limit` int(10) UNSIGNED DEFAULT NULL,
  `per_user_limit` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `vouchers`
--

INSERT INTO `vouchers` (`id`, `code`, `name`, `discount_type`, `discount_value`, `min_order_amount`, `max_discount_amount`, `starts_at`, `ends_at`, `usage_limit`, `per_user_limit`, `is_active`, `created_at`) VALUES
(1, 'NGU', 'ưu đãi', 'percent', 10, 1, 100, '2026-11-04 18:06:00', '2026-11-05 18:06:00', 2, 1, 1, '2026-11-04 11:06:39');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `voucher_usages`
--

CREATE TABLE `voucher_usages` (
  `id` int(10) UNSIGNED NOT NULL,
  `voucher_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `discount_amount` decimal(12,0) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `voucher_usages`
--

INSERT INTO `voucher_usages` (`id`, `voucher_id`, `user_id`, `order_id`, `discount_amount`, `created_at`) VALUES
(1, 1, 4, 29, 100, '2026-11-04 13:57:02');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `wallet_deposits`
--

CREATE TABLE `wallet_deposits` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `amount` bigint(20) UNSIGNED NOT NULL,
  `reference_mode` enum('with_code','no_code') NOT NULL DEFAULT 'with_code',
  `request_type` enum('payos','direct') NOT NULL DEFAULT 'direct',
  `sender_name` varchar(100) NOT NULL DEFAULT '',
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `payos_payment_link_id` varchar(80) DEFAULT NULL,
  `payos_checkout_url` varchar(500) DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `payos_bank_bin` varchar(16) DEFAULT NULL,
  `payos_account_number` varchar(40) DEFAULT NULL,
  `payos_account_name` varchar(150) DEFAULT NULL,
  `payos_transfer_description` varchar(100) DEFAULT NULL,
  `payos_qr_code` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` datetime DEFAULT NULL,
  `reviewed_by` int(10) UNSIGNED DEFAULT NULL,
  UNIQUE KEY `uq_wallet_deposit_payos_link` (`payos_payment_link_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `wallet_deposits`
--

INSERT INTO `wallet_deposits` (`id`, `user_id`, `amount`, `reference_mode`, `sender_name`, `status`, `created_at`, `reviewed_at`, `reviewed_by`) VALUES
(1, 4, 1000000, 'with_code', '', 'approved', '2026-11-04 10:31:39', '2026-11-04 17:51:34', 1),
(2, 4, 20000, 'no_code', '1212', 'approved', '2026-11-04 10:45:51', '2026-11-04 17:51:33', 1),
(3, 4, 33430000, 'no_code', 'sadas', 'pending', '2026-11-04 13:56:05', NULL, NULL);

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_cart_user` (`user_id`);

--
-- Chỉ mục cho bảng `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_cart_product` (`cart_id`,`product_id`),
  ADD KEY `idx_cart_items_product` (`product_id`);

--
-- Chỉ mục cho bảng `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_categories_name` (`name`);

--
-- Chỉ mục cho bảng `customer_ranks`
--
ALTER TABLE `customer_ranks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_customer_ranks_user` (`user_id`);

--
-- Chỉ mục cho bảng `login_google_accounts`
--
ALTER TABLE `login_google_accounts`
  ADD PRIMARY KEY (`google_sub`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_orders_id_user` (`id`,`user_id`),
  ADD KEY `idx_orders_user` (`user_id`),
  ADD KEY `idx_orders_status` (`status`);

--
-- Chỉ mục cho bảng `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_order_product` (`order_id`,`product_id`),
  ADD KEY `idx_order_items_order` (`order_id`),
  ADD KEY `idx_order_items_product` (`product_id`);

--
-- Chỉ mục cho bảng `password_reset_attempts`
--
ALTER TABLE `password_reset_attempts`
  ADD PRIMARY KEY (`user_id`);

--
-- Chỉ mục cho bảng `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`user_id`);

--
-- Chỉ mục cho bảng `prize_rewards`
--
ALTER TABLE `prize_rewards`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`,`status`);

--
-- Chỉ mục cho bảng `prize_spins`
--
ALTER TABLE `prize_spins`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`,`created_at`),
  ADD KEY `fk_prize_spin_reward` (`reward_id`);

--
-- Chỉ mục cho bảng `prize_wallets`
--
ALTER TABLE `prize_wallets`
  ADD PRIMARY KEY (`user_id`);

--
-- Chỉ mục cho bảng `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_products_category` (`category_id`),
  ADD KEY `idx_products_status` (`status`);

--
-- Chỉ mục cho bảng `rank_order_earnings`
--
ALTER TABLE `rank_order_earnings`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `idx_rank_order_earnings_user` (`user_id`),
  ADD KEY `fk_rank_order_earnings_order` (`order_id`,`user_id`);

--
-- Chỉ mục cho bảng `registration_otps`
--
ALTER TABLE `registration_otps`
  ADD PRIMARY KEY (`email`),
  ADD UNIQUE KEY `challenge_hash` (`challenge_hash`);

--
-- Chỉ mục cho bảng `registration_rate_limits`
--
ALTER TABLE `registration_rate_limits`
  ADD PRIMARY KEY (`bucket`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_username` (`username`),
  ADD UNIQUE KEY `uq_users_email` (`email`),
  ADD KEY `idx_users_account_status` (`account_status`),
  ADD KEY `idx_users_locked_by_admin` (`locked_by_admin_id`);

--
-- Chỉ mục cho bảng `user_account_lock_history`
--
ALTER TABLE `user_account_lock_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_lock_history_user` (`user_id`),
  ADD KEY `idx_user_lock_history_admin` (`performed_by_admin_id`);

--
-- Chỉ mục cho bảng `vouchers`
--
ALTER TABLE `vouchers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Chỉ mục cho bảng `voucher_usages`
--
ALTER TABLE `voucher_usages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_id` (`order_id`),
  ADD KEY `idx_voucher_usage_user` (`voucher_id`,`user_id`),
  ADD KEY `fk_voucher_usage_user` (`user_id`);

--
-- Chỉ mục cho bảng `wallet_deposits`
--
ALTER TABLE `wallet_deposits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `status` (`status`,`created_at`),
  ADD KEY `user_id` (`user_id`,`created_at`),
  ADD KEY `fk_wallet_deposit_reviewer` (`reviewed_by`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT cho bảng `customer_ranks`
--
ALTER TABLE `customer_ranks`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `prize_rewards`
--
ALTER TABLE `prize_rewards`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `prize_spins`
--
ALTER TABLE `prize_spins`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT cho bảng `products`
--
ALTER TABLE `products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `user_account_lock_history`
--
ALTER TABLE `user_account_lock_history`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `vouchers`
--
ALTER TABLE `vouchers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `voucher_usages`
--
ALTER TABLE `voucher_usages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `wallet_deposits`
--
ALTER TABLE `wallet_deposits`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `fk_cart_items_cart` FOREIGN KEY (`cart_id`) REFERENCES `cart` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cart_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `customer_ranks`
--
ALTER TABLE `customer_ranks`
  ADD CONSTRAINT `fk_customer_ranks_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `login_google_accounts`
--
ALTER TABLE `login_google_accounts`
  ADD CONSTRAINT `fk_login_google_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `password_reset_attempts`
--
ALTER TABLE `password_reset_attempts`
  ADD CONSTRAINT `fk_password_reset_attempts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD CONSTRAINT `fk_password_reset_tokens_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `prize_rewards`
--
ALTER TABLE `prize_rewards`
  ADD CONSTRAINT `fk_prize_reward_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `prize_spins`
--
ALTER TABLE `prize_spins`
  ADD CONSTRAINT `fk_prize_spin_reward` FOREIGN KEY (`reward_id`) REFERENCES `prize_rewards` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_prize_spin_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `prize_wallets`
--
ALTER TABLE `prize_wallets`
  ADD CONSTRAINT `fk_prize_wallet_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `rank_order_earnings`
--
ALTER TABLE `rank_order_earnings`
  ADD CONSTRAINT `fk_rank_order_earnings_order` FOREIGN KEY (`order_id`,`user_id`) REFERENCES `orders` (`id`, `user_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rank_order_earnings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_locked_by_admin` FOREIGN KEY (`locked_by_admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `user_account_lock_history`
--
ALTER TABLE `user_account_lock_history`
  ADD CONSTRAINT `fk_user_lock_history_admin` FOREIGN KEY (`performed_by_admin_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_user_lock_history_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `voucher_usages`
--
ALTER TABLE `voucher_usages`
  ADD CONSTRAINT `fk_voucher_usage_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_voucher_usage_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_voucher_usage_voucher` FOREIGN KEY (`voucher_id`) REFERENCES `vouchers` (`id`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `wallet_deposits`
--
ALTER TABLE `wallet_deposits`
  ADD CONSTRAINT `fk_wallet_deposit_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_wallet_deposit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

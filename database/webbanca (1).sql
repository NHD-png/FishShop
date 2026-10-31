-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th10 31, 2026 lúc 09:16 AM
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
(1, 1, '2026-10-31 07:40:29', '2026-10-31 07:43:27');

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
(6, 'Khác', 'Các sản phẩm không thuộc những danh mục trên.', 'active');

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
(2, 2, 0, 0, 0, 'dong', NULL, '2026-10-29 15:28:40', '2026-10-29 15:28:40');

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
  `payment_status` enum('unpaid','pending_verification','paid','rejected') NOT NULL DEFAULT 'unpaid'
) ;

--
-- Đang đổ dữ liệu cho bảng `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total`, `shipping_address`, `phone`, `created_at`, `status`, `payment_status`) VALUES
(1, 1, 100000, 'Duy_Vip_Pro', '00000000000', '2026-10-31 07:40:42', 'completed', 'paid'),
(2, 1, 100000, 'Duy_Vip_Pro', '00000000000', '2026-10-31 07:41:54', 'cancelled', 'paid'),
(3, 1, 100000, 'Duy_Vip_Pro', '00000000000', '2026-10-31 07:43:33', 'completed', 'paid');

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
(3, 3, 1, 1, 100000);

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
(1, 'Hồ Thái Khang', 100000, 'uploads/products/986f2760ebb3ea0a75d0e78c.jpg', 'Còn sót lại trong kho 1 con', 6, 9, 'active', '2026-10-31 07:40:13', '2026-10-31 07:43:33');

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
(3, 1, 100000, 1, '2026-10-31 07:53:50', '2026-10-31 07:53:50');

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
(2, 'KhangGay', '$2y$10$m8hgdOa2MtkUSe9eKunTIedwpo.v9TnKwf9axNi6DQw.Nx9Ovtou2', 'Hồ Thái Khang', 'fff2@gmail.com', 'favorite_teacher', '$2y$10$pjYo4IU3fiwjQLnp.CLO6OSNIenwzGfHGN.PtJyimhXpOld3o6aNC', '', 'Địa Ngục', NULL, 'customer', 'locked', 'Có dấu hiệu gian lận đơn hàng', '', '2026-10-29 23:09:52', 1, '2026-10-29 15:28:40');

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
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

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
-- AUTO_INCREMENT cho bảng `products`
--
ALTER TABLE `products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `user_account_lock_history`
--
ALTER TABLE `user_account_lock_history`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

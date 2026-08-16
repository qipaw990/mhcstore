-- ==========================================================
-- CicalengkaGO Database Schema
-- Multi-Vendor On-Demand Delivery Platform
-- Clean, Modular, Indexed, InnoDB, utf8mb4
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `cicalengkago_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `cicalengkago_db`;

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Users Table (Admin, Vendor, Customer, Delivery Man)
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role` ENUM('admin', 'vendor', 'customer', 'delivery_man') NOT NULL DEFAULT 'customer',
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `phone` VARCHAR(25) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `avatar` VARCHAR(255) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `remember_token` VARCHAR(100) NULL,
  `api_token` VARCHAR(255) NULL,
  `email_verified_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_role_status` (`role`, `is_active`),
  INDEX `idx_email` (`email`),
  INDEX `idx_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Modules Table (Food, Grocery, Pharmacy, Ecommerce, Parcel)
DROP TABLE IF EXISTS `modules`;
CREATE TABLE `modules` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL,
  `module_type` ENUM('food', 'grocery', 'pharmacy', 'ecommerce', 'parcel') NOT NULL,
  `icon` VARCHAR(100) NOT NULL DEFAULT 'bi-box-seam',
  `thumbnail` VARCHAR(255) NULL,
  `theme_color` VARCHAR(20) NOT NULL DEFAULT '#0d6efd',
  `description` VARCHAR(255) NULL,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `stores_count` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_module_type` (`module_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Zones Table (Delivery coverage & polygon)
DROP TABLE IF EXISTS `zones`;
CREATE TABLE `zones` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `coordinates_json` LONGTEXT NULL,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `min_delivery_charge` DECIMAL(12, 2) NOT NULL DEFAULT 5000.00,
  `per_km_delivery_charge` DECIMAL(12, 2) NOT NULL DEFAULT 2500.00,
  `center_latitude` DECIMAL(10, 8) NULL,
  `center_longitude` DECIMAL(11, 8) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Stores / Vendors Table
DROP TABLE IF EXISTS `stores`;
CREATE TABLE `stores` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `vendor_id` BIGINT UNSIGNED NOT NULL,
  `module_id` INT UNSIGNED NOT NULL,
  `zone_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(25) NOT NULL,
  `email` VARCHAR(100) NULL,
  `logo` VARCHAR(255) NULL,
  `cover_photo` VARCHAR(255) NULL,
  `address` TEXT NOT NULL,
  `latitude` DECIMAL(10, 8) NOT NULL,
  `longitude` DECIMAL(11, 8) NOT NULL,
  `minimum_order` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `delivery_time` VARCHAR(30) NOT NULL DEFAULT '20-30 min',
  `delivery_fee` DECIMAL(12, 2) NOT NULL DEFAULT 5000.00,
  `tax_percent` DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
  `is_open` TINYINT(1) NOT NULL DEFAULT 1,
  `status` ENUM('pending', 'approved', 'suspended') NOT NULL DEFAULT 'approved',
  `rating` DECIMAL(3, 2) NOT NULL DEFAULT 5.00,
  `reviews_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `order_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_vendor` (`vendor_id`),
  INDEX `idx_module_zone` (`module_id`, `zone_id`),
  INDEX `idx_open_status` (`is_open`, `status`),
  CONSTRAINT `fk_store_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_store_module` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_store_zone` FOREIGN KEY (`zone_id`) REFERENCES `zones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Store Schedules Table
DROP TABLE IF EXISTS `store_schedules`;
CREATE TABLE `store_schedules` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `store_id` BIGINT UNSIGNED NOT NULL,
  `day_of_week` TINYINT UNSIGNED NOT NULL COMMENT '0=Sunday, 1=Monday, ..., 6=Saturday',
  `opening_time` TIME NOT NULL DEFAULT '08:00:00',
  `closing_time` TIME NOT NULL DEFAULT '22:00:00',
  PRIMARY KEY (`id`),
  KEY `idx_store_schedule` (`store_id`, `day_of_week`),
  CONSTRAINT `fk_schedule_store` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Categories Table
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `module_id` INT UNSIGNED NOT NULL,
  `parent_id` INT UNSIGNED NULL DEFAULT 0,
  `name` VARCHAR(100) NOT NULL,
  `image` VARCHAR(255) NULL,
  `icon` VARCHAR(100) NULL DEFAULT 'bi-tag',
  `priority` INT NOT NULL DEFAULT 0,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_category_module` (`module_id`, `status`),
  CONSTRAINT `fk_cat_module` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Products Table
DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `store_id` BIGINT UNSIGNED NOT NULL,
  `module_id` INT UNSIGNED NOT NULL,
  `category_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(200) NOT NULL,
  `description` TEXT NULL,
  `image` VARCHAR(255) NULL,
  `price` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `discount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `discount_type` ENUM('percent', 'amount') NOT NULL DEFAULT 'percent',
  `unit` VARCHAR(20) NOT NULL DEFAULT 'pcs',
  `stock` INT NOT NULL DEFAULT 100,
  `is_veg` TINYINT(1) NOT NULL DEFAULT 0,
  `is_recommended` TINYINT(1) NOT NULL DEFAULT 0,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `order_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `rating` DECIMAL(3, 2) NOT NULL DEFAULT 5.00,
  `reviews_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_product_store` (`store_id`),
  INDEX `idx_product_cat` (`category_id`),
  INDEX `idx_product_module` (`module_id`),
  INDEX `idx_product_status` (`status`, `is_recommended`),
  CONSTRAINT `fk_product_store` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_product_module` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Product Variations Table
DROP TABLE IF EXISTS `product_variations`;
CREATE TABLE `product_variations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `price` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `stock` INT NOT NULL DEFAULT 100,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_variation_product` (`product_id`),
  CONSTRAINT `fk_var_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Product Add-ons Table
DROP TABLE IF EXISTS `product_addons`;
CREATE TABLE `product_addons` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `store_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `price` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_addon_store` (`store_id`),
  CONSTRAINT `fk_addon_store` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Customer Addresses Table
DROP TABLE IF EXISTS `customer_addresses`;
CREATE TABLE `customer_addresses` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `address_type` ENUM('home', 'work', 'other') NOT NULL DEFAULT 'home',
  `contact_name` VARCHAR(100) NOT NULL,
  `contact_phone` VARCHAR(25) NOT NULL,
  `address` TEXT NOT NULL,
  `road` VARCHAR(100) NULL,
  `house` VARCHAR(50) NULL,
  `floor` VARCHAR(20) NULL,
  `latitude` DECIMAL(10, 8) NOT NULL,
  `longitude` DECIMAL(11, 8) NOT NULL,
  `is_default` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_addr_user` (`user_id`),
  CONSTRAINT `fk_addr_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Carts Table (Persistent & guest-friendly)
DROP TABLE IF EXISTS `carts`;
CREATE TABLE `carts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NULL,
  `session_id` VARCHAR(100) NULL,
  `store_id` BIGINT UNSIGNED NOT NULL,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `variation_id` BIGINT UNSIGNED NULL,
  `addons_json` LONGTEXT NULL,
  `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
  `price` DECIMAL(12, 2) NOT NULL,
  `item_notes` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_cart_user` (`user_id`),
  INDEX `idx_cart_session` (`session_id`),
  CONSTRAINT `fk_cart_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Coupons Table
DROP TABLE IF EXISTS `coupons`;
CREATE TABLE `coupons` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(50) NOT NULL UNIQUE,
  `title` VARCHAR(150) NOT NULL,
  `discount_type` ENUM('percent', 'amount') NOT NULL DEFAULT 'percent',
  `discount` DECIMAL(12, 2) NOT NULL,
  `min_purchase` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `max_discount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `start_date` DATE NOT NULL,
  `expire_date` DATE NOT NULL,
  `limit_per_user` INT NOT NULL DEFAULT 1,
  `usage_count` INT NOT NULL DEFAULT 0,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_coupon_code` (`code`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Delivery Men Table
DROP TABLE IF EXISTS `delivery_men`;
CREATE TABLE `delivery_men` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL UNIQUE,
  `zone_id` INT UNSIGNED NOT NULL,
  `vehicle_type` VARCHAR(50) NOT NULL DEFAULT 'Motor',
  `vehicle_number` VARCHAR(30) NOT NULL,
  `identity_type` VARCHAR(30) NOT NULL DEFAULT 'KTP',
  `identity_number` VARCHAR(50) NOT NULL,
  `identity_image` VARCHAR(255) NULL,
  `is_online` TINYINT(1) NOT NULL DEFAULT 1,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `current_latitude` DECIMAL(10, 8) NULL,
  `current_longitude` DECIMAL(11, 8) NULL,
  `current_order_id` BIGINT UNSIGNED NULL,
  `rating` DECIMAL(3, 2) NOT NULL DEFAULT 5.00,
  `reviews_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `total_orders` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_dm_zone` (`zone_id`, `is_online`),
  CONSTRAINT `fk_dm_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_dm_zone` FOREIGN KEY (`zone_id`) REFERENCES `zones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Orders Table
DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_code` VARCHAR(30) NOT NULL UNIQUE,
  `customer_id` BIGINT UNSIGNED NOT NULL,
  `store_id` BIGINT UNSIGNED NULL,
  `delivery_man_id` BIGINT UNSIGNED NULL,
  `module_id` INT UNSIGNED NOT NULL,
  `zone_id` INT UNSIGNED NOT NULL,
  `order_amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `delivery_charge` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `coupon_code` VARCHAR(50) NULL,
  `coupon_discount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `tax_amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `total_amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `payment_status` ENUM('unpaid', 'paid', 'refunded') NOT NULL DEFAULT 'unpaid',
  `payment_method` ENUM('cod', 'wallet', 'qris', 'bank_transfer') NOT NULL DEFAULT 'cod',
  `order_status` ENUM('pending', 'confirmed', 'processing', 'handover', 'picked_up', 'on_the_way', 'delivered', 'canceled') NOT NULL DEFAULT 'pending',
  `order_type` ENUM('delivery', 'takeaway', 'parcel') NOT NULL DEFAULT 'delivery',
  `delivery_address_json` LONGTEXT NOT NULL,
  `order_notes` TEXT NULL,
  `otp` VARCHAR(6) NOT NULL,
  `distance_km` DECIMAL(6, 2) NOT NULL DEFAULT 1.00,
  `parcel_details_json` LONGTEXT NULL,
  `is_scheduled` TINYINT(1) NOT NULL DEFAULT 0,
  `scheduled_at` TIMESTAMP NULL,
  `confirmed_at` TIMESTAMP NULL,
  `processing_at` TIMESTAMP NULL,
  `handover_at` TIMESTAMP NULL,
  `picked_up_at` TIMESTAMP NULL,
  `delivered_at` TIMESTAMP NULL,
  `canceled_at` TIMESTAMP NULL,
  `cancellation_reason` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_order_customer` (`customer_id`),
  INDEX `idx_order_store` (`store_id`),
  INDEX `idx_order_dm` (`delivery_man_id`),
  INDEX `idx_order_status` (`order_status`),
  INDEX `idx_order_code` (`order_code`),
  CONSTRAINT `fk_order_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_order_dm` FOREIGN KEY (`delivery_man_id`) REFERENCES `delivery_men` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. Order Items Table
DROP TABLE IF EXISTS `order_items`;
CREATE TABLE `order_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `product_id` BIGINT UNSIGNED NULL,
  `product_name` VARCHAR(200) NOT NULL,
  `price` DECIMAL(12, 2) NOT NULL,
  `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
  `variation_json` LONGTEXT NULL,
  `addons_json` LONGTEXT NULL,
  `total_price` DECIMAL(12, 2) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_item_order` (`order_id`),
  CONSTRAINT `fk_item_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. Delivery Tracking Table
DROP TABLE IF EXISTS `delivery_trackings`;
CREATE TABLE `delivery_trackings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `delivery_man_id` BIGINT UNSIGNED NOT NULL,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `latitude` DECIMAL(10, 8) NOT NULL,
  `longitude` DECIMAL(11, 8) NOT NULL,
  `recorded_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_dt_order` (`order_id`, `delivery_man_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. Wallets Table
DROP TABLE IF EXISTS `wallets`;
CREATE TABLE `wallets` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL UNIQUE,
  `user_type` ENUM('customer', 'vendor', 'delivery_man', 'admin') NOT NULL,
  `balance` DECIMAL(14, 2) NOT NULL DEFAULT 0.00,
  `total_earned` DECIMAL(14, 2) NOT NULL DEFAULT 0.00,
  `total_withdrawn` DECIMAL(14, 2) NOT NULL DEFAULT 0.00,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_wallet_user` (`user_id`),
  CONSTRAINT `fk_wallet_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 18. Wallet Transactions Table
DROP TABLE IF EXISTS `wallet_transactions`;
CREATE TABLE `wallet_transactions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `wallet_id` BIGINT UNSIGNED NOT NULL,
  `amount` DECIMAL(14, 2) NOT NULL,
  `type` ENUM('credit', 'debit') NOT NULL,
  `category` ENUM('order_payment', 'topup', 'order_earning', 'withdrawal', 'refund', 'bonus', 'cashback') NOT NULL,
  `reference_id` VARCHAR(100) NULL,
  `description` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_wt_wallet` (`wallet_id`),
  CONSTRAINT `fk_wt_wallet` FOREIGN KEY (`wallet_id`) REFERENCES `wallets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 19. Reviews Table
DROP TABLE IF EXISTS `reviews`;
CREATE TABLE `reviews` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `store_id` BIGINT UNSIGNED NULL,
  `product_id` BIGINT UNSIGNED NULL,
  `delivery_man_id` BIGINT UNSIGNED NULL,
  `rating` TINYINT UNSIGNED NOT NULL DEFAULT 5,
  `comment` TEXT NULL,
  `reply` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_review_store` (`store_id`),
  INDEX `idx_review_product` (`product_id`),
  INDEX `idx_review_dm` (`delivery_man_id`),
  CONSTRAINT `fk_review_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_review_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 20. Banners Table
DROP TABLE IF EXISTS `banners`;
CREATE TABLE `banners` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `module_id` INT UNSIGNED NULL,
  `zone_id` INT UNSIGNED NULL,
  `title` VARCHAR(150) NOT NULL,
  `image` VARCHAR(255) NOT NULL,
  `banner_type` ENUM('main_banner', 'popup', 'promo_strip') NOT NULL DEFAULT 'main_banner',
  `target_type` ENUM('store', 'product', 'category', 'url') NOT NULL DEFAULT 'store',
  `target_id` VARCHAR(100) NULL,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `priority` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 21. Notifications Table
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `message` TEXT NOT NULL,
  `type` VARCHAR(50) NOT NULL DEFAULT 'order',
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `data_json` LONGTEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_notif_user` (`user_id`, `is_read`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 22. Chats Table
DROP TABLE IF EXISTS `chats`;
CREATE TABLE `chats` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `sender_id` BIGINT UNSIGNED NOT NULL,
  `receiver_id` BIGINT UNSIGNED NOT NULL,
  `message` TEXT NOT NULL,
  `file` VARCHAR(255) NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_chat_order` (`order_id`),
  INDEX `idx_chat_pair` (`sender_id`, `receiver_id`),
  CONSTRAINT `fk_chat_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 23. Business Settings Table
DROP TABLE IF EXISTS `business_settings`;
CREATE TABLE `business_settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `key_name` VARCHAR(100) NOT NULL UNIQUE,
  `value_text` LONGTEXT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_setting_key` (`key_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

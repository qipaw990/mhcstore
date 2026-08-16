-- ==========================================================
-- CicalengkaGO Seeders Data
-- Realistic Localized Data for Cicalengka, Bandung
-- Password for all demo accounts: password
-- ==========================================================

USE `cicalengkago_db`;

-- Default Password Hash for "password"
-- $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi

-- 1. Users
INSERT INTO `users` (`id`, `role`, `name`, `email`, `phone`, `password`, `avatar`, `is_active`, `api_token`) VALUES
(1, 'admin', 'Super Administrator', 'admin@cicalengkago.id', '081234567890', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'assets/images/users/admin.png', 1, 'token_admin_cicalengkago_secret_123'),
(2, 'vendor', 'Owner Geprek Juara', 'vendor@cicalengkago.id', '082211445566', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'assets/images/users/vendor1.png', 1, 'token_vendor_cicalengkago_secret_456'),
(3, 'vendor', 'Owner Sembako Berkah', 'sembako@cicalengkago.id', '082211445577', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'assets/images/users/vendor2.png', 1, 'token_vendor2_cicalengkago_secret_789'),
(4, 'delivery_man', 'Kang Asep Driver', 'driver@cicalengkago.id', '085566778899', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'assets/images/users/driver.png', 1, 'token_driver_cicalengkago_secret_321'),
(5, 'customer', 'Budi Santoso', 'customer@cicalengkago.id', '087788990011', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'assets/images/users/customer.png', 1, 'token_customer_cicalengkago_secret_654')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- 2. Modules
INSERT INTO `modules` (`id`, `name`, `module_type`, `icon`, `thumbnail`, `theme_color`, `description`, `status`, `stores_count`) VALUES
(1, 'Kuliner & Makanan', 'food', 'bi-egg-fried', 'assets/images/modules/food.png', '#ef4444', 'Pesan aneka makanan lezat & minuman segar di Cicalengka', 1, 2),
(2, 'Sembako & Mart', 'grocery', 'bi-basket3', 'assets/images/modules/grocery.png', '#10b981', 'Kebutuhan pokok, sayur, dan dapur cepat sampai', 1, 1),
(3, 'Farmasi & Apotek', 'pharmacy', 'bi-capsule', 'assets/images/modules/pharmacy.png', '#06b6d4', 'Obat-obatan, vitamin & suplemen kesehatan', 1, 1),
(4, 'Olshop Cicalengka', 'ecommerce', 'bi-bag-check', 'assets/images/modules/ecommerce.png', '#8b5cf6', 'Fashion, gadget & produk UMKM Cicalengka', 1, 1),
(5, 'Kirim Paket (Parcel)', 'parcel', 'bi-truck', 'assets/images/modules/parcel.png', '#f59e0b', 'Jasa antar paket kilat & dokumen se-Cicalengka', 1, 0)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- 3. Zones (Cicalengka Area: -6.9840, 107.8340)
INSERT INTO `zones` (`id`, `name`, `coordinates_json`, `status`, `min_delivery_charge`, `per_km_delivery_charge`, `center_latitude`, `center_longitude`) VALUES
(1, 'Zona Cicalengka Raya', '[{"lat": -6.9700, "lng": 107.8200}, {"lat": -6.9700, "lng": 107.8550}, {"lat": -7.0000, "lng": 107.8550}, {"lat": -7.0000, "lng": 107.8200}]', 1, 5000.00, 2500.00, -6.98400000, 107.83400000)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- 4. Stores
INSERT INTO `stores` (`id`, `vendor_id`, `module_id`, `zone_id`, `name`, `phone`, `email`, `logo`, `cover_photo`, `address`, `latitude`, `longitude`, `minimum_order`, `delivery_time`, `delivery_fee`, `tax_percent`, `is_open`, `status`, `rating`, `reviews_count`, `order_count`) VALUES
(1, 2, 1, 1, 'Ayam Geprek Cicalengka Juara', '082211445566', 'geprekjuara@cicalengkago.id', 'assets/images/stores/geprek_logo.png', 'assets/images/stores/geprek_cover.jpg', 'Jl. Raya Cicalengka No. 45, Cicalengka Kulon', -6.98350000, 107.83350000, 10000.00, '15-25 min', 5000.00, 0.00, 1, 'approved', 4.9, 128, 450),
(2, 2, 1, 1, 'Sate Maranggi Alun-Alun Cicalengka', '082211445568', 'maranggi@cicalengkago.id', 'assets/images/stores/maranggi_logo.png', 'assets/images/stores/maranggi_cover.jpg', 'Depan Alun-alun Cicalengka, Bandung', -6.98480000, 107.83450000, 15000.00, '20-30 min', 5000.00, 0.00, 1, 'approved', 4.8, 95, 320),
(3, 3, 2, 1, 'Toko Sembako Berkah Cicalengka', '082211445577', 'sembakoberkah@cicalengkago.id', 'assets/images/stores/sembako_logo.png', 'assets/images/stores/sembako_cover.jpg', 'Jl. Pasar Cicalengka No. 12', -6.98520000, 107.83600000, 20000.00, '20-35 min', 6000.00, 0.00, 1, 'approved', 4.7, 60, 180),
(4, 2, 3, 1, 'Apotek Sehat Medika Cicalengka', '082211445588', 'apoteksehat@cicalengkago.id', 'assets/images/stores/apotek_logo.png', 'assets/images/stores/apotek_cover.jpg', 'Jl. Stasiun Cicalengka No. 8', -6.98250000, 107.83200000, 10000.00, '10-20 min', 5000.00, 0.00, 1, 'approved', 5.0, 42, 110),
(5, 3, 4, 1, 'Cicalengka Hijab & Fashion Store', '082211445599', 'fashion@cicalengkago.id', 'assets/images/stores/fashion_logo.png', 'assets/images/stores/fashion_cover.jpg', 'Jl. Dipati Ukur Cicalengka No. 20', -6.98400000, 107.83500000, 0.00, '1-2 jam', 5000.00, 0.00, 1, 'approved', 4.8, 30, 85)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- 5. Store Schedules
INSERT INTO `store_schedules` (`store_id`, `day_of_week`, `opening_time`, `closing_time`) VALUES
(1, 0, '08:00:00', '22:00:00'), (1, 1, '08:00:00', '22:00:00'), (1, 2, '08:00:00', '22:00:00'),
(1, 3, '08:00:00', '22:00:00'), (1, 4, '08:00:00', '22:00:00'), (1, 5, '08:00:00', '22:00:00'), (1, 6, '08:00:00', '22:00:00'),
(2, 0, '10:00:00', '23:00:00'), (2, 1, '10:00:00', '23:00:00'), (2, 2, '10:00:00', '23:00:00'),
(2, 3, '10:00:00', '23:00:00'), (2, 4, '10:00:00', '23:00:00'), (2, 5, '10:00:00', '23:00:00'), (2, 6, '10:00:00', '23:00:00');

-- 6. Categories
INSERT INTO `categories` (`id`, `module_id`, `parent_id`, `name`, `image`, `icon`, `priority`, `status`) VALUES
(1, 1, 0, 'Ayam & Bebek', 'assets/images/categories/ayam.png', 'bi-egg-fried', 1, 1),
(2, 1, 0, 'Aneka Sate & Daging', 'assets/images/categories/sate.png', 'bi-fire', 2, 1),
(3, 1, 0, 'Minuman & Boba', 'assets/images/categories/minuman.png', 'bi-cup-straw', 3, 1),
(4, 2, 0, 'Beras & Minyak', 'assets/images/categories/beras.png', 'bi-box', 1, 1),
(5, 2, 0, 'Bumbu Dapur & Sayur', 'assets/images/categories/sayur.png', 'bi-flower2', 2, 1),
(6, 3, 0, 'Obat Flu & Batuk', 'assets/images/categories/obat.png', 'bi-capsule', 1, 1),
(7, 3, 0, 'Vitamin & Imunitas', 'assets/images/categories/vitamin.png', 'bi-heart-pulse', 2, 1),
(8, 4, 0, 'Fashion Muslim', 'assets/images/categories/fashion.png', 'bi-person-badge', 1, 1)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- 7. Products
INSERT INTO `products` (`id`, `store_id`, `module_id`, `category_id`, `name`, `description`, `image`, `price`, `discount`, `discount_type`, `unit`, `stock`, `is_veg`, `is_recommended`, `status`, `order_count`, `rating`, `reviews_count`) VALUES
(1, 1, 1, 1, 'Paket Ayam Geprek Sambal Bawang + Nasi', 'Ayam goreng crispy digeprek dengan sambal bawang khas Cicalengka pedas nampol, disajikan dengan nasi pulen hangat dan lalapan.', 'assets/images/products/geprek_sambal.jpg', 18000.00, 3000.00, 'amount', 'porsi', 95, 0, 1, 1, 240, 4.9, 85),
(2, 1, 1, 1, 'Ayam Geprek Keju Mozzarella', 'Ayam geprek krispi dibalut lelehan keju mozzarella premium yang gurih dan melar.', 'assets/images/products/geprek_mozza.jpg', 24000.00, 0.00, 'percent', 'porsi', 50, 0, 1, 1, 120, 4.8, 30),
(3, 1, 1, 3, 'Es Jeruk Peras Segar Asli', 'Jeruk peras segar alami dengan es batu kristal, manis dan menyegarkan dahaga.', 'assets/images/products/es_jeruk.jpg', 6000.00, 0.00, 'percent', 'cup', 150, 1, 1, 1, 190, 4.9, 45),
(4, 1, 1, 3, 'Es Teh Manis Jumbo', 'Es teh manis segar ukuran jumbo 22oz.', 'assets/images/products/es_teh.jpg', 4000.00, 0.00, 'percent', 'cup', 200, 1, 0, 1, 310, 4.8, 60),
(5, 2, 1, 2, 'Sate Maranggi Sapi Porsi 10 Tusuk', 'Sate sapi pilihan berbumbu rempah maranggi khas gurih manis, disajikan dengan sambal tomat kecap pedas.', 'assets/images/products/sate_maranggi.jpg', 35000.00, 5000.00, 'amount', 'porsi', 40, 0, 1, 1, 180, 4.9, 72),
(6, 2, 1, 2, 'Sop Iga Sapi Kuah Rempah Cicalengka', 'Sop iga sapi empuk dengan kuah kaldu rempah hangat, wortel, kentang dan seledri.', 'assets/images/products/sop_iga.jpg', 38000.00, 0.00, 'percent', 'porsi', 30, 0, 1, 1, 95, 4.8, 25),
(7, 3, 2, 4, 'Minyak Goreng Sania 2 Liter', 'Minyak goreng kelapa sawit higienis kemasan pouch 2 Liter.', 'assets/images/products/minyak_sania.jpg', 34000.00, 2000.00, 'amount', 'pouch', 80, 1, 1, 1, 90, 4.8, 18),
(8, 3, 2, 4, 'Beras Pandan Wangi Super 5 Kg', 'Beras pulen aromatik asli Jawa Barat kemasan 5 Kg.', 'assets/images/products/beras_pandan.jpg', 72000.00, 0.00, 'percent', 'karung', 45, 1, 1, 1, 65, 4.7, 12),
(9, 4, 3, 7, 'Enervon-C Multivitamin 30 Tablet', 'Suplemen vitamin C dan vitamin B kompleks untuk menjaga daya tahan tubuh.', 'assets/images/products/enervon_c.jpg', 38000.00, 3000.00, 'amount', 'botol', 30, 1, 1, 1, 55, 5.0, 15),
(10, 5, 4, 8, 'Pashmina Silk Premium Cicalengka', 'Hijab pashmina silk jatuh, mewah, adem dan mudah dibentuk.', 'assets/images/products/pashmina.jpg', 45000.00, 10000.00, 'amount', 'pcs', 60, 1, 1, 1, 40, 4.8, 10)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- 8. Product Variations
INSERT INTO `product_variations` (`id`, `product_id`, `name`, `price`, `stock`) VALUES
(1, 1, 'Level 1 (Pedas Santai)', 18000.00, 100),
(2, 1, 'Level 3 (Pedas Nampol)', 18000.00, 100),
(3, 1, 'Level 5 (Super Geledek)', 20000.00, 100),
(4, 5, 'Bumbu Kecap Tomat', 35000.00, 100),
(5, 5, 'Bumbu Oncom Pedas', 35000.00, 100);

-- 9. Product Addons
INSERT INTO `product_addons` (`id`, `store_id`, `name`, `price`, `status`) VALUES
(1, 1, 'Ekstra Sambal Bawang', 3000.00, 1),
(2, 1, 'Telur Dadar Krispi', 4000.00, 1),
(3, 1, 'Tahu & Tempe Goreng', 3000.00, 1),
(4, 2, 'Lontong Pulen Daun Pisang', 4000.00, 1),
(5, 2, 'Nasi Putih Hangat', 5000.00, 1);

-- 10. Customer Addresses
INSERT INTO `customer_addresses` (`id`, `user_id`, `address_type`, `contact_name`, `contact_phone`, `address`, `road`, `house`, `floor`, `latitude`, `longitude`, `is_default`) VALUES
(1, 5, 'home', 'Budi Santoso', '087788990011', 'Komplek Griya Cicalengka Blok B3 No. 14, Cicalengka Kulon', 'Jl. Griya Utama', 'No. 14', 'Lantai 1', -6.98550000, 107.83500000, 1),
(2, 5, 'work', 'Budi Santoso (Kantor)', '087788990011', 'Ruko Sentra Bisnis Cicalengka No. 5', 'Jl. Raya Stasiun', 'Ruko B5', 'Lt 2', -6.98200000, 107.83100000, 0);

-- 11. Coupons
INSERT INTO `coupons` (`id`, `code`, `title`, `discount_type`, `discount`, `min_purchase`, `max_discount`, `start_date`, `expire_date`, `limit_per_user`, `usage_count`, `status`) VALUES
(1, 'CICAGOHEMAT', 'Diskon Spesial Pengguna Baru Rp 10.000', 'amount', 10000.00, 30000.00, 10000.00, '2025-01-01', '2030-12-31', 1, 12, 1),
(2, 'DISKON20', 'Diskon Kuliner Cicalengka 20%', 'percent', 20.00, 40000.00, 15000.00, '2025-01-01', '2030-12-31', 3, 45, 1),
(3, 'GRATISONGKIR', 'Potongan Ongkir Rp 5.000', 'amount', 5000.00, 25000.00, 5000.00, '2025-01-01', '2030-12-31', 5, 90, 1)
ON DUPLICATE KEY UPDATE `title`=VALUES(`title`);

-- 12. Delivery Men
INSERT INTO `delivery_men` (`id`, `user_id`, `zone_id`, `vehicle_type`, `vehicle_number`, `identity_type`, `identity_number`, `identity_image`, `is_online`, `is_active`, `current_latitude`, `current_longitude`, `current_order_id`, `rating`, `reviews_count`, `total_orders`) VALUES
(1, 4, 1, 'Honda Vario 160 (Hitam)', 'D 4589 CCG', 'KTP', '3204123456780001', 'assets/images/ktp_sample.jpg', 1, 1, -6.98400000, 107.83400000, NULL, 4.9, 115, 340)
ON DUPLICATE KEY UPDATE `vehicle_number`=VALUES(`vehicle_number`);

-- 13. Wallets
INSERT INTO `wallets` (`id`, `user_id`, `user_type`, `balance`, `total_earned`, `total_withdrawn`) VALUES
(1, 1, 'admin', 5500000.00, 5500000.00, 0.00),
(2, 2, 'vendor', 850000.00, 1250000.00, 400000.00),
(3, 3, 'vendor', 420000.00, 620000.00, 200000.00),
(4, 4, 'delivery_man', 245000.00, 545000.00, 300000.00),
(5, 5, 'customer', 250000.00, 0.00, 0.00)
ON DUPLICATE KEY UPDATE `balance`=VALUES(`balance`);

-- 14. Wallet Transactions Sample
INSERT INTO `wallet_transactions` (`wallet_id`, `amount`, `type`, `category`, `reference_id`, `description`, `created_at`) VALUES
(5, 250000.00, 'credit', 'topup', 'TOPUP-998822', 'Top Up Saldo CicagoPay via QRIS Instan', NOW() - INTERVAL 2 DAY),
(4, 15000.00, 'credit', 'order_earning', 'ORD-1002', 'Ongkir Pengantaran Pesanan #ORD-1002', NOW() - INTERVAL 1 DAY),
(2, 45000.00, 'credit', 'order_earning', 'ORD-1002', 'Hasil Penjualan Pesanan #ORD-1002', NOW() - INTERVAL 1 DAY);

-- 15. Banners
INSERT INTO `banners` (`id`, `module_id`, `zone_id`, `title`, `image`, `banner_type`, `target_type`, `target_id`, `status`, `priority`) VALUES
(1, 1, 1, 'Promo Kuliner Cicalengka Diskon 20%', 'assets/images/banners/banner1.jpg', 'main_banner', 'store', '1', 1, 1),
(2, 1, 1, 'Sate Maranggi Khas Alun-Alun', 'assets/images/banners/banner2.jpg', 'main_banner', 'store', '2', 1, 2),
(3, 2, 1, 'Belanja Sembako Cepat Sampai di Rumah', 'assets/images/banners/banner3.jpg', 'main_banner', 'store', '3', 1, 3)
ON DUPLICATE KEY UPDATE `title`=VALUES(`title`);

-- 16. Business Settings
INSERT INTO `business_settings` (`key_name`, `value_text`) VALUES
('business_name', 'CicalengkaGO'),
('phone', '081234567890'),
('email', 'support@cicalengkago.id'),
('address', 'Cicalengka, Kabupaten Bandung, Jawa Barat'),
('currency_symbol', 'Rp'),
('currency_code', 'IDR'),
('admin_commission_percent', '10.00'),
('delivery_man_commission_percent', '80.00'),
('default_location_lat', '-6.9840'),
('default_location_lng', '107.8340'),
('default_location_name', 'Cicalengka, Bandung'),
('free_delivery_over', '100000'),
('pwa_theme_color', '#0d6efd')
ON DUPLICATE KEY UPDATE `value_text`=VALUES(`value_text`);

-- 17. Sample Demo Order (Completed)
INSERT INTO `orders` (`id`, `order_code`, `customer_id`, `store_id`, `delivery_man_id`, `module_id`, `zone_id`, `order_amount`, `delivery_charge`, `coupon_code`, `coupon_discount`, `tax_amount`, `total_amount`, `payment_status`, `payment_method`, `order_status`, `order_type`, `delivery_address_json`, `order_notes`, `otp`, `distance_km`, `delivered_at`, `created_at`) VALUES
(1, 'ORD-1001', 5, 1, 1, 1, 1, 36000.00, 5000.00, 'CICAGOHEMAT', 10000.00, 0.00, 31000.00, 'paid', 'wallet', 'delivered', 'delivery', '{"contact_name":"Budi Santoso","contact_phone":"087788990011","address":"Komplek Griya Cicalengka Blok B3 No. 14, Cicalengka Kulon","lat":-6.9855,"lng":107.8350}', 'Tolong sambal dipisah ya kang.', '4521', 1.2, NOW() - INTERVAL 3 HOUR, NOW() - INTERVAL 4 HOUR);

INSERT INTO `order_items` (`order_id`, `product_id`, `product_name`, `price`, `quantity`, `variation_json`, `addons_json`, `total_price`) VALUES
(1, 1, 'Paket Ayam Geprek Sambal Bawang + Nasi', 15000.00, 2, '{"name":"Level 3 (Pedas Nampol)"}', '{"items":[{"name":"Ekstra Sambal Bawang","price":3000}]}', 36000.00);

-- 18. Notifications Sample
INSERT INTO `notifications` (`user_id`, `title`, `message`, `type`, `is_read`, `data_json`) VALUES
(5, 'Selamat Datang di CicalengkaGO! 🎉', 'Nikmati kemudahan pesan antar makanan, sembako, dan kirim paket di Cicalengka. Gunakan kode promo CICAGOHEMAT.', 'promo', 0, '{"coupon_code":"CICAGOHEMAT"}'),
(5, 'Pesanan Telah Selesai', 'Pesanan #ORD-1001 telah berhasil diantar oleh Kang Asep Driver. Selamat menikmati!', 'order', 1, '{"order_id":1}');

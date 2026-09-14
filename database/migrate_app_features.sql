-- ============================================================
-- Migration: app_features table
-- Menyimpan semua fitur/layanan yang tampil di Flutter app
-- dan dikelola dari admin panel.
-- ============================================================

CREATE TABLE IF NOT EXISTS `app_features` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `feature_type` enum('service_grid','filter_chip','trending_chip','quick_action','home_section') NOT NULL COMMENT 'Jenis fitur',
  `name` varchar(150) NOT NULL COMMENT 'Label tampilan',
  `icon` varchar(100) DEFAULT NULL COMMENT 'Emoji atau nama ikon',
  `icon_type` enum('emoji','material','bi') NOT NULL DEFAULT 'emoji',
  `color` varchar(20) DEFAULT NULL COMMENT 'Hex warna ikon/teks',
  `bg_color` varchar(20) DEFAULT NULL COMMENT 'Hex warna background',
  `action_type` enum('module','route','url','search','none') NOT NULL DEFAULT 'search',
  `action_value` varchar(255) DEFAULT NULL COMMENT 'module_type / route / url / search query',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_feature_type` (`feature_type`, `is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Seed: Service Grid (Kategori Kuliner di home)
-- ============================================================
INSERT IGNORE INTO `app_features`
  (`id`, `feature_type`, `name`, `icon`, `icon_type`, `color`, `bg_color`, `action_type`, `action_value`, `sort_order`, `is_active`)
VALUES
  (1,  'service_grid', 'Ayam & Bebek',     '🍗', 'emoji', '#DC2626', '#FEE2E2', 'search', 'Ayam',       1,  1),
  (2,  'service_grid', 'Seblak & Pedas',   '🌶️', 'emoji', '#E11D48', '#FFE4E6', 'search', 'Seblak',     2,  1),
  (3,  'service_grid', 'Bakso & Mie',      '🍜', 'emoji', '#D97706', '#FEF3C7', 'search', 'Bakso',      3,  1),
  (4,  'service_grid', 'Nasi & Lauk',      '🍚', 'emoji', '#CA8A04', '#FEF9C3', 'search', 'Nasi Goreng',4,  1),
  (5,  'service_grid', 'Kopi & Cafe',      '☕', 'emoji', '#7C3AED', '#EDE9FE', 'search', 'Kopi',       5,  1),
  (6,  'service_grid', 'Snack & Boba',     '🧋', 'emoji', '#16A34A', '#DCFCE7', 'search', 'Snack',      6,  1),
  (7,  'service_grid', 'Sate & Bakaran',   '🍢', 'emoji', '#0891B2', '#CFFAFE', 'search', 'Sate',       7,  1),
  (8,  'service_grid', 'Aneka Minuman',    '🥤', 'emoji', '#2563EB', '#DBEAFE', 'search', 'Minuman',    8,  1);

-- ============================================================
-- Seed: Filter Chips (Horizontal scroll di atas stores)
-- ============================================================
INSERT IGNORE INTO `app_features`
  (`id`, `feature_type`, `name`, `icon`, `icon_type`, `color`, `bg_color`, `action_type`, `action_value`, `sort_order`, `is_active`)
VALUES
  (20, 'filter_chip', 'Semua Kuliner',        '🔥', 'emoji', NULL, NULL, 'search', '',             1, 1),
  (21, 'filter_chip', 'Gratis Ongkir (<300m)','🚶', 'emoji', NULL, NULL, 'search', 'Gratis Ongkir',2, 1),
  (22, 'filter_chip', 'Flash Sale',           '⚡', 'emoji', NULL, NULL, 'search', 'Promo',         3, 1),
  (23, 'filter_chip', 'Ayam & Bebek',         '🍗', 'emoji', NULL, NULL, 'search', 'Ayam',          4, 1),
  (24, 'filter_chip', 'Nasi & Bento',         '🍚', 'emoji', NULL, NULL, 'search', 'Nasi',          5, 1),
  (25, 'filter_chip', 'Mie & Seblak',         '🍜', 'emoji', NULL, NULL, 'search', 'Seblak',        6, 1),
  (26, 'filter_chip', 'Kopi & Boba',          '🧋', 'emoji', NULL, NULL, 'search', 'Kopi',          7, 1),
  (27, 'filter_chip', 'Camilan & Dessert',    '🍰', 'emoji', NULL, NULL, 'search', 'Camilan',       8, 1),
  (28, 'filter_chip', 'Rating Tertinggi',     '⭐', 'emoji', NULL, NULL, 'search', 'Top',           9, 1);

-- ============================================================
-- Seed: Trending Chips (Search suggestions trending)
-- ============================================================
INSERT IGNORE INTO `app_features`
  (`id`, `feature_type`, `name`, `icon`, `icon_type`, `color`, `bg_color`, `action_type`, `action_value`, `sort_order`, `is_active`)
VALUES
  (40, 'trending_chip', 'Seblak Pedas',   '🌶️', 'emoji', NULL, NULL, 'search', 'Seblak',     1, 1),
  (41, 'trending_chip', 'Bento Cake',     '🎂', 'emoji', NULL, NULL, 'search', 'Bento',      2, 1),
  (42, 'trending_chip', 'Burger Bangor',  '🍔', 'emoji', NULL, NULL, 'search', 'Burger',     3, 1),
  (43, 'trending_chip', 'Nasi Goreng',    '🍳', 'emoji', NULL, NULL, 'search', 'Nasi Goreng',4, 1),
  (44, 'trending_chip', 'Sate Maranggi',  '🍢', 'emoji', NULL, NULL, 'search', 'Sate',       5, 1),
  (45, 'trending_chip', 'Bobba Drink',    '🧋', 'emoji', NULL, NULL, 'search', 'Boba',       6, 1);

-- ============================================================
-- Seed: Quick Actions (Tombol di CicalengkaPay wallet card)
-- ============================================================
INSERT IGNORE INTO `app_features`
  (`id`, `feature_type`, `name`, `icon`, `icon_type`, `color`, `bg_color`, `action_type`, `action_value`, `sort_order`, `is_active`)
VALUES
  (60, 'quick_action', 'Kirim',   'send_rounded',                'material', '#6366F1', '#EEF2FF', 'route', 'wallet', 1, 1),
  (61, 'quick_action', 'Top Up',  'add_circle_outline_rounded',  'material', '#10B981', '#ECFDF5', 'route', 'wallet', 2, 1),
  (62, 'quick_action', 'Riwayat', 'history_rounded',             'material', '#F59E0B', '#FFFBEB', 'route', 'wallet', 3, 1),
  (63, 'quick_action', 'Voucher', 'confirmation_number_rounded', 'material', '#EF4444', '#FEF2F2', 'route', 'vouchers',4,1);

-- ============================================================
-- Seed: Home Sections (Toggle visibility sections di home)
-- ============================================================
INSERT IGNORE INTO `app_features`
  (`id`, `feature_type`, `name`, `icon`, `icon_type`, `color`, `bg_color`, `action_type`, `action_value`, `sort_order`, `is_active`)
VALUES
  (80, 'home_section', 'Banner Promo Carousel',       '🖼️', 'emoji', NULL, NULL, 'none', 'banners',             1, 1),
  (81, 'home_section', 'Voucher & Promo Discovery',   '🎟️', 'emoji', NULL, NULL, 'none', 'vouchers',            2, 1),
  (82, 'home_section', 'Flash Sale & Diskon Produk',  '⚡', 'emoji', NULL, NULL, 'none', 'flash_sale',          3, 1),
  (83, 'home_section', 'Gratis Ongkir Merchant Dekat','🚴', 'emoji', NULL, NULL, 'none', 'free_ongkir',         4, 1),
  (84, 'home_section', 'Resto & Toko Paling Hit',     '🏪', 'emoji', NULL, NULL, 'none', 'top_stores',          5, 1),
  (85, 'home_section', 'Menu Makanan Rekomendasi',    '🍱', 'emoji', NULL, NULL, 'none', 'recommended_products', 6, 1);

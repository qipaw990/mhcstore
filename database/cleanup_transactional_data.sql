-- ============================================================
-- CicalengkaGO - Cleanup Transactional Data
-- ============================================================
-- Yang DIHAPUS (data transaksi):
--   orders, order_items, reviews, chats, voice_calls,
--   notifications, carts, wallet_transactions,
--   delivery_trackings, topup_logs, withdraw_requests
--
-- Yang DIRESET (counter/state, data profil tetap):
--   wallets          → balance & earning direset ke 0
--   delivery_men     → batch/trip state direset, total_orders=0, rating=5
--   stores           → rating & order_count direset ke default
--   products         → rating & order_count direset ke default
--   coupons          → usage_count direset ke 0
--
-- Yang TIDAK DIUBAH:
--   users, stores (profil), delivery_men (profil), products,
--   product_variations, product_addons, categories, modules,
--   zones, store_schedules, banners, business_settings,
--   customer_addresses, coupons (data tetap, hanya usage direset)
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ─── 1. HAPUS data transaksi ──────────────────────────────
TRUNCATE TABLE `delivery_trackings`;
TRUNCATE TABLE `voice_calls`;
TRUNCATE TABLE `chats`;
TRUNCATE TABLE `order_items`;
TRUNCATE TABLE `reviews`;
TRUNCATE TABLE `notifications`;
TRUNCATE TABLE `carts`;
TRUNCATE TABLE `wallet_transactions`;
TRUNCATE TABLE `topup_logs`;
TRUNCATE TABLE `withdraw_requests`;
TRUNCATE TABLE `orders`;

-- ─── 2. RESET saldo wallet ke 0 (tetap ada, tapi kosong) ──
UPDATE `wallets`
SET `balance`         = 0.00,
    `total_earned`    = 0.00,
    `total_withdrawn` = 0.00;

-- ─── 3. RESET state aktif driver (profil & data diri tetap) 
UPDATE `delivery_men`
SET `current_order_id` = NULL,
    `active_batch_id`  = NULL,
    `active_order_ids` = NULL,
    `total_orders`     = 0,
    `rating`           = 5.00,
    `reviews_count`    = 0;

-- ─── 4. RESET statistik toko (nama/logo/alamat tetap) ─────
UPDATE `stores`
SET `rating`         = 5.00,
    `reviews_count`  = 0,
    `order_count`    = 0;

-- ─── 5. RESET statistik produk (nama/harga/foto tetap) ────
UPDATE `products`
SET `rating`         = 5.00,
    `reviews_count`  = 0,
    `order_count`    = 0;

-- ─── 6. RESET penggunaan kupon ────────────────────────────
UPDATE `coupons`
SET `usage_count` = 0;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- SELESAI. Data bersih. Semua user/toko/driver/zona tetap ada.
-- ============================================================

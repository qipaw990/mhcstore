-- =============================================================
-- Migration SQL Script: Multi-Order Batch Delivery (CasaOS)
-- Modifikasi Struktur Tabel untuk Fitur Batch Pickup CicalengkaGO
-- =============================================================

-- 1. Tambah Kolom Batch Pickup & Sequence di Tabel `orders`
ALTER TABLE `orders` 
    ADD COLUMN IF NOT EXISTS `delivery_batch_id` VARCHAR(24) NULL DEFAULT NULL AFTER `delivery_man_id`,
    ADD COLUMN IF NOT EXISTS `pickup_sequence`   TINYINT UNSIGNED NOT NULL DEFAULT 1 AFTER `delivery_batch_id`;

-- 2. Tambah Kolom Active Batch di Tabel `delivery_men`
ALTER TABLE `delivery_men` 
    ADD COLUMN IF NOT EXISTS `active_batch_id`  VARCHAR(24) NULL DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `active_order_ids` TEXT NULL DEFAULT NULL;

-- 3. Tambah Index untuk Optimasi Query Batch
SET @dbname = DATABASE();
SET @tablename = "orders";
SET @columnname = "delivery_batch_id";
SET @preparedStatement = (SELECT IF(
    (
        SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
        WHERE (table_name = @tablename)
        AND (table_schema = @dbname)
        AND (index_name = "idx_batch")
    ) > 0,
    "SELECT 1",
    "CREATE INDEX idx_batch ON orders (delivery_batch_id);"
));
PREPARE createIdx FROM @preparedStatement;
EXECUTE createIdx;
DEALLOCATE PREPARE createIdx;

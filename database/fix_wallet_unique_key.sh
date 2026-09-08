#!/bin/bash
# fix_wallet_unique_key.sh - Jalankan di dalam container Docker CicalengkaGO
# Menghapus constraint UNIQUE KEY user_id pada tabel wallets 
# agar setiap user bisa memiliki dompet terpisah per user_type (customer, delivery_man, vendor)

set -e

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_NAME="${DB_NAME:-cicalengkago}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-}"

echo "=== FIX: Wallet UNIQUE KEY Migration ==="
echo "Connecting to database: ${DB_NAME} on ${DB_HOST}:${DB_PORT}"

mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" ${DB_PASS:+-p"$DB_PASS"} "$DB_NAME" <<SQL

-- Step 1: Check current wallet table structure
SELECT 'Current wallets table structure:' AS info;
SHOW CREATE TABLE wallets\G

-- Step 2: Drop the unique constraint on user_id alone (we'll add composite unique key)
-- First check if the unique key exists
SET @sql = IF(
    EXISTS (
        SELECT 1 FROM information_schema.STATISTICS
        WHERE table_schema = DATABASE()
          AND table_name = 'wallets'
          AND index_name = 'user_id'
          AND non_unique = 0
    ),
    'ALTER TABLE wallets DROP INDEX user_id',
    'SELECT "UNIQUE KEY user_id does not exist, skipping" AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Step 3: Add composite unique key on (user_id, user_type) so each user can have one wallet per type
ALTER TABLE wallets ADD CONSTRAINT uk_wallet_user_type UNIQUE KEY (user_id, user_type);

-- Step 4: Fix any existing wallets where delivery_man user has user_type='customer'
-- First, identify drivers who have a 'customer' type wallet
SELECT 'Checking drivers with wrong wallet user_type:' AS info;
SELECT u.id as user_id, u.name, w.id as wallet_id, w.user_type, w.balance
FROM delivery_men dm
JOIN users u ON dm.user_id = u.id
JOIN wallets w ON w.user_id = u.id
WHERE w.user_type != 'delivery_man';

-- Step 5: For each driver that only has a 'customer' wallet, create or update to delivery_man wallet
-- Insert new delivery_man wallet for any driver that doesn't have one yet
INSERT IGNORE INTO wallets (user_id, user_type, balance, total_earned, total_withdrawn)
SELECT 
    dm.user_id,
    'delivery_man',
    COALESCE(w.balance, 0.00),
    COALESCE(w.total_earned, 0.00),
    COALESCE(w.total_withdrawn, 0.00)
FROM delivery_men dm
LEFT JOIN wallets w ON w.user_id = dm.user_id AND w.user_type = 'delivery_man'
WHERE w.id IS NULL;

-- Step 6: Show result
SELECT 'Wallet table after fix:' AS info;
SELECT w.id, w.user_id, w.user_type, w.balance, u.name
FROM wallets w
JOIN users u ON w.user_id = u.id
ORDER BY w.user_id, w.user_type;

SELECT 'Migration complete!' AS result;

SQL

echo ""
echo "=== Migration Complete ==="
echo "Now each user can have separate wallets per user_type."
echo "Run the PHP debug script to verify: php database/debug_driver_wallet.php"

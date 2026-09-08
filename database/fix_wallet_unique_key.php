<?php
/**
 * PERBAIKAN KRITIS: Wallet UNIQUE KEY Migration
 *
 * Masalah: Tabel wallets memiliki UNIQUE KEY hanya pada user_id,
 * sehingga setiap user hanya bisa punya 1 wallet. Ini menyebabkan
 * driver tidak bisa memiliki wallet dengan user_type='delivery_man'
 * terpisah dari wallet customer mereka.
 *
 * Solusi: Ubah constraint menjadi UNIQUE KEY pada (user_id, user_type)
 *
 * Jalankan: php database/fix_wallet_unique_key.php
 */
require_once __DIR__ . '/../app/autoload.php';

use App\Core\Database;

echo "=== PERBAIKAN: Wallet UNIQUE KEY Migration ===\n\n";

// Step 1: Check current unique key
echo "--- Cek constraint saat ini ---\n";
$indexes = Database::query("SHOW INDEX FROM `wallets`");
foreach ($indexes as $idx) {
    echo "  Key: {$idx['Key_name']} | Column: {$idx['Column_name']} | Non_unique: {$idx['Non_unique']}\n";
}

// Step 2: Check if unique key on user_id alone exists
$hasOldUniqueKey = false;
foreach ($indexes as $idx) {
    if ($idx['Key_name'] === 'user_id' && $idx['Non_unique'] == 0) {
        $hasOldUniqueKey = true;
        break;
    }
}

$hasCompositeKey = false;
foreach ($indexes as $idx) {
    if ($idx['Key_name'] === 'uk_wallet_user_type') {
        $hasCompositeKey = true;
        break;
    }
}

echo "\nHasOldUniqueKey (user_id alone): " . ($hasOldUniqueKey ? 'YA' : 'TIDAK') . "\n";
echo "HasCompositeKey (user_id, user_type): " . ($hasCompositeKey ? 'YA' : 'TIDAK') . "\n\n";

if (!$hasOldUniqueKey && $hasCompositeKey) {
    echo "Database sudah dikonfigurasi dengan benar. Tidak ada yang perlu diubah.\n";
} else {
    // Step 3: Drop old unique key
    if ($hasOldUniqueKey) {
        echo "--- Menghapus UNIQUE KEY lama pada user_id... ---\n";
        try {
            Database::execute("ALTER TABLE `wallets` DROP INDEX `user_id`");
            echo "BERHASIL: UNIQUE KEY user_id dihapus.\n";
        } catch (\Throwable $e) {
            echo "GAGAL: " . $e->getMessage() . "\n";
        }
    }

    // Also drop idx_wallet_user if it conflicts
    $hasIdxWallet = false;
    foreach ($indexes as $idx) {
        if ($idx['Key_name'] === 'idx_wallet_user') {
            $hasIdxWallet = true;
            break;
        }
    }
    if ($hasIdxWallet) {
        try {
            Database::execute("ALTER TABLE `wallets` DROP INDEX `idx_wallet_user`");
            echo "BERHASIL: INDEX idx_wallet_user dihapus.\n";
        } catch (\Throwable $e) {
            // ignore if already gone
        }
    }

    // Step 4: Add composite unique key
    if (!$hasCompositeKey) {
        echo "\n--- Menambahkan UNIQUE KEY (user_id, user_type)... ---\n";
        try {
            Database::execute("ALTER TABLE `wallets` ADD CONSTRAINT `uk_wallet_user_type` UNIQUE KEY (`user_id`, `user_type`)");
            echo "BERHASIL: UNIQUE KEY (user_id, user_type) ditambahkan.\n";
        } catch (\Throwable $e) {
            echo "GAGAL: " . $e->getMessage() . "\n";
            // Try without constraint name
            try {
                Database::execute("ALTER TABLE `wallets` ADD UNIQUE KEY (`user_id`, `user_type`)");
                echo "BERHASIL (tanpa nama constraint).\n";
            } catch (\Throwable $e2) {
                echo "GAGAL (tanpa nama constraint): " . $e2->getMessage() . "\n";
            }
        }
    }

    // Step 5: Add non-unique index for user_id (for FK queries)
    try {
        Database::execute("ALTER TABLE `wallets` ADD INDEX `idx_wallet_user` (`user_id`)");
        echo "BERHASIL: Index idx_wallet_user ditambahkan.\n";
    } catch (\Throwable $e) {
        // OK if already exists
    }
}

// Step 6: Create delivery_man wallets for drivers that only have customer wallets
echo "\n--- Memeriksa dan membuat wallet delivery_man untuk driver... ---\n";
$drivers = Database::query("
    SELECT dm.id as dm_id, dm.user_id, u.name
    FROM delivery_men dm
    JOIN users u ON dm.user_id = u.id
");

$created = 0;
$existing = 0;

foreach ($drivers as $dm) {
    $dmWallet = Database::fetchOne(
        "SELECT id, balance FROM wallets WHERE user_id = ? AND user_type = 'delivery_man' LIMIT 1",
        [$dm['user_id']]
    );

    if (!$dmWallet) {
        // Check if they have any wallet
        $anyWallet = Database::fetchOne(
            "SELECT id, balance, total_earned, total_withdrawn FROM wallets WHERE user_id = ? LIMIT 1",
            [$dm['user_id']]
        );

        $balance = $anyWallet ? (float)$anyWallet['balance'] : 0.0;
        $earned = $anyWallet ? (float)$anyWallet['total_earned'] : 0.0;
        $withdrawn = $anyWallet ? (float)$anyWallet['total_withdrawn'] : 0.0;

        try {
            Database::insert('wallets', [
                'user_id'         => $dm['user_id'],
                'user_type'       => 'delivery_man',
                'balance'         => $balance,
                'total_earned'    => $earned,
                'total_withdrawn' => $withdrawn,
            ]);
            echo "  DIBUAT: Wallet delivery_man untuk driver {$dm['name']} (user_id={$dm['user_id']}) balance={$balance}\n";
            $created++;
        } catch (\Throwable $e) {
            echo "  GAGAL membuat wallet untuk {$dm['name']}: " . $e->getMessage() . "\n";
        }
    } else {
        echo "  OK: Wallet delivery_man sudah ada untuk {$dm['name']} (user_id={$dm['user_id']}) balance={$dmWallet['balance']}\n";
        $existing++;
    }
}

echo "\nTotal dibuat: $created | Sudah ada: $existing\n";

// Step 7: Show final state
echo "\n--- Status Akhir Tabel wallets ---\n";
$allWallets = Database::query("
    SELECT w.id, w.user_id, w.user_type, w.balance, w.total_earned, u.name
    FROM wallets w
    JOIN users u ON w.user_id = u.id
    ORDER BY w.user_id, w.user_type
");
foreach ($allWallets as $w) {
    echo "  wallet.id={$w['id']} user_id={$w['user_id']} type={$w['user_type']} balance={$w['balance']} name={$w['name']}\n";
}

echo "\n=== MIGRASI SELESAI ===\n";
echo "Jalankan: php database/debug_driver_wallet.php untuk verifikasi\n";

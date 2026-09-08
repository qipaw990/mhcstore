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

require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/models/Wallet.php';

if (!defined('APP_PATH')) {
    define('APP_PATH', __DIR__ . '/../app');
}

use App\Core\Database;
use App\Models\Wallet;

echo "=== PERBAIKAN: Wallet UNIQUE KEY Migration ===\n\n";

try {
    // Step 1: Check current unique keys
    echo "--- Cek constraint saat ini ---\n";
    $indexes = Database::query("SHOW INDEX FROM `wallets`");
    foreach ($indexes as $idx) {
        echo "  Key: {$idx['Key_name']} | Column: {$idx['Column_name']} | Non_unique: {$idx['Non_unique']}\n";
    }

    // Step 2: Determine what exists
    $hasOldUniqueKey = false;
    $hasCompositeKey = false;
    $hasIdxWalletUser = false;

    foreach ($indexes as $idx) {
        if ($idx['Key_name'] === 'user_id' && $idx['Non_unique'] == 0) {
            $hasOldUniqueKey = true;
        }
        if ($idx['Key_name'] === 'uk_wallet_user_type') {
            $hasCompositeKey = true;
        }
        if ($idx['Key_name'] === 'idx_wallet_user') {
            $hasIdxWalletUser = true;
        }
    }

    echo "\nHasOldUniqueKey (user_id saja): " . ($hasOldUniqueKey ? 'YA' : 'TIDAK') . "\n";
    echo "HasCompositeKey (user_id, user_type): " . ($hasCompositeKey ? 'YA' : 'TIDAK') . "\n\n";

    if (!$hasOldUniqueKey && $hasCompositeKey) {
        echo "Database sudah dikonfigurasi dengan benar. Memeriksa wallet driver...\n\n";
    } else {
        // Step 3: Drop old unique key on user_id alone
        if ($hasOldUniqueKey) {
            echo "--- Menghapus UNIQUE KEY lama pada user_id... ---\n";
            try {
                Database::execute("ALTER TABLE `wallets` DROP INDEX `user_id`");
                echo "BERHASIL: UNIQUE KEY user_id dihapus.\n";
            } catch (\Throwable $e) {
                echo "GAGAL hapus user_id: " . $e->getMessage() . "\n";
            }
        }

        // Drop idx_wallet_user if it conflicts
        if ($hasIdxWalletUser) {
            try {
                Database::execute("ALTER TABLE `wallets` DROP INDEX `idx_wallet_user`");
                echo "BERHASIL: INDEX idx_wallet_user dihapus.\n";
            } catch (\Throwable $e) {
                // ignore
            }
        }

        // Step 4: Add composite unique key (user_id, user_type)
        if (!$hasCompositeKey) {
            echo "\n--- Menambahkan UNIQUE KEY (user_id, user_type)... ---\n";
            $addedComposite = false;
            try {
                Database::execute("ALTER TABLE `wallets` ADD CONSTRAINT `uk_wallet_user_type` UNIQUE KEY (`user_id`, `user_type`)");
                echo "BERHASIL: UNIQUE KEY (user_id, user_type) ditambahkan.\n";
                $addedComposite = true;
            } catch (\Throwable $e) {
                echo "Coba tanpa nama constraint...\n";
                try {
                    Database::execute("ALTER TABLE `wallets` ADD UNIQUE KEY `uk_wallet_user_type` (`user_id`, `user_type`)");
                    echo "BERHASIL (tanpa constraint keyword).\n";
                    $addedComposite = true;
                } catch (\Throwable $e2) {
                    echo "GAGAL: " . $e2->getMessage() . "\n";
                }
            }
        }

        // Step 5: Re-add non-unique index for user_id for FK performance
        try {
            Database::execute("ALTER TABLE `wallets` ADD INDEX `idx_wallet_user` (`user_id`)");
            echo "BERHASIL: Index idx_wallet_user ditambahkan.\n";
        } catch (\Throwable $e) {
            // OK jika sudah ada
        }
    }

    // Step 6: Cek dan buat wallet delivery_man untuk semua driver
    echo "\n--- Memeriksa dan membuat wallet delivery_man untuk setiap driver ---\n";
    $drivers = Database::query("
        SELECT dm.id as dm_id, dm.user_id, u.name
        FROM delivery_men dm
        JOIN users u ON dm.user_id = u.id
    ");

    if (empty($drivers)) {
        echo "Tidak ada driver ditemukan.\n";
    }

    $created = 0;
    $already = 0;

    foreach ($drivers as $dm) {
        $dmWallet = Database::fetchOne(
            "SELECT id, balance FROM wallets WHERE user_id = ? AND user_type = 'delivery_man' LIMIT 1",
            [$dm['user_id']]
        );

        if (!$dmWallet) {
            // Cek apakah user punya wallet dengan type lain (customer)
            $anyWallet = Database::fetchOne(
                "SELECT id, balance, total_earned, total_withdrawn FROM wallets WHERE user_id = ? LIMIT 1",
                [$dm['user_id']]
            );

            $balance   = $anyWallet ? (float)$anyWallet['balance'] : 0.0;
            $earned    = $anyWallet ? (float)$anyWallet['total_earned'] : 0.0;
            $withdrawn = $anyWallet ? (float)$anyWallet['total_withdrawn'] : 0.0;

            try {
                Database::insert('wallets', [
                    'user_id'         => $dm['user_id'],
                    'user_type'       => 'delivery_man',
                    'balance'         => $balance,
                    'total_earned'    => $earned,
                    'total_withdrawn' => $withdrawn,
                ]);
                echo "  DIBUAT: Wallet delivery_man untuk driver {$dm['name']} (user_id={$dm['user_id']}) saldo=Rp{$balance}\n";
                $created++;
            } catch (\Throwable $e) {
                echo "  GAGAL buat wallet untuk {$dm['name']}: " . $e->getMessage() . "\n";
            }
        } else {
            echo "  OK: Wallet delivery_man sudah ada untuk {$dm['name']} (user_id={$dm['user_id']}) saldo=Rp{$dmWallet['balance']}\n";
            $already++;
        }
    }

    echo "\nTotal dibuat: $created | Sudah ada: $already\n";

    // Step 7: Show final wallet state
    echo "\n--- Status Akhir Tabel wallets ---\n";
    $allWallets = Database::query("
        SELECT w.id, w.user_id, w.user_type, w.balance, w.total_earned, u.name
        FROM wallets w
        JOIN users u ON w.user_id = u.id
        ORDER BY w.user_id, w.user_type
    ");
    foreach ($allWallets as $w) {
        echo "  wallet.id={$w['id']} user_id={$w['user_id']} type={$w['user_type']} saldo=Rp{$w['balance']} name={$w['name']}\n";
    }

    // Step 8: Verify final index state
    echo "\n--- Index akhir tabel wallets ---\n";
    $finalIndexes = Database::query("SHOW INDEX FROM `wallets`");
    foreach ($finalIndexes as $idx) {
        echo "  Key: {$idx['Key_name']} | Column: {$idx['Column_name']} | Non_unique: {$idx['Non_unique']}\n";
    }

    echo "\n=== MIGRASI SELESAI ===\n";
    echo "Jalankan: php database/repair_driver_commissions.php untuk kredit ulang komisi\n";

} catch (\Throwable $e) {
    echo "ERROR FATAL: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

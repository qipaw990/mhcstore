<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Database;
use Exception;

class Wallet extends Model
{
    protected string $table = 'wallets';
    protected array $fillable = ['user_id', 'user_type', 'balance', 'total_earned', 'total_withdrawn'];

    public function getOrCreate(int $userId, string $userType = 'customer'): array
    {
        $wallet = $this->firstWhere('user_id', $userId);
        if (!$wallet) {
            try {
                $id = $this->create([
                    'user_id'         => $userId,
                    'user_type'       => $userType,
                    'balance'         => 0.00,
                    'total_earned'    => 0.00,
                    'total_withdrawn' => 0.00
                ]);
                $wallet = $this->find($id);
            } catch (\Throwable $e) {
                $wallet = $this->firstWhere('user_id', $userId);
            }
        }

        if ($wallet && $userType !== 'customer' && ($wallet['user_type'] ?? '') !== $userType) {
            Database::update('wallets', ['user_type' => $userType], 'id = ?', [$wallet['id']]);
            $wallet['user_type'] = $userType;
        }

        return $wallet ?: [
            'id'              => 0,
            'user_id'         => $userId,
            'user_type'       => $userType,
            'balance'         => 0.00,
            'total_earned'    => 0.00,
            'total_withdrawn' => 0.00
        ];
    }

    public function credit(int $userId, float $amount, string $category, string $description, ?string $refId = null, ?string $userType = null): bool
    {
        // Safe mapping to match MySQL ENUM column
        if ($category === 'order_refund') {
            $category = 'refund';
        }

        $targetType = $userType;
        if (!$targetType && $category === 'order_earning') {
            $dm = Database::fetchOne("SELECT id FROM `delivery_men` WHERE `user_id` = ? LIMIT 1", [$userId]);
            if ($dm) {
                $targetType = 'delivery_man';
            }
        }
        $targetType = $targetType ?: 'customer';

        $wallet = $this->getOrCreate($userId, $targetType);

        // Atomic calculation in MySQL
        Database::execute(
            "UPDATE `wallets` SET `balance` = `balance` + ?, `total_earned` = `total_earned` + ?, `updated_at` = NOW() WHERE `id` = ?",
            [$amount, $amount, (int)$wallet['id']]
        );

        try {
            Database::insert('wallet_transactions', [
                'wallet_id'    => (int)$wallet['id'],
                'amount'       => $amount,
                'type'         => 'credit',
                'category'     => $category,
                'reference_id' => $refId,
                'description'  => $description
            ]);
        } catch (\Throwable $e) {
            // If category fails due to MySQL ENUM, heal table and retry
            try {
                Database::execute("ALTER TABLE `wallet_transactions` MODIFY COLUMN `category` VARCHAR(50) NOT NULL DEFAULT 'transfer'");
                Database::insert('wallet_transactions', [
                    'wallet_id'    => (int)$wallet['id'],
                    'amount'       => $amount,
                    'type'         => 'credit',
                    'category'     => $category,
                    'reference_id' => $refId,
                    'description'  => $description
                ]);
            } catch (\Throwable $e2) {
                // Fallback to basic credit category
                Database::insert('wallet_transactions', [
                    'wallet_id'    => (int)$wallet['id'],
                    'amount'       => $amount,
                    'type'         => 'credit',
                    'category'     => 'topup',
                    'reference_id' => $refId,
                    'description'  => $description
                ]);
            }
        }

        return true;
    }

    public function debit(int $userId, float $amount, string $category, string $description, ?string $refId = null, string $userType = 'customer'): bool
    {
        // Safe mapping to match MySQL ENUM column
        if ($category === 'order_refund') {
            $category = 'refund';
        }

        $wallet = $this->getOrCreate($userId, $userType);

        // Atomic UPDATE with balance condition check to prevent race conditions & double debiting
        $affected = Database::execute(
            "UPDATE `wallets` SET `balance` = `balance` - ? WHERE `id` = ? AND `balance` >= ?",
            [$amount, $wallet['id'], $amount]
        );

        if (!$affected) {
            throw new Exception("Saldo CicalengkaPay tidak mencukupi untuk melakukan transaksi ini.");
        }

        try {
            Database::insert('wallet_transactions', [
                'wallet_id'    => $wallet['id'],
                'amount'       => $amount,
                'type'         => 'debit',
                'category'     => $category,
                'reference_id' => $refId,
                'description'  => $description
            ]);
        } catch (\Throwable $e) {
            // If category fails due to MySQL ENUM, heal table and retry
            try {
                Database::execute("ALTER TABLE `wallet_transactions` MODIFY COLUMN `category` VARCHAR(50) NOT NULL DEFAULT 'transfer'");
                Database::insert('wallet_transactions', [
                    'wallet_id'    => $wallet['id'],
                    'amount'       => $amount,
                    'type'         => 'debit',
                    'category'     => $category,
                    'reference_id' => $refId,
                    'description'  => $description
                ]);
            } catch (\Throwable $e2) {
                // Fallback to standard debit category
                Database::insert('wallet_transactions', [
                    'wallet_id'    => $wallet['id'],
                    'amount'       => $amount,
                    'type'         => 'debit',
                    'category'     => 'order_payment',
                    'reference_id' => $refId,
                    'description'  => $description
                ]);
            }
        }

        return true;
    }

    public function getTransactions(int $userId, int $limit = 20, ?string $userType = null): array
    {
        $wallet = null;
        if ($userType) {
            $wallet = Database::fetchOne("SELECT * FROM `wallets` WHERE `user_id` = ? AND `user_type` = ? LIMIT 1", [$userId, $userType]);
        }
        if (!$wallet) {
            $dm = Database::fetchOne("SELECT id FROM `delivery_men` WHERE `user_id` = ? LIMIT 1", [$userId]);
            if ($dm) {
                $wallet = Database::fetchOne("SELECT * FROM `wallets` WHERE `user_id` = ? AND `user_type` = 'delivery_man' LIMIT 1", [$userId]);
            }
        }
        if (!$wallet) {
            $wallet = $this->firstWhere('user_id', $userId);
        }
        if (!$wallet) return [];

        return Database::query("SELECT * FROM `wallet_transactions` WHERE `wallet_id` = ? ORDER BY `id` DESC LIMIT {$limit}", [$wallet['id']]);
    }
}

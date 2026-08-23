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
            $id = $this->create([
                'user_id' => $userId,
                'user_type' => $userType,
                'balance' => 0.00,
                'total_earned' => 0.00,
                'total_withdrawn' => 0.00
            ]);
            $wallet = $this->find($id);
        }
        return $wallet;
    }

    public function credit(int $userId, float $amount, string $category, string $description, ?string $refId = null): bool
    {
        // Safe mapping to match MySQL ENUM column
        if ($category === 'order_refund') {
            $category = 'refund';
        }

        $wallet = $this->getOrCreate($userId, 'customer');

        // Atomic calculation in MySQL
        Database::execute(
            "UPDATE `wallets` SET `balance` = `balance` + ?, `total_earned` = `total_earned` + ? WHERE `id` = ?",
            [$amount, $amount, $wallet['id']]
        );

        Database::insert('wallet_transactions', [
            'wallet_id'    => $wallet['id'],
            'amount'       => $amount,
            'type'         => 'credit',
            'category'     => $category,
            'reference_id' => $refId,
            'description'  => $description
        ]);

        return true;
    }

    public function debit(int $userId, float $amount, string $category, string $description, ?string $refId = null): bool
    {
        // Safe mapping to match MySQL ENUM column
        if ($category === 'order_refund') {
            $category = 'refund';
        }

        $wallet = $this->getOrCreate($userId, 'customer');

        // Atomic UPDATE with balance condition check to prevent race conditions & double debiting
        $affected = Database::execute(
            "UPDATE `wallets` SET `balance` = `balance` - ? WHERE `id` = ? AND `balance` >= ?",
            [$amount, $wallet['id'], $amount]
        );

        if (!$affected) {
            throw new Exception("Saldo CicalengkaPay tidak mencukupi untuk melakukan transaksi ini.");
        }

        Database::insert('wallet_transactions', [
            'wallet_id'    => $wallet['id'],
            'amount'       => $amount,
            'type'         => 'debit',
            'category'     => $category,
            'reference_id' => $refId,
            'description'  => $description
        ]);

        return true;
    }

    public function getTransactions(int $userId, int $limit = 20): array
    {
        $wallet = $this->firstWhere('user_id', $userId);
        if (!$wallet) return [];

        return Database::query("SELECT * FROM `wallet_transactions` WHERE `wallet_id` = ? ORDER BY `id` DESC LIMIT {$limit}", [$wallet['id']]);
    }
}

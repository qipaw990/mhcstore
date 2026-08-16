<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Database;
use Exception;

class WithdrawRequest extends Model
{
    protected string $table = 'withdraw_requests';
    protected array $fillable = [
        'withdraw_code', 'user_id', 'user_type', 'amount', 'bank_name',
        'account_number', 'account_holder', 'status', 'admin_notes',
        'requested_at', 'processed_at'
    ];

    public function getByUser(int $userId, string $userType, int $limit = 50): array
    {
        return Database::query(
            "SELECT * FROM `withdraw_requests` WHERE `user_id` = ? AND `user_type` = ? ORDER BY `id` DESC LIMIT {$limit}",
            [$userId, $userType]
        );
    }

    public function requestPayout(int $userId, string $userType, float $amount, string $bankName, string $accNumber, string $accHolder): array
    {
        if ($amount < 10000) {
            throw new Exception("Minimal penarikan saldo adalah Rp 10.000.");
        }

        $walletModel = new Wallet();
        $wallet = $walletModel->getOrCreate($userId, $userType);

        if ((float)($wallet['balance'] ?? 0) < $amount) {
            throw new Exception("Saldo Anda tidak mencukupi untuk penarikan sebesar " . format_rupiah($amount) . " (Saldo saat ini: " . format_rupiah($wallet['balance'] ?? 0) . ").");
        }

        $withdrawCode = 'WD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));

        // Debit wallet
        $walletModel->debit(
            $userId,
            $amount,
            'withdrawal',
            "Penarikan saldo ({$bankName} - {$accNumber})",
            $withdrawCode
        );

        // Update total_withdrawn
        Database::execute(
            "UPDATE `wallets` SET `total_withdrawn` = `total_withdrawn` + ? WHERE `id` = ?",
            [$amount, $wallet['id']]
        );

        // Create withdraw request record
        $id = $this->create([
            'withdraw_code'  => $withdrawCode,
            'user_id'        => $userId,
            'user_type'      => $userType,
            'amount'         => $amount,
            'bank_name'      => $bankName,
            'account_number' => $accNumber,
            'account_holder' => $accHolder,
            'status'         => 'pending',
            'requested_at'   => date('Y-m-d H:i:s')
        ]);

        return $this->find($id);
    }

    public function getTotalWithdrawn(int $userId, string $userType): float
    {
        $res = Database::fetchOne(
            "SELECT COALESCE(SUM(amount), 0) as total FROM `withdraw_requests` WHERE `user_id` = ? AND `user_type` = ? AND `status` != 'rejected'",
            [$userId, $userType]
        );
        return (float)($res['total'] ?? 0);
    }

    public function getPendingWithdrawn(int $userId, string $userType): float
    {
        $res = Database::fetchOne(
            "SELECT COALESCE(SUM(amount), 0) as total FROM `withdraw_requests` WHERE `user_id` = ? AND `user_type` = ? AND `status` = 'pending'",
            [$userId, $userType]
        );
        return (float)($res['total'] ?? 0);
    }
}

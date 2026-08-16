<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Database;
use Exception;

class TopupLog extends Model
{
    protected string $table = 'topup_logs';
    protected array $fillable = [
        'topup_code', 'user_id', 'amount', 'payment_method',
        'payment_type', 'status', 'snap_token', 'notes',
        'created_at', 'updated_at'
    ];

    public function recordPending(int $userId, string $topupCode, float $amount, ?string $snapToken = null, ?string $paymentType = 'midtrans', ?string $notes = null): array
    {
        // Check if exists
        $existing = Database::fetchOne("SELECT * FROM `topup_logs` WHERE `topup_code` = ? LIMIT 1", [$topupCode]);
        if ($existing) {
            return $existing;
        }

        $id = $this->create([
            'topup_code'     => $topupCode,
            'user_id'        => $userId,
            'amount'         => $amount,
            'payment_method' => 'midtrans',
            'payment_type'   => $paymentType ?: 'midtrans_snap',
            'status'         => 'pending',
            'snap_token'     => $snapToken,
            'notes'          => $notes ?: 'Menunggu pembayaran via Midtrans Snap',
            'created_at'     => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s')
        ]);

        return $this->find($id) ?: [];
    }

    public function markSuccess(string $topupCode, ?string $paymentType = null, ?string $notes = null): bool
    {
        $log = Database::fetchOne("SELECT * FROM `topup_logs` WHERE `topup_code` = ? LIMIT 1", [$topupCode]);
        if (!$log) {
            // Extract user id from topup_code (e.g. TOPUP-4-123456)
            $userId = 0;
            if (preg_match('/^TOPUP-(\d+)-/i', $topupCode, $m)) {
                $userId = (int)$m[1];
            }
            if ($userId > 0) {
                $this->create([
                    'topup_code'     => $topupCode,
                    'user_id'        => $userId,
                    'amount'         => 0,
                    'payment_method' => 'midtrans',
                    'payment_type'   => $paymentType ?: 'midtrans',
                    'status'         => 'success',
                    'notes'          => $notes ?: 'Top up berhasil diselesaikan',
                    'created_at'     => date('Y-m-d H:i:s'),
                    'updated_at'     => date('Y-m-d H:i:s')
                ]);
            }
            return true;
        }

        if ($log['status'] === 'success') {
            return true; // Already success
        }

        Database::update('topup_logs', [
            'status'       => 'success',
            'payment_type' => $paymentType ?: $log['payment_type'],
            'notes'        => $notes ?: 'Pembayaran berhasil dikonfirmasi',
            'updated_at'   => date('Y-m-d H:i:s')
        ], 'id = ?', [$log['id']]);

        return true;
    }

    public function markFailed(string $topupCode, ?string $notes = 'Pembayaran dibatalkan atau gagal'): bool
    {
        $log = Database::fetchOne("SELECT * FROM `topup_logs` WHERE `topup_code` = ? LIMIT 1", [$topupCode]);
        if (!$log) {
            return false;
        }

        if ($log['status'] === 'success') {
            return false; // Cannot fail an already completed top up
        }

        Database::update('topup_logs', [
            'status'     => 'failed',
            'notes'      => $notes,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$log['id']]);

        return true;
    }

    public function updateStatusByCode(string $topupCode, string $status, ?string $notes = null): bool
    {
        $log = Database::fetchOne("SELECT * FROM `topup_logs` WHERE `topup_code` = ? LIMIT 1", [$topupCode]);
        if (!$log) {
            return false;
        }

        if ($log['status'] === 'success' && $status !== 'success') {
            return false; // Cannot downgrade a completed top up
        }

        Database::update('topup_logs', [
            'status'     => $status,
            'notes'      => $notes ?: $log['notes'],
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$log['id']]);

        return true;
    }

    public function getByUser(int $userId, ?string $status = null, int $limit = 50): array
    {
        if ($status) {
            return Database::query(
                "SELECT * FROM `topup_logs` WHERE `user_id` = ? AND `status` = ? ORDER BY `id` DESC LIMIT {$limit}",
                [$userId, $status]
            );
        }

        return Database::query(
            "SELECT * FROM `topup_logs` WHERE `user_id` = ? ORDER BY `id` DESC LIMIT {$limit}",
            [$userId]
        );
    }

    public function getStats(int $userId): array
    {
        $totalSuccess = Database::fetchOne(
            "SELECT COALESCE(SUM(amount), 0) as total_amount, COUNT(*) as count FROM `topup_logs` WHERE `user_id` = ? AND `status` = 'success'",
            [$userId]
        );
        $totalFailed = Database::fetchOne(
            "SELECT COUNT(*) as count FROM `topup_logs` WHERE `user_id` = ? AND `status` = 'failed'",
            [$userId]
        );
        $totalPending = Database::fetchOne(
            "SELECT COUNT(*) as count FROM `topup_logs` WHERE `user_id` = ? AND `status` = 'pending'",
            [$userId]
        );

        return [
            'total_success_amount' => (float)($totalSuccess['total_amount'] ?? 0),
            'success_count'        => (int)($totalSuccess['count'] ?? 0),
            'failed_count'         => (int)($totalFailed['count'] ?? 0),
            'pending_count'        => (int)($totalPending['count'] ?? 0),
        ];
    }
}

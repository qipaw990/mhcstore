<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Database;
use Exception;

class PaymentInvoice extends Model
{
    protected string $table = 'payment_invoices';
    protected array $fillable = [
        'invoice_code', 'user_id', 'order_id', 'type',
        'bank_name', 'account_number', 'account_name',
        'base_amount', 'unique_code', 'total_amount',
        'qris_payload', 'status', 'paid_at', 'expires_at',
        'notes', 'raw_webhook'
    ];

    /**
     * Default Admin Destination Bank Accounts
     */
    public static function getAvailableBanks(): array
    {
        return [
            [
                'id'             => 'QRIS',
                'name'           => 'QRIS Otomatis (Semua E-Wallet & M-Banking)',
                'code'           => 'QRIS',
                'account_number' => 'NMID: ID1024328492048',
                'account_name'   => 'CICALENGKAGO PAYMENT',
                'type'           => 'qris',
                'icon'           => 'qris.png',
                'description'    => 'Scan pakai GoPay, OVO, DANA, BCA, Mandiri, BRI, ShopeePay'
            ],
            [
                'id'             => 'BCA',
                'name'           => 'Bank Central Asia (BCA)',
                'code'           => 'BCA',
                'account_number' => '1380721839',
                'account_name'   => 'CICALENGKA MEDIA SOLUSI',
                'type'           => 'bank',
                'icon'           => 'bca.png',
                'description'    => 'Transfer ke Rekening BCA (Otomatis dicek)'
            ],
            [
                'id'             => 'BRI',
                'name'           => 'Bank Rakyat Indonesia (BRI)',
                'code'           => 'BRI',
                'account_number' => '015301002948531',
                'account_name'   => 'CICALENGKA MEDIA SOLUSI',
                'type'           => 'bank',
                'icon'           => 'bri.png',
                'description'    => 'Transfer ke Rekening BRI (Otomatis dicek)'
            ],
            [
                'id'             => 'MANDIRI',
                'name'           => 'Bank Mandiri',
                'code'           => 'MANDIRI',
                'account_number' => '1300029384910',
                'account_name'   => 'CICALENGKA MEDIA SOLUSI',
                'type'           => 'bank',
                'icon'           => 'mandiri.png',
                'description'    => 'Transfer ke Rekening Mandiri (Otomatis dicek)'
            ],
            [
                'id'             => 'DANA',
                'name'           => 'DANA / E-Wallet',
                'code'           => 'DANA',
                'account_number' => '0895333190888',
                'account_name'   => 'CICALENGKAGO OFFICIAL',
                'type'           => 'ewallet',
                'icon'           => 'dana.png',
                'description'    => 'Kirim ke nomor DANA CicalengkaGO'
            ],
            [
                'id'             => 'GOPAY',
                'name'           => 'GoPay / GoJek',
                'code'           => 'GOPAY',
                'account_number' => '0895333190888',
                'account_name'   => 'CICALENGKAGO OFFICIAL',
                'type'           => 'ewallet',
                'icon'           => 'gopay.png',
                'description'    => 'Transfer saldo GoPay / QRIS GoPay'
            ],
        ];
    }

    /**
     * Ensure table exists on first call
     */
    public static function ensureTable(): void
    {
        try {
            Database::execute("
                CREATE TABLE IF NOT EXISTS `payment_invoices` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `invoice_code` VARCHAR(64) UNIQUE NOT NULL,
                    `user_id` INT NOT NULL,
                    `order_id` INT NULL,
                    `type` VARCHAR(32) DEFAULT 'topup',
                    `bank_name` VARCHAR(32) NOT NULL,
                    `account_number` VARCHAR(64) NOT NULL,
                    `account_name` VARCHAR(128) NOT NULL,
                    `base_amount` DECIMAL(12,2) NOT NULL,
                    `unique_code` INT NOT NULL,
                    `total_amount` DECIMAL(12,2) NOT NULL,
                    `qris_payload` TEXT NULL,
                    `status` ENUM('pending', 'paid', 'expired', 'cancelled') DEFAULT 'pending',
                    `paid_at` DATETIME NULL,
                    `expires_at` DATETIME NULL,
                    `notes` TEXT NULL,
                    `raw_webhook` TEXT NULL,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX(`user_id`),
                    INDEX(`status`),
                    INDEX(`total_amount`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        } catch (\Throwable $e) {}
    }

    /**
     * Create In-House Payment Invoice with 3-digit unique code
     */
    public function createInvoice(int $userId, string $type, string $bankCode, float $baseAmount, ?int $orderId = null): array
    {
        self::ensureTable();

        if ($baseAmount < 1000) {
            throw new Exception("Nominal minimal pembayaran adalah Rp 1.000");
        }

        // Find bank config
        $banks = self::getAvailableBanks();
        $selectedBank = null;
        foreach ($banks as $b) {
            if (strtoupper($b['code']) === strtoupper($bankCode) || strtoupper($b['id']) === strtoupper($bankCode)) {
                $selectedBank = $b;
                break;
            }
        }
        if (!$selectedBank) {
            $selectedBank = $banks[0]; // Default to QRIS
        }

        // Generate non-colliding unique 3-digit code between 101 and 989
        $uniqueCode = rand(101, 899);
        for ($i = 0; $i < 20; $i++) {
            $testTotal = $baseAmount + $uniqueCode;
            $exists = Database::fetchOne(
                "SELECT id FROM `payment_invoices` WHERE `status` = 'pending' AND `total_amount` = ? AND `expires_at` > NOW() LIMIT 1",
                [$testTotal]
            );
            if (!$exists) {
                break;
            }
            $uniqueCode = rand(101, 899);
        }

        $totalAmount = $baseAmount + $uniqueCode;
        $invoiceCode = 'INV-' . strtoupper(substr($selectedBank['code'], 0, 3)) . '-' . date('Ymd') . '-' . rand(1000, 9999);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+2 hours'));

        // Generate Dynamic QRIS payload if QRIS is selected
        $qrisPayload = null;
        if ($selectedBank['type'] === 'qris' || $selectedBank['code'] === 'QRIS') {
            $qrisPayload = self::generateDynamicQrisPayload($totalAmount);
        }

        $insertData = [
            'invoice_code'   => $invoiceCode,
            'user_id'        => $userId,
            'order_id'       => $orderId,
            'type'           => $type, // 'topup' or 'order'
            'bank_name'      => $selectedBank['name'],
            'account_number' => $selectedBank['account_number'],
            'account_name'   => $selectedBank['account_name'],
            'base_amount'    => $baseAmount,
            'unique_code'    => $uniqueCode,
            'total_amount'   => $totalAmount,
            'qris_payload'   => $qrisPayload,
            'status'         => 'pending',
            'expires_at'     => $expiresAt,
            'notes'          => "Menunggu transfer tepat Rp " . number_format($totalAmount, 0, ',', '.') . " ke " . $selectedBank['name'],
            'created_at'     => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s')
        ];

        Database::insert('payment_invoices', $insertData);
        $invoice = Database::fetchOne("SELECT * FROM `payment_invoices` WHERE `invoice_code` = ? LIMIT 1", [$invoiceCode]);

        // If topup, also log in topup_logs
        if ($type === 'topup') {
            try {
                (new TopupLog())->recordPending(
                    $userId,
                    $invoiceCode,
                    $baseAmount,
                    $invoiceCode,
                    'inhouse_transfer',
                    "Transfer Rp " . number_format($totalAmount, 0, ',', '.') . " ke {$selectedBank['name']} (Kode Unik: {$uniqueCode})"
                );
            } catch (\Throwable $e) {}
        }

        return $invoice ?: $insertData;
    }

    /**
     * Approve invoice automatically and credit wallet or activate order
     */
    public function approveInvoice(string $invoiceCode, ?string $sender = null, ?string $rawWebhook = null): bool
    {
        self::ensureTable();

        $invoice = Database::fetchOne("SELECT * FROM `payment_invoices` WHERE `invoice_code` = ? LIMIT 1", [$invoiceCode]);
        if (!$invoice) {
            return false;
        }

        if ($invoice['status'] === 'paid') {
            return true; // Already processed
        }

        $userId = (int)$invoice['user_id'];
        $baseAmount = (float)$invoice['base_amount'];
        $type = $invoice['type'];
        $bankName = $invoice['bank_name'];
        $uniqueCode = (int)$invoice['unique_code'];

        // Mark invoice as paid
        Database::update('payment_invoices', [
            'status'      => 'paid',
            'paid_at'     => date('Y-m-d H:i:s'),
            'raw_webhook' => $rawWebhook ?: ($sender ? "Dana masuk dari: {$sender}" : "Auto-approved by webhook"),
            'notes'       => "Pembayaran lunas terverifikasi otomatis pada " . date('d M Y H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s')
        ], 'id = ?', [$invoice['id']]);

        // Handle based on type
        if ($type === 'topup') {
            $walletModel = new Wallet();
            $walletModel->credit(
                $userId,
                $baseAmount,
                'topup',
                "Top Up CicalengkaPay via {$bankName} (Kode Unik: {$uniqueCode})",
                $invoiceCode
            );

            $topupLogModel = new TopupLog();
            $topupLogModel->markSuccess($invoiceCode, 'inhouse_transfer', "Top up otomatis diverifikasi");

            // Send notification
            try {
                (new Notification())->createForUser(
                    $userId,
                    'Top Up CicalengkaPay Berhasil! 💰',
                    'Saldo Rp ' . number_format($baseAmount, 0, ',', '.') . ' telah masuk ke akun Anda. Selamat bertransaksi!',
                    'wallet'
                );
            } catch (\Throwable $e) {}
        } elseif ($type === 'order' && !empty($invoice['order_id'])) {
            $orderId = (int)$invoice['order_id'];
            Database::update('orders', [
                'payment_status' => 'paid',
                'status'         => 'confirmed',
                'updated_at'     => date('Y-m-d H:i:s')
            ], 'id = ?', [$orderId]);

            try {
                (new Notification())->createForUser(
                    $userId,
                    'Pembayaran Pesanan Berhasil! 🍽️',
                    'Pembayaran pesanan #' . $orderId . ' telah diterima. Pesanan Anda sedang disiapkan mitra resto.',
                    'order'
                );
            } catch (\Throwable $e) {}
        }

        return true;
    }

    /**
     * Process incoming webhook / mutasi scraper
     * Matches exact incoming amount with pending invoice
     */
    public function processWebhookData(float $amount, ?string $bank = null, ?string $sender = null, ?string $rawText = null): array
    {
        self::ensureTable();

        if ($amount <= 0 && !empty($rawText)) {
            // Attempt to parse amount from SMS / WhatsApp / Notification text
            // e.g. "Transfer masuk Rp 50.284 dari REK 123 AHMAD" or "CR 50284.00"
            if (preg_match('/(?:Rp\.?|CR|IDR)?\s*([0-9]{1,3}(?:\.[0-9]{3})+|[0-9]{4,8})/i', $rawText, $match)) {
                $clean = str_replace('.', '', $match[1]);
                $amount = (float)$clean;
            }
        }

        if ($amount <= 0) {
            return [
                'success' => false,
                'message' => 'Nominal transfer tidak valid atau tidak terbaca'
            ];
        }

        // Find matching pending invoice within valid time window
        $invoice = Database::fetchOne("
            SELECT * FROM `payment_invoices` 
            WHERE `status` = 'pending' 
              AND `total_amount` = ? 
              AND `expires_at` > NOW()
            ORDER BY `id` DESC 
            LIMIT 1
        ", [$amount]);

        if (!$invoice) {
            return [
                'success' => false,
                'message' => "Tidak ada tagihan pending yang cocok untuk nominal Rp " . number_format($amount, 0, ',', '.')
            ];
        }

        $this->approveInvoice($invoice['invoice_code'], $sender, $rawText ?: "Webhook nominal: {$amount}");

        return [
            'success'      => true,
            'message'      => "Pembayaran invoice {$invoice['invoice_code']} berhasil dikonfirmasi otomatis!",
            'invoice_code' => $invoice['invoice_code'],
            'amount'       => $invoice['total_amount'],
            'user_id'      => $invoice['user_id']
        ];
    }

    /**
     * Dynamic QRIS EMVCo Generator
     */
    public static function generateDynamicQrisPayload(float $amount): string
    {
        // Base static QRIS template for CicalengkaGO
        $baseQris = "00020101021226670014ID.GO.CICAGO.WWW01189360091800000000000215ID10243284920480303UMI51440014ID.CO.QRIS.WWW0215ID10243284920480303UMI5204581253033605802ID5914CICALENGKAGO6010KAB BANDUNG610540395";

        $amountStr = number_format($amount, 0, '', '');
        $amountTag = '54' . sprintf('%02d', strlen($amountStr)) . $amountStr;

        // Insert Tag 54 before Country Code (Tag 58) or Postal Code
        $modifiedQris = $baseQris . $amountTag . "5802ID5914CICALENGKAGO6010KAB BANDUNG6105403956304";

        // Calculate CRC16-CCITT
        $crc = self::crc16($modifiedQris);
        return $modifiedQris . strtoupper($crc);
    }

    private static function crc16(string $data): string
    {
        $crc = 0xFFFF;
        for ($i = 0; $i < strlen($data); $i++) {
            $crc ^= (ord($data[$i]) << 8);
            for ($j = 0; $j < 8; $j++) {
                if ($crc & 0x8000) {
                    $crc = (($crc << 1) ^ 0x1021) & 0xFFFF;
                } else {
                    $crc = ($crc << 1) & 0xFFFF;
                }
            }
        }
        return sprintf('%04X', $crc);
    }
}

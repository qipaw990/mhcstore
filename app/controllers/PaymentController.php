<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Services\MidtransService;
use App\Models\Wallet;
use App\Models\TopupLog;
use App\Models\Notification;
use Exception;

class PaymentController extends Controller
{
    private MidtransService $midtransService;

    public function __construct()
    {
        $this->midtransService = new MidtransService();
    }

    /**
     * Generate Snap Token for CicalengkaPay Wallet Top-Up
     */
    public function topupSnap(): void
    {
        $userId = auth_id();
        if (!$userId) {
            $this->errorResponse('Silakan login terlebih dahulu.', null, 401);
            return;
        }

        $user = auth_user();
        $data = $this->getPost();
        $amount = (float)($data['amount'] ?? 0);

        if ($amount < 10000) {
            $this->errorResponse('Nominal top up minimal Rp 10.000.');
            return;
        }

        $orderId = 'TOPUP-' . $userId . '-' . time() . '-' . rand(100, 999);

        try {
            $appConfig = require APP_PATH . '/config/app.php';
            $publicUrl = rtrim($appConfig['public_url'] ?? '', '/');

            $params = [
                'transaction_details' => [
                    'order_id'     => $orderId,
                    'gross_amount' => (int)$amount
                ],
                'customer_details' => [
                    'first_name' => $user['name'] ?? 'Pengguna CicalengkaGO',
                    'email'      => $user['email'] ?? 'customer@cicalengkago.id',
                    'phone'      => $user['phone'] ?? '081234567890'
                ],
                'item_details' => [
                    [
                        'id'       => 'TOPUP_CICALENGKAPAY',
                        'price'    => (int)$amount,
                        'quantity' => 1,
                        'name'     => 'Top Up Saldo CicalengkaPay'
                    ]
                ],
                'callbacks' => [
                    'finish'   => $publicUrl . '/wallet',
                    'error'    => $publicUrl . '/wallet',
                    'unfinish' => $publicUrl . '/wallet'
                ]
            ];

            $snapResult = $this->midtransService->createSnapToken($params);

            // Record pending log in topup_logs
            (new \App\Models\TopupLog())->recordPending(
                $userId,
                $orderId,
                $amount,
                $snapResult['token'],
                'midtrans_snap',
                'Menunggu pembayaran via Midtrans Snap'
            );

            $this->successResponse('Snap token berhasil dibuat', [
                'snap_token'   => $snapResult['token'],
                'order_id'     => $orderId,
                'client_key'   => $snapResult['client_key'],
                'redirect_url' => $snapResult['redirect_url']
            ]);
        } catch (\Throwable $e) {
            $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Update status log for top up (e.g. failed/canceled when user closes window or fails)
     */
    public function updateTopupStatus(): void
    {
        $userId = auth_id();
        if (!$userId) {
            $this->errorResponse('Silakan login terlebih dahulu.', null, 401);
            return;
        }

        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true) ?: $this->getPost();

        $orderId = trim($data['order_id'] ?? '');
        $status = trim($data['status'] ?? 'failed');
        $notes = trim($data['notes'] ?? 'Dibatalkan oleh pengguna');

        if (empty($orderId)) {
            $this->errorResponse('Order ID tidak valid.');
            return;
        }

        $topupLogModel = new \App\Models\TopupLog();
        if ($status === 'failed' || $status === 'canceled') {
            $topupLogModel->markFailed($orderId, $notes);
        } elseif ($status === 'success') {
            $topupLogModel->markSuccess($orderId, $data['payment_type'] ?? 'midtrans', $notes);
        }

        $this->successResponse('Status log top up berhasil diperbarui');
    }

    public function verifyClientCallback(): void
    {
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true) ?: $this->getPost();

        if (empty($data['order_id'])) {
            $this->errorResponse('Data pembayaran tidak lengkap.');
            return;
        }

        $orderId = $data['order_id'];

        // Handle Top Up Callback
        if (str_starts_with($orderId, 'TOPUP-')) {
            try {
                if ($this->midtransService->isSandbox()) {
                    $data['transaction_status'] = 'settlement';
                }
                $result = $this->midtransService->processNotification($data);
                (new \App\Models\TopupLog())->markSuccess($orderId, $data['payment_type'] ?? 'midtrans', 'Pembayaran terkonfirmasi');
                $this->successResponse('Top Up berhasil diverifikasi', $result);
                return;
            } catch (\Throwable $e) {
                (new \App\Models\TopupLog())->markFailed($orderId, $e->getMessage());
                $this->errorResponse($e->getMessage());
                return;
            }
        }

        // Extract base order code
        $cleanCode = $orderId;
        if (preg_match('/^((?:CCG|PCL)-[A-Za-z0-9]+)/i', $orderId, $matches)) {
            $cleanCode = $matches[1];
        }

        try {
            // Check if already paid in DB
            $dbOrder = \App\Core\Database::fetchOne("SELECT * FROM `orders` WHERE `order_code` = ? LIMIT 1", [$cleanCode]);
            if ($dbOrder && $dbOrder['payment_status'] === 'paid') {
                $this->successResponse('Pesanan sudah lunas', [
                    'status'         => 'settled',
                    'payment_status' => 'paid',
                    'order_code'     => $cleanCode
                ]);
                return;
            }

            if (!empty($data['transaction_status'])) {
                // If in sandbox and client completed payment or requested settlement
                $txStatus = $data['transaction_status'];
                if ($this->midtransService->isSandbox() && in_array($txStatus, ['settlement', 'capture', 'success', 'accept', 'pending'])) {
                    if ($txStatus === 'pending' && !empty($data['force_sandbox_settle'])) {
                        $data['transaction_status'] = 'settlement';
                    }
                }

                $result = $this->midtransService->processNotification($data);
                $this->successResponse('Status pembayaran berhasil diproses', $result);
                return;
            }

            // Fallback: check live Midtrans status API
            $liveStatus = $this->midtransService->getTransactionStatus($orderId);
            if (!empty($liveStatus['success']) && !empty($liveStatus['data'])) {
                $result = $this->midtransService->processNotification($liveStatus['data']);
                $this->successResponse('Status pembayaran berhasil diproses', $result);
                return;
            }

            // In Sandbox mode fallback: if user calls verify, mark as settled
            if ($this->midtransService->isSandbox()) {
                $data['transaction_status'] = 'settlement';
                $result = $this->midtransService->processNotification($data);
                $this->successResponse('Pembayaran diselesaikan (Mode Sandbox)', $result);
                return;
            }

            $this->errorResponse($liveStatus['message'] ?? 'Belum ada data pembayaran terkonfirmasi.');
        } catch (\Throwable $e) {
            $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Instantly mark payment as success in Sandbox Mode (for Orders or Wallet Top-Up)
     */
    public function simulateSandboxSuccess(): void
    {
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true) ?: $this->getPost();

        $orderId = trim($data['order_id'] ?? '');
        $paymentType = sanitize($data['payment_type'] ?? 'midtrans_sandbox');

        if (empty($orderId)) {
            $this->errorResponse('Order ID atau kode transaksi tidak valid.');
            return;
        }

        try {
            // Handle Top-Up (TOPUP-{userId}-{timestamp}-{rand})
            if (str_starts_with($orderId, 'TOPUP-')) {
                $parts = explode('-', $orderId);
                $userId = (int)($parts[1] ?? auth_id());
                $amount = (float)($data['amount'] ?? 0);

                if ($amount <= 0 && !empty($data['gross_amount'])) {
                    $amount = (float)$data['gross_amount'];
                }

                $topupLog = Database::fetchOne("SELECT amount FROM `topup_logs` WHERE `topup_code` = ? LIMIT 1", [$orderId]);
                if ($topupLog && (float)$topupLog['amount'] > 0) {
                    $amount = (float)$topupLog['amount'];
                } elseif ($amount < 10000) {
                    $amount = 50000;
                }

                $walletModel = new Wallet();
                $walletModel->credit(
                    $userId,
                    $amount,
                    'topup',
                    "Top Up CicalengkaPay via Midtrans Sandbox ({$paymentType})",
                    $orderId
                );

                // Update or record TopupLog as success
                $topupLogModel = new \App\Models\TopupLog();
                $topupLogModel->recordPending($userId, $orderId, $amount, null, $paymentType);
                $topupLogModel->markSuccess($orderId, $paymentType, 'Top Up Midtrans Sandbox Berhasil');

                (new \App\Models\Notification())->createNotification(
                    $userId,
                    'Top Up Midtrans Berhasil! 🎉',
                    "Saldo CicalengkaPay sebesar " . format_rupiah($amount) . " berhasil ditambahkan (Mode Sandbox).",
                    'wallet'
                );

                $this->successResponse('Top Up berhasil diselesaikan (Sandbox Mode)', [
                    'status'         => 'settled',
                    'order_id'       => $orderId,
                    'amount'         => $amount,
                    'payment_status' => 'paid'
                ]);
                return;
            }

            // Handle Order Checkout (CCG-xxx or PCL-xxx)
            $cleanCode = $orderId;
            if (preg_match('/^((?:CCG|PCL)-[A-Za-z0-9]+)/i', $orderId, $matches)) {
                $cleanCode = $matches[1];
            }

            $order = \App\Core\Database::fetchOne("SELECT * FROM `orders` WHERE `order_code` = ? LIMIT 1", [$cleanCode]);
            if (!$order) {
                $this->errorResponse("Pesanan #{$cleanCode} tidak ditemukan.");
                return;
            }

            // Update order status to paid and confirmed
            \App\Core\Database::update('orders', [
                'payment_status' => 'paid',
                'payment_method' => 'midtrans',
                'order_status'   => ($order['order_status'] === 'pending' || $order['order_status'] === 'unpaid') ? 'confirmed' : $order['order_status'],
                'confirmed_at'   => date('Y-m-d H:i:s')
            ], 'id = ?', [$order['id']]);

            // Notify customer
            (new \App\Models\Notification())->createNotification(
                (int)$order['customer_id'],
                'Pembayaran Berhasil! 💳',
                "Pembayaran pesanan #{$order['order_code']} via Midtrans Sandbox berhasil dikonfirmasi.",
                'order'
            );

            $this->successResponse('Pembayaran pesanan berhasil diselesaikan (Sandbox Mode)', [
                'status'         => 'settled',
                'order_code'     => $order['order_code'],
                'order_id'       => $orderId,
                'payment_status' => 'paid',
                'order_status'   => ($order['order_status'] === 'pending') ? 'confirmed' : $order['order_status']
            ]);
        } catch (\Throwable $e) {
            $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Webhook Notification Handler from Midtrans Server
     */
    public function notification(): void
    {
        $rawInput = file_get_contents('php://input');
        $payload = json_decode($rawInput, true);

        if (!$payload) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid payload']);
            return;
        }

        try {
            $result = $this->midtransService->processNotification($payload);
            http_response_code(200);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'result' => $result]);
        } catch (\Throwable $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    // =========================================================================
    // IN-HOUSE AUTOMATED PAYMENT SYSTEM (TRANSFER BANK + QRIS + KODE UNIK + WEBHOOK)
    // =========================================================================

    /**
     * Get list of supported destination bank accounts & QRIS
     */
    public function getBanks(): void
    {
        $banks = \App\Models\PaymentInvoice::getAvailableBanks();
        $this->successResponse('Daftar metode pembayaran berhasil diambil', $banks);
    }

    /**
     * Create In-House Payment Invoice with 3-digit unique code
     */
    public function createInvoice(): void
    {
        $userId = auth_id();
        if (!$userId) {
            $this->errorResponse('Silakan login terlebih dahulu.', null, 401);
            return;
        }

        $data = $this->getPost();
        if (empty($data)) {
            $raw = file_get_contents('php://input');
            $data = json_decode($raw, true) ?: [];
        }

        $amount    = (float)($data['amount'] ?? 0);
        $bankCode  = trim((string)($data['bank'] ?? $data['bank_code'] ?? 'QRIS'));
        $type      = trim((string)($data['type'] ?? 'topup'));
        $orderId   = !empty($data['order_id']) ? (int)$data['order_id'] : null;

        if ($amount < 1000) {
            $this->errorResponse('Nominal pembayaran minimal Rp 1.000');
            return;
        }

        try {
            $model = new \App\Models\PaymentInvoice();
            $invoice = $model->createInvoice($userId, $type, $bankCode, $amount, $orderId);

            // Generate Google Charts / QR Server URL for QRIS QR display
            $qrisQrUrl = null;
            if (!empty($invoice['qris_payload'])) {
                $qrisQrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($invoice['qris_payload']);
            }

            $invoice['qris_qr_url'] = $qrisQrUrl;

            $this->successResponse('Tiket pembayaran berhasil dibuat', $invoice);
        } catch (\Throwable $e) {
            $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Check real-time payment status of an invoice
     */
    public function checkInvoice(): void
    {
        $code = trim((string)($_GET['code'] ?? $_GET['invoice_code'] ?? ''));
        if (empty($code)) {
            $this->errorResponse('Invoice code is required');
            return;
        }

        $invoice = \App\Core\Database::fetchOne("SELECT * FROM `payment_invoices` WHERE `invoice_code` = ? LIMIT 1", [$code]);
        if (!$invoice) {
            $this->errorResponse('Invoice tidak ditemukan', null, 404);
            return;
        }

        $isExpired = ($invoice['status'] === 'pending' && strtotime($invoice['expires_at']) < time());
        if ($isExpired) {
            \App\Core\Database::update('payment_invoices', ['status' => 'expired'], 'id = ?', [$invoice['id']]);
            $invoice['status'] = 'expired';
        }

        $this->successResponse('Status invoice berhasil dicek', [
            'invoice_code' => $invoice['invoice_code'],
            'status'       => $invoice['status'],
            'total_amount' => (float)$invoice['total_amount'],
            'base_amount'  => (float)$invoice['base_amount'],
            'unique_code'  => (int)$invoice['unique_code'],
            'paid_at'      => $invoice['paid_at'],
            'expires_at'   => $invoice['expires_at']
        ]);
    }

    /**
     * In-House Auto-Approve Webhook API
     * Can receive data from:
     * - Bank Mutasi Scraper
     * - Android Notification Listener / MacroDroid (SMS Banking, BCA Mobile, Livin, GoPay, DANA)
     */
    public function autoWebhook(): void
    {
        $rawInput = file_get_contents('php://input');
        $payload = json_decode($rawInput, true) ?: $_POST;

        $amount  = (float)($payload['amount'] ?? 0);
        $bank    = $payload['bank'] ?? $payload['bank_name'] ?? null;
        $sender  = $payload['sender'] ?? $payload['from'] ?? null;
        $rawText = $payload['text'] ?? $payload['message'] ?? $payload['notification'] ?? null;

        $model = new \App\Models\PaymentInvoice();
        $result = $model->processWebhookData($amount, $bank, $sender, $rawText);

        header('Content-Type: application/json');
        if ($result['success']) {
            http_response_code(200);
            echo json_encode(['status' => 'success', 'data' => $result]);
        } else {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => $result['message']]);
        }
    }

    /**
     * Testing Simulator: Auto-approve an invoice without actual bank transfer (Development/Admin testing)
     */
    public function simulatePay(): void
    {
        $data = $this->getPost();
        if (empty($data)) {
            $raw = file_get_contents('php://input');
            $data = json_decode($raw, true) ?: [];
        }

        $code = trim((string)($data['invoice_code'] ?? $data['code'] ?? ''));
        if (empty($code)) {
            $this->errorResponse('Invoice code diperlukan');
            return;
        }

        $model = new \App\Models\PaymentInvoice();
        $success = $model->approveInvoice($code, 'Simulator Admin CicalengkaGO');

        if ($success) {
            $this->successResponse('Pembayaran invoice berhasil disimulasikan & lunas!', [
                'invoice_code' => $code,
                'status'       => 'paid'
            ]);
        } else {
            $this->errorResponse('Invoice tidak ditemukan atau sudah dibayar.');
        }
    }

    /**
     * Kirim Uang / Transfer Saldo CicalengkaPay ke Rekening Bank, E-Wallet (Fee Rp 1.500), atau Sesama CicalengkaPay
     */
    public function transfer(): void
    {
        $senderId = auth_id();
        if (!$senderId) {
            $this->errorResponse('Silakan login terlebih dahulu.', null, 401);
            return;
        }

        $data = $this->getPost();
        $transferType = trim((string)($data['transfer_type'] ?? $data['type'] ?? 'bank'));
        $amount = (float)($data['amount'] ?? 0);
        $notes = trim((string)($data['notes'] ?? $data['note'] ?? ''));

        if ($amount < 1000) {
            $this->errorResponse('Nominal kirim uang minimal Rp 1.000.');
            return;
        }

        $walletModel = new \App\Models\Wallet();
        $sender = auth_user();
        $senderWallet = $walletModel->getOrCreate($senderId, 'customer');
        $currentBalance = (float)($senderWallet['balance'] ?? 0);

        // Auto-heal table columns for customer transfers
        try {
            Database::execute("ALTER TABLE `withdraw_requests` MODIFY COLUMN `user_type` VARCHAR(32) NOT NULL DEFAULT 'customer'");
        } catch (\Throwable $e) {}
        try {
            Database::execute("ALTER TABLE `wallet_transactions` MODIFY COLUMN `category` VARCHAR(50) NOT NULL DEFAULT 'transfer'");
        } catch (\Throwable $e) {}

        // =====================================================================
        // 1. TRANSFER KE REKENING BANK (Fee Rp 1.500)
        // =====================================================================
        if ($transferType === 'bank') {
            $bankName = trim((string)($data['bank_name'] ?? 'BCA'));
            $accountNumber = trim((string)($data['account_number'] ?? ''));
            $accountHolder = trim((string)($data['account_holder'] ?? ''));
            $fee = 1500.0;
            $totalDeduct = $amount + $fee;

            if (empty($bankName) || empty($accountNumber) || empty($accountHolder)) {
                $this->errorResponse('Nama Bank, Nomor Rekening, dan Nama Pemilik Rekening wajib diisi lengkap.');
                return;
            }

            if ($amount < 10000) {
                $this->errorResponse('Nominal transfer ke rekening bank minimal Rp 10.000.');
                return;
            }

            if ($currentBalance < $totalDeduct) {
                $this->errorResponse("Saldo tidak mencukupi. Dibutuhkan Rp " . number_format($totalDeduct, 0, ',', '.') . " (Transfer Rp " . number_format($amount, 0, ',', '.') . " + Biaya Admin Rp 1.500), sedangkan saldo Anda Rp " . number_format($currentBalance, 0, ',', '.') . ".");
                return;
            }

            try {
                $withdrawCode = 'TRF-BANK-' . strtoupper(substr(uniqid(), -6)) . rand(10, 99);
                Database::transaction(function () use ($walletModel, $senderId, $amount, $fee, $bankName, $accountNumber, $accountHolder, $notes, $withdrawCode) {
                    // 1. Debit pokok transfer
                    $walletModel->debit(
                        $senderId,
                        $amount,
                        'withdraw',
                        "Kirim uang ke Bank {$bankName} ({$accountNumber} a.n {$accountHolder})" . ($notes ? " - {$notes}" : ""),
                        $withdrawCode
                    );

                    // 2. Debit biaya admin Rp 1.500
                    $walletModel->debit(
                        $senderId,
                        $fee,
                        'fee',
                        "Biaya admin transfer ke Bank {$bankName} (Rp 1.500)",
                        $withdrawCode . '-FEE'
                    );

                    // 3. Catat ke tabel withdraw_requests / pengajuan transfer bank
                    Database::insert('withdraw_requests', [
                        'withdraw_code'  => $withdrawCode,
                        'user_id'        => $senderId,
                        'user_type'      => 'customer',
                        'amount'         => $amount,
                        'bank_name'      => $bankName,
                        'account_number' => $accountNumber,
                        'account_holder' => $accountHolder,
                        'status'         => 'pending',
                        'admin_notes'    => 'Kirim Uang ke Bank via CicalengkaPay (Biaya Admin Rp 1.500)' . ($notes ? " | Pesan: {$notes}" : '')
                    ]);
                });

                $this->successResponse("Permintaan transfer ke {$bankName} sebesar Rp " . number_format($amount, 0, ',', '.') . " berhasil diajukan! (Biaya admin Rp 1.500).", [
                    'transfer_type'   => 'bank',
                    'bank_name'       => $bankName,
                    'account_number'  => $accountNumber,
                    'account_holder'  => $accountHolder,
                    'amount'          => $amount,
                    'fee'             => $fee,
                    'total_deducted'  => $totalDeduct,
                    'reference_id'    => $withdrawCode
                ]);
                return;
            } catch (\Throwable $e) {
                $this->errorResponse('Gagal memproses transfer ke bank: ' . $e->getMessage());
                return;
            }
        }

        // =====================================================================
        // 2. TRANSFER KE E-WALLET (Fee Rp 1.500)
        // =====================================================================
        if ($transferType === 'ewallet') {
            $ewalletName = trim((string)($data['ewallet_name'] ?? 'DANA'));
            $accountNumber = trim((string)($data['account_number'] ?? $data['phone'] ?? ''));
            $accountHolder = trim((string)($data['account_holder'] ?? ''));
            $fee = 1500.0;
            $totalDeduct = $amount + $fee;

            if (empty($ewalletName) || empty($accountNumber) || empty($accountHolder)) {
                $this->errorResponse('Nama E-Wallet, Nomor HP Akun, dan Nama Akun Penerima wajib diisi.');
                return;
            }

            if ($amount < 10000) {
                $this->errorResponse('Nominal transfer ke E-Wallet minimal Rp 10.000.');
                return;
            }

            if ($currentBalance < $totalDeduct) {
                $this->errorResponse("Saldo tidak mencukupi. Dibutuhkan Rp " . number_format($totalDeduct, 0, ',', '.') . " (Transfer Rp " . number_format($amount, 0, ',', '.') . " + Biaya Admin Rp 1.500), sedangkan saldo Anda Rp " . number_format($currentBalance, 0, ',', '.') . ".");
                return;
            }

            try {
                $withdrawCode = 'TRF-EWAL-' . strtoupper(substr(uniqid(), -6)) . rand(10, 99);
                Database::transaction(function () use ($walletModel, $senderId, $amount, $fee, $ewalletName, $accountNumber, $accountHolder, $notes, $withdrawCode) {
                    // 1. Debit pokok transfer
                    $walletModel->debit(
                        $senderId,
                        $amount,
                        'withdraw',
                        "Kirim uang ke E-Wallet {$ewalletName} ({$accountNumber} a.n {$accountHolder})" . ($notes ? " - {$notes}" : ""),
                        $withdrawCode
                    );

                    // 2. Debit biaya admin Rp 1.500
                    $walletModel->debit(
                        $senderId,
                        $fee,
                        'fee',
                        "Biaya admin transfer ke E-Wallet {$ewalletName} (Rp 1.500)",
                        $withdrawCode . '-FEE'
                    );

                    // 3. Catat ke tabel withdraw_requests / pengajuan transfer e-wallet
                    Database::insert('withdraw_requests', [
                        'withdraw_code'  => $withdrawCode,
                        'user_id'        => $senderId,
                        'user_type'      => 'customer',
                        'amount'         => $amount,
                        'bank_name'      => 'E-Wallet ' . $ewalletName,
                        'account_number' => $accountNumber,
                        'account_holder' => $accountHolder,
                        'status'         => 'pending',
                        'admin_notes'    => 'Kirim Uang ke E-Wallet via CicalengkaPay (Biaya Admin Rp 1.500)' . ($notes ? " | Pesan: {$notes}" : '')
                    ]);
                });

                $this->successResponse("Permintaan transfer ke E-Wallet {$ewalletName} sebesar Rp " . number_format($amount, 0, ',', '.') . " berhasil diajukan! (Biaya admin Rp 1.500).", [
                    'transfer_type'   => 'ewallet',
                    'ewallet_name'    => $ewalletName,
                    'account_number'  => $accountNumber,
                    'account_holder'  => $accountHolder,
                    'amount'          => $amount,
                    'fee'             => $fee,
                    'total_deducted'  => $totalDeduct,
                    'reference_id'    => $withdrawCode
                ]);
                return;
            } catch (\Throwable $e) {
                $this->errorResponse('Gagal memproses transfer ke e-wallet: ' . $e->getMessage());
                return;
            }
        }

        // =====================================================================
        // 3. TRANSFER SESAMA CICALENGKAPAY (Bebas Biaya Admin)
        // =====================================================================
        $recipientPhone = trim((string)($data['recipient_phone'] ?? $data['phone'] ?? $data['recipient'] ?? ''));
        if (empty($recipientPhone)) {
            $this->errorResponse('Nomor WhatsApp / HP penerima wajib diisi.');
            return;
        }

        $cleanPhone = preg_replace('/[^0-9]/', '', $recipientPhone);
        $cleanPhoneAlt = $cleanPhone;
        if (str_starts_with($cleanPhone, '62')) {
            $cleanPhoneAlt = '0' . substr($cleanPhone, 2);
        } elseif (str_starts_with($cleanPhone, '0')) {
            $cleanPhoneAlt = '62' . substr($cleanPhone, 1);
        }

        $recipient = Database::fetchOne(
            "SELECT id, name, phone, email FROM `users` WHERE (`phone` = ? OR `phone` = ? OR `email` = ?) AND `id` != ? LIMIT 1",
            [$cleanPhone, $cleanPhoneAlt, $recipientPhone, $senderId]
        );

        if (!$recipient) {
            $this->errorResponse('Pengguna penerima dengan nomor atau email tersebut tidak ditemukan di CicalengkaGO.');
            return;
        }

        if ($currentBalance < $amount) {
            $this->errorResponse('Saldo CicalengkaPay Anda tidak mencukupi untuk melakukan transfer ini.');
            return;
        }

        try {
            $refId = 'TRF-' . time() . '-' . rand(100, 999);
            Database::transaction(function () use ($walletModel, $senderId, $recipient, $sender, $amount, $notes, $refId) {
                $walletModel->debit(
                    $senderId,
                    $amount,
                    'transfer',
                    "Kirim saldo ke {$recipient['name']} ({$recipient['phone']})" . ($notes ? " - {$notes}" : ""),
                    $refId
                );

                $walletModel->credit(
                    (int)$recipient['id'],
                    $amount,
                    'transfer',
                    "Terima saldo dari {$sender['name']} ({$sender['phone']})" . ($notes ? " - {$notes}" : ""),
                    $refId
                );
            });

            $this->successResponse("Berhasil mengirim uang sebesar Rp " . number_format($amount, 0, ',', '.') . " ke {$recipient['name']}! (Bebas Biaya Admin)", [
                'recipient_name'  => $recipient['name'],
                'recipient_phone' => $recipient['phone'],
                'amount'          => $amount,
                'fee'             => 0,
                'total_deducted'  => $amount,
                'reference_id'    => $refId,
                'notes'           => $notes
            ]);
        } catch (\Throwable $e) {
            $this->errorResponse('Gagal mengirim saldo: ' . $e->getMessage());
        }
    }
}

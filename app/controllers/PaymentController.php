<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Wallet;
use App\Models\TopupLog;
use App\Models\Notification;
use App\Services\DokuService;
use Exception;

/**
 * PaymentController
 *
 * Menangani seluruh alur pembayaran produksi (PRODUCTION):
 *  - Top Up CicalengkaPay via DOKU Checkout
 *  - Checkout Pesanan via DOKU Checkout
 *  - Callback redirect dari DOKU (GET) setelah user selesai di halaman bayar
 *  - Webhook / Server Notification dari DOKU (POST) sebagai konfirmasi resmi
 *  - Transfer saldo antar pengguna / ke rekening bank / e-wallet
 *  - In-house payment invoice (QRIS / Bank Transfer)
 *
 * CATATAN KEAMANAN:
 *  - Endpoint simulateSandboxSuccess TIDAK ADA di production.
 *  - Status pembayaran hanya diubah melalui webhook DOKU yang terverifikasi HMAC.
 *  - Callback redirect DOKU hanya menampilkan halaman informasi, TIDAK mengubah DB.
 */
class PaymentController extends Controller
{
    private DokuService $dokuService;

    public function __construct()
    {
        $this->dokuService = new DokuService();
    }

    // =========================================================================
    // DOKU CHECKOUT - TOP UP CICALENGKAPAY
    // =========================================================================

    /**
     * Inisiasi sesi pembayaran DOKU untuk Top Up Wallet
     * POST /wallet/topup-doku
     *
     * Flow:
     * 1. Validasi user login & nominal
     * 2. Generate invoice_number unik (TOPUP-{userId}-{timestamp}-{rand})
     * 3. Kirim request ke DOKU API → dapat payment_url
     * 4. Simpan log pending di topup_logs
     * 5. Return payment_url ke client (mobile/web buka WebView)
     */
    public function topupDoku(): void
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

        if ($amount > 10000000) {
            $this->errorResponse('Nominal top up maksimal Rp 10.000.000 per transaksi.');
            return;
        }

        $orderId = 'TOPUP-' . $userId . '-' . time() . '-' . rand(100, 999);

        try {
            $appConfig = require APP_PATH . '/config/app.php';
            $publicUrl = rtrim($appConfig['public_url'] ?? '', '/');

            // Jika ada old_topup_code (retry dari riwayat), cancel yang lama
            $oldTopupCode = trim($data['old_topup_code'] ?? '');
            if (!empty($oldTopupCode) && str_starts_with($oldTopupCode, 'TOPUP-')) {
                // Pastikan log lama milik user yang sama (keamanan)
                $oldLog = Database::fetchOne(
                    "SELECT id, user_id, status, amount FROM `topup_logs` WHERE `topup_code` = ? LIMIT 1",
                    [$oldTopupCode]
                );
                if ($oldLog && (int)$oldLog['user_id'] === (int)$userId && $oldLog['status'] !== 'success') {
                    // Cancel log lama — session DOKU-nya memang sudah expired
                    Database::update('topup_logs', [
                        'status'     => 'canceled',
                        'notes'      => 'Dibuat ulang oleh pengguna — sesi baru: ' . $orderId,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ], 'id = ?', [$oldLog['id']]);
                }
            }

            // Callback: halaman yang akan dibuka browser setelah user selesai bayar
            // DOKU redirect ke sini dengan query param ?status=SUCCESS&order=...
            $callbackUrl = $publicUrl . '/payment/doku/callback';

            // Izinkan override dari mobile (untuk deep link)
            if (!empty($data['callback_url'])) {
                $allowedCallbackPrefixes = [
                    $publicUrl,
                    'https://market.cicago.store',
                    'cicalengkago://',   // Flutter deep link
                ];
                $isAllowed = false;
                foreach ($allowedCallbackPrefixes as $prefix) {
                    if (str_starts_with($data['callback_url'], $prefix)) {
                        $isAllowed = true;
                        break;
                    }
                }
                if ($isAllowed) {
                    $callbackUrl = $data['callback_url'];
                }
            }

            $params = [
                'invoice_number' => $orderId,
                'amount'         => (int)$amount,
                'callback_url'   => $callbackUrl,
                'customer'       => [
                    'id'    => (string)$userId,
                    'name'  => $user['name'] ?? 'Pengguna CicalengkaGO',
                    'email' => $user['email'] ?? 'customer@cicalengkago.id',
                    'phone' => $user['phone'] ?? '081234567890',
                ],
                'line_items' => [
                    [
                        'name'     => 'Top Up Saldo CicalengkaPay',
                        'price'    => (int)$amount,
                        'quantity' => 1,
                    ]
                ],
            ];

            $dokuResult = $this->dokuService->createPaymentUrl($params);

            // Simpan log pending baru sebelum user diarahkan ke DOKU
            (new \App\Models\TopupLog())->recordPending(
                $userId,
                $orderId,
                $amount,
                null,
                'doku_checkout',
                'Menunggu pembayaran via DOKU'
            );

            $this->successResponse('Sesi pembayaran DOKU berhasil dibuat', [
                'payment_url'    => $dokuResult['payment_url'],
                'redirect_url'   => $dokuResult['redirect_url'],
                'order_id'       => $orderId,
                'invoice_number' => $orderId,
            ]);
        } catch (\Throwable $e) {
            error_log('[DOKU TopUp Error] ' . $e->getMessage());
            $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Legacy alias → topupDoku()
     */
    public function topupSnap(): void
    {
        $this->topupDoku();
    }

    /**
     * Halaman Snap lama sudah dihapus
     */
    public function snapPage(): void
    {
        http_response_code(410);
        header('Content-Type: text/html; charset=UTF-8');
        echo '<h3>Halaman ini sudah tidak tersedia. Gunakan DOKU Checkout.</h3>';
        exit;
    }

    // =========================================================================
    // DOKU CALLBACK (REDIRECT SETELAH HALAMAN BAYAR DOKU)
    // =========================================================================

    /**
     * Halaman redirect setelah user selesai di halaman DOKU Checkout
     * GET /payment/doku/callback?status=SUCCESS&order=TOPUP-xxx&...
     *
     * PENTING: Ini BUKAN konfirmasi pembayaran resmi!
     * Status resmi datang dari server-to-server DOKU Webhook → dokuNotification().
     *
     * Halaman ini hanya memberi tahu user bahwa proses sedang berjalan,
     * kemudian mengarahkan kembali ke aplikasi.
     */
    public function dokuCallback(): void
    {
        $status    = strtoupper(trim($_GET['status'] ?? $_GET['transaction_status'] ?? ''));
        $orderId   = trim($_GET['order'] ?? $_GET['invoice_number'] ?? $_GET['order_id'] ?? '');
        $resultCode = trim($_GET['result_code'] ?? '');

        // Tentukan jenis transaksi
        $isTopup = str_starts_with($orderId, 'TOPUP-');

        // Redirect target setelah informasi ditampilkan
        $appConfig   = require APP_PATH . '/config/app.php';
        $publicUrl   = rtrim($appConfig['public_url'] ?? '', '/');
        $redirectUrl = $isTopup ? $publicUrl . '/wallet' : $publicUrl . '/orders';

        // Ambil info dari DB jika tersedia
        $dbStatus = null;
        if (!empty($orderId)) {
            if ($isTopup) {
                $log = Database::fetchOne(
                    "SELECT status FROM `topup_logs` WHERE `topup_code` = ? LIMIT 1",
                    [$orderId]
                );
                $dbStatus = $log['status'] ?? null;
            } else {
                $cleanCode = $orderId;
                if (preg_match('/^((?:CCG|PCL)-[A-Za-z0-9]+)/i', $orderId, $m)) {
                    $cleanCode = $m[1];
                }
                $order = Database::fetchOne(
                    "SELECT payment_status FROM `orders` WHERE `order_code` = ? LIMIT 1",
                    [$cleanCode]
                );
                $dbStatus = $order['payment_status'] ?? null;
            }
        }

        $isSuccess = in_array($status, ['SUCCESS', 'COMPLETED', 'PAID', '00']) || $dbStatus === 'paid' || $dbStatus === 'success';
        $isPending = in_array($status, ['PENDING', 'WAITING', 'PROCESS']) && !$isSuccess;

        http_response_code(200);
        header('Content-Type: text/html; charset=UTF-8');
        ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Pembayaran - CicalengkaGO</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f0f4f8;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .card {
            background: #fff;
            border-radius: 20px;
            padding: 40px 32px;
            max-width: 420px;
            width: 100%;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0,0,0,.10);
        }
        .icon { font-size: 72px; margin-bottom: 16px; }
        h1 { font-size: 22px; font-weight: 700; margin-bottom: 8px; }
        p  { font-size: 15px; color: #555; line-height: 1.6; margin-bottom: 20px; }
        .note {
            background: #fff8e1;
            border-left: 4px solid #f5a623;
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 13px;
            color: #7a5500;
            text-align: left;
            margin-bottom: 24px;
        }
        .btn {
            display: inline-block;
            background: #e8232a;
            color: #fff;
            text-decoration: none;
            border-radius: 12px;
            padding: 14px 32px;
            font-size: 15px;
            font-weight: 600;
            transition: background .2s;
        }
        .btn:hover { background: #c0181e; }
        .btn-secondary {
            display: inline-block;
            background: #f0f4f8;
            color: #333;
            text-decoration: none;
            border-radius: 12px;
            padding: 12px 24px;
            font-size: 14px;
            font-weight: 500;
            margin-top: 10px;
        }
        .progress {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            color: #888;
            font-size: 13px;
            margin-top: 16px;
        }
        .spinner {
            width: 18px;
            height: 18px;
            border: 2px solid #ddd;
            border-top-color: #e8232a;
            border-radius: 50%;
            animation: spin .7s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
<div class="card">
    <?php if ($isSuccess): ?>
        <div class="icon">✅</div>
        <h1>Pembayaran Sedang Diproses</h1>
        <p>Terima kasih! Pembayaran Anda telah diterima DOKU.<br>
           Konfirmasi otomatis akan segera masuk ke akun Anda.</p>
        <div class="note">
            🔔 <strong>Info:</strong> Saldo atau status pesanan akan diperbarui otomatis dalam beberapa detik setelah notifikasi server dari DOKU diterima. Mohon jangan tutup aplikasi.
        </div>
        <a href="<?= htmlspecialchars($redirectUrl) ?>" class="btn">Kembali ke Aplikasi</a>
    <?php elseif ($isPending): ?>
        <div class="icon">⏳</div>
        <h1>Menunggu Pembayaran</h1>
        <p>Pembayaran Anda masih dalam proses. Selesaikan pembayaran sesuai instruksi dari DOKU.</p>
        <div class="note">
            🔔 <strong>Info:</strong> Jika sudah membayar, sistem akan memperbarui status Anda secara otomatis.
        </div>
        <a href="<?= htmlspecialchars($redirectUrl) ?>" class="btn">Kembali ke Aplikasi</a>
    <?php else: ?>
        <div class="icon">❌</div>
        <h1>Pembayaran Dibatalkan</h1>
        <p>Transaksi tidak berhasil diselesaikan atau telah dibatalkan. Tidak ada saldo yang terpotong.</p>
        <a href="<?= htmlspecialchars($redirectUrl) ?>" class="btn">Coba Lagi</a>
    <?php endif; ?>

    <div class="progress" id="progressMsg">
        <div class="spinner"></div>
        <span>Mengarahkan kembali dalam <span id="countdown">5</span> detik...</span>
    </div>
</div>
<script>
    // Auto-redirect countdown
    let sec = 5;
    const cd  = document.getElementById('countdown');
    const msg = document.getElementById('progressMsg');
    const timer = setInterval(() => {
        sec--;
        if (cd) cd.textContent = sec;
        if (sec <= 0) {
            clearInterval(timer);
            window.location.href = <?= json_encode($redirectUrl) ?>;
        }
    }, 1000);
</script>
</body>
</html>
        <?php
        exit;
    }

    // =========================================================================
    // DOKU SERVER WEBHOOK (NOTIFIKASI SERVER-TO-SERVER)
    // =========================================================================

    /**
     * Menerima Webhook Server-to-Server dari DOKU
     * POST /payment/doku/notification
     *
     * Flow:
     * 1. Baca raw body
     * 2. Verifikasi HMAC-SHA256 signature dari DOKU
     * 3. Proses payload (update wallet / order status)
     * 4. Return HTTP 200 → JSON {status: "success"}
     *
     * PENTING: Hanya perubahan status melalui webhook ini yang dianggap sah.
     * Daftarkan URL ini di DOKU Merchant Portal:
     *   https://app.doku.com → Settings → Notification URL
     *   URL: https://yourdomain.com/payment/doku/notification
     */
    public function dokuNotification(): void
    {
        $rawInput = file_get_contents('php://input');

        if (empty($rawInput)) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Empty payload']);
            return;
        }

        // Kumpulkan headers DOKU untuk verifikasi signature
        $dokuHeaders = [
            'Client-Id'         => $_SERVER['HTTP_CLIENT_ID'] ?? $_SERVER['HTTP_X_CLIENT_ID'] ?? '',
            'Request-Id'        => $_SERVER['HTTP_REQUEST_ID'] ?? $_SERVER['HTTP_X_REQUEST_ID'] ?? '',
            'Request-Timestamp' => $_SERVER['HTTP_REQUEST_TIMESTAMP'] ?? $_SERVER['HTTP_X_REQUEST_TIMESTAMP'] ?? '',
            'Signature'         => $_SERVER['HTTP_SIGNATURE'] ?? $_SERVER['HTTP_X_SIGNATURE'] ?? '',
        ];

        // Verifikasi HMAC Signature — tolak jika tidak valid
        if (!$this->dokuService->verifyNotification($dokuHeaders, $rawInput)) {
            error_log('[DOKU Webhook] Signature tidak valid. Headers: ' . json_encode($dokuHeaders));
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Invalid signature']);
            return;
        }

        $payload = json_decode($rawInput, true);

        if (!is_array($payload)) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Invalid JSON payload']);
            return;
        }

        try {
            $result = $this->dokuService->processNotification($payload);
            http_response_code(200);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'result' => $result]);
        } catch (\Throwable $e) {
            error_log('[DOKU Webhook Error] ' . $e->getMessage());
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Legacy alias → dokuNotification()
     */
    public function notification(): void
    {
        $this->dokuNotification();
    }

    // =========================================================================
    // UPDATE STATUS LOG TOP UP (CLIENT-SIDE: user close/cancel)
    // =========================================================================

    /**
     * Update log status top up dari sisi client (misal user menutup WebView)
     * POST /payment/topup-update-status
     * Body: { order_id, status: "failed"|"canceled"|"success", notes }
     *
     * Catatan: "success" di sini hanya update log sementara.
     * Konfirmasi saldo resmi tetap menunggu webhook DOKU.
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
        $status  = trim($data['status'] ?? 'failed');
        $notes   = trim($data['notes'] ?? 'Dibatalkan oleh pengguna');

        if (empty($orderId)) {
            $this->errorResponse('Order ID tidak valid.');
            return;
        }

        // Pastikan log milik user yang login (keamanan)
        $log = Database::fetchOne(
            "SELECT user_id FROM `topup_logs` WHERE `topup_code` = ? LIMIT 1",
            [$orderId]
        );

        if (!$log || (int)$log['user_id'] !== (int)$userId) {
            $this->errorResponse('Transaksi tidak ditemukan atau bukan milik Anda.');
            return;
        }

        $topupLogModel = new \App\Models\TopupLog();
        if ($status === 'failed' || $status === 'canceled') {
            $topupLogModel->markFailed($orderId, $notes);
        } elseif ($status === 'success') {
            // Hanya update log — saldo resmi lewat webhook
            $topupLogModel->markSuccess($orderId, $data['payment_type'] ?? 'doku', $notes);
        }

        $this->successResponse('Status log top up berhasil diperbarui');
    }

    /**
     * Batalkan semua top up pending milik user yang sedang login
     * POST /payment/topup-cancel-all
     */
    public function cancelAllPendingTopup(): void
    {
        $userId = auth_id();
        if (!$userId) {
            $this->errorResponse('Silakan login terlebih dahulu.', null, 401);
            return;
        }

        $count = Database::execute(
            "UPDATE `topup_logs` SET `status` = 'canceled', `notes` = 'Dibatalkan sekaligus oleh pengguna', `updated_at` = ? WHERE `user_id` = ? AND `status` = 'pending'",
            [date('Y-m-d H:i:s'), $userId]
        );

        $this->successResponse("Semua transaksi pending berhasil dibatalkan ($count transaksi)", ['canceled_count' => $count]);
    }

    // =========================================================================
    // VERIFIKASI STATUS (CLIENT POLLING)
    // =========================================================================

    /**
     * Client polling untuk cek status pembayaran setelah callback
     * POST /payment/verify
     * Body: { order_id }
     *
     * Hanya membaca DB — tidak mengubah status apapun.
     * Digunakan mobile/web untuk menampilkan status terkini.
     */
    public function verifyClientCallback(): void
    {
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true) ?: $this->getPost();

        if (empty($data['order_id'])) {
            $this->errorResponse('Data pembayaran tidak lengkap.');
            return;
        }

        $orderId = trim($data['order_id']);

        // Cek Top Up
        if (str_starts_with($orderId, 'TOPUP-')) {
            $log = Database::fetchOne(
                "SELECT status, amount FROM `topup_logs` WHERE `topup_code` = ? LIMIT 1",
                [$orderId]
            );

            if (!$log) {
                $this->errorResponse('Transaksi top up tidak ditemukan.');
                return;
            }

            $dbStatus = strtolower($log['status'] ?? 'pending');
            $isPaid = in_array($dbStatus, ['success', 'paid', 'settled']);

            $this->successResponse('Status top up berhasil dicek', [
                'order_id'  => $orderId,
                'status'    => $isPaid ? 'settled' : $dbStatus,
                'amount'    => (float)($log['amount'] ?? 0),
                'is_paid'   => $isPaid,
            ]);
            return;
        }

        // Cek Order Checkout
        $cleanCode = $orderId;
        if (preg_match('/^((?:CCG|PCL)-[A-Za-z0-9]+)/i', $orderId, $matches)) {
            $cleanCode = $matches[1];
        }

        $order = Database::fetchOne(
            "SELECT order_code, payment_status, order_status FROM `orders` WHERE `order_code` = ? LIMIT 1",
            [$cleanCode]
        );

        if (!$order) {
            $this->errorResponse('Pesanan tidak ditemukan.', null, 404);
            return;
        }

        $isPaid = $order['payment_status'] === 'paid';

        $this->successResponse('Status pesanan berhasil dicek', [
            'order_code'     => $order['order_code'],
            'payment_status' => $order['payment_status'],
            'order_status'   => $order['order_status'],
            'is_paid'        => $isPaid,
        ]);
    }

    // =========================================================================
    // IN-HOUSE AUTOMATED PAYMENT SYSTEM
    // (Transfer Bank + QRIS + Kode Unik + Webhook Mutasi)
    // =========================================================================

    /**
     * Daftar metode pembayaran tersedia (Bank + QRIS)
     * GET /payment/banks
     */
    public function getBanks(): void
    {
        $banks = \App\Models\PaymentInvoice::getAvailableBanks();
        $this->successResponse('Daftar metode pembayaran berhasil diambil', $banks);
    }

    /**
     * Buat invoice pembayaran in-house (kode unik 3 digit)
     * POST /payment/create-invoice
     * Body: { amount, bank, type: "topup"|"order", order_id? }
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
            $raw  = file_get_contents('php://input');
            $data = json_decode($raw, true) ?: [];
        }

        $amount   = (float)($data['amount'] ?? 0);
        $bankCode = trim((string)($data['bank'] ?? $data['bank_code'] ?? 'QRIS'));
        $type     = trim((string)($data['type'] ?? 'topup'));
        $orderId  = !empty($data['order_id']) ? (int)$data['order_id'] : null;

        if ($amount < 1000) {
            $this->errorResponse('Nominal pembayaran minimal Rp 1.000');
            return;
        }

        try {
            $model   = new \App\Models\PaymentInvoice();
            $invoice = $model->createInvoice($userId, $type, $bankCode, $amount, $orderId);

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
     * Cek status real-time invoice in-house
     * GET /payment/check-invoice?code=INV-xxx
     */
    public function checkInvoice(): void
    {
        $code = trim((string)($_GET['code'] ?? $_GET['invoice_code'] ?? ''));
        if (empty($code)) {
            $this->errorResponse('Invoice code is required');
            return;
        }

        $invoice = Database::fetchOne(
            "SELECT * FROM `payment_invoices` WHERE `invoice_code` = ? LIMIT 1",
            [$code]
        );

        if (!$invoice) {
            $this->errorResponse('Invoice tidak ditemukan', null, 404);
            return;
        }

        $isExpired = ($invoice['status'] === 'pending' && strtotime($invoice['expires_at']) < time());
        if ($isExpired) {
            Database::update('payment_invoices', ['status' => 'expired'], 'id = ?', [$invoice['id']]);
            $invoice['status'] = 'expired';
        }

        $this->successResponse('Status invoice berhasil dicek', [
            'invoice_code' => $invoice['invoice_code'],
            'status'       => $invoice['status'],
            'total_amount' => (float)$invoice['total_amount'],
            'base_amount'  => (float)$invoice['base_amount'],
            'unique_code'  => (int)$invoice['unique_code'],
            'paid_at'      => $invoice['paid_at'],
            'expires_at'   => $invoice['expires_at'],
        ]);
    }

    /**
     * Webhook otomatis dari sistem mutasi bank / MacroDroid / SMS Banking
     * POST /payment/auto-webhook
     */
    public function autoWebhook(): void
    {
        $rawInput = file_get_contents('php://input');
        $payload  = json_decode($rawInput, true) ?: $_POST;

        $amount  = (float)($payload['amount'] ?? 0);
        $bank    = $payload['bank'] ?? $payload['bank_name'] ?? null;
        $sender  = $payload['sender'] ?? $payload['from'] ?? null;
        $rawText = $payload['text'] ?? $payload['message'] ?? $payload['notification'] ?? null;

        $model  = new \App\Models\PaymentInvoice();
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
     * Admin approve manual invoice
     * POST /payment/simulate-pay  (only via admin panel, tidak expose ke public)
     */
    public function simulatePay(): void
    {
        // Pastikan hanya admin yang bisa memanggil ini
        $userId = auth_id();
        if (!$userId) {
            $this->errorResponse('Unauthorized.', null, 401);
            return;
        }

        $user = auth_user();
        if (($user['role'] ?? '') !== 'admin') {
            $this->errorResponse('Akses ditolak. Hanya admin yang dapat melakukan ini.', null, 403);
            return;
        }

        $data = $this->getPost();
        if (empty($data)) {
            $raw  = file_get_contents('php://input');
            $data = json_decode($raw, true) ?: [];
        }

        $code = trim((string)($data['invoice_code'] ?? $data['code'] ?? ''));
        if (empty($code)) {
            $this->errorResponse('Invoice code diperlukan');
            return;
        }

        $model   = new \App\Models\PaymentInvoice();
        $success = $model->approveInvoice($code, 'Admin CicalengkaGO');

        if ($success) {
            $this->successResponse('Pembayaran invoice berhasil diapprove oleh admin!', [
                'invoice_code' => $code,
                'status'       => 'paid',
            ]);
        } else {
            $this->errorResponse('Invoice tidak ditemukan atau sudah dibayar.');
        }
    }

    // =========================================================================
    // TRANSFER SALDO CICALENGKAPAY
    // =========================================================================

    /**
     * Transfer saldo ke: Rekening Bank | E-Wallet | Sesama CicalengkaPay
     * POST /payment/transfer
     * Body: { transfer_type: "bank"|"ewallet"|"internal", amount, ... }
     */
    public function transfer(): void
    {
        $senderId = auth_id();
        if (!$senderId) {
            $this->errorResponse('Silakan login terlebih dahulu.', null, 401);
            return;
        }

        $data         = $this->getPost();
        $transferType = trim((string)($data['transfer_type'] ?? $data['type'] ?? 'bank'));
        $amount       = (float)($data['amount'] ?? 0);
        $notes        = trim((string)($data['notes'] ?? $data['note'] ?? ''));

        if ($amount < 1000) {
            $this->errorResponse('Nominal kirim uang minimal Rp 1.000.');
            return;
        }

        $walletModel  = new \App\Models\Wallet();
        $sender       = auth_user();
        $senderWallet = $walletModel->getOrCreate($senderId, 'customer');
        $currentBalance = (float)($senderWallet['balance'] ?? 0);

        // Auto-heal kolom (migrasi aman)
        try { Database::execute("ALTER TABLE `withdraw_requests` MODIFY COLUMN `user_type` VARCHAR(32) NOT NULL DEFAULT 'customer'"); } catch (\Throwable $e) {}
        try { Database::execute("ALTER TABLE `wallet_transactions` MODIFY COLUMN `category` VARCHAR(50) NOT NULL DEFAULT 'transfer'"); } catch (\Throwable $e) {}

        // ------------------------------------------------------------------
        // 1. Transfer ke Rekening Bank (Fee Rp 1.500)
        // ------------------------------------------------------------------
        if ($transferType === 'bank') {
            $bankName      = trim((string)($data['bank_name'] ?? 'BCA'));
            $accountNumber = trim((string)($data['account_number'] ?? ''));
            $accountHolder = trim((string)($data['account_holder'] ?? ''));
            $fee           = 1500.0;
            $totalDeduct   = $amount + $fee;

            if (empty($bankName) || empty($accountNumber) || empty($accountHolder)) {
                $this->errorResponse('Nama Bank, Nomor Rekening, dan Nama Pemilik Rekening wajib diisi lengkap.');
                return;
            }

            if ($amount < 10000) {
                $this->errorResponse('Nominal transfer ke rekening bank minimal Rp 10.000.');
                return;
            }

            if ($currentBalance < $totalDeduct) {
                $this->errorResponse(
                    'Saldo tidak mencukupi. Dibutuhkan Rp ' . number_format($totalDeduct, 0, ',', '.') .
                    ' (Transfer Rp ' . number_format($amount, 0, ',', '.') . ' + Biaya Admin Rp 1.500)' .
                    ', sedangkan saldo Anda Rp ' . number_format($currentBalance, 0, ',', '.') . '.'
                );
                return;
            }

            try {
                $withdrawCode = 'TRF-BANK-' . strtoupper(substr(uniqid(), -6)) . rand(10, 99);
                Database::transaction(function () use ($walletModel, $senderId, $amount, $fee, $bankName, $accountNumber, $accountHolder, $notes, $withdrawCode) {
                    $walletModel->debit($senderId, $amount, 'withdraw',
                        "Kirim uang ke Bank {$bankName} ({$accountNumber} a.n {$accountHolder})" . ($notes ? " - {$notes}" : ''),
                        $withdrawCode
                    );
                    $walletModel->debit($senderId, $fee, 'fee',
                        "Biaya admin transfer ke Bank {$bankName} (Rp 1.500)",
                        $withdrawCode . '-FEE'
                    );
                    Database::insert('withdraw_requests', [
                        'withdraw_code'  => $withdrawCode,
                        'user_id'        => $senderId,
                        'user_type'      => 'customer',
                        'amount'         => $amount,
                        'bank_name'      => $bankName,
                        'account_number' => $accountNumber,
                        'account_holder' => $accountHolder,
                        'status'         => 'pending',
                        'admin_notes'    => 'Kirim Uang ke Bank via CicalengkaPay (Biaya Admin Rp 1.500)' . ($notes ? " | Pesan: {$notes}" : ''),
                    ]);
                });

                $this->successResponse(
                    "Permintaan transfer ke {$bankName} sebesar Rp " . number_format($amount, 0, ',', '.') . " berhasil diajukan! (Biaya admin Rp 1.500).",
                    [
                        'transfer_type'  => 'bank',
                        'bank_name'      => $bankName,
                        'account_number' => $accountNumber,
                        'account_holder' => $accountHolder,
                        'amount'         => $amount,
                        'fee'            => $fee,
                        'total_deducted' => $totalDeduct,
                        'reference_id'   => $withdrawCode,
                    ]
                );
                return;
            } catch (\Throwable $e) {
                $this->errorResponse('Gagal memproses transfer ke bank: ' . $e->getMessage());
                return;
            }
        }

        // ------------------------------------------------------------------
        // 2. Transfer ke E-Wallet (Fee Rp 1.500)
        // ------------------------------------------------------------------
        if ($transferType === 'ewallet') {
            $ewalletName   = trim((string)($data['ewallet_name'] ?? 'DANA'));
            $accountNumber = trim((string)($data['account_number'] ?? $data['phone'] ?? ''));
            $accountHolder = trim((string)($data['account_holder'] ?? ''));
            $fee           = 1500.0;
            $totalDeduct   = $amount + $fee;

            if (empty($ewalletName) || empty($accountNumber) || empty($accountHolder)) {
                $this->errorResponse('Nama E-Wallet, Nomor HP Akun, dan Nama Akun Penerima wajib diisi.');
                return;
            }

            if ($amount < 10000) {
                $this->errorResponse('Nominal transfer ke E-Wallet minimal Rp 10.000.');
                return;
            }

            if ($currentBalance < $totalDeduct) {
                $this->errorResponse(
                    'Saldo tidak mencukupi. Dibutuhkan Rp ' . number_format($totalDeduct, 0, ',', '.') .
                    ' (Transfer Rp ' . number_format($amount, 0, ',', '.') . ' + Biaya Admin Rp 1.500)' .
                    ', sedangkan saldo Anda Rp ' . number_format($currentBalance, 0, ',', '.') . '.'
                );
                return;
            }

            try {
                $withdrawCode = 'TRF-EWAL-' . strtoupper(substr(uniqid(), -6)) . rand(10, 99);
                Database::transaction(function () use ($walletModel, $senderId, $amount, $fee, $ewalletName, $accountNumber, $accountHolder, $notes, $withdrawCode) {
                    $walletModel->debit($senderId, $amount, 'withdraw',
                        "Kirim uang ke E-Wallet {$ewalletName} ({$accountNumber} a.n {$accountHolder})" . ($notes ? " - {$notes}" : ''),
                        $withdrawCode
                    );
                    $walletModel->debit($senderId, $fee, 'fee',
                        "Biaya admin transfer ke E-Wallet {$ewalletName} (Rp 1.500)",
                        $withdrawCode . '-FEE'
                    );
                    Database::insert('withdraw_requests', [
                        'withdraw_code'  => $withdrawCode,
                        'user_id'        => $senderId,
                        'user_type'      => 'customer',
                        'amount'         => $amount,
                        'bank_name'      => 'E-Wallet ' . $ewalletName,
                        'account_number' => $accountNumber,
                        'account_holder' => $accountHolder,
                        'status'         => 'pending',
                        'admin_notes'    => 'Kirim Uang ke E-Wallet via CicalengkaPay (Biaya Admin Rp 1.500)' . ($notes ? " | Pesan: {$notes}" : ''),
                    ]);
                });

                $this->successResponse(
                    "Permintaan transfer ke E-Wallet {$ewalletName} sebesar Rp " . number_format($amount, 0, ',', '.') . " berhasil diajukan! (Biaya admin Rp 1.500).",
                    [
                        'transfer_type'  => 'ewallet',
                        'ewallet_name'   => $ewalletName,
                        'account_number' => $accountNumber,
                        'account_holder' => $accountHolder,
                        'amount'         => $amount,
                        'fee'            => $fee,
                        'total_deducted' => $totalDeduct,
                        'reference_id'   => $withdrawCode,
                    ]
                );
                return;
            } catch (\Throwable $e) {
                $this->errorResponse('Gagal memproses transfer ke e-wallet: ' . $e->getMessage());
                return;
            }
        }

        // ------------------------------------------------------------------
        // 3. Transfer Sesama CicalengkaPay (Bebas Biaya Admin)
        // ------------------------------------------------------------------
        $recipientPhone = trim((string)($data['recipient_phone'] ?? $data['phone'] ?? $data['recipient'] ?? ''));
        if (empty($recipientPhone)) {
            $this->errorResponse('Nomor WhatsApp / HP penerima wajib diisi.');
            return;
        }

        $cleanPhone    = preg_replace('/[^0-9]/', '', $recipientPhone);
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
                    $senderId, $amount, 'transfer',
                    "Kirim saldo ke {$recipient['name']} ({$recipient['phone']})" . ($notes ? " - {$notes}" : ''),
                    $refId
                );
                $walletModel->credit(
                    (int)$recipient['id'], $amount, 'transfer',
                    "Terima saldo dari {$sender['name']} ({$sender['phone']})" . ($notes ? " - {$notes}" : ''),
                    $refId
                );
            });

            $this->successResponse(
                "Berhasil mengirim uang sebesar Rp " . number_format($amount, 0, ',', '.') . " ke {$recipient['name']}! (Bebas Biaya Admin)",
                [
                    'recipient_name'  => $recipient['name'],
                    'recipient_phone' => $recipient['phone'],
                    'amount'          => $amount,
                    'fee'             => 0,
                    'total_deducted'  => $amount,
                    'reference_id'    => $refId,
                    'notes'           => $notes,
                ]
            );
        } catch (\Throwable $e) {
            $this->errorResponse('Gagal mengirim saldo: ' . $e->getMessage());
        }
    }
}

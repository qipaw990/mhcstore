<?php
namespace App\Services;

use App\Core\Database;
use App\Models\BusinessSetting;
use App\Models\Order;
use App\Models\Wallet;
use App\Models\TopupLog;
use App\Models\Notification;
use Exception;

class DokuService
{
    private string $clientId;
    private string $secretKey;
    private bool $isProduction;
    private bool $isEnabled;
    private string $baseUrl;

    public function __construct()
    {
        $dbClientId     = $this->getSetting('doku_client_id');
        $dbSecretKey    = $this->getSetting('doku_secret_key');
        $dbEnvironment  = $this->getSetting('doku_environment', 'production'); // default production
        $dbEnabled      = $this->getSetting('doku_enabled', '1');

        $this->clientId     = $dbClientId ?: (getenv('DOKU_CLIENT_ID') ?: '');
        $this->secretKey    = $dbSecretKey ?: (getenv('DOKU_SECRET_KEY') ?: '');
        $this->isProduction = ($dbEnvironment === 'production');
        $this->isEnabled    = ($dbEnabled === '1' || $dbEnabled === true);

        if ($this->isProduction) {
            $this->baseUrl = 'https://api.doku.com';
        } else {
            $this->baseUrl = 'https://api-sandbox.doku.com';
        }
    }

    private function getSetting(string $key, $default = ''): string
    {
        try {
            $row = Database::fetchOne(
                "SELECT value_text FROM business_settings WHERE key_name = ? LIMIT 1",
                [$key]
            );
            return $row['value_text'] ?? $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    public function isProduction(): bool
    {
        return $this->isProduction;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    /**
     * Generate DOKU HMAC-SHA256 Signature for API Requests
     */
    public function generateSignature(string $clientId, string $requestId, string $requestTimestamp, string $targetPath, string $requestBody): string
    {
        // 1. Digest = Base64(SHA256(requestBody))
        $digest = base64_encode(hash('sha256', $requestBody, true));

        // 2. Component String
        $component = "Client-Id:" . $clientId . "\n" .
                     "Request-Id:" . $requestId . "\n" .
                     "Request-Timestamp:" . $requestTimestamp . "\n" .
                     "Request-Target:" . $targetPath . "\n" .
                     "Digest:" . $digest;

        // 3. Signature = "HMACSHA256=" + Base64(HMAC-SHA256(SecretKey, Component))
        $signature = base64_encode(hash_hmac('sha256', $component, $this->secretKey, true));
        return "HMACSHA256=" . $signature;
    }

    /**
     * Generate DOKU Checkout Payment URL
     * Endpoint: POST /checkout/v1/payment
     */
    public function createPaymentUrl(array $params): array
    {
        if (empty($this->clientId) || empty($this->secretKey)) {
            throw new Exception("Kredensial DOKU Payment Gateway belum dikonfigurasi di Pengaturan Admin.");
        }

        $appConfig = require APP_PATH . '/config/app.php';
        $publicUrl = rtrim($appConfig['public_url'] ?? '', '/');

        $invoiceNumber = $params['invoice_number'] ?? ('INV-' . time() . '-' . rand(100, 999));
        $amount = (int)round((float)($params['amount'] ?? 0));

        if ($amount < 1000) {
            throw new Exception("Nominal transaksi tidak valid (minimal Rp 1.000).");
        }

        $callbackUrl = $params['callback_url'] ?? ($publicUrl . '/orders');

        $body = [
            'order' => [
                'invoice_number' => $invoiceNumber,
                'amount'         => $amount,
                'currency'       => 'IDR',
                'callback_url'   => $callbackUrl,
                'auto_redirect'  => true,
            ],
            'payment' => [
                'payment_due_date' => 60, // 60 menit masa berlaku
            ],
            'customer' => [
                'id'    => (string)($params['customer']['id'] ?? 'GUEST'),
                'name'  => $params['customer']['name'] ?? 'Pengguna CicalengkaGO',
                'email' => $params['customer']['email'] ?? 'customer@cicalengkago.id',
                'phone' => $params['customer']['phone'] ?? '081234567890',
            ],
        ];

        if (!empty($params['line_items']) && is_array($params['line_items'])) {
            $body['order']['line_items'] = $params['line_items'];
        }

        $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES);
        $targetPath = '/checkout/v1/payment';
        $requestId = 'doku-req-' . uniqid() . '-' . time();
        $requestTimestamp = gmdate('Y-m-d\TH:i:s\Z');

        $signature = $this->generateSignature($this->clientId, $requestId, $requestTimestamp, $targetPath, $jsonBody);

        $headers = [
            'Client-Id: ' . $this->clientId,
            'Request-Id: ' . $requestId,
            'Request-Timestamp: ' . $requestTimestamp,
            'Signature: ' . $signature,
            'Content-Type: application/json',
        ];

        $ch = curl_init($this->baseUrl . $targetPath);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonBody);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new Exception("Koneksi ke DOKU Checkout gagal: " . $curlError);
        }

        $result = json_decode($response, true);

        if ($httpCode >= 200 && $httpCode < 300 && !empty($result['response']['payment']['url'])) {
            return [
                'success'      => true,
                'payment_url'  => $result['response']['payment']['url'],
                'redirect_url' => $result['response']['payment']['url'],
                'invoice_number' => $invoiceNumber,
                'raw_response' => $result,
            ];
        }

        // Ekstrak pesan error secara aman (DOKU sering mengembalikan array pada error.message)
        $errorMsg = 'Gagal membuat sesi pembayaran DOKU (HTTP ' . $httpCode . ')';
        if (!empty($result)) {
            $rawMsg = $result['error']['message'] ?? $result['message'] ?? $result['errors']['message'] ?? $result['error'] ?? null;
            if (is_array($rawMsg)) {
                $errorMsg = implode(', ', array_map(function($v) {
                    return is_array($v) ? json_encode($v, JSON_UNESCAPED_SLASHES) : (string)$v;
                }, $rawMsg));
            } elseif (is_string($rawMsg) && trim($rawMsg) !== '') {
                $errorMsg = $rawMsg;
            } else {
                $errorMsg = json_encode($result, JSON_UNESCAPED_SLASHES);
            }
        }
        error_log('[DOKU Checkout Error] ' . $errorMsg . ' | Response HTTP ' . $httpCode . ' | Request: ' . $jsonBody);
        throw new Exception($errorMsg);
    }

    /**
     * Test connection to DOKU API
     */
    public function testApiConnection(): array
    {
        if (empty($this->clientId) || empty($this->secretKey)) {
            return [
                'success' => false,
                'message' => 'Client ID atau Secret Key DOKU belum diisi.',
            ];
        }

        try {
            $testInvoice = 'TEST-CONN-' . time();
            $body = [
                'order' => [
                    'invoice_number' => $testInvoice,
                    'amount'         => 10000,
                    'currency'       => 'IDR',
                    'callback_url'   => 'https://market.cicago.store',
                ],
                'payment' => [
                    'payment_due_date' => 15,
                ],
                'customer' => [
                    'id'    => 'TEST_ADMIN',
                    'name'  => 'Test Admin CicalengkaGO',
                    'email' => 'admin@cicalengkago.id',
                    'phone' => '081234567890',
                ],
            ];

            $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES);
            $targetPath = '/checkout/v1/payment';
            $requestId = 'test-' . uniqid();
            $requestTimestamp = gmdate('Y-m-d\TH:i:s\Z');

            $signature = $this->generateSignature($this->clientId, $requestId, $requestTimestamp, $targetPath, $jsonBody);

            $headers = [
                'Client-Id: ' . $this->clientId,
                'Request-Id: ' . $requestId,
                'Request-Timestamp: ' . $requestTimestamp,
                'Signature: ' . $signature,
                'Content-Type: application/json',
            ];

            $ch = curl_init($this->baseUrl . $targetPath);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonBody);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr = curl_error($ch);
            curl_close($ch);

            if ($response === false) {
                return [
                    'success' => false,
                    'message' => 'Gagal terhubung ke server DOKU: ' . $curlErr,
                ];
            }

            $res = json_decode($response, true);
            if ($httpCode === 200 || !empty($res['response']['payment']['url'])) {
                return [
                    'success'     => true,
                    'environment' => $this->isProduction ? 'Production / Live' : 'Sandbox / Testing',
                    'message'     => 'Koneksi DOKU API (' . ($this->isProduction ? 'Production' : 'Sandbox') . ') Berhasil & Kredensial Valid!',
                ];
            }

            $errMsg = $res['error']['message'] ?? $res['message'] ?? 'Respons tidak valid dari DOKU (HTTP ' . $httpCode . ')';
            return [
                'success' => false,
                'message' => 'DOKU API Error: ' . $errMsg,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Verify Webhook Signature from DOKU
     *
     * DOKU mengirim signature menggunakan path notifikasi yang didaftarkan
     * di Merchant Portal DOKU. Pastikan $targetPath sesuai dengan
     * Notification URL yang Anda daftarkan, contoh: /payment/doku/notification
     *
     * @param array  $headers  HTTP headers dari DOKU
     * @param string $rawBody  Raw request body (JSON string)
     * @return bool  true jika valid, false jika tidak
     */
    public function verifyNotification(array $headers, string $rawBody): bool
    {
        if (empty($this->secretKey)) {
            error_log('[DOKU] verifyNotification: secretKey kosong, tolak semua webhook');
            return false;
        }

        // Header bisa dalam berbagai format (case-sensitive PHP server)
        $clientId         = $headers['Client-Id']         ?? $headers['client-id']         ?? $headers['CLIENT_ID']         ?? '';
        $requestId        = $headers['Request-Id']        ?? $headers['request-id']        ?? $headers['REQUEST_ID']        ?? '';
        $requestTimestamp = $headers['Request-Timestamp'] ?? $headers['request-timestamp'] ?? $headers['REQUEST_TIMESTAMP'] ?? '';
        $receivedSignature = $headers['Signature']        ?? $headers['signature']         ?? $headers['SIGNATURE']         ?? '';

        if (empty($receivedSignature) || empty($requestId) || empty($requestTimestamp)) {
            error_log('[DOKU] verifyNotification: header tidak lengkap. RequestId=' . $requestId . ' Timestamp=' . $requestTimestamp);
            return false;
        }

        // Path harus sesuai dengan yang terdaftar di DOKU Merchant Portal
        $targetPath = '/payment/doku/notification';

        $expectedSignature = $this->generateSignature(
            $this->clientId ?: $clientId,
            $requestId,
            $requestTimestamp,
            $targetPath,
            $rawBody
        );

        $valid = hash_equals($expectedSignature, $receivedSignature);

        if (!$valid) {
            error_log('[DOKU] Signature mismatch. Expected=' . $expectedSignature . ' Got=' . $receivedSignature);
        }

        return $valid;
    }

    /**
     * Process Webhook Notification from DOKU
     */
    public function processNotification(array $payload): array
    {
        // DOKU notification structure contains order and transaction
        $orderInfo = $payload['order'] ?? [];
        $transactionInfo = $payload['transaction'] ?? [];

        $invoiceNumber = $orderInfo['invoice_number'] ?? $payload['invoice_number'] ?? '';
        $status = strtoupper($transactionInfo['status'] ?? $orderInfo['status'] ?? $payload['status'] ?? '');
        $amount = (float)($orderInfo['amount'] ?? $transactionInfo['amount'] ?? 0);
        $paymentChannel = $payload['channel']['id'] ?? $transactionInfo['payment_channel'] ?? 'doku_checkout';

        if (empty($invoiceNumber)) {
            return ['success' => false, 'message' => 'Invoice number tidak ditemukan'];
        }

        $isSettled = in_array($status, ['SUCCESS', 'COMPLETED', 'PAID', 'SETTLEMENT']);
        $isPending = in_array($status, ['PENDING', 'WAITING']);
        $isFailed  = in_array($status, ['FAILED', 'EXPIRED', 'CANCELLED', 'DENY']);

        // 1. CicalengkaPay Wallet Top-Up
        if (str_starts_with($invoiceNumber, 'TOPUP-')) {
            return $this->handleWalletTopup($invoiceNumber, $amount, $paymentChannel, $isSettled, $isPending, $isFailed);
        }

        // 2. Order Checkout
        return $this->handleOrderPayment($invoiceNumber, $paymentChannel, $isSettled, $isPending, $isFailed);
    }

    private function handleWalletTopup(string $orderId, float $amount, string $paymentType, bool $isSettled, bool $isPending, bool $isFailed): array
    {
        $parts = explode('-', $orderId);
        $userId = (int)($parts[1] ?? 0);

        if ($userId <= 0) {
            return ['success' => false, 'message' => 'User ID tidak valid dalam ID topup'];
        }

        $topupLogModel = new TopupLog();

        if ($isSettled) {
            $existing = Database::fetchOne(
                "SELECT id FROM `wallet_transactions` WHERE `reference_id` = ? LIMIT 1",
                [$orderId]
            );

            if (!$existing) {
                $walletModel = new Wallet();
                $walletModel->credit(
                    $userId,
                    $amount,
                    'topup',
                    "Top Up CicalengkaPay via DOKU ({$paymentType})",
                    $orderId
                );

                (new Notification())->createNotification(
                    $userId,
                    'Top Up DOKU Berhasil! 🎉',
                    "Saldo CicalengkaPay sebesar " . format_rupiah($amount) . " berhasil ditambahkan via DOKU ({$paymentType}).",
                    'wallet'
                );
            }

            $topupLogModel->markSuccess($orderId, 'doku_' . strtolower($paymentType), "Top Up via DOKU ({$paymentType}) berhasil");
            return ['success' => true, 'status' => 'settled', 'message' => 'Top Up DOKU berhasil diproses'];
        }

        if ($isPending) {
            $topupLogModel->recordPending($userId, $orderId, $amount, null, 'doku_' . strtolower($paymentType), 'Menunggu pembayaran DOKU');
            return ['success' => true, 'status' => 'pending', 'message' => 'Menunggu pembayaran top-up DOKU'];
        }

        $topupLogModel->markFailed($orderId, "Top Up via DOKU ({$paymentType}) gagal/dibatalkan");
        return ['success' => true, 'status' => 'failed', 'message' => 'Top Up DOKU gagal atau dibatalkan'];
    }

    private function handleOrderPayment(string $invoiceNumber, string $paymentType, bool $isSettled, bool $isPending, bool $isFailed): array
    {
        $order = Database::fetchOne("SELECT * FROM `orders` WHERE `order_code` = ? LIMIT 1", [$invoiceNumber]);
        if (!$order) {
            // Check prefix extraction e.g. CCG-A1B2C3 from CCG-A1B2C3-1723829100
            if (preg_match('/^((?:CCG|PCL)-[A-Za-z0-9]+)/i', $invoiceNumber, $matches)) {
                $baseCode = $matches[1];
                $order = Database::fetchOne("SELECT * FROM `orders` WHERE `order_code` = ? LIMIT 1", [$baseCode]);
            }
        }

        if (!$order) {
            return ['success' => false, 'message' => 'Pesanan tidak ditemukan'];
        }

        $orderId = (int)$order['id'];
        $orderCode = $order['order_code'];
        $customerId = (int)$order['user_id'];

        if ($isSettled) {
            if ($order['payment_status'] !== 'paid') {
                Database::update('orders', [
                    'payment_status' => 'paid',
                    'payment_method' => 'doku',
                    'order_status'   => ($order['order_status'] === 'pending') ? 'confirmed' : $order['order_status'],
                    'updated_at'     => date('Y-m-d H:i:s'),
                ], '`id` = ?', [$orderId]);

                (new Notification())->createNotification(
                    $customerId,
                    'Pembayaran DOKU Berhasil! ✅',
                    "Pembayaran pesanan #{$orderCode} via DOKU telah berhasil diverifikasi.",
                    'order',
                    $orderId
                );
            }

            return ['success' => true, 'status' => 'settled', 'message' => 'Pesanan berhasil diverifikasi'];
        }

        if ($isFailed) {
            Database::update('orders', [
                'payment_status' => 'unpaid',
                'order_status'   => 'canceled',
                'cancel_reason'  => "Pembayaran DOKU {$status} atau kedaluwarsa",
                'updated_at'     => date('Y-m-d H:i:s'),
            ], '`id` = ?', [$orderId]);

            return ['success' => true, 'status' => 'failed', 'message' => 'Pesanan dibatalkan karena pembayaran DOKU gagal'];
        }

        return ['success' => true, 'status' => 'pending', 'message' => 'Menunggu pembayaran DOKU'];
    }
}

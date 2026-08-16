<?php
namespace App\Services;

use App\Core\Database;
use App\Models\Order;
use App\Models\Wallet;
use App\Models\Notification;
use Exception;

class MidtransService
{
    private array $config;
    private string $serverKey;
    private string $clientKey;
    private bool $isProduction;
    private string $apiUrl;
    private string $snapUrl;

    public function __construct()
    {
        $this->config = require APP_PATH . '/config/midtrans.php';

        // Priority: DB business_settings → ENV/config fallback
        $dbServerKey    = $this->getSettingFromDb('midtrans_server_key');
        $dbClientKey    = $this->getSettingFromDb('midtrans_client_key');
        $dbMerchantId   = $this->getSettingFromDb('midtrans_merchant_id');
        $dbEnvironment  = $this->getSettingFromDb('midtrans_environment');

        $this->serverKey    = $dbServerKey ?: $this->config['server_key'];
        $this->clientKey    = $dbClientKey ?: $this->config['client_key'];
        $this->isProduction = $dbEnvironment ? ($dbEnvironment === 'production') : (bool)$this->config['is_production'];

        if ($this->isProduction) {
            $this->apiUrl       = 'https://app.midtrans.com/snap/v1/transactions';
            $this->snapUrl      = 'https://app.midtrans.com/snap/snap.js';
        } else {
            $this->apiUrl       = 'https://app.sandbox.midtrans.com/snap/v1/transactions';
            $this->snapUrl      = 'https://app.sandbox.midtrans.com/snap/snap.js';
        }
    }

    private function getSettingFromDb(string $key): string
    {
        try {
            $row = Database::fetchOne(
                "SELECT value_text FROM business_settings WHERE key_name = ? LIMIT 1",
                [$key]
            );
            return $row['value_text'] ?? '';
        } catch (\Exception $e) {
            return '';
        }
    }

    public function getClientKey(): string
    {
        return $this->clientKey;
    }

    public function getSnapUrl(): string
    {
        return $this->snapUrl;
    }

    /**
     * Create Snap Token for transactions (Order Checkout or Wallet Top-Up)
     */
    public function createSnapToken(array $params): array
    {
        if (empty($params['transaction_details']['order_id']) || empty($params['transaction_details']['gross_amount'])) {
            throw new Exception("Parameter transaksi Midtrans tidak lengkap.");
        }

        // Ensure gross_amount is integer (Midtrans requires integer in IDR)
        $params['transaction_details']['gross_amount'] = (int)round((float)$params['transaction_details']['gross_amount']);

        $payloadJson = json_encode($params);
        $authHeader = 'Basic ' . base64_encode($this->serverKey . ':');

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $this->apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payloadJson,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: ' . $authHeader
            ],
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => false // Sandbox local development compatibility
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false || !empty($curlError)) {
            throw new Exception("Gagal menghubungi server Midtrans: " . $curlError);
        }

        $result = json_decode($response, true);

        if ($httpCode >= 400 || empty($result['token'])) {
            $errMsg = $result['error_messages'][0] ?? $result['message'] ?? "Error Snap API (HTTP {$httpCode})";
            throw new Exception("Midtrans Error: " . $errMsg);
        }

        return [
            'token'        => $result['token'],
            'redirect_url' => $result['redirect_url'] ?? '',
            'client_key'   => $this->clientKey
        ];
    }

    /**
     * Verify SHA512 Signature Key from Midtrans Webhook
     */
    public function verifySignature(string $orderId, string $statusCode, string $grossAmount, string $signatureKey): bool
    {
        $calculated = hash('sha512', $orderId . $statusCode . $grossAmount . $this->serverKey);
        return hash_equals($calculated, $signatureKey);
    }

    /**
     * Process Webhook Notification or Client Payment Result
     */
    public function processNotification(array $payload): array
    {
        $orderId = $payload['order_id'] ?? '';
        $statusCode = (string)($payload['status_code'] ?? '');
        $grossAmount = (string)($payload['gross_amount'] ?? '0');
        $signatureKey = $payload['signature_key'] ?? '';
        $transactionStatus = $payload['transaction_status'] ?? '';
        $fraudStatus = $payload['fraud_status'] ?? '';
        $paymentType = $payload['payment_type'] ?? 'midtrans';

        if (empty($orderId)) {
            return ['success' => false, 'message' => 'Order ID tidak valid'];
        }

        // Verify signature if provided by webhook
        if (!empty($signatureKey)) {
            if (!$this->verifySignature($orderId, $statusCode, $grossAmount, $signatureKey)) {
                return ['success' => false, 'message' => 'Invalid signature key'];
            }
        }

        $isSettled = ($transactionStatus === 'settlement' || ($transactionStatus === 'capture' && $fraudStatus === 'accept'));
        $isPending = ($transactionStatus === 'pending');
        $isFailed = in_array($transactionStatus, ['deny', 'cancel', 'expire']);

        // Handle CicalengkaPay Wallet Top-Up (Order ID format: TOPUP-{userId}-{random})
        if (str_starts_with($orderId, 'TOPUP-')) {
            return $this->handleWalletTopup($orderId, (float)$grossAmount, $paymentType, $isSettled, $isPending, $isFailed);
        }

        // Handle Order Checkout (Order ID format: CCG-...)
        return $this->handleOrderPayment($orderId, $paymentType, $isSettled, $isPending, $isFailed);
    }

    private function handleWalletTopup(string $orderId, float $amount, string $paymentType, bool $isSettled, bool $isPending, bool $isFailed): array
    {
        // Parse user ID from orderId: TOPUP-{userId}-{timestamp}-{random}
        $parts = explode('-', $orderId);
        $userId = (int)($parts[1] ?? 0);

        if ($userId <= 0) {
            return ['success' => false, 'message' => 'User ID tidak valid dalam ID topup'];
        }

        if ($isSettled) {
            // Check idempotency: avoid duplicate credits
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
                    "Top Up CicalengkaPay via Midtrans ({$paymentType})",
                    $orderId
                );

                (new Notification())->createNotification(
                    $userId,
                    'Top Up Berhasil! 🎉',
                    "Saldo CicalengkaPay sebesar " . format_rupiah($amount) . " berhasil ditambahkan via {$paymentType}.",
                    'wallet'
                );
            }

            return ['success' => true, 'status' => 'settled', 'message' => 'Top Up berhasil diproses'];
        }

        if ($isPending) {
            return ['success' => true, 'status' => 'pending', 'message' => 'Menunggu pembayaran top-up'];
        }

        return ['success' => true, 'status' => 'failed', 'message' => 'Top Up gagal atau dibatalkan'];
    }

    private function handleOrderPayment(string $orderId, string $paymentType, bool $isSettled, bool $isPending, bool $isFailed): array
    {
        $order = Database::fetchOne("SELECT * FROM `orders` WHERE `order_code` = ? LIMIT 1", [$orderId]);
        if (!$order) {
            // Extract base code e.g. CCG-A1B2C3 from CCG-A1B2C3-1723829100
            if (preg_match('/^((?:CCG|PCL)-[A-Za-z0-9]+)/i', $orderId, $matches)) {
                $baseCode = $matches[1];
                $order = Database::fetchOne("SELECT * FROM `orders` WHERE `order_code` = ? LIMIT 1", [$baseCode]);
            }
        }

        if (!$order) {
            return ['success' => false, 'message' => 'Pesanan tidak ditemukan'];
        }

        $orderCode = $order['order_code'];

        if ($isSettled) {
            if ($order['payment_status'] !== 'paid') {
                Database::update('orders', [
                    'payment_status' => 'paid',
                    'payment_method' => 'midtrans',
                    'order_status'   => ($order['order_status'] === 'pending') ? 'confirmed' : $order['order_status'],
                    'confirmed_at'   => ($order['order_status'] === 'pending') ? date('Y-m-d H:i:s') : ($order['confirmed_at'] ?? date('Y-m-d H:i:s'))
                ], 'id = ?', [$order['id']]);

                // Create customer notification
                (new Notification())->createNotification(
                    $order['customer_id'],
                    'Pembayaran Berhasil! 💳',
                    "Pembayaran pesanan #{$orderCode} via {$paymentType} telah berhasil dikonfirmasi.",
                    'order'
                );
            }

            return ['success' => true, 'status' => 'settled', 'message' => 'Pembayaran pesanan berhasil dikonfirmasi'];
        }

        if ($isPending) {
            Database::update('orders', [
                'payment_status' => 'unpaid',
                'payment_method' => 'midtrans'
            ], 'id = ?', [$order['id']]);

            return ['success' => true, 'status' => 'pending', 'message' => 'Menunggu pembayaran dari pelanggan'];
        }

        if ($isFailed) {
            Database::update('orders', [
                'payment_status' => 'failed'
            ], 'id = ?', [$order['id']]);

            return ['success' => true, 'status' => 'failed', 'message' => 'Pembayaran gagal'];
        }

        return ['success' => true, 'status' => 'unknown', 'message' => 'Status tidak berubah'];
    }

    /**
     * Fetch live transaction status directly from Midtrans Status API
     */
    public function getTransactionStatus(string $orderId): array
    {
        $statusApiBaseUrl = $this->isProduction 
            ? 'https://api.midtrans.com/v2' 
            : 'https://api.sandbox.midtrans.com/v2';

        $url = $statusApiBaseUrl . '/' . rawurlencode($orderId) . '/status';
        $authHeader = 'Basic ' . base64_encode($this->serverKey . ':');

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: ' . $authHeader
            ],
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false || !empty($curlError)) {
            return [
                'success' => false,
                'message' => 'Gagal terhubung ke Midtrans API: ' . $curlError
            ];
        }

        $result = json_decode($response, true);
        if ($httpCode >= 400 || empty($result['status_code'])) {
            return [
                'success'   => false,
                'http_code' => $httpCode,
                'message'   => $result['status_message'] ?? 'Transaksi tidak ditemukan di Midtrans',
                'data'      => $result
            ];
        }

        return [
            'success'   => true,
            'http_code' => $httpCode,
            'data'      => $result
        ];
    }

    /**
     * Test API connection with configured Server Key
     */
    public function testApiConnection(): array
    {
        if (empty($this->serverKey)) {
            return ['success' => false, 'message' => 'Server Key Midtrans belum dikonfigurasi.'];
        }

        // Test request to Midtrans status endpoint for ping
        $statusApiBaseUrl = $this->isProduction 
            ? 'https://api.midtrans.com/v2' 
            : 'https://api.sandbox.midtrans.com/v2';

        $url = $statusApiBaseUrl . '/PING-TEST/status';
        $authHeader = 'Basic ' . base64_encode($this->serverKey . ':');

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: ' . $authHeader
            ],
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // 404 means Server Key is VALID and authenticated (order PING-TEST not found is expected)
        // 401 means Access Denied (invalid Server Key)
        if ($httpCode === 401) {
            return [
                'success' => false,
                'message' => 'Otentikasi Gagal! Server Key Midtrans tidak valid (401 Unauthorized).'
            ];
        }

        return [
            'success'        => true,
            'message'        => 'Koneksi Midtrans API Berhasil & Terverifikasi! (Mode: ' . ($this->isProduction ? 'Production' : 'Sandbox') . ')',
            'environment'    => $this->isProduction ? 'Production' : 'Sandbox',
            'merchant_id'    => $this->config['merchant_id'] ?? '-'
        ];
    }
}

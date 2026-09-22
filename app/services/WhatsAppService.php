<?php
namespace App\Services;

use App\Models\BusinessSetting;

/**
 * WhatsAppService
 * 
 * Service untuk mengirim pesan WhatsApp melalui gateway CicalengkaGO
 * URL Gateway Produksi: https://otp.cicago.store
 */
class WhatsAppService
{
    private string $gatewayUrl;
    private string $secretKey;
    private int    $timeout;
    private string $lastError = '';

    public function __construct()
    {
        // Default URL gateway resmi CicalengkaGO: https://otp.cicago.store
        $dbUrl = '';
        try {
            $dbUrl = BusinessSetting::get('whatsapp_gateway_url', '');
        } catch (\Throwable $e) {}

        if (empty($dbUrl) || str_contains($dbUrl, 'localhost:3005') || str_contains($dbUrl, '127.0.0.1:3005')) {
            $this->gatewayUrl = 'https://otp.cicago.store';
        } else {
            $this->gatewayUrl = rtrim($dbUrl, '/');
        }

        $secret = 'cicago_wa_secret_2024';
        try {
            $secret = BusinessSetting::get('whatsapp_gateway_secret', 'cicago_wa_secret_2024');
        } catch (\Throwable $e) {}

        $this->secretKey = $secret;
        $this->timeout = 10; // detik
    }

    public function getLastError(): string
    {
        return $this->lastError;
    }

    /**
     * Kirim OTP via WhatsApp
     * 
     * @param string $phone Nomor HP (format apa saja, otomatis dikonversi)
     * @param string $name  Nama penerima
     * @param string $otp   Kode OTP 6 digit
     * @return bool true jika berhasil
     */
    public function sendOtp(string $phone, string $name, string $otp): bool
    {
        return $this->post('/send-otp', [
            'phone' => $phone,
            'name'  => $name,
            'otp'   => $otp,
        ]);
    }

    /**
     * Kirim pesan bebas via WhatsApp
     * 
     * @param string $phone   Nomor HP tujuan
     * @param string $message Isi pesan (bisa menggunakan *bold*, _italic_)
     * @return bool true jika berhasil
     */
    public function sendMessage(string $phone, string $message): bool
    {
        return $this->post('/send-message', [
            'phone'   => $phone,
            'message' => $message,
        ]);
    }

    /**
     * Cek apakah gateway sedang aktif dan siap
     * 
     * @return bool
     */
    public function isReady(): bool
    {
        try {
            $result = $this->get('/status');
            return !empty($result['ready']) && $result['ready'] === true;
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Kirim notifikasi order masuk ke vendor
     */
    public function sendOrderNotification(string $phone, string $storeName, string $orderCode, float $amount): bool
    {
        $message  = "🛵 *Pesanan Baru Masuk!*\n\n";
        $message .= "Toko: *{$storeName}*\n";
        $message .= "Kode Order: *{$orderCode}*\n";
        $message .= "Total: *Rp " . number_format($amount, 0, ',', '.') . "*\n\n";
        $message .= "Segera konfirmasi pesanan di dashboard vendor.\n";
        $message .= "_CicalengkaGO_ 🏍️";

        return $this->sendMessage($phone, $message);
    }

    /**
     * Kirim update status order ke customer
     */
    public function sendOrderStatusUpdate(string $phone, string $customerName, string $orderCode, string $status): bool
    {
        $statusLabel = match($status) {
            'confirmed'  => '✅ Pesanan dikonfirmasi oleh toko',
            'processing' => '👨‍🍳 Pesanan sedang diproses',
            'on_the_way' => '🛵 Driver sedang mengantar pesanan',
            'delivered'  => '🎉 Pesanan telah sampai!',
            'canceled'   => '❌ Pesanan dibatalkan',
            default      => ucfirst($status),
        };

        $message  = "📦 *Update Pesanan CicalengkaGO*\n\n";
        $message .= "Halo, *{$customerName}*!\n\n";
        $message .= "Kode Order: *{$orderCode}*\n";
        $message .= "Status: {$statusLabel}\n\n";
        $message .= "_CicalengkaGO - Super App Cicalengka_ 🏍️";

        return $this->sendMessage($phone, $message);
    }

    /**
     * Kirim notifikasi pencairan dana (withdrawal) ke mitra
     */
    public function sendWithdrawalNotification(string $phone, string $name, float $amount, string $status): bool
    {
        if ($status === 'approved') {
            $message  = "💰 *Dana Berhasil Dicairkan!*\n\n";
            $message .= "Halo, *{$name}*!\n\n";
            $message .= "Penarikan saldo sebesar *Rp " . number_format($amount, 0, ',', '.') . "* telah berhasil diproses dan ditransfer.\n\n";
        } else {
            $message  = "❌ *Penarikan Dana Ditolak*\n\n";
            $message .= "Halo, *{$name}*!\n\n";
            $message .= "Penarikan saldo sebesar *Rp " . number_format($amount, 0, ',', '.') . "* ditolak. Saldo telah dikembalikan ke dompet Anda.\n\n";
        }
        $message .= "_CicalengkaGO_ 🏍️";

        return $this->sendMessage($phone, $message);
    }

    // ─── Internal HTTP Helpers ──────────────────────────────────────────────

    private function post(string $endpoint, array $data): bool
    {
        $urlsToTry = [
            $this->gatewayUrl . $endpoint
        ];

        // Fallback ke https://otp.cicago.store jika gatewayUrl berbeda
        if ($this->gatewayUrl !== 'https://otp.cicago.store') {
            $urlsToTry[] = 'https://otp.cicago.store' . $endpoint;
        }

        $body = json_encode($data);

        foreach ($urlsToTry as $url) {
            if (function_exists('curl_init')) {
                $ch = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_POST           => true,
                    CURLOPT_POSTFIELDS     => $body,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT        => $this->timeout,
                    CURLOPT_CONNECTTIMEOUT => 4,
                    CURLOPT_HTTPHEADER     => [
                        'Content-Type: application/json',
                        'X-WA-Secret: ' . $this->secretKey,
                        'Accept: application/json',
                    ],
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                ]);

                $response = curl_exec($ch);
                $curlErr  = curl_error($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($response !== false && $httpCode >= 200 && $httpCode < 500) {
                    $json = json_decode((string)$response, true);
                    if (!empty($json['success']) && $json['success'] === true) {
                        $this->lastError = '';
                        return true;
                    }
                    $this->lastError = $json['message'] ?? "Gateway HTTP {$httpCode}";
                    if (!empty($json)) {
                        return false;
                    }
                } else {
                    $this->lastError = "cURL error ke {$url}: " . ($curlErr ?: "HTTP {$httpCode}");
                    continue;
                }
            } else {
                $context = stream_context_create([
                    'http' => [
                        'method'        => 'POST',
                        'header'        => implode("\r\n", [
                            'Content-Type: application/json',
                            'X-WA-Secret: ' . $this->secretKey,
                            'Accept: application/json',
                        ]),
                        'content'       => $body,
                        'timeout'       => $this->timeout,
                        'ignore_errors' => true,
                    ],
                    'ssl' => [
                        'verify_peer'      => false,
                        'verify_peer_name' => false,
                    ]
                ]);

                $response = @file_get_contents($url, false, $context);
                if ($response === false) {
                    $this->lastError = "Gagal terhubung ke {$url}";
                    continue;
                }

                $json = json_decode($response, true);
                if (!empty($json['success']) && $json['success'] === true) {
                    $this->lastError = '';
                    return true;
                }

                $this->lastError = $json['message'] ?? 'Gateway merespon dengan error.';
                return false;
            }
        }

        return false;
    }

    private function get(string $endpoint): array
    {
        $urlsToTry = [
            $this->gatewayUrl . $endpoint
        ];

        if ($this->gatewayUrl !== 'https://otp.cicago.store') {
            $urlsToTry[] = 'https://otp.cicago.store' . $endpoint;
        }

        foreach ($urlsToTry as $url) {
            if (function_exists('curl_init')) {
                $ch = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT        => $this->timeout,
                    CURLOPT_CONNECTTIMEOUT => 4,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                ]);
                $response = curl_exec($ch);
                curl_close($ch);
                if ($response !== false) {
                    $json = json_decode((string)$response, true);
                    if (is_array($json)) return $json;
                }
            } else {
                $context = stream_context_create([
                    'http' => [
                        'method'        => 'GET',
                        'timeout'       => $this->timeout,
                        'ignore_errors' => true,
                    ],
                    'ssl' => [
                        'verify_peer'      => false,
                        'verify_peer_name' => false,
                    ]
                ]);
                $response = @file_get_contents($url, false, $context);
                if ($response !== false) {
                    return json_decode($response, true) ?? ['ready' => false];
                }
            }
        }

        return ['ready' => false];
    }
}

<?php
namespace App\Services;

use App\Models\BusinessSetting;

/**
 * WhatsAppService
 * 
 * Service untuk mengirim pesan WhatsApp melalui self-hosted gateway
 * (whatsapp-web.js berbasis Node.js yang berjalan di localhost:3005)
 */
class WhatsAppService
{
    private string $gatewayUrl;
    private string $secretKey;
    private int    $timeout;

    public function __construct()
    {
        $this->gatewayUrl = rtrim(
            BusinessSetting::get('whatsapp_gateway_url', 'http://localhost:3005'),
            '/'
        );
        $this->secretKey = BusinessSetting::get(
            'whatsapp_gateway_secret',
            'cicago_wa_secret_2024'
        );
        $this->timeout = 8; // detik
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
        $url  = $this->gatewayUrl . $endpoint;
        $body = json_encode($data);

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
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            error_log("[WhatsAppService] Gagal menghubungi gateway: {$url}");
            return false;
        }

        $json = json_decode($response, true);
        $ok   = !empty($json['success']) && $json['success'] === true;

        if (!$ok) {
            error_log("[WhatsAppService] Gateway error: " . ($json['message'] ?? 'Unknown'));
        }

        return $ok;
    }

    private function get(string $endpoint): array
    {
        $url = $this->gatewayUrl . $endpoint;

        $context = stream_context_create([
            'http' => [
                'method'        => 'GET',
                'timeout'       => $this->timeout,
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            return ['ready' => false];
        }

        return json_decode($response, true) ?? ['ready' => false];
    }
}

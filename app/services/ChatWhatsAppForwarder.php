<?php
namespace App\Services;

/**
 * ChatWhatsAppForwarder
 *
 * Mem-forward pesan chat in-app ke WhatsApp penerima.
 * Menggunakan WhatsAppService (gateway: https://otp.cicago.store).
 */
class ChatWhatsAppForwarder
{
    private WhatsAppService $wa;

    public function __construct()
    {
        $this->wa = new WhatsAppService();
    }

    /**
     * Forward pesan order chat ke WhatsApp penerima.
     *
     * @param array  $order      Row order dari getOrderChatDetails()
     * @param string $senderRole 'customer' | 'driver' | 'delivery_man' | 'vendor' | 'merchant' | 'admin'
     * @param string $senderName Nama pengirim
     * @param string $message    Isi pesan
     * @param string $receiverPhone Nomor WA penerima (08xxx / 62xxx)
     */
    public function forwardOrderChat(
        array  $order,
        string $senderRole,
        string $senderName,
        string $message,
        string $receiverPhone
    ): void {
        $phone = $this->normalizePhone($receiverPhone);
        if (empty($phone)) {
            error_log("[ChatWhatsAppForwarder] Order chat skip: receiverPhone kosong/invalid (raw: '{$receiverPhone}')");
            return;
        }

        $orderCode  = $order['order_code'] ?? '-';
        $storeName  = $order['store_name'] ?? 'Toko';

        // Label pengirim berdasarkan role
        $senderLabel = match(true) {
            in_array($senderRole, ['driver', 'delivery_man']) => "🛵 *Driver* ({$senderName})",
            in_array($senderRole, ['vendor', 'merchant'])     => "🏪 *Merchant* {$storeName}",
            $senderRole === 'admin'                           => "⚙️ *Admin CicalengkaGO*",
            default                                           => "👤 *Pelanggan* ({$senderName})",
        };

        $waMessage  = "💬 *Pesan Baru via CicalengkaGO*\n\n";
        $waMessage .= "Dari: {$senderLabel}\n";
        $waMessage .= "Pesanan: *#{$orderCode}*\n";
        if (!empty($storeName) && $senderRole !== 'vendor' && $senderRole !== 'merchant') {
            $waMessage .= "Toko: {$storeName}\n";
        }
        $waMessage .= "\n";
        $waMessage .= "💬 {$message}\n\n";
        $waMessage .= "_Balas pesan ini di aplikasi CicalengkaGO atau kunjungi market.cicago.store_";

        error_log("[ChatWhatsAppForwarder] Mengirim WA order #{$orderCode} ke {$phone} (dari: {$senderName})");
        $sent = $this->wa->sendMessage($phone, $waMessage);
        if ($sent) {
            error_log("[ChatWhatsAppForwarder] ✅ Berhasil forward chat order #{$orderCode} ke WA {$phone}");
        } else {
            error_log("[ChatWhatsAppForwarder] ❌ Gagal forward chat order #{$orderCode} ke WA {$phone}. Error: " . $this->wa->getLastError());
        }
    }

    /**
     * Forward pesan store chat (direct in-app chat antara pelanggan dan toko)
     * ke WhatsApp penerima.
     *
     * @param string $storeName    Nama toko
     * @param string $senderRole  'customer' | 'vendor' | 'merchant'
     * @param string $senderName  Nama pengirim
     * @param string $message     Isi pesan
     * @param string $receiverPhone Nomor WA penerima
     */
    public function forwardStoreChat(
        string $storeName,
        string $senderRole,
        string $senderName,
        string $message,
        string $receiverPhone
    ): void {
        $phone = $this->normalizePhone($receiverPhone);
        if (empty($phone)) {
            error_log("[ChatWhatsAppForwarder] Store chat skip: receiverPhone kosong/invalid (raw: '{$receiverPhone}')");
            return;
        }

        $isMerchant  = in_array($senderRole, ['vendor', 'merchant']);
        $senderLabel = $isMerchant
            ? "🏪 *{$storeName}*"
            : "👤 *{$senderName}* (Pelanggan)";

        $waMessage  = "💬 *Pesan Baru via CicalengkaGO*\n\n";
        $waMessage .= "Dari: {$senderLabel}\n";
        if (!$isMerchant) {
            $waMessage .= "Toko: {$storeName}\n";
        }
        $waMessage .= "\n";
        $waMessage .= "💬 {$message}\n\n";
        $waMessage .= "_Balas di aplikasi CicalengkaGO atau market.cicago.store_";

        error_log("[ChatWhatsAppForwarder] Mengirim WA toko '{$storeName}' ke {$phone} (dari: {$senderName})");
        $sent = $this->wa->sendMessage($phone, $waMessage);
        if ($sent) {
            error_log("[ChatWhatsAppForwarder] ✅ Berhasil forward chat toko '{$storeName}' ke WA {$phone}");
        } else {
            error_log("[ChatWhatsAppForwarder] ❌ Gagal forward chat toko '{$storeName}' ke WA {$phone}. Error: " . $this->wa->getLastError());
        }
    }

    /**
     * Normalisasi nomor HP ke format 62xxxxxxxxxx.
     * Mendukung format 08xxx, +62xxx, 62xxx, 8xxx.
     * Mengembalikan string kosong jika nomor tidak valid.
     */
    public function normalizePhone(string $phone): string
    {
        $phone = trim($phone);
        if (empty($phone)) {
            return '';
        }

        // Hapus semua karakter non-digit
        $digits = preg_replace('/\D/', '', $phone);
        if (empty($digits)) {
            return '';
        }

        // Tangani awalan
        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        } elseif (str_starts_with($digits, '62')) {
            if (str_starts_with($digits, '620')) {
                $digits = '62' . substr($digits, 3);
            }
        } else {
            $digits = '62' . $digits;
        }

        // Validasi panjang nomor HP Indonesia (10-16 digit)
        if (strlen($digits) < 10 || strlen($digits) > 16) {
            return '';
        }

        return $digits;
    }
}

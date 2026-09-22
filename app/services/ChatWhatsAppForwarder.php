<?php
namespace App\Services;

/**
 * ChatWhatsAppForwarder
 *
 * Mem-forward pesan chat in-app ke WhatsApp penerima.
 * Dipanggil secara non-blocking (fire-and-forget) setelah pesan disimpan ke DB,
 * sehingga tidak menghambat respons API ke user.
 *
 * Alur:
 *   User/Driver/Tenant kirim chat di app
 *       → disimpan ke tabel chats
 *       → ChatWhatsAppForwarder::forward() dipanggil
 *           → cek nomor WA penerima
 *           → format pesan dengan konteks order/toko
 *           → kirim ke WhatsApp gateway (localhost:3005/send-message)
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
     * @param string $receiverPhone Nomor WA penerima (62xxxxxxxxxx)
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
            return; // Tidak ada nomor WA → skip
        }

        $orderCode  = $order['order_code']  ?? '-';
        $storeName  = $order['store_name']  ?? 'Toko';

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

        $this->wa->sendMessage($phone, $waMessage);
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

        $this->wa->sendMessage($phone, $waMessage);
    }

    /**
     * Normalisasi nomor HP ke format 62xxxxxxxxxx.
     * Mengembalikan string kosong jika nomor tidak valid.
     */
    private function normalizePhone(string $phone): string
    {
        $phone = trim($phone);
        if (empty($phone)) {
            return '';
        }

        // Hapus semua karakter non-digit kecuali +
        $phone = preg_replace('/[^\d+]/', '', $phone);

        // Ganti awalan 0 dengan 62
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        // Hapus tanda +
        if (str_starts_with($phone, '+')) {
            $phone = substr($phone, 1);
        }

        // Tambahkan 62 jika belum ada
        if (!str_starts_with($phone, '62')) {
            $phone = '62' . $phone;
        }

        // Validasi panjang minimal (8 digit setelah 62)
        if (strlen($phone) < 10 || strlen($phone) > 15) {
            return '';
        }

        return $phone;
    }
}

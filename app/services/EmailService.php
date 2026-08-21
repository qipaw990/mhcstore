<?php
namespace App\Services;

use App\Core\Database;
use Exception;

class EmailService
{
    private static function getSetting(string $key, string $default = ''): string
    {
        try {
            $row = Database::fetchOne("SELECT value_text FROM business_settings WHERE key_name = ? LIMIT 1", [$key]);
            return $row['value_text'] ?? $default;
        } catch (Exception $e) {
            return $default;
        }
    }

    /**
     * Send Verification OTP Email
     */
    public static function sendOtpEmail(string $toEmail, string $userName, string $otpCode): bool
    {
        // Jangan kirim email nyata jika sedang dalam Demo Mode
        $otpMode = self::getSetting('otp_mode', 'real');
        if ($otpMode === 'demo') {
            error_log("[EMAIL SKIP] Mode OTP adalah DEMO. Email tidak dikirim ke {$toEmail}. Kode OTP: {$otpCode}");
            return true;
        }

        $subject = "[CicalengkaGO] Kode Verifikasi Keamanan: {$otpCode}";
        $plainText = self::buildOtpEmailPlainText($userName, $otpCode);
        $htmlContent = self::buildOtpEmailHtml($userName, $otpCode);

        return self::sendEmail($toEmail, $subject, $htmlContent, $plainText);
    }

    /**
     * Generic Email Sender with SMTP & Native Fallback (Multipart Alternative Anti-Spam)
     */
    public static function sendEmail(string $toEmail, string $subject, string $htmlBody, string $plainText = ''): bool
    {
        if (empty($plainText)) {
            $plainText = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $htmlBody));
            $plainText = trim(preg_replace("/[\r\n]+/", "\n", $plainText));
        }

        $smtpHost   = self::getSetting('smtp_host', 'smtp.gmail.com');
        $smtpPort   = (int)self::getSetting('smtp_port', '587');
        $smtpEmail  = self::getSetting('smtp_email', 'no-reply@cicalengkago.id');
        $smtpPass   = self::getSetting('smtp_password', '');
        $smtpEnc    = strtolower(self::getSetting('smtp_encryption', 'tls'));
        $senderName = self::getSetting('smtp_sender_name', 'CicalengkaGO Security');

        error_log("[EMAIL OUTBOUND] To: {$toEmail} | Subject: {$subject}");

        // Attempt Socket SMTP sending if credentials exist
        if (!empty($smtpHost) && !empty($smtpPass)) {
            try {
                return self::sendViaSmtpSocket($smtpHost, $smtpPort, $smtpEmail, $smtpPass, $smtpEnc, $senderName, $toEmail, $subject, $htmlBody, $plainText);
            } catch (Exception $e) {
                error_log("[SMTP ERROR] " . $e->getMessage() . " -> Falling back to mail()");
            }
        }

        // Native PHP mail() fallback with Multipart Alternative
        $boundary = "==_Multipart_Boundary_" . md5(uniqid(time()));
        $headers = [
            'MIME-Version: 1.0',
            "From: {$senderName} <{$smtpEmail}>",
            "Reply-To: {$senderName} <{$smtpEmail}>",
            'X-Mailer: PHP/' . phpversion(),
            'X-Priority: 1 (Highest)',
            'Importance: High',
            'Organization: CicalengkaGO Platform',
            "Content-Type: multipart/alternative; boundary=\"{$boundary}\""
        ];

        $body  = "--{$boundary}\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $body .= $plainText . "\r\n\r\n";

        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $body .= $htmlBody . "\r\n\r\n";
        $body .= "--{$boundary}--";

        return @mail($toEmail, $subject, $body, implode("\r\n", $headers));
    }

    /**
     * Test SMTP Email Gateway Connection
     */
    public static function testEmailGateway(string $targetEmail): array
    {
        $smtpHost   = self::getSetting('smtp_host', 'smtp.gmail.com');
        $smtpPort   = (int)self::getSetting('smtp_port', '587');
        $smtpEmail  = self::getSetting('smtp_email', 'no-reply@cicalengkago.id');
        $smtpPass   = self::getSetting('smtp_password', '');
        $smtpEnc    = strtolower(self::getSetting('smtp_encryption', 'tls'));
        $senderName = self::getSetting('smtp_sender_name', 'CicalengkaGO Security');

        if (empty($smtpHost) || empty($smtpEmail)) {
            return ['success' => false, 'message' => 'Host SMTP dan Email Pengirim belum dikonfigurasi.'];
        }

        $testSubject = "[CicalengkaGO] Tes Koneksi Gateway Email - " . date('H:i:s');
        $testText = "Halo,\n\nIni adalah email pengujian koneksi SMTP CicalengkaGO ({$smtpHost}:{$smtpPort}).\nKoneksi berhasil dan email siap digunakan.\n\nWaktu: " . date('Y-m-d H:i:s');
        $testBody = "
        <div style='font-family: Arial, sans-serif; padding: 25px; border: 1px solid #e2e8f0; border-radius: 12px; max-width: 500px;'>
            <h3 style='color: #EE2737; margin-top: 0;'>Koneksi Email Gateway Berhasil! 🎉</h3>
            <p style='color: #334155; line-height: 1.5;'>Email pengujian ini mengonfirmasi bahwa server SMTP <strong>{$smtpHost}:{$smtpPort}</strong> CicalengkaGO berfungsi dengan optimal dan aman.</p>
            <hr style='border: 0; border-top: 1px solid #e2e8f0; margin: 15px 0;'>
            <small style='color: #64748b;'>Waktu Pengujian: " . date('Y-m-d H:i:s') . "<br>CicalengkaGO Multi-Vendor Platform</small>
        </div>";

        try {
            if (!empty($smtpPass)) {
                $sent = self::sendViaSmtpSocket($smtpHost, $smtpPort, $smtpEmail, $smtpPass, $smtpEnc, $senderName, $targetEmail, $testSubject, $testBody, $testText);
            } else {
                $sent = self::sendEmail($targetEmail, $testSubject, $testBody, $testText);
            }

            if ($sent) {
                return [
                    'success' => true,
                    'message' => "Email pengujian berhasil dikirim ke {$targetEmail} via {$smtpHost}:{$smtpPort} ({$smtpEnc})!"
                ];
            } else {
                return ['success' => false, 'message' => 'Gagal mengirim email pengujian. Periksa log server.'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error SMTP: ' . $e->getMessage()];
        }
    }

    private static function sendViaSmtpSocket(
        string $host,
        int $port,
        string $username,
        string $password,
        string $encryption,
        string $senderName,
        string $toEmail,
        string $subject,
        string $htmlBody,
        string $plainText
    ): bool {
        $protocol = ($encryption === 'ssl') ? 'ssl://' : '';
        $timeout = 15;
        
        $socket = @fsockopen($protocol . $host, $port, $errno, $errstr, $timeout);
        if (!$socket) {
            throw new Exception("Tidak dapat terhubung ke SMTP host {$host}:{$port} ({$errstr})");
        }

        $read = function() use ($socket) {
            $response = '';
            while ($str = fgets($socket, 512)) {
                $response .= $str;
                if (substr($str, 3, 1) == ' ') break;
            }
            return $response;
        };

        $write = function($cmd) use ($socket) {
            fputs($socket, $cmd . "\r\n");
        };

        $read(); // Initial connection greeting

        $write("EHLO " . (gethostname() ?: 'cicalengkago.id'));
        $read();

        if ($encryption === 'tls' && $port !== 465) {
            $write("STARTTLS");
            $res = $read();
            if (strpos($res, '220') === false) {
                throw new Exception("STARTTLS tidak didukung oleh server SMTP");
            }
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT);
            $write("EHLO " . (gethostname() ?: 'cicalengkago.id'));
            $read();
        }

        if (!empty($username) && !empty($password)) {
            $write("AUTH LOGIN");
            $read();
            $write(base64_encode($username));
            $read();
            $write(base64_encode($password));
            $res = $read();
            if (strpos($res, '235') === false) {
                throw new Exception("Otentikasi SMTP Gagal! Username atau password SMTP salah.");
            }
        }

        $write("MAIL FROM: <{$username}>");
        $read();
        $write("RCPT TO: <{$toEmail}>");
        $read();
        $write("DATA");
        $read();

        $domain = substr(strrchr($username, "@"), 1) ?: 'cicalengkago.id';
        $messageId = "<otp." . time() . "." . bin2hex(random_bytes(4)) . "@" . $domain . ">";
        $boundary = "==_Multipart_Boundary_" . md5(uniqid(time()));

        // RFC compliant headers for maximum deliverability & anti-spam scoring
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "From: {$senderName} <{$username}>\r\n";
        $headers .= "Reply-To: {$senderName} <{$username}>\r\n";
        $headers .= "To: <{$toEmail}>\r\n";
        $headers .= "Subject: {$subject}\r\n";
        $headers .= "Date: " . date('r') . "\r\n";
        $headers .= "Message-ID: {$messageId}\r\n";
        $headers .= "Organization: CicalengkaGO Platform\r\n";
        $headers .= "X-Priority: 1 (Highest)\r\n";
        $headers .= "Priority: Urgent\r\n";
        $headers .= "Importance: High\r\n";
        $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";

        // Multipart Body (Both text/plain and text/html)
        $body  = "--{$boundary}\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $body .= $plainText . "\r\n\r\n";

        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $body .= $htmlBody . "\r\n\r\n";
        $body .= "--{$boundary}--";

        $write($headers . "\r\n" . $body . "\r\n.");
        $res = $read();

        $write("QUIT");
        fclose($socket);

        return (strpos($res, '250') !== false);
    }

    private static function buildOtpEmailPlainText(string $userName, string $otpCode): string
    {
        return "Halo {$userName},\n\n"
            . "Kode OTP Verifikasi CicalengkaGO Anda adalah:\n\n"
            . ">>> {$otpCode} <<<\n\n"
            . "Kode ini berlaku selama 10 menit.\n"
            . "Jangan pernah memberitahukan kode OTP ini kepada siapa pun.\n\n"
            . "Jika Anda tidak meminta kode ini, mohon abaikan email ini.\n\n"
            . "Salam,\n"
            . "Tim Keamanan CicalengkaGO\n"
            . "https://cicago.store";
    }

    private static function buildOtpEmailHtml(string $userName, string $otpCode): string
    {
        $year = date('Y');
        $siteUrl = 'https://cicago.store';

        return "<!DOCTYPE html>
<html lang='id'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Kode Verifikasi CicalengkaGO</title>
</head>
<body style='margin: 0; padding: 20px; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif; background-color: #f1f5f9; color: #334155;'>
    <table align='center' border='0' cellpadding='0' cellspacing='0' width='100%' style='max-width: 520px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.06); border: 1px solid #e2e8f0; margin: 0 auto;'>
        <!-- Header -->
        <tr>
            <td style='background: #111827; padding: 28px 24px; text-align: center; border-bottom: 4px solid #EE2737;'>
                <h1 style='margin: 0; font-size: 26px; font-weight: 800; color: #ffffff; letter-spacing: -0.5px;'>
                    Cicalengka<span style='color: #EE2737;'>GO</span>
                </h1>
                <p style='margin: 6px 0 0 0; font-size: 13px; color: #94a3b8; font-weight: 500;'>Otentikasi & Verifikasi Akun Aman</p>
            </td>
        </tr>

        <!-- Content -->
        <tr>
            <td style='padding: 32px 28px;'>
                <p style='margin: 0 0 16px 0; font-size: 16px; font-weight: 600; color: #0f172a;'>
                    Halo, " . htmlspecialchars($userName) . " 👋
                </p>
                <p style='margin: 0 0 24px 0; font-size: 14px; line-height: 1.6; color: #475569;'>
                    Kami menerima permintaan verifikasi untuk akun CicalengkaGO Anda. Gunakan kode <strong>One-Time Password (OTP)</strong> berikut:
                </p>

                <!-- OTP Code Box -->
                <div style='background-color: #fff1f2; border: 2px dashed #f43f5e; border-radius: 14px; padding: 22px; text-align: center; margin-bottom: 24px;'>
                    <div style='font-size: 40px; font-weight: 900; letter-spacing: 12px; color: #EE2737; font-family: Courier, monospace; margin-left: 12px;'>
                        {$otpCode}
                    </div>
                    <p style='margin: 10px 0 0 0; font-size: 12px; color: #e11d48; font-weight: 600;'>
                        ⏱️ Berlaku selama 10 menit
                    </p>
                </div>

                <div style='background-color: #f8fafc; border-radius: 10px; padding: 14px 16px; border-left: 4px solid #64748b; margin-bottom: 20px;'>
                    <p style='margin: 0; font-size: 12px; line-height: 1.5; color: #64748b;'>
                        🔒 <strong>Penting:</strong> Jangan berikan kode ini kepada siapa pun. Staf CicalengkaGO tidak pernah meminta kode OTP Anda.
                    </p>
                </div>

                <p style='margin: 0; font-size: 13px; line-height: 1.5; color: #64748b;'>
                    Jika Anda tidak merasa melakukan permintaan ini, abaikan pesan ini atau segera amankan kata sandi akun Anda.
                </p>
            </td>
        </tr>

        <!-- Footer -->
        <tr>
            <td style='background-color: #f8fafc; padding: 20px 24px; text-align: center; border-top: 1px solid #e2e8f0;'>
                <p style='margin: 0 0 6px 0; font-size: 12px; font-weight: 600; color: #64748b;'>
                    CicalengkaGO • Super App Cicalengka
                </p>
                <p style='margin: 0; font-size: 11px; color: #94a3b8;'>
                    &copy; {$year} <a href='{$siteUrl}' style='color: #EE2737; text-decoration: none;'>CicalengkaGO</a>. Hak Cipta Dilindungi.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>";
    }
}

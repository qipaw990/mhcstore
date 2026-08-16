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
        $subject = "Kode Verifikasi Login CicalengkaGO: {$otpCode}";
        $htmlContent = self::buildOtpEmailHtml($userName, $otpCode);
        return self::sendEmail($toEmail, $subject, $htmlContent);
    }

    /**
     * Generic Email Sender with SMTP & Native Fallback
     */
    public static function sendEmail(string $toEmail, string $subject, string $htmlBody): bool
    {
        $smtpHost   = self::getSetting('smtp_host', 'smtp.gmail.com');
        $smtpPort   = (int)self::getSetting('smtp_port', '587');
        $smtpEmail  = self::getSetting('smtp_email', 'no-reply@cicalengkago.id');
        $smtpPass   = self::getSetting('smtp_password', '');
        $smtpEnc    = strtolower(self::getSetting('smtp_encryption', 'tls'));
        $senderName = self::getSetting('smtp_sender_name', 'CicalengkaGO Auth');

        error_log("[EMAIL OUTBOUND] To: {$toEmail} | Subject: {$subject}");

        // Attempt Socket SMTP sending if credentials exist
        if (!empty($smtpHost) && !empty($smtpPass)) {
            try {
                return self::sendViaSmtpSocket($smtpHost, $smtpPort, $smtpEmail, $smtpPass, $smtpEnc, $senderName, $toEmail, $subject, $htmlBody);
            } catch (Exception $e) {
                error_log("[SMTP ERROR] " . $e->getMessage() . " -> Falling back to mail()");
            }
        }

        // Native PHP mail() fallback
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            "From: {$senderName} <{$smtpEmail}>",
            "Reply-To: {$smtpEmail}",
            'X-Mailer: PHP/' . phpversion()
        ];

        return @mail($toEmail, $subject, $htmlBody, implode("\r\n", $headers));
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
        $senderName = self::getSetting('smtp_sender_name', 'CicalengkaGO Auth');

        if (empty($smtpHost) || empty($smtpEmail)) {
            return ['success' => false, 'message' => 'Host SMTP dan Email Pengirim belum dikonfigurasi.'];
        }

        $testSubject = "Tes Koneksi Gateway Email CicalengkaGO - " . date('H:i:s');
        $testBody = "
        <div style='font-family:sans-serif; padding:20px; border:1px solid #e2e8f0; border-radius:12px;'>
            <h3 style='color:#EE2737;'>Koneksi Email Gateway Berhasil! 🎉</h3>
            <p>Email pengujian ini mengonfirmasi bahwa server SMTP <strong>{$smtpHost}:{$smtpPort}</strong> CicalengkaGO berfungsi dengan normal.</p>
            <small style='color:#64748b;'>Waktu Pengujian: " . date('Y-m-d H:i:s') . "</small>
        </div>";

        try {
            if (!empty($smtpPass)) {
                $sent = self::sendViaSmtpSocket($smtpHost, $smtpPort, $smtpEmail, $smtpPass, $smtpEnc, $senderName, $targetEmail, $testSubject, $testBody);
            } else {
                $sent = self::sendEmail($targetEmail, $testSubject, $testBody);
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
        string $htmlBody
    ): bool {
        $protocol = ($encryption === 'ssl') ? 'ssl://' : '';
        $timeout = 10;
        
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

        $write("EHLO " . gethostname());
        $read();

        if ($encryption === 'tls' && $port !== 465) {
            $write("STARTTLS");
            $res = $read();
            if (strpos($res, '220') === false) {
                throw new Exception("STARTTLS tidak didukung oleh server SMTP");
            }
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT);
            $write("EHLO " . gethostname());
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

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: {$senderName} <{$username}>\r\n";
        $headers .= "To: <{$toEmail}>\r\n";
        $headers .= "Subject: {$subject}\r\n";
        $headers .= "Date: " . date('r') . "\r\n";
        $headers .= "Message-ID: {$messageId}\r\n";
        $headers .= "Auto-Submitted: auto-generated\r\n";
        $headers .= "X-Auto-Response-Suppress: All\r\n";

        $write($headers . "\r\n" . $htmlBody . "\r\n.");
        $res = $read();

        $write("QUIT");
        fclose($socket);

        return (strpos($res, '250') !== false);
    }

    private static function buildOtpEmailHtml(string $userName, string $otpCode): string
    {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f6f9; margin: 0; padding: 20px; }
                .email-card { max-width: 520px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.08); border: 1px solid #e2e8f0; }
                .email-header { background: linear-gradient(135deg, #101820 0%, #1e293b 100%); padding: 30px; text-align: center; color: #ffffff; border-bottom: 4px solid #EE2737; }
                .email-body { padding: 32px 28px; color: #334155; }
                .otp-box { background: #f8fafc; border: 2px dashed #EE2737; border-radius: 14px; padding: 20px; text-align: center; margin: 24px 0; }
                .otp-code { font-size: 38px; font-weight: 800; letter-spacing: 10px; color: #EE2737; margin: 0; font-family: monospace; }
                .email-footer { background: #f8fafc; padding: 18px; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0; }
            </style>
        </head>
        <body>
            <div class='email-card'>
                <div class='email-header'>
                    <h2 style='margin:0; font-weight:800; font-size:24px;'>Cicalengka<span style='color:#EE2737;'>GO</span></h2>
                    <p style='margin:5px 0 0 0; font-size:13px; opacity:0.8;'>Super App On-Demand Cicalengka</p>
                </div>
                <div class='email-body'>
                    <h3 style='margin-top:0; color:#0f172a;'>Halo, " . htmlspecialchars($userName) . "! 👋</h3>
                    <p>Anda sedang melakukan proses masuk/verifikasi akun di <strong>CicalengkaGO</strong>. Gunakan kode OTP di bawah ini untuk menyelesaikan otentikasi:</p>
                    
                    <div class='otp-box'>
                        <div class='otp-code'>{$otpCode}</div>
                        <small style='color:#64748b; margin-top:8px; display:block;'>Kode ini berlaku selama <strong>10 menit</strong>.</small>
                    </div>

                    <p style='font-size:13px; color:#64748b;'>⚠️ Jangan berikan kode OTP ini kepada siapa pun, termasuk pihak CicalengkaGO.</p>
                </div>
                <div class='email-footer'>
                    &copy; " . date('Y') . " CicalengkaGO Platform. Hak Cipta Dilindungi.
                </div>
            </div>
        </body>
        </html>";
    }
}

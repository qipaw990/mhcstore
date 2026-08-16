<?php
namespace App\Services;

class EmailService
{
    /**
     * Send Verification OTP Email
     */
    public static function sendOtpEmail(string $toEmail, string $userName, string $otpCode): bool
    {
        $subject = "Kode Verifikasi Login CicalengkaGO: {$otpCode}";

        $htmlContent = "
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
        </html>
        ";

        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: CicalengkaGO Auth <no-reply@cicalengkago.id>',
            'Reply-To: support@cicalengkago.id',
            'X-Mailer: PHP/' . phpversion()
        ];

        // Send via PHP mail()
        @mail($toEmail, $subject, $htmlContent, implode("\r\n", $headers));

        // Log OTP locally for audit / dev debugging
        error_log("[OTP VERIFICATION] Email: {$toEmail} | OTP Code: {$otpCode}");

        return true;
    }
}

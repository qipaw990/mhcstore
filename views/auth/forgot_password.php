<form action="<?= $baseUrl ?>/forgot-password" method="POST" style="margin-bottom: 20px;">
    
    <div style="text-align: center; margin-bottom: 20px;">
        <div style="width: 50px; height: 50px; background: #FEE2E2; color: #EE2737; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 10px;">
            <i class="bi bi-key-fill" style="font-size: 24px;"></i>
        </div>
        <h6 style="font-size: 15px; font-weight: 800; color: #0F172A; margin-bottom: 4px;">Lupa Kata Sandi?</h6>
        <p style="font-size: 11px; color: #64748B; margin: 0; line-height: 1.4;">
            Masukkan nomor WhatsApp atau Email terdaftar Anda. Kami akan mengirimkan 6 digit kode OTP verifikasi.
        </p>
    </div>

    <!-- Email / Nomor HP -->
    <div style="margin-bottom: 20px;">
        <label for="username" style="display: block; font-size: 11.5px; font-weight: 700; color: #0F172A; margin-bottom: 6px;">Email atau Nomor WhatsApp</label>
        <div style="display: flex; align-items: center; background: #F8FAFC; border: 1.5px solid #E2E8F0; border-radius: 12px; overflow: hidden; transition: border-color 0.15s;" id="input-wrapper">
            <span style="padding: 0 12px; color: #94A3B8; flex-shrink: 0; display: flex; align-items: center;">
                <i class="bi bi-whatsapp" style="font-size: 14px; color: #25D366;"></i>
            </span>
            <input type="text" name="username" id="username"
                   style="flex: 1; border: none; background: transparent; padding: 10px 12px 10px 0; font-size: 12px; color: #0F172A; outline: none;"
                   placeholder="08xxxxxxxxxx atau email@domain.com" required
                   onfocus="document.getElementById('input-wrapper').style.borderColor='#EE2737'"
                   onblur="document.getElementById('input-wrapper').style.borderColor='#E2E8F0'">
        </div>
    </div>

    <!-- Tombol Kirim Kode OTP -->
    <button type="submit" style="width: 100%; background: linear-gradient(135deg, #EE2737 0%, #C61524 100%); color: #FFFFFF; border: none; border-radius: 9999px; padding: 12px 16px; font-size: 13px; font-weight: 700; letter-spacing: -0.2px; box-shadow: 0 4px 14px rgba(238, 39, 55, 0.3); cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px;">
        <i class="bi bi-send-fill" style="font-size: 14px;"></i> Kirim Kode OTP Reset
    </button>
</form>

<!-- Link ke Login -->
<div style="text-align: center; margin-bottom: 8px;">
    <span style="font-size: 11.5px; color: #64748B;">Ingat kata sandi Anda?</span>
    <a href="<?= $baseUrl ?>/login" style="font-size: 11.5px; font-weight: 700; color: #EE2737; text-decoration: none; margin-left: 4px;">Masuk</a>
</div>

<div style="text-align: center; margin-top: 12px; padding-top: 10px; border-top: 1px dashed #E2E8F0;">
    <a href="<?= $baseUrl ?>/" style="font-size: 11px; font-weight: 600; color: #64748B; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
        <i class="bi bi-house-door" style="font-size: 12px;"></i> Kembali ke Beranda Utama
    </a>
</div>

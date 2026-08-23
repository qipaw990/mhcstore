<form action="<?= $baseUrl ?>/register" method="POST" style="margin-bottom: 20px;">

    <!-- Nama Lengkap -->
    <div style="margin-bottom: 14px;">
        <label for="reg_name" style="display: block; font-size: 11.5px; font-weight: 700; color: #0F172A; margin-bottom: 6px;">Nama Lengkap</label>
        <div style="display: flex; align-items: center; background: #F8FAFC; border: 1.5px solid #E2E8F0; border-radius: 12px; overflow: hidden;" id="wrap-name">
            <span style="padding: 0 12px; color: #94A3B8; flex-shrink: 0; display: flex; align-items: center;">
                <i class="bi bi-person-fill" style="font-size: 14px;"></i>
            </span>
            <input type="text" name="name" id="reg_name"
                   style="flex: 1; border: none; background: transparent; padding: 10px 12px 10px 0; font-size: 12px; color: #0F172A; outline: none;"
                   placeholder="Masukkan nama lengkap" required
                   onfocus="document.getElementById('wrap-name').style.borderColor='#EE2737'"
                   onblur="document.getElementById('wrap-name').style.borderColor='#E2E8F0'">
        </div>
    </div>

    <!-- Nomor WhatsApp / HP -->
    <div style="margin-bottom: 14px;">
        <label for="reg_phone" style="display: block; font-size: 11.5px; font-weight: 700; color: #0F172A; margin-bottom: 6px;">Nomor WhatsApp / HP</label>
        <div style="display: flex; align-items: center; background: #F8FAFC; border: 1.5px solid #E2E8F0; border-radius: 12px; overflow: hidden;" id="wrap-phone">
            <span style="padding: 0 12px; color: #25D366; flex-shrink: 0; display: flex; align-items: center;">
                <i class="bi bi-whatsapp" style="font-size: 14px;"></i>
            </span>
            <input type="text" name="phone" id="reg_phone"
                   style="flex: 1; border: none; background: transparent; padding: 10px 12px 10px 0; font-size: 12px; color: #0F172A; outline: none;"
                   placeholder="08xxxxxxxxxx" required
                   onfocus="document.getElementById('wrap-phone').style.borderColor='#EE2737'"
                   onblur="document.getElementById('wrap-phone').style.borderColor='#E2E8F0'">
        </div>
    </div>

    <!-- Email -->
    <div style="margin-bottom: 14px;">
        <label for="reg_email" style="display: block; font-size: 11.5px; font-weight: 700; color: #0F172A; margin-bottom: 6px;">Email</label>
        <div style="display: flex; align-items: center; background: #F8FAFC; border: 1.5px solid #E2E8F0; border-radius: 12px; overflow: hidden;" id="wrap-email">
            <span style="padding: 0 12px; color: #94A3B8; flex-shrink: 0; display: flex; align-items: center;">
                <i class="bi bi-envelope-fill" style="font-size: 14px;"></i>
            </span>
            <input type="email" name="email" id="reg_email"
                   style="flex: 1; border: none; background: transparent; padding: 10px 12px 10px 0; font-size: 12px; color: #0F172A; outline: none;"
                   placeholder="nama@email.com" required
                   onfocus="document.getElementById('wrap-email').style.borderColor='#EE2737'"
                   onblur="document.getElementById('wrap-email').style.borderColor='#E2E8F0'">
        </div>
    </div>

    <!-- Kata Sandi -->
    <div style="margin-bottom: 18px;">
        <label for="reg_password" style="display: block; font-size: 11.5px; font-weight: 700; color: #0F172A; margin-bottom: 6px;">Kata Sandi</label>
        <div style="display: flex; align-items: center; background: #F8FAFC; border: 1.5px solid #E2E8F0; border-radius: 12px; overflow: hidden;" id="wrap-pass">
            <span style="padding: 0 12px; color: #94A3B8; flex-shrink: 0; display: flex; align-items: center;">
                <i class="bi bi-lock-fill" style="font-size: 14px;"></i>
            </span>
            <input type="password" name="password" id="reg_password"
                   style="flex: 1; border: none; background: transparent; padding: 10px 0; font-size: 12px; color: #0F172A; outline: none; width: 0;"
                   placeholder="Minimal 6 karakter" required
                   onfocus="document.getElementById('wrap-pass').style.borderColor='#EE2737'"
                   onblur="document.getElementById('wrap-pass').style.borderColor='#E2E8F0'">
            <button type="button" onclick="toggleRegPass()" style="padding: 0 14px; background: transparent; border: none; color: #94A3B8; cursor: pointer; display: flex; align-items: center; flex-shrink: 0;">
                <i id="reg-pass-icon" class="bi bi-eye" style="font-size: 14px;"></i>
            </button>
        </div>
        <div style="font-size: 10px; color: #94A3B8; margin-top: 4px; padding-left: 2px;">Gunakan minimal 6 karakter.</div>
    </div>



    <!-- Tombol Daftar -->
    <button type="submit" style="width: 100%; background: linear-gradient(135deg, #EE2737 0%, #C61524 100%); color: #FFFFFF; border: none; border-radius: 9999px; padding: 12px 16px; font-size: 13px; font-weight: 700; letter-spacing: -0.2px; box-shadow: 0 4px 14px rgba(238, 39, 55, 0.3); cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px;">
        <i class="bi bi-person-plus-fill" style="font-size: 14px;"></i> Daftar Sekarang
    </button>
</form>

<!-- Link ke Login & Beranda -->
<div style="text-align: center; margin-bottom: 8px;">
    <span style="font-size: 11.5px; color: #64748B;">Sudah memiliki akun?</span>
    <a href="<?= $baseUrl ?>/login" style="font-size: 11.5px; font-weight: 700; color: #EE2737; text-decoration: none; margin-left: 4px;">Masuk</a>
</div>

<div style="text-align: center; margin-top: 12px; padding-top: 10px; border-top: 1px dashed #E2E8F0;">
    <a href="<?= $baseUrl ?>/" style="font-size: 11px; font-weight: 600; color: #64748B; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
        <i class="bi bi-house-door" style="font-size: 12px;"></i> Kembali ke Beranda Utama
    </a>
</div>

<script>
function toggleRegPass() {
    const input = document.getElementById('reg_password');
    const icon  = document.getElementById('reg-pass-icon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}
</script>

<form action="<?= $baseUrl ?>/reset-password" method="POST" style="margin-bottom: 20px;">
    
    <div style="text-align: center; margin-bottom: 20px;">
        <div style="width: 50px; height: 50px; background: #DCFCE7; color: #16A34A; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 10px;">
            <i class="bi bi-shield-lock-fill" style="font-size: 24px;"></i>
        </div>
        <h6 style="font-size: 15px; font-weight: 800; color: #0F172A; margin-bottom: 4px;">Buat Kata Sandi Baru</h6>
        <p style="font-size: 11px; color: #64748B; margin: 0; line-height: 1.4;">
            OTP Berhasil Diverifikasi. Silakan tentukan kata sandi baru untuk akun Anda.
        </p>
    </div>

    <!-- Kata Sandi Baru -->
    <div style="margin-bottom: 14px;">
        <label for="password" style="display: block; font-size: 11.5px; font-weight: 700; color: #0F172A; margin-bottom: 6px;">Kata Sandi Baru</label>
        <div style="display: flex; align-items: center; background: #F8FAFC; border: 1.5px solid #E2E8F0; border-radius: 12px; overflow: hidden;" id="pass1-wrapper">
            <span style="padding: 0 12px; color: #94A3B8; flex-shrink: 0; display: flex; align-items: center;">
                <i class="bi bi-lock-fill" style="font-size: 14px;"></i>
            </span>
            <input type="password" name="password" id="password"
                   style="flex: 1; border: none; background: transparent; padding: 10px 0; font-size: 12px; color: #0F172A; outline: none; width: 0;"
                   placeholder="Minimal 6 karakter" required
                   onfocus="document.getElementById('pass1-wrapper').style.borderColor='#EE2737'"
                   onblur="document.getElementById('pass1-wrapper').style.borderColor='#E2E8F0'">
            <button type="button" onclick="togglePass('password', 'pass1-icon')" style="padding: 0 14px; background: transparent; border: none; color: #94A3B8; cursor: pointer; display: flex; align-items: center; flex-shrink: 0;">
                <i id="pass1-icon" class="bi bi-eye" style="font-size: 14px;"></i>
            </button>
        </div>
    </div>

    <!-- Konfirmasi Kata Sandi Baru -->
    <div style="margin-bottom: 20px;">
        <label for="confirm_password" style="display: block; font-size: 11.5px; font-weight: 700; color: #0F172A; margin-bottom: 6px;">Ulangi Kata Sandi Baru</label>
        <div style="display: flex; align-items: center; background: #F8FAFC; border: 1.5px solid #E2E8F0; border-radius: 12px; overflow: hidden;" id="pass2-wrapper">
            <span style="padding: 0 12px; color: #94A3B8; flex-shrink: 0; display: flex; align-items: center;">
                <i class="bi bi-shield-check" style="font-size: 14px;"></i>
            </span>
            <input type="password" name="confirm_password" id="confirm_password"
                   style="flex: 1; border: none; background: transparent; padding: 10px 0; font-size: 12px; color: #0F172A; outline: none; width: 0;"
                   placeholder="Ketik ulang kata sandi baru" required
                   onfocus="document.getElementById('pass2-wrapper').style.borderColor='#EE2737'"
                   onblur="document.getElementById('pass2-wrapper').style.borderColor='#E2E8F0'">
            <button type="button" onclick="togglePass('confirm_password', 'pass2-icon')" style="padding: 0 14px; background: transparent; border: none; color: #94A3B8; cursor: pointer; display: flex; align-items: center; flex-shrink: 0;">
                <i id="pass2-icon" class="bi bi-eye" style="font-size: 14px;"></i>
            </button>
        </div>
    </div>

    <!-- Tombol Simpan Kata Sandi Baru -->
    <button type="submit" style="width: 100%; background: linear-gradient(135deg, #16A34A 0%, #15803D 100%); color: #FFFFFF; border: none; border-radius: 9999px; padding: 12px 16px; font-size: 13px; font-weight: 700; letter-spacing: -0.2px; box-shadow: 0 4px 14px rgba(22, 163, 74, 0.3); cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px;">
        <i class="bi bi-check-circle-fill" style="font-size: 14px;"></i> Simpan Kata Sandi Baru
    </button>
</form>

<script>
function togglePass(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(iconId);
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}
</script>

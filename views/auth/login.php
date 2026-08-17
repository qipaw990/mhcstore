<form action="<?= $baseUrl ?>/login" method="POST" style="margin-bottom: 20px;">

    <!-- Email / Nomor HP -->
    <div style="margin-bottom: 14px;">
        <label for="username" style="display: block; font-size: 11.5px; font-weight: 700; color: #0F172A; margin-bottom: 6px;">Email / Nomor HP</label>
        <div style="display: flex; align-items: center; background: #F8FAFC; border: 1.5px solid #E2E8F0; border-radius: 12px; overflow: hidden; transition: border-color 0.15s;">
            <span style="padding: 0 12px; color: #94A3B8; flex-shrink: 0; display: flex; align-items: center;">
                <i class="bi bi-person-fill" style="font-size: 14px;"></i>
            </span>
            <input type="text" name="username" id="username"
                   style="flex: 1; border: none; background: transparent; padding: 10px 12px 10px 0; font-size: 12px; color: #0F172A; outline: none;"
                   placeholder="Masukkan email atau nomor HP" required
                   onfocus="this.parentElement.style.borderColor='#EE2737'"
                   onblur="this.parentElement.style.borderColor='#E2E8F0'">
        </div>
    </div>

    <!-- Kata Sandi -->
    <div style="margin-bottom: 20px;">
        <label for="password" style="display: block; font-size: 11.5px; font-weight: 700; color: #0F172A; margin-bottom: 6px;">Kata Sandi</label>
        <div style="display: flex; align-items: center; background: #F8FAFC; border: 1.5px solid #E2E8F0; border-radius: 12px; overflow: hidden; transition: border-color 0.15s;" id="pass-wrapper">
            <span style="padding: 0 12px; color: #94A3B8; flex-shrink: 0; display: flex; align-items: center;">
                <i class="bi bi-lock-fill" style="font-size: 14px;"></i>
            </span>
            <input type="password" name="password" id="password"
                   style="flex: 1; border: none; background: transparent; padding: 10px 0; font-size: 12px; color: #0F172A; outline: none; width: 0;"
                   placeholder="Masukkan kata sandi" required
                   onfocus="document.getElementById('pass-wrapper').style.borderColor='#EE2737'"
                   onblur="document.getElementById('pass-wrapper').style.borderColor='#E2E8F0'">
            <button type="button" onclick="togglePass()" style="padding: 0 14px; background: transparent; border: none; color: #94A3B8; cursor: pointer; display: flex; align-items: center; flex-shrink: 0;">
                <i id="pass-icon" class="bi bi-eye" style="font-size: 14px;"></i>
            </button>
        </div>
    </div>

    <!-- Tombol Masuk -->
    <button type="submit" style="width: 100%; background: linear-gradient(135deg, #EE2737 0%, #C61524 100%); color: #FFFFFF; border: none; border-radius: 9999px; padding: 12px 16px; font-size: 13px; font-weight: 700; letter-spacing: -0.2px; box-shadow: 0 4px 14px rgba(238, 39, 55, 0.3); cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px;">
        <i class="bi bi-box-arrow-in-right" style="font-size: 14px;"></i> Masuk Sekarang
    </button>
</form>

<!-- Daftar Baru -->
<div style="text-align: center; margin-bottom: 4px;">
    <span style="font-size: 11.5px; color: #64748B;">Belum punya akun?</span>
    <a href="<?= $baseUrl ?>/register" style="font-size: 11.5px; font-weight: 700; color: #EE2737; text-decoration: none; margin-left: 4px;">Daftar Baru</a>
</div>

<script>
function togglePass() {
    const input = document.getElementById('password');
    const icon  = document.getElementById('pass-icon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}
</script>

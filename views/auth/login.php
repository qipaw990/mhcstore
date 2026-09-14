<form action="<?= $baseUrl ?>/login" method="POST" style="margin-bottom: 20px;">

    <!-- Email / Nomor HP -->
    <div style="margin-bottom: 14px;">
        <label for="username" style="display: block; font-size: 11.5px; font-weight: 700; color: #0F172A; margin-bottom: 6px;">Email / Nomor HP</label>
        <div style="display: flex; align-items: center; background: #F8FAFC; border: 1.5px solid #E2E8F0; border-radius: 12px; overflow: hidden; transition: border-color 0.15s;" id="username-wrapper">
            <span style="padding: 0 12px; color: #94A3B8; flex-shrink: 0; display: flex; align-items: center;">
                <i class="bi bi-person-fill" style="font-size: 14px;"></i>
            </span>
            <input type="text" name="username" id="username"
                   style="flex: 1; border: none; background: transparent; padding: 10px 12px 10px 0; font-size: 12px; color: #0F172A; outline: none;"
                   placeholder="Masukkan email atau nomor HP" required
                   onfocus="document.getElementById('username-wrapper').style.borderColor='#090A0F'"
                   onblur="document.getElementById('username-wrapper').style.borderColor='#E2E8F0'">
        </div>
    </div>

    <!-- Kata Sandi -->
    <div style="margin-bottom: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
            <label for="password" style="font-size: 11.5px; font-weight: 700; color: #0F172A; margin: 0;">Kata Sandi</label>
            <a href="<?= $baseUrl ?>/forgot-password" style="font-size: 11px; font-weight: 600; color: #475569; text-decoration: none;">Lupa Sandi?</a>
        </div>
        <div style="display: flex; align-items: center; background: #F8FAFC; border: 1.5px solid #E2E8F0; border-radius: 12px; overflow: hidden; transition: border-color 0.15s;" id="pass-wrapper">
            <span style="padding: 0 12px; color: #94A3B8; flex-shrink: 0; display: flex; align-items: center;">
                <i class="bi bi-lock-fill" style="font-size: 14px;"></i>
            </span>
            <input type="password" name="password" id="password"
                   style="flex: 1; border: none; background: transparent; padding: 10px 0; font-size: 12px; color: #0F172A; outline: none; width: 0;"
                   placeholder="Masukkan kata sandi" required
                   onfocus="document.getElementById('pass-wrapper').style.borderColor='#090A0F'"
                   onblur="document.getElementById('pass-wrapper').style.borderColor='#E2E8F0'">
            <button type="button" onclick="togglePass()" style="padding: 0 14px; background: transparent; border: none; color: #94A3B8; cursor: pointer; display: flex; align-items: center; flex-shrink: 0;">
                <i id="pass-icon" class="bi bi-eye" style="font-size: 14px;"></i>
            </button>
        </div>
    </div>

    <!-- Kode Keamanan / Captcha -->
    <div style="margin-bottom: 20px;">
        <label for="captcha" style="display: block; font-size: 11.5px; font-weight: 700; color: #0F172A; margin-bottom: 6px;">Kode Keamanan (Captcha)</label>
        <div style="display: flex; gap: 8px; align-items: center;">
            <!-- Box Kode Captcha -->
            <div style="background: linear-gradient(135deg, #18181B 0%, #090A0F 100%); border-radius: 12px; padding: 8px 14px; display: flex; align-items: center; justify-content: center; gap: 8px; border: 1.5px solid #27272A; user-select: none;">
                <span id="captcha-code" style="font-family: 'Courier New', monospace; font-size: 18px; font-weight: 900; letter-spacing: 4px; color: #F8FAFC; text-shadow: 0 0 10px rgba(255, 255, 255, 0.3);"><?= htmlspecialchars($captcha ?? ($_SESSION['login_captcha'] ?? '1234')) ?></span>
                <button type="button" onclick="refreshCaptcha()" style="background: transparent; border: none; color: #94A3B8; cursor: pointer; padding: 2px; display: flex; align-items: center; transition: color 0.2s;" title="Acak Ulang Kode Captcha" onmouseover="this.style.color='#FFFFFF'" onmouseout="this.style.color='#94A3B8'">
                    <i class="bi bi-arrow-clockwise" style="font-size: 15px;"></i>
                </button>
            </div>

            <!-- Input Kode Captcha -->
            <div style="flex: 1; display: flex; align-items: center; background: #F8FAFC; border: 1.5px solid #E2E8F0; border-radius: 12px; overflow: hidden; transition: border-color 0.15s;" id="captcha-wrapper">
                <span style="padding: 0 10px; color: #94A3B8; flex-shrink: 0; display: flex; align-items: center;">
                    <i class="bi bi-shield-check" style="font-size: 14px;"></i>
                </span>
                <input type="text" name="captcha" id="captcha" maxlength="4" autocomplete="off"
                       style="flex: 1; border: none; background: transparent; padding: 10px 10px 10px 0; font-size: 13px; font-weight: 700; color: #0F172A; outline: none; letter-spacing: 2px;"
                       required
                       onfocus="document.getElementById('captcha-wrapper').style.borderColor='#090A0F'"
                       onblur="document.getElementById('captcha-wrapper').style.borderColor='#E2E8F0'">
            </div>
        </div>
    </div>

    <!-- Tombol Masuk -->
    <button type="submit" style="width: 100%; background: #090A0F; color: #FFFFFF; border: none; border-radius: 12px; padding: 12px 16px; font-size: 13px; font-weight: 700; letter-spacing: -0.2px; box-shadow: 0 4px 14px rgba(9, 10, 15, 0.25); cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; transition: all 0.2s;" onmouseover="this.style.background='#18181B'" onmouseout="this.style.background='#090A0F'">
        <i class="bi bi-box-arrow-in-right" style="font-size: 14px;"></i> Masuk ke Portal
    </button>
</form>

<!-- Informasi Mitra Toko & Aplikasi Mobile -->
<div style="text-align: center; margin-bottom: 12px; padding-top: 10px; border-top: 1px dashed #E2E8F0;">
    <a href="<?= $baseUrl ?>/register-merchant" style="font-size: 11.5px; font-weight: 700; color: #2563EB; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
        <i class="bi bi-shop"></i> Buka Usaha? Daftar Sebagai Mitra Toko
    </a>
</div>

<div style="text-align: center; background: #F8FAFC; border-radius: 10px; padding: 8px 12px; border: 1px solid #E2E8F0;">
    <small style="font-size: 10.5px; color: #64748B; display: flex; align-items: center; justify-content: center; gap: 4px;">
        <i class="bi bi-phone"></i> Akun Pelanggan terintegrasi via aplikasi mobile CicalengkaGO (Flutter)
    </small>
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

function refreshCaptcha() {
    const btnIcon = document.querySelector('[title="Acak Ulang Kode Captcha"] i');
    if (btnIcon) btnIcon.classList.add('spin');
    
    fetch('<?= $baseUrl ?>/refresh-captcha')
        .then(res => res.json())
        .then(data => {
            if (data.captcha) {
                document.getElementById('captcha-code').innerText = data.captcha;
                document.getElementById('captcha').value = '';
            }
        })
        .catch(err => console.error('Error refresh captcha:', err))
        .finally(() => {
            if (btnIcon) btnIcon.classList.remove('spin');
        });
}
</script>

<style>
@keyframes spinAnim {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
.spin {
    animation: spinAnim 0.5s linear infinite;
}
</style>

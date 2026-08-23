<?php
$otpChannel    = $_SESSION['otp_channel'] ?? 'whatsapp';
$phoneMasked   = $_SESSION['otp_phone_masked'] ?? (!empty($email) ? $email : 'Nomor WhatsApp');
$isWaChannel   = ($otpChannel !== 'email');
?>
<div class="text-center mb-3">
    <?php if ($isWaChannel): ?>
        <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-2" style="width: 56px; height: 56px; background: #dcfce7;">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="#16a34a" xmlns="http://www.w3.org/2000/svg"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg>
        </div>
        <h6 class="fw-bold text-dark mb-1" style="font-size: 15px;">Verifikasi Kode OTP WhatsApp</h6>
        <p class="text-muted small mb-0" style="font-size: 11px;">
            Kode verifikasi 6-digit telah dikirimkan via <strong>WhatsApp</strong> ke:<br>
            <span class="fw-bold text-success font-monospace" style="font-size: 12px;"><?= htmlspecialchars($phoneMasked) ?></span>
        </p>
    <?php else: ?>
        <div class="d-inline-flex align-items-center justify-content-center bg-danger-subtle text-danger rounded-circle mb-2" style="width: 56px; height: 56px;">
            <i class="bi bi-envelope-check-fill fs-3"></i>
        </div>
        <h6 class="fw-bold text-dark mb-1" style="font-size: 15px;">Verifikasi Kode OTP Email</h6>
        <p class="text-muted small mb-0" style="font-size: 11px;">
            Kode verifikasi 6-digit telah dikirimkan ke email:<br>
            <span class="fw-bold text-dark text-break" style="font-size: 11.5px;"><?= htmlspecialchars($email ?? '') ?></span>
        </p>
    <?php endif; ?>
</div>

<!-- DEMO / REAL MODE OTP BANNER -->
<?php if (!empty($demoOtp)): ?>
<div class="p-3 bg-white border shadow-2xs mb-3 d-flex align-items-center justify-content-between" style="border-radius: 16px; border-color: #E2E8F0 !important;">
    <div>
        <small class="fw-bold text-dark d-block mb-1" style="font-size: 10.5px;"><i class="bi bi-shield-lock-fill text-warning me-1"></i> Mode Testing / Demo OTP:</small>
        <div class="h5 fw-bold text-danger m-0 font-monospace tracking-wider"><?= htmlspecialchars($demoOtp) ?></div>
    </div>
    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 py-1.5 fw-bold text-nowrap" style="font-size: 11px;" onclick="fillOtp('<?= htmlspecialchars($demoOtp) ?>')">
        <i class="bi bi-magic me-1"></i> Isi Otomatis
    </button>
</div>
<?php else: ?>
<div class="p-3 bg-white border shadow-2xs mb-3 d-flex align-items-center gap-2.5" style="border-radius: 16px; border-color: #E2E8F0 !important;">
    <?php if ($isWaChannel): ?>
        <svg width="24" height="24" viewBox="0 0 24 24" fill="#16a34a" class="flex-shrink-0"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg>
        <small class="text-dark" style="font-size: 11px;">
            <strong>OTP dikirim via WhatsApp:</strong> Buka aplikasi WhatsApp Anda dan cari pesan dari nomor gateway kami.
        </small>
    <?php else: ?>
        <i class="bi bi-shield-check text-primary fs-3 flex-shrink-0"></i>
        <small class="text-dark" style="font-size: 11px;">
            <strong>Mode Real Aktif:</strong> Silakan buka kotak masuk (inbox) atau folder spam pada email Anda untuk melihat 6 digit kode OTP rahasia.
        </small>
    <?php endif; ?>
</div>
<?php endif; ?>

<form action="<?= $baseUrl ?>/verify-otp" method="POST" id="otp-form">
    <!-- 6 Digit Input Group -->
    <div class="d-flex justify-content-between gap-1.5 mb-3">
        <?php for ($i = 0; $i < 6; $i++): ?>
            <input type="text" 
                   name="otp_digit[]" 
                   id="otp_digit_<?= $i ?>"
                   class="form-control text-center fw-bold fs-4 py-2 border otp-input bg-light" 
                   style="border-radius: 12px; border-color: #E2E8F0 !important;"
                   maxlength="1" 
                   pattern="[0-9]" 
                   inputmode="numeric" 
                   required
                   autocomplete="off"
                   data-index="<?= $i ?>">
        <?php endfor; ?>
    </div>

    <!-- Hidden full OTP field fallback -->
    <input type="hidden" name="otp" id="full-otp">

    <button type="submit" class="btn text-white w-100 py-2.5 fw-bold shadow-2xs mb-3" style="background: linear-gradient(135deg, #EE2737, #C61524); border-radius: 9999px; font-size: 12.5px;">
        <i class="bi bi-shield-check me-1"></i> Konfirmasi & Verifikasi OTP
    </button>
</form>

<?php
$lastSent = $_SESSION['otp_last_sent'] ?? 0;
$elapsed = time() - $lastSent;
$cooldownRemaining = max(0, 60 - $elapsed);
?>

<div class="d-flex align-items-center justify-content-between text-muted small pt-2 border-top" style="font-size: 11px;">
    <span>Tidak menerima kode?</span>
    <form action="<?= $baseUrl ?>/resend-otp" method="POST" class="d-inline" id="resend-form">
        <button type="submit" class="btn btn-link p-0 small fw-bold text-danger text-decoration-none" id="btn-resend" style="font-size: 11px;">
            <i class="bi bi-arrow-clockwise me-1"></i> Kirim Ulang OTP
        </button>
    </form>
</div>

<div class="text-center mt-3">
    <a href="<?= $baseUrl ?>/logout" class="text-muted text-decoration-none" style="font-size: 11px;">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke Halaman Masuk
    </a>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('.otp-input');
    const fullOtpInput = document.getElementById('full-otp');
    const form = document.getElementById('otp-form');
    const btnResend = document.getElementById('btn-resend');

    // Resend OTP Cooldown Timer (Anti-Spam)
    let cooldown = <?= (int)$cooldownRemaining ?>;
    function updateResendTimer() {
        if (!btnResend) return;
        if (cooldown > 0) {
            btnResend.disabled = true;
            btnResend.classList.add('opacity-50', 'pe-none');
            btnResend.innerHTML = `<i class="bi bi-clock-history me-1"></i> Kirim Ulang (${cooldown}s)`;
            cooldown--;
            setTimeout(updateResendTimer, 1000);
        } else {
            btnResend.disabled = false;
            btnResend.classList.remove('opacity-50', 'pe-none');
            btnResend.innerHTML = `<i class="bi bi-arrow-clockwise me-1"></i> Kirim Ulang OTP`;
        }
    }
    updateResendTimer();

    if (inputs.length > 0) {
        inputs[0].focus();
    }

    function updateFullOtp() {
        let val = '';
        inputs.forEach(input => val += input.value);
        fullOtpInput.value = val;
    }

    inputs.forEach((input, index) => {
        input.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value.length === 1 && index < inputs.length - 1) {
                inputs[index + 1].focus();
            }
            updateFullOtp();
        });

        input.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' && !this.value && index > 0) {
                inputs[index - 1].focus();
            }
        });

        input.addEventListener('paste', function(e) {
            e.preventDefault();
            const pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '').slice(0, 6);
            if (pasted) {
                pasted.split('').forEach((char, i) => {
                    if (inputs[i]) inputs[i].value = char;
                });
                updateFullOtp();
                if (inputs[Math.min(pasted.length, 5)]) {
                    inputs[Math.min(pasted.length, 5)].focus();
                }
            }
        });
    });

    form.addEventListener('submit', function() {
        updateFullOtp();
    });
});

function fillOtp(code) {
    const inputs = document.querySelectorAll('.otp-input');
    const codeStr = String(code).padStart(6, '0');
    codeStr.split('').forEach((char, i) => {
        if (inputs[i]) inputs[i].value = char;
    });
    document.getElementById('full-otp').value = codeStr;
    document.getElementById('otp-form').submit();
}
</script>

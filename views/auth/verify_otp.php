<div class="text-center mb-4">
    <div class="d-inline-flex align-items-center justify-content-center bg-danger-subtle text-danger rounded-circle mb-3" style="width: 64px; height: 64px;">
        <i class="bi bi-envelope-check-fill fs-2"></i>
    </div>
    <h5 class="fw-bold text-dark mb-1">Verifikasi Kode OTP Email</h5>
    <p class="text-muted small mb-0">
        Kode verifikasi 6-digit telah dikirimkan ke email:<br>
        <span class="fw-bold text-dark text-break"><?= htmlspecialchars($email ?? '') ?></span>
    </p>
</div>

<!-- DEMO / REAL MODE OTP BANNER -->
<?php if (!empty($demoOtp)): ?>
<div class="alert alert-warning border-0 rounded-4 shadow-xs p-3 mb-4 d-flex align-items-center justify-content-between">
    <div>
        <small class="fw-bold text-dark d-block mb-1"><i class="bi bi-shield-lock-fill text-warning me-1"></i> Mode Testing / Demo OTP:</small>
        <div class="h4 fw-bold text-danger m-0 font-monospace tracking-wider"><?= htmlspecialchars($demoOtp) ?></div>
    </div>
    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 py-1.5 fw-bold text-nowrap" onclick="fillOtp('<?= htmlspecialchars($demoOtp) ?>')">
        <i class="bi bi-magic me-1"></i> Isi Otomatis
    </button>
</div>
<?php else: ?>
<div class="alert alert-info border-0 rounded-4 shadow-xs p-3 mb-4 d-flex align-items-center gap-3" style="background:#eef6ff;">
    <i class="bi bi-shield-check text-primary fs-3 flex-shrink-0"></i>
    <small class="text-dark">
        <strong>Mode Real Aktif:</strong> Silakan buka kotak masuk (inbox) atau folder spam pada email Anda untuk melihat 6 digit kode OTP rahasia.
    </small>
</div>
<?php endif; ?>

<form action="<?= $baseUrl ?>/verify-otp" method="POST" id="otp-form">
    <!-- 6 Digit Input Group -->
    <div class="d-flex justify-content-between gap-2 mb-4">
        <?php for ($i = 0; $i < 6; $i++): ?>
            <input type="text" 
                   name="otp_digit[]" 
                   class="form-control text-center fw-bold fs-3 rounded-3 py-2 border-2 otp-input" 
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

    <button type="submit" class="btn text-white w-100 py-3 fw-bold rounded-4 shadow-sm mb-3" style="background:#EE2737;">
        <i class="bi bi-check-circle-fill me-1"></i> Verifikasi & Masuk
    </button>
</form>

<div class="d-flex align-items-center justify-content-between text-muted small pt-2 border-top">
    <span>Tidak menerima kode?</span>
    <form action="<?= $baseUrl ?>/resend-otp" method="POST" class="d-inline">
        <button type="submit" class="btn btn-link p-0 small fw-bold text-danger text-decoration-none" id="btn-resend">
            <i class="bi bi-arrow-clockwise me-1"></i> Kirim Ulang OTP
        </button>
    </form>
</div>

<div class="text-center mt-4">
    <a href="<?= $baseUrl ?>/logout" class="small text-muted text-decoration-none">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke Halaman Masuk
    </a>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('.otp-input');
    const fullOtpInput = document.getElementById('full-otp');
    const form = document.getElementById('otp-form');

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

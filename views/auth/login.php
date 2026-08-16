<form action="<?= $baseUrl ?>/login" method="POST" class="mb-3">
    <div class="mb-2.5">
        <label class="form-label text-dark mb-1" for="username" style="font-size: 11px; font-weight: 700;">Email / Nomor HP</label>
        <div class="input-group input-group-sm">
            <span class="input-group-text bg-light text-muted border-end-0" style="border-radius: 10px 0 0 10px; border-color: #E2E8F0;"><i class="bi bi-person-fill" style="font-size: 13px;"></i></span>
            <input type="text" name="username" id="username" class="form-control bg-light border-start-0" style="font-size: 11.5px; border-radius: 0 10px 10px 0; border-color: #E2E8F0;" placeholder="admin@cicalengkago.id" required>
        </div>
    </div>

    <div class="mb-3">
        <div class="d-flex justify-content-between align-items-center mb-1">
            <label class="form-label text-dark m-0" for="password" style="font-size: 11px; font-weight: 700;">Kata Sandi</label>
        </div>
        <div class="input-group input-group-sm">
            <span class="input-group-text bg-light text-muted border-end-0" style="border-radius: 10px 0 0 10px; border-color: #E2E8F0;"><i class="bi bi-lock-fill" style="font-size: 13px;"></i></span>
            <input type="password" name="password" id="password" class="form-control bg-light border-start-0 border-end-0" style="font-size: 11.5px; border-color: #E2E8F0;" placeholder="••••••••" required>
            <button class="btn bg-light border-start-0 text-muted px-2.5" type="button" onclick="togglePass()" style="border-radius: 0 10px 10px 0; border-color: #E2E8F0;"><i id="pass-icon" class="bi bi-eye" style="font-size: 13px;"></i></button>
        </div>
    </div>

    <button type="submit" class="btn text-white w-100 py-2.5 fw-bold shadow-2xs mb-2.5" style="background: linear-gradient(135deg, #EE2737, #C61524); border-radius: 9999px; font-size: 12.5px; letter-spacing: -0.2px;">
        <i class="bi bi-box-arrow-in-right me-1"></i> Masuk Sekarang
    </button>
</form>

<div class="text-center mb-3">
    <span class="text-muted" style="font-size: 11px;">Belum punya akun?</span>
    <a href="<?= $baseUrl ?>/register" class="fw-bold text-danger text-decoration-none ms-1" style="font-size: 11px;">Daftar Baru</a>
</div>

<!-- Fast 1-Click Demo Selector -->
<div class="p-2.5 bg-light border shadow-2xs" style="border-radius: 14px; border-color: #E2E8F0 !important;">
    <div class="text-center fw-bold text-muted mb-2" style="font-size: 10px;">⚡ AKSES 1-KLIK DEMO AKUN</div>
    <div class="row g-1.5">
        <div class="col-6">
            <button onclick="fillLogin('admin@cicalengkago.id', 'password')" class="btn btn-dark btn-sm w-100 py-1.5 px-2 text-start d-flex align-items-center justify-content-between rounded-3 shadow-2xs" style="font-size: 10px;">
                <span class="fw-bold text-truncate me-1"><i class="bi bi-shield-lock-fill text-warning me-1"></i> Admin</span>
                <i class="bi bi-arrow-right-short text-white-50"></i>
            </button>
        </div>
        <div class="col-6">
            <button onclick="fillLogin('vendor@cicalengkago.id', 'password')" class="btn btn-danger btn-sm w-100 py-1.5 px-2 text-start d-flex align-items-center justify-content-between rounded-3 shadow-2xs" style="font-size: 10px; background:#B91C1C;">
                <span class="fw-bold text-truncate me-1"><i class="bi bi-shop me-1"></i> Resto</span>
                <i class="bi bi-arrow-right-short text-white-50"></i>
            </button>
        </div>
        <div class="col-6">
            <button onclick="fillLogin('driver@cicalengkago.id', 'password')" class="btn text-white btn-sm w-100 py-1.5 px-2 text-start d-flex align-items-center justify-content-between rounded-3 shadow-2xs" style="font-size: 10px; background:#101820; border-left: 3px solid #EE2737;">
                <span class="fw-bold text-truncate me-1"><i class="bi bi-bicycle me-1 text-danger"></i> Driver</span>
                <i class="bi bi-arrow-right-short text-white-50"></i>
            </button>
        </div>
        <div class="col-6">
            <button onclick="fillLogin('customer@cicalengkago.id', 'password')" class="btn text-white btn-sm w-100 py-1.5 px-2 text-start d-flex align-items-center justify-content-between rounded-3 shadow-2xs" style="font-size: 10px; background:#EE2737;">
                <span class="fw-bold text-truncate me-1"><i class="bi bi-person-fill me-1"></i> Customer</span>
                <i class="bi bi-arrow-right-short text-white-50"></i>
            </button>
        </div>
    </div>
</div>

<script>
function togglePass() {
    const input = document.getElementById('password');
    const icon = document.getElementById('pass-icon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}

function fillLogin(u, p) {
    document.getElementById('username').value = u;
    document.getElementById('password').value = p;
    document.querySelector('form').submit();
}
</script>

<form action="<?= $baseUrl ?>/login" method="POST">
    <div class="mb-3">
        <label class="form-label small fw-bold text-dark">Email atau Nomor HP</label>
        <div class="input-group">
            <span class="input-group-text bg-light border-end-0 rounded-start-3 text-primary"><i class="bi bi-person"></i></span>
            <input type="text" name="username" id="username" class="form-control bg-light border-start-0 rounded-end-3 py-2" placeholder="admin@cicalengkago.id" required>
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label small fw-bold text-dark">Kata Sandi</label>
        <div class="input-group">
            <span class="input-group-text bg-light border-end-0 rounded-start-3 text-primary"><i class="bi bi-lock"></i></span>
            <input type="password" name="password" id="password" class="form-control bg-light border-start-0 border-end-0 py-2" placeholder="••••••••" required>
            <button class="btn bg-light border-start-0 rounded-end-3 text-muted" type="button" onclick="togglePass()"><i id="pass-icon" class="bi bi-eye"></i></button>
        </div>
    </div>

    <button type="submit" class="btn text-white w-100 py-3 fw-bold rounded-4 shadow-sm mb-3" style="background:#EE2737;">
        <i class="bi bi-box-arrow-in-right me-1"></i> Masuk Sekarang
    </button>
</form>

<div class="text-center mb-4">
    <span class="text-muted small">Belum punya akun?</span>
    <a href="<?= $baseUrl ?>/register" class="small fw-bold text-danger text-decoration-none ms-1">Daftar Akun Baru</a>
</div>

<!-- Fast Demo Account Selector -->
<div class="p-3 bg-light rounded-4 border">
    <div class="text-center small fw-bold text-muted mb-2">⚡ Akses Cepat Akun Demo (1-Klik):</div>
    <div class="d-grid gap-2">
        <button onclick="fillLogin('admin@cicalengkago.id', 'password')" class="btn btn-dark btn-sm py-2 px-3 text-start d-flex align-items-center justify-content-between rounded-3 shadow-xs">
            <span class="fw-semibold"><i class="bi bi-shield-lock-fill me-2 text-warning"></i> Super Admin</span>
            <span class="badge bg-secondary" style="font-size: 10px;">admin@cicalengkago.id</span>
        </button>
        <button onclick="fillLogin('vendor@cicalengkago.id', 'password')" class="btn btn-danger btn-sm py-2 px-3 text-start d-flex align-items-center justify-content-between rounded-3 shadow-xs" style="background:#B91C1C;">
            <span class="fw-semibold"><i class="bi bi-shop me-2"></i> Mitra Resto / Toko</span>
            <span class="badge bg-white text-danger" style="font-size: 10px;">vendor@cicalengkago.id</span>
        </button>
        <button onclick="fillLogin('driver@cicalengkago.id', 'password')" class="btn text-white btn-sm py-2 px-3 text-start d-flex align-items-center justify-content-between rounded-3 shadow-xs" style="background:#101820; border-left: 4px solid #EE2737;">
            <span class="fw-semibold"><i class="bi bi-bicycle me-2" style="color:#EE2737;"></i> Mitra Driver</span>
            <span class="badge bg-danger" style="font-size: 10px; background:#EE2737 !important;">driver@cicalengkago.id</span>
        </button>
        <button onclick="fillLogin('customer@cicalengkago.id', 'password')" class="btn text-white btn-sm py-2 px-3 text-start d-flex align-items-center justify-content-between rounded-3 shadow-xs" style="background:#EE2737;">
            <span class="fw-semibold"><i class="bi bi-person-fill me-2"></i> Pelanggan (Customer)</span>
            <span class="badge bg-white text-danger" style="font-size: 10px;">customer@cicalengkago.id</span>
        </button>
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
</script>

<script>
function fillLogin(u, p) {
    document.getElementById('username').value = u;
    document.getElementById('password').value = p;
    document.querySelector('form').submit();
}
</script>

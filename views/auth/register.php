<form action="<?= $baseUrl ?>/register" method="POST">
    <div class="mb-3">
        <label class="form-label small fw-bold" for="reg_name">Nama Lengkap</label>
        <div class="input-group">
            <span class="input-group-text bg-light border-end-0"><i class="bi bi-person text-muted"></i></span>
            <input type="text" name="name" id="reg_name" class="form-control bg-light border-start-0" placeholder="Budi Santoso" required>
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label small fw-bold" for="reg_phone">Nomor WhatsApp / HP</label>
        <div class="input-group">
            <span class="input-group-text bg-light border-end-0"><i class="bi bi-whatsapp text-muted"></i></span>
            <input type="text" name="phone" id="reg_phone" class="form-control bg-light border-start-0" placeholder="08xxxxxxxxxx" required>
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label small fw-bold" for="reg_email">Email</label>
        <div class="input-group">
            <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-muted"></i></span>
            <input type="email" name="email" id="reg_email" class="form-control bg-light border-start-0" placeholder="nama@email.com" required>
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label small fw-bold" for="reg_password">Kata Sandi</label>
        <div class="input-group">
            <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-muted"></i></span>
            <input type="password" name="password" id="reg_password" class="form-control bg-light border-start-0" placeholder="Minimal 6 karakter" required>
        </div>
    </div>

    <div class="p-3 mb-3 bg-danger-subtle text-danger rounded-3 small">
        <i class="bi bi-gift-fill me-1"></i> Dapatkan bonus saldo <strong>Rp 25.000</strong> langsung di dompet CicalengkaPay setelah mendaftar!
    </div>

    <button type="submit" class="btn text-white w-100 py-2 fw-bold rounded-3 shadow-sm mb-3" style="background:#EE2737;">
        Daftar Sekarang
    </button>
</form>

<div class="text-center">
    <span class="text-muted small">Sudah memiliki akun?</span>
    <a href="<?= $baseUrl ?>/login" class="small fw-bold text-decoration-none ms-1">Masuk</a>
</div>

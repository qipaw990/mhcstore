<form action="<?= $baseUrl ?>/register" method="POST" class="mb-3">
    <div class="mb-2">
        <label class="form-label text-dark mb-1" for="reg_name" style="font-size: 11px; font-weight: 700;">Nama Lengkap</label>
        <div class="input-group input-group-sm">
            <span class="input-group-text bg-light text-muted border-end-0" style="border-radius: 10px 0 0 10px; border-color: #E2E8F0;"><i class="bi bi-person-fill" style="font-size: 13px;"></i></span>
            <input type="text" name="name" id="reg_name" class="form-control bg-light border-start-0" style="font-size: 11.5px; border-radius: 0 10px 10px 0; border-color: #E2E8F0;" placeholder="Budi Santoso" required>
        </div>
    </div>

    <div class="mb-2">
        <label class="form-label text-dark mb-1" for="reg_phone" style="font-size: 11px; font-weight: 700;">Nomor WhatsApp / HP</label>
        <div class="input-group input-group-sm">
            <span class="input-group-text bg-light text-muted border-end-0" style="border-radius: 10px 0 0 10px; border-color: #E2E8F0;"><i class="bi bi-whatsapp" style="font-size: 13px;"></i></span>
            <input type="text" name="phone" id="reg_phone" class="form-control bg-light border-start-0" style="font-size: 11.5px; border-radius: 0 10px 10px 0; border-color: #E2E8F0;" placeholder="08xxxxxxxxxx" required>
        </div>
    </div>

    <div class="mb-2">
        <label class="form-label text-dark mb-1" for="reg_email" style="font-size: 11px; font-weight: 700;">Email</label>
        <div class="input-group input-group-sm">
            <span class="input-group-text bg-light text-muted border-end-0" style="border-radius: 10px 0 0 10px; border-color: #E2E8F0;"><i class="bi bi-envelope-fill" style="font-size: 13px;"></i></span>
            <input type="email" name="email" id="reg_email" class="form-control bg-light border-start-0" style="font-size: 11.5px; border-radius: 0 10px 10px 0; border-color: #E2E8F0;" placeholder="nama@email.com" required>
        </div>
    </div>

    <div class="mb-2.5">
        <label class="form-label text-dark mb-1" for="reg_password" style="font-size: 11px; font-weight: 700;">Kata Sandi</label>
        <div class="input-group input-group-sm">
            <span class="input-group-text bg-light text-muted border-end-0" style="border-radius: 10px 0 0 10px; border-color: #E2E8F0;"><i class="bi bi-lock-fill" style="font-size: 13px;"></i></span>
            <input type="password" name="password" id="reg_password" class="form-control bg-light border-start-0" style="font-size: 11.5px; border-radius: 0 10px 10px 0; border-color: #E2E8F0;" placeholder="Minimal 6 karakter" required>
        </div>
    </div>

    <div class="p-2.5 mb-3 bg-danger-subtle text-danger rounded-3 border border-danger-subtle" style="font-size: 10.5px; border-radius: 10px !important;">
        <i class="bi bi-gift-fill me-1"></i> Bonus saldo <strong>Rp 25.000</strong> langsung di CicalengkaPay setelah mendaftar!
    </div>

    <button type="submit" class="btn text-white w-100 py-2.5 fw-bold shadow-2xs mb-2.5" style="background: linear-gradient(135deg, #EE2737, #C61524); border-radius: 9999px; font-size: 12.5px; letter-spacing: -0.2px;">
        Daftar Sekarang
    </button>
</form>

<div class="text-center">
    <span class="text-muted" style="font-size: 11px;">Sudah memiliki akun?</span>
    <a href="<?= $baseUrl ?>/login" class="fw-bold text-danger text-decoration-none ms-1" style="font-size: 11px;">Masuk</a>
</div>

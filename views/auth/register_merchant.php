<div class="mb-3 text-center">
    <div class="d-inline-flex align-items-center justify-content-center bg-danger-subtle text-danger rounded-circle mb-2" style="width: 48px; height: 48px;">
        <i class="bi bi-shop fs-4"></i>
    </div>
    <h5 class="fw-bold text-dark mb-1">Daftar Mitra Merchant / Toko</h5>
    <p class="text-muted small mb-0" style="font-size: 11.5px;">Buka toko & tingkatkan penjualan produk kuliner/usaha Anda di CicalengkaGO</p>
</div>

<form action="<?= $baseUrl ?>/register-merchant" method="POST" style="margin-bottom: 20px;">

    <!-- Section 1: Informasi Pemilik Toko -->
    <div class="p-2.5 bg-light rounded-3 mb-3 border">
        <div class="fw-bold small text-primary mb-2" style="font-size: 11px;"><i class="bi bi-person-badge-fill me-1"></i> Data Pemilik Toko</div>
        
        <!-- Nama Lengkap Pemilik -->
        <div style="margin-bottom: 10px;">
            <label for="reg_name" style="display: block; font-size: 11px; font-weight: 700; color: #0F172A; margin-bottom: 4px;">Nama Lengkap Pemilik *</label>
            <div style="display: flex; align-items: center; background: #FFFFFF; border: 1.5px solid #E2E8F0; border-radius: 10px; overflow: hidden;" id="wrap-name">
                <span style="padding: 0 10px; color: #94A3B8; flex-shrink: 0; display: flex; align-items: center;">
                    <i class="bi bi-person-fill" style="font-size: 13px;"></i>
                </span>
                <input type="text" name="name" id="reg_name"
                       style="flex: 1; border: none; background: transparent; padding: 8px 10px 8px 0; font-size: 11.5px; color: #0F172A; outline: none;"
                       placeholder="Nama lengkap sesuai KTP" required>
            </div>
        </div>

        <!-- Nomor WhatsApp Pemilik -->
        <div style="margin-bottom: 10px;">
            <label for="reg_phone" style="display: block; font-size: 11px; font-weight: 700; color: #0F172A; margin-bottom: 4px;">Nomor WhatsApp Pemilik *</label>
            <div style="display: flex; align-items: center; background: #FFFFFF; border: 1.5px solid #E2E8F0; border-radius: 10px; overflow: hidden;" id="wrap-phone">
                <span style="padding: 0 10px; color: #25D366; flex-shrink: 0; display: flex; align-items: center;">
                    <i class="bi bi-whatsapp" style="font-size: 13px;"></i>
                </span>
                <input type="text" name="phone" id="reg_phone"
                       style="flex: 1; border: none; background: transparent; padding: 8px 10px 8px 0; font-size: 11.5px; color: #0F172A; outline: none;"
                       placeholder="08xxxxxxxxxx" required>
            </div>
        </div>

        <!-- Email Pemilik -->
        <div style="margin-bottom: 10px;">
            <label for="reg_email" style="display: block; font-size: 11px; font-weight: 700; color: #0F172A; margin-bottom: 4px;">Email Pemilik *</label>
            <div style="display: flex; align-items: center; background: #FFFFFF; border: 1.5px solid #E2E8F0; border-radius: 10px; overflow: hidden;" id="wrap-email">
                <span style="padding: 0 10px; color: #94A3B8; flex-shrink: 0; display: flex; align-items: center;">
                    <i class="bi bi-envelope-fill" style="font-size: 13px;"></i>
                </span>
                <input type="email" name="email" id="reg_email"
                       style="flex: 1; border: none; background: transparent; padding: 8px 10px 8px 0; font-size: 11.5px; color: #0F172A; outline: none;"
                       placeholder="nama@email.com" required>
            </div>
        </div>

        <!-- Kata Sandi Login -->
        <div style="margin-bottom: 4px;">
            <label for="reg_password" style="display: block; font-size: 11px; font-weight: 700; color: #0F172A; margin-bottom: 4px;">Kata Sandi Akun *</label>
            <div style="display: flex; align-items: center; background: #FFFFFF; border: 1.5px solid #E2E8F0; border-radius: 10px; overflow: hidden;" id="wrap-pass">
                <span style="padding: 0 10px; color: #94A3B8; flex-shrink: 0; display: flex; align-items: center;">
                    <i class="bi bi-lock-fill" style="font-size: 13px;"></i>
                </span>
                <input type="password" name="password" id="reg_password"
                       style="flex: 1; border: none; background: transparent; padding: 8px 0; font-size: 11.5px; color: #0F172A; outline: none; width: 0;"
                       placeholder="Minimal 6 karakter" required>
                <button type="button" onclick="toggleMerchantRegPass()" style="padding: 0 12px; background: transparent; border: none; color: #94A3B8; cursor: pointer; display: flex; align-items: center; flex-shrink: 0;">
                    <i id="reg-pass-icon" class="bi bi-eye" style="font-size: 13px;"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Section 2: Informasi Toko / Usaha -->
    <div class="p-2.5 bg-light rounded-3 mb-3 border">
        <div class="fw-bold small text-danger mb-2" style="font-size: 11px;"><i class="bi bi-shop-window me-1"></i> Data Toko / Resto</div>

        <!-- Nama Toko -->
        <div style="margin-bottom: 10px;">
            <label for="reg_store_name" style="display: block; font-size: 11px; font-weight: 700; color: #0F172A; margin-bottom: 4px;">Nama Toko / Resto / Warung *</label>
            <div style="display: flex; align-items: center; background: #FFFFFF; border: 1.5px solid #E2E8F0; border-radius: 10px; overflow: hidden;" id="wrap-store-name">
                <span style="padding: 0 10px; color: #EE2737; flex-shrink: 0; display: flex; align-items: center;">
                    <i class="bi bi-shop" style="font-size: 13px;"></i>
                </span>
                <input type="text" name="store_name" id="reg_store_name"
                       style="flex: 1; border: none; background: transparent; padding: 8px 10px 8px 0; font-size: 11.5px; color: #0F172A; outline: none;"
                       placeholder="Contoh: Rumah Makan Padang Sederhana" required>
            </div>
        </div>

        <!-- Modul Bisnis -->
        <div style="margin-bottom: 10px;">
            <label for="reg_module_id" style="display: block; font-size: 11px; font-weight: 700; color: #0F172A; margin-bottom: 4px;">Kategori / Modul Usaha *</label>
            <select name="module_id" id="reg_module_id" class="form-select form-select-sm rounded-3" style="font-size: 11.5px;" required>
                <?php foreach (($modules ?? []) as $m): ?>
                    <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['name']) ?> (<?= $m['module_type'] ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- No WhatsApp Khusus Toko -->
        <div style="margin-bottom: 10px;">
            <label for="reg_store_phone" style="display: block; font-size: 11px; font-weight: 700; color: #0F172A; margin-bottom: 4px;">Nomor Telepon / WhatsApp Toko</label>
            <div style="display: flex; align-items: center; background: #FFFFFF; border: 1.5px solid #E2E8F0; border-radius: 10px; overflow: hidden;">
                <span style="padding: 0 10px; color: #94A3B8; flex-shrink: 0; display: flex; align-items: center;">
                    <i class="bi bi-telephone-fill" style="font-size: 13px;"></i>
                </span>
                <input type="text" name="store_phone" id="reg_store_phone"
                       style="flex: 1; border: none; background: transparent; padding: 8px 10px 8px 0; font-size: 11.5px; color: #0F172A; outline: none;"
                       placeholder="Nomor kontak untuk pelanggan & driver">
            </div>
        </div>

        <!-- Alamat Lengkap Toko -->
        <div style="margin-bottom: 10px;">
            <label for="reg_store_address" style="display: block; font-size: 11px; font-weight: 700; color: #0F172A; margin-bottom: 4px;">Alamat Lengkap Toko / Penjemputan Driver *</label>
            <textarea name="store_address" id="reg_store_address" class="form-control form-control-sm rounded-3" rows="2" style="font-size: 11.5px;" placeholder="Jl. Raya Cicalengka No. ..., Patokan: ..." required></textarea>
        </div>

        <!-- Titik Koordinat Peta & Kalibrasi GPS -->
        <div class="row g-2">
            <div class="col-6">
                <label class="small fw-bold" style="font-size: 10.5px;">Latitude GPS *</label>
                <input type="text" name="latitude" id="reg_lat" class="form-control form-control-sm rounded-3" value="-6.9840" required readonly style="font-size: 11px; background: #F1F5F9;">
            </div>
            <div class="col-6">
                <label class="small fw-bold" style="font-size: 10.5px;">Longitude GPS *</label>
                <input type="text" name="longitude" id="reg_lng" class="form-control form-control-sm rounded-3" value="107.8340" required readonly style="font-size: 11px; background: #F1F5F9;">
            </div>
            <div class="col-12 mt-1">
                <button type="button" class="btn btn-outline-primary btn-sm w-100 rounded-pill fw-bold" onclick="detectMerchantGPS()" style="font-size: 11px;">
                    <i class="bi bi-crosshair me-1"></i> <span id="regGpsLabel">Kalibrasi GPS Lokasi Toko Saat Ini</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Notice Review Admin -->
    <div class="p-2.5 bg-warning-subtle text-dark rounded-3 mb-3 border border-warning-subtle d-flex align-items-start gap-2">
        <i class="bi bi-info-circle-fill text-warning fs-5 flex-shrink-0 mt-0.5"></i>
        <div style="font-size: 11px; line-height: 1.4;">
            <strong>Proses Review Admin:</strong> Setelah Anda mendaftar, tim CicalengkaGO akan meninjau kelayakan toko Anda. Akun akan aktif segera setelah disetujui.
        </div>
    </div>

    <!-- Tombol Daftar -->
    <button type="submit" style="width: 100%; background: linear-gradient(135deg, #EE2737 0%, #C61524 100%); color: #FFFFFF; border: none; border-radius: 9999px; padding: 12px 16px; font-size: 13px; font-weight: 700; letter-spacing: -0.2px; box-shadow: 0 4px 14px rgba(238, 39, 55, 0.3); cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px;">
        <i class="bi bi-send-check-fill" style="font-size: 14px;"></i> Ajukan Pendaftaran Toko
    </button>
</form>

<!-- Link ke Login Pelanggan & Merchant -->
<div style="text-align: center; margin-bottom: 8px;">
    <span style="font-size: 11.5px; color: #64748B;">Sudah punya akun Mitra Toko?</span>
    <a href="<?= $baseUrl ?>/login" style="font-size: 11.5px; font-weight: 700; color: #EE2737; text-decoration: none; margin-left: 4px;">Masuk Di Sini</a>
</div>

<div style="text-align: center; margin-top: 12px; padding-top: 10px; border-top: 1px dashed #E2E8F0;">
    <a href="<?= $baseUrl ?>/" style="font-size: 11px; font-weight: 600; color: #64748B; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
        <i class="bi bi-house-door" style="font-size: 12px;"></i> Kembali ke Beranda Utama
    </a>
</div>

<script>
function toggleMerchantRegPass() {
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

function detectMerchantGPS() {
    const lbl = document.getElementById('regGpsLabel');
    if (!navigator.geolocation) {
        alert('Browser tidak mendukung deteksi GPS.');
        return;
    }
    lbl.textContent = 'Mendeteksi koordinat GPS...';
    navigator.geolocation.getCurrentPosition(
        function(pos) {
            lbl.textContent = 'GPS Terkalibrasi (Akurat)';
            document.getElementById('reg_lat').value = pos.coords.latitude.toFixed(6);
            document.getElementById('reg_lng').value = pos.coords.longitude.toFixed(6);
        },
        function(err) {
            lbl.textContent = 'Kalibrasi GPS Lokasi Toko Saat Ini';
            alert('Gagal mendeteksi GPS. Pastikan izin lokasi aktif.');
        },
        { enableHighAccuracy: true, timeout: 10000 }
    );
}
</script>

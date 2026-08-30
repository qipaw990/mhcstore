<style>
.radar-pulse-circle {
    stroke: #EE2737;
    stroke-width: 2;
    fill: #EE2737;
    fill-opacity: 0.15;
}
.image-upload-box {
    border: 1.5px dashed #CBD5E1;
    border-radius: 12px;
    padding: 12px;
    text-align: center;
    background: #F8FAFC;
    cursor: pointer;
    transition: all 0.2s;
}
.image-upload-box:hover {
    border-color: #EE2737;
    background: #FEF2F2;
}
.preview-img {
    width: 100%;
    height: 90px;
    object-fit: cover;
    border-radius: 8px;
    display: none;
}
</style>

<div class="mb-3 text-center">
    <div class="d-inline-flex align-items-center justify-content-center bg-danger-subtle text-danger rounded-circle mb-2" style="width: 48px; height: 48px;">
        <i class="bi bi-shop fs-4"></i>
    </div>
    <h5 class="fw-bold text-dark mb-1">Daftar Mitra Merchant / Toko</h5>
    <p class="text-muted small mb-0" style="font-size: 11.5px;">Buka toko & tingkatkan omzet usaha Anda bersama CicalengkaGO</p>
</div>

<form action="<?= $baseUrl ?>/register-merchant" method="POST" enctype="multipart/form-data" style="margin-bottom: 20px;">

    <!-- Section 1: Informasi Pemilik Toko -->
    <div class="p-3 bg-light rounded-4 mb-3 border">
        <div class="fw-bold small text-primary mb-2.5" style="font-size: 11.5px;">
            <i class="bi bi-person-badge-fill me-1"></i> 1. Data Pemilik Toko
        </div>
        
        <!-- Nama Lengkap Pemilik -->
        <div style="margin-bottom: 10px;">
            <label for="reg_name" style="display: block; font-size: 11px; font-weight: 700; color: #0F172A; margin-bottom: 4px;">Nama Lengkap Pemilik (Sesuai KTP) *</label>
            <div style="display: flex; align-items: center; background: #FFFFFF; border: 1.5px solid #E2E8F0; border-radius: 10px; overflow: hidden;" id="wrap-name">
                <span style="padding: 0 10px; color: #94A3B8; flex-shrink: 0; display: flex; align-items: center;">
                    <i class="bi bi-person-fill" style="font-size: 13px;"></i>
                </span>
                <input type="text" name="name" id="reg_name"
                       style="flex: 1; border: none; background: transparent; padding: 8px 10px 8px 0; font-size: 11.5px; color: #0F172A; outline: none;"
                       placeholder="Nama lengkap pemilik toko" required>
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
        <div style="margin-bottom: 12px;">
            <label for="reg_password" style="display: block; font-size: 11px; font-weight: 700; color: #0F172A; margin-bottom: 4px;">Kata Sandi Akun Login *</label>
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

        <!-- Foto KTP Pemilik -->
        <div>
            <label style="display: block; font-size: 11px; font-weight: 700; color: #0F172A; margin-bottom: 4px;">Foto KTP Pemilik Toko *</label>
            <div class="image-upload-box" onclick="document.getElementById('input_ktp').click()">
                <input type="file" name="identity_image" id="input_ktp" accept="image/*" style="display: none;" onchange="previewUpload(this, 'preview_ktp', 'icon_ktp')" required>
                <img id="preview_ktp" class="preview-img mb-1">
                <div id="icon_ktp">
                    <i class="bi bi-card-heading text-primary fs-4"></i>
                    <div class="small fw-bold text-dark mt-1" style="font-size: 11px;">Upload Foto KTP</div>
                    <small class="text-muted" style="font-size: 10px;">Format: JPG, PNG (Maks 5MB)</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 2: Informasi Toko & Usaha -->
    <div class="p-3 bg-light rounded-4 mb-3 border">
        <div class="fw-bold small text-danger mb-2.5" style="font-size: 11.5px;">
            <i class="bi bi-shop-window me-1"></i> 2. Profil Usaha & Toko
        </div>

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
                       placeholder="Nomor kontak untuk pesanan pelanggan">
            </div>
        </div>

        <!-- Alamat Lengkap Toko -->
        <div style="margin-bottom: 12px;">
            <label for="reg_store_address" style="display: block; font-size: 11px; font-weight: 700; color: #0F172A; margin-bottom: 4px;">Alamat Lengkap Toko / Titik Jemput *</label>
            <textarea name="store_address" id="reg_store_address" class="form-control form-control-sm rounded-3" rows="2" style="font-size: 11.5px;" placeholder="Jl. Raya Cicalengka No. ..., Patokan: ..." required></textarea>
        </div>

        <!-- Upload Logo Toko & Foto Depan Toko -->
        <div class="row g-2 mb-2">
            <div class="col-6">
                <label style="display: block; font-size: 10.5px; font-weight: 700; color: #0F172A; margin-bottom: 3px;">Logo / Ikon Toko *</label>
                <div class="image-upload-box" onclick="document.getElementById('input_logo').click()">
                    <input type="file" name="logo" id="input_logo" accept="image/*" style="display: none;" onchange="previewUpload(this, 'preview_logo', 'icon_logo')" required>
                    <img id="preview_logo" class="preview-img mb-1">
                    <div id="icon_logo">
                        <i class="bi bi-image text-danger fs-5"></i>
                        <div class="small fw-bold text-dark" style="font-size: 10.5px;">Upload Logo</div>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <label style="display: block; font-size: 10.5px; font-weight: 700; color: #0F172A; margin-bottom: 3px;">Foto Toko Tampak Depan *</label>
                <div class="image-upload-box" onclick="document.getElementById('input_cover').click()">
                    <input type="file" name="cover_photo" id="input_cover" accept="image/*" style="display: none;" onchange="previewUpload(this, 'preview_cover', 'icon_cover')" required>
                    <img id="preview_cover" class="preview-img mb-1">
                    <div id="icon_cover">
                        <i class="bi bi-camera text-info fs-5"></i>
                        <div class="small fw-bold text-dark" style="font-size: 10.5px;">Foto Depan Toko</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 3: Radar Maps & Kalibrasi GPS Jangkauan Antar -->
    <div class="p-3 bg-light rounded-4 mb-3 border">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
            <div class="fw-bold small text-dark" style="font-size: 11.5px;">
                <i class="bi bi-radar text-danger me-1"></i> 3. Radar Jangkauan & Titik Lokasi
            </div>
            <button type="button" class="btn btn-outline-primary btn-sm rounded-pill fw-bold" onclick="detectMerchantGPS()" style="font-size: 10.5px;">
                <i class="bi bi-crosshair me-1"></i> <span id="regGpsLabel">Kalibrasi GPS Saya</span>
            </button>
        </div>

        <div class="row g-2 mb-2">
            <div class="col-6">
                <label class="small fw-bold" style="font-size: 10.5px;">Latitude GPS *</label>
                <input type="text" name="latitude" id="reg_lat" class="form-control form-control-sm rounded-3 bg-white" value="-6.9840" required readonly style="font-size: 11px;">
            </div>
            <div class="col-6">
                <label class="small fw-bold" style="font-size: 10.5px;">Longitude GPS *</label>
                <input type="text" name="longitude" id="reg_lng" class="form-control form-control-sm rounded-3 bg-white" value="107.8340" required readonly style="font-size: 11px;">
            </div>
        </div>

        <!-- Radar Map Container -->
        <div id="merchant-reg-map" style="width: 100%; height: 220px; border-radius: 12px; border: 1px solid #cbd5e1; z-index: 1;"></div>
        
        <div class="d-flex align-items-center justify-content-between mt-2" style="font-size: 10.5px; color: #64748B;">
            <span><i class="bi bi-broadcast text-danger me-1"></i> Radius Radar Delivery: <strong>5 km</strong></span>
            <span>Geser pin untuk memindahkan titik</span>
        </div>
    </div>

    <!-- Notice Review Admin -->
    <div class="p-2.5 bg-warning-subtle text-dark rounded-3 mb-3 border border-warning-subtle d-flex align-items-start gap-2">
        <i class="bi bi-info-circle-fill text-warning fs-5 flex-shrink-0 mt-0.5"></i>
        <div style="font-size: 11px; line-height: 1.4;">
            <strong>Proses Review Admin:</strong> Dokumen KTP, Logo, Foto Toko, dan Titik Lokasi akan diperiksa oleh Tim Admin CicalengkaGO sebelum akun aktif.
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
let regMap = null;
let regMarker = null;
let regRadarCircle = null;

function initRegisterRadarMap() {
    const lat = -6.9840;
    const lng = 107.8340;

    if (!regMap && document.getElementById('merchant-reg-map')) {
        regMap = L.map('merchant-reg-map').setView([lat, lng], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 18, attribution: '© OpenStreetMap' }).addTo(regMap);

        regMarker = L.marker([lat, lng], { draggable: true }).addTo(regMap);

        // Radar Delivery Radius Circle (5000 meters)
        regRadarCircle = L.circle([lat, lng], {
            radius: 5000,
            color: '#EE2737',
            weight: 2,
            fillColor: '#EE2737',
            fillOpacity: 0.12,
            dashArray: '6, 6'
        }).addTo(regMap);

        regMarker.on('drag', function(e) {
            const pos = regMarker.getLatLng();
            regRadarCircle.setLatLng(pos);
        });

        regMarker.on('dragend', function(e) {
            const pos = regMarker.getLatLng();
            document.getElementById('reg_lat').value = pos.lat.toFixed(6);
            document.getElementById('reg_lng').value = pos.lng.toFixed(6);
            regRadarCircle.setLatLng(pos);
        });

        regMap.on('click', function(e) {
            regMarker.setLatLng(e.latlng);
            regRadarCircle.setLatLng(e.latlng);
            document.getElementById('reg_lat').value = e.latlng.lat.toFixed(6);
            document.getElementById('reg_lng').value = e.latlng.lng.toFixed(6);
        });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    setTimeout(initRegisterRadarMap, 300);
});

function previewUpload(input, previewId, iconId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = document.getElementById(previewId);
            const icon = document.getElementById(iconId);
            img.src = e.target.result;
            img.style.display = 'block';
            if (icon) icon.style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

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
    lbl.textContent = 'Mendeteksi...';
    navigator.geolocation.getCurrentPosition(
        function(pos) {
            lbl.textContent = 'GPS Terkalibrasi';
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;
            document.getElementById('reg_lat').value = lat.toFixed(6);
            document.getElementById('reg_lng').value = lng.toFixed(6);
            if (regMap && regMarker && regRadarCircle) {
                regMarker.setLatLng([lat, lng]);
                regRadarCircle.setLatLng([lat, lng]);
                regMap.setView([lat, lng], 14);
            }
        },
        function(err) {
            lbl.textContent = 'Kalibrasi GPS Saya';
            alert('Gagal mendeteksi GPS. Pastikan izin lokasi aktif.');
        },
        { enableHighAccuracy: true, timeout: 10000 }
    );
}
</script>

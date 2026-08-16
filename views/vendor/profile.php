<!-- Store & Owner Summary Card -->
<div class="card border-0 shadow-sm rounded-4 p-3 bg-white text-center mb-3">
    <div class="position-relative d-inline-block mx-auto mb-2">
        <img src="<?= $baseUrl ?>/<?= htmlspecialchars($store['logo'] ?? 'assets/images/stores/default.jpg') ?>" alt="Store Logo" class="rounded-circle border border-3 border-danger shadow-xs" style="width: 80px; height: 80px; object-fit: cover;">
        <?php if ($store && $store['is_open']): ?>
            <span class="position-absolute bottom-0 end-0 bg-success border border-2 border-white rounded-circle p-1.5" style="width: 14px; height: 14px;" title="Toko Buka"></span>
        <?php else: ?>
            <span class="position-absolute bottom-0 end-0 bg-danger border border-2 border-white rounded-circle p-1.5" style="width: 14px; height: 14px;" title="Toko Tutup"></span>
        <?php endif; ?>
    </div>
    
    <h6 class="fw-bold text-dark m-0" style="font-size: 15px;"><?= htmlspecialchars($store['name'] ?? 'Nama Toko') ?></h6>
    <span class="badge bg-danger-subtle text-danger rounded-pill px-2.5 py-1 my-1.5 fw-bold" style="font-size: 10px;">
        <i class="bi bi-shop me-1"></i> MITRA RESMI CICALENGKAGO
    </span>

    <div class="p-2.5 bg-light rounded-3 text-start small mt-2 border" style="font-size: 11.5px;">
        <div class="mb-1 text-muted"><i class="bi bi-person-fill text-danger me-1.5"></i> <strong>Pemilik:</strong> <?= htmlspecialchars($user['name'] ?? '-') ?></div>
        <div class="mb-1 text-muted"><i class="bi bi-envelope-fill text-danger me-1.5"></i> <strong>Email:</strong> <?= htmlspecialchars($user['email'] ?? '-') ?></div>
        <div class="mb-1 text-muted"><i class="bi bi-phone-fill text-danger me-1.5"></i> <strong>No. HP:</strong> <?= htmlspecialchars($user['phone'] ?? '-') ?></div>
        <div class="mb-0 text-muted"><i class="bi bi-geo-alt-fill text-danger me-1.5"></i> <strong>Alamat:</strong> <?= htmlspecialchars($store['address'] ?? '-') ?></div>
    </div>
</div>

<!-- Edit Settings Form -->
<div class="card border-0 shadow-sm rounded-4 p-3.5 bg-white mb-4">
    <div class="d-flex align-items-center justify-content-between border-bottom pb-2.5 mb-3">
        <h6 class="fw-bold text-dark m-0" style="font-size: 13.5px;">
            <i class="bi bi-sliders text-danger me-1"></i> Pengaturan Toko & Pemilik
        </h6>
        <span class="badge bg-light text-muted border px-2 py-0.5" style="font-size: 10px;">Mitra Merchant</span>
    </div>

    <form action="<?= $baseUrl ?>/vendor/profile/update" method="POST" enctype="multipart/form-data">
        <!-- 1. Owner Profile -->
        <h6 class="fw-bold text-dark mb-2 pb-1 border-bottom" style="font-size: 12.5px;">
            <i class="bi bi-person-circle text-primary me-1"></i> 1. Informasi Akun Pemilik
        </h6>

        <!-- Foto Profil Pemilik -->
        <div class="mb-3">
            <label class="form-label small fw-bold text-dark">Foto Profil Pemilik</label>
            <div class="d-flex align-items-center gap-2.5 p-2.5 bg-light rounded-3 border">
                <img id="vendor-avatar-preview" src="<?= $baseUrl ?>/<?= htmlspecialchars($user['avatar'] ?? 'assets/images/users/default.png') ?>" alt="Preview" class="rounded-circle border border-2 border-danger" style="width: 50px; height: 50px; object-fit: cover;">
                <div class="flex-grow-1">
                    <input type="file" name="avatar" class="form-control form-control-sm rounded-3" accept="image/*" onchange="previewImg(this, 'vendor-avatar-preview')">
                </div>
            </div>
        </div>

        <div class="mb-2.5">
            <label class="form-label small fw-bold text-dark">Nama Pemilik Toko <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control form-control-sm rounded-3" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
        </div>

        <div class="mb-2.5">
            <label class="form-label small fw-bold text-dark">No HP / WhatsApp Pemilik <span class="text-danger">*</span></label>
            <input type="text" name="phone" class="form-control form-control-sm rounded-3" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label small fw-bold text-dark">Alamat Email <span class="text-danger">*</span></label>
            <input type="email" name="email" class="form-control form-control-sm rounded-3" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
            <div class="text-muted mt-1" style="font-size: 10px;">
                <i class="bi bi-shield-check text-warning me-0.5"></i> Perubahan email memerlukan verifikasi OTP.
            </div>
        </div>

        <!-- 2. Store Info -->
        <h6 class="fw-bold text-dark mb-2 pb-1 border-bottom mt-3" style="font-size: 12.5px;">
            <i class="bi bi-shop text-success me-1"></i> 2. Informasi Toko / Resto
        </h6>

        <!-- Logo Toko -->
        <div class="mb-3">
            <label class="form-label small fw-bold text-dark">Logo / Foto Profil Toko</label>
            <div class="d-flex align-items-center gap-2.5 p-2.5 bg-light rounded-3 border">
                <img id="store-logo-preview" src="<?= $baseUrl ?>/<?= htmlspecialchars($store['logo'] ?? 'assets/images/stores/default.jpg') ?>" alt="Preview" class="rounded-3 border border-2 border-danger" style="width: 50px; height: 50px; object-fit: cover;">
                <div class="flex-grow-1">
                    <input type="file" name="store_logo" class="form-control form-control-sm rounded-3" accept="image/*" onchange="previewImg(this, 'store-logo-preview')">
                </div>
            </div>
        </div>

        <div class="mb-2.5">
            <label class="form-label small fw-bold text-dark">Nama Toko / Resto</label>
            <input type="text" name="store_name" class="form-control form-control-sm rounded-3" value="<?= htmlspecialchars($store['name'] ?? '') ?>" required>
        </div>

        <div class="mb-2.5">
            <label class="form-label small fw-bold text-dark">No HP / Kontak Toko</label>
            <input type="text" name="store_phone" class="form-control form-control-sm rounded-3" value="<?= htmlspecialchars($store['phone'] ?? '') ?>">
        </div>

        <div class="mb-3">
            <label class="form-label small fw-bold text-dark">Alamat Lengkap Toko</label>
            <textarea name="store_address" class="form-control form-control-sm rounded-3" rows="2"><?= htmlspecialchars($store['address'] ?? '') ?></textarea>
        </div>

        <!-- 3. GPS Pinpoint Picker (Leaflet Map) -->
        <h6 class="fw-bold text-dark mb-2 pb-1 border-bottom mt-3" style="font-size: 12.5px;">
            <i class="bi bi-geo-alt-fill text-danger me-1"></i> 3. Titik Koordinat GPS Toko
        </h6>

        <div class="mb-2">
            <div class="d-flex align-items-center justify-content-between mb-1.5">
                <span class="small text-muted" style="font-size: 11px;">Geser pin merah ke lokasi toko Anda:</span>
                <button type="button" onclick="detectCurrentStoreGps()" class="btn btn-outline-danger btn-sm rounded-pill px-2.5 py-0.5" style="font-size: 10.5px;">
                    <i class="bi bi-crosshair me-1"></i> Deteksi GPS Saya
                </button>
            </div>

            <div id="profile-store-picker-map" style="width: 100%; height: 180px; border-radius: 12px; border: 1px solid #e2e8f0;"></div>

            <div class="row g-2 mt-1">
                <div class="col-6">
                    <label class="form-label text-muted" style="font-size: 10px;">Latitude</label>
                    <input type="text" id="storeLatInput" name="latitude" class="form-control form-control-sm rounded-3 bg-light" value="<?= (float)($store['latitude'] ?? -6.9835) ?>" readonly>
                </div>
                <div class="col-6">
                    <label class="form-label text-muted" style="font-size: 10px;">Longitude</label>
                    <input type="text" id="storeLngInput" name="longitude" class="form-control form-control-sm rounded-3 bg-light" value="<?= (float)($store['longitude'] ?? 107.8335) ?>" readonly>
                </div>
            </div>
        </div>

        <!-- 4. Password (Optional) -->
        <h6 class="fw-bold text-dark mb-2 pb-1 border-bottom mt-3" style="font-size: 12.5px;">
            <i class="bi bi-key text-warning me-1"></i> 4. Ganti Kata Sandi (Opsional)
        </h6>

        <div class="mb-2">
            <label class="form-label text-muted small" style="font-size: 11px;">Kata Sandi Saat Ini</label>
            <input type="password" name="current_password" class="form-control form-control-sm rounded-3" placeholder="Masukkan jika ingin ganti password">
        </div>

        <div class="row g-2 mb-3">
            <div class="col-6">
                <label class="form-label text-muted small" style="font-size: 11px;">Password Baru</label>
                <input type="password" name="new_password" class="form-control form-control-sm rounded-3" placeholder="Minimal 6 karakter">
            </div>
            <div class="col-6">
                <label class="form-label text-muted small" style="font-size: 11px;">Ulangi Password</label>
                <input type="password" name="confirm_password" class="form-control form-control-sm rounded-3" placeholder="Konfirmasi password">
            </div>
        </div>

        <!-- Submit Button -->
        <button type="submit" class="btn btn-danger w-100 py-2.5 rounded-pill fw-bold shadow-sm" style="background:#EE2737; font-size: 13.5px; border:none;">
            <i class="bi bi-check2-circle me-1"></i> Simpan Perubahan Toko
        </button>
    </form>
</div>

<script>
let pickerMap, pickerMarker;

document.addEventListener('DOMContentLoaded', () => {
    const initLat = parseFloat(document.getElementById('storeLatInput').value) || -6.9835;
    const initLng = parseFloat(document.getElementById('storeLngInput').value) || 107.8335;

    pickerMap = L.map('profile-store-picker-map', { zoomControl: true }).setView([initLat, initLng], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap'
    }).addTo(pickerMap);

    const redIcon = L.divIcon({
        className: 'custom-pin',
        html: '<div style="background:#EE2737;color:white;width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:3px solid white;box-shadow:0 3px 8px rgba(0,0,0,0.3);"><i class="bi bi-shop fs-6"></i></div>',
        iconSize: [32, 32],
        iconAnchor: [16, 16]
    });

    pickerMarker = L.marker([initLat, initLng], { icon: redIcon, draggable: true }).addTo(pickerMap);

    pickerMarker.on('dragend', function(e) {
        const pos = pickerMarker.getLatLng();
        document.getElementById('storeLatInput').value = pos.lat.toFixed(8);
        document.getElementById('storeLngInput').value = pos.lng.toFixed(8);
    });

    pickerMap.on('click', function(e) {
        pickerMarker.setLatLng(e.latlng);
        document.getElementById('storeLatInput').value = e.latlng.lat.toFixed(8);
        document.getElementById('storeLngInput').value = e.latlng.lng.toFixed(8);
    });
});

function detectCurrentStoreGps() {
    if ('geolocation' in navigator) {
        navigator.geolocation.getCurrentPosition((pos) => {
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;
            pickerMap.setView([lat, lng], 17);
            pickerMarker.setLatLng([lat, lng]);
            document.getElementById('storeLatInput').value = lat.toFixed(8);
            document.getElementById('storeLngInput').value = lng.toFixed(8);
            Swal.fire({
                title: 'GPS Terdeteksi',
                text: 'Titik koordinat toko Anda berhasil disesuaikan dengan GPS perangkat.',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            });
        }, (err) => {
            Swal.fire('GPS Tidak Aktif', 'Pastikan izin lokasi GPS diaktifkan di browser HP Anda.', 'warning');
        }, { enableHighAccuracy: true });
    }
}

function previewImg(input, targetId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById(targetId).src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

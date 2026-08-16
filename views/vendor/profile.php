<!-- Store & Owner Summary Card -->
<div class="card border-0 shadow-sm rounded-4 bg-white text-center mb-3 overflow-hidden">
    <div class="p-3.5 text-white" style="background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%);">
        <div class="position-relative d-inline-block mx-auto mt-2 mb-2">
            <img src="<?= $baseUrl ?>/<?= htmlspecialchars($store['logo'] ?? 'assets/images/stores/default.jpg') ?>" alt="Store Logo" class="rounded-circle border border-3 border-white shadow" style="width: 78px; height: 78px; object-fit: cover; background: #ffffff;">
            <?php if ($store && $store['is_open']): ?>
                <span class="position-absolute bottom-0 end-0 bg-success border border-2 border-white rounded-circle" style="width: 16px; height: 16px;" title="Toko Buka"></span>
            <?php else: ?>
                <span class="position-absolute bottom-0 end-0 bg-danger border border-2 border-white rounded-circle" style="width: 16px; height: 16px;" title="Toko Tutup"></span>
            <?php endif; ?>
        </div>
        
        <h5 class="fw-bold text-white m-0" style="font-size: 16px; letter-spacing: -0.3px;"><?= htmlspecialchars($store['name'] ?? 'Nama Toko') ?></h5>
        <span class="badge bg-danger text-white rounded-pill px-2.5 py-1 my-1.5 fw-bold" style="font-size: 9.5px; letter-spacing: 0.3px;">
            <i class="bi bi-patch-check-fill me-1"></i> MITRA RESMI CICALENGKAGO
        </span>
    </div>

    <div class="p-3">
        <div class="p-2.5 bg-light rounded-3 text-start small border" style="font-size: 11.5px;">
            <div class="mb-1.5 text-muted d-flex align-items-center"><i class="bi bi-person-fill text-danger me-2 fs-6"></i> <span><strong>Pemilik:</strong> <?= htmlspecialchars($user['name'] ?? '-') ?></span></div>
            <div class="mb-1.5 text-muted d-flex align-items-center"><i class="bi bi-envelope-fill text-danger me-2 fs-6"></i> <span><strong>Email:</strong> <?= htmlspecialchars($user['email'] ?? '-') ?></span></div>
            <div class="mb-1.5 text-muted d-flex align-items-center"><i class="bi bi-phone-fill text-danger me-2 fs-6"></i> <span><strong>No. HP:</strong> <?= htmlspecialchars($user['phone'] ?? '-') ?></span></div>
            <div class="mb-0 text-muted d-flex align-items-start"><i class="bi bi-geo-alt-fill text-danger me-2 fs-6 mt-0.5"></i> <span><strong>Alamat:</strong> <?= htmlspecialchars($store['address'] ?? '-') ?></span></div>
        </div>
    </div>
</div>

<!-- Edit Settings Form -->
<div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-4">
    <div class="d-flex align-items-center justify-content-between border-bottom pb-2.5 mb-3">
        <h6 class="fw-bold text-dark m-0" style="font-size: 13.5px;">
            <i class="bi bi-sliders text-danger me-1.5"></i> Pengaturan Toko & Pemilik
        </h6>
        <span class="badge bg-light text-muted border px-2 py-0.5" style="font-size: 10px;">Mitra Merchant</span>
    </div>

    <form action="<?= $baseUrl ?>/vendor/profile/update" method="POST" enctype="multipart/form-data">
        <!-- 1. Owner Profile -->
        <h6 class="fw-bold text-dark mb-2.5 pb-1 border-bottom d-flex align-items-center gap-1.5" style="font-size: 12.5px;">
            <i class="bi bi-person-circle text-primary"></i> 1. Informasi Akun Pemilik
        </h6>

        <!-- Foto Profil Pemilik -->
        <div class="mb-3">
            <label class="form-label small fw-bold text-dark mb-1" for="vendor_avatar" style="font-size: 11.5px;">Foto Profil Pemilik</label>
            <div class="d-flex align-items-center gap-3 p-2.5 bg-light rounded-3 border">
                <img id="vendor-avatar-preview" src="<?= $baseUrl ?>/<?= htmlspecialchars($user['avatar'] ?? 'assets/images/users/default.png') ?>" alt="Preview" class="rounded-circle border border-2 border-danger shadow-xs" style="width: 52px; height: 52px; object-fit: cover; flex-shrink: 0;">
                <div class="flex-grow-1">
                    <label class="btn btn-outline-dark btn-sm rounded-pill fw-semibold mb-1" style="font-size: 11px; cursor: pointer;">
                        <i class="bi bi-camera-fill me-1"></i> Pilih Foto Profil
                        <input type="file" name="avatar" id="vendor_avatar" class="d-none" accept="image/*" onchange="previewImg(this, 'vendor-avatar-preview')">
                    </label>
                    <div class="text-muted" style="font-size: 10px;">Format JPG, PNG maksimal 2MB</div>
                </div>
            </div>
        </div>

        <div class="mb-2.5">
            <label class="form-label small fw-bold text-dark mb-1" for="vendor_name" style="font-size: 11.5px;">Nama Pemilik Toko <span class="text-danger">*</span></label>
            <input type="text" name="name" id="vendor_name" class="form-control form-control-sm rounded-3" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required style="padding: 8px 12px; font-size: 12.5px;">
        </div>

        <div class="mb-2.5">
            <label class="form-label small fw-bold text-dark mb-1" for="vendor_phone" style="font-size: 11.5px;">No HP / WhatsApp Pemilik <span class="text-danger">*</span></label>
            <input type="text" name="phone" id="vendor_phone" class="form-control form-control-sm rounded-3" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" required style="padding: 8px 12px; font-size: 12.5px;">
        </div>

        <div class="mb-3">
            <label class="form-label small fw-bold text-dark mb-1" for="vendor_email" style="font-size: 11.5px;">Alamat Email <span class="text-danger">*</span></label>
            <input type="email" name="email" id="vendor_email" class="form-control form-control-sm rounded-3" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required style="padding: 8px 12px; font-size: 12.5px;">
            <div class="text-muted mt-1" style="font-size: 10px;">
                <i class="bi bi-shield-check text-warning me-0.5"></i> Perubahan email memerlukan verifikasi OTP.
            </div>
        </div>

        <!-- 2. Store Info -->
        <h6 class="fw-bold text-dark mb-2.5 pb-1 border-bottom mt-3.5 d-flex align-items-center gap-1.5" style="font-size: 12.5px;">
            <i class="bi bi-shop text-success"></i> 2. Informasi Toko / Resto
        </h6>

        <!-- Logo Toko -->
        <div class="mb-3">
            <label class="form-label small fw-bold text-dark mb-1" for="store_logo" style="font-size: 11.5px;">Logo / Foto Profil Toko</label>
            <div class="d-flex align-items-center gap-3 p-2.5 bg-light rounded-3 border">
                <img id="store-logo-preview" src="<?= $baseUrl ?>/<?= htmlspecialchars($store['logo'] ?? 'assets/images/stores/default.jpg') ?>" alt="Preview" class="rounded-3 border border-2 border-danger shadow-xs" style="width: 52px; height: 52px; object-fit: cover; flex-shrink: 0;">
                <div class="flex-grow-1">
                    <label class="btn btn-outline-danger btn-sm rounded-pill fw-semibold mb-1" style="font-size: 11px; cursor: pointer;">
                        <i class="bi bi-image-fill me-1"></i> Pilih Logo Toko
                        <input type="file" name="store_logo" id="store_logo" class="d-none" accept="image/*" onchange="previewImg(this, 'store-logo-preview')">
                    </label>
                    <div class="text-muted" style="font-size: 10px;">Foto logo restoran / banner etalase</div>
                </div>
            </div>
        </div>

        <div class="mb-2.5">
            <label class="form-label small fw-bold text-dark mb-1" for="store_name" style="font-size: 11.5px;">Nama Toko / Resto <span class="text-danger">*</span></label>
            <input type="text" name="store_name" id="store_name" class="form-control form-control-sm rounded-3" value="<?= htmlspecialchars($store['name'] ?? '') ?>" required style="padding: 8px 12px; font-size: 12.5px;">
        </div>

        <div class="mb-2.5">
            <label class="form-label small fw-bold text-dark mb-1" for="store_phone" style="font-size: 11.5px;">No HP / Kontak Toko</label>
            <input type="text" name="store_phone" id="store_phone" class="form-control form-control-sm rounded-3" value="<?= htmlspecialchars($store['phone'] ?? '') ?>" style="padding: 8px 12px; font-size: 12.5px;">
        </div>

        <div class="mb-3">
            <label class="form-label small fw-bold text-dark mb-1" for="store_address" style="font-size: 11.5px;">Alamat Lengkap Toko</label>
            <textarea name="store_address" id="store_address" class="form-control form-control-sm rounded-3" rows="2" style="padding: 8px 12px; font-size: 12.5px;"><?= htmlspecialchars($store['address'] ?? '') ?></textarea>
        </div>

        <!-- 3. GPS Pinpoint Picker (Leaflet Map) -->
        <h6 class="fw-bold text-dark mb-2 pb-1 border-bottom mt-3.5 d-flex align-items-center gap-1.5" style="font-size: 12.5px;">
            <i class="bi bi-geo-alt-fill text-danger"></i> 3. Titik Koordinat GPS Toko
        </h6>

        <div class="mb-2">
            <div class="d-flex align-items-center justify-content-between mb-1.5">
                <span class="small text-muted" style="font-size: 11px;">Geser pin merah ke lokasi toko:</span>
                <button type="button" onclick="detectCurrentStoreGps()" class="btn btn-outline-danger btn-sm rounded-pill px-2.5 py-0.5" style="font-size: 10.5px;">
                    <i class="bi bi-crosshair me-1"></i> Deteksi GPS Saya
                </button>
            </div>

            <div id="profile-store-picker-map" style="width: 100%; height: 180px; border-radius: 12px; border: 1px solid #e2e8f0;"></div>

            <div class="row g-2 mt-1">
                <div class="col-6">
                    <label class="form-label text-muted mb-0.5" style="font-size: 10px;">Latitude</label>
                    <input type="text" id="storeLatInput" name="latitude" class="form-control form-control-sm rounded-3 bg-light" value="<?= (float)($store['latitude'] ?? -6.9835) ?>" readonly style="font-size: 11px;">
                </div>
                <div class="col-6">
                    <label class="form-label text-muted mb-0.5" style="font-size: 10px;">Longitude</label>
                    <input type="text" id="storeLngInput" name="longitude" class="form-control form-control-sm rounded-3 bg-light" value="<?= (float)($store['longitude'] ?? 107.8340) ?>" readonly style="font-size: 11px;">
                </div>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="mt-4 pt-2 border-top">
            <button type="submit" class="btn btn-danger w-100 rounded-pill py-2.5 fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2" style="background: #EE2737; font-size: 13px; border: none;">
                <i class="bi bi-floppy2-fill"></i> Simpan Perubahan Profil & Toko
            </button>
        </div>
    </form>
</div>

<script>
function previewImg(input, targetId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById(targetId).src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

let profileMap = null;
let profileMarker = null;

document.addEventListener('DOMContentLoaded', function() {
    initProfileStoreMap();
});

function initProfileStoreMap() {
    const lat = parseFloat(document.getElementById('storeLatInput').value) || -6.9835;
    const lng = parseFloat(document.getElementById('storeLngInput').value) || 107.8340;

    const mapContainer = document.getElementById('profile-store-picker-map');
    if (!mapContainer) return;

    profileMap = L.map('profile-store-picker-map').setView([lat, lng], 15);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap'
    }).addTo(profileMap);

    const storeIcon = L.divIcon({
        className: 'custom-store-pin',
        html: `<div style="background: #EE2737; color: white; width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(0,0,0,0.3); border: 2px solid white; font-size: 16px;"><i class="bi bi-shop"></i></div>`,
        iconSize: [34, 34],
        iconAnchor: [17, 17]
    });

    profileMarker = L.marker([lat, lng], {
        draggable: true,
        icon: storeIcon
    }).addTo(profileMap);

    profileMarker.on('dragend', function(e) {
        const pos = e.target.getLatLng();
        document.getElementById('storeLatInput').value = pos.lat.toFixed(7);
        document.getElementById('storeLngInput').value = pos.lng.toFixed(7);
    });

    profileMap.on('click', function(e) {
        profileMarker.setLatLng(e.latlng);
        document.getElementById('storeLatInput').value = e.latlng.lat.toFixed(7);
        document.getElementById('storeLngInput').value = e.latlng.lng.toFixed(7);
    });

    setTimeout(() => {
        profileMap.invalidateSize();
    }, 400);
}

function detectCurrentStoreGps() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                const lat = pos.coords.latitude;
                const lng = pos.coords.longitude;
                document.getElementById('storeLatInput').value = lat.toFixed(7);
                document.getElementById('storeLngInput').value = lng.toFixed(7);
                if (profileMap && profileMarker) {
                    profileMap.setView([lat, lng], 16);
                    profileMarker.setLatLng([lat, lng]);
                }
                Swal.fire({
                    icon: 'success',
                    title: 'Lokasi Terdeteksi',
                    text: 'Pin GPS berhasil disesuaikan dengan posisi perangkat Anda.',
                    timer: 1500,
                    showConfirmButton: false
                });
            },
            () => {
                Swal.fire({
                    icon: 'warning',
                    title: 'Izin Lokasi Ditolak',
                    text: 'Silakan aktifkan GPS atau geser pin peta secara manual.',
                    confirmButtonColor: '#EE2737'
                });
            }
        );
    }
}
</script>

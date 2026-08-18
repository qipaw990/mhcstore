<!-- Store & Owner Summary Card -->
<div class="vnd-card p-0 overflow-hidden mb-3">
    <div class="p-4 text-white text-center position-relative" style="background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%);">
        <div class="position-relative d-inline-block mx-auto mb-2">
            <img src="<?= $baseUrl ?>/<?= htmlspecialchars($store['logo'] ?? 'assets/images/stores/default.jpg') ?>" alt="Store Logo" class="rounded-circle border border-3 border-white shadow-sm" style="width: 78px; height: 78px; object-fit: cover; background: #ffffff;">
            <?php if ($store && $store['is_open']): ?>
                <span class="position-absolute bottom-0 end-0 bg-success border border-2 border-white rounded-circle" style="width: 14px; height: 14px;" title="Toko Buka"></span>
            <?php else: ?>
                <span class="position-absolute bottom-0 end-0 bg-danger border border-2 border-white rounded-circle" style="width: 14px; height: 14px;" title="Toko Tutup"></span>
            <?php endif; ?>
        </div>
        
        <h6 class="fw-bold text-white m-0" style="font-size: 15.5px; letter-spacing: -0.3px;"><?= htmlspecialchars($store['name'] ?? 'Nama Toko') ?></h6>
        <span class="badge bg-danger text-white rounded-pill px-3 py-1 my-1.5 fw-bold" style="font-size: 9.5px; letter-spacing: 0.3px;">
            <i class="bi bi-patch-check-fill me-1"></i> MITRA RESMI CICALENGKAGO
        </span>
    </div>

    <div class="p-3 bg-white">
        <div class="d-flex flex-column gap-2 p-3 bg-light rounded-3 border" style="font-size: 11.5px; border-color: #E2E8F0 !important;">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-person-fill text-danger fs-6 flex-shrink-0"></i>
                <span class="text-muted">Pemilik:</span>
                <strong class="text-dark ms-auto"><?= htmlspecialchars($user['name'] ?? '-') ?></strong>
            </div>
            <div class="d-flex align-items-center gap-2 border-top pt-2" style="border-color: #F1F5F9 !important;">
                <i class="bi bi-envelope-fill text-danger fs-6 flex-shrink-0"></i>
                <span class="text-muted">Email:</span>
                <strong class="text-dark ms-auto"><?= htmlspecialchars($user['email'] ?? '-') ?></strong>
            </div>
            <div class="d-flex align-items-center gap-2 border-top pt-2" style="border-color: #F1F5F9 !important;">
                <i class="bi bi-phone-fill text-danger fs-6 flex-shrink-0"></i>
                <span class="text-muted">No. HP:</span>
                <strong class="text-dark ms-auto"><?= htmlspecialchars($user['phone'] ?? '-') ?></strong>
            </div>
            <div class="d-flex align-items-start gap-2 border-top pt-2" style="border-color: #F1F5F9 !important;">
                <i class="bi bi-geo-alt-fill text-danger fs-6 flex-shrink-0 mt-0.5"></i>
                <span class="text-muted">Alamat:</span>
                <strong class="text-dark text-end ms-auto text-wrap" style="max-width: 200px;"><?= htmlspecialchars($store['address'] ?? '-') ?></strong>
            </div>
        </div>
    </div>
</div>

<!-- Edit Settings Form -->
<div class="vnd-card mb-4">
    <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-3">
        <h6 class="vnd-card-title m-0">
            <i class="bi bi-sliders text-danger"></i> Pengaturan Toko & Pemilik
        </h6>
        <span class="badge bg-light text-muted border px-2 py-1 rounded-pill" style="font-size: 9.5px; font-weight: 600;">Mitra Merchant</span>
    </div>

    <form action="<?= $baseUrl ?>/vendor/profile/update" method="POST" enctype="multipart/form-data">
        <!-- 1. Owner Profile -->
        <h6 class="fw-bold text-dark mb-3 pb-1.5 border-bottom d-flex align-items-center gap-1.5" style="font-size: 12.5px;">
            <i class="bi bi-person-circle text-primary"></i> 1. Informasi Akun Pemilik
        </h6>

        <!-- Foto Profil Pemilik -->
        <div class="mb-3">
            <label class="form-label fw-bold text-dark mb-1" for="vendor_avatar" style="font-size: 11.5px;">Foto Profil Pemilik</label>
            <div class="d-flex align-items-center gap-3 p-3 bg-light rounded-3 border" style="border-color: #E2E8F0 !important;">
                <img id="vendor-avatar-preview" src="<?= $baseUrl ?>/<?= htmlspecialchars($user['avatar'] ?? 'assets/images/users/default.png') ?>" alt="Preview" class="rounded-circle border border-2 border-danger shadow-2xs flex-shrink-0" style="width: 54px; height: 54px; object-fit: cover;">
                <div class="flex-grow-1">
                    <label class="btn btn-outline-dark btn-sm rounded-pill fw-semibold mb-1" style="font-size: 10.5px; cursor: pointer;">
                        <i class="bi bi-camera-fill me-1"></i> Pilih Foto Profil
                        <input type="file" name="avatar" id="vendor_avatar" class="d-none" accept="image/*" onchange="previewImg(this, 'vendor-avatar-preview')">
                    </label>
                    <div class="text-muted" style="font-size: 10px;">Format JPG, PNG maksimal 2MB</div>
                </div>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold text-dark mb-1" for="vendor_name" style="font-size: 11.5px;">Nama Pemilik Toko <span class="text-danger">*</span></label>
            <input type="text" name="name" id="vendor_name" class="form-control form-control-sm vnd-input" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold text-dark mb-1" for="vendor_phone" style="font-size: 11.5px;">No HP / WhatsApp Pemilik <span class="text-danger">*</span></label>
            <input type="text" name="phone" id="vendor_phone" class="form-control form-control-sm vnd-input" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold text-dark mb-1" for="vendor_email" style="font-size: 11.5px;">Alamat Email <span class="text-danger">*</span></label>
            <input type="email" name="email" id="vendor_email" class="form-control form-control-sm vnd-input" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
            <div class="text-muted mt-1" style="font-size: 10px;">
                <i class="bi bi-shield-check text-warning me-0.5"></i> Perubahan email memerlukan verifikasi OTP.
            </div>
        </div>

        <!-- 2. Store Info -->
        <h6 class="fw-bold text-dark mb-3 pb-1.5 border-bottom mt-4 d-flex align-items-center gap-1.5" style="font-size: 12.5px;">
            <i class="bi bi-shop text-success"></i> 2. Informasi Toko / Resto
        </h6>

        <!-- Logo Toko -->
        <div class="mb-3">
            <label class="form-label fw-bold text-dark mb-1" for="store_logo" style="font-size: 11.5px;">Logo / Foto Profil Toko</label>
            <div class="d-flex align-items-center gap-3 p-3 bg-light rounded-3 border" style="border-color: #E2E8F0 !important;">
                <img id="store-logo-preview" src="<?= $baseUrl ?>/<?= htmlspecialchars($store['logo'] ?? 'assets/images/stores/default.jpg') ?>" alt="Preview" class="rounded-3 border border-2 border-danger shadow-2xs flex-shrink-0" style="width: 54px; height: 54px; object-fit: cover;">
                <div class="flex-grow-1">
                    <label class="btn btn-outline-danger btn-sm rounded-pill fw-semibold mb-1" style="font-size: 10.5px; cursor: pointer;">
                        <i class="bi bi-image-fill me-1"></i> Pilih Logo Toko
                        <input type="file" name="store_logo" id="store_logo" class="d-none" accept="image/*" onchange="previewImg(this, 'store-logo-preview')">
                    </label>
                    <div class="text-muted" style="font-size: 10px;">Foto logo restoran / banner etalase</div>
                </div>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold text-dark mb-1" for="store_name" style="font-size: 11.5px;">Nama Toko / Resto <span class="text-danger">*</span></label>
            <input type="text" name="store_name" id="store_name" class="form-control form-control-sm vnd-input" value="<?= htmlspecialchars($store['name'] ?? '') ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold text-dark mb-1" for="store_phone" style="font-size: 11.5px;">No HP / Kontak Toko</label>
            <input type="text" name="store_phone" id="store_phone" class="form-control form-control-sm vnd-input" value="<?= htmlspecialchars($store['phone'] ?? '') ?>">
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold text-dark mb-1" for="store_address" style="font-size: 11.5px;">Alamat Lengkap Toko</label>
            <textarea name="store_address" id="store_address" class="form-control form-control-sm vnd-input" rows="2"><?= htmlspecialchars($store['address'] ?? '') ?></textarea>
        </div>

        <!-- 3. GPS Pinpoint Picker (Leaflet Map) -->
        <h6 class="fw-bold text-dark mb-3 pb-1.5 border-bottom mt-4 d-flex align-items-center gap-1.5" style="font-size: 12.5px;">
            <i class="bi bi-geo-alt-fill text-danger"></i> 3. Titik Koordinat GPS Toko
        </h6>

        <div class="mb-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="small text-muted" style="font-size: 10.5px;">Geser pin merah ke lokasi toko:</span>
                <button type="button" onclick="detectCurrentStoreGps()" class="btn btn-outline-danger btn-sm rounded-pill px-2.5 py-0.5" style="font-size: 10px;">
                    <i class="bi bi-crosshair me-1"></i> Deteksi GPS Saya
                </button>
            </div>

            <div id="profile-store-picker-map" style="width: 100%; height: 180px; border-radius: 12px; border: 1px solid #CBD5E1;"></div>

            <div class="row g-2.5 mt-1">
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
        <div class="mt-4 pt-3 border-top">
            <button type="submit" class="vnd-action-btn red w-100 py-2.5" style="font-size: 13px; border-radius: 12px;">
                <i class="bi bi-floppy2-fill me-1" style="font-size: 15px;"></i> Simpan Perubahan Profil & Toko
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

    profileMap = L.map('profile-store-picker-map', { zoomControl: false, attributionControl: false }).setView([lat, lng], 15);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(profileMap);

    const storeSvg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 46" width="32" height="46">
      <defs>
        <linearGradient id="vpsg" x1="0%" y1="0%" x2="100%" y2="100%">
          <stop offset="0%" stop-color="#f87171"/>
          <stop offset="100%" stop-color="#b91c1c"/>
        </linearGradient>
      </defs>
      <path d="M16 2C9 2 3 8 3 15c0 10 13 29 13 29S29 25 29 15C29 8 23 2 16 2z" fill="url(#vpsg)" stroke="white" stroke-width="2"/>
      <path d="M9 14 L9 12 Q9 10 16 10 Q23 10 23 12 L23 14 Q19.5 17 16 16 Q12.5 17 9 14z" fill="white"/>
      <rect x="11" y="14.5" width="10" height="6" rx="0.5" fill="white" opacity="0.25"/>
      <rect x="13" y="15" width="6" height="5.5" fill="white"/>
      <rect x="14.5" y="16" width="3" height="4.5" fill="#b91c1c"/>
    </svg>`;
    const storeIcon = L.icon({
        iconUrl: 'data:image/svg+xml,' + encodeURIComponent(storeSvg),
        iconSize: [32, 46],
        iconAnchor: [16, 46],
        popupAnchor: [0, -46]
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
                showVendorToast('Pin GPS berhasil disesuaikan dengan posisi Anda.', 'success');
            },
            () => {
                showVendorToast('Izin lokasi ditolak. Geser pin peta secara manual.', 'warning');
            }
        );
    }
}
</script>

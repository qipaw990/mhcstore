<div class="border-bottom bg-white d-flex align-items-center gap-2.5 sticky-top app-subpage-header px-3 py-3">
    <a href="<?= $baseUrl ?>/cart" class="btn btn-light btn-sm rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 36px; height: 36px; border: 1px solid #E2E8F0; background: #F8FAFC;"><i class="bi bi-arrow-left text-dark" style="font-size: 15px;"></i></a>
    <h6 class="fw-bold m-0 text-dark" style="font-size: 15px; letter-spacing: -0.3px;">Konfirmasi & Pembayaran</h6>
</div>

<form id="checkoutForm" onsubmit="handlePlaceOrder(event)" class="px-3 py-3">
    <!-- Map Location Picker Card -->
    <div class="p-3 bg-white border shadow-2xs mb-3 overflow-hidden" style="border-radius: 16px; border-color: #E2E8F0 !important; padding: 14px 16px !important;">
        <div class="d-flex align-items-center justify-content-between mb-2 gap-2">
            <h6 class="fw-bold m-0 text-dark text-truncate" style="font-size: 12.5px;"><i class="bi bi-geo-alt-fill text-danger me-1.5"></i> Titik Lokasi Antar</h6>
            <button type="button" onclick="getCurrentLocation()" class="btn btn-sm rounded-pill py-1 px-3 fw-bold text-white shadow-2xs flex-shrink-0" style="background: linear-gradient(135deg, #EE2737, #C61524); font-size: 10.5px;">
                <i class="bi bi-crosshair me-1"></i> GPS Saya
            </button>
        </div>
        <div class="text-muted mb-2.5" style="font-size: 10.5px; line-height: 1.4;">
            <i class="bi bi-lock-fill text-muted me-1"></i> Titik lokasi pengantaran terkunci presisi sesuai posisi GPS / alamat Anda.
        </div>
        <div id="checkout-map" style="width: 100%; height: 180px; border-radius: 12px;" class="border shadow-2xs mb-2.5"></div>
        <div class="d-flex align-items-center justify-content-between gap-2 overflow-hidden">
            <span class="badge bg-light text-dark border py-1.5 px-2.5 rounded-pill text-truncate" id="distance-badge" style="font-size: 9.5px;"><i class="bi bi-signpost-2 me-1 text-primary"></i> Est. Jarak: 1.5 Km</span>
            <span class="badge bg-danger-subtle text-danger py-1.5 px-2.5 rounded-pill text-truncate flex-shrink-0" id="zone-badge" style="font-size: 9.5px;"><i class="bi bi-shield-check me-1"></i> Tercover Zona Cicalengka</span>
        </div>

        <input type="hidden" name="latitude" id="input-lat" value="-6.9855">
        <input type="hidden" name="longitude" id="input-lng" value="107.8350">
        <input type="hidden" name="distance_km" id="input-distance" value="1.5">
    </div>

    <!-- Delivery Address Details -->
    <div class="p-3 bg-white border shadow-2xs mb-3 overflow-hidden" style="border-radius: 16px; border-color: #E2E8F0 !important; padding: 14px 16px !important;">
        <div class="mb-2.5">
            <label class="form-label text-dark mb-1" style="font-size: 10.5px; font-weight: 700;">Alamat Lengkap / Patokan Rumah</label>
            <textarea name="address" id="input-address" class="form-control form-control-sm bg-light" rows="2" style="font-size: 11.5px; border-radius: 10px; border-color: #E2E8F0;" required placeholder="Jl. Raya Cicalengka No. 45 (Dekat Stasiun / Rumah Cat Hijau)">Jl. Cicalengka Raya No. 45, RT 02/03</textarea>
        </div>
        <div class="row g-2">
            <div class="col-6">
                <label class="form-label text-dark mb-1" for="contact_name" style="font-size: 10.5px; font-weight: 700;">Nama Penerima</label>
                <input type="text" name="contact_name" id="contact_name" class="form-control form-control-sm bg-light" style="font-size: 11.5px; border-radius: 10px; border-color: #E2E8F0;" value="<?= htmlspecialchars($_SESSION['user']['name'] ?? '') ?>" required>
            </div>
            <div class="col-6">
                <label class="form-label text-dark mb-1" for="contact_phone" style="font-size: 10.5px; font-weight: 700;">No. WhatsApp</label>
                <input type="text" name="contact_phone" id="contact_phone" class="form-control form-control-sm bg-light" style="font-size: 11.5px; border-radius: 10px; border-color: #E2E8F0;" value="<?= htmlspecialchars($_SESSION['user']['phone'] ?? '') ?>" required>
            </div>
        </div>
    </div>

    <!-- Payment Method Selector Card -->
    <div class="bg-white border shadow-2xs mb-3.5 overflow-hidden" style="border-radius: 16px; border-color: #E2E8F0 !important; padding: 14px 16px !important; margin-bottom: 16px !important;">
        <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom" style="border-color: #F1F5F9 !important;">
            <h6 class="fw-bold m-0 text-dark" style="font-size: 13px;"><i class="bi bi-wallet2 text-danger me-2"></i>Metode Pembayaran</h6>
            <span class="text-muted fw-normal" style="font-size: 10.5px;">Pilih salah satu</span>
        </div>

        <div class="payment-option-list">
            <!-- CicalengkaPay Digital Wallet -->
            <label class="payment-option <?= ((float)$wallet['balance'] >= (float)$cart_data['subtotal']) ? 'border-danger bg-danger-subtle active' : 'opacity-75' ?>" id="label_pay_wallet">
                <div class="d-flex align-items-center min-w-0 flex-grow-1">
                    <input type="radio" name="payment_method" id="pay_wallet" value="wallet" onchange="updatePaymentCardStyles()" <?= ((float)$wallet['balance'] >= (float)$cart_data['subtotal']) ? 'checked' : 'disabled' ?>>
                    <div class="min-w-0 flex-grow-1">
                        <div class="fw-bold text-dark text-truncate" style="font-size: 12px;">
                            <span style="color:#EE2737; font-weight:800;">CicalengkaPay</span>
                            <span class="text-muted fw-normal ms-1" style="font-size: 10.5px;">(Saldo Digital)</span>
                        </div>
                        <div class="text-muted mt-0.5 text-truncate" style="font-size: 10.5px;">Saldo: <?= format_rupiah($wallet['balance'] ?? 0) ?></div>
                    </div>
                </div>
                <?php if ((float)$wallet['balance'] < (float)$cart_data['subtotal']): ?>
                    <span class="badge bg-warning text-dark flex-shrink-0 ms-2">Kurang</span>
                <?php else: ?>
                    <span class="badge text-white flex-shrink-0 ms-2" style="background:#EE2737 !important;">Tersedia</span>
                <?php endif; ?>
            </label>

            <!-- Midtrans Online Payment (QRIS / VA / E-Wallet) -->
            <label class="payment-option" id="label_pay_midtrans">
                <div class="d-flex align-items-center min-w-0 flex-grow-1">
                    <input type="radio" name="payment_method" id="pay_midtrans" value="midtrans" onchange="updatePaymentCardStyles()">
                    <div class="min-w-0 flex-grow-1">
                        <div class="fw-bold text-dark text-truncate d-flex align-items-center gap-1.5" style="font-size: 12px;">
                            <span>Bayar Online (Midtrans)</span>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 9px; padding: 2px 6px;">Otomatis</span>
                        </div>
                        <div class="text-muted mt-0.5 text-truncate" style="font-size: 10.5px;">QRIS, GoPay, ShopeePay, VA Bank</div>
                    </div>
                </div>
                <div class="d-flex align-items-center flex-shrink-0 ms-2">
                    <span class="badge text-white px-2.5 py-1" style="background: #002B49; font-size: 9.5px; font-weight: 700; border-radius: 6px;">MIDTRANS</span>
                </div>
            </label>

            <!-- COD (Cash on Delivery) -->
            <label class="payment-option <?= ((float)$wallet['balance'] < (float)$cart_data['subtotal']) ? 'border-danger bg-danger-subtle active' : '' ?>" id="label_pay_cod">
                <div class="d-flex align-items-center min-w-0 flex-grow-1">
                    <input type="radio" name="payment_method" id="pay_cod" value="cod" onchange="updatePaymentCardStyles()" <?= ((float)$wallet['balance'] < (float)$cart_data['subtotal']) ? 'checked' : '' ?>>
                    <div class="min-w-0 flex-grow-1">
                        <div class="fw-bold text-dark text-truncate" style="font-size: 12px;">Tunai saat Tiba (COD)</div>
                        <div class="text-muted mt-0.5 text-truncate" style="font-size: 10.5px;">Bayar langsung ke kurir motor</div>
                    </div>
                </div>
                <i class="bi bi-cash-coin text-success fs-5 flex-shrink-0 ms-2"></i>
            </label>
        </div>
    </div>

    <!-- Voucher / Coupon Code -->
    <div class="p-3 bg-white border shadow-2xs mb-3.5 overflow-hidden" style="border-radius: 16px; border-color: #E2E8F0 !important; padding: 14px 16px !important; margin-bottom: 16px !important;">
        <h6 class="fw-bold mb-2.5 text-dark d-flex align-items-center" style="font-size: 12px;"><i class="bi bi-percent text-warning me-2"></i>Promo & Kupon</h6>
        <div class="input-group input-group-sm">
            <input type="text" name="coupon_code" id="coupon_code" class="form-control bg-light" style="font-size: 11px; border-radius: 10px 0 0 10px; border-color: #E2E8F0;" placeholder="Kode promo (Contoh: CCGHEMAT)">
            <button type="button" class="btn text-white fw-bold px-3.5" style="background:#EE2737; font-size: 11px; border-radius: 0 10px 10px 0;" onclick="applyCouponPreview()">Pakai</button>
        </div>
    </div>

    <!-- Order Notes -->
    <div class="p-3 bg-white border shadow-2xs mb-3.5 overflow-hidden" style="border-radius: 16px; border-color: #E2E8F0 !important; padding: 14px 16px !important; margin-bottom: 16px !important;">
        <label class="form-label fw-bold mb-1.5 d-block text-dark" for="order_notes" style="font-size: 12px;"><i class="bi bi-chat-left-text me-2 text-muted"></i>Catatan Pesanan</label>
        <input type="text" name="order_notes" id="order_notes" class="form-control form-control-sm bg-light" style="font-size: 11px; border-radius: 10px; border-color: #E2E8F0;" placeholder="Contoh: Sambal dipisah, jangan pakai bawang goreng">
    </div>

    <!-- Order Breakdown Card -->
    <div class="p-3 bg-white border shadow-2xs mb-3.5 overflow-hidden" style="border-radius: 16px; border-color: #E2E8F0 !important; padding: 16px !important; margin-bottom: 16px !important;">
        <h6 class="fw-bold mb-3 text-dark d-flex align-items-center justify-content-between gap-2" style="font-size: 12.5px;">
            <span class="text-truncate"><i class="bi bi-receipt me-2 text-danger"></i>Rincian Tagihan</span>
            <span class="badge bg-light text-muted fw-normal px-2 py-1 rounded-pill flex-shrink-0" style="font-size: 9.5px;">Super Fast Delivery</span>
        </h6>
        <div class="d-flex justify-content-between text-muted mb-2 gap-2" style="font-size: 11px;">
            <span class="text-truncate">Subtotal Pesanan</span>
            <span class="text-dark fw-bold flex-shrink-0"><?= format_rupiah($cart_data['subtotal']) ?></span>
        </div>
        <div class="d-flex justify-content-between text-muted mb-2 gap-2" style="font-size: 11px;">
            <span class="text-truncate">Ongkir (<span id="fee-dist-text">1.5 Km</span>)</span>
            <span class="text-dark fw-bold flex-shrink-0" id="delivery-fee-display"><?= format_rupiah($cart_data['store']['delivery_fee']) ?></span>
        </div>
        <hr class="my-2.5" style="border-color: #F1F5F9;">
        <div class="d-flex justify-content-between align-items-center fw-bold gap-2" style="font-size: 13px;">
            <span class="text-dark text-truncate">Total Pembayaran</span>
            <span class="text-danger fs-6 flex-shrink-0" id="total-amount-display"><?= format_rupiah($cart_data['subtotal'] + $cart_data['store']['delivery_fee']) ?></span>
        </div>
    </div>

    <!-- Submit Order Button -->
    <button type="submit" id="btnPlaceOrder" class="btn btn-gojek-green w-100 py-2.5 mb-3" style="background: linear-gradient(135deg, #EE2737, #C61524) !important; color:#FFFFFF !important; border-radius:9999px; font-weight:800; font-size:12.5px; box-shadow:0 4px 14px rgba(238,39,55,0.3); display:flex; align-items:center; justify-content:center; gap:8px;">
        <i class="bi bi-shield-check fs-6"></i>
        <span>Pesan & Antar Sekarang</span>
    </button>
</form>

<script>
function updatePaymentCardStyles() {
    document.querySelectorAll('.payment-option').forEach(label => {
        const radio = label.querySelector('input[type="radio"]');
        if (radio && radio.checked) {
            label.classList.add('border-danger', 'bg-danger-subtle', 'active');
        } else {
            label.classList.remove('border-danger', 'bg-danger-subtle', 'active');
        }
    });
}
document.addEventListener('DOMContentLoaded', () => {
    updatePaymentCardStyles();
});

const STORE_LAT = <?= (float)($cart_data['store']['latitude'] ?? -6.9835) ?>;
const STORE_LNG = <?= (float)($cart_data['store']['longitude'] ?? 107.8335) ?>;
const STORE_NAME = "<?= htmlspecialchars($cart_data['store']['name'] ?? 'Resto') ?>";
const BASE_SUBTOTAL = <?= (float)$cart_data['subtotal'] ?>;
const BASE_DELIVERY_FEE = <?= (float)$cart_data['store']['delivery_fee'] ?>;

let map, customerMarker, storeMarker, routeLine;
let mapInitialized = false;

function initCheckoutMap() {
    if (mapInitialized) return;
    mapInitialized = true;

    if ('geolocation' in navigator) {
        const gpsTimeout = setTimeout(() => {
            _buildMap(STORE_LAT, STORE_LNG, false);
        }, 5000);

        navigator.geolocation.getCurrentPosition(
            (pos) => {
                clearTimeout(gpsTimeout);
                _buildMap(pos.coords.latitude, pos.coords.longitude, true);
            },
            () => {
                clearTimeout(gpsTimeout);
                _buildMap(STORE_LAT, STORE_LNG, false);
            },
            { enableHighAccuracy: true, timeout: 6000, maximumAge: 0 }
        );
    } else {
        _buildMap(STORE_LAT, STORE_LNG, false);
    }
}

function _buildMap(initLat, initLng, isGPS) {
    document.getElementById('input-lat').value = initLat.toFixed(6);
    document.getElementById('input-lng').value = initLng.toFixed(6);

    map = L.map('checkout-map', { zoomControl: true, attributionControl: false })
           .setView([initLat, initLng], isGPS ? 16 : 14);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

    const storeSvg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 46" width="32" height="46">
      <defs>
        <linearGradient id="csg" x1="0%" y1="0%" x2="100%" y2="100%">
          <stop offset="0%" stop-color="#f87171"/>
          <stop offset="100%" stop-color="#b91c1c"/>
        </linearGradient>
      </defs>
      <path d="M16 2C9 2 3 8 3 15c0 10 13 29 13 29S29 25 29 15C29 8 23 2 16 2z" fill="url(#csg)" stroke="white" stroke-width="2"/>
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
    storeMarker = L.marker([STORE_LAT, STORE_LNG], { icon: storeIcon, zIndexOffset: 100 })
        .addTo(map)
        .bindPopup(`<div style="font-size:12px;"><b>🏪 ${STORE_NAME}</b><br><span style="color:#666;">Titik Penjemputan</span></div>`);

    const custSvg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 46" width="32" height="46">
      <defs>
        <linearGradient id="ccg" x1="0%" y1="0%" x2="100%" y2="100%">
          <stop offset="0%" stop-color="#34d399"/>
          <stop offset="100%" stop-color="#047857"/>
        </linearGradient>
      </defs>
      <path d="M16 2C9 2 3 8 3 15c0 10 13 29 13 29S29 25 29 15C29 8 23 2 16 2z" fill="url(#ccg)" stroke="white" stroke-width="2"/>
      <circle cx="16" cy="11" r="3.8" fill="white"/>
      <path d="M9 22 Q9 17 16 17 Q23 17 23 22" fill="white"/>
    </svg>`;
    const custIcon = L.icon({
        iconUrl: 'data:image/svg+xml,' + encodeURIComponent(custSvg),
        iconSize: [32, 46],
        iconAnchor: [16, 46],
        popupAnchor: [0, -46]
    });

    customerMarker = L.marker([initLat, initLng], { icon: custIcon, draggable: false, zIndexOffset: 500 })
        .addTo(map)
        .bindPopup('<div style="font-size:12px;"><b>📍 Lokasi Antar Terkunci</b><br><span style="color:#666;">Posisi sesuai lokasi GPS / Alamat</span></div>')
        .openPopup();

    // Paksa render ulang tile supaya tidak blank di mobile
    map.whenReady(() => setTimeout(() => map.invalidateSize(), 150));

    updateRouteAndDistance(initLat, initLng);
    if (isGPS) reverseGeocode(initLat, initLng, 'input-address');

    const bounds = L.latLngBounds([[STORE_LAT, STORE_LNG], [initLat, initLng]]);
    map.fitBounds(bounds, { padding: [40, 40] });
}

let geocodeTimer = null;
function reverseGeocode(lat, lng, targetInputId) {
    if (geocodeTimer) clearTimeout(geocodeTimer);
    geocodeTimer = setTimeout(async () => {
        try {
            const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}`, {
                headers: { 'Accept-Language': 'id-ID,id;q=0.9' }
            });
            const data = await res.json();
            if (data && data.display_name) {
                const input = document.getElementById(targetInputId);
                if (input) {
                    let cleanAddr = data.display_name
                        .replace(/, Indonesia$/i, '')
                        .replace(/, Jawa Barat$/i, '')
                        .replace(/, Kabupaten Bandung$/i, '');
                    input.value = cleanAddr;
                }
            }
        } catch (err) {
            console.warn('[ReverseGeocode] Error:', err);
        }
    }, 400);
}

function updateLocationData(lat, lng) {
    document.getElementById('input-lat').value = lat.toFixed(6);
    document.getElementById('input-lng').value = lng.toFixed(6);
    updateRouteAndDistance(lat, lng);
    reverseGeocode(lat, lng, 'input-address');
}

function updateRouteAndDistance(lat, lng) {
    if (routeLine) map.removeLayer(routeLine);

    routeLine = L.polyline([[STORE_LAT, STORE_LNG], [lat, lng]], {
        color: '#EE2737',
        weight: 4,
        opacity: 0.8,
        dashArray: '6, 8'
    }).addTo(map);

    const bounds = L.latLngBounds([[STORE_LAT, STORE_LNG], [lat, lng]]);
    map.fitBounds(bounds, { padding: [30, 30] });

    // Haversine Distance in KM
    const dLat = (lat - STORE_LAT) * Math.PI / 180;
    const dLng = (lng - STORE_LNG) * Math.PI / 180;
    const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
              Math.cos(STORE_LAT * Math.PI / 180) * Math.cos(lat * Math.PI / 180) *
              Math.sin(dLng / 2) * Math.sin(dLng / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    const distKm = Math.max(0.5, Math.round((6371 * c) * 10) / 10);

    document.getElementById('input-distance').value = distKm;
    document.getElementById('distance-badge').innerHTML = `<i class="bi bi-signpost-2 me-1"></i> Est. Jarak: ${distKm} Km`;
    document.getElementById('fee-dist-text').textContent = `${distKm} Km`;

    // Dynamic Delivery Fee: Base 5000 + 2000/km after 2km
    let currentFee = BASE_DELIVERY_FEE;
    if (distKm > 2) {
        currentFee = BASE_DELIVERY_FEE + Math.round((distKm - 2) * 2000);
    }
    const total = BASE_SUBTOTAL + currentFee;

    document.getElementById('delivery-fee-display').textContent = 'Rp ' + currentFee.toLocaleString('id-ID');
    document.getElementById('total-amount-display').textContent = 'Rp ' + total.toLocaleString('id-ID');
}

function getCurrentLocation(isSilent = false) {
    if ('geolocation' in navigator) {
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                const lat = pos.coords.latitude;
                const lng = pos.coords.longitude;

                if (!map) {
                    // Peta belum dibuat (seharusnya sudah), build ulang
                    _buildMap(lat, lng, true);
                    return;
                }

                if (customerMarker) customerMarker.setLatLng([lat, lng]);
                const bounds = L.latLngBounds([[STORE_LAT, STORE_LNG], [lat, lng]]);
                map.fitBounds(bounds, { padding: [40, 40] });
                setTimeout(() => map.invalidateSize(), 100);

                updateLocationData(lat, lng);
                reverseGeocode(lat, lng, 'input-address');

                if (!isSilent) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Lokasi Ditemukan!',
                        text: 'Pin pengantaran disesuaikan ke posisi GPS Anda.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                }
            },
            (err) => {
                if (!isSilent) {
                    Swal.fire('GPS Error', 'Gagal membaca koordinat GPS perangkat. Silakan klik manual pada peta.', 'warning');
                }
            },
            { enableHighAccuracy: true, timeout: 7000 }
        );
    }
}

async function handlePlaceOrder(e) {
    e.preventDefault();
    const btn = document.getElementById('btnPlaceOrder');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Memproses Pesanan...';

    const formData = new FormData(document.getElementById('checkoutForm'));

    try {
        const res = await fetch(window.BASE_URL + '/orders/place', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data.success) {
            // Check if Midtrans Snap Online Payment is chosen
            if (data.data.payment_method === 'midtrans' && data.data.snap_token) {
                btn.innerHTML = '<i class="bi bi-credit-card me-1"></i> Menunggu Pembayaran Midtrans...';
                
                window.snap.pay(data.data.snap_token, {
                    onSuccess: function(result) {
                        // Notify backend to confirm payment immediately
                        fetch(window.BASE_URL + '/payment/verify', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                order_id: data.data.order_code,
                                transaction_status: result.transaction_status || 'settlement',
                                payment_type: result.payment_type || 'midtrans',
                                gross_amount: result.gross_amount
                            })
                        }).finally(() => {
                            Swal.fire({
                                title: 'Pembayaran Berhasil! 🎉',
                                text: 'Pesanan Anda telah lunas dan siap diantar kurir CicalengkaGO.',
                                icon: 'success',
                                timer: 2500,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.href = window.BASE_URL + '/' + data.data.redirect;
                            });
                        });
                    },
                    onPending: function(result) {
                        Swal.fire({
                            title: 'Menunggu Pembayaran ⏳',
                            text: 'Instruksi pembayaran virtual account / QRIS telah dibuat. Silakan selesaikan pembayaran Anda.',
                            icon: 'info',
                            confirmButtonText: 'Lihat Pesanan',
                            confirmButtonColor: '#EE2737'
                        }).then(() => {
                            window.location.href = window.BASE_URL + '/' + data.data.redirect;
                        });
                    },
                    onError: function(result) {
                        Swal.fire('Pembayaran Gagal', 'Terjadi kendala saat memproses pembayaran online.', 'error');
                        btn.disabled = false;
                        btn.innerHTML = '<i class="bi bi-shield-check"></i> <span>Coba Bayar Lagi</span>';
                    },
                    onClose: function() {
                        window.location.href = window.BASE_URL + '/' + data.data.redirect;
                    }
                });
                return;
            }

            // Regular COD or Wallet Payment
            Swal.fire({
                title: 'Pesanan Berhasil!',
                text: 'Pesanan Anda langsung diteruskan ke resto dan kurir Cicalengka.',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                window.location.href = window.BASE_URL + '/' + data.data.redirect;
            });
        } else {
            Swal.fire('Gagal', data.message || 'Terjadi kesalahan.', 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-shield-check"></i> <span>Pesan & Antar Sekarang</span>';
        }
    } catch (err) {
        console.error(err);
        Swal.fire('Error', 'Terjadi kesalahan sistem.', 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-shield-check"></i> <span>Pesan & Antar Sekarang</span>';
    }
}

function applyCouponPreview() {
    const code = document.getElementById('coupon_code').value.trim();
    if (!code) {
        Swal.fire('Info', 'Ketikkan kode voucher terlebih dahulu.', 'info');
        return;
    }
    Swal.fire('Kupon Dipasang', 'Kupon ' + code + ' akan dihitung pada saat konfirmasi pesanan.', 'success');
}

document.addEventListener('DOMContentLoaded', initCheckoutMap);
</script>

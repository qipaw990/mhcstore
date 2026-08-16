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
            Geser pin merah atau ketuk peta untuk menyesuaikan lokasi tepat rumah Anda.
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

    <!-- Payment Method Selector -->
    <div class="p-3 bg-white border shadow-2xs mb-3 overflow-hidden" style="border-radius: 16px; border-color: #E2E8F0 !important; padding: 14px 16px !important;">
        <h6 class="fw-bold mb-3 text-dark" style="font-size: 12.5px;"><i class="bi bi-wallet2 text-danger me-1.5"></i> Metode Pembayaran</h6>

        <div class="d-flex flex-column gap-2.5">
            <!-- CicalengkaPay Digital Wallet -->
            <label class="p-2.5 px-3 border rounded-3 d-flex align-items-center justify-content-between cursor-pointer payment-option overflow-hidden <?= ((float)$wallet['balance'] >= (float)$cart_data['subtotal']) ? 'border-danger bg-danger-subtle' : 'opacity-75' ?>" style="cursor: pointer; border-radius: 12px !important; padding: 12px 14px !important;">
                <div class="d-flex align-items-center gap-2.5 min-w-0">
                    <input type="radio" name="payment_method" id="pay_wallet" value="wallet" class="flex-shrink-0" <?= ((float)$wallet['balance'] >= (float)$cart_data['subtotal']) ? 'checked' : 'disabled' ?>>
                    <div class="min-w-0">
                        <div class="fw-bold d-flex align-items-center gap-1.5 text-truncate" style="font-size: 11.5px;">
                            <span style="color:#EE2737;font-weight:800;">CicalengkaPay</span>
                            <span class="text-muted text-truncate" style="font-size: 10px;">(Saldo Digital)</span>
                        </div>
                        <div class="text-muted text-truncate" style="font-size: 10px;">Saldo: <?= format_rupiah($wallet['balance'] ?? 0) ?></div>
                    </div>
                </div>
                <?php if ((float)$wallet['balance'] < (float)$cart_data['subtotal']): ?>
                    <span class="badge bg-warning text-dark py-1 px-2.5 rounded-pill flex-shrink-0" style="font-size: 9px;">Kurang</span>
                <?php else: ?>
                    <span class="badge py-1 px-2.5 rounded-pill text-white flex-shrink-0" style="background:#EE2737 !important; font-size: 9px;">Tersedia</span>
                <?php endif; ?>
            </label>

            <!-- Midtrans Online Payment (QRIS / VA / E-Wallet) -->
            <label class="p-2.5 px-3 border rounded-3 d-flex align-items-center justify-content-between cursor-pointer payment-option overflow-hidden" style="cursor: pointer; border-radius: 12px !important; padding: 12px 14px !important;">
                <div class="d-flex align-items-center gap-2.5 min-w-0">
                    <input type="radio" name="payment_method" id="pay_midtrans" value="midtrans" class="flex-shrink-0">
                    <div class="min-w-0">
                        <div class="fw-bold d-flex align-items-center gap-1.5 text-dark text-truncate" style="font-size: 11.5px;">
                            <span class="text-truncate">Bayar Online (Midtrans)</span>
                            <span class="badge bg-danger-subtle text-danger py-0.5 px-2 flex-shrink-0" style="font-size: 9px; font-weight: 700;">Otomatis</span>
                        </div>
                        <div class="text-muted text-truncate" style="font-size: 10px;">QRIS, GoPay, ShopeePay, VA Bank</div>
                    </div>
                </div>
                <div class="d-flex align-items-center flex-shrink-0">
                    <span class="badge text-white px-2.5 py-1" style="background: #002B49; font-size: 9px; font-weight: 700; border-radius: 6px;">MIDTRANS</span>
                </div>
            </label>

            <!-- COD (Cash on Delivery) -->
            <label class="p-2.5 px-3 border rounded-3 d-flex align-items-center justify-content-between cursor-pointer payment-option overflow-hidden" style="cursor: pointer; border-radius: 12px !important; padding: 12px 14px !important;">
                <div class="d-flex align-items-center gap-2.5 min-w-0">
                    <input type="radio" name="payment_method" id="pay_cod" value="cod" class="flex-shrink-0" <?= ((float)$wallet['balance'] < (float)$cart_data['subtotal']) ? 'checked' : '' ?>>
                    <div class="min-w-0">
                        <div class="fw-bold text-dark text-truncate" style="font-size: 11.5px;">Tunai saat Tiba (COD)</div>
                        <div class="text-muted text-truncate" style="font-size: 10px;">Bayar langsung ke kurir motor</div>
                    </div>
                </div>
                <i class="bi bi-cash-coin text-success fs-5 flex-shrink-0"></i>
            </label>
        </div>
    </div>

    <!-- Voucher / Coupon Code -->
    <div class="p-3 bg-white border shadow-2xs mb-3 overflow-hidden" style="border-radius: 16px; border-color: #E2E8F0 !important; padding: 14px 16px !important;">
        <h6 class="fw-bold mb-2 text-dark" style="font-size: 12px;"><i class="bi bi-percent text-warning me-1.5"></i> Promo & Kupon</h6>
        <div class="input-group input-group-sm">
            <input type="text" name="coupon_code" id="coupon_code" class="form-control bg-light" style="font-size: 11px; border-radius: 10px 0 0 10px; border-color: #E2E8F0;" placeholder="Kode promo (Contoh: CCGHEMAT)">
            <button type="button" class="btn text-white fw-bold px-3.5" style="background:#EE2737; font-size: 11px; border-radius: 0 10px 10px 0;" onclick="applyCouponPreview()">Pakai</button>
        </div>
    </div>

    <!-- Order Notes -->
    <div class="p-3 bg-white border shadow-2xs mb-3 overflow-hidden" style="border-radius: 16px; border-color: #E2E8F0 !important; padding: 14px 16px !important;">
        <label class="form-label fw-bold mb-1.5 d-block text-dark" for="order_notes" style="font-size: 12px;"><i class="bi bi-chat-left-text me-1.5 text-muted"></i> Catatan Pesanan</label>
        <input type="text" name="order_notes" id="order_notes" class="form-control form-control-sm bg-light" style="font-size: 11px; border-radius: 10px; border-color: #E2E8F0;" placeholder="Contoh: Sambal dipisah, jangan pakai bawang goreng">
    </div>

    <!-- Order Breakdown Card -->
    <div class="p-3 bg-white border shadow-2xs mb-3.5 overflow-hidden" style="border-radius: 16px; border-color: #E2E8F0 !important; padding: 16px !important;">
        <h6 class="fw-bold mb-3 text-dark d-flex align-items-center justify-content-between gap-2" style="font-size: 12.5px;">
            <span class="text-truncate"><i class="bi bi-receipt me-1.5 text-danger"></i> Rincian Tagihan</span>
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
const STORE_LAT = <?= (float)($cart_data['store']['latitude'] ?? -6.9835) ?>;
const STORE_LNG = <?= (float)($cart_data['store']['longitude'] ?? 107.8335) ?>;
const STORE_NAME = "<?= htmlspecialchars($cart_data['store']['name'] ?? 'Resto') ?>";
const BASE_SUBTOTAL = <?= (float)$cart_data['subtotal'] ?>;
const BASE_DELIVERY_FEE = <?= (float)$cart_data['store']['delivery_fee'] ?>;

let map, customerMarker, storeMarker, routeLine;

function initCheckoutMap() {
    const initialLat = parseFloat(document.getElementById('input-lat').value) || -6.9855;
    const initialLng = parseFloat(document.getElementById('input-lng').value) || 107.8350;

    map = L.map('checkout-map', { zoomControl: false }).setView([initialLat, initialLng], 14);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap'
    }).addTo(map);

    // Store Marker (Distinct Red Store Badge)
    const storeIcon = L.divIcon({
        className: 'custom-pin-store',
        html: '<div style="background:#101820;color:#EE2737;width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:2.5px solid white;box-shadow:0 3px 10px rgba(0,0,0,0.35);font-size:16px;"><i class="bi bi-shop"></i></div>',
        iconSize: [34, 34],
        iconAnchor: [17, 17]
    });
    storeMarker = L.marker([STORE_LAT, STORE_LNG], { icon: storeIcon })
        .addTo(map)
        .bindPopup(`<div style="font-size:12px;"><b>🏪 Resto / Toko:</b><br>${STORE_NAME}</div>`);

    // Customer Marker (CicalengkaGO Red Draggable Pin)
    const custIcon = L.divIcon({
        className: 'custom-pin-customer',
        html: '<div style="background:#EE2737;color:white;width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:3px solid white;box-shadow:0 4px 12px rgba(238,39,55,0.45);font-size:18px;"><i class="bi bi-geo-alt-fill"></i></div>',
        iconSize: [36, 36],
        iconAnchor: [18, 18]
    });

    customerMarker = L.marker([initialLat, initialLng], {
        icon: custIcon,
        draggable: true
    }).addTo(map).bindPopup('<div style="font-size:12px;"><b>📍 Lokasi Pengantaran (Rumah Anda)</b><br><span style="color:#666;">Geser pin ini jika lokasi belum pas</span></div>').openPopup();

    customerMarker.on('dragend', function (e) {
        const pos = e.target.getLatLng();
        updateLocationData(pos.lat, pos.lng);
    });

    map.on('click', function (e) {
        customerMarker.setLatLng(e.latlng);
        updateLocationData(e.latlng.lat, e.latlng.lng);
    });

    updateRouteAndDistance(initialLat, initialLng);

    // Auto-detect real device GPS on load silently
    setTimeout(() => {
        getCurrentLocation(true);
    }, 500);
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
                if (customerMarker) customerMarker.setLatLng([lat, lng]);
                if (map) map.setView([lat, lng], 15);
                updateLocationData(lat, lng);

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

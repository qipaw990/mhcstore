<div class="p-3 border-bottom bg-white d-flex align-items-center gap-2">
    <a href="<?= $baseUrl ?>" class="btn btn-light btn-sm rounded-circle"><i class="bi bi-arrow-left"></i></a>
    <h6 class="fw-bold m-0" style="color: var(--gojek-charcoal);">Kirim Paket Kilat (CicalengkaSend)</h6>
</div>

<div class="p-3">
    <!-- CicalengkaSend Header Banner -->
    <div class="p-3 mb-3 text-white rounded-4 shadow-sm" style="background: linear-gradient(135deg, #EE2737 0%, #C61524 100%);">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-4 bg-white text-danger d-flex align-items-center justify-content-center shadow-xs" style="width: 50px; height: 50px;">
                <i class="bi bi-box-seam-fill fs-3" style="color: #EE2737;"></i>
            </div>
            <div>
                <h6 class="fw-bold mb-1">CicalengkaSend Kilat</h6>
                <div class="small text-white-50">Kirim dokumen, baju, makanan, dan barang se-Cicalengka cepat sampai dengan aman!</div>
            </div>
        </div>
    </div>

    <form id="parcelForm" onsubmit="handlePlaceParcel(event)">
        <!-- Interactive Parcel Route Map -->
        <div class="p-3 bg-white rounded-4 border shadow-sm mb-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <h6 class="fw-bold small m-0" style="color: var(--gojek-charcoal);"><i class="bi bi-map-fill text-danger me-1"></i> Rute Antar Paket Cicalengka</h6>
                <button type="button" onclick="getParcelGps()" class="btn btn-sm rounded-pill py-0 px-2 fw-bold text-white shadow-xs" style="background:#EE2737; font-size: 11px;">
                    <i class="bi bi-crosshair me-1"></i> GPS Penjemputan
                </button>
            </div>
            <div class="small text-muted mb-2" style="font-size: 11px;">
                Geser pin <span class="text-danger fw-bold">🔴 Penjemputan</span> dan <span class="text-dark fw-bold">🎯 Tujuan</span> untuk menentukan rute kurir.
            </div>
            <div id="parcel-map" style="width: 100%; height: 220px; border-radius: 12px;" class="border shadow-sm mb-2"></div>
            <div class="d-flex align-items-center justify-content-between px-1">
                <span class="badge bg-light text-dark border small" id="parcel-dist-badge"><i class="bi bi-signpost-2 me-1"></i> Est. Jarak: 2.5 Km</span>
                <span class="badge bg-danger-subtle text-danger small"><i class="bi bi-clock-history me-1"></i> Est. Waktu: 15-25 Mnt</span>
            </div>

            <input type="hidden" name="pickup_lat" id="pickup_lat" value="-6.9840">
            <input type="hidden" name="pickup_lng" id="pickup_lng" value="107.8340">
            <input type="hidden" name="dest_lat" id="dest_lat" value="-6.9870">
            <input type="hidden" name="dest_lng" id="dest_lng" value="107.8380">
            <input type="hidden" name="distance_km" id="distance_km" value="2.5">
        </div>

        <!-- Sender Information -->
        <div class="p-3 bg-white rounded-4 border shadow-sm mb-3">
            <h6 class="fw-bold small mb-2" style="color: var(--gojek-charcoal);"><i class="bi bi-geo-alt text-danger me-1"></i> Titik Penjemputan (Pengirim)</h6>
            <div class="mb-2">
                <input type="text" name="pickup_address" id="pickup_address" class="form-control form-control-sm bg-light" placeholder="Alamat lengkap penjemputan barang" required value="Jl. Dipati Ukur Cicalengka Kulon No. 12">
            </div>
            <div class="row g-2">
                <div class="col-6">
                    <input type="text" name="sender_name" class="form-control form-control-sm bg-light" placeholder="Nama Pengirim" value="<?= htmlspecialchars($_SESSION['user']['name'] ?? '') ?>" required>
                </div>
                <div class="col-6">
                    <input type="text" name="sender_phone" class="form-control form-control-sm bg-light" placeholder="No HP Pengirim" value="<?= htmlspecialchars($_SESSION['user']['phone'] ?? '') ?>" required>
                </div>
            </div>
        </div>

        <!-- Destination Information -->
        <div class="p-3 bg-white rounded-4 border shadow-sm mb-3">
            <h6 class="fw-bold small mb-2" style="color: var(--gojek-charcoal);"><i class="bi bi-geo-alt-fill text-danger me-1"></i> Titik Tujuan (Penerima)</h6>
            <div class="mb-2">
                <input type="text" name="destination_address" id="destination_address" class="form-control form-control-sm bg-light" placeholder="Alamat lengkap tujuan pengantaran" required value="Komplek Griya Cicalengka Asri Blok C4">
            </div>
            <div class="row g-2">
                <div class="col-6">
                    <input type="text" name="recipient_name" class="form-control form-control-sm bg-light" placeholder="Nama Penerima" required value="Pak Agus">
                </div>
                <div class="col-6">
                    <input type="text" name="recipient_phone" class="form-control form-control-sm bg-light" placeholder="No HP Penerima" required value="082199887766">
                </div>
            </div>
        </div>

        <!-- Parcel Details -->
        <div class="p-3 bg-white rounded-4 border shadow-sm mb-3">
            <h6 class="fw-bold small mb-2" style="color: var(--gojek-charcoal);"><i class="bi bi-box me-1 text-muted"></i> Detail Paket</h6>
            <div class="row g-2 mb-2">
                <div class="col-7">
                    <select name="item_category" class="form-select form-select-sm bg-light">
                        <option value="Dokumen / Surat">Dokumen / Surat</option>
                        <option value="Makanan / Kue">Makanan / Kue</option>
                        <option value="Pakaian / Laundry">Pakaian / Laundry</option>
                        <option value="Barang Elektronik Kecil">Barang Elektronik</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>
                <div class="col-5">
                    <div class="input-group input-group-sm">
                        <input type="number" name="weight_kg" id="weight_kg" onchange="calculateParcelFare()" class="form-control bg-light" value="1.0" step="0.5" min="0.5" max="10">
                        <span class="input-group-text bg-light">Kg</span>
                    </div>
                </div>
            </div>
            <input type="text" name="parcel_notes" class="form-control form-control-sm bg-light" placeholder="Instruksi tambahan untuk kurir...">
        </div>

        <!-- Payment Method -->
        <div class="p-3 bg-white rounded-4 border shadow-sm mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-bold small m-0" style="color: var(--gojek-charcoal);"><i class="bi bi-wallet2 me-1 text-danger"></i> Pembayaran Ongkir</h6>
                <span class="badge text-white px-2 py-0" style="background:#002B49; font-size: 9px; font-weight:700;">MIDTRANS</span>
            </div>
            <div class="d-flex flex-column gap-2 pt-1">
                <label class="d-flex align-items-center justify-content-between p-2 px-3 border rounded-3 small cursor-pointer">
                    <div class="d-flex align-items-center gap-2">
                        <input type="radio" name="payment_method" value="midtrans">
                        <div>
                            <span class="fw-bold text-dark">Bayar Online (Midtrans)</span>
                            <div class="text-muted" style="font-size: 10px;">QRIS, GoPay, ShopeePay, VA BCA/BRI/BNI/Mandiri</div>
                        </div>
                    </div>
                    <span class="badge bg-danger-subtle text-danger" style="font-size: 9px;">Otomatis</span>
                </label>
                <div class="d-flex gap-3 px-1">
                    <label class="d-flex align-items-center gap-2 small cursor-pointer">
                        <input type="radio" name="payment_method" value="cod" checked>
                        <span>Tunai (COD)</span>
                    </label>
                    <label class="d-flex align-items-center gap-2 small cursor-pointer">
                        <input type="radio" name="payment_method" value="wallet" <?= ((float)$wallet_balance >= 8000) ? '' : 'disabled' ?>>
                        <span class="d-flex align-items-center gap-1">
                            <span style="color:#EE2737;font-weight:800;">CicalengkaPay</span>
                            <span class="text-muted">(<?= format_rupiah($wallet_balance ?? 0) ?>)</span>
                        </span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Estimated Fare Dynamic -->
        <div class="p-3 bg-white rounded-4 border shadow-sm mb-4 d-flex align-items-center justify-content-between">
            <div>
                <div class="text-muted" style="font-size: 11px;">Tarif CicalengkaSend Instant (<span id="fare-dist-text">2.5 Km</span>)</div>
                <div class="fw-bold fs-5" style="color: #EE2737;" id="parcel-fare-display">Rp 8.000</div>
            </div>
            <span class="badge text-white px-3 py-2 rounded-pill fw-bold" style="background:#EE2737;">CicalengkaSend Instant</span>
        </div>

        <button type="submit" id="btnParcelSubmit" class="btn btn-gojek-green mb-3" style="background:#EE2737 !important; color:#FFFFFF !important; border-radius:9999px; font-weight:800; padding:14px 20px; box-shadow:0 4px 14px rgba(238,39,55,0.35);">
            <i class="bi bi-box-seam-fill"></i>
            <span>Panggil Kurir CicalengkaSend Sekarang</span>
        </button>
    </form>
</div>

<script>
let pMap, pickupMarker, destMarker, pRouteLine;

function initParcelMap() {
    let pLat = parseFloat(document.getElementById('pickup_lat').value) || -6.9840;
    let pLng = parseFloat(document.getElementById('pickup_lng').value) || 107.8340;
    let dLat = parseFloat(document.getElementById('dest_lat').value) || -6.9870;
    let dLng = parseFloat(document.getElementById('dest_lng').value) || 107.8380;

    pMap = L.map('parcel-map', { zoomControl: false }).setView([pLat, pLng], 14);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap'
    }).addTo(pMap);

    const pickupIcon = L.divIcon({
        className: 'custom-pin',
        html: '<div style="background:#EE2737;color:white;width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:2px solid white;box-shadow:0 3px 8px rgba(0,0,0,0.3);"><i class="bi bi-box-arrow-up"></i></div>',
        iconSize: [32, 32],
        iconAnchor: [16, 16]
    });

    const destIcon = L.divIcon({
        className: 'custom-pin',
        html: '<div style="background:#111827;color:white;width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:2px solid white;box-shadow:0 3px 8px rgba(0,0,0,0.3);"><i class="bi bi-geo-alt-fill"></i></div>',
        iconSize: [32, 32],
        iconAnchor: [16, 16]
    });

    pickupMarker = L.marker([pLat, pLng], { icon: pickupIcon, draggable: true }).addTo(pMap).bindPopup('<b>Titik Jemput Barang (Pengirim)</b>').openPopup();
    destMarker = L.marker([dLat, dLng], { icon: destIcon, draggable: true }).addTo(pMap).bindPopup('<b>Titik Tujuan (Penerima)</b>');

    let pickupGeocodeTimer = null;
    let destGeocodeTimer = null;

    function reverseGeocode(lat, lng, targetInputId, isPickup = true) {
        let timer = isPickup ? pickupGeocodeTimer : destGeocodeTimer;
        if (timer) clearTimeout(timer);

        const newTimer = setTimeout(async () => {
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

        if (isPickup) pickupGeocodeTimer = newTimer;
        else destGeocodeTimer = newTimer;
    }

    pickupMarker.on('dragend', function (e) {
        const pos = e.target.getLatLng();
        document.getElementById('pickup_lat').value = pos.lat.toFixed(6);
        document.getElementById('pickup_lng').value = pos.lng.toFixed(6);
        reverseGeocode(pos.lat, pos.lng, 'pickup_address', true);
        redrawParcelRoute();
    });

    destMarker.on('dragend', function (e) {
        const pos = e.target.getLatLng();
        document.getElementById('dest_lat').value = pos.lat.toFixed(6);
        document.getElementById('dest_lng').value = pos.lng.toFixed(6);
        reverseGeocode(pos.lat, pos.lng, 'destination_address', false);
        redrawParcelRoute();
    });

    redrawParcelRoute();

    // Auto detect sender pickup GPS on load silently
    setTimeout(() => {
        getParcelGps(true);
    }, 500);
}

function redrawParcelRoute() {
    const p1 = pickupMarker.getLatLng();
    const p2 = destMarker.getLatLng();

    if (pRouteLine) pMap.removeLayer(pRouteLine);

    pRouteLine = L.polyline([p1, p2], {
        color: '#EE2737',
        weight: 4,
        opacity: 0.8,
        dashArray: '6, 8'
    }).addTo(pMap);

    const bounds = L.latLngBounds([p1, p2]);
    pMap.fitBounds(bounds, { padding: [30, 30] });

    // Haversine formula
    const dLat = (p2.lat - p1.lat) * Math.PI / 180;
    const dLng = (p2.lng - p1.lng) * Math.PI / 180;
    const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
              Math.cos(p1.lat * Math.PI / 180) * Math.cos(p2.lat * Math.PI / 180) *
              Math.sin(dLng / 2) * Math.sin(dLng / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    const distKm = Math.max(0.5, Math.round((6371 * c) * 10) / 10);

    document.getElementById('distance_km').value = distKm;
    document.getElementById('parcel-dist-badge').innerHTML = `<i class="bi bi-signpost-2 me-1"></i> Est. Jarak: ${distKm} Km`;
    document.getElementById('fare-dist-text').textContent = `${distKm} Km`;

    calculateParcelFare(distKm);
}

function calculateParcelFare(dist = null) {
    const distKm = dist !== null ? dist : parseFloat(document.getElementById('distance_km').value) || 2.5;
    const weight = parseFloat(document.getElementById('weight_kg').value) || 1.0;

    // Base fare 8.000 for up to 3 km, + 2000 per extra km, + 1000 per kg above 2kg
    let fare = 8000;
    if (distKm > 3) {
        fare += Math.round((distKm - 3) * 2000);
    }
    if (weight > 2) {
        fare += Math.round((weight - 2) * 1000);
    }

    document.getElementById('parcel-fare-display').textContent = 'Rp ' + fare.toLocaleString('id-ID');
}

function getParcelGps(isSilent = false) {
    if ('geolocation' in navigator) {
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                const lat = pos.coords.latitude;
                const lng = pos.coords.longitude;
                if (pickupMarker) {
                    pickupMarker.setLatLng([lat, lng]);
                    document.getElementById('pickup_lat').value = lat.toFixed(6);
                    document.getElementById('pickup_lng').value = lng.toFixed(6);
                    if (typeof reverseGeocode === 'function') {
                        reverseGeocode(lat, lng, 'pickup_address', true);
                    }
                    redrawParcelRoute();
                }
                if (!isSilent) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Lokasi Penjemputan Terdeteksi',
                        text: 'Pin penjemputan disesuaikan dengan posisi GPS Anda.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                }
            },
            (err) => {
                if (!isSilent) Swal.fire('Info', 'Geser pin pada peta untuk menentukan titik jemput.', 'info');
            },
            { enableHighAccuracy: true, timeout: 6000 }
        );
    }
}

async function handlePlaceParcel(e) {
    e.preventDefault();
    const btn = document.getElementById('btnParcelSubmit');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Memproses Permintaan Kirim...';

    const formData = new FormData(document.getElementById('parcelForm'));

    try {
        const res = await fetch(window.BASE_URL + '/parcel/place', {
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
                                text: 'Ongkir CicalengkaSend telah lunas. Kurir segera meluncur ke lokasi Anda.',
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
                            text: 'Silakan selesaikan pembayaran ongkir via QRIS / Virtual Account.',
                            icon: 'info',
                            confirmButtonText: 'Lihat Status Pengiriman',
                            confirmButtonColor: '#EE2737'
                        }).then(() => {
                            window.location.href = window.BASE_URL + '/' + data.data.redirect;
                        });
                    },
                    onError: function(result) {
                        Swal.fire('Pembayaran Gagal', 'Terjadi kendala saat memproses pembayaran ongkir online.', 'error');
                        btn.disabled = false;
                        btn.innerHTML = '<i class="bi bi-box-seam-fill"></i> <span>Coba Bayar Lagi</span>';
                    },
                    onClose: function() {
                        Swal.fire({
                            title: 'Pembayaran Belum Selesai',
                            text: 'Order kirim paket Anda telah dicatat. Anda dapat memantau status di riwayat pesanan.',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Ke Lacak Pengiriman',
                            confirmButtonColor: '#EE2737',
                            cancelButtonText: 'Tutup'
                        }).then((r) => {
                            if (r.isConfirmed) {
                                window.location.href = window.BASE_URL + '/' + data.data.redirect;
                            } else {
                                btn.disabled = false;
                                btn.innerHTML = '<i class="bi bi-box-seam-fill"></i> <span>Panggil Kurir CicalengkaSend Sekarang</span>';
                            }
                        });
                    }
                });
                return;
            }

            // Regular COD or Wallet Payment
            Swal.fire({
                title: 'Kurir Sedang Dipanggil!',
                text: 'Mitra Driver CicalengkaGO terdekat akan segera menuju lokasi Anda.',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                window.location.href = window.BASE_URL + '/' + data.data.redirect;
            });
        } else {
            Swal.fire('Gagal', data.message || 'Terjadi kesalahan.', 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-box-seam-fill"></i> <span>Panggil Kurir CicalengkaSend Sekarang</span>';
        }
    } catch (err) {
        console.error(err);
        Swal.fire('Error', 'Terjadi kesalahan sistem.', 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-box-seam-fill"></i> <span>Panggil Kurir CicalengkaSend Sekarang</span>';
    }
}

document.addEventListener('DOMContentLoaded', initParcelMap);
</script>

<div class="border-bottom bg-white d-flex align-items-center gap-2.5 sticky-top shadow-xs px-3.5 py-3">
    <a href="<?= $baseUrl ?>" class="btn btn-light btn-sm rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 36px; height: 36px; border: 1px solid #E2E8F0; background: #F8FAFC;"><i class="bi bi-arrow-left text-dark" style="font-size: 15px;"></i></a>
    <h6 class="fw-bold m-0 text-dark" style="font-size: 14px;">Kirim Paket (CicalengkaSend)</h6>
</div>

<div class="px-3.5 pt-4 pb-5" style="min-height: 85vh;">
    <!-- CicalengkaSend Header Banner -->
    <div class="p-3.5 mb-4 text-white shadow-xs" style="background: linear-gradient(135deg, #EE2737 0%, #C61524 100%); border-radius: 16px;">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-3 bg-white text-danger d-flex align-items-center justify-content-center shadow-xs" style="width: 42px; height: 42px; font-size: 20px; flex-shrink: 0;">
                <i class="bi bi-box-seam-fill" style="color: #EE2737;"></i>
            </div>
            <div>
                <h6 class="fw-bold mb-0.5" style="font-size: 13.5px;">CicalengkaSend Kilat</h6>
                <div class="text-white-50" style="font-size: 10.5px; line-height: 1.3;">Kirim barang & dokumen se-Cicalengka cepat sampai dengan aman!</div>
            </div>
        </div>
    </div>

    <form id="parcelForm" onsubmit="handlePlaceParcel(event)">
        <!-- Interactive Parcel Route Map -->
        <div class="p-3.5 bg-white border shadow-xs mb-4" style="border-radius: 16px;">
            <div class="d-flex align-items-center justify-content-between mb-2.5">
                <h6 class="fw-bold m-0 text-dark" style="font-size: 13px;"><i class="bi bi-map-fill text-danger me-1"></i> Rute Antar Paket</h6>
                <button type="button" onclick="getParcelGps()" class="btn btn-sm rounded-pill py-1.5 px-3 fw-bold text-white shadow-xs" style="background:#EE2737; font-size: 10.5px;">
                    <i class="bi bi-crosshair me-0.5"></i> GPS Jemput
                </button>
            </div>
            <div class="text-muted mb-2.5" style="font-size: 10px;">
                Geser pin <span class="text-danger fw-bold">🔴 Jemput</span> dan <span class="text-dark fw-bold">🎯 Tujuan</span> pada peta.
            </div>
            <div id="parcel-map" style="width: 100%; height: 180px; border-radius: 12px;" class="border shadow-xs mb-2.5"></div>
            <div class="d-flex align-items-center justify-content-between">
                <span class="badge bg-light text-dark border py-1.5 px-2.5" id="parcel-dist-badge" style="font-size: 9.5px;"><i class="bi bi-signpost-2 me-1"></i> Est. Jarak: 2.5 Km</span>
                <span class="badge bg-danger-subtle text-danger py-1.5 px-2.5" style="font-size: 9.5px;"><i class="bi bi-clock-history me-1"></i> Est. Waktu: 15-25 Mnt</span>
            </div>

            <input type="hidden" name="pickup_lat" id="pickup_lat" value="-6.9840">
            <input type="hidden" name="pickup_lng" id="pickup_lng" value="107.8340">
            <input type="hidden" name="dest_lat" id="dest_lat" value="-6.9870">
            <input type="hidden" name="dest_lng" id="dest_lng" value="107.8380">
            <input type="hidden" name="distance_km" id="distance_km" value="2.5">
        </div>

        <!-- Sender Information -->
        <div class="p-3.5 bg-white border shadow-xs mb-4" style="border-radius: 16px;">
            <h6 class="fw-bold mb-2.5 text-dark" style="font-size: 13px;"><i class="bi bi-geo-alt text-danger me-1"></i> Penjemputan (Pengirim)</h6>
            <div class="mb-2.5">
                <input type="text" name="pickup_address" id="pickup_address" class="form-control form-control-sm bg-light" style="font-size: 11.5px; border-radius: 10px; padding: 8px 12px;" placeholder="Alamat penjemputan" required value="Jl. Dipati Ukur Cicalengka Kulon No. 12">
            </div>
            <div class="row g-2.5">
                <div class="col-6">
                    <input type="text" name="sender_name" id="sender_name" class="form-control form-control-sm bg-light" style="font-size: 11.5px; border-radius: 10px; padding: 8px 12px;" placeholder="Nama Pengirim" value="<?= htmlspecialchars($_SESSION['user']['name'] ?? '') ?>" required>
                </div>
                <div class="col-6">
                    <input type="text" name="sender_phone" id="sender_phone" class="form-control form-control-sm bg-light" style="font-size: 11.5px; border-radius: 10px; padding: 8px 12px;" placeholder="No HP Pengirim" value="<?= htmlspecialchars($_SESSION['user']['phone'] ?? '') ?>" required>
                </div>
            </div>
        </div>

        <!-- Destination Information -->
        <div class="p-3.5 bg-white border shadow-xs mb-4" style="border-radius: 16px;">
            <h6 class="fw-bold mb-2.5 text-dark" style="font-size: 13px;"><i class="bi bi-geo-alt-fill text-danger me-1"></i> Tujuan (Penerima)</h6>
            <div class="mb-2.5">
                <input type="text" name="destination_address" id="destination_address" class="form-control form-control-sm bg-light" style="font-size: 11.5px; border-radius: 10px; padding: 8px 12px;" placeholder="Alamat tujuan" required value="Komplek Griya Cicalengka Asri Blok C4">
            </div>
            <div class="row g-2.5">
                <div class="col-6">
                    <input type="text" name="recipient_name" id="recipient_name" class="form-control form-control-sm bg-light" style="font-size: 11.5px; border-radius: 10px; padding: 8px 12px;" placeholder="Nama Penerima" required value="Pak Agus">
                </div>
                <div class="col-6">
                    <input type="text" name="recipient_phone" id="recipient_phone" class="form-control form-control-sm bg-light" style="font-size: 11.5px; border-radius: 10px; padding: 8px 12px;" placeholder="No HP Penerima" required value="082199887766">
                </div>
            </div>
        </div>

        <!-- Parcel Details -->
        <div class="p-3.5 bg-white border shadow-xs mb-4" style="border-radius: 16px;">
            <h6 class="fw-bold mb-2.5 text-dark" style="font-size: 13px;"><i class="bi bi-box me-1 text-muted"></i> Detail Paket</h6>
            <div class="row g-2.5 mb-2.5">
                <div class="col-7">
                    <select name="item_category" id="item_category" class="form-select form-select-sm bg-light" style="font-size: 11.5px; border-radius: 10px; padding: 8px 12px;">
                        <option value="Dokumen / Surat">Dokumen / Surat</option>
                        <option value="Makanan / Kue">Makanan / Kue</option>
                        <option value="Pakaian / Laundry">Pakaian / Laundry</option>
                        <option value="Barang Elektronik Kecil">Barang Elektronik</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>
                <div class="col-5">
                    <div class="input-group input-group-sm">
                        <input type="number" name="weight_kg" id="weight_kg" onchange="calculateParcelFare()" class="form-control bg-light" style="font-size: 11.5px; border-radius: 10px 0 0 10px; padding: 8px 12px;" value="1.0" step="0.5" min="0.5" max="10">
                        <span class="input-group-text bg-light" style="font-size: 11.5px; border-radius: 0 10px 10px 0;">Kg</span>
                    </div>
                </div>
            </div>
            <input type="text" name="parcel_notes" id="parcel_notes" class="form-control form-control-sm bg-light" style="font-size: 11.5px; border-radius: 10px; padding: 8px 12px;" placeholder="Instruksi tambahan untuk kurir...">
        </div>

        <!-- Payment Method -->
        <div class="p-3.5 bg-white border shadow-xs mb-4" style="border-radius: 16px;">
            <div class="d-flex justify-content-between align-items-center mb-2.5">
                <h6 class="fw-bold m-0 text-dark" style="font-size: 13px;"><i class="bi bi-wallet2 me-1 text-danger"></i> Pembayaran Ongkir</h6>
                <span class="badge text-white px-2.5 py-1" style="background:#002B49; font-size: 9px; font-weight:700; border-radius: 6px;">MIDTRANS</span>
            </div>
            <div class="d-flex flex-column gap-2.5 pt-1">
                <label class="d-flex align-items-center justify-content-between p-2.5 px-3 border rounded-3 cursor-pointer">
                    <div class="d-flex align-items-center gap-2.5">
                        <input type="radio" name="payment_method" value="midtrans">
                        <div>
                            <span class="fw-bold text-dark" style="font-size: 12px;">Bayar Online (Midtrans)</span>
                            <div class="text-muted" style="font-size: 9.5px;">QRIS, GoPay, ShopeePay, VA Bank</div>
                        </div>
                    </div>
                    <span class="badge bg-danger-subtle text-danger py-1 px-2" style="font-size: 9px;">Otomatis</span>
                </label>
                <div class="d-flex gap-3 px-1" style="font-size: 11.5px;">
                    <label class="d-flex align-items-center gap-1.5 cursor-pointer">
                        <input type="radio" name="payment_method" value="cod" checked>
                        <span>Tunai (COD)</span>
                    </label>
                    <label class="d-flex align-items-center gap-1.5 cursor-pointer">
                        <input type="radio" name="payment_method" value="wallet" <?= ((float)$wallet_balance >= 8000) ? '' : 'disabled' ?>>
                        <span class="d-flex align-items-center gap-1">
                            <span style="color:#EE2737;font-weight:800;">CicalengkaPay</span>
                            <span class="text-muted" style="font-size: 10px;">(<?= format_rupiah($wallet_balance ?? 0) ?>)</span>
                        </span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Estimated Fare Dynamic -->
        <div class="p-3.5 bg-white border shadow-xs mb-4 d-flex align-items-center justify-content-between" style="border-radius: 16px;">
            <div>
                <div class="text-muted" style="font-size: 10.5px;">Tarif Instant (<span id="fare-dist-text">2.5 Km</span>)</div>
                <div class="fw-bold" style="color: #EE2737; font-size: 16px;" id="parcel-fare-display">Rp 8.000</div>
            </div>
            <span class="badge text-white px-3 py-1.5 rounded-pill fw-bold" style="background:#EE2737; font-size: 10.5px;">Instant Delivery</span>
        </div>

        <button type="submit" id="btnParcelSubmit" class="btn btn-gojek-green w-100 mb-4" style="background:#EE2737 !important; color:#FFFFFF !important; border-radius:9999px; font-weight:700; padding:12px 18px; font-size:13px; box-shadow:0 3px 12px rgba(238,39,55,0.3); display:flex; align-items:center; justify-content:center; gap:8px;">
            <i class="bi bi-box-seam-fill"></i>
            <span>Panggil Kurir Sekarang</span>
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
                            text: 'Instruksi pembayaran ongkir telah dibuat. Silakan selesaikan pembayaran Anda.',
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
                        window.location.href = window.BASE_URL + '/' + data.data.redirect;
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

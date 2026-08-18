<!-- Header & Filter Toolbar -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold m-0 text-dark"><i class="bi bi-receipt-cutoff text-primary me-2"></i> Pusat Pemantauan & Dispatch Pesanan</h4>
        <div class="text-muted small">Kelola penugasan kurir, lacak rute GPS real-time, dan pantau status seluruh transaksi di Cicalengka.</div>
    </div>
    
    <div class="d-flex align-items-center gap-2">
        <button onclick="window.location.reload()" class="btn btn-light border btn-sm px-3 rounded-3 shadow-xs">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh Data
        </button>
    </div>
</div>

<!-- Status Filter Tabs -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-2">
        <div class="d-flex flex-wrap gap-2">
            <a href="<?= $baseUrl ?>/admin/orders" class="btn btn-sm <?= empty($status_filter) ? 'btn-primary' : 'btn-light border' ?> rounded-pill px-3 fw-semibold">
                Semua (<?= count($orders) ?>)
            </a>
            <a href="<?= $baseUrl ?>/admin/orders?status=pending" class="btn btn-sm <?= ($status_filter === 'pending') ? 'btn-warning text-dark' : 'btn-light border' ?> rounded-pill px-3 fw-semibold">
                Menunggu Konfirmasi
            </a>
            <a href="<?= $baseUrl ?>/admin/orders?status=confirmed" class="btn btn-sm <?= ($status_filter === 'confirmed') ? 'btn-info text-white' : 'btn-light border' ?> rounded-pill px-3 fw-semibold">
                Terkonfirmasi
            </a>
            <a href="<?= $baseUrl ?>/admin/orders?status=processing" class="btn btn-sm <?= ($status_filter === 'processing') ? 'btn-primary' : 'btn-light border' ?> rounded-pill px-3 fw-semibold">
                Sedang Dimasak / Disiapkan
            </a>
            <a href="<?= $baseUrl ?>/admin/orders?status=handover" class="btn btn-sm <?= ($status_filter === 'handover') ? 'btn-secondary text-white' : 'btn-light border' ?> rounded-pill px-3 fw-semibold">
                Siap Diambil Kurir
            </a>
            <a href="<?= $baseUrl ?>/admin/orders?status=on_the_way" class="btn btn-sm <?= ($status_filter === 'on_the_way') ? 'btn-dark' : 'btn-light border' ?> rounded-pill px-3 fw-semibold">
                Sedang Diantar
            </a>
            <a href="<?= $baseUrl ?>/admin/orders?status=delivered" class="btn btn-sm <?= ($status_filter === 'delivered') ? 'btn-success' : 'btn-light border' ?> rounded-pill px-3 fw-semibold">
                Selesai (Delivered)
            </a>
            <a href="<?= $baseUrl ?>/admin/orders?status=canceled" class="btn btn-sm <?= ($status_filter === 'canceled') ? 'btn-danger' : 'btn-light border' ?> rounded-pill px-3 fw-semibold">
                Dibatalkan
            </a>
        </div>
    </div>
</div>

<!-- Live Dispatch Fleet & Orders Radar Map -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 bg-white">
    <div class="card-header bg-white border-0 py-3 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                <i class="bi bi-radar"></i>
            </div>
            <div>
                <h6 class="fw-bold m-0">Live Dispatch & Fleet GPS Radar</h6>
                <div class="text-muted" style="font-size: 11px;">Pemantauan persebaran kurir aktif dan lokasi pesanan berjalan di Cicalengka</div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="bi bi-dot"></i> <?= count($drivers) ?> Driver Aktif</span>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1"><i class="bi bi-dot"></i> <?= count($stores) ?> Mitra Resto</span>
        </div>
    </div>
    <div class="position-relative">
        <div id="admin-dispatch-map" style="width: 100%; height: 320px;"></div>
        <div class="position-absolute bottom-0 start-0 m-3 z-3 bg-white p-2 rounded-3 shadow-sm border small" style="font-size: 11px;">
            <div class="d-flex align-items-center gap-3">
                <span class="d-flex align-items-center gap-1"><span style="width:10px;height:10px;background:#ef4444;border-radius:50%;display:inline-block;"></span> Toko / Resto</span>
                <span class="d-flex align-items-center gap-1"><span style="width:10px;height:10px;background:#2563eb;border-radius:50%;display:inline-block;"></span> Driver Online</span>
                <span class="d-flex align-items-center gap-1"><span style="width:10px;height:10px;background:#10b981;border-radius:50%;display:inline-block;"></span> Tujuan Pengantaran</span>
            </div>
        </div>
    </div>
</div>

<!-- Orders Table Card -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
    <div class="card-header bg-white border-0 py-3 d-flex align-items-center justify-content-between">
        <h6 class="fw-bold m-0"><i class="bi bi-table me-1 text-primary"></i> Daftar Transaksi Pesanan (<?= count($orders) ?>)</h6>
        <div class="text-muted small">Data terurut dari yang terbaru</div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">Kode Order</th>
                    <th>Tipe & Mitra</th>
                    <th>Pelanggan</th>
                    <th>Total Biaya</th>
                    <th>Kurir Ditugaskan</th>
                    <th>Status</th>
                    <th class="text-end pe-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                            <div class="fw-semibold">Tidak ada pesanan yang sesuai dengan filter ini.</div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $o): ?>
                        <?php
                            $delAddr = json_decode($o['delivery_address_json'] ?? '{}', true) ?: [];
                            $statusBadge = match($o['order_status']) {
                                'pending'     => 'bg-warning text-dark',
                                'confirmed'   => 'bg-info text-white',
                                'processing'  => 'bg-primary',
                                'handover'    => 'bg-secondary',
                                'picked_up', 'on_the_way' => 'bg-dark',
                                'delivered'   => 'bg-success',
                                'canceled'    => 'bg-danger',
                                default       => 'bg-light text-dark border'
                            };
                            $isParcel = ($o['order_type'] === 'parcel');
                        ?>
                        <tr>
                            <td class="ps-3">
                                <div class="fw-bold text-primary">#<?= htmlspecialchars($o['order_code']) ?></div>
                                <div class="text-muted" style="font-size: 11px;"><?= date('d M Y, H:i', strtotime($o['created_at'])) ?></div>
                            </td>
                            <td>
                                <?php if ($isParcel): ?>
                                    <span class="badge bg-purple-subtle text-purple fw-bold mb-1" style="background:#f3e8ff;color:#7e22ce;"><i class="bi bi-box-seam me-1"></i> Cicalengka Parcel</span>
                                    <div class="small fw-semibold text-dark">Kirim Paket Kilat</div>
                                <?php else: ?>
                                    <span class="badge bg-primary-subtle text-primary fw-bold mb-1"><i class="bi bi-shop me-1"></i> Food & Mart</span>
                                    <div class="small fw-bold text-dark"><?= htmlspecialchars($o['store_name'] ?? 'Mitra Toko') ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="small fw-bold text-dark"><?= htmlspecialchars($o['customer_name'] ?? 'Pelanggan') ?></div>
                                <div class="text-muted small" style="font-size: 11px;"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($o['customer_phone'] ?? '-') ?></div>
                                <?php if (!empty($delAddr['address'])): ?>
                                    <div class="text-muted text-truncate" style="max-width: 180px; font-size: 11px;" title="<?= htmlspecialchars($delAddr['address']) ?>">
                                        <i class="bi bi-geo-alt me-1 text-danger"></i><?= htmlspecialchars($delAddr['address']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= format_rupiah($o['total_amount']) ?></div>
                                <span class="badge <?= $o['payment_status'] === 'paid' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' ?> py-0 px-2" style="font-size: 10px;">
                                    <?= strtoupper($o['payment_method']) ?> • <?= $o['payment_status'] === 'paid' ? 'LUNAS' : 'BELUM BAYAR' ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($o['dm_name'])): ?>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 12px;">
                                            <i class="bi bi-bicycle"></i>
                                        </div>
                                        <div>
                                            <div class="small fw-bold text-dark"><?= htmlspecialchars($o['dm_name']) ?></div>
                                            <div class="text-muted" style="font-size: 10px;"><?= htmlspecialchars($o['dm_phone'] ?? '-') ?></div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <button onclick="openAssignDriverModal(<?= $o['id'] ?>, '<?= htmlspecialchars($o['order_code']) ?>')" class="btn btn-outline-primary btn-sm rounded-pill py-1 px-2 fw-semibold" style="font-size: 11px;">
                                        <i class="bi bi-person-plus me-1"></i> Tugaskan Kurir
                                    </button>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?= $statusBadge ?> text-uppercase py-1 px-2" style="font-size: 10px;">
                                    <?= str_replace('_', ' ', $o['order_status']) ?>
                                </span>
                                <?php if (!empty($o['delivery_otp']) && $o['order_status'] !== 'delivered'): ?>
                                    <div class="text-muted fw-semibold mt-1" style="font-size: 10px;">OTP: <span class="text-primary fw-bold"><?= $o['delivery_otp'] ?></span></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-3">
                                <div class="d-flex align-items-center justify-content-end gap-1">
                                    <div class="dropdown">
                                        <button class="btn btn-light btn-sm border dropdown-toggle fw-semibold" type="button" data-bs-toggle="dropdown">
                                            Kelola
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
                                            <li>
                                                <button class="dropdown-item small py-2" onclick="viewOrderDetail(<?= $o['id'] ?>)">
                                                    <i class="bi bi-eye text-primary me-2"></i> Rincian Pesanan
                                                </button>
                                            </li>
                                            <li>
                                                <button class="dropdown-item small py-2 text-danger fw-semibold" onclick="viewMidtransDetail('<?= htmlspecialchars($o['order_code']) ?>')">
                                                    <i class="bi bi-credit-card-2-front-fill me-2"></i> Detail Midtrans API
                                                </button>
                                            </li>
                                            <li>
                                                <a class="dropdown-item small py-2" href="<?= $baseUrl ?>/admin/orders/invoice/<?= $o['id'] ?>" target="_blank">
                                                    <i class="bi bi-printer text-success me-2"></i> Cetak Faktur / Nota
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <button class="dropdown-item small py-2 text-primary" onclick="openAssignDriverModal(<?= $o['id'] ?>, '<?= htmlspecialchars($o['order_code']) ?>')">
                                                    <i class="bi bi-bicycle me-2"></i> Ganti / Tugaskan Driver
                                                </button>
                                            </li>
                                            <li>
                                                <button class="dropdown-item small py-2 text-info text-dark" onclick="promptUpdateStatus(<?= $o['id'] ?>, '<?= htmlspecialchars($o['order_code']) ?>', '<?= $o['order_status'] ?>')">
                                                    <i class="bi bi-arrow-repeat me-2 text-info"></i> Update Status Pesanan
                                                </button>
                                            </li>
                                            <li>
                                                <button class="dropdown-item small py-2 text-warning" onclick="promptCancelOrder(<?= $o['id'] ?>, '<?= htmlspecialchars($o['order_code']) ?>')">
                                                    <i class="bi bi-x-circle me-2"></i> Batalkan Pesanan
                                                </button>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <button class="dropdown-item small py-2 text-danger fw-bold" onclick="deleteOrder(<?= $o['id'] ?>, '<?= htmlspecialchars($o['order_code']) ?>')">
                                                    <i class="bi bi-trash3-fill me-2"></i> Hapus Pesanan Permanen
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                    <button type="button" class="btn btn-outline-danger btn-sm border-0 rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;" title="Hapus Pesanan #<?= htmlspecialchars($o['order_code']) ?>" onclick="deleteOrder(<?= $o['id'] ?>, '<?= htmlspecialchars($o['order_code']) ?>')">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Assign Driver Modal -->
<div class="modal fade" id="assignDriverModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold"><i class="bi bi-bicycle text-primary me-2"></i>Tugaskan Kurir ke Pesanan</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">Pilih mitra kurir aktif untuk mengantarkan order <span id="assign-order-code" class="fw-bold text-primary"></span></p>
                <form id="assignDriverForm" onsubmit="submitAssignDriver(event)">
                    <input type="hidden" name="order_id" id="assign_order_id">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Pilih Mitra Kurir</label>
                        <select name="delivery_man_id" id="delivery_man_select" class="form-select rounded-3 py-2" required>
                            <option value="">-- Pilih Kurir Aktif --</option>
                            <?php foreach ($drivers as $d): ?>
                                <option value="<?= $d['id'] ?>">
                                    <?= htmlspecialchars($d['name']) ?> (<?= htmlspecialchars($d['phone']) ?>) - <?= strtoupper($d['vehicle_type'] ?? 'Motor') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="d-flex justify-content-end gap-2 pt-2">
                        <button type="button" class="btn btn-light btn-sm px-3 rounded-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm px-4 rounded-3 fw-bold">Tugaskan Sekarang</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Order Detail Modal -->
<div class="modal fade" id="orderDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="modal-order-title">Rincian Pesanan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modal-order-content">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Midtrans Transaction Detail Modal -->
<div class="modal fade" id="midtransDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-danger-subtle text-danger d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                        <i class="bi bi-credit-card-2-front-fill fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark m-0">Detail Transaksi Midtrans API</h5>
                        <div class="text-muted small">Status pembayaran langsung dari Server Midtrans API</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="midtrans-modal-body">
                <div class="text-center py-5">
                    <div class="spinner-border text-danger" role="status"></div>
                    <div class="text-muted small mt-2">Menghubungi Server Midtrans API...</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let dispatchMap;

function initAdminDispatchMap() {
    const mapEl = document.getElementById('admin-dispatch-map');
    if (!mapEl) return;

    dispatchMap = L.map('admin-dispatch-map').setView([-6.9840, 107.8340], 14);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap CicalengkaGO'
    }).addTo(dispatchMap);

    // Cicalengka Operational Zone Area Polygon
    const cicalengkaZoneCoords = [
        [-6.9700, 107.8150],
        [-6.9700, 107.8550],
        [-6.9950, 107.8650],
        [-7.0050, 107.8350],
        [-6.9950, 107.8100]
    ];
    L.polygon(cicalengkaZoneCoords, {
        color: '#2563eb',
        fillColor: '#3b82f6',
        fillOpacity: 0.08,
        weight: 1.5,
        dashArray: '4, 6'
    }).addTo(dispatchMap);

    const storeSvg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 46" width="32" height="46">
      <defs>
        <linearGradient id="asg" x1="0%" y1="0%" x2="100%" y2="100%">
          <stop offset="0%" stop-color="#f87171"/>
          <stop offset="100%" stop-color="#b91c1c"/>
        </linearGradient>
      </defs>
      <path d="M16 2C9 2 3 8 3 15c0 10 13 29 13 29S29 25 29 15C29 8 23 2 16 2z" fill="url(#asg)" stroke="white" stroke-width="2"/>
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

    const driverSvg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 46" width="32" height="46">
      <defs>
        <linearGradient id="adg" x1="0%" y1="0%" x2="100%" y2="100%">
          <stop offset="0%" stop-color="#3b82f6"/>
          <stop offset="100%" stop-color="#1d4ed8"/>
        </linearGradient>
      </defs>
      <path d="M16 2C9 2 3 8 3 15c0 10 13 29 13 29S29 25 29 15C29 8 23 2 16 2z" fill="url(#adg)" stroke="white" stroke-width="2"/>
      <circle cx="11" cy="17" r="3.5" fill="none" stroke="white" stroke-width="1.8"/>
      <circle cx="21" cy="17" r="3.5" fill="none" stroke="white" stroke-width="1.8"/>
      <polyline points="11,17 15,12 21,17" fill="none" stroke="white" stroke-width="1.8" stroke-linejoin="round" stroke-linecap="round"/>
      <line x1="15" y1="12" x2="15" y2="17" stroke="white" stroke-width="1.8" stroke-linecap="round"/>
      <circle cx="17" cy="10" r="2" fill="white"/>
    </svg>`;
    const driverIcon = L.icon({
        iconUrl: 'data:image/svg+xml,' + encodeURIComponent(driverSvg),
        iconSize: [32, 46],
        iconAnchor: [16, 46],
        popupAnchor: [0, -46]
    });

    const orderSvg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 46" width="32" height="46">
      <defs>
        <linearGradient id="aog" x1="0%" y1="0%" x2="100%" y2="100%">
          <stop offset="0%" stop-color="#34d399"/>
          <stop offset="100%" stop-color="#047857"/>
        </linearGradient>
      </defs>
      <path d="M16 2C9 2 3 8 3 15c0 10 13 29 13 29S29 25 29 15C29 8 23 2 16 2z" fill="url(#aog)" stroke="white" stroke-width="2"/>
      <circle cx="16" cy="11" r="3.8" fill="white"/>
      <path d="M9 22 Q9 17 16 17 Q23 17 23 22" fill="white"/>
    </svg>`;
    const orderPinIcon = L.icon({
        iconUrl: 'data:image/svg+xml,' + encodeURIComponent(orderSvg),
        iconSize: [32, 46],
        iconAnchor: [16, 46],
        popupAnchor: [0, -46]
    });

    // Render Stores Markers
    <?php foreach ($stores as $st): ?>
        <?php if (!empty($st['latitude']) && !empty($st['longitude'])): ?>
            L.marker([<?= (float)$st['latitude'] ?>, <?= (float)$st['longitude'] ?>], { icon: storeIcon })
                .bindPopup("<b>Mitra: <?= addslashes($st['name']) ?></b><br><small><?= addslashes($st['address']) ?></small>")
                .addTo(dispatchMap);
        <?php endif; ?>
    <?php endforeach; ?>

    // Render Drivers Markers
    <?php foreach ($drivers as $dr): ?>
        <?php if (!empty($dr['current_latitude']) && !empty($dr['current_longitude'])): ?>
            L.marker([<?= (float)$dr['current_latitude'] ?>, <?= (float)$dr['current_longitude'] ?>], { icon: driverIcon })
                .bindPopup("<b>Driver: <?= addslashes($dr['name']) ?></b><br><small>Telp: <?= addslashes($dr['phone']) ?></small>")
                .addTo(dispatchMap);
        <?php endif; ?>
    <?php endforeach; ?>

    // Render Orders Active Delivery Markers
    <?php foreach ($orders as $ord): ?>
        <?php 
            $dAddr = json_decode($ord['delivery_address_json'] ?? '{}', true) ?: [];
            $oLat = (float)($dAddr['latitude'] ?? 0);
            $oLng = (float)($dAddr['longitude'] ?? 0);
        ?>
        <?php if ($oLat != 0 && $oLng != 0 && in_array($ord['order_status'], ['confirmed', 'processing', 'handover', 'on_the_way'])): ?>
            L.marker([<?= $oLat ?>, <?= $oLng ?>], { icon: orderPinIcon })
                .bindPopup("<b>Order #<?= addslashes($ord['order_code']) ?></b><br>Pelanggan: <?= addslashes($ord['customer_name'] ?? '') ?><br><small>Status: <?= $ord['order_status'] ?></small>")
                .addTo(dispatchMap);
        <?php endif; ?>
    <?php endforeach; ?>
}

document.addEventListener('DOMContentLoaded', initAdminDispatchMap);

function openAssignDriverModal(orderId, orderCode) {
    document.getElementById('assign_order_id').value = orderId;
    document.getElementById('assign-order-code').textContent = '#' + orderCode;
    const modal = new bootstrap.Modal(document.getElementById('assignDriverModal'));
    modal.show();
}

async function submitAssignDriver(e) {
    e.preventDefault();
    const orderId = document.getElementById('assign_order_id').value;
    const driverId = document.getElementById('delivery_man_select').value;

    if (!driverId) {
        Swal.fire('Perhatian', 'Pilih driver terlebih dahulu.', 'warning');
        return;
    }

    try {
        const formData = new FormData();
        formData.append('order_id', orderId);
        formData.append('delivery_man_id', driverId);

        const res = await fetch(window.BASE_URL + '/admin/orders/assign-driver', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data.success) {
            Swal.fire('Berhasil', 'Kurir berhasil ditugaskan ke pesanan.', 'success').then(() => {
                location.reload();
            });
        } else {
            Swal.fire('Gagal', data.message || 'Gagal menugaskan kurir.', 'error');
        }
    } catch (err) {
        Swal.fire('Error', 'Terjadi kesalahan sistem.', 'error');
    }
}

async function viewOrderDetail(orderId) {
    const modal = new bootstrap.Modal(document.getElementById('orderDetailModal'));
    modal.show();

    const contentEl = document.getElementById('modal-order-content');
    contentEl.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>';

    try {
        const res = await fetch(window.BASE_URL + '/admin/orders/detail/' + orderId);
        const data = await res.json();

        if (data.success) {
            const o = data.data;
            let itemsHtml = '';
            if (o.items && o.items.length > 0) {
                itemsHtml = `
                    <table class="table table-sm align-middle mt-2">
                        <thead class="table-light"><tr><th>Produk</th><th class="text-center">Qty</th><th class="text-end">Harga</th><th class="text-end">Subtotal</th></tr></thead>
                        <tbody>
                            ${o.items.map(it => `
                                <tr>
                                    <td>${it.item_name}</td>
                                    <td class="text-center">${it.quantity}</td>
                                    <td class="text-end">Rp ${Number(it.price).toLocaleString('id-ID')}</td>
                                    <td class="text-end fw-bold">Rp ${Number(it.total_price).toLocaleString('id-ID')}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;
            }

            contentEl.innerHTML = `
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-4">
                            <div class="text-muted small">INFORMASI PESANAN</div>
                            <div class="fw-bold fs-6 text-primary">#${o.order_code}</div>
                            <div class="small">Tipe: <b class="text-uppercase">${o.order_type}</b></div>
                            <div class="small">Status: <span class="badge bg-primary text-uppercase">${o.order_status}</span></div>
                            <div class="small">Metode Bayar: <b>${(o.payment_method || '').toUpperCase()} (${(o.payment_status || '').toUpperCase()})</b></div>
                            <div class="small mt-2">OTP Pengantaran: <span class="badge bg-dark fs-6">${o.delivery_otp || '-'}</span></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-4">
                            <div class="text-muted small">PELANGGAN & ALAMAT</div>
                            <div class="fw-bold">${o.customer_name}</div>
                            <div class="small text-muted"><i class="bi bi-telephone me-1"></i>${o.customer_phone || '-'}</div>
                            <div class="small text-dark mt-1"><i class="bi bi-geo-alt-fill text-danger me-1"></i>${o.delivery_address ? o.delivery_address.address : '-'}</div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="p-3 bg-light rounded-4">
                            <div class="fw-bold small mb-1">Rincian Item / Barang:</div>
                            ${itemsHtml || '<div class="text-muted small">Order Parcel / Layanan Ekspedisi</div>'}
                            <div class="d-flex justify-content-between border-top pt-2 mt-2 fw-bold">
                                <span>Total Tagihan:</span>
                                <span class="text-primary fs-6">Rp ${Number(o.total_amount).toLocaleString('id-ID')}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 border-top pt-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div class="d-flex flex-wrap gap-2">
                            <a href="${window.BASE_URL}/admin/orders/invoice/${o.id}" target="_blank" class="btn btn-sm btn-outline-success rounded-pill px-3 fw-semibold">
                                <i class="bi bi-printer me-1"></i> Cetak Faktur
                            </a>
                            <button type="button" onclick="promptUpdateStatus(${o.id}, '${o.order_code}', '${o.order_status}')" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-semibold">
                                <i class="bi bi-arrow-repeat me-1"></i> Ubah Status
                            </button>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" onclick="promptCancelOrder(${o.id}, '${o.order_code}')" class="btn btn-sm btn-outline-warning rounded-pill px-3 fw-semibold">
                                <i class="bi bi-x-circle me-1"></i> Batalkan
                            </button>
                            <button type="button" onclick="deleteOrder(${o.id}, '${o.order_code}')" class="btn btn-sm btn-danger rounded-pill px-3 fw-bold shadow-xs">
                                <i class="bi bi-trash3-fill me-1"></i> Hapus Pesanan
                            </button>
                        </div>
                    </div>
                </div>
            `;
        } else {
            contentEl.innerHTML = '<div class="alert alert-danger">Gagal mengambil data pesanan.</div>';
        }
    } catch (e) {
        contentEl.innerHTML = '<div class="alert alert-danger">Terjadi kesalahan saat memuat data.</div>';
    }
}

// Hapus Pesanan Permanen (Admin Action)
function deleteOrder(orderId, orderCode) {
    Swal.fire({
        title: 'Hapus Pesanan?',
        html: `Apakah Anda yakin ingin menghapus pesanan <strong>#${orderCode}</strong> secara permanen?<br><div class="alert alert-danger p-2 mt-3 mb-0 text-start small"><i class="bi bi-exclamation-triangle-fill me-1"></i> <strong>Perhatian:</strong> Seluruh data rincian item, riwayat GPS kurir, ulasan, dan relasi pesanan ini akan dihapus dari sistem.</div>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '<i class="bi bi-trash3-fill me-1"></i> Ya, Hapus Sekarang',
        cancelButtonText: 'Batal',
        reverseButtons: true
    }).then(async (result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Menghapus Pesanan...',
                text: 'Sedang memproses pembersihan data...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            try {
                const formData = new FormData();
                formData.append('order_id', orderId);

                const res = await fetch(window.BASE_URL + '/admin/orders/delete', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (data.success) {
                    // Close detail modal if open
                    const detailModalEl = document.getElementById('orderDetailModal');
                    const modalInst = bootstrap.Modal.getInstance(detailModalEl);
                    if (modalInst) modalInst.hide();

                    Swal.fire({
                        icon: 'success',
                        title: 'Pesanan Dihapus!',
                        text: data.message || 'Pesanan berhasil dihapus secara permanen.',
                        timer: 1600,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire('Gagal Menghapus', data.message || 'Terjadi kesalahan sistem.', 'error');
                }
            } catch (err) {
                Swal.fire('Error', 'Terjadi kesalahan jaringan atau server.', 'error');
            }
        }
    });
}

// Quick Update Status Pesanan
async function promptUpdateStatus(orderId, orderCode, currentStatus) {
    const { value: newStatus } = await Swal.fire({
        title: `Update Status #${orderCode}`,
        input: 'select',
        inputOptions: {
            'pending': 'Pending (Menunggu Konfirmasi)',
            'confirmed': 'Confirmed (Terkonfirmasi)',
            'processing': 'Processing (Sedang Disiapkan)',
            'handover': 'Handover (Siap Diambil Kurir)',
            'on_the_way': 'On The Way (Sedang Diantar)',
            'delivered': 'Delivered (Selesai)',
            'canceled': 'Canceled (Dibatalkan)'
        },
        inputValue: currentStatus,
        showCancelButton: true,
        confirmButtonText: 'Simpan Status',
        cancelButtonText: 'Batal'
    });

    if (newStatus && newStatus !== currentStatus) {
        Swal.fire({ title: 'Menyimpan...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });

        try {
            const formData = new FormData();
            formData.append('order_id', orderId);
            formData.append('status', newStatus);

            const res = await fetch(window.BASE_URL + '/admin/orders/update-status', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.success) {
                Swal.fire({ icon: 'success', title: 'Berhasil', text: data.message, timer: 1500, showConfirmButton: false })
                    .then(() => location.reload());
            } else {
                Swal.fire('Gagal', data.message || 'Gagal mengubah status', 'error');
            }
        } catch (e) {
            Swal.fire('Error', 'Gagal menghubungi server.', 'error');
        }
    }
}

// Prompt Batalkan Pesanan
async function promptCancelOrder(orderId, orderCode) {
    const { value: reason } = await Swal.fire({
        title: `Batalkan Order #${orderCode}?`,
        input: 'text',
        inputLabel: 'Alasan Pembatalan',
        placeholder: 'Contoh: Pelanggan meminta pembatalan / Toko tutup',
        showCancelButton: true,
        confirmButtonColor: '#d97706',
        confirmButtonText: 'Batalkan Pesanan',
        cancelButtonText: 'Tutup'
    });

    if (reason !== undefined) {
        Swal.fire({ title: 'Membatalkan...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });

        try {
            const formData = new FormData();
            formData.append('order_id', orderId);
            formData.append('reason', reason || 'Dibatalkan oleh Administrator');

            const res = await fetch(window.BASE_URL + '/admin/orders/cancel', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.success) {
                Swal.fire({ icon: 'success', title: 'Pesanan Dibatalkan', text: data.message, timer: 1500, showConfirmButton: false })
                    .then(() => location.reload());
            } else {
                Swal.fire('Gagal', data.message || 'Gagal membatalkan pesanan', 'error');
            }
        } catch (e) {
            Swal.fire('Error', 'Gagal menghubungi server.', 'error');
        }
    }
}

async function viewMidtransDetail(orderCode) {
    const modal = new bootstrap.Modal(document.getElementById('midtransDetailModal'));
    modal.show();

    const bodyEl = document.getElementById('midtrans-modal-body');
    bodyEl.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-danger" role="status"></div>
            <div class="text-muted small mt-2">Menghubungi Server Midtrans API untuk Order #${orderCode}...</div>
        </div>
    `;

    try {
        const res = await fetch(window.BASE_URL + '/admin/midtrans/status/' + orderCode);
        const resData = await res.json();

        if (!resData.success) {
            bodyEl.innerHTML = `<div class="alert alert-danger mb-0">${resData.message || 'Terjadi kesalahan sistem.'}</div>`;
            return;
        }

        const mt = resData.midtrans;
        const dbOrder = resData.db_order || {};

        if (!mt.success && !mt.data) {
            bodyEl.innerHTML = `
                <div class="text-center py-4">
                    <i class="bi bi-exclamation-triangle-fill text-warning fs-1 d-block mb-2"></i>
                    <h6 class="fw-bold text-dark mb-1">Transaksi Belum Terdaftar di Midtrans</h6>
                    <p class="text-muted small mb-3">Order #${orderCode} mungkin menggunakan metode pembayaran Cash / COD, atau belum membuka popup Snap.</p>
                    <div class="p-3 bg-light rounded-3 text-start small">
                        <div><b>Metode Bayar di DB:</b> ${dbOrder.payment_method ? dbOrder.payment_method.toUpperCase() : '-'}</div>
                        <div><b>Status Bayar di DB:</b> ${dbOrder.payment_status ? dbOrder.payment_status.toUpperCase() : '-'}</div>
                        <div><b>Pesan API:</b> ${mt.message || '-'}</div>
                    </div>
                </div>
            `;
            return;
        }

        const d = mt.data || {};
        const statusBadge = (d.transaction_status === 'settlement' || d.transaction_status === 'capture')
            ? '<span class="badge bg-success fs-6 px-3 py-1.5"><i class="bi bi-check-circle-fill me-1"></i> SETTLEMENT (LUNAS)</span>'
            : (d.transaction_status === 'pending')
            ? '<span class="badge bg-warning text-dark fs-6 px-3 py-1.5"><i class="bi bi-clock-history me-1"></i> PENDING (MENUNGGU BAYAR)</span>'
            : `<span class="badge bg-danger fs-6 px-3 py-1.5"><i class="bi bi-x-circle-fill me-1"></i> ${String(d.transaction_status || 'FAILED').toUpperCase()}</span>`;

        let vaInfo = '';
        if (d.va_numbers && d.va_numbers.length > 0) {
            vaInfo = d.va_numbers.map(v => `
                <div class="d-flex align-items-center justify-content-between p-2 bg-white rounded border mb-1">
                    <span class="fw-bold text-uppercase">${v.bank} Virtual Account:</span>
                    <code class="fs-6 fw-bold text-primary">${v.va_number}</code>
                </div>
            `).join('');
        }

        bodyEl.innerHTML = `
            <div class="row g-3">
                <div class="col-12 text-center pb-2 border-bottom">
                    <div class="text-muted small mb-1">STATUS TRANSAKSI MIDTRANS</div>
                    ${statusBadge}
                </div>

                <div class="col-md-6">
                    <div class="p-3 bg-light rounded-3 h-100">
                        <div class="fw-bold text-dark small mb-2"><i class="bi bi-receipt me-1 text-danger"></i> Rincian Midtrans</div>
                        <div class="small mb-1"><b>Order ID:</b> <code>${d.order_id || orderCode}</code></div>
                        <div class="small mb-1"><b>Transaction ID:</b> <code style="font-size: 10px;">${d.transaction_id || '-'}</code></div>
                        <div class="small mb-1"><b>Metode Bayar:</b> <span class="badge bg-dark text-uppercase">${d.payment_type || '-'}</span></div>
                        <div class="small mb-1"><b>Bank / Channel:</b> ${d.bank || (d.va_numbers ? d.va_numbers[0]?.bank : '-')}</div>
                        <div class="small mb-1"><b>Status Code:</b> <code>${d.status_code || '-'}</code></div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="p-3 bg-light rounded-3 h-100">
                        <div class="fw-bold text-dark small mb-2"><i class="bi bi-currency-dollar me-1 text-danger"></i> Nominal & Waktu</div>
                        <div class="small mb-1"><b>Gross Amount:</b> <span class="fw-bold text-danger fs-6">Rp ${Number(d.gross_amount || 0).toLocaleString('id-ID')}</span></div>
                        <div class="small mb-1"><b>Waktu Transaksi:</b> ${d.transaction_time || '-'}</div>
                        <div class="small mb-1"><b>Waktu Settlement:</b> ${d.settlement_time || '-'}</div>
                        <div class="small mb-1"><b>Fraud Status:</b> ${d.fraud_status || 'accept'}</div>
                    </div>
                </div>

                ${vaInfo ? `<div class="col-12"><div class="p-3 bg-light rounded-3"><div class="fw-bold small mb-2">Instruksi Pembayaran VA:</div>${vaInfo}</div></div>` : ''}

                <div class="col-12 pt-2">
                    <button class="btn btn-sm btn-outline-secondary w-100" type="button" data-bs-toggle="collapse" data-bs-target="#rawMidtransJson">
                        <i class="bi bi-code-slash me-1"></i> Lihat Data Mentah JSON API Midtrans
                    </button>
                    <div class="collapse mt-2" id="rawMidtransJson">
                        <pre class="bg-dark text-success p-3 rounded-3 small mb-0" style="max-height: 200px; overflow-y: auto;">${JSON.stringify(d, null, 2)}</pre>
                    </div>
                </div>
            </div>
        `;
    } catch (err) {
        bodyEl.innerHTML = `<div class="alert alert-danger mb-0">Terjadi kesalahan saat memuat detail dari Midtrans.</div>`;
    }
}
</script>

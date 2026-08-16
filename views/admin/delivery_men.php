<?php
    $countTotalDrivers = count($drivers);
    $countOnlineDrivers = count(array_filter($drivers, fn($d) => !empty($d['is_online'])));
    $countActiveDrivers = count(array_filter($drivers, fn($d) => !empty($d['is_active'])));
    $totalFleetBalance = array_sum(array_column($drivers, 'wallet_balance'));
?>

<!-- Drivers KPI Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <small class="text-muted fw-semibold">Total Armada Driver</small>
                    <h4 class="fw-black text-dark mb-0 mt-1"><?= $countTotalDrivers ?></h4>
                </div>
                <div style="width:42px;height:42px;border-radius:12px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;color:#475569;font-size:20px;">
                    <i class="bi bi-bicycle"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <small class="text-success fw-semibold">Driver Online Siaga</small>
                    <h4 class="fw-black text-success mb-0 mt-1"><?= $countOnlineDrivers ?></h4>
                </div>
                <div style="width:42px;height:42px;border-radius:12px;background:#f0fdf4;display:flex;align-items:center;justify-content:center;color:#16a34a;font-size:20px;">
                    <i class="bi bi-broadcast-pin"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <small class="text-primary fw-semibold">Akun Terverifikasi</small>
                    <h4 class="fw-black text-primary mb-0 mt-1"><?= $countActiveDrivers ?></h4>
                </div>
                <div style="width:42px;height:42px;border-radius:12px;background:#eff6ff;display:flex;align-items:center;justify-content:center;color:#2563eb;font-size:20px;">
                    <i class="bi bi-person-check-fill"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <small class="text-secondary fw-semibold">Total Saldo Armada</small>
                    <h5 class="fw-black text-success mb-0 mt-1"><?= format_rupiah($totalFleetBalance) ?></h5>
                </div>
                <div style="width:42px;height:42px;border-radius:12px;background:#f0fdf4;display:flex;align-items:center;justify-content:center;color:#16a34a;font-size:20px;">
                    <i class="bi bi-wallet2"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Header & Action Bar -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3 flex-grow-1" style="max-width: 400px;">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0 rounded-start-pill"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="driverSearchInput" onkeyup="filterDriverTable()" class="form-control bg-light border-start-0 rounded-end-pill" placeholder="Cari Nama Driver, Plat Nomor, No HP...">
                    </div>
                </div>
                <button type="button" class="btn btn-primary rounded-pill px-4 fw-bold" onclick="openAddDriverModal()">
                    <i class="bi bi-person-plus-fill me-1"></i> Daftarkan Driver Baru
                </button>
            </div>
        </div>
    </div>

    <!-- Drivers Table List -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="driversTable">
                    <thead>
                        <tr>
                            <th>Mitra Driver</th>
                            <th>Kontak & Email</th>
                            <th>Kendaraan & Plat</th>
                            <th>Zona Operasi</th>
                            <th>Saldo Dompet</th>
                            <th>Status GPS</th>
                            <th>Status Akun</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($drivers)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">Belum ada armada driver terdaftar.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($drivers as $d): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <div style="width: 44px; height: 44px; border-radius: 12px; background: #eff6ff; display: flex; align-items: center; justify-content: center; font-size: 20px; color: #2563eb;">
                                                <i class="bi bi-bicycle"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold small text-dark"><?= htmlspecialchars($d['name']) ?></div>
                                                <small class="text-muted">ID: CCG-DRV-<?= $d['id'] ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="small fw-semibold"><?= htmlspecialchars($d['phone']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($d['email']) ?></small>
                                    </td>
                                    <td>
                                        <div class="fw-semibold small text-dark"><?= htmlspecialchars($d['vehicle_type']) ?></div>
                                        <span class="badge bg-light text-dark border"><?= htmlspecialchars($d['vehicle_number']) ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary-subtle text-primary"><?= htmlspecialchars($d['zone_name'] ?? 'Zona Cicalengka') ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-success small"><?= format_rupiah($d['wallet_balance'] ?? 0) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge <?= !empty($d['is_online']) ? 'bg-success' : 'bg-secondary' ?>">
                                            <?= !empty($d['is_online']) ? '🟢 ONLINE' : '⚪ OFFLINE' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?= !empty($d['is_active']) ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?>">
                                            <?= !empty($d['is_active']) ? 'AKTIF' : 'DIBLOKIR' ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="dropdown">
                                            <button class="btn btn-light btn-sm rounded-pill px-3 dropdown-toggle fw-semibold" type="button" data-bs-toggle="dropdown" data-bs-boundary="viewport">
                                                Aksi
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3">
                                                <li>
                                                    <a class="dropdown-item py-2 small" href="javascript:void(0)" onclick='openTopupDriverModal(<?= $d['user_id'] ?>, "<?= addslashes($d['name']) ?>")'>
                                                        <i class="bi bi-wallet2 text-success me-2"></i> Top-up Saldo Dompet
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item py-2 small" href="javascript:void(0)" onclick='viewDriverWalletHistory(<?= $d['user_id'] ?>, "<?= addslashes($d['name']) ?>")'>
                                                        <i class="bi bi-clock-history text-info me-2"></i> Riwayat Mutasi Dompet
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item py-2 small" href="javascript:void(0)" onclick='openEditDriverModal(<?= json_encode($d) ?>)'>
                                                        <i class="bi bi-pencil-square text-primary me-2"></i> Edit Data Kurir
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item py-2 small" href="javascript:void(0)" onclick="toggleDriverStatus(<?= $d['id'] ?>, <?= !empty($d['is_active']) ? 0 : 1 ?>)">
                                                        <i class="bi bi-power <?= !empty($d['is_active']) ? 'text-warning' : 'text-success' ?> me-2"></i>
                                                        <?= !empty($d['is_active']) ? 'Blokir Driver' : 'Buka Blokir Driver' ?>
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <a class="dropdown-item py-2 small text-danger" href="javascript:void(0)" onclick="deleteDriver(<?= $d['id'] ?>, '<?= addslashes($d['name']) ?>')">
                                                        <i class="bi bi-trash me-2"></i> Hapus Driver
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Driver Form (Create / Edit) -->
<div class="modal fade" id="driverModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom py-3">
                <h6 class="modal-title fw-bold" id="driverModalTitle">Daftarkan Mitra Driver Baru</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= $baseUrl ?>/admin/delivery-men/save" method="POST">
                <input type="hidden" name="id" id="driverId">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-bold">Nama Lengkap Driver *</label>
                            <input type="text" name="name" id="driverName" class="form-control rounded-3" placeholder="Contoh: Budi Santoso" required>
                        </div>
                        <div class="col-md-6" id="driverEmailCol">
                            <label class="form-label small fw-bold">Email Login Driver *</label>
                            <input type="email" name="email" id="driverEmail" class="form-control rounded-3" placeholder="driver@cicalengkago.id" required>
                        </div>
                        <div class="col-md-6" id="driverPassCol">
                            <label class="form-label small fw-bold">Password Login</label>
                            <input type="password" name="password" id="driverPassword" class="form-control rounded-3" placeholder="Default: 123456">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Nomor WhatsApp / HP *</label>
                            <input type="text" name="phone" id="driverPhone" class="form-control rounded-3" placeholder="08xxxxxxxx" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Jenis Kendaraan</label>
                            <select name="vehicle_type" id="driverVehicleType" class="form-select rounded-3">
                                <option value="Sepeda Motor">Sepeda Motor</option>
                                <option value="Mobil">Mobil Pickup / Van</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Nomor Plat Polisi *</label>
                            <input type="text" name="vehicle_number" id="driverVehicleNum" class="form-control rounded-3" placeholder="D 4567 ABC" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Zona Operasi Penugasan</label>
                            <select name="zone_id" id="driverZoneId" class="form-select rounded-3">
                                <?php foreach ($zones as $z): ?>
                                    <option value="<?= $z['id'] ?>"><?= htmlspecialchars($z['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top py-3">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">Simpan Driver</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Top-up Driver Wallet -->
<div class="modal fade" id="topupDriverModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom py-3">
                <h6 class="modal-title fw-bold">Top-up Saldo Dompet Driver</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="topupDriverForm" onsubmit="handleTopupDriver(event)">
                <input type="hidden" name="user_id" id="topupUserId">
                <div class="modal-body p-4">
                    <p class="small text-muted mb-3">Isi saldo dompet penarikan untuk mitra driver <b id="topupDriverName"></b>.</p>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nominal Top-up (Rp)</label>
                        <input type="number" name="amount" class="form-control rounded-3" placeholder="Contoh: 50000" min="10000" step="5000" required>
                    </div>
                </div>
                <div class="modal-footer border-top py-3">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold">Tambah Saldo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Driver Wallet Mutation History -->
<div class="modal fade" id="driverWalletModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom py-3">
                <h6 class="modal-title fw-bold" id="driverWalletModalTitle">Riwayat Mutasi Saldo Driver</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="driverWalletModalBody">
                <div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>
            </div>
        </div>
    </div>
</div>

<script>
let driverModal = null;

function filterDriverTable() {
    const input = document.getElementById('driverSearchInput').value.toLowerCase();
    const rows = document.querySelectorAll('#driversTable tbody tr');
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(input) ? '' : 'none';
    });
}

function openAddDriverModal() {
    document.getElementById('driverModalTitle').textContent = 'Daftarkan Mitra Driver Baru';
    document.getElementById('driverId').value = '';
    document.getElementById('driverName').value = '';
    document.getElementById('driverEmail').value = '';
    document.getElementById('driverPhone').value = '';
    document.getElementById('driverVehicleNum').value = 'D ';
    document.getElementById('driverEmailCol').style.display = 'block';
    document.getElementById('driverPassCol').style.display = 'block';
    
    driverModal = new bootstrap.Modal(document.getElementById('driverModal'));
    driverModal.show();
}

function openEditDriverModal(d) {
    document.getElementById('driverModalTitle').textContent = 'Edit Data Driver #' + d.id;
    document.getElementById('driverId').value = d.id;
    document.getElementById('driverName').value = d.name;
    document.getElementById('driverPhone').value = d.phone;
    document.getElementById('driverVehicleType').value = d.vehicle_type;
    document.getElementById('driverVehicleNum').value = d.vehicle_number;
    document.getElementById('driverZoneId').value = d.zone_id || 1;
    document.getElementById('driverEmailCol').style.display = 'none';
    document.getElementById('driverPassCol').style.display = 'none';

    driverModal = new bootstrap.Modal(document.getElementById('driverModal'));
    driverModal.show();
}

function openTopupDriverModal(userId, name) {
    document.getElementById('topupUserId').value = userId;
    document.getElementById('topupDriverName').textContent = name;
    new bootstrap.Modal(document.getElementById('topupDriverModal')).show();
}

async function viewDriverWalletHistory(userId, name) {
    const modal = new bootstrap.Modal(document.getElementById('driverWalletModal'));
    document.getElementById('driverWalletModalTitle').textContent = `Mutasi Dompet Driver: ${name}`;
    document.getElementById('driverWalletModalBody').innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>';
    modal.show();

    try {
        const res = await fetch(`${window.BASE_URL}/admin/delivery-men/wallet-history/${userId}`);
        const json = await res.json();
        if (json.success) {
            let txHtml = '';
            if (json.transactions && json.transactions.length > 0) {
                json.transactions.forEach(t => {
                    const isCredit = t.transaction_type === 'credit';
                    txHtml += `
                        <tr>
                            <td class="small text-muted">${new Date(t.created_at).toLocaleString('id-ID')}</td>
                            <td><span class="badge ${isCredit ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'}">${t.transaction_type.toUpperCase()}</span></td>
                            <td class="small fw-semibold">${t.reference_type || 'Transaksi'}</td>
                            <td class="small text-muted">${t.notes || '-'}</td>
                            <td class="text-end fw-bold ${isCredit ? 'text-success' : 'text-danger'}">
                                ${isCredit ? '+' : '-'} Rp ${Number(t.amount).toLocaleString('id-ID')}
                            </td>
                        </tr>
                    `;
                });
            } else {
                txHtml = '<tr><td colspan="5" class="text-center text-muted py-4">Belum ada riwayat transaksi dompet.</td></tr>';
            }

            document.getElementById('driverWalletModalBody').innerHTML = `
                <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded-3 mb-3">
                    <span class="fw-bold">Saldo Dompet Saat Ini:</span>
                    <span class="fs-5 fw-bold text-success">Rp ${Number(json.balance).toLocaleString('id-ID')}</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr class="small text-muted">
                                <th>WAKTU</th>
                                <th>TIPE</th>
                                <th>REFERENSI</th>
                                <th>KETERANGAN</th>
                                <th class="text-end">NOMINAL</th>
                            </tr>
                        </thead>
                        <tbody>${txHtml}</tbody>
                    </table>
                </div>
            `;
        }
    } catch (e) {
        document.getElementById('driverWalletModalBody').innerHTML = '<div class="alert alert-danger">Gagal memuat mutasi saldo.</div>';
    }
}

async function handleTopupDriver(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const res = await fetch(`${window.BASE_URL}/admin/delivery-men/topup`, { method: 'POST', body: formData });
    const json = await res.json();
    if (json.success) {
        Swal.fire({ icon: 'success', title: 'Berhasil', text: json.message }).then(() => location.reload());
    } else {
        Swal.fire({ icon: 'error', title: 'Gagal', text: json.message });
    }
}

async function toggleDriverStatus(id, newStatus) {
    const formData = new FormData();
    formData.append('id', id);
    formData.append('status', newStatus);

    const res = await fetch(`${window.BASE_URL}/admin/delivery-men/toggle-status`, { method: 'POST', body: formData });
    const json = await res.json();
    if (json.success) {
        Swal.fire({ icon: 'success', title: 'Berhasil', text: json.message }).then(() => location.reload());
    } else {
        Swal.fire({ icon: 'error', title: 'Gagal', text: json.message });
    }
}

function deleteDriver(id, name) {
    Swal.fire({
        title: 'Hapus Driver?',
        text: `Apakah Anda yakin ingin menghapus data armada driver "${name}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then(async (result) => {
        if (result.isConfirmed) {
            const fd = new FormData();
            fd.append('id', id);
            const res = await fetch(`${window.BASE_URL}/admin/delivery-men/delete`, { method: 'POST', body: fd });
            const json = await res.json();
            if (json.success) {
                Swal.fire('Terhapus!', json.message, 'success').then(() => location.reload());
            } else {
                Swal.fire('Gagal!', json.message, 'error');
            }
        }
    });
}
</script>

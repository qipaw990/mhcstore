<?php
    $countTotalCust = count($customers);
    $countActiveCust = count(array_filter($customers, fn($c) => !empty($c['is_active'])));
    $totalCustWallet = array_sum(array_column($customers, 'wallet_balance'));
    $totalCustOrders = array_sum(array_column($customers, 'order_count'));
?>

<!-- Customers KPI Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <small class="text-muted fw-semibold">Total Pelanggan Terdaftar</small>
                    <h4 class="fw-black text-dark mb-0 mt-1"><?= $countTotalCust ?></h4>
                </div>
                <div style="width:42px;height:42px;border-radius:12px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;color:#475569;font-size:20px;">
                    <i class="bi bi-people-fill"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <small class="text-success fw-semibold">Pelanggan Aktif</small>
                    <h4 class="fw-black text-success mb-0 mt-1"><?= $countActiveCust ?></h4>
                </div>
                <div style="width:42px;height:42px;border-radius:12px;background:#f0fdf4;display:flex;align-items:center;justify-content:center;color:#16a34a;font-size:20px;">
                    <i class="bi bi-person-check-fill"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <small class="text-primary fw-semibold">Total Saldo CicalengkaPay</small>
                    <h5 class="fw-black text-primary mb-0 mt-1"><?= format_rupiah($totalCustWallet) ?></h5>
                </div>
                <div style="width:42px;height:42px;border-radius:12px;background:#eff6ff;display:flex;align-items:center;justify-content:center;color:#2563eb;font-size:20px;">
                    <i class="bi bi-wallet-fill"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <small class="text-warning fw-semibold">Total Pesanan Dibuat</small>
                    <h4 class="fw-black text-warning mb-0 mt-1"><?= $totalCustOrders ?></h4>
                </div>
                <div style="width:42px;height:42px;border-radius:12px;background:#fffbeb;display:flex;align-items:center;justify-content:center;color:#d97706;font-size:20px;">
                    <i class="bi bi-bag-check-fill"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Header -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3 flex-grow-1" style="max-width: 400px;">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0 rounded-start-pill"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="customerSearchInput" onkeyup="filterCustomerTable()" class="form-control bg-light border-start-0 rounded-end-pill" placeholder="Cari Nama, Email, No HP...">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Customers Table List -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="customersTable">
                    <thead>
                        <tr>
                            <th>Pelanggan</th>
                            <th>Kontak & WhatsApp</th>
                            <th>Email Akun</th>
                            <th>Saldo CicalengkaPay</th>
                            <th>Total Belanja</th>
                            <th>Status Akun</th>
                            <th>Tgl Terdaftar</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($customers)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">Belum ada pelanggan terdaftar.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($customers as $c): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <div style="width: 44px; height: 44px; border-radius: 50%; background: #eff6ff; display: flex; align-items: center; justify-content: center; font-size: 20px; color: #2563eb;">
                                                <i class="bi bi-person-fill"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold small text-dark"><?= htmlspecialchars($c['name']) ?></div>
                                                <small class="text-muted">ID: #USR-<?= $c['id'] ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="small fw-semibold"><?= htmlspecialchars($c['phone'] ?: '-') ?></div>
                                    </td>
                                    <td>
                                        <div class="small text-muted"><?= htmlspecialchars($c['email']) ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-success small"><?= format_rupiah($c['wallet_balance'] ?? 0) ?></div>
                                    </td>
                                    <td>
                                        <a href="javascript:void(0)" onclick='viewCustomerHistory(<?= $c['id'] ?>, "<?= addslashes($c['name']) ?>")' class="badge bg-primary-subtle text-primary border text-decoration-none px-2 py-1">
                                            <?= $c['order_count'] ?? 0 ?> Pesanan <i class="bi bi-arrow-right-short"></i>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge <?= !empty($c['is_active']) ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?>">
                                            <?= !empty($c['is_active']) ? 'AKTIF' : 'DIBLOKIR' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?= date('d M Y', strtotime($c['created_at'])) ?></small>
                                    </td>
                                    <td class="text-end">
                                        <div class="dropdown">
                                            <button class="btn btn-light btn-sm rounded-pill px-3 dropdown-toggle fw-semibold" type="button" data-bs-toggle="dropdown" data-bs-boundary="viewport">
                                                Aksi
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3">
                                                <li>
                                                    <a class="dropdown-item py-2 small" href="javascript:void(0)" onclick='openTopupCustomerModal(<?= $c['id'] ?>, "<?= addslashes($c['name']) ?>")'>
                                                        <i class="bi bi-wallet2 text-success me-2"></i> Isi Saldo CicalengkaPay
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item py-2 small" href="javascript:void(0)" onclick='viewCustomerHistory(<?= $c['id'] ?>, "<?= addslashes($c['name']) ?>")'>
                                                        <i class="bi bi-bag-check text-primary me-2"></i> Riwayat Belanja Pelanggan
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item py-2 small" href="javascript:void(0)" onclick="toggleCustomerStatus(<?= $c['id'] ?>, <?= !empty($c['is_active']) ? 0 : 1 ?>)">
                                                        <i class="bi bi-power <?= !empty($c['is_active']) ? 'text-warning' : 'text-success' ?> me-2"></i>
                                                        <?= !empty($c['is_active']) ? 'Blokir Akun Pelanggan' : 'Aktifkan Akun Pelanggan' ?>
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

<!-- Modal: Top-up Customer Wallet -->
<div class="modal fade" id="topupCustomerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom py-3">
                <h6 class="modal-title fw-bold">Top-up Saldo CicalengkaPay Pelanggan</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="topupCustomerForm" onsubmit="handleTopupCustomer(event)">
                <input type="hidden" name="user_id" id="topupCustUserId">
                <div class="modal-body p-4">
                    <p class="small text-muted mb-3">Tambahkan saldo dompet CicalengkaPay untuk pelanggan <b id="topupCustName"></b>.</p>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nominal Top-up (Rp)</label>
                        <input type="number" name="amount" class="form-control rounded-3" placeholder="Contoh: 50000" min="5000" step="5000" required>
                    </div>
                </div>
                <div class="modal-footer border-top py-3">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">Kreditkan Saldo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Customer Order History -->
<div class="modal fade" id="customerHistoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom py-3">
                <h6 class="modal-title fw-bold" id="customerHistoryTitle">Riwayat Belanja Pelanggan</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="customerHistoryBody">
                <div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>
            </div>
        </div>
    </div>
</div>

<script>
function filterCustomerTable() {
    const input = document.getElementById('customerSearchInput').value.toLowerCase();
    const rows = document.querySelectorAll('#customersTable tbody tr');
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(input) ? '' : 'none';
    });
}

function openTopupCustomerModal(userId, name) {
    document.getElementById('topupCustUserId').value = userId;
    document.getElementById('topupCustName').textContent = name;
    new bootstrap.Modal(document.getElementById('topupCustomerModal')).show();
}

async function viewCustomerHistory(userId, name) {
    const modal = new bootstrap.Modal(document.getElementById('customerHistoryModal'));
    document.getElementById('customerHistoryTitle').textContent = `Riwayat Transaksi: ${name}`;
    document.getElementById('customerHistoryBody').innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>';
    modal.show();

    try {
        const res = await fetch(`${window.BASE_URL}/admin/customers/history/${userId}`);
        const json = await res.json();
        if (json.success) {
            let html = '';
            if (json.orders && json.orders.length > 0) {
                json.orders.forEach(o => {
                    html += `
                        <tr>
                            <td class="fw-bold small text-primary">#${o.order_code}</td>
                            <td class="small text-muted">${new Date(o.created_at).toLocaleString('id-ID')}</td>
                            <td class="small fw-semibold">${o.store_name || 'Cicago Parcel'}</td>
                            <td class="fw-bold small">Rp ${Number(o.total_amount).toLocaleString('id-ID')}</td>
                            <td><span class="badge bg-primary text-uppercase">${o.order_status}</span></td>
                            <td class="text-end">
                                <a href="${window.BASE_URL}/admin/orders/invoice/${o.id}" target="_blank" class="btn btn-light btn-sm rounded-pill px-3">
                                    <i class="bi bi-printer me-1"></i> Struk
                                </a>
                            </td>
                        </tr>
                    `;
                });
            } else {
                html = '<tr><td colspan="6" class="text-center text-muted py-4">Belum ada riwayat pesanan untuk pelanggan ini.</td></tr>';
            }

            document.getElementById('customerHistoryBody').innerHTML = `
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr class="small text-muted">
                                <th>KODE ORDER</th>
                                <th>WAKTU</th>
                                <th>TOKO / LAYANAN</th>
                                <th>TOTAL BAYAR</th>
                                <th>STATUS</th>
                                <th class="text-end">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>${html}</tbody>
                    </table>
                </div>
            `;
        }
    } catch (e) {
        document.getElementById('customerHistoryBody').innerHTML = '<div class="alert alert-danger">Gagal memuat riwayat belanja.</div>';
    }
}

async function handleTopupCustomer(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const res = await fetch(`${window.BASE_URL}/admin/customers/topup`, { method: 'POST', body: formData });
    const json = await res.json();
    if (json.success) {
        Swal.fire({ icon: 'success', title: 'Berhasil', text: json.message }).then(() => location.reload());
    } else {
        Swal.fire({ icon: 'error', title: 'Gagal', text: json.message });
    }
}

async function toggleCustomerStatus(id, newStatus) {
    const formData = new FormData();
    formData.append('id', id);
    formData.append('status', newStatus);

    const res = await fetch(`${window.BASE_URL}/admin/customers/toggle-status`, { method: 'POST', body: formData });
    const json = await res.json();
    if (json.success) {
        Swal.fire({ icon: 'success', title: 'Berhasil', text: json.message }).then(() => location.reload());
    } else {
        Swal.fire({ icon: 'error', title: 'Gagal', text: json.message });
    }
}
</script>

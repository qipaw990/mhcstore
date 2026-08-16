<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white border-0 py-3">
        <h6 class="fw-bold m-0"><i class="bi bi-receipt me-1 text-primary"></i> Seluruh Pesanan Masuk (<?= count($orders) ?>)</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>No. Order</th>
                    <th>Waktu</th>
                    <th>Pelanggan</th>
                    <th>Detail Menu</th>
                    <th>Total</th>
                    <th>Kurir</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">Belum ada pesanan.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $ord): ?>
                        <tr>
                            <td class="fw-bold text-primary">#<?= htmlspecialchars($ord['order_code']) ?></td>
                            <td class="small text-muted"><?= date('d M Y, H:i', strtotime($ord['created_at'])) ?></td>
                            <td>
                                <div class="fw-bold small"><?= htmlspecialchars($ord['customer_name']) ?></div>
                                <div class="text-muted small"><?= htmlspecialchars($ord['customer_phone']) ?></div>
                            </td>
                            <td>
                                <div class="small">
                                    <?php foreach ($ord['items'] as $it): ?>
                                        <div><?= $it['quantity'] ?>x <?= htmlspecialchars($it['product_name']) ?></div>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td class="fw-bold"><?= format_rupiah($ord['total_amount']) ?></td>
                            <td>
                                <?php if (!empty($ord['dm_name'])): ?>
                                    <div class="small fw-semibold text-primary"><i class="bi bi-bicycle me-1"></i><?= htmlspecialchars($ord['dm_name']) ?></div>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border">Mencari Kurir</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-primary text-uppercase"><?= $ord['order_status'] ?></span>
                            </td>
                            <td>
                                <?php if ($ord['order_status'] === 'confirmed'): ?>
                                    <button onclick="updateStoreOrderStatus(<?= $ord['id'] ?>, 'processing')" class="btn btn-sm btn-warning fw-bold">
                                        Proses & Masak
                                    </button>
                                <?php elseif ($ord['order_status'] === 'processing'): ?>
                                    <button onclick="updateStoreOrderStatus(<?= $ord['id'] ?>, 'handover')" class="btn btn-sm btn-info text-white fw-bold">
                                        Siap Diambil Kurir
                                    </button>
                                <?php else: ?>
                                    <span class="small text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
async function updateStoreOrderStatus(orderId, status) {
    const fd = new FormData();
    fd.append('order_id', orderId);
    fd.append('status', status);

    try {
        const res = await fetch(window.BASE_URL + '/vendor/orders/update-status', {
            method: 'POST',
            body: fd
        });
        const data = await res.json();
        if (data.success) {
            Swal.fire('Berhasil', data.message, 'success').then(() => location.reload());
        }
    } catch (e) {
        console.error(e);
    }
}
</script>

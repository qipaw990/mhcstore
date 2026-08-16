<!-- Orders Header & Filter Tabs -->
<div class="mb-3">
    <div class="d-flex align-items-center justify-content-between mb-2">
        <h6 class="fw-bold m-0 text-dark" style="font-size: 15px;">
            <i class="bi bi-receipt-cutoff text-danger me-1"></i> Pesanan Masuk (<?= count($orders) ?>)
        </h6>
        <button onclick="location.reload()" class="btn btn-light btn-sm rounded-circle p-0 d-flex align-items-center justify-content-center shadow-xs" style="width: 32px; height: 32px;" title="Segarkan">
            <i class="bi bi-arrow-clockwise text-dark"></i>
        </button>
    </div>

    <!-- Horizontal Scroll Status Filter Chips -->
    <div class="merchant-tab-scroll">
        <button class="merchant-tab-chip active" onclick="filterOrderList('all', this)">
            Semua (<?= count($orders) ?>)
        </button>
        <button class="merchant-tab-chip" onclick="filterOrderList('new', this)">
            Perlu Diproses
            <?php 
            $newCount = count(array_filter($orders, fn($o) => in_array($o['order_status'], ['pending', 'confirmed'])));
            if ($newCount > 0): ?>
                <span class="badge-count"><?= $newCount ?></span>
            <?php endif; ?>
        </button>
        <button class="merchant-tab-chip" onclick="filterOrderList('processing', this)">
            Sedang Dimasak
            <?php 
            $cookCount = count(array_filter($orders, fn($o) => $o['order_status'] === 'processing'));
            if ($cookCount > 0): ?>
                <span class="badge-count" style="background:#0284C7;"><?= $cookCount ?></span>
            <?php endif; ?>
        </button>
        <button class="merchant-tab-chip" onclick="filterOrderList('handover', this)">
            Siap Diambil
            <?php 
            $readyCount = count(array_filter($orders, fn($o) => $o['order_status'] === 'handover'));
            if ($readyCount > 0): ?>
                <span class="badge-count" style="background:#8B5CF6;"><?= $readyCount ?></span>
            <?php endif; ?>
        </button>
        <button class="merchant-tab-chip" onclick="filterOrderList('delivered', this)">
            Selesai
        </button>
    </div>
</div>

<!-- Order List Container -->
<?php if (empty($orders)): ?>
    <div class="text-center py-5 bg-white rounded-4 border p-4 shadow-xs">
        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto mb-2" style="width: 54px; height: 54px;">
            <i class="bi bi-inbox text-muted fs-3"></i>
        </div>
        <h6 class="fw-bold text-dark">Belum Ada Pesanan</h6>
        <p class="small text-muted mb-0">Pesanan baru dari pembeli akan muncul di sini saat toko Anda buka.</p>
    </div>
<?php else: ?>
    <div class="d-flex flex-column gap-3" id="orderItemsContainer">
        <?php foreach ($orders as $ord): 
            $statusGroup = match($ord['order_status']) {
                'pending', 'confirmed' => 'new',
                'processing' => 'processing',
                'handover' => 'handover',
                'picked_up', 'delivered' => 'delivered',
                default => 'other'
            };

            $statusBadge = match($ord['order_status']) {
                'pending' => 'bg-warning text-dark',
                'confirmed' => 'bg-primary text-white',
                'processing' => 'bg-info text-white',
                'handover' => 'bg-purple text-white',
                'picked_up' => 'bg-indigo text-white',
                'delivered' => 'bg-success text-white',
                'canceled' => 'bg-danger text-white',
                default => 'bg-secondary text-white'
            };
        ?>
            <div class="merchant-order-card order-item-node" data-status-group="<?= $statusGroup ?>">
                <!-- Header -->
                <div class="merchant-order-header">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-danger-subtle text-danger fw-extrabold" style="font-size: 11.5px;">#<?= htmlspecialchars($ord['order_code']) ?></span>
                        <span class="text-muted" style="font-size: 11px;"><i class="bi bi-clock me-1"></i><?= date('d M, H:i', strtotime($ord['created_at'])) ?></span>
                    </div>
                    <span class="badge <?= $statusBadge ?> text-uppercase rounded-pill" style="font-size: 10px; font-weight: 700;">
                        <?= $ord['order_status'] ?>
                    </span>
                </div>

                <!-- Customer Details -->
                <div class="d-flex align-items-center justify-content-between mb-2.5">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                            <i class="bi bi-person-fill text-dark" style="font-size: 16px;"></i>
                        </div>
                        <div>
                            <div class="fw-bold small text-dark"><?= htmlspecialchars($ord['customer_name']) ?></div>
                            <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $ord['customer_phone']) ?>" target="_blank" class="text-success text-decoration-none fw-semibold" style="font-size: 11px;">
                                <i class="bi bi-whatsapp me-0.5"></i> <?= htmlspecialchars($ord['customer_phone']) ?>
                            </a>
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="fw-extrabold text-danger" style="font-size: 14px;"><?= format_rupiah($ord['total_amount']) ?></div>
                        <span class="badge bg-light text-muted border" style="font-size: 9.5px;"><?= strtoupper($ord['payment_method']) ?></span>
                    </div>
                </div>

                <!-- Courier Assignment Info -->
                <div class="p-2 bg-light rounded-3 d-flex align-items-center justify-content-between mb-2 border" style="font-size: 11.5px;">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-bicycle text-danger fs-6"></i>
                        <span>
                            Kurir: <strong><?= !empty($ord['dm_name']) ? htmlspecialchars($ord['dm_name']) : 'Mencari Kurir...' ?></strong>
                        </span>
                    </div>
                    <?php if (!empty($ord['dm_phone'])): ?>
                        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $ord['dm_phone']) ?>" target="_blank" class="btn btn-outline-success btn-sm rounded-pill px-2 py-0.5" style="font-size: 10px;">
                            <i class="bi bi-whatsapp me-0.5"></i> Hubungi Kurir
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Items Breakdown -->
                <div class="merchant-order-items">
                    <?php foreach ($ord['items'] as $it): ?>
                        <div class="merchant-order-item-row">
                            <span class="fw-bold text-dark"><?= $it['quantity'] ?>x <?= htmlspecialchars($it['product_name']) ?></span>
                            <span class="text-muted small"><?= format_rupiah($it['unit_price'] * $it['quantity']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Order Notes -->
                <?php if (!empty($ord['order_notes'])): ?>
                    <div class="merchant-notes-box">
                        <i class="bi bi-chat-left-dots-fill me-1"></i> <strong>Catatan:</strong> <?= htmlspecialchars($ord['order_notes']) ?>
                    </div>
                <?php endif; ?>

                <!-- Action Button -->
                <?php if ($ord['order_status'] === 'confirmed' || $ord['order_status'] === 'pending'): ?>
                    <button onclick="updateStoreOrderStatus(<?= $ord['id'] ?>, 'processing')" class="merchant-btn-action bg-warning text-dark">
                        <i class="bi bi-fire"></i> Terima & Masak Pesanan
                    </button>
                <?php elseif ($ord['order_status'] === 'processing'): ?>
                    <button onclick="updateStoreOrderStatus(<?= $ord['id'] ?>, 'handover')" class="merchant-btn-action bg-info text-white">
                        <i class="bi bi-box-seam-fill"></i> Pesanan Siap Diambil Kurir
                    </button>
                <?php elseif ($ord['order_status'] === 'handover'): ?>
                    <div class="p-2 bg-light text-center rounded-3 text-muted small border">
                        <i class="bi bi-bicycle text-primary me-1"></i> Menunggu kurir mengambil pesanan di toko
                    </div>
                <?php elseif ($ord['order_status'] === 'picked_up'): ?>
                    <div class="p-2 bg-light text-center rounded-3 text-success small border">
                        <i class="bi bi-check2-circle me-1"></i> Pesanan sedang diantar kurir ke pelanggan
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
function filterOrderList(group, btn) {
    document.querySelectorAll('.merchant-tab-chip').forEach(c => c.classList.remove('active'));
    btn.classList.add('active');

    const nodes = document.querySelectorAll('.order-item-node');
    nodes.forEach(node => {
        if (group === 'all' || node.dataset.statusGroup === group) {
            node.classList.remove('d-none');
        } else {
            node.classList.add('d-none');
        }
    });
}

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
            Swal.fire({
                title: 'Berhasil!',
                text: data.message,
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            }).then(() => location.reload());
        }
    } catch (e) {
        console.error(e);
    }
}
</script>

<div class="p-3 border-bottom bg-white d-flex align-items-center justify-content-between">
    <h6 class="fw-bold m-0" style="color: var(--gojek-charcoal);">Pesanan Saya</h6>
    <span id="order-count-badge" class="badge text-white px-2 py-1 rounded-pill" style="background:#EE2737; font-size: 10px;">
        <?= count($orders) ?> Pesanan
    </span>
</div>

<div class="p-3" id="orders-main-container">
    <?php if (empty($orders)): ?>
        <div class="text-center py-5" id="empty-orders-view">
            <div class="rounded-circle bg-light text-muted d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 70px; height: 70px; font-size: 28px;">
                <i class="bi bi-receipt text-muted"></i>
            </div>
            <h6 class="fw-bold" style="color: var(--gojek-charcoal);">Belum Ada Riwayat Pesanan</h6>
            <p class="text-muted small">Pesanan Kuliner & Kirim Paket Anda akan muncul di halaman ini.</p>
            <a href="<?= $baseUrl ?>" class="btn btn-gojek-green px-4 mt-2" style="background:#EE2737 !important; color:#FFFFFF !important; border-radius:9999px; width: auto; display: inline-flex; text-decoration:none;">Pesan Sekarang</a>
        </div>
    <?php else: ?>
        <div class="d-flex flex-column gap-3" id="orders-list-wrapper">
            <?php foreach ($orders as $order): ?>
                <?php
                $isCanceled = ($order['order_status'] === 'canceled');
                $isUnpaid = ($order['payment_method'] === 'midtrans' && $order['payment_status'] !== 'paid' && !$isCanceled);
                
                $status = $order['order_status'];
                $badgeClass = 'bg-secondary text-white';
                $statusLabel = $status;

                if ($isCanceled) {
                    $badgeClass = 'bg-danger-subtle text-danger border border-danger-subtle';
                    $statusLabel = 'Dibatalkan';
                } elseif ($isUnpaid) {
                    $badgeClass = 'bg-warning-subtle text-warning-emphasis border border-warning';
                    $statusLabel = 'Menunggu Pembayaran';
                } elseif ($status === 'confirmed') {
                    $badgeClass = 'bg-info-subtle text-info border border-info-subtle';
                    $statusLabel = 'Dikonfirmasi';
                } elseif ($status === 'processing') {
                    $badgeClass = 'bg-warning-subtle text-warning border border-warning-subtle';
                    $statusLabel = 'Diproses Resto';
                } elseif (in_array($status, ['handover', 'picked_up', 'on_the_way'])) {
                    $badgeClass = 'bg-primary-subtle text-primary border border-primary-subtle';
                    $statusLabel = 'Sedang Diantar';
                } elseif ($status === 'delivered') {
                    $badgeClass = 'bg-success-subtle text-success border border-success-subtle';
                    $statusLabel = 'Selesai';
                }
                ?>
                <div class="p-3 bg-white rounded-4 border shadow-sm order-card-item" id="order-card-<?= htmlspecialchars($order['order_code']) ?>">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-circle text-white d-flex align-items-center justify-content-center" style="width: 26px; height: 26px; background: #EE2737;">
                                <i class="bi <?= empty($order['items']) ? 'bi-box-seam-fill' : 'bi-egg-fried' ?>" style="font-size: 12px;"></i>
                            </div>
                            <span class="fw-bold small" style="color: var(--gojek-charcoal);"><?= empty($order['items']) ? 'GoSend' : 'GoFood' ?></span>
                            <span class="text-muted" style="font-size: 11px;">#<?= htmlspecialchars($order['order_code']) ?></span>
                        </div>
                        <span class="badge <?= $badgeClass ?> fw-bold status-badge" style="font-size: 10px;"><?= $statusLabel ?></span>
                    </div>

                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-shop text-muted" style="font-size: 13px;"></i>
                        <span class="small fw-semibold text-dark"><?= htmlspecialchars($order['store_name'] ?? 'Penjemputan GoSend') ?></span>
                        <span class="text-muted small">• <?= date('d M, H:i', strtotime($order['created_at'])) ?></span>
                    </div>

                    <div class="p-2 bg-light rounded-3 small text-muted mb-3" style="font-size: 11px;">
                        <?php if (!empty($order['items'])): ?>
                            <?php foreach ($order['items'] as $item): ?>
                                <div class="text-truncate">• <?= $item['quantity'] ?>x <?= htmlspecialchars($item['product_name']) ?></div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div>• Pengiriman Paket Kilat GoSend Cicalengka</div>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted" style="font-size: 10px;">
                                <?= $isUnpaid ? 'Total Tagihan' : 'Total Pembayaran' ?>
                                <span class="fw-semibold">(<?= strtoupper($order['payment_method']) ?>)</span>
                            </div>
                            <div class="fw-bold <?= $isUnpaid ? 'text-danger' : 'text-dark' ?> fs-6"><?= format_rupiah($order['total_amount']) ?></div>
                        </div>

                        <?php if ($isUnpaid): ?>
                            <a href="<?= $baseUrl ?>/orders/<?= $order['order_code'] ?>/tracking" class="btn btn-sm rounded-pill fw-bold px-3 text-white shadow-xs" style="background:#EE2737; font-size: 11px;">
                                <i class="bi bi-credit-card-2-front-fill me-1"></i> Bayar Sekarang
                            </a>
                        <?php elseif ($isCanceled): ?>
                            <a href="<?= $baseUrl ?>" class="btn btn-sm rounded-pill fw-semibold px-3 btn-light border" style="font-size: 11px;">
                                <i class="bi bi-arrow-clockwise me-1"></i> Pesan Lagi
                            </a>
                        <?php else: ?>
                            <a href="<?= $baseUrl ?>/orders/<?= $order['order_code'] ?>/tracking" class="btn btn-sm rounded-pill fw-bold px-3 text-white shadow-xs" style="background:#EE2737; font-size: 11px;">
                                <i class="bi bi-geo-alt-fill me-1"></i> Lacak Live
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
// Customer Order List Live Auto-Sync
(function() {
    let lastOrderStateHash = '';

    function getStatusMeta(status, paymentMethod, paymentStatus) {
        const isCanceled = (status === 'canceled');
        const isUnpaid = (paymentMethod === 'midtrans' && paymentStatus !== 'paid' && !isCanceled);

        if (isCanceled) return { label: 'Dibatalkan', class: 'bg-danger-subtle text-danger border border-danger-subtle' };
        if (isUnpaid) return { label: 'Menunggu Pembayaran', class: 'bg-warning-subtle text-warning-emphasis border border-warning' };
        if (status === 'confirmed') return { label: 'Dikonfirmasi', class: 'bg-info-subtle text-info border border-info-subtle' };
        if (status === 'processing') return { label: 'Diproses Resto', class: 'bg-warning-subtle text-warning border border-warning-subtle' };
        if (['handover', 'picked_up', 'on_the_way'].includes(status)) return { label: 'Sedang Diantar', class: 'bg-primary-subtle text-primary border border-primary-subtle' };
        if (status === 'delivered') return { label: 'Selesai', class: 'bg-success-subtle text-success border border-success-subtle' };
        return { label: status, class: 'bg-secondary text-white' };
    }

    async function syncOrdersList() {
        try {
            const res = await fetch(window.BASE_URL + '/orders/live-list');
            if (!res.ok) return;
            const json = await res.json();
            if (!json.success || !json.data) return;

            const orders = json.data.orders || [];
            const badgeCount = document.getElementById('order-count-badge');
            if (badgeCount) badgeCount.textContent = `${orders.length} Pesanan`;

            orders.forEach(ord => {
                const card = document.getElementById(`order-card-${ord.order_code}`);
                if (card) {
                    const badge = card.querySelector('.status-badge');
                    if (badge) {
                        const meta = getStatusMeta(ord.order_status, ord.payment_method, ord.payment_status);
                        badge.className = `badge ${meta.class} fw-bold status-badge`;
                        badge.textContent = meta.label;
                    }
                }
            });
        } catch (e) {
            console.warn('Customer orders sync error:', e);
        }
    }

    setInterval(syncOrdersList, 3500);
})();
</script>

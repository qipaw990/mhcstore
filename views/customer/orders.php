<div class="p-3 border-bottom bg-white d-flex align-items-center justify-content-between">
    <h6 class="fw-bold m-0" style="color: var(--gojek-charcoal);">Pesanan Saya</h6>
    <span class="badge text-white px-2 py-1 rounded-pill" style="background:#EE2737; font-size: 10px;">
        <?= count($orders) ?> Pesanan
    </span>
</div>

<div class="p-3">
    <?php if (empty($orders)): ?>
        <div class="text-center py-5">
            <div class="rounded-circle bg-light text-muted d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 70px; height: 70px; font-size: 28px;">
                <i class="bi bi-receipt text-muted"></i>
            </div>
            <h6 class="fw-bold" style="color: var(--gojek-charcoal);">Belum Ada Riwayat Pesanan</h6>
            <p class="text-muted small">Pesanan Kuliner & Kirim Paket Anda akan muncul di halaman ini.</p>
            <a href="<?= $baseUrl ?>" class="btn btn-gojek-green px-4 mt-2" style="background:#EE2737 !important; color:#FFFFFF !important; border-radius:9999px; width: auto; display: inline-flex; text-decoration:none;">Pesan Sekarang</a>
        </div>
    <?php else: ?>
        <div class="d-flex flex-column gap-3">
            <?php foreach ($orders as $order): ?>
                <div class="p-3 bg-white rounded-4 border shadow-sm">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-circle text-white d-flex align-items-center justify-content-center" style="width: 26px; height: 26px; background: #EE2737;">
                                <i class="bi <?= empty($order['items']) ? 'bi-box-seam-fill' : 'bi-egg-fried' ?>" style="font-size: 12px;"></i>
                            </div>
                            <span class="fw-bold small" style="color: var(--gojek-charcoal);"><?= empty($order['items']) ? 'GoSend' : 'GoFood' ?></span>
                            <span class="text-muted" style="font-size: 11px;">#<?= htmlspecialchars($order['order_code']) ?></span>
                        </div>
                        
                        <?php
                        $status = $order['order_status'];
                        $badgeClass = 'bg-secondary';
                        $statusLabel = $status;
                        if ($status === 'confirmed') { $badgeClass = 'bg-info-subtle text-info'; $statusLabel = 'Dikonfirmasi'; }
                        elseif ($status === 'processing') { $badgeClass = 'bg-warning-subtle text-warning'; $statusLabel = 'Diproses Resto'; }
                        elseif ($status === 'on_the_way') { $badgeClass = 'bg-primary-subtle text-primary'; $statusLabel = 'Sedang Diantar'; }
                        elseif ($status === 'delivered') { $badgeClass = 'bg-success-subtle text-success'; $statusLabel = 'Selesai'; }
                        elseif ($status === 'canceled') { $badgeClass = 'bg-danger-subtle text-danger'; $statusLabel = 'Dibatalkan'; }
                        ?>
                        <span class="badge <?= $badgeClass ?> fw-bold" style="font-size: 10px;"><?= $statusLabel ?></span>
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
                            <div class="text-muted" style="font-size: 10px;">Total Pembayaran</div>
                            <div class="fw-bold text-dark fs-6"><?= format_rupiah($order['total_amount']) ?></div>
                        </div>
                        <a href="<?= $baseUrl ?>/orders/<?= $order['order_code'] ?>/tracking" class="btn btn-sm rounded-pill fw-bold px-3 text-white shadow-xs" style="background:#EE2737; font-size: 11px;">
                            <i class="bi bi-geo-alt-fill me-1"></i> Lacak Live
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

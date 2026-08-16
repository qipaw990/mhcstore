<div class="border-bottom bg-white d-flex align-items-center justify-content-between sticky-top shadow-xs px-3 py-2.5">
    <h6 class="fw-bold m-0 text-dark" style="font-size: 13.5px; letter-spacing: -0.2px;">Pesanan Saya</h6>
    <span id="order-count-badge" class="badge text-white px-3 py-1.5 rounded-pill shadow-2xs" style="background: linear-gradient(135deg, #EE2737, #C61524); font-size: 10px;">
        <?= count($orders) ?> Pesanan
    </span>
</div>

<div class="px-3 py-3" id="orders-main-container">
    <?php if (empty($orders)): ?>
        <div class="text-center py-5" id="empty-orders-view">
            <div class="rounded-circle bg-light text-muted d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 58px; height: 58px; font-size: 24px;">
                <i class="bi bi-receipt text-muted"></i>
            </div>
            <h6 class="fw-bold mb-1.5" style="color: var(--gojek-charcoal); font-size: 13.5px;">Belum Ada Riwayat Pesanan</h6>
            <p class="text-muted mb-3.5" style="font-size: 11px; max-width: 280px; margin-left: auto; margin-right: auto; line-height: 1.5;">Pesanan Kuliner & Kirim Paket Anda akan muncul di halaman ini.</p>
            <a href="<?= $baseUrl ?>" class="btn btn-gojek-green px-4 py-2.5" style="background:#EE2737 !important; color:#FFFFFF !important; border-radius:9999px; width: auto; display: inline-flex; text-decoration:none; font-size: 12px; font-weight: 700;">Pesan Sekarang</a>
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
                <div class="p-3.5 bg-white border shadow-2xs order-card-item" id="order-card-<?= htmlspecialchars($order['order_code']) ?>" style="border-radius: 16px; border-color: #E2E8F0 !important;">
                    <div class="d-flex align-items-center justify-content-between mb-2.5">
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-circle text-white d-flex align-items-center justify-content-center" style="width: 26px; height: 26px; background: linear-gradient(135deg, #EE2737, #C61524); box-shadow: 0 2px 6px rgba(238,39,55,0.25);">
                                <i class="bi <?= empty($order['items']) ? 'bi-box-seam-fill' : 'bi-egg-fried' ?>" style="font-size: 12px;"></i>
                            </div>
                            <span class="fw-bold" style="color: var(--gojek-charcoal); font-size: 12.5px; letter-spacing: -0.2px;"><?= empty($order['items']) ? 'GoSend' : 'GoFood' ?></span>
                            <span class="text-muted" style="font-size: 10.5px;">#<?= htmlspecialchars($order['order_code']) ?></span>
                        </div>
                        <span class="badge <?= $badgeClass ?> fw-bold status-badge" style="font-size: 9.5px; padding: 4px 9px; border-radius: 8px;"><?= $statusLabel ?></span>
                    </div>

                    <div class="d-flex align-items-center gap-2 mb-2.5">
                        <i class="bi bi-shop text-muted" style="font-size: 13px;"></i>
                        <span class="fw-bold text-dark text-truncate" style="font-size: 12px; letter-spacing: -0.2px;"><?= htmlspecialchars($order['store_name'] ?? 'Penjemputan GoSend') ?></span>
                        <span class="text-muted" style="font-size: 10.5px;">• <?= date('d M, H:i', strtotime($order['created_at'])) ?></span>
                    </div>

                    <div class="p-3 bg-light rounded-3 text-muted mb-3" style="font-size: 11px; line-height: 1.5; border-radius: 10px !important;">
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
                            <div class="fw-extrabold <?= $isUnpaid ? 'text-danger' : 'text-dark' ?>" style="font-size: 14px;"><?= format_rupiah($order['total_amount']) ?></div>
                        </div>

                        <div class="d-flex gap-2 align-items-center">
                            <?php if ($isUnpaid): ?>
                                <a href="<?= $baseUrl ?>/orders/<?= $order['order_code'] ?>/tracking" class="btn btn-sm rounded-pill fw-bold px-3.5 py-1.5 text-white shadow-2xs" style="background: linear-gradient(135deg, #EE2737, #C61524); font-size: 11px;">
                                    <i class="bi bi-credit-card-2-front-fill me-1"></i> Bayar
                                </a>
                            <?php elseif ($isCanceled): ?>
                                <a href="<?= $baseUrl ?>" class="btn btn-sm rounded-pill fw-semibold px-3 py-1.5 btn-light border" style="font-size: 11px;">
                                    <i class="bi bi-arrow-clockwise me-1"></i> Pesan Lagi
                                </a>
                            <?php elseif ($status === 'delivered'): ?>
                                <a href="<?= $baseUrl ?>/orders/<?= $order['order_code'] ?>/tracking" class="btn btn-sm rounded-pill fw-semibold px-3 py-1.5 btn-light border" style="font-size: 11px;">
                                    <i class="bi bi-receipt me-1"></i> Rincian
                                </a>
                                <?php if (!empty($order['is_reviewed'])): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle py-1.5 px-3 rounded-pill" style="font-size: 10px; font-weight: 700;">
                                        <i class="bi bi-star-fill text-warning me-1"></i> Diulas
                                    </span>
                                <?php else: ?>
                                    <button type="button" onclick="openOrderReviewModal(<?= (int)$order['id'] ?>, '<?= htmlspecialchars($order['order_code']) ?>', '<?= htmlspecialchars(addslashes($order['store_name'] ?? 'Toko')) ?>', <?= !empty($order['delivery_man_id']) ? 'true' : 'false' ?>)" class="btn btn-sm rounded-pill fw-bold px-3 py-1.5 text-dark shadow-2xs" style="background: #FBBF24; font-size: 11px; border:none;">
                                        <i class="bi bi-star-fill me-1 text-dark"></i> Beri Ulasan
                                    </button>
                                <?php endif; ?>
                            <?php else: ?>
                                <a href="<?= $baseUrl ?>/orders/<?= $order['order_code'] ?>/tracking" class="btn btn-sm rounded-pill fw-bold px-3.5 py-1.5 text-white shadow-2xs" style="background: linear-gradient(135deg, #EE2737, #C61524); font-size: 11px;">
                                    <i class="bi bi-geo-alt-fill me-1"></i> Lacak Live
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
// Review Modal for Customer Orders
function openOrderReviewModal(orderId, orderCode, storeName, hasDriver) {
    let currentStoreRating = 5;
    let currentDriverRating = 5;

    const html = `
        <div class="text-start" style="font-size: 13px;">
            <div class="mb-3 text-center">
                <div class="text-muted small mb-1">Pesanan #${orderCode}</div>
                <div class="fw-bold text-dark fs-6">${storeName}</div>
            </div>

            <div class="mb-3 p-3 bg-light rounded-4 border">
                <label class="fw-bold text-dark d-block mb-1.5">Rating untuk Toko / Makanan</label>
                <div class="d-flex justify-content-center gap-2 mb-2" id="store-star-rating">
                    <i class="bi bi-star-fill text-warning fs-3 cursor-pointer" data-val="1" onclick="setStoreStar(1)"></i>
                    <i class="bi bi-star-fill text-warning fs-3 cursor-pointer" data-val="2" onclick="setStoreStar(2)"></i>
                    <i class="bi bi-star-fill text-warning fs-3 cursor-pointer" data-val="3" onclick="setStoreStar(3)"></i>
                    <i class="bi bi-star-fill text-warning fs-3 cursor-pointer" data-val="4" onclick="setStoreStar(4)"></i>
                    <i class="bi bi-star-fill text-warning fs-3 cursor-pointer" data-val="5" onclick="setStoreStar(5)"></i>
                </div>
                <div class="text-center small fw-bold text-warning-emphasis mb-2" id="store-star-text">Sangat Puas (5 Bintang)</div>
                <textarea id="store-review-comment" name="store_comment" class="form-control form-control-sm rounded-3" rows="2" placeholder="Bagaimana rasa makanan / kualitas pesanan toko ini?"></textarea>
            </div>

            ${hasDriver ? `
            <div class="mb-2 p-3 bg-light rounded-4 border">
                <label class="fw-bold text-dark d-block mb-1.5">Rating Pelayanan Kurir Driver</label>
                <div class="d-flex justify-content-center gap-2 mb-2" id="driver-star-rating">
                    <i class="bi bi-star-fill text-warning fs-3 cursor-pointer" data-val="1" onclick="setDriverStar(1)"></i>
                    <i class="bi bi-star-fill text-warning fs-3 cursor-pointer" data-val="2" onclick="setDriverStar(2)"></i>
                    <i class="bi bi-star-fill text-warning fs-3 cursor-pointer" data-val="3" onclick="setDriverStar(3)"></i>
                    <i class="bi bi-star-fill text-warning fs-3 cursor-pointer" data-val="4" onclick="setDriverStar(4)"></i>
                    <i class="bi bi-star-fill text-warning fs-3 cursor-pointer" data-val="5" onclick="setDriverStar(5)"></i>
                </div>
                <div class="text-center small fw-bold text-warning-emphasis mb-2" id="driver-star-text">Pengantaran Cepat & Ramah (5 Bintang)</div>
                <textarea id="driver-review-comment" name="driver_comment" class="form-control form-control-sm rounded-3" rows="2" placeholder="Tuliskan ulasan untuk kurir (opsional)..."></textarea>
            </div>
            ` : ''}
        </div>
    `;

    Swal.fire({
        title: 'Beri Rating & Ulasan ⭐',
        html: html,
        showCancelButton: true,
        confirmButtonText: 'Kirim Ulasan',
        confirmButtonColor: '#EE2737',
        cancelButtonText: 'Nanti Saja',
        focusConfirm: false,
        didOpen: () => {
            window.setStoreStar = function(val) {
                currentStoreRating = val;
                const stars = document.querySelectorAll('#store-star-rating i');
                stars.forEach((s, idx) => {
                    if (idx < val) {
                        s.className = 'bi bi-star-fill text-warning fs-3 cursor-pointer';
                    } else {
                        s.className = 'bi bi-star text-muted fs-3 cursor-pointer';
                    }
                });
                const texts = ['', 'Kecewa (1 Bintang)', 'Kurang Puas (2 Bintang)', 'Cukup Baik (3 Bintang)', 'Puas (4 Bintang)', 'Sangat Puas (5 Bintang)'];
                document.getElementById('store-star-text').textContent = texts[val] || '';
            };

            window.setDriverStar = function(val) {
                currentDriverRating = val;
                const stars = document.querySelectorAll('#driver-star-rating i');
                stars.forEach((s, idx) => {
                    if (idx < val) {
                        s.className = 'bi bi-star-fill text-warning fs-3 cursor-pointer';
                    } else {
                        s.className = 'bi bi-star text-muted fs-3 cursor-pointer';
                    }
                });
                const texts = ['', 'Kurang Baik (1 Bintang)', 'Biasa Saja (2 Bintang)', 'Cukup Ramah (3 Bintang)', 'Pengantaran Baik (4 Bintang)', 'Pengantaran Cepat & Ramah (5 Bintang)'];
                const txtEl = document.getElementById('driver-star-text');
                if (txtEl) txtEl.textContent = texts[val] || '';
            };
        },
        preConfirm: async () => {
            const storeComment = document.getElementById('store-review-comment')?.value || '';
            const driverComment = document.getElementById('driver-review-comment')?.value || '';

            const fd = new FormData();
            fd.append('order_id', orderId);
            fd.append('order_code', orderCode);
            fd.append('store_rating', currentStoreRating);
            fd.append('store_comment', storeComment);
            if (hasDriver) {
                fd.append('dm_rating', currentDriverRating);
                fd.append('dm_comment', driverComment);
            }

            try {
                const res = await fetch(window.BASE_URL + '/orders/review', {
                    method: 'POST',
                    body: fd
                });
                const data = await res.json();
                if (!data.success) {
                    throw new Error(data.message || 'Gagal mengirimkan ulasan.');
                }
                return data;
            } catch (err) {
                Swal.showValidationMessage(err.message || 'Terjadi kesalahan sistem.');
                return false;
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                icon: 'success',
                title: 'Ulasan Terkirim! 🎉',
                text: 'Terima kasih telah memberikan rating dan penilaian Anda.',
                timer: 1800,
                showConfirmButton: false
            }).then(() => {
                location.reload();
            });
        }
    });
}

// Customer Order List Live Auto-Sync
(function() {
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

    setInterval(syncOrdersList, 4000);
})();
</script>

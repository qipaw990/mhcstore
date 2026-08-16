<div class="p-3 border-bottom bg-white d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-2">
        <a href="<?= $baseUrl ?>" class="btn btn-light btn-sm rounded-circle"><i class="bi bi-arrow-left"></i></a>
        <h6 class="fw-bold m-0" style="color: var(--gojek-charcoal);">Chat & Notifikasi</h6>
    </div>
    <span class="badge text-white px-2 py-1 rounded-pill" style="background:#EE2737; font-size: 10px;">
        <?= count($notifications) ?> Pesan
    </span>
</div>

<div class="p-3">
    <!-- Chat with CS / Driver Quick Card -->
    <div class="p-3 bg-white rounded-4 border shadow-sm mb-3 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle text-white d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background: #EE2737;">
                <i class="bi bi-headset fs-5"></i>
            </div>
            <div>
                <div class="fw-bold small text-dark">Customer Care CicalengkaGO</div>
                <div class="text-muted" style="font-size: 11px;">Bantuan pesanan & driver 24/7</div>
            </div>
        </div>
        <a href="https://wa.me/6281234567890?text=Halo%20Admin%20CicalengkaGO,%20saya%20butuh%20bantuan" target="_blank" class="btn btn-sm rounded-pill fw-bold px-3 text-white" style="background:#25D366; font-size: 11px;">
            <i class="bi bi-whatsapp me-1"></i> Chat
        </a>
    </div>

    <h6 class="fw-bold mb-3" style="font-size: 13px; color: var(--gojek-charcoal);"><i class="bi bi-bell-fill text-warning me-1"></i> Pesan & Pemberitahuan Masuk</h6>

    <?php if (empty($notifications)): ?>
        <div class="text-center py-5 text-muted bg-light rounded-4 border p-3">
            <i class="bi bi-chat-dots-fill display-4 text-muted mb-2 d-block"></i>
            <h6 class="fw-bold" style="color: var(--gojek-charcoal);">Belum Ada Pesan Masuk</h6>
            <p class="small text-muted mb-0">Pemberitahuan status pesanan dan voucher promo akan tampil di sini.</p>
        </div>
    <?php else: ?>
        <div class="d-flex flex-column gap-2">
            <?php foreach ($notifications as $n): ?>
                <div class="p-3 bg-white rounded-4 border shadow-sm d-flex align-items-start gap-3">
                    <div class="rounded-circle text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px; background: #EE2737;">
                        <i class="bi bi-bell-fill" style="font-size: 14px;"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold small text-dark"><?= htmlspecialchars($n['title']) ?></div>
                        <p class="small text-muted mb-1" style="font-size: 12px;"><?= htmlspecialchars($n['message']) ?></p>
                        <span class="text-muted" style="font-size: 10px;"><i class="bi bi-clock me-1"></i><?= date('d M Y, H:i', strtotime($n['created_at'])) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

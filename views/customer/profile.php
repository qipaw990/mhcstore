<div class="p-3 border-bottom bg-white d-flex align-items-center justify-content-between">
    <h6 class="fw-bold m-0" style="color: var(--gojek-charcoal);">Akun Saya</h6>
    <?php if ($user): ?>
        <span class="badge text-white px-2 py-1 rounded-pill" style="background: #EE2737; font-size: 10px;">
            <i class="bi bi-star-fill me-1 text-warning"></i> CicalengkaClub
        </span>
    <?php endif; ?>
</div>

<div class="p-3">
    <?php if ($user): ?>
        <!-- User Profile Header Card -->
        <div class="p-3 bg-white rounded-4 border shadow-sm mb-3 d-flex align-items-center gap-3">
            <div class="position-relative">
                <img src="<?= $baseUrl ?>/<?= htmlspecialchars($user['avatar'] ?? 'assets/images/users/default.png') ?>" alt="User" class="rounded-circle border border-2" style="width: 60px; height: 60px; object-fit: cover; border-color: #EE2737 !important;">
            </div>
            <div class="flex-grow-1">
                <div class="fw-bold fs-6" style="color: var(--gojek-charcoal);"><?= htmlspecialchars($user['name'] ?? 'Pengguna') ?></div>
                <div class="text-muted small" style="font-size: 12px;"><?= htmlspecialchars($user['phone'] ?? '-') ?></div>
                <?php if (!empty($user['email'])): ?>
                    <div class="text-muted small" style="font-size: 11px;"><?= htmlspecialchars($user['email']) ?></div>
                <?php endif; ?>
            </div>
            <a href="<?= $baseUrl ?>/profile" class="btn btn-light btn-sm rounded-circle border">
                <i class="bi bi-pencil-fill text-muted" style="font-size: 12px;"></i>
            </a>
        </div>

        <!-- CicalengkaPay Quick Card -->
        <div class="p-3 bg-white rounded-4 border shadow-sm mb-3 d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 44px; height: 44px; background: linear-gradient(135deg, #EE2737, #C61524); box-shadow: 0 4px 10px rgba(238,39,55,0.25);">
                    <i class="bi bi-wallet2 fs-5"></i>
                </div>
                <div>
                    <div class="small fw-bold" style="color: #EE2737;">Cicalengka<span style="color:#C61524;">Pay</span></div>
                    <div class="fw-bold text-dark fs-6"><?= format_rupiah($wallet['balance'] ?? 0) ?></div>
                </div>
            </div>
            <a href="<?= $baseUrl ?>/wallet" class="btn btn-sm rounded-pill fw-bold px-3 text-white" style="background:#EE2737; font-size: 11px;">
                Isi Saldo
            </a>
        </div>

        <!-- Menu Navigation List -->
        <div class="bg-white rounded-4 border shadow-sm overflow-hidden mb-3">
            <a href="<?= $baseUrl ?>/orders" class="p-3 d-flex align-items-center justify-content-between text-decoration-none text-dark border-bottom">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-receipt fs-5" style="color: #EE2737 !important;"></i>
                    <span class="small fw-bold">Riwayat Pesanan</span>
                </div>
                <i class="bi bi-chevron-right text-muted small"></i>
            </a>

            <a href="<?= $baseUrl ?>/search" class="p-3 d-flex align-items-center justify-content-between text-decoration-none text-dark border-bottom">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-percent text-warning fs-5"></i>
                    <span class="small fw-bold">Voucher & Promo Saya</span>
                </div>
                <i class="bi bi-chevron-right text-muted small"></i>
            </a>

            <a href="<?= $baseUrl ?>/notifications" class="p-3 d-flex align-items-center justify-content-between text-decoration-none text-dark border-bottom">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-bell text-primary fs-5"></i>
                    <span class="small fw-bold">Pusat Notifikasi</span>
                </div>
                <i class="bi bi-chevron-right text-muted small"></i>
            </a>

            <a href="javascript:void(0)" onclick="Swal.fire('Pusat Bantuan', 'Hubungi layanan pelanggan CicalengkaGO via WhatsApp di 0812-3456-7890', 'info')" class="p-3 d-flex align-items-center justify-content-between text-decoration-none text-dark border-bottom">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-question-circle-fill text-info fs-5"></i>
                    <span class="small fw-bold">Bantuan & CS 24 Jam</span>
                </div>
                <i class="bi bi-chevron-right text-muted small"></i>
            </a>

            <a href="<?= $baseUrl ?>/logout" class="p-3 d-flex align-items-center justify-content-between text-decoration-none text-danger">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-box-arrow-right fs-5"></i>
                    <span class="small fw-bold">Keluar Akun</span>
                </div>
                <i class="bi bi-chevron-right small"></i>
            </a>
        </div>
    <?php else: ?>
        <div class="text-center py-5">
            <div class="rounded-circle bg-light text-muted d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 70px; height: 70px; font-size: 28px;">
                <i class="bi bi-person-lock text-muted"></i>
            </div>
            <h6 class="fw-bold" style="color: var(--gojek-charcoal);">Masuk ke Akun CicalengkaGO</h6>
            <p class="text-muted small">Masuk untuk melihat pesanan, saldo CicalengkaPay, dan promo khusus Anda.</p>
            <div class="d-grid gap-2 mt-3">
                <a href="<?= $baseUrl ?>/login" class="btn btn-gojek-green py-2" style="background:#EE2737 !important; color:#FFFFFF !important; border-radius:9999px; font-weight:800; text-decoration:none;">Masuk Sekarang</a>
                <a href="<?= $baseUrl ?>/register" class="btn btn-outline-danger rounded-pill fw-bold py-2" style="border-color:#EE2737; color:#EE2737;">Daftar Akun Baru</a>
            </div>
        </div>
    <?php endif; ?>
</div>

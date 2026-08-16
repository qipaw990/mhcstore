<div class="border-bottom bg-white d-flex align-items-center justify-content-between sticky-top shadow-xs px-3 py-2.5">
    <div class="d-flex align-items-center gap-2.5">
        <a href="<?= $baseUrl ?>" class="btn btn-light btn-sm rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 34px; height: 34px; border: 1px solid #E2E8F0; background: #F8FAFC;"><i class="bi bi-arrow-left text-dark" style="font-size: 14px;"></i></a>
        <h6 class="fw-bold m-0 text-dark" style="font-size: 13.5px; letter-spacing: -0.2px;">Keranjang Belanja</h6>
    </div>
    <?php if (!empty($cart_summary['items'])): ?>
        <button onclick="clearAllCart()" class="btn btn-link text-danger text-decoration-none p-0 fw-bold d-flex align-items-center gap-1" style="font-size: 11px;">
            <i class="bi bi-trash3"></i> Kosongkan
        </button>
    <?php endif; ?>
</div>

<div class="px-3 py-3">
    <?php if (empty($cart_summary['items'])): ?>
        <div class="text-center py-5">
            <div class="rounded-circle bg-light text-muted d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 58px; height: 58px; font-size: 24px;">
                <i class="bi bi-cart-x text-muted"></i>
            </div>
            <h6 class="fw-bold mb-1.5" style="color: var(--gojek-charcoal); font-size: 13.5px;">Keranjang Anda Masih Kosong</h6>
            <p class="text-muted mb-3.5" style="font-size: 11px; max-width: 280px; margin-left: auto; margin-right: auto; line-height: 1.5;">Yuk cari kuliner lezat GoFood atau belanja kebutuhan GoMart Anda sekarang.</p>
            <a href="<?= $baseUrl ?>" class="btn btn-gojek-green px-4 py-2.5" style="background:#EE2737 !important; color:#FFFFFF !important; border-radius:9999px; width: auto; display: inline-flex; text-decoration:none; font-size: 12px; font-weight: 700;">Mulai Belanja</a>
        </div>
    <?php else: ?>
        <!-- Store Name Header Card -->
        <div class="p-3 bg-white border shadow-2xs mb-3 d-flex align-items-center gap-3" style="border-radius: 16px; border-color: #E2E8F0 !important;">
            <div class="rounded-circle text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width: 36px; height: 36px; background: linear-gradient(135deg, #EE2737, #C61524); font-size: 15px; box-shadow: 0 2px 6px rgba(238,39,55,0.25);">
                <i class="bi bi-shop"></i>
            </div>
            <div class="min-w-0 flex-grow-1">
                <div class="fw-bold text-truncate text-dark" style="font-size: 13px; letter-spacing: -0.2px;"><?= htmlspecialchars($cart_summary['store']['name']) ?></div>
                <div class="text-muted d-flex align-items-center gap-1.5 mt-0.5" style="font-size: 10.5px;">
                    <span class="badge bg-success-subtle text-success fw-semibold px-2 py-0.5 rounded-pill" style="font-size: 9px;"><i class="bi bi-clock-fill me-0.5"></i> 20-30 mnt</span>
                    <span>• Pengantaran Langsung</span>
                </div>
            </div>
        </div>

        <!-- Cart Items List -->
        <div class="d-flex flex-column gap-2.5 mb-3">
            <?php foreach ($cart_summary['items'] as $item): ?>
                <div class="p-3 bg-white border shadow-2xs d-flex align-items-center justify-content-between gap-3" style="border-radius: 16px; border-color: #E2E8F0 !important;">
                    <img src="<?= $baseUrl ?>/<?= htmlspecialchars($item['product_image'] ?? 'assets/images/products/default.jpg') ?>" alt="Img" class="rounded-3 flex-shrink-0" style="width: 56px; height: 56px; object-fit: cover;">
                    
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-bold text-dark text-truncate" style="font-size: 12px; letter-spacing: -0.2px;"><?= htmlspecialchars($item['product_name']) ?></div>
                        <div class="fw-extrabold text-danger mt-1" style="font-size: 12.5px;"><?= format_rupiah($item['price']) ?></div>
                    </div>

                    <!-- Quantity Modifiers -->
                    <div class="d-flex align-items-center gap-2 bg-light p-1 px-1.5 rounded-pill border" style="border-color: #E2E8F0 !important;">
                        <button onclick="updateCartQty(<?= $item['id'] ?>, -1)" class="btn btn-sm btn-white p-0 d-flex align-items-center justify-content-center text-dark border shadow-2xs" style="width: 26px; height: 26px; border-radius: 50%; font-size: 12px; background:#FFFFFF;">
                            <i class="bi bi-dash-lg"></i>
                        </button>
                        <span class="fw-bold px-1" style="font-size: 12px; color: var(--gojek-charcoal);"><?= $item['quantity'] ?></span>
                        <button onclick="updateCartQty(<?= $item['id'] ?>, 1)" class="btn btn-sm p-0 d-flex align-items-center justify-content-center text-white border-0 shadow-2xs" style="width: 26px; height: 26px; background: #EE2737; border-radius: 50%; font-size: 12px;">
                            <i class="bi bi-plus-lg"></i>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Summary Calculation Card -->
        <div class="p-3.5 bg-white border shadow-2xs mb-3.5" style="border-radius: 16px; border-color: #E2E8F0 !important;">
            <h6 class="fw-bold mb-3 text-dark d-flex align-items-center justify-content-between" style="font-size: 12.5px;">
                <span><i class="bi bi-receipt me-1.5 text-danger"></i> Ringkasan Pembayaran</span>
                <span class="badge bg-light text-muted fw-normal px-2 py-1 rounded-pill" style="font-size: 9.5px;"><?= $cart_summary['count'] ?> Item</span>
            </h6>
            <div class="d-flex justify-content-between text-muted mb-2" style="font-size: 11px;">
                <span>Subtotal Menu</span>
                <span class="text-dark fw-bold"><?= format_rupiah($cart_summary['subtotal']) ?></span>
            </div>
            <div class="d-flex justify-content-between text-muted mb-2" style="font-size: 11px;">
                <span>Estimasi Ongkir</span>
                <span class="text-dark fw-bold"><?= format_rupiah($cart_summary['store']['delivery_fee']) ?></span>
            </div>
            <hr class="my-2.5" style="border-color: #F1F5F9;">
            <div class="d-flex justify-content-between align-items-center fw-bold" style="font-size: 13px;">
                <span class="text-dark">Total Pembayaran</span>
                <span class="text-danger fs-6"><?= format_rupiah($cart_summary['subtotal'] + $cart_summary['store']['delivery_fee']) ?></span>
            </div>
        </div>

        <!-- Checkout Action Button -->
        <a href="<?= $baseUrl ?>/checkout" class="btn btn-gojek-green w-100 py-2.5" style="background: linear-gradient(135deg, #EE2737, #C61524) !important; color:#FFFFFF !important; border-radius:9999px; font-weight:800; font-size:12.5px; box-shadow:0 4px 14px rgba(238,39,55,0.3); text-decoration:none; display:flex; align-items:center; justify-content:center; gap:8px;">
            <span>Lanjut ke Pembayaran</span>
            <i class="bi bi-arrow-right fs-6"></i>
        </a>
    <?php endif; ?>
</div>

<script>
async function clearAllCart() {
    Swal.fire({
        title: 'Kosongkan Keranjang?',
        text: 'Semua menu dalam keranjang akan dihapus.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Kosongkan',
        cancelButtonText: 'Batal'
    }).then(async (result) => {
        if (result.isConfirmed) {
            await fetch(window.BASE_URL + '/cart/clear', { method: 'POST' });
            location.reload();
        }
    });
}
</script>

<div class="border-bottom bg-white d-flex align-items-center justify-content-between sticky-top app-subpage-header px-3 py-2">
    <div class="d-flex align-items-center gap-2">
        <a href="<?= $baseUrl ?>" class="btn btn-light btn-sm rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 30px; height: 30px; border: 1px solid #E2E8F0; background: #F8FAFC;"><i class="bi bi-arrow-left text-dark" style="font-size: 13px;"></i></a>
        <h6 class="fw-bold m-0 text-dark" style="font-size: 13.5px; letter-spacing: -0.3px;">Keranjang Belanja</h6>
    </div>
    <?php if (!empty($cart_summary['items'])): ?>
        <button onclick="clearAllCart()" class="btn btn-link text-danger text-decoration-none p-0 fw-bold d-flex align-items-center gap-1" style="font-size: 10px;">
            <i class="bi bi-trash3"></i> Kosongkan
        </button>
    <?php endif; ?>
</div>

<div class="px-2.5 py-2.5">
    <?php if (empty($cart_summary['items'])): ?>
        <div class="text-center py-4">
            <div class="rounded-circle bg-light text-muted d-flex align-items-center justify-content-center mx-auto mb-2.5" style="width: 52px; height: 52px; font-size: 22px;">
                <i class="bi bi-cart-x text-muted"></i>
            </div>
            <h6 class="fw-bold mb-1" style="color: var(--gojek-charcoal); font-size: 13px;">Keranjang Anda Masih Kosong</h6>
            <p class="text-muted mb-3" style="font-size: 10.5px; max-width: 260px; margin-left: auto; margin-right: auto; line-height: 1.4;">Yuk cari kuliner lezat GoFood atau belanja kebutuhan GoMart Anda sekarang.</p>
            <a href="<?= $baseUrl ?>" class="btn btn-gojek-green px-3.5 py-2" style="background:#EE2737 !important; color:#FFFFFF !important; border-radius:9999px; width: auto; display: inline-flex; text-decoration:none; font-size: 11.5px; font-weight: 700;">Mulai Belanja</a>
        </div>
    <?php else: ?>
        <!-- Store Name Header Card -->
        <div class="p-2.5 bg-white border shadow-2xs mb-2.5 d-flex align-items-center gap-2 overflow-hidden" style="border-radius: 14px; border-color: #E2E8F0 !important;">
            <div class="rounded-circle text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px; background: linear-gradient(135deg, #EE2737, #C61524); font-size: 13px; box-shadow: 0 2px 5px rgba(238,39,55,0.25);">
                <i class="bi bi-shop"></i>
            </div>
            <div class="min-w-0 flex-grow-1">
                <div class="fw-bold text-truncate text-dark" style="font-size: 12px; letter-spacing: -0.2px;"><?= htmlspecialchars($cart_summary['store']['name']) ?></div>
                <div class="text-muted d-flex align-items-center gap-1 mt-0.5" style="font-size: 9.5px;">
                    <span class="badge bg-success-subtle text-success fw-semibold px-1.5 py-0.5 rounded-pill flex-shrink-0" style="font-size: 8.5px;"><i class="bi bi-clock-fill me-0.5"></i> 20-30 mnt</span>
                    <span class="text-truncate">• Pengantaran Langsung</span>
                </div>
            </div>
        </div>

        <!-- Cart Items List -->
        <div class="d-flex flex-column gap-2 mb-2.5">
            <?php foreach ($cart_summary['items'] as $item): ?>
                <div class="p-2 bg-white border shadow-2xs d-flex align-items-center justify-content-between gap-2 overflow-hidden" style="border-radius: 14px; border-color: #E2E8F0 !important;">
                    <img src="<?= $baseUrl ?>/<?= htmlspecialchars($item['product_image'] ?? 'assets/images/products/default.jpg') ?>" alt="Img" class="rounded-3 flex-shrink-0" style="width: 44px; height: 44px; object-fit: cover;">
                    
                    <div class="flex-grow-1 min-w-0 me-1">
                        <div class="fw-bold text-dark text-truncate" style="font-size: 11.5px; letter-spacing: -0.2px;" title="<?= htmlspecialchars($item['product_name']) ?>"><?= htmlspecialchars($item['product_name']) ?></div>
                        <div class="fw-extrabold text-danger mt-0.5 text-truncate" style="font-size: 11.5px;"><?= format_rupiah($item['price']) ?></div>
                    </div>

                    <!-- Quantity Modifiers -->
                    <div class="d-flex align-items-center gap-1 bg-light p-0.5 px-1 rounded-pill border flex-shrink-0" style="border-color: #E2E8F0 !important;">
                        <button onclick="updateCartQty(<?= $item['id'] ?>, -1)" class="btn btn-sm btn-white p-0 d-flex align-items-center justify-content-center text-dark border shadow-2xs" style="width: 22px; height: 22px; border-radius: 50%; font-size: 10px; background:#FFFFFF;">
                            <i class="bi bi-dash-lg"></i>
                        </button>
                        <span class="fw-bold px-1" style="font-size: 11px; color: var(--gojek-charcoal);"><?= $item['quantity'] ?></span>
                        <button onclick="updateCartQty(<?= $item['id'] ?>, 1)" class="btn btn-sm p-0 d-flex align-items-center justify-content-center text-white border-0 shadow-2xs" style="width: 22px; height: 22px; background: #EE2737; border-radius: 50%; font-size: 10px;">
                            <i class="bi bi-plus-lg"></i>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Summary Calculation Card -->
        <div class="p-2.5 bg-white border shadow-2xs mb-2.5 overflow-hidden" style="border-radius: 14px; border-color: #E2E8F0 !important;">
            <h6 class="fw-bold mb-2 text-dark d-flex align-items-center justify-content-between gap-2" style="font-size: 11.5px;">
                <span class="text-truncate"><i class="bi bi-receipt me-1 text-danger"></i> Ringkasan Pembayaran</span>
                <span class="badge bg-light text-muted fw-normal px-1.5 py-0.5 rounded-pill flex-shrink-0" style="font-size: 9px;"><?= $cart_summary['count'] ?> Item</span>
            </h6>
            <div class="d-flex justify-content-between text-muted mb-1.5 gap-2" style="font-size: 10.5px;">
                <span class="text-truncate">Subtotal Menu</span>
                <span class="text-dark fw-bold flex-shrink-0"><?= format_rupiah($cart_summary['subtotal']) ?></span>
            </div>
            <div class="d-flex justify-content-between text-muted mb-1.5 gap-2" style="font-size: 10.5px;">
                <span class="text-truncate">Estimasi Ongkir</span>
                <span class="text-dark fw-bold flex-shrink-0"><?= format_rupiah($cart_summary['store']['delivery_fee']) ?></span>
            </div>
            <hr class="my-2" style="border-color: #F1F5F9;">
            <div class="d-flex justify-content-between align-items-center fw-bold gap-2" style="font-size: 12px;">
                <span class="text-dark text-truncate">Total Pembayaran</span>
                <span class="text-danger flex-shrink-0" style="font-size: 13.5px; font-weight: 900;"><?= format_rupiah($cart_summary['subtotal'] + $cart_summary['store']['delivery_fee']) ?></span>
            </div>
        </div>

        <!-- Checkout Action Button -->
        <a href="<?= $baseUrl ?>/checkout" class="btn btn-gojek-green w-100 py-2" style="background: linear-gradient(135deg, #EE2737, #C61524) !important; color:#FFFFFF !important; border-radius:9999px; font-weight:800; font-size:11.5px; box-shadow:0 3px 10px rgba(238,39,55,0.3); text-decoration:none; display:flex; align-items:center; justify-content:center; gap:6px;">
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

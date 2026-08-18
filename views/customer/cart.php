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

<div class="px-3 py-3 pb-5" style="background: #F8FAFC; min-height: calc(100vh - 60px);">
    <?php if (empty($cart_summary['items'])): ?>
        <div class="text-center py-5 bg-white border my-2" style="border-radius: 20px; border-color: #E2E8F0 !important;">
            <div class="d-flex align-items-center justify-content-center mx-auto mb-3 rounded-circle shadow-2xs" style="width: 64px; height: 64px; background: #FEE2E2; color: #EE2737; font-size: 28px;">
                <i class="bi bi-cart-x"></i>
            </div>
            <h6 class="fw-extrabold mb-1.5" style="color: #0F172A; font-size: 14px;">Keranjang Masih Kosong</h6>
            <p class="text-muted mb-4 mx-auto" style="font-size: 11px; max-width: 260px; line-height: 1.5;">Yuk cari kuliner lezat atau belanja kebutuhan sehari-hari Anda sekarang.</p>
            <a href="<?= $baseUrl ?>" class="btn text-white fw-extrabold shadow-2xs px-4 py-2.5" style="background: linear-gradient(135deg, #EE2737, #C61524); border-radius: 9999px; font-size: 12px; text-decoration: none;">
                <i class="bi bi-bag-check-fill me-1.5"></i> Mulai Belanja
            </a>
        </div>
    <?php else: ?>
        <!-- Store Name Header Card -->
        <div class="p-3 bg-white border shadow-2xs mb-3 d-flex align-items-center gap-2.5 overflow-hidden" style="border-radius: 18px; border-color: #E2E8F0 !important;">
            <div class="rounded-circle text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px; background: linear-gradient(135deg, #EE2737, #C61524); font-size: 16px; box-shadow: 0 4px 12px rgba(238,39,55,0.25);">
                <i class="bi bi-shop"></i>
            </div>
            <div class="min-w-0 flex-grow-1">
                <div class="fw-extrabold text-truncate text-dark" style="font-size: 13px; letter-spacing: -0.3px;"><?= htmlspecialchars($cart_summary['store']['name']) ?></div>
                <div class="d-flex align-items-center gap-1.5 mt-1">
                    <span class="badge bg-success-subtle text-success fw-bold px-2 py-1 rounded-pill flex-shrink-0" style="font-size: 9px;"><i class="bi bi-clock-fill me-1"></i>20-30 mnt</span>
                    <span class="text-muted" style="font-size: 9.5px; font-weight: 500;">• Pengantaran Langsung</span>
                </div>
            </div>
        </div>

        <!-- Cart Items List -->
        <div class="d-flex flex-column gap-2.5 mb-3">
            <?php foreach ($cart_summary['items'] as $item): ?>
                <div class="p-2.5 bg-white border shadow-2xs d-flex align-items-center justify-content-between gap-2.5 overflow-hidden" style="border-radius: 16px; border-color: #E2E8F0 !important;">
                    <img src="<?= $baseUrl ?>/<?= htmlspecialchars($item['product_image'] ?? 'assets/images/products/default.jpg') ?>" alt="Img" class="rounded-3 flex-shrink-0" style="width: 52px; height: 52px; object-fit: cover; border-radius: 12px !important;">
                    
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-bold text-dark text-truncate" style="font-size: 12px; letter-spacing: -0.2px;" title="<?= htmlspecialchars($item['product_name']) ?>"><?= htmlspecialchars($item['product_name']) ?></div>
                        <div class="fw-extrabold text-danger mt-1" style="font-size: 12.5px;"><?= format_rupiah($item['price']) ?></div>
                    </div>

                    <!-- Quantity Modifiers -->
                    <div class="d-flex align-items-center gap-1.5 flex-shrink-0">
                        <button onclick="updateCartQty(<?= $item['id'] ?>, -1)" class="btn btn-sm p-0 d-flex align-items-center justify-content-center text-dark border shadow-2xs" style="width: 28px; height: 28px; border-radius: 50%; font-size: 11px; background:#FFFFFF; border-color: #CBD5E1 !important;">
                            <i class="bi bi-dash"></i>
                        </button>
                        <span class="fw-extrabold text-center" style="font-size: 13px; min-width: 20px; color: #0F172A;"><?= $item['quantity'] ?></span>
                        <button onclick="updateCartQty(<?= $item['id'] ?>, 1)" class="btn btn-sm p-0 d-flex align-items-center justify-content-center text-white border-0 shadow-2xs" style="width: 28px; height: 28px; background: #EE2737; border-radius: 50%; font-size: 11px;">
                            <i class="bi bi-plus"></i>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Summary Calculation Card -->
        <div class="p-3 bg-white border shadow-2xs mb-3" style="border-radius: 18px; border-color: #E2E8F0 !important;">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="d-flex align-items-center justify-content-center rounded-2" style="width: 28px; height: 28px; background: #FEE2E2;">
                        <i class="bi bi-receipt text-danger" style="font-size: 13px;"></i>
                    </div>
                    <span class="fw-extrabold text-dark" style="font-size: 13px; letter-spacing: -0.2px;">Ringkasan Pembayaran</span>
                </div>
                <span class="badge bg-danger-subtle text-danger fw-bold px-2 py-1 rounded-pill flex-shrink-0" style="font-size: 9.5px;"><?= $cart_summary['count'] ?> Item</span>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-2 gap-2">
                <span class="text-muted" style="font-size: 11px; font-weight: 500;">Subtotal Menu</span>
                <span class="text-dark fw-bold flex-shrink-0" style="font-size: 11.5px;"><?= format_rupiah($cart_summary['subtotal']) ?></span>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-2.5 gap-2">
                <span class="text-muted" style="font-size: 11px; font-weight: 500;">Estimasi Ongkir</span>
                <span class="text-dark fw-bold flex-shrink-0" style="font-size: 11.5px;"><?= format_rupiah($cart_summary['store']['delivery_fee']) ?></span>
            </div>

            <div class="pt-2.5" style="border-top: 1.5px dashed #E2E8F0;">
                <div class="d-flex justify-content-between align-items-center gap-2">
                    <span class="fw-extrabold text-dark" style="font-size: 13px; letter-spacing: -0.2px;">Total Pembayaran</span>
                    <span class="fw-black text-danger flex-shrink-0" style="font-size: 15px; letter-spacing: -0.5px;"><?= format_rupiah($cart_summary['subtotal'] + $cart_summary['store']['delivery_fee']) ?></span>
                </div>
            </div>
        </div>

        <!-- Checkout Action Button -->
        <a href="<?= $baseUrl ?>/checkout" class="btn w-100 fw-extrabold text-white shadow-2xs d-flex align-items-center justify-content-center gap-2" style="background: linear-gradient(135deg, #EE2737, #C61524); border-radius: 16px; padding: 14px 20px; font-size: 13px; box-shadow: 0 6px 20px rgba(238,39,55,0.35); text-decoration: none; letter-spacing: -0.2px;">
            <span>Lanjut ke Pembayaran</span>
            <i class="bi bi-arrow-right" style="font-size: 15px;"></i>
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

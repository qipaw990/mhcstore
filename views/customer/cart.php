<div class="border-bottom bg-white d-flex align-items-center justify-content-between sticky-top shadow-xs px-3.5 py-3">
    <div class="d-flex align-items-center gap-2.5">
        <a href="<?= $baseUrl ?>" class="btn btn-light btn-sm rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 36px; height: 36px; border: 1px solid #E2E8F0; background: #F8FAFC;"><i class="bi bi-arrow-left text-dark" style="font-size: 15px;"></i></a>
        <h6 class="fw-bold m-0 text-dark" style="font-size: 14px;">Keranjang Belanja</h6>
    </div>
    <?php if (!empty($cart_summary['items'])): ?>
        <button onclick="clearAllCart()" class="btn btn-link text-danger text-decoration-none p-0 fw-bold" style="font-size: 12px;">Kosongkan</button>
    <?php endif; ?>
</div>

<div class="px-3.5 pt-4 pb-5" style="min-height: 85vh;">
    <?php if (empty($cart_summary['items'])): ?>
        <div class="text-center py-5 bg-white border p-4 shadow-xs" style="border-radius: 16px;">
            <div class="rounded-circle bg-light text-muted d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 58px; height: 58px; font-size: 24px;">
                <i class="bi bi-cart-x text-muted"></i>
            </div>
            <h6 class="fw-bold mb-1.5" style="color: var(--gojek-charcoal); font-size: 13.5px;">Keranjang Anda Masih Kosong</h6>
            <p class="text-muted mb-3.5" style="font-size: 11px;">Yuk cari kuliner lezat atau belanja kebutuhan Anda sekarang.</p>
            <a href="<?= $baseUrl ?>" class="btn btn-gojek-green px-4 py-2.5" style="background:#EE2737 !important; color:#FFFFFF !important; border-radius:9999px; width: auto; display: inline-flex; text-decoration:none; font-size: 12px; font-weight: 700;">Mulai Belanja</a>
        </div>
    <?php else: ?>
        <!-- Store Name Header -->
        <div class="p-3.5 bg-white border shadow-xs mb-4 d-flex align-items-center gap-3" style="border-radius: 16px;">
            <div class="rounded-circle text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px; background: #EE2737; font-size: 16px;">
                <i class="bi bi-shop"></i>
            </div>
            <div class="min-w-0">
                <div class="fw-bold text-truncate text-dark" style="font-size: 13px;"><?= htmlspecialchars($cart_summary['store']['name']) ?></div>
                <div class="text-muted" style="font-size: 10.5px;">Estimasi pengantaran 20-30 mnt</div>
            </div>
        </div>

        <!-- Cart Items List -->
        <div class="d-flex flex-column gap-3.5 mb-4">
            <?php foreach ($cart_summary['items'] as $item): ?>
                <div class="p-3.5 bg-white border shadow-xs d-flex align-items-center justify-content-between gap-3" style="border-radius: 16px;">
                    <img src="<?= $baseUrl ?>/<?= htmlspecialchars($item['product_image'] ?? 'assets/images/products/default.jpg') ?>" alt="Img" class="rounded-3 flex-shrink-0" style="width: 56px; height: 56px; object-fit: cover;">
                    
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-bold text-dark text-truncate" style="font-size: 12.5px;"><?= htmlspecialchars($item['product_name']) ?></div>
                        <div class="fw-bold text-danger mt-0.5" style="font-size: 12.5px;"><?= format_rupiah($item['price']) ?></div>
                    </div>

                    <!-- Quantity Modifiers -->
                    <div class="d-flex align-items-center gap-2 bg-light p-1 rounded-pill border">
                        <button onclick="updateCartQty(<?= $item['id'] ?>, -1)" class="btn btn-sm btn-white p-0 d-flex align-items-center justify-content-center text-dark" style="width: 26px; height: 26px; border-radius: 50%; font-size: 12px;">
                            <i class="bi bi-dash"></i>
                        </button>
                        <span class="fw-bold px-1" style="font-size: 12px;"><?= $item['quantity'] ?></span>
                        <button onclick="updateCartQty(<?= $item['id'] ?>, 1)" class="btn btn-sm btn-white p-0 d-flex align-items-center justify-content-center text-white" style="width: 26px; height: 26px; background: #EE2737; border-radius: 50%; font-size: 12px;">
                            <i class="bi bi-plus"></i>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Summary Calculation Card -->
        <div class="p-3.5 bg-white border shadow-xs mb-4" style="border-radius: 16px;">
            <h6 class="fw-bold mb-3 text-dark" style="font-size: 13px;">Ringkasan Pembayaran</h6>
            <div class="d-flex justify-content-between text-muted mb-2" style="font-size: 11px;">
                <span>Subtotal (<?= $cart_summary['count'] ?> item)</span>
                <span class="text-dark fw-bold"><?= format_rupiah($cart_summary['subtotal']) ?></span>
            </div>
            <div class="d-flex justify-content-between text-muted mb-2" style="font-size: 11px;">
                <span>Estimasi Ongkir</span>
                <span class="text-dark fw-bold"><?= format_rupiah($cart_summary['store']['delivery_fee']) ?></span>
            </div>
            <hr class="my-2.5">
            <div class="d-flex justify-content-between fw-bold" style="font-size: 13px;">
                <span>Total Sementara</span>
                <span class="text-danger"><?= format_rupiah($cart_summary['subtotal'] + $cart_summary['store']['delivery_fee']) ?></span>
            </div>
        </div>

        <!-- Checkout Action Button -->
        <a href="<?= $baseUrl ?>/checkout" class="btn btn-gojek-green w-100" style="background:#EE2737 !important; color:#FFFFFF !important; border-radius:9999px; font-weight:700; padding:12px 18px; font-size:13px; box-shadow:0 3px 12px rgba(238,39,55,0.3); text-decoration:none; display:flex; align-items:center; justify-content:center; gap:8px;">
            <span>Lanjut ke Pembayaran</span>
            <i class="bi bi-arrow-right"></i>
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

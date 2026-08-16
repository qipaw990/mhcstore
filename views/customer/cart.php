<div class="p-3 border-bottom bg-white d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-2">
        <a href="<?= $baseUrl ?>" class="btn btn-light btn-sm rounded-circle"><i class="bi bi-arrow-left"></i></a>
        <h6 class="fw-bold m-0" style="color: var(--gojek-charcoal);">Keranjang Belanja</h6>
    </div>
    <?php if (!empty($cart_summary['items'])): ?>
        <button onclick="clearAllCart()" class="btn btn-link text-danger text-decoration-none small p-0 fw-bold" style="font-size: 12px;">Kosongkan</button>
    <?php endif; ?>
</div>

<div class="p-3">
    <?php if (empty($cart_summary['items'])): ?>
        <div class="text-center py-5">
            <div class="rounded-circle bg-light text-muted d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px; font-size: 32px;">
                <i class="bi bi-cart-x text-muted"></i>
            </div>
            <h6 class="fw-bold" style="color: var(--gojek-charcoal);">Keranjang Anda Masih Kosong</h6>
            <p class="text-muted small">Yuk cari kuliner lezat atau belanja kebutuhan Anda sekarang.</p>
            <a href="<?= $baseUrl ?>" class="btn btn-gojek-green px-4 mt-3" style="background:#EE2737 !important; color:#FFFFFF !important; border-radius:9999px; width: auto; display: inline-flex; text-decoration:none;">Mulai Belanja</a>
        </div>
    <?php else: ?>
        <!-- Store Name Header -->
        <div class="p-3 bg-white rounded-4 border shadow-sm mb-3 d-flex align-items-center gap-2">
            <div class="rounded-circle text-white d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; background: #EE2737;">
                <i class="bi bi-shop"></i>
            </div>
            <div>
                <div class="fw-bold small" style="color: var(--gojek-charcoal);"><?= htmlspecialchars($cart_summary['store']['name']) ?></div>
                <div class="text-muted" style="font-size: 11px;">Estimasi pengantaran 20-30 mnt</div>
            </div>
        </div>

        <!-- Cart Items List -->
        <div class="d-flex flex-column gap-2 mb-4">
            <?php foreach ($cart_summary['items'] as $item): ?>
                <div class="p-3 bg-white rounded-4 border shadow-sm d-flex align-items-center justify-content-between gap-3">
                    <img src="<?= $baseUrl ?>/<?= htmlspecialchars($item['product_image'] ?? 'assets/images/products/default.jpg') ?>" alt="Img" class="rounded-3" style="width: 60px; height: 60px; object-fit: cover;">
                    
                    <div class="flex-grow-1">
                        <div class="fw-bold small text-dark"><?= htmlspecialchars($item['product_name']) ?></div>
                        <div class="fw-bold small text-dark"><?= format_rupiah($item['price']) ?></div>
                    </div>

                    <!-- Quantity Modifiers -->
                    <div class="d-flex align-items-center gap-2 bg-light p-1 rounded-pill border">
                        <button onclick="updateCartQty(<?= $item['id'] ?>, -1)" class="btn btn-sm btn-white p-0 d-flex align-items-center justify-content-center text-dark" style="width: 26px; height: 26px; border-radius: 50%;">
                            <i class="bi bi-dash"></i>
                        </button>
                        <span class="small fw-bold px-1"><?= $item['quantity'] ?></span>
                        <button onclick="updateCartQty(<?= $item['id'] ?>, 1)" class="btn btn-sm btn-white p-0 d-flex align-items-center justify-content-center text-white" style="width: 26px; height: 26px; background: #EE2737; border-radius: 50%;">
                            <i class="bi bi-plus"></i>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Summary Calculation Card -->
        <div class="p-3 bg-white rounded-4 border shadow-sm mb-4">
            <h6 class="fw-bold small mb-3" style="color: var(--gojek-charcoal);">Ringkasan Pembayaran</h6>
            <div class="d-flex justify-content-between small text-muted mb-2">
                <span>Subtotal Menu (<?= $cart_summary['count'] ?> item)</span>
                <span class="text-dark fw-bold"><?= format_rupiah($cart_summary['subtotal']) ?></span>
            </div>
            <div class="d-flex justify-content-between small text-muted mb-2">
                <span>Estimasi Ongkir</span>
                <span class="text-dark fw-bold"><?= format_rupiah($cart_summary['store']['delivery_fee']) ?></span>
            </div>
            <hr class="my-2">
            <div class="d-flex justify-content-between fw-bold">
                <span>Total Sementara</span>
                <span class="text-dark fs-6"><?= format_rupiah($cart_summary['subtotal'] + $cart_summary['store']['delivery_fee']) ?></span>
            </div>
        </div>

        <!-- Checkout Action Button -->
        <a href="<?= $baseUrl ?>/checkout" class="btn btn-gojek-green" style="background:#EE2737 !important; color:#FFFFFF !important; border-radius:9999px; font-weight:800; padding:14px 20px; box-shadow:0 4px 14px rgba(238,39,55,0.35); text-decoration:none;">
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

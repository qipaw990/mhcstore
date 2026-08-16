<div class="p-2 border-bottom bg-white d-flex align-items-center justify-content-between" style="padding: 8px 12px !important;">
    <div class="d-flex align-items-center gap-1.5">
        <a href="<?= $baseUrl ?>" class="btn btn-light btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 26px; height: 26px; font-size: 12px;"><i class="bi bi-arrow-left"></i></a>
        <h6 class="fw-bold m-0" style="color: var(--gojek-charcoal); font-size: 12.5px;">Keranjang Belanja</h6>
    </div>
    <?php if (!empty($cart_summary['items'])): ?>
        <button onclick="clearAllCart()" class="btn btn-link text-danger text-decoration-none p-0 fw-bold" style="font-size: 10.5px;">Kosongkan</button>
    <?php endif; ?>
</div>

<div class="p-2" style="padding: 8px 10px !important;">
    <?php if (empty($cart_summary['items'])): ?>
        <div class="text-center py-4">
            <div class="rounded-circle bg-light text-muted d-flex align-items-center justify-content-center mx-auto mb-2" style="width: 48px; height: 48px; font-size: 20px;">
                <i class="bi bi-cart-x text-muted"></i>
            </div>
            <h6 class="fw-bold mb-1" style="color: var(--gojek-charcoal); font-size: 12.5px;">Keranjang Anda Masih Kosong</h6>
            <p class="text-muted" style="font-size: 10px;">Yuk cari kuliner lezat atau belanja kebutuhan Anda sekarang.</p>
            <a href="<?= $baseUrl ?>" class="btn btn-gojek-green px-3 py-1.5 mt-2" style="background:#EE2737 !important; color:#FFFFFF !important; border-radius:9999px; width: auto; display: inline-flex; text-decoration:none; font-size: 11px;">Mulai Belanja</a>
        </div>
    <?php else: ?>
        <!-- Store Name Header -->
        <div class="p-2 bg-white rounded-3 border shadow-xs mb-2 d-flex align-items-center gap-2">
            <div class="rounded-circle text-white d-flex align-items-center justify-content-center" style="width: 24px; height: 24px; background: #EE2737; font-size: 11px;">
                <i class="bi bi-shop"></i>
            </div>
            <div class="min-w-0">
                <div class="fw-bold text-truncate" style="color: var(--gojek-charcoal); font-size: 11.5px;"><?= htmlspecialchars($cart_summary['store']['name']) ?></div>
                <div class="text-muted" style="font-size: 9.5px;">Estimasi pengantaran 20-30 mnt</div>
            </div>
        </div>

        <!-- Cart Items List -->
        <div class="d-flex flex-column gap-1.5 mb-2.5">
            <?php foreach ($cart_summary['items'] as $item): ?>
                <div class="p-2 bg-white rounded-3 border shadow-xs d-flex align-items-center justify-content-between gap-2">
                    <img src="<?= $baseUrl ?>/<?= htmlspecialchars($item['product_image'] ?? 'assets/images/products/default.jpg') ?>" alt="Img" class="rounded-2" style="width: 44px; height: 44px; object-fit: cover;">
                    
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-bold text-dark text-truncate" style="font-size: 11px;"><?= htmlspecialchars($item['product_name']) ?></div>
                        <div class="fw-bold text-danger" style="font-size: 11px;"><?= format_rupiah($item['price']) ?></div>
                    </div>

                    <!-- Quantity Modifiers -->
                    <div class="d-flex align-items-center gap-1.5 bg-light p-0.5 rounded-pill border">
                        <button onclick="updateCartQty(<?= $item['id'] ?>, -1)" class="btn btn-sm btn-white p-0 d-flex align-items-center justify-content-center text-dark" style="width: 22px; height: 22px; border-radius: 50%; font-size: 10px;">
                            <i class="bi bi-dash"></i>
                        </button>
                        <span class="fw-bold px-1" style="font-size: 10px;"><?= $item['quantity'] ?></span>
                        <button onclick="updateCartQty(<?= $item['id'] ?>, 1)" class="btn btn-sm btn-white p-0 d-flex align-items-center justify-content-center text-white" style="width: 22px; height: 22px; background: #EE2737; border-radius: 50%; font-size: 10px;">
                            <i class="bi bi-plus"></i>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Summary Calculation Card -->
        <div class="p-2.5 bg-white rounded-3 border shadow-xs mb-2.5">
            <h6 class="fw-bold mb-2" style="color: var(--gojek-charcoal); font-size: 11px;">Ringkasan Pembayaran</h6>
            <div class="d-flex justify-content-between text-muted mb-1" style="font-size: 10px;">
                <span>Subtotal (<?= $cart_summary['count'] ?> item)</span>
                <span class="text-dark fw-bold"><?= format_rupiah($cart_summary['subtotal']) ?></span>
            </div>
            <div class="d-flex justify-content-between text-muted mb-1" style="font-size: 10px;">
                <span>Estimasi Ongkir</span>
                <span class="text-dark fw-bold"><?= format_rupiah($cart_summary['store']['delivery_fee']) ?></span>
            </div>
            <hr class="my-1.5">
            <div class="d-flex justify-content-between fw-bold" style="font-size: 11.5px;">
                <span>Total Sementara</span>
                <span class="text-danger"><?= format_rupiah($cart_summary['subtotal'] + $cart_summary['store']['delivery_fee']) ?></span>
            </div>
        </div>

        <!-- Checkout Action Button -->
        <a href="<?= $baseUrl ?>/checkout" class="btn btn-gojek-green w-100" style="background:#EE2737 !important; color:#FFFFFF !important; border-radius:9999px; font-weight:700; padding:8px 14px; font-size:11.5px; box-shadow:0 2px 8px rgba(238,39,55,0.3); text-decoration:none; display:flex; align-items:center; justify-content:center; gap:6px;">
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

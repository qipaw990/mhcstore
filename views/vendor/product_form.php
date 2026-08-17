<!-- Product Form Top Bar -->
<div class="d-flex align-items-center gap-2.5 mb-3">
    <a href="<?= $baseUrl ?>/vendor/products" class="btn btn-light btn-sm rounded-circle p-0 d-flex align-items-center justify-content-center border shadow-2xs" style="width: 34px; height: 34px; border-color: #E2E8F0 !important;">
        <i class="bi bi-arrow-left text-dark fs-6"></i>
    </a>
    <div>
        <h6 class="fw-bold m-0 text-dark" style="font-size: 14.5px;">
            <?= !empty($product) ? 'Edit Menu' : 'Tambah Menu Baru' ?>
        </h6>
        <span class="text-muted" style="font-size: 10.5px;">Lengkapi detail menu etalase toko Anda</span>
    </div>
</div>

<div class="vnd-card mb-4">
    <form action="<?= $baseUrl ?>/vendor/products/save" method="POST" enctype="multipart/form-data">
        <?php if (!empty($product)): ?>
            <input type="hidden" name="id" value="<?= $product['id'] ?>">
            <input type="hidden" name="existing_image" value="<?= $product['image'] ?>">
        <?php endif; ?>

        <!-- Image Upload Box -->
        <div class="mb-3">
            <label class="form-label fw-bold text-dark mb-1.5" for="inpImage" style="font-size: 11.5px;">Foto Menu / Produk</label>
            <div class="d-flex align-items-center gap-3 p-3 bg-light rounded-3 border" style="border-color: #E2E8F0 !important;">
                <img id="product-preview" src="<?= $baseUrl ?>/<?= htmlspecialchars($product['image'] ?? 'assets/images/products/default.jpg') ?>" alt="Preview" class="rounded-3 border shadow-2xs flex-shrink-0" style="width: 64px; height: 64px; object-fit: cover;">
                <div class="flex-grow-1 min-w-0">
                    <input type="file" name="image" id="inpImage" class="form-control form-control-sm bg-white" style="font-size: 11px; border-radius: 8px; border-color: #CBD5E1;" accept="image/*" onchange="previewProductImg(this)">
                    <small class="text-muted d-block mt-1" style="font-size: 10px;">Foto makanan menarik meningkatkan minat beli.</small>
                </div>
            </div>
        </div>

        <!-- Product Name -->
        <div class="mb-3">
            <label class="form-label fw-bold text-dark mb-1" for="inpName" style="font-size: 11.5px;">Nama Menu / Produk <span class="text-danger">*</span></label>
            <input type="text" name="name" id="inpName" class="form-control form-control-sm vnd-input" value="<?= htmlspecialchars($product['name'] ?? '') ?>" required placeholder="Contoh: Ayam Geprek Sambal Korek">
        </div>

        <!-- Category & Unit -->
        <div class="row g-2.5 mb-3">
            <div class="col-7">
                <label class="form-label fw-bold text-dark mb-1" for="inpCategory" style="font-size: 11.5px;">Kategori</label>
                <select name="category_id" id="inpCategory" class="form-select form-select-sm vnd-input">
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= (!empty($product) && $product['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-5">
                <label class="form-label fw-bold text-dark mb-1" for="inpUnit" style="font-size: 11.5px;">Satuan</label>
                <input type="text" name="unit" id="inpUnit" class="form-control form-control-sm vnd-input" value="<?= htmlspecialchars($product['unit'] ?? 'porsi') ?>" placeholder="porsi/pcs/box">
            </div>
        </div>

        <!-- Price & Discount -->
        <div class="row g-2.5 mb-2.5">
            <div class="col-7">
                <label class="form-label fw-bold text-dark mb-1" for="inpPrice" style="font-size: 11.5px;">Harga Normal (Rp) <span class="text-danger">*</span></label>
                <input type="number" id="inpPrice" name="price" class="form-control form-control-sm vnd-input" value="<?= (int)($product['price'] ?? 15000) ?>" required oninput="calculateFinalPrice()">
            </div>
            <div class="col-5">
                <label class="form-label fw-bold text-dark mb-1" for="inpDiscount" style="font-size: 11.5px;">Diskon Promo (%)</label>
                <input type="number" id="inpDiscount" name="discount" class="form-control form-control-sm vnd-input" value="<?= (int)($product['discount'] ?? 0) ?>" min="0" max="100" oninput="calculateFinalPrice()">
            </div>
        </div>

        <!-- Calculated Final Price Preview Pill -->
        <div class="p-3 mb-3 d-flex align-items-center justify-content-between" style="background: #FEF2F2; border: 1px solid #FECDD3; border-radius: 12px;">
            <div class="d-flex align-items-center gap-1.5">
                <i class="bi bi-tag-fill text-danger"></i>
                <span class="fw-bold text-dark" style="font-size: 11.5px;">Harga Tampil ke Pembeli:</span>
            </div>
            <span id="finalPriceDisplay" class="fw-extrabold text-danger" style="font-size: 15px; letter-spacing: -0.3px;">
                <?= format_rupiah($product['final_price'] ?? 15000) ?>
            </span>
        </div>

        <!-- Stock -->
        <div class="mb-3">
            <label class="form-label fw-bold text-dark mb-1" for="inpStock" style="font-size: 11.5px;">Jumlah Stok Awal</label>
            <input type="number" name="stock" id="inpStock" class="form-control form-control-sm vnd-input" value="<?= (int)($product['stock'] ?? 100) ?>" min="0">
        </div>

        <!-- Description -->
        <div class="mb-3">
            <label class="form-label fw-bold text-dark mb-1" for="inpDescription" style="font-size: 11.5px;">Deskripsi Singkat Menu</label>
            <textarea name="description" id="inpDescription" class="form-control form-control-sm vnd-input" rows="3" placeholder="Jelaskan rasa, tingkat kepedasan, atau bahan menu..."><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
        </div>

        <!-- Recommended Toggle -->
        <div class="p-3 bg-light rounded-3 mb-4 border" style="border-color: #E2E8F0 !important;">
            <div class="form-check form-switch d-flex align-items-center justify-content-between p-0 m-0">
                <label class="form-check-label fw-bold text-dark" style="font-size: 11.5px;" for="recSwitch">
                    <i class="bi bi-star-fill text-warning me-1"></i> Jadikan Menu Rekomendasi
                </label>
                <input class="form-check-input ms-0" type="checkbox" role="switch" name="is_recommended" value="1" id="recSwitch" <?= (!empty($product['is_recommended'])) ? 'checked' : '' ?> style="width: 44px; height: 22px; cursor: pointer;">
            </div>
        </div>

        <!-- Save Button -->
        <button type="submit" class="vnd-action-btn red w-100 py-2.5" style="font-size: 13px; border-radius: 12px;">
            <i class="bi bi-check2-circle me-1" style="font-size: 16px;"></i> Simpan Menu
        </button>
    </form>
</div>

<script>
function previewProductImg(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('product-preview').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function calculateFinalPrice() {
    const price = parseFloat(document.getElementById('inpPrice').value) || 0;
    const discount = parseFloat(document.getElementById('inpDiscount').value) || 0;
    const finalPrice = Math.max(0, price - (price * (discount / 100)));
    
    document.getElementById('finalPriceDisplay').textContent = 'Rp ' + Math.round(finalPrice).toLocaleString('id-ID');
}
</script>

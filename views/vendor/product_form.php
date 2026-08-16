<!-- Product Form Top Bar -->
<div class="d-flex align-items-center gap-2 mb-3">
    <a href="<?= $baseUrl ?>/vendor/products" class="btn btn-light btn-sm rounded-circle p-0 d-flex align-items-center justify-content-center shadow-xs" style="width: 34px; height: 34px;">
        <i class="bi bi-arrow-left text-dark fs-6"></i>
    </a>
    <h6 class="fw-bold m-0 text-dark" style="font-size: 15px;">
        <?= !empty($product) ? 'Edit Menu' : 'Tambah Menu Baru' ?>
    </h6>
</div>

<div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-4">
    <form action="<?= $baseUrl ?>/vendor/products/save" method="POST" enctype="multipart/form-data">
        <?php if (!empty($product)): ?>
            <input type="hidden" name="id" value="<?= $product['id'] ?>">
            <input type="hidden" name="existing_image" value="<?= $product['image'] ?>">
        <?php endif; ?>

        <!-- Image Upload Box -->
        <div class="mb-3">
            <label class="form-label small fw-bold text-dark" for="inpImage">Foto Menu / Produk</label>
            <div class="d-flex align-items-center gap-3 p-3 bg-light rounded-4 border">
                <img id="product-preview" src="<?= $baseUrl ?>/<?= htmlspecialchars($product['image'] ?? 'assets/images/products/default.jpg') ?>" alt="Preview" class="rounded-3 border shadow-xs" style="width: 70px; height: 70px; object-fit: cover;">
                <div class="flex-grow-1">
                    <input type="file" name="image" id="inpImage" class="form-control form-control-sm rounded-3" accept="image/*" onchange="previewProductImg(this)">
                    <small class="text-muted" style="font-size: 10.5px;">Foto makanan menarik meningkatkan minat beli.</small>
                </div>
            </div>
        </div>

        <!-- Product Name -->
        <div class="mb-3">
            <label class="form-label small fw-bold text-dark" for="inpName">Nama Menu / Produk <span class="text-danger">*</span></label>
            <input type="text" name="name" id="inpName" class="form-control rounded-3" value="<?= htmlspecialchars($product['name'] ?? '') ?>" required placeholder="Contoh: Ayam Geprek Sambal Korek">
        </div>

        <!-- Category & Unit -->
        <div class="row g-2 mb-3">
            <div class="col-7">
                <label class="form-label small fw-bold text-dark" for="inpCategory">Kategori</label>
                <select name="category_id" id="inpCategory" class="form-select form-select-sm rounded-3">
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= (!empty($product) && $product['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-5">
                <label class="form-label small fw-bold text-dark" for="inpUnit">Satuan</label>
                <input type="text" name="unit" id="inpUnit" class="form-control form-control-sm rounded-3" value="<?= htmlspecialchars($product['unit'] ?? 'porsi') ?>" placeholder="porsi/pcs/box">
            </div>
        </div>

        <!-- Price & Discount -->
        <div class="row g-2 mb-3">
            <div class="col-7">
                <label class="form-label small fw-bold text-dark" for="inpPrice">Harga Normal (Rp) <span class="text-danger">*</span></label>
                <input type="number" id="inpPrice" name="price" class="form-control rounded-3" value="<?= (int)($product['price'] ?? 15000) ?>" required oninput="calculateFinalPrice()">
            </div>
            <div class="col-5">
                <label class="form-label small fw-bold text-dark" for="inpDiscount">Diskon Promo (%)</label>
                <input type="number" id="inpDiscount" name="discount" class="form-control rounded-3" value="<?= (int)($product['discount'] ?? 0) ?>" min="0" max="100" oninput="calculateFinalPrice()">
            </div>
        </div>

        <!-- Calculated Final Price Preview Pill -->
        <div class="p-2.5 bg-danger-subtle rounded-3 d-flex align-items-center justify-content-between mb-3 border border-danger-subtle">
            <span class="small fw-bold text-danger">Harga Tampil ke Pembeli:</span>
            <span id="finalPriceDisplay" class="fw-extrabold text-danger" style="font-size: 14px;">
                <?= format_rupiah($product['final_price'] ?? 15000) ?>
            </span>
        </div>

        <!-- Stock -->
        <div class="mb-3">
            <label class="form-label small fw-bold text-dark" for="inpStock">Jumlah Stok Awal</label>
            <input type="number" name="stock" id="inpStock" class="form-control rounded-3" value="<?= (int)($product['stock'] ?? 100) ?>" min="0">
        </div>

        <!-- Description -->
        <div class="mb-3">
            <label class="form-label small fw-bold text-dark" for="inpDescription">Deskripsi Singkat Menu</label>
            <textarea name="description" id="inpDescription" class="form-control rounded-3" rows="3" placeholder="Jelaskan rasa, tingkat kepedasan, atau bahan menu..."><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
        </div>

        <!-- Recommended Toggle -->
        <div class="p-3 bg-light rounded-3 mb-4 border">
            <div class="form-check form-switch d-flex align-items-center justify-content-between p-0 m-0">
                <label class="form-check-label small fw-bold text-dark" for="recSwitch">
                    <i class="bi bi-star-fill text-warning me-1"></i> Jadikan Menu Rekomendasi
                </label>
                <input class="form-check-input ms-0" type="checkbox" role="switch" name="is_recommended" value="1" id="recSwitch" <?= (!empty($product['is_recommended'])) ? 'checked' : '' ?> style="width: 44px; height: 22px;">
            </div>
        </div>

        <!-- Save Button -->
        <button type="submit" class="btn btn-danger w-100 py-2.5 rounded-pill fw-bold shadow-sm" style="background:#EE2737; font-size: 14px; border:none;">
            <i class="bi bi-check2-circle me-1"></i> Simpan Menu
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

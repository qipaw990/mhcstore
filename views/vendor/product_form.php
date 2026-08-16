<!-- Product Form Top Bar -->
<div class="d-flex align-items-center gap-2 mb-3">
    <a href="<?= $baseUrl ?>/vendor/products" class="btn btn-light btn-sm rounded-circle p-0 d-flex align-items-center justify-content-center shadow-2xs" style="width: 32px; height: 32px; border-color: #E2E8F0 !important;">
        <i class="bi bi-arrow-left text-dark fs-6"></i>
    </a>
    <h6 class="fw-bold m-0 text-dark" style="font-size: 14px;">
        <?= !empty($product) ? 'Edit Menu' : 'Tambah Menu Baru' ?>
    </h6>
</div>

<div class="p-3.5 bg-white border shadow-2xs mb-4" style="border-radius: 16px; border-color: #E2E8F0 !important;">
    <form action="<?= $baseUrl ?>/vendor/products/save" method="POST" enctype="multipart/form-data">
        <?php if (!empty($product)): ?>
            <input type="hidden" name="id" value="<?= $product['id'] ?>">
            <input type="hidden" name="existing_image" value="<?= $product['image'] ?>">
        <?php endif; ?>

        <!-- Image Upload Box -->
        <div class="mb-3">
            <label class="form-label small fw-bold text-dark" for="inpImage" style="font-size: 11px;">Foto Menu / Produk</label>
            <div class="d-flex align-items-center gap-3 p-2.5 bg-light rounded-3 border" style="border-radius: 12px !important; border-color: #E2E8F0 !important;">
                <img id="product-preview" src="<?= $baseUrl ?>/<?= htmlspecialchars($product['image'] ?? 'assets/images/products/default.jpg') ?>" alt="Preview" class="rounded-3 border shadow-2xs" style="width: 60px; height: 60px; object-fit: cover;">
                <div class="flex-grow-1">
                    <input type="file" name="image" id="inpImage" class="form-control form-control-sm rounded-3" style="font-size: 11px; border-radius: 8px;" accept="image/*" onchange="previewProductImg(this)">
                    <small class="text-muted" style="font-size: 10px;">Foto makanan menarik meningkatkan minat beli.</small>
                </div>
            </div>
        </div>

        <!-- Product Name -->
        <div class="mb-2.5">
            <label class="form-label fw-bold text-dark mb-1" for="inpName" style="font-size: 11px;">Nama Menu / Produk <span class="text-danger">*</span></label>
            <input type="text" name="name" id="inpName" class="form-control form-control-sm bg-light" style="font-size: 11.5px; border-radius: 10px; border-color: #E2E8F0;" value="<?= htmlspecialchars($product['name'] ?? '') ?>" required placeholder="Contoh: Ayam Geprek Sambal Korek">
        </div>

        <!-- Category & Unit -->
        <div class="row g-2 mb-2.5">
            <div class="col-7">
                <label class="form-label fw-bold text-dark mb-1" for="inpCategory" style="font-size: 11px;">Kategori</label>
                <select name="category_id" id="inpCategory" class="form-select form-select-sm bg-light" style="font-size: 11.5px; border-radius: 10px; border-color: #E2E8F0;">
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= (!empty($product) && $product['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-5">
                <label class="form-label fw-bold text-dark mb-1" for="inpUnit" style="font-size: 11px;">Satuan</label>
                <input type="text" name="unit" id="inpUnit" class="form-control form-control-sm bg-light" style="font-size: 11.5px; border-radius: 10px; border-color: #E2E8F0;" value="<?= htmlspecialchars($product['unit'] ?? 'porsi') ?>" placeholder="porsi/pcs/box">
            </div>
        </div>

        <!-- Price & Discount -->
        <div class="row g-2 mb-2.5">
            <div class="col-7">
                <label class="form-label fw-bold text-dark mb-1" for="inpPrice" style="font-size: 11px;">Harga Normal (Rp) <span class="text-danger">*</span></label>
                <input type="number" id="inpPrice" name="price" class="form-control form-control-sm bg-light" style="font-size: 11.5px; border-radius: 10px; border-color: #E2E8F0;" value="<?= (int)($product['price'] ?? 15000) ?>" required oninput="calculateFinalPrice()">
            </div>
            <div class="col-5">
                <label class="form-label fw-bold text-dark mb-1" for="inpDiscount" style="font-size: 11px;">Diskon Promo (%)</label>
                <input type="number" id="inpDiscount" name="discount" class="form-control form-control-sm bg-light" style="font-size: 11.5px; border-radius: 10px; border-color: #E2E8F0;" value="<?= (int)($product['discount'] ?? 0) ?>" min="0" max="100" oninput="calculateFinalPrice()">
            </div>
        </div>

        <!-- Calculated Final Price Preview Pill -->
        <div class="p-2.5 bg-danger-subtle rounded-3 d-flex align-items-center justify-content-between mb-3 border border-danger-subtle">
            <span class="fw-bold text-danger" style="font-size: 11px;">Harga Tampil ke Pembeli:</span>
            <span id="finalPriceDisplay" class="fw-extrabold text-danger" style="font-size: 13.5px;">
                <?= format_rupiah($product['final_price'] ?? 15000) ?>
            </span>
        </div>

        <!-- Stock -->
        <div class="mb-2.5">
            <label class="form-label fw-bold text-dark mb-1" for="inpStock" style="font-size: 11px;">Jumlah Stok Awal</label>
            <input type="number" name="stock" id="inpStock" class="form-control form-control-sm bg-light" style="font-size: 11.5px; border-radius: 10px; border-color: #E2E8F0;" value="<?= (int)($product['stock'] ?? 100) ?>" min="0">
        </div>

        <!-- Description -->
        <div class="mb-3">
            <label class="form-label fw-bold text-dark mb-1" for="inpDescription" style="font-size: 11px;">Deskripsi Singkat Menu</label>
            <textarea name="description" id="inpDescription" class="form-control form-control-sm bg-light" style="font-size: 11.5px; border-radius: 10px; border-color: #E2E8F0;" rows="3" placeholder="Jelaskan rasa, tingkat kepedasan, atau bahan menu..."><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
        </div>

        <!-- Recommended Toggle -->
        <div class="p-2.5 bg-light rounded-3 mb-4 border" style="border-color: #E2E8F0 !important;">
            <div class="form-check form-switch d-flex align-items-center justify-content-between p-0 m-0">
                <label class="form-check-label fw-bold text-dark" style="font-size: 11px;" for="recSwitch">
                    <i class="bi bi-star-fill text-warning me-1"></i> Jadikan Menu Rekomendasi
                </label>
                <input class="form-check-input ms-0" type="checkbox" role="switch" name="is_recommended" value="1" id="recSwitch" <?= (!empty($product['is_recommended'])) ? 'checked' : '' ?> style="width: 44px; height: 22px;">
            </div>
        </div>

        <!-- Save Button -->
        <button type="submit" class="btn text-white w-100 py-2.5 fw-bold shadow-2xs" style="background: linear-gradient(135deg, #EE2737, #C61524); border-radius: 9999px; font-size: 12.5px; border:none;">
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

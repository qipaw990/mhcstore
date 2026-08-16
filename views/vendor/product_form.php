<div class="card border-0 shadow-sm rounded-4 max-w-700">
    <div class="card-header bg-white border-0 py-3">
        <h6 class="fw-bold m-0"><?= !empty($product) ? 'Edit Menu: ' . htmlspecialchars($product['name']) : 'Tambah Menu Baru' ?></h6>
    </div>
    <div class="card-body p-4">
        <form action="<?= $baseUrl ?>/vendor/products/save" method="POST" enctype="multipart/form-data">
            <?php if (!empty($product)): ?>
                <input type="hidden" name="id" value="<?= $product['id'] ?>">
                <input type="hidden" name="existing_image" value="<?= $product['image'] ?>">
            <?php endif; ?>

            <div class="mb-3">
                <label class="form-label small fw-bold">Nama Menu / Produk</label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($product['name'] ?? '') ?>" required placeholder="Contoh: Paket Ayam Geprek Keju Lumer">
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Kategori</label>
                    <select name="category_id" class="form-select">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= (!empty($product) && $product['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Satuan</label>
                    <input type="text" name="unit" class="form-control" value="<?= htmlspecialchars($product['unit'] ?? 'porsi') ?>" placeholder="porsi / pcs / kg / box">
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Harga Normal (Rp)</label>
                    <input type="number" name="price" class="form-control" value="<?= (int)($product['price'] ?? 15000) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Diskon Promo (%)</label>
                    <input type="number" name="discount" class="form-control" value="<?= (int)($product['discount'] ?? 0) ?>" min="0" max="100">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-bold">Deskripsi Menu</label>
                <textarea name="description" class="form-control" rows="3" placeholder="Jelaskan rasa, kepedasan, atau komposisi menu..."><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Jumlah Stok Tersedia</label>
                    <input type="number" name="stock" class="form-control" value="<?= (int)($product['stock'] ?? 100) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Foto Produk</label>
                    <input type="file" name="image" class="form-control" accept="image/*">
                </div>
            </div>

            <div class="d-flex gap-4 mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_recommended" value="1" id="recCheck" <?= (!empty($product['is_recommended'])) ? 'checked' : '' ?>>
                    <label class="form-check-label small fw-bold" for="recCheck">Jadikan Menu Rekomendasi Utama</label>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4 fw-bold">Simpan Produk</button>
                <a href="<?= $baseUrl ?>/vendor/products" class="btn btn-light px-4">Batal</a>
            </div>
        </form>
    </div>
</div>

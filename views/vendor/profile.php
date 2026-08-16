<div class="row g-4">
    <div class="col-lg-4">
        <!-- Store & Owner Summary Card -->
        <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
            <div class="position-relative d-inline-block mx-auto mb-3">
                <img src="<?= $baseUrl ?>/<?= htmlspecialchars($store['logo'] ?? 'assets/images/stores/default.jpg') ?>" alt="Store Logo" class="rounded-circle border border-3 border-danger shadow-sm" style="width: 100px; height: 100px; object-fit: cover;">
                <span class="position-absolute bottom-0 end-0 bg-success border border-2 border-white rounded-circle p-2" title="Toko Buka"></span>
            </div>
            <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($store['name'] ?? 'Nama Toko') ?></h5>
            <div class="badge bg-success-subtle text-success rounded-pill px-3 py-1 mb-3 fw-bold" style="font-size: 11px;">
                <i class="bi bi-shop me-1"></i> MITRA RESMI CICALENGKAGO
            </div>
            
            <hr class="my-3 opacity-25">

            <div class="text-start small">
                <div class="mb-2 text-muted">
                    <i class="bi bi-person-fill me-2 text-danger"></i> <strong>Pemilik:</strong> <?= htmlspecialchars($user['name'] ?? '-') ?>
                </div>
                <div class="mb-2 text-muted">
                    <i class="bi bi-envelope me-2 text-danger"></i> <strong>Email:</strong> <?= htmlspecialchars($user['email'] ?? '-') ?>
                </div>
                <div class="mb-2 text-muted">
                    <i class="bi bi-phone me-2 text-danger"></i> <strong>No. HP Pemilik:</strong> <?= htmlspecialchars($user['phone'] ?? '-') ?>
                </div>
                <div class="mb-0 text-muted">
                    <i class="bi bi-geo-alt-fill me-2 text-danger"></i> <strong>Alamat Toko:</strong> <?= htmlspecialchars($store['address'] ?? '-') ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <!-- Edit Form Card -->
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-4">
                <h6 class="fw-bold text-dark m-0"><i class="bi bi-sliders text-danger me-2"></i> Pengaturan Profil Pemilik & Informasi Toko</h6>
                <span class="badge bg-light text-muted border px-2.5 py-1 rounded-pill" style="font-size: 11px;">Mitra Merchant</span>
            </div>

            <?php if (!empty($_SESSION['error'])): ?>
                <div class="alert alert-danger rounded-3 border-0 small mb-3">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= htmlspecialchars($_SESSION['error']) ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <?php if (!empty($_SESSION['success'])): ?>
                <div class="alert alert-success rounded-3 border-0 small mb-3">
                    <i class="bi bi-check-circle-fill me-1"></i> <?= htmlspecialchars($_SESSION['success']) ?>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <form action="<?= $baseUrl ?>/vendor/profile/update" method="POST" enctype="multipart/form-data">
                <div class="row g-3">
                    <div class="col-12">
                        <h6 class="fw-bold text-dark mb-2 pb-1 border-bottom" style="font-size: 13px;">
                            <i class="bi bi-person-circle text-primary me-1"></i> 1. Informasi Akun Pemilik Toko
                        </h6>
                    </div>

                    <!-- Foto Profil Pemilik -->
                    <div class="col-12">
                        <label class="form-label small fw-bold text-dark d-block">Foto Profil Pemilik</label>
                        <div class="d-flex align-items-center gap-3 p-3 bg-light rounded-4 border">
                            <img id="vendor-avatar-preview" src="<?= $baseUrl ?>/<?= htmlspecialchars($user['avatar'] ?? 'assets/images/users/default.png') ?>" alt="Preview Pemilik" class="rounded-circle border border-2 border-danger shadow-xs" style="width: 65px; height: 65px; object-fit: cover;">
                            <div class="flex-grow-1">
                                <input type="file" name="avatar" class="form-control rounded-3 form-control-sm" accept="image/jpeg,image/png,image/webp,image/gif" onchange="previewImage(this, 'vendor-avatar-preview')">
                                <small class="text-muted" style="font-size: 11px;">Format JPG, PNG, WEBP. Maks 2MB.</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-dark">Nama Pemilik Toko</label>
                        <input type="text" name="name" class="form-control rounded-3" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-dark">No HP / WA Pemilik</label>
                        <input type="text" name="phone" class="form-control rounded-3" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-bold text-dark">Alamat Email (Gmail)</label>
                        <input type="email" name="email" class="form-control rounded-3" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                        <div class="alert alert-warning border-0 rounded-3 p-2.5 mt-2 mb-0 d-flex align-items-start gap-2" style="font-size: 11px;">
                            <i class="bi bi-shield-exclamation text-warning fs-6 mt-0.5"></i>
                            <div>
                                <strong>Verifikasi OTP Email:</strong> Mengubah alamat email membutuhkan <strong>kode OTP 6-digit</strong> yang dikirim ke email baru sebelum diterapkan.
                            </div>
                        </div>
                    </div>

                    <div class="col-12 mt-4">
                        <h6 class="fw-bold text-dark mb-2 pb-1 border-bottom" style="font-size: 13px;">
                            <i class="bi bi-shop text-success me-1"></i> 2. Informasi Toko / Resto
                        </h6>
                    </div>

                    <!-- Logo Toko -->
                    <div class="col-12">
                        <label class="form-label small fw-bold text-dark d-block">Logo / Foto Profil Toko</label>
                        <div class="d-flex align-items-center gap-3 p-3 bg-light rounded-4 border">
                            <img id="store-logo-preview" src="<?= $baseUrl ?>/<?= htmlspecialchars($store['logo'] ?? 'assets/images/stores/default.jpg') ?>" alt="Preview Toko" class="rounded-circle border border-2 border-success shadow-xs" style="width: 65px; height: 65px; object-fit: cover;">
                            <div class="flex-grow-1">
                                <input type="file" name="store_logo" class="form-control rounded-3 form-control-sm" accept="image/jpeg,image/png,image/webp,image/gif" onchange="previewImage(this, 'store-logo-preview')">
                                <small class="text-muted" style="font-size: 11px;">Logo ini akan tampil pada etalase toko pelanggan. Maks 2MB.</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-dark">Nama Toko / Resto</label>
                        <input type="text" name="store_name" class="form-control rounded-3" value="<?= htmlspecialchars($store['name'] ?? '') ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-dark">Telepon Toko</label>
                        <input type="text" name="store_phone" class="form-control rounded-3" value="<?= htmlspecialchars($store['phone'] ?? '') ?>">
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-bold text-dark">Alamat Lengkap Toko</label>
                        <textarea name="store_address" class="form-control rounded-3" rows="2" required><?= htmlspecialchars($store['address'] ?? '') ?></textarea>
                    </div>

                    <!-- Section Ubah Kata Sandi -->
                    <div class="col-12 mt-4 pt-3 border-top">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h6 class="fw-bold text-dark m-0" style="font-size: 13px;">
                                <i class="bi bi-key-fill text-danger me-1"></i> 3. Ubah Kata Sandi Akun Pemilik (Opsional)
                            </h6>
                            <span class="text-muted small" style="font-size: 11px;">Kosongkan jika tidak ingin mengubah</span>
                        </div>

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label small fw-bold text-muted">Kata Sandi Saat Ini</label>
                                <div class="input-group">
                                    <input type="password" name="current_password" id="vendor_current_password" class="form-control rounded-start-3" placeholder="Masukkan kata sandi lama">
                                    <button class="btn btn-outline-secondary border rounded-end-3" type="button" onclick="togglePasswordVisibility('vendor_current_password', this)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">Kata Sandi Baru (Min. 6 Karakter)</label>
                                <div class="input-group">
                                    <input type="password" name="new_password" id="vendor_new_password" class="form-control rounded-start-3" placeholder="Kata sandi baru">
                                    <button class="btn btn-outline-secondary border rounded-end-3" type="button" onclick="togglePasswordVisibility('vendor_new_password', this)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">Konfirmasi Kata Sandi Baru</label>
                                <div class="input-group">
                                    <input type="password" name="confirm_password" id="vendor_confirm_password" class="form-control rounded-start-3" placeholder="Ulangi kata sandi baru">
                                    <button class="btn btn-outline-secondary border rounded-end-3" type="button" onclick="togglePasswordVisibility('vendor_confirm_password', this)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 mt-4 text-end">
                        <button type="submit" class="btn text-white rounded-pill px-4 py-2 fw-bold shadow-sm" style="background:#EE2737;">
                            <i class="bi bi-floppy2-fill me-1"></i> Simpan Perubahan Profil & Toko
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function previewImage(input, targetId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById(targetId).src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function togglePasswordVisibility(fieldId, btn) {
    const field = document.getElementById(fieldId);
    const icon = btn.querySelector('i');
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
}
</script>

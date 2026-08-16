<div class="p-3 border-bottom bg-white d-flex align-items-center justify-content-between">
    <h6 class="fw-bold m-0 text-dark"><i class="bi bi-person-badge-fill me-2 text-danger"></i> Profil Saya - Mitra Driver</h6>
    <span class="badge text-dark px-2.5 py-1 rounded-pill" style="background: #F7A800; font-size: 10px; font-weight: 800;">
        <i class="bi bi-star-fill me-1"></i> Driver CicalengkaGO
    </span>
</div>

<div class="p-3">
    <!-- Driver Info Header Card -->
    <div class="p-3 bg-white rounded-4 border shadow-sm mb-3 text-center">
        <div class="position-relative d-inline-block mx-auto mb-2">
            <img src="<?= $baseUrl ?>/<?= htmlspecialchars($user['avatar'] ?? 'assets/images/users/driver.png') ?>" alt="Driver" class="rounded-circle border border-3 border-danger shadow-sm" style="width: 76px; height: 76px; object-fit: cover;">
            <span class="position-absolute bottom-0 end-0 bg-success border border-2 border-white rounded-circle p-1.5" title="Status Online"></span>
        </div>
        <h6 class="fw-bold text-dark mb-0"><?= htmlspecialchars($user['name'] ?? 'Mitra Driver') ?></h6>
        <div class="text-muted small" style="font-size: 12px;"><?= htmlspecialchars($user['phone'] ?? '-') ?></div>
        <div class="badge bg-danger-subtle text-danger rounded-pill px-3 py-1 mt-2 fw-bold" style="font-size: 11px;">
            <i class="bi bi-bicycle me-1"></i> <?= htmlspecialchars($driver['vehicle_type'] ?? 'Motor') ?> (<?= htmlspecialchars($driver['vehicle_number'] ?? '-') ?>)
        </div>
    </div>

    <!-- Edit Profile Form Card -->
    <div class="p-3 bg-white rounded-4 border shadow-sm mb-3">
        <h6 class="fw-bold text-dark mb-3 border-bottom pb-2" style="font-size: 13px;">
            <i class="bi bi-pencil-square text-danger me-1"></i> Form Edit Profil Driver
        </h6>

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

        <form action="<?= $baseUrl ?>/delivery/profile/update" method="POST" enctype="multipart/form-data">
            <!-- Foto Profil Driver -->
            <div class="mb-3">
                <label class="form-label small fw-bold text-dark d-block">Foto Profil Driver</label>
                <div class="d-flex align-items-center gap-3 p-2.5 bg-light rounded-4 border">
                    <img id="driver-avatar-preview" src="<?= $baseUrl ?>/<?= htmlspecialchars($user['avatar'] ?? 'assets/images/users/driver.png') ?>" alt="Preview Driver" class="rounded-circle border border-2 border-danger shadow-xs" style="width: 60px; height: 60px; object-fit: cover;">
                    <div class="flex-grow-1">
                        <input type="file" name="avatar" class="form-control rounded-3 form-control-sm" accept="image/jpeg,image/png,image/webp,image/gif" onchange="previewImage(this, 'driver-avatar-preview')">
                        <small class="text-muted" style="font-size: 10px;">Format: JPG, PNG, WEBP. Maks 2MB.</small>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-bold text-dark">Nama Lengkap Driver</label>
                <input type="text" name="name" class="form-control rounded-3" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-bold text-dark">Alamat Email (Gmail)</label>
                <input type="email" name="email" class="form-control rounded-3" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                <div class="alert alert-warning border-0 rounded-3 p-2 mt-2 mb-0 d-flex align-items-start gap-2" style="font-size: 11px;">
                    <i class="bi bi-shield-exclamation text-warning fs-6 mt-0.5"></i>
                    <div>
                        Mengubah email akan mengirimkan <strong>kode OTP verifikasi 6-digit</strong> ke email baru Anda sebelum diperbarui.
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-bold text-dark">Nomor Telepon / WA</label>
                <input type="text" name="phone" class="form-control rounded-3" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-bold text-dark">Tipe Kendaraan Operasional</label>
                <input type="text" name="vehicle_type" class="form-control rounded-3" value="<?= htmlspecialchars($driver['vehicle_type'] ?? 'Motor Honda Beat') ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-bold text-dark">Nomor Polisi Kendaraan (Plat Nomor)</label>
                <input type="text" name="vehicle_number" class="form-control rounded-3" value="<?= htmlspecialchars($driver['vehicle_number'] ?? 'D 1234 CCG') ?>" required>
            </div>

            <div class="d-grid mt-4">
                <button type="submit" class="btn text-white rounded-pill py-2.5 fw-bold" style="background:#EE2737;">
                    <i class="bi bi-floppy-fill me-1"></i> Simpan Perubahan Profil
                </button>
            </div>
        </form>
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
</script>

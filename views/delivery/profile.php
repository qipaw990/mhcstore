<div class="p-3 border-bottom bg-white d-flex align-items-center justify-content-between">
    <h6 class="fw-bold m-0 text-dark" style="font-size: 13.5px;"><i class="bi bi-person-badge-fill me-2 text-danger"></i> Profil Saya - Mitra Driver</h6>
    <span class="badge text-dark px-2.5 py-1 rounded-pill" style="background: #F7A800; font-size: 10px; font-weight: 800;">
        <i class="bi bi-star-fill me-1"></i> Driver CicalengkaGO
    </span>
</div>

<div class="p-3 driver-page-container">
    <!-- Driver Info Header Card -->
    <div class="ccg-card mb-3 text-center">
        <div class="position-relative d-inline-block mx-auto mb-2">
            <img src="<?= $baseUrl ?>/<?= htmlspecialchars($user['avatar'] ?? 'assets/images/users/driver.png') ?>" alt="Driver" class="rounded-circle border border-3 border-danger shadow-xs" style="width: 76px; height: 76px; object-fit: cover;">
            <span class="position-absolute bottom-0 end-0 bg-success border border-2 border-white rounded-circle p-1.5" title="Status Online"></span>
        </div>
        <h6 class="fw-bold text-dark mb-0" style="font-size: 14px;"><?= htmlspecialchars($user['name'] ?? 'Mitra Driver') ?></h6>
        <div class="text-muted" style="font-size: 11px;"><?= htmlspecialchars($user['phone'] ?? '-') ?></div>
        <div class="badge bg-danger-subtle text-danger rounded-pill px-3 py-1 mt-2 fw-bold" style="font-size: 11px;">
            <i class="bi bi-bicycle me-1"></i> <?= htmlspecialchars($driver['vehicle_type'] ?? 'Motor') ?> (<?= htmlspecialchars($driver['vehicle_number'] ?? '-') ?>)
        </div>
    <!-- Driver Performance Rating Card -->
    <div class="bg-white rounded-4 border p-3.5 mb-3 shadow-sm" style="border-color: #E2E8F0 !important;">
        <div class="d-flex align-items-center justify-content-between mb-3 pb-2.5 border-bottom">
            <div class="d-flex align-items-center gap-2.5">
                <div class="rounded-circle d-flex align-items-center justify-content-center text-white flex-shrink-0" style="width: 36px; height: 36px; background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%); box-shadow: 0 3px 10px rgba(245, 158, 11, 0.3);">
                    <i class="bi bi-star-fill" style="font-size: 16px;"></i>
                </div>
                <div>
                    <h6 class="fw-extrabold m-0 text-dark" style="font-size: 13.5px; letter-spacing: -0.2px;">Rating Saya & Ulasan Pelanggan</h6>
                    <span class="text-muted" style="font-size: 10.5px;">Penilaian kepuasan pengantaran dari pembeli</span>
                </div>
            </div>
            <div class="text-end">
                <span class="badge rounded-pill px-3 py-1.5 fw-extrabold d-inline-flex align-items-center gap-1" style="background: #FEF3C7; color: #92400E; border: 1px solid #FCD34D; font-size: 12px;">
                    <i class="bi bi-star-fill text-warning" style="font-size: 11px;"></i>
                    <?= !empty($driver['reviews_count']) && (int)$driver['reviews_count'] > 0 ? number_format($driver['rating'], 1) : '5.0' ?> <span class="text-muted fw-normal" style="font-size: 10px;">/ 5.0</span>
                </span>
            </div>
        </div>

        <?php if (empty($reviews)): ?>
            <div class="text-center py-4 text-muted bg-light rounded-4 border border-dashed my-1">
                <div class="rounded-circle bg-warning-subtle text-warning d-inline-flex align-items-center justify-content-center mb-2" style="width: 44px; height: 44px;">
                    <i class="bi bi-chat-heart-fill fs-4"></i>
                </div>
                <div class="fw-bold text-dark" style="font-size: 12.5px;">Belum Ada Ulasan Driver</div>
                <div class="text-muted" style="font-size: 10.5px;">Penilaian dari pelanggan yang Anda antar orderannya akan muncul di sini.</div>
            </div>
        <?php else: ?>
            <div class="d-flex flex-column gap-2.5" style="max-height: 360px; overflow-y: auto;">
                <?php foreach ($reviews as $rev): ?>
                    <?php
                    $orderLabel = 'Pesanan';
                    if (!empty($rev['order_code']) && strpos($rev['order_code'], '#-') === false && $rev['order_code'] !== '-') {
                        $orderLabel = '#' . ltrim($rev['order_code'], '#');
                    } elseif (!empty($rev['order_id'])) {
                        $orderLabel = '#ORD-' . $rev['order_id'];
                    }
                    $revDate = !empty($rev['created_at']) ? date('d M Y', strtotime($rev['created_at'])) : date('d M Y');
                    $ratingVal = (int)($rev['rating'] ?? 5);
                    ?>
                    <div class="p-3 rounded-4" style="background: #F8FAFC; border: 1px solid #E2E8F0; transition: all 0.2s ease;">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2.5 min-w-0">
                                <img src="<?= $baseUrl ?>/<?= htmlspecialchars($rev['customer_avatar'] ?? 'assets/images/users/customer.png') ?>" alt="Customer" class="rounded-circle border border-2 border-white shadow-2xs flex-shrink-0" style="width: 34px; height: 34px; object-fit: cover;">
                                <div class="min-w-0">
                                    <div class="fw-bold text-dark text-truncate" style="font-size: 12px; line-height: 1.2;"><?= htmlspecialchars($rev['customer_name'] ?? 'Pelanggan') ?></div>
                                    <div class="d-flex align-items-center gap-1.5 mt-0.5" style="font-size: 10px;">
                                        <span class="badge rounded-pill fw-semibold px-2 py-0.5" style="background: #E2E8F0; color: #334155; font-size: 9.5px;"><?= htmlspecialchars($orderLabel) ?></span>
                                        <span class="text-muted">• <?= $revDate ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex-shrink-0 ms-2">
                                <div class="d-inline-flex align-items-center gap-1 px-2.5 py-1 rounded-pill bg-warning-subtle text-warning-emphasis fw-bold" style="font-size: 11px; border: 1px solid rgba(245, 158, 11, 0.25);">
                                    <i class="bi bi-star-fill text-warning" style="font-size: 10.5px;"></i> <?= number_format($ratingVal, 1) ?>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($rev['comment'])): ?>
                            <div class="mt-2.5 p-2.5 rounded-3 position-relative" style="background: #FFFFFF; border-left: 3.5px solid #F59E0B; border-top: 1px solid #F1F5F9; border-right: 1px solid #F1F5F9; border-bottom: 1px solid #F1F5F9; font-size: 11.5px; color: #334155; line-height: 1.45;">
                                <i class="bi bi-quote text-warning me-1 opacity-75" style="font-size: 13px;"></i>
                                <span><?= htmlspecialchars($rev['comment']) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Edit Profile Form Card -->
    <div class="ccg-card mb-3">
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
                <label class="form-label small fw-bold text-dark d-block mb-1" for="driver_avatar" style="font-size: 11px;">Foto Profil Driver</label>
                <div class="d-flex align-items-center gap-3 p-2.5 bg-light rounded-3 border" style="border-color: #E2E8F0 !important;">
                    <img id="driver-avatar-preview" src="<?= $baseUrl ?>/<?= htmlspecialchars($user['avatar'] ?? 'assets/images/users/driver.png') ?>" alt="Preview Driver" class="rounded-circle border border-2 border-danger shadow-xs" style="width: 52px; height: 52px; object-fit: cover;">
                    <div class="flex-grow-1">
                        <input type="file" name="avatar" id="driver_avatar" class="form-control form-control-sm driver-input" accept="image/jpeg,image/png,image/webp,image/gif" onchange="previewImage(this, 'driver-avatar-preview')">
                        <small class="text-muted d-block mt-1" style="font-size: 10px;">Format: JPG, PNG, WEBP. Maks 2MB.</small>
                    </div>
                </div>
            </div>

            <div class="mb-2.5">
                <label class="form-label fw-bold text-dark mb-1" for="driver_name" style="font-size: 11px;">Nama Lengkap Driver</label>
                <input type="text" name="name" id="driver_name" class="form-control form-control-sm driver-input" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
            </div>

            <div class="mb-2.5">
                <label class="form-label fw-bold text-dark mb-1" for="driver_email" style="font-size: 11px;">Alamat Email (Gmail)</label>
                <input type="email" name="email" id="driver_email" class="form-control form-control-sm driver-input" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                <div class="alert alert-warning border-0 rounded-3 p-2 mt-2 mb-0 d-flex align-items-start gap-2" style="font-size: 10.5px;">
                    <i class="bi bi-shield-exclamation text-warning fs-6 mt-0.5"></i>
                    <div>
                        Mengubah email akan mengirimkan <strong>kode OTP verifikasi 6-digit</strong> ke email baru Anda sebelum diperbarui.
                    </div>
                </div>
            </div>

            <div class="mb-2.5">
                <label class="form-label fw-bold text-dark mb-1" for="driver_phone" style="font-size: 11px;">Nomor Telepon / WA</label>
                <input type="text" name="phone" id="driver_phone" class="form-control form-control-sm driver-input" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" required>
            </div>

            <div class="mb-2.5">
                <label class="form-label fw-bold text-dark mb-1" for="driver_vehicle_type" style="font-size: 11px;">Tipe Kendaraan Operasional</label>
                <input type="text" name="vehicle_type" id="driver_vehicle_type" class="form-control form-control-sm driver-input" value="<?= htmlspecialchars($driver['vehicle_type'] ?? 'Motor Honda Beat') ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold text-dark mb-1" for="driver_vehicle_number" style="font-size: 11px;">Nomor Polisi Kendaraan (Plat Nomor)</label>
                <input type="text" name="vehicle_number" id="driver_vehicle_number" class="form-control form-control-sm driver-input" value="<?= htmlspecialchars($driver['vehicle_number'] ?? 'D 1234 CCG') ?>" required>
            </div>

            <!-- Section Ubah Kata Sandi Driver -->
            <div class="mt-4 pt-3 border-top">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="fw-bold text-dark m-0" style="font-size: 12.5px;">
                        <i class="bi bi-key-fill text-danger me-1"></i> Ubah Kata Sandi (Opsional)
                    </h6>
                    <span class="text-muted" style="font-size: 10.5px;">Kosongkan jika tidak ubah</span>
                </div>

                <div class="alert alert-info border-0 rounded-3 p-2 mb-3 d-flex align-items-start gap-2" style="font-size: 10.5px;">
                    <i class="bi bi-shield-lock-fill text-primary fs-6 mt-0.5"></i>
                    <div>
                        <strong>Verifikasi OTP Keamanan:</strong> Ubah kata sandi wajib dikonfirmasi dengan <strong>kode OTP 6-digit</strong> ke email akun Driver.
                    </div>
                </div>

                <div class="mb-2">
                    <label class="form-label fw-bold text-muted mb-1" style="font-size: 11px;">Kata Sandi Saat Ini</label>
                    <div class="input-group input-group-sm">
                        <input type="password" name="current_password" id="driver_current_password" class="form-control driver-input" style="border-radius: 10px 0 0 10px !important;" placeholder="Kata sandi lama">
                        <button class="btn btn-outline-secondary border-start-0" type="button" style="border-color: #E2E8F0; border-radius: 0 10px 10px 0;" onclick="togglePasswordVisibility('driver_current_password', this)">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-2">
                    <label class="form-label fw-bold text-muted mb-1" style="font-size: 11px;">Kata Sandi Baru (Min. 6 Karakter)</label>
                    <div class="input-group input-group-sm">
                        <input type="password" name="new_password" id="driver_new_password" class="form-control driver-input" style="border-radius: 10px 0 0 10px !important;" placeholder="Kata sandi baru">
                        <button class="btn btn-outline-secondary border-start-0" type="button" style="border-color: #E2E8F0; border-radius: 0 10px 10px 0;" onclick="togglePasswordVisibility('driver_new_password', this)">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold text-muted mb-1" style="font-size: 11px;">Konfirmasi Kata Sandi Baru</label>
                    <div class="input-group input-group-sm">
                        <input type="password" name="confirm_password" id="driver_confirm_password" class="form-control driver-input" style="border-radius: 10px 0 0 10px !important;" placeholder="Ulangi kata sandi baru">
                        <button class="btn btn-outline-secondary border-start-0" type="button" style="border-color: #E2E8F0; border-radius: 0 10px 10px 0;" onclick="togglePasswordVisibility('driver_confirm_password', this)">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="d-grid mt-4">
                <button type="submit" class="btn text-white shadow-xs py-2.5 fw-bold" style="background: linear-gradient(135deg, #EE2737 0%, #DC2626 100%); border-radius: 9999px; font-size: 12.5px;">
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

<div class="p-3 border-bottom bg-white d-flex align-items-center justify-content-between">
    <h6 class="fw-bold m-0" style="color: var(--gojek-charcoal);">Akun Saya</h6>
    <?php if ($user): ?>
        <span class="badge text-white px-2 py-1 rounded-pill" style="background: #EE2737; font-size: 10px;">
            <i class="bi bi-star-fill me-1 text-warning"></i> CicalengkaClub
        </span>
    <?php endif; ?>
</div>

<div class="p-3">
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger rounded-4 border-0 small mb-3">
            <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= htmlspecialchars($_SESSION['error']) ?>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success rounded-4 border-0 small mb-3">
            <i class="bi bi-check-circle-fill me-1"></i> <?= htmlspecialchars($_SESSION['success']) ?>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if ($user): ?>
        <!-- User Profile Header Card -->
        <div class="p-3 bg-white rounded-4 border shadow-sm mb-3 d-flex align-items-center gap-3">
            <div class="position-relative">
                <img src="<?= $baseUrl ?>/<?= htmlspecialchars($user['avatar'] ?? 'assets/images/users/default.png') ?>" alt="User" class="rounded-circle border border-2" style="width: 60px; height: 60px; object-fit: cover; border-color: #EE2737 !important;">
            </div>
            <div class="flex-grow-1">
                <div class="fw-bold fs-6" style="color: var(--gojek-charcoal);"><?= htmlspecialchars($user['name'] ?? 'Pengguna') ?></div>
                <div class="text-muted small" style="font-size: 12px;"><?= htmlspecialchars($user['phone'] ?? '-') ?></div>
                <?php if (!empty($user['email'])): ?>
                    <div class="text-muted small" style="font-size: 11px;"><?= htmlspecialchars($user['email']) ?></div>
                <?php endif; ?>
            </div>
            <button type="button" class="btn btn-light btn-sm rounded-circle border shadow-xs" data-bs-toggle="modal" data-bs-target="#editProfileModal" title="Edit Profil & Kata Sandi">
                <i class="bi bi-pencil-fill text-danger" style="font-size: 12px;"></i>
            </button>
        </div>

        <!-- CicalengkaPay Quick Card -->
        <div class="p-3 bg-white rounded-4 border shadow-sm mb-3 d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 44px; height: 44px; background: linear-gradient(135deg, #EE2737, #C61524); box-shadow: 0 4px 10px rgba(238,39,55,0.25);">
                    <i class="bi bi-wallet2 fs-5"></i>
                </div>
                <div>
                    <div class="small fw-bold" style="color: #EE2737;">Cicalengka<span style="color:#C61524;">Pay</span></div>
                    <div class="fw-bold text-dark fs-6"><?= format_rupiah($wallet['balance'] ?? 0) ?></div>
                </div>
            </div>
            <a href="<?= $baseUrl ?>/wallet" class="btn btn-sm rounded-pill fw-bold px-3 text-white" style="background:#EE2737; font-size: 11px;">
                Isi Saldo
            </a>
        </div>

        <!-- Menu Navigation List -->
        <div class="bg-white rounded-4 border shadow-sm overflow-hidden mb-3">
            <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#editProfileModal" class="p-3 d-flex align-items-center justify-content-between text-decoration-none text-dark border-bottom">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-person-gear fs-5" style="color: #EE2737 !important;"></i>
                    <div>
                        <div class="small fw-bold">Edit Profil & Ganti Kata Sandi</div>
                        <div class="text-muted" style="font-size: 11px;">Ubah foto, nama, email, no HP & password</div>
                    </div>
                </div>
                <i class="bi bi-chevron-right text-muted small"></i>
            </a>

            <a href="<?= $baseUrl ?>/orders" class="p-3 d-flex align-items-center justify-content-between text-decoration-none text-dark border-bottom">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-receipt fs-5" style="color: #EE2737 !important;"></i>
                    <span class="small fw-bold">Riwayat Pesanan</span>
                </div>
                <i class="bi bi-chevron-right text-muted small"></i>
            </a>

            <a href="<?= $baseUrl ?>/search" class="p-3 d-flex align-items-center justify-content-between text-decoration-none text-dark border-bottom">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-percent text-warning fs-5"></i>
                    <span class="small fw-bold">Voucher & Promo Saya</span>
                </div>
                <i class="bi bi-chevron-right text-muted small"></i>
            </a>

            <a href="<?= $baseUrl ?>/notifications" class="p-3 d-flex align-items-center justify-content-between text-decoration-none text-dark border-bottom">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-bell text-primary fs-5"></i>
                    <span class="small fw-bold">Pusat Notifikasi</span>
                </div>
                <i class="bi bi-chevron-right text-muted small"></i>
            </a>

            <a href="javascript:void(0)" onclick="Swal.fire('Pusat Bantuan', 'Hubungi layanan pelanggan CicalengkaGO via WhatsApp di 0812-3456-7890', 'info')" class="p-3 d-flex align-items-center justify-content-between text-decoration-none text-dark border-bottom">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-question-circle-fill text-info fs-5"></i>
                    <span class="small fw-bold">Bantuan & CS 24 Jam</span>
                </div>
                <i class="bi bi-chevron-right text-muted small"></i>
            </a>

            <a href="<?= $baseUrl ?>/logout" class="p-3 d-flex align-items-center justify-content-between text-decoration-none text-danger">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-box-arrow-right fs-5"></i>
                    <span class="small fw-bold">Keluar Akun</span>
                </div>
                <i class="bi bi-chevron-right small"></i>
            </a>
        </div>
    <?php else: ?>
        <div class="text-center py-5">
            <div class="rounded-circle bg-light text-muted d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 70px; height: 70px; font-size: 28px;">
                <i class="bi bi-person-lock text-muted"></i>
            </div>
            <h6 class="fw-bold" style="color: var(--gojek-charcoal);">Masuk ke Akun CicalengkaGO</h6>
            <p class="text-muted small">Masuk untuk melihat pesanan, saldo CicalengkaPay, dan promo khusus Anda.</p>
            <div class="d-grid gap-2 mt-3">
                <a href="<?= $baseUrl ?>/login" class="btn btn-gojek-green py-2" style="background:#EE2737 !important; color:#FFFFFF !important; border-radius:9999px; font-weight:800; text-decoration:none;">Masuk Sekarang</a>
                <a href="<?= $baseUrl ?>/register" class="btn btn-outline-danger rounded-pill fw-bold py-2" style="border-color:#EE2737; color:#EE2737;">Daftar Akun Baru</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Edit Profil -->
<?php if ($user): ?>
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h6 class="modal-title fw-bold text-dark" id="editProfileModalLabel">
                    <i class="bi bi-person-gear me-1 text-danger"></i> Edit Profil Saya
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= $baseUrl ?>/profile/update" method="POST" enctype="multipart/form-data">
                <div class="modal-body py-3">
                    <!-- Foto Profil Avatar -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark d-block">Foto Profil</label>
                        <div class="d-flex align-items-center gap-3 p-2.5 bg-light rounded-4 border">
                            <img id="customer-avatar-preview" src="<?= $baseUrl ?>/<?= htmlspecialchars($user['avatar'] ?? 'assets/images/users/default.png') ?>" alt="Preview" class="rounded-circle border border-2 border-danger shadow-xs" style="width: 55px; height: 55px; object-fit: cover;">
                            <div class="flex-grow-1">
                                <input type="file" name="avatar" class="form-control rounded-3 form-control-sm" accept="image/jpeg,image/png,image/webp,image/gif" onchange="previewImage(this, 'customer-avatar-preview')">
                                <small class="text-muted" style="font-size: 10px;">Format: JPG, PNG, WEBP. Maks 2MB.</small>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Nama Lengkap</label>
                        <input type="text" name="name" class="form-control rounded-3" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Alamat Email (Gmail)</label>
                        <input type="email" name="email" class="form-control rounded-3" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                        <div class="alert alert-warning border-0 rounded-3 p-2 mt-2 mb-0 d-flex align-items-start gap-2" style="font-size: 11px;">
                            <i class="bi bi-info-circle-fill text-warning fs-6 mt-0.5"></i>
                            <div>
                                Jika Anda mengubah alamat email, sistem akan mengirimkan <strong>kode OTP verifikasi</strong> ke email baru Anda sebelum perubahan diterapkan.
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Nomor Telepon / WhatsApp</label>
                        <input type="text" name="phone" class="form-control rounded-3" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" required>
                    </div>

                    <!-- Section Ubah Kata Sandi Pelanggan -->
                    <div class="mt-3 pt-3 border-top">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <label class="form-label small fw-bold text-dark m-0">
                                <i class="bi bi-key-fill text-danger me-1"></i> Ubah Kata Sandi (Opsional)
                            </label>
                            <span class="text-muted" style="font-size: 10px;">Kosongkan jika tidak diubah</span>
                        </div>

                        <div class="mb-2">
                            <div class="input-group input-group-sm">
                                <input type="password" name="current_password" id="customer_current_password" class="form-control rounded-start-3" placeholder="Kata sandi saat ini">
                                <button class="btn btn-outline-secondary border rounded-end-3" type="button" onclick="togglePasswordVisibility('customer_current_password', this)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-2">
                            <div class="input-group input-group-sm">
                                <input type="password" name="new_password" id="customer_new_password" class="form-control rounded-start-3" placeholder="Kata sandi baru (Min. 6 karakter)">
                                <button class="btn btn-outline-secondary border rounded-end-3" type="button" onclick="togglePasswordVisibility('customer_new_password', this)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-1">
                            <div class="input-group input-group-sm">
                                <input type="password" name="confirm_password" id="customer_confirm_password" class="form-control rounded-start-3" placeholder="Konfirmasi kata sandi baru">
                                <button class="btn btn-outline-secondary border rounded-end-3" type="button" onclick="togglePasswordVisibility('customer_confirm_password', this)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn text-white rounded-pill btn-sm px-4 fw-bold" style="background:#EE2737;">
                        <i class="bi bi-floppy-fill me-1"></i> Simpan Perubahan
                    </button>
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
<?php endif; ?>

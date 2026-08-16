<div class="border-bottom bg-white d-flex align-items-center justify-content-between sticky-top shadow-xs px-3 py-2">
    <h6 class="fw-bold m-0 text-dark" style="font-size: 13px;">Akun Saya</h6>
    <?php if ($user): ?>
        <span class="badge text-white px-2.5 py-1 rounded-pill" style="background: #EE2737; font-size: 10px;">
            <i class="bi bi-star-fill me-1 text-warning"></i> CicalengkaClub
        </span>
    <?php endif; ?>
</div>

<div class="px-3 pt-3.5 pb-5" style="min-height: 85vh;">
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger rounded-3 border-0 p-3 mb-3" style="font-size: 10.5px;">
            <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= htmlspecialchars($_SESSION['error']) ?>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success rounded-3 border-0 p-3 mb-3" style="font-size: 10.5px;">
            <i class="bi bi-check-circle-fill me-1"></i> <?= htmlspecialchars($_SESSION['success']) ?>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if ($user): ?>
        <!-- User Profile Header Card -->
        <div class="p-3 bg-white border shadow-xs mb-3.5 d-flex align-items-center gap-3" style="border-radius: 14px;">
            <div class="position-relative">
                <img src="<?= $baseUrl ?>/<?= htmlspecialchars($user['avatar'] ?? 'assets/images/users/default.png') ?>" alt="User" class="rounded-circle border border-2" style="width: 48px; height: 48px; object-fit: cover; border-color: #EE2737 !important;">
            </div>
            <div class="flex-grow-1 min-w-0">
                <div class="fw-bold text-truncate" style="color: var(--gojek-charcoal); font-size: 13px;"><?= htmlspecialchars($user['name'] ?? 'Pengguna') ?></div>
                <div class="text-muted" style="font-size: 10px;"><?= htmlspecialchars($user['phone'] ?? '-') ?></div>
                <?php if (!empty($user['email'])): ?>
                    <div class="text-muted text-truncate" style="font-size: 9.5px;"><?= htmlspecialchars($user['email']) ?></div>
                <?php endif; ?>
            </div>
            <button type="button" class="btn btn-light btn-sm rounded-circle border shadow-xs d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;" data-bs-toggle="modal" data-bs-target="#editProfileModal" title="Edit Profil">
                <i class="bi bi-pencil-fill text-danger" style="font-size: 12px;"></i>
            </button>
        </div>

        <!-- CicalengkaPay Quick Card -->
        <div class="p-3 bg-white border shadow-xs mb-3.5 d-flex align-items-center justify-content-between" style="border-radius: 14px;">
            <div class="d-flex align-items-center gap-2.5">
                <div class="rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 38px; height: 38px; font-size: 17px; background: linear-gradient(135deg, #EE2737, #C61524); box-shadow: 0 2px 6px rgba(238,39,55,0.25);">
                    <i class="bi bi-wallet2"></i>
                </div>
                <div>
                    <div class="fw-bold" style="color: #EE2737; font-size: 10.5px;">Cicalengka<span style="color:#C61524;">Pay</span></div>
                    <div class="fw-extrabold text-dark" style="font-size: 13.5px;"><?= format_rupiah($wallet['balance'] ?? 0) ?></div>
                </div>
            </div>
            <a href="<?= $baseUrl ?>/wallet" class="btn btn-sm rounded-pill fw-bold px-3 py-1.5 text-white shadow-xs" style="background:#EE2737; font-size: 10.5px;">
                Isi Saldo
            </a>
        </div>

        <!-- Menu Navigation List -->
        <div class="bg-white border shadow-xs overflow-hidden mb-4" style="border-radius: 14px;">
            <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#editProfileModal" class="p-3 d-flex align-items-center justify-content-between text-decoration-none text-dark border-bottom hover-bg-light transition">
                <div class="d-flex align-items-center gap-2.5">
                    <i class="bi bi-person-gear" style="color: #EE2737 !important; font-size: 16px;"></i>
                    <div>
                        <div class="fw-bold" style="font-size: 11.5px;">Edit Profil & Kata Sandi</div>
                        <div class="text-muted" style="font-size: 9.5px;">Ubah foto, profil & password</div>
                    </div>
                </div>
                <i class="bi bi-chevron-right text-muted" style="font-size: 11px;"></i>
            </a>

            <a href="<?= $baseUrl ?>/orders" class="p-3 d-flex align-items-center justify-content-between text-decoration-none text-dark border-bottom hover-bg-light transition">
                <div class="d-flex align-items-center gap-2.5">
                    <i class="bi bi-receipt" style="color: #EE2737 !important; font-size: 16px;"></i>
                    <span class="fw-bold" style="font-size: 11.5px;">Riwayat Pesanan</span>
                </div>
                <i class="bi bi-chevron-right text-muted" style="font-size: 11px;"></i>
            </a>

            <a href="<?= $baseUrl ?>/search" class="p-3 d-flex align-items-center justify-content-between text-decoration-none text-dark border-bottom hover-bg-light transition">
                <div class="d-flex align-items-center gap-2.5">
                    <i class="bi bi-percent text-warning" style="font-size: 16px;"></i>
                    <span class="fw-bold" style="font-size: 11.5px;">Voucher & Promo Saya</span>
                </div>
                <i class="bi bi-chevron-right text-muted" style="font-size: 11px;"></i>
            </a>

            <a href="<?= $baseUrl ?>/notifications" class="p-3 d-flex align-items-center justify-content-between text-decoration-none text-dark border-bottom hover-bg-light transition">
                <div class="d-flex align-items-center gap-2.5">
                    <i class="bi bi-bell text-primary" style="font-size: 16px;"></i>
                    <span class="fw-bold" style="font-size: 11.5px;">Pusat Notifikasi</span>
                </div>
                <i class="bi bi-chevron-right text-muted" style="font-size: 11px;"></i>
            </a>

            <a href="javascript:void(0)" onclick="Swal.fire('Pusat Bantuan', 'Hubungi layanan pelanggan CicalengkaGO via WhatsApp di 0812-3456-7890', 'info')" class="p-3 d-flex align-items-center justify-content-between text-decoration-none text-dark border-bottom hover-bg-light transition">
                <div class="d-flex align-items-center gap-2.5">
                    <i class="bi bi-question-circle-fill text-info" style="font-size: 16px;"></i>
                    <span class="fw-bold" style="font-size: 11.5px;">Bantuan & CS 24 Jam</span>
                </div>
                <i class="bi bi-chevron-right text-muted" style="font-size: 11px;"></i>
            </a>

            <a href="<?= $baseUrl ?>/logout" class="p-3 d-flex align-items-center justify-content-between text-decoration-none text-danger hover-bg-light transition">
                <div class="d-flex align-items-center gap-2.5">
                    <i class="bi bi-box-arrow-right" style="font-size: 16px;"></i>
                    <span class="fw-bold" style="font-size: 11.5px;">Keluar Akun</span>
                </div>
                <i class="bi bi-chevron-right" style="font-size: 11px;"></i>
            </a>
        </div>
    <?php else: ?>
        <div class="text-center py-5">
            <div class="rounded-circle bg-light text-muted d-flex align-items-center justify-content-center mx-auto mb-2.5" style="width: 52px; height: 52px; font-size: 22px;">
                <i class="bi bi-person-lock text-muted"></i>
            </div>
            <h6 class="fw-bold mb-1" style="color: var(--gojek-charcoal); font-size: 13px;">Masuk ke Akun CicalengkaGO</h6>
            <p class="text-muted mb-3" style="font-size: 10.5px;">Masuk untuk melihat pesanan, saldo CicalengkaPay, dan promo khusus Anda.</p>
            <div class="d-grid gap-2">
                <a href="<?= $baseUrl ?>/login" class="btn btn-gojek-green py-2" style="background:#EE2737 !important; color:#FFFFFF !important; border-radius:9999px; font-weight:700; font-size:12px; text-decoration:none;">Masuk Sekarang</a>
                <a href="<?= $baseUrl ?>/register" class="btn btn-outline-danger rounded-pill fw-bold py-2" style="border-color:#EE2737; color:#EE2737; font-size:12px;">Daftar Akun Baru</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Edit Profil -->
<?php if ($user): ?>
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0 pt-2.5 px-3">
                <h6 class="modal-title fw-bold text-dark" id="editProfileModalLabel" style="font-size: 12px;">
                    <i class="bi bi-person-gear me-1 text-danger"></i> Edit Profil Saya
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="font-size: 10px;"></button>
            </div>
            <form action="<?= $baseUrl ?>/profile/update" method="POST" enctype="multipart/form-data">
                <div class="modal-body py-2 px-3">
                    <!-- Foto Profil Avatar -->
                    <div class="mb-2">
                        <label class="form-label fw-bold text-dark d-block mb-1" style="font-size: 10px;">Foto Profil</label>
                        <div class="d-flex align-items-center gap-2 p-2 bg-light rounded-3 border">
                            <img id="customer-avatar-preview" src="<?= $baseUrl ?>/<?= htmlspecialchars($user['avatar'] ?? 'assets/images/users/default.png') ?>" alt="Preview" class="rounded-circle border border-2 border-danger shadow-xs" style="width: 40px; height: 40px; object-fit: cover;">
                            <div class="flex-grow-1">
                                <input type="file" name="avatar" id="customer_avatar" class="form-control rounded-2 form-control-sm" style="font-size: 9.5px;" accept="image/jpeg,image/png,image/webp,image/gif" onchange="previewImage(this, 'customer-avatar-preview')">
                                <small class="text-muted" style="font-size: 8.5px;">JPG, PNG, WEBP. Maks 2MB.</small>
                            </div>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-bold text-dark mb-0.5" for="customer_name" style="font-size: 10px;">Nama Lengkap</label>
                        <input type="text" name="name" id="customer_name" class="form-control form-control-sm rounded-2" style="font-size: 10.5px;" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-bold text-dark mb-0.5" for="customer_email" style="font-size: 10px;">Alamat Email</label>
                        <input type="email" name="email" id="customer_email" class="form-control form-control-sm rounded-2" style="font-size: 10.5px;" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                        <div class="alert alert-warning border-0 rounded-2 p-1.5 mt-1 mb-0 d-flex align-items-start gap-1.5" style="font-size: 9px;">
                            <i class="bi bi-info-circle-fill text-warning mt-0.5" style="font-size: 10px;"></i>
                            <div>Perubahan email butuh verifikasi <strong>kode OTP</strong> ke email baru Anda.</div>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-bold text-dark mb-0.5" for="customer_phone" style="font-size: 10px;">No. WhatsApp</label>
                        <input type="text" name="phone" id="customer_phone" class="form-control form-control-sm rounded-2" style="font-size: 10.5px;" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" required>
                    </div>

                    <!-- Section Ubah Kata Sandi Pelanggan -->
                    <div class="mt-2 pt-2 border-top">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <label class="form-label fw-bold text-dark m-0" style="font-size: 10px;">
                                <i class="bi bi-key-fill text-danger me-0.5"></i> Kata Sandi (Opsional)
                            </label>
                            <span class="text-muted" style="font-size: 8.5px;">Kosongkan jika tdk diubah</span>
                        </div>

                        <div class="mb-1.5">
                            <div class="input-group input-group-sm">
                                <input type="password" name="current_password" id="customer_current_password" class="form-control rounded-start-2" style="font-size: 10px;" placeholder="Kata sandi saat ini">
                                <button class="btn btn-outline-secondary border rounded-end-2 px-2" type="button" onclick="togglePasswordVisibility('customer_current_password', this)">
                                    <i class="bi bi-eye" style="font-size: 10px;"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-1.5">
                            <div class="input-group input-group-sm">
                                <input type="password" name="new_password" id="customer_new_password" class="form-control rounded-start-2" style="font-size: 10px;" placeholder="Kata sandi baru (Min. 6)">
                                <button class="btn btn-outline-secondary border rounded-end-2 px-2" type="button" onclick="togglePasswordVisibility('customer_new_password', this)">
                                    <i class="bi bi-eye" style="font-size: 10px;"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-1">
                            <div class="input-group input-group-sm">
                                <input type="password" name="confirm_password" id="customer_confirm_password" class="form-control rounded-start-2" style="font-size: 10px;" placeholder="Ulangi kata sandi baru">
                                <button class="btn btn-outline-secondary border rounded-end-2 px-2" type="button" onclick="togglePasswordVisibility('customer_confirm_password', this)">
                                    <i class="bi bi-eye" style="font-size: 10px;"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0 pb-2.5 px-3">
                    <button type="button" class="btn btn-light rounded-pill btn-sm px-2.5 py-1" style="font-size: 10px;" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn text-white rounded-pill btn-sm px-3 py-1 fw-bold" style="background:#EE2737; font-size: 10px;">
                        <i class="bi bi-floppy-fill me-1"></i> Simpan
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

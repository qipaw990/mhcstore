<div class="border-bottom bg-white d-flex align-items-center justify-content-between sticky-top app-subpage-header px-3 py-2.5 shadow-2xs" style="z-index: 1020;">
    <h6 class="fw-bold m-0 text-dark" style="font-size: 14px; letter-spacing: -0.3px;">Akun Saya</h6>
    <?php if ($user): ?>
        <span class="badge text-white px-2.5 py-1 rounded-pill shadow-2xs flex-shrink-0" style="background: linear-gradient(135deg, #EE2737, #C61524); font-size: 9.5px; font-weight: 700;">
            <i class="bi bi-star-fill me-1 text-warning"></i> CicalengkaClub
        </span>
    <?php endif; ?>
</div>

<div class="px-3 py-3" style="background: #F8FAFC; min-height: calc(100vh - 110px);">
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger rounded-3 border-0 p-2.5 mb-3 shadow-2xs" style="font-size: 11px;">
            <i class="bi bi-exclamation-triangle-fill me-1.5"></i> <?= htmlspecialchars($_SESSION['error']) ?>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success rounded-3 border-0 p-2.5 mb-3 shadow-2xs" style="font-size: 11px;">
            <i class="bi bi-check-circle-fill me-1.5"></i> <?= htmlspecialchars($_SESSION['success']) ?>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if ($user): ?>
        <!-- User Profile Card -->
        <div class="bg-white border shadow-2xs mb-3 p-3 position-relative" style="border-radius: 16px; border-color: #E2E8F0 !important;">
            <div class="d-flex align-items-center gap-3">
                <div class="position-relative flex-shrink-0">
                    <img src="<?= $baseUrl ?>/<?= htmlspecialchars($user['avatar'] ?? 'assets/images/users/default.png') ?>" alt="User" class="rounded-circle border border-2 shadow-2xs" style="width: 52px; height: 52px; object-fit: cover; border-color: #EE2737 !important;">
                </div>
                <div class="flex-grow-1 min-w-0">
                    <div class="fw-bold text-truncate" style="color: #0F172A; font-size: 14px; letter-spacing: -0.2px;"><?= htmlspecialchars($user['name'] ?? 'Pengguna') ?></div>
                    <div class="text-secondary text-truncate mt-0.5" style="font-size: 11px; font-weight: 500;">
                        <i class="bi bi-telephone-fill me-1 text-muted" style="font-size: 10px;"></i><?= htmlspecialchars($user['phone'] ?? '-') ?>
                    </div>
                    <?php if (!empty($user['email'])): ?>
                        <div class="text-muted text-truncate mt-0.5" style="font-size: 10.5px;">
                            <i class="bi bi-envelope-fill me-1 text-muted" style="font-size: 9.5px;"></i><?= htmlspecialchars($user['email']) ?>
                        </div>
                    <?php endif; ?>
                </div>
                <button type="button" class="btn btn-light btn-sm rounded-circle border shadow-2xs d-flex align-items-center justify-content-center flex-shrink-0" style="width: 36px; height: 36px; background: #F1F5F9; border-color: #CBD5E1 !important;" data-bs-toggle="modal" data-bs-target="#editProfileModal" title="Edit Profil">
                    <i class="bi bi-pencil-fill text-danger" style="font-size: 13px;"></i>
                </button>
            </div>
        </div>

        <!-- CicalengkaPay Quick Balance Card -->
        <div class="bg-white border shadow-2xs mb-3 p-3 d-flex align-items-center justify-content-between gap-2 position-relative overflow-hidden" style="border-radius: 16px; border-color: #E2E8F0 !important;">
            <div class="d-flex align-items-center gap-3 min-w-0">
                <div class="rounded-circle d-flex align-items-center justify-content-center text-white flex-shrink-0 shadow-2xs" style="width: 38px; height: 38px; font-size: 17px; background: linear-gradient(135deg, #EE2737, #C61524);">
                    <i class="bi bi-wallet2"></i>
                </div>
                <div class="min-w-0">
                    <div class="fw-bold text-uppercase text-muted" style="font-size: 9.5px; letter-spacing: 0.5px;">Saldo Cicalengka<span style="color: #EE2737;">Pay</span></div>
                    <div class="fw-black text-dark text-truncate" style="font-size: 15px; letter-spacing: -0.3px; color: #0F172A;"><?= format_rupiah($wallet['balance'] ?? 0) ?></div>
                </div>
            </div>
            <a href="<?= $baseUrl ?>/wallet" class="btn btn-sm rounded-pill fw-bold px-3 py-1.5 text-white shadow-2xs flex-shrink-0" style="background: linear-gradient(135deg, #EE2737, #C61524); font-size: 11px;">
                Isi Saldo
            </a>
        </div>

        <!-- Menu Navigation List -->
        <div class="bg-white border shadow-2xs overflow-hidden mb-3" style="border-radius: 16px; border-color: #E2E8F0 !important;">
            
            <!-- Edit Profil & Kata Sandi -->
            <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#editProfileModal" class="d-flex align-items-center justify-content-between text-decoration-none border-bottom transition" style="padding: 12px 14px !important; background: #FFFFFF;">
                <div class="d-flex align-items-center gap-3 min-w-0">
                    <div style="width: 38px; height: 38px; min-width: 38px; min-height: 38px; border-radius: 12px; background: #FEE2E2; color: #EE2737; display: flex; align-items: center; justify-content: center; flex-shrink: 0; align-self: center;">
                        <i class="bi bi-person-gear" style="font-size: 18px;"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="fw-bold text-truncate" style="font-size: 13px; color: #0F172A; line-height: 1.3;">Edit Profil & Kata Sandi</div>
                        <div class="text-muted text-truncate mt-0.5" style="font-size: 10px; color: #64748B;">Ubah foto profil, nama & password</div>
                    </div>
                </div>
                <i class="bi bi-chevron-right flex-shrink-0 ms-2" style="font-size: 12px; color: #94A3B8;"></i>
            </a>

            <!-- Riwayat Pesanan -->
            <a href="<?= $baseUrl ?>/orders" class="d-flex align-items-center justify-content-between text-decoration-none border-bottom transition" style="padding: 12px 14px !important; background: #FFFFFF;">
                <div class="d-flex align-items-center gap-3 min-w-0">
                    <div style="width: 38px; height: 38px; min-width: 38px; min-height: 38px; border-radius: 12px; background: #E0F2FE; color: #0284C7; display: flex; align-items: center; justify-content: center; flex-shrink: 0; align-self: center;">
                        <i class="bi bi-receipt" style="font-size: 18px;"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="fw-bold text-truncate" style="font-size: 13px; color: #0F172A; line-height: 1.3;">Riwayat Pesanan</div>
                        <div class="text-muted text-truncate mt-0.5" style="font-size: 10px; color: #64748B;">Cek daftar transaksi & status pengiriman</div>
                    </div>
                </div>
                <i class="bi bi-chevron-right flex-shrink-0 ms-2" style="font-size: 12px; color: #94A3B8;"></i>
            </a>

            <!-- Voucher & Promo Saya -->
            <a href="<?= $baseUrl ?>/search" class="d-flex align-items-center justify-content-between text-decoration-none border-bottom transition" style="padding: 12px 14px !important; background: #FFFFFF;">
                <div class="d-flex align-items-center gap-3 min-w-0">
                    <div style="width: 38px; height: 38px; min-width: 38px; min-height: 38px; border-radius: 12px; background: #FEF3C7; color: #D97706; display: flex; align-items: center; justify-content: center; flex-shrink: 0; align-self: center;">
                        <i class="bi bi-percent" style="font-size: 18px;"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="fw-bold text-truncate" style="font-size: 13px; color: #0F172A; line-height: 1.3;">Voucher & Promo Saya</div>
                        <div class="text-muted text-truncate mt-0.5" style="font-size: 10px; color: #64748B;">Kupon diskon & penawaran menarik</div>
                    </div>
                </div>
                <i class="bi bi-chevron-right flex-shrink-0 ms-2" style="font-size: 12px; color: #94A3B8;"></i>
            </a>

            <!-- Pusat Notifikasi -->
            <a href="<?= $baseUrl ?>/notifications" class="d-flex align-items-center justify-content-between text-decoration-none border-bottom transition" style="padding: 12px 14px !important; background: #FFFFFF;">
                <div class="d-flex align-items-center gap-3 min-w-0">
                    <div style="width: 38px; height: 38px; min-width: 38px; min-height: 38px; border-radius: 12px; background: #F3E8FF; color: #9333EA; display: flex; align-items: center; justify-content: center; flex-shrink: 0; align-self: center;">
                        <i class="bi bi-bell" style="font-size: 18px;"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="fw-bold text-truncate" style="font-size: 13px; color: #0F172A; line-height: 1.3;">Pusat Notifikasi</div>
                        <div class="text-muted text-truncate mt-0.5" style="font-size: 10px; color: #64748B;">Pesan masuk, update pesanan & promo</div>
                    </div>
                </div>
                <i class="bi bi-chevron-right flex-shrink-0 ms-2" style="font-size: 12px; color: #94A3B8;"></i>
            </a>

            <!-- Bantuan & CS 24 Jam -->
            <a href="https://wa.me/6285158397756?text=Halo%20CS%20CicalengkaGO%2C%20saya%20butuh%20bantuan%20mengenai%20aplikasi." target="_blank" class="d-flex align-items-center justify-content-between text-decoration-none border-bottom transition" style="padding: 12px 14px !important; background: #FFFFFF;">
                <div class="d-flex align-items-center gap-3 min-w-0">
                    <div style="width: 38px; height: 38px; min-width: 38px; min-height: 38px; border-radius: 12px; background: #CCFBF1; color: #0D9488; display: flex; align-items: center; justify-content: center; flex-shrink: 0; align-self: center;">
                        <i class="bi bi-whatsapp" style="font-size: 18px;"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="fw-bold text-truncate" style="font-size: 13px; color: #0F172A; line-height: 1.3;">Bantuan & CS 24 Jam</div>
                        <div class="text-muted text-truncate mt-0.5" style="font-size: 10px; color: #64748B;">0851-5839-7756 (Chat WhatsApp)</div>
                    </div>
                </div>
                <i class="bi bi-chevron-right flex-shrink-0 ms-2" style="font-size: 12px; color: #94A3B8;"></i>
            </a>

            <!-- Keluar Akun -->
            <a href="<?= $baseUrl ?>/logout" class="d-flex align-items-center justify-content-between text-decoration-none transition" style="padding: 12px 14px !important; background: #FFFFFF;">
                <div class="d-flex align-items-center gap-3 min-w-0">
                    <div style="width: 38px; height: 38px; min-width: 38px; min-height: 38px; border-radius: 12px; background: #FFE4E6; color: #E11D48; display: flex; align-items: center; justify-content: center; flex-shrink: 0; align-self: center;">
                        <i class="bi bi-box-arrow-right" style="font-size: 18px;"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="fw-bold text-truncate" style="font-size: 13.5px; color: #E11D48; line-height: 1.3;">Keluar Akun</div>
                        <div class="text-muted text-truncate mt-0.5" style="font-size: 10px; color: #64748B;">Keluar dari sesi akun saat ini</div>
                    </div>
                </div>
                <i class="bi bi-chevron-right flex-shrink-0 ms-2" style="font-size: 12px; color: #E11D48;"></i>
            </a>
        </div>

        <div class="text-center text-muted py-2 mb-3" style="font-size: 10px; font-weight: 500;">
            CicalengkaGO v3.6.0 • Platform Layanan Lokal Cicalengka
        </div>
    <?php else: ?>
        <div class="bg-white border shadow-sm p-4 my-2 text-center" style="border-radius: 20px; border-color: #E2E8F0 !important;">
            <!-- Icon Squircle Illustration -->
            <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3 shadow-2xs" style="width: 72px; height: 72px; background: #FEE2E2; color: #EE2737;">
                <i class="bi bi-person-badge-fill" style="font-size: 32px;"></i>
            </div>
            
            <h5 class="fw-extrabold text-dark mb-1.5" style="font-size: 15px; letter-spacing: -0.3px; color: #0F172A;">Selamat Datang di CicalengkaGO!</h5>
            <p class="text-secondary mb-4 mx-auto" style="font-size: 11px; max-width: 290px; line-height: 1.5; font-weight: 500;">
                Masuk ke akun Anda untuk menikmati transaksi pesan antar makanan, saldo CicalengkaPay, dan promo menarik setiap hari.
            </p>
            
            <div class="d-flex flex-column gap-2 mb-4 mx-auto" style="max-width: 300px;">
                <a href="<?= $baseUrl ?>/login" class="btn text-white fw-extrabold shadow-2xs d-flex align-items-center justify-content-center gap-2 py-2.5 text-decoration-none" style="background: linear-gradient(135deg, #EE2737, #C61524); border-radius: 9999px; font-size: 13px; box-shadow: 0 4px 12px rgba(238, 39, 55, 0.25);">
                    <i class="bi bi-box-arrow-in-right" style="font-size: 15px;"></i> Masuk Sekarang
                </a>
                <a href="<?= $baseUrl ?>/register" class="btn btn-light fw-bold text-dark d-flex align-items-center justify-content-center gap-2 py-2 text-decoration-none" style="background: #F1F5F9; border: 1px solid #CBD5E1; border-radius: 9999px; font-size: 12px;">
                    <i class="bi bi-person-plus-fill text-danger" style="font-size: 14px;"></i> Daftar Akun Baru
                </a>
            </div>

            <!-- Features Grid -->
            <div class="pt-3 border-top d-flex justify-content-center align-items-center gap-3 text-muted" style="font-size: 10px; font-weight: 600;">
                <span class="d-flex align-items-center gap-1"><i class="bi bi-lightning-charge-fill text-warning"></i> Cepat</span>
                <span>•</span>
                <span class="d-flex align-items-center gap-1"><i class="bi bi-shield-check text-success"></i> Aman</span>
                <span>•</span>
                <span class="d-flex align-items-center gap-1"><i class="bi bi-tags-fill text-danger"></i> Hemat</span>
            </div>
        </div>

        <div class="text-center text-muted py-3" style="font-size: 10px; font-weight: 500;">
            CicalengkaGO v3.5.0 • Platform Layanan Lokal Cicalengka
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

<div class="row g-4 mb-5">
    <!-- Header -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 text-white position-relative overflow-hidden" style="background: linear-gradient(135deg, #101820 0%, #1e293b 100%); border-left: 5px solid #EE2737 !important;">
            <div class="card-body p-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div>
                    <h5 class="fw-bold m-0 text-white"><i class="bi bi-sliders text-danger me-2"></i>Pusat Konfigurasi System & API Setup</h5>
                    <small class="text-white-50">Kelola API Keys (Midtrans & WhatsApp Gateway), identitas aplikasi, tarif komisi, dan parameter operasional CicalengkaGO.</small>
                </div>
                <span class="badge bg-danger-subtle text-danger px-3 py-2 rounded-pill fw-bold" style="font-size: 11px;">
                    <i class="bi bi-shield-lock-fill me-1"></i> System Admin Privilege
                </span>
            </div>
        </div>
    </div>

    <!-- Settings Form -->
    <div class="col-12">
        <form action="<?= $baseUrl ?>/admin/settings/save" method="POST">
            <div class="row g-4">

                <!-- 1. SETUP INTEGRASI API PAYMENT GATEWAY (MIDTRANS SNAP) -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100 p-4">
                        <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                            <h6 class="fw-bold text-dark m-0"><i class="bi bi-credit-card-2-front-fill me-2 text-danger"></i>Setup Midtrans Payment Gateway API</h6>
                            <span class="badge bg-primary-subtle text-primary rounded-pill px-2.5 py-1" style="font-size: 10px;">Payment API</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label small fw-bold">Mode Lingkungan (Environment)</label>
                                <select name="midtrans_environment" class="form-select rounded-3">
                                    <option value="sandbox" <?= ($settings['midtrans_environment'] ?? 'sandbox') === 'sandbox' ? 'selected' : '' ?>>🔴 Sandbox / Testing Mode (Development)</option>
                                    <option value="production" <?= ($settings['midtrans_environment'] ?? '') === 'production' ? 'selected' : '' ?>>🟢 Production / Live Mode (Real Money)</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Midtrans Server Key</label>
                                <input type="password" name="midtrans_server_key" class="form-control rounded-3" value="<?= htmlspecialchars($settings['midtrans_server_key'] ?? 'SB-Mid-server-YOUR_SERVER_KEY') ?>" placeholder="SB-Mid-server-xxxx">
                                <small class="text-muted" style="font-size: 10px;">Gunakan Server Key dari Dashboard Midtrans (MAP).</small>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Midtrans Client Key</label>
                                <input type="text" name="midtrans_client_key" class="form-control rounded-3" value="<?= htmlspecialchars($settings['midtrans_client_key'] ?? 'SB-Mid-client-YOUR_CLIENT_KEY') ?>" placeholder="SB-Mid-client-xxxx">
                                <small class="text-muted" style="font-size: 10px;">Client Key publik untuk frontend Snap JS Popup.</small>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Merchant ID (Opsional)</label>
                                <input type="text" name="midtrans_merchant_id" class="form-control rounded-3" value="<?= htmlspecialchars($settings['midtrans_merchant_id'] ?? 'G123456789') ?>" placeholder="G123456789">
                            </div>
                            <div class="col-12 mt-2">
                                <button type="button" onclick="testMidtransConnection()" class="btn btn-outline-danger btn-sm rounded-pill w-100 fw-bold py-2" id="btn-test-midtrans">
                                    <i class="bi bi-lightning-charge-fill me-1"></i> Tes Koneksi & Status Midtrans API
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. SETUP INTEGRASI WHATSAPP GATEWAY API (BABLAST / FONNTE) -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100 p-4">
                        <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                            <h6 class="fw-bold text-dark m-0"><i class="bi bi-whatsapp me-2 text-success"></i>Setup WhatsApp Gateway API (OTP & Notifikasi)</h6>
                            <span class="badge bg-success-subtle text-success rounded-pill px-2.5 py-1" style="font-size: 10px;">WA Gateway</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label small fw-bold">API Endpoint Provider</label>
                                <input type="text" name="wa_gateway_endpoint" class="form-control rounded-3" value="<?= htmlspecialchars($settings['wa_gateway_endpoint'] ?? 'https://api.bablast.id/send-message') ?>" placeholder="https://api.bablast.id/send-message">
                                <small class="text-muted" style="font-size: 10px;">URL API untuk pengiriman pesan otomatis (Bablast / Fonnte / WooWA).</small>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Secret API Key / Token WA</label>
                                <input type="password" name="wa_api_key" class="form-control rounded-3" value="<?= htmlspecialchars($settings['wa_api_key'] ?? '') ?>" placeholder="Masukkan Secret API Token">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Nomor Pengirim (Sender)</label>
                                <input type="text" name="wa_sender_number" class="form-control rounded-3" value="<?= htmlspecialchars($settings['wa_sender_number'] ?? '081234567890') ?>" placeholder="0812xxxx">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">No WA Target CS / Admin</label>
                                <input type="text" name="support_whatsapp" class="form-control rounded-3" value="<?= htmlspecialchars($settings['support_whatsapp'] ?? '081234567890') ?>" required>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. SETUP INTEGRASI SMTP EMAIL GATEWAY (OTP & VERIFIKASI) -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100 p-4">
                        <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                            <h6 class="fw-bold text-dark m-0"><i class="bi bi-envelope-at-fill me-2 text-primary"></i>Setup SMTP Email Gateway (OTP & Notifikasi)</h6>
                            <span class="badge bg-primary-subtle text-primary rounded-pill px-2.5 py-1" style="font-size: 10px;">Email Gateway</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label small fw-bold">SMTP Host</label>
                                <input type="text" name="smtp_host" class="form-control rounded-3" value="<?= htmlspecialchars($settings['smtp_host'] ?? 'smtp.gmail.com') ?>" placeholder="smtp.gmail.com">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Port</label>
                                <input type="number" name="smtp_port" class="form-control rounded-3" value="<?= htmlspecialchars($settings['smtp_port'] ?? '587') ?>" placeholder="587">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Email Pengirim (Sender Email)</label>
                                <input type="email" name="smtp_email" class="form-control rounded-3" value="<?= htmlspecialchars($settings['smtp_email'] ?? 'no-reply@cicalengkago.id') ?>" placeholder="no-reply@cicalengkago.id">
                            </div>
                            <div class="col-md-7">
                                <label class="form-label small fw-bold">Password SMTP / App Password</label>
                                <input type="password" name="smtp_password" class="form-control rounded-3" value="<?= htmlspecialchars($settings['smtp_password'] ?? '') ?>" placeholder="••••••••••••••••">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small fw-bold">Enkripsi</label>
                                <select name="smtp_encryption" class="form-select rounded-3">
                                    <option value="tls" <?= ($settings['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : '' ?>>TLS (Port 587)</option>
                                    <option value="ssl" <?= ($settings['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL (Port 465)</option>
                                    <option value="none" <?= ($settings['smtp_encryption'] ?? '') === 'none' ? 'selected' : '' ?>>Tanpa Enkripsi (25)</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Nama Pengirim (Sender Name)</label>
                                <input type="text" name="smtp_sender_name" class="form-control rounded-3" value="<?= htmlspecialchars($settings['smtp_sender_name'] ?? 'CicalengkaGO Auth') ?>" placeholder="CicalengkaGO Auth">
                            </div>
                            <div class="col-12 mt-2">
                                <button type="button" onclick="testEmailConnection()" class="btn btn-outline-primary btn-sm rounded-pill w-100 fw-bold py-2" id="btn-test-email">
                                    <i class="bi bi-send-check-fill me-1"></i> Tes Kirim Email OTP Pengujian
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. IDENTITAS PROFIL APLIKASI & MAPS KEY -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100 p-4">
                        <h6 class="fw-bold text-dark mb-3 border-bottom pb-2"><i class="bi bi-building me-2 text-danger"></i>Profil & Identitas Aplikasi</h6>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label small fw-bold">Nama Platform Aplikasi</label>
                                <input type="text" name="business_name" class="form-control rounded-3" value="<?= htmlspecialchars($settings['business_name'] ?? 'CicalengkaGO') ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Slogan Bisnis / Tagline</label>
                                <input type="text" name="business_tagline" class="form-control rounded-3" value="<?= htmlspecialchars($settings['business_tagline'] ?? 'Platform On-Demand Super-App Cicalengka') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Simbol Mata Uang</label>
                                <input type="text" name="currency_symbol" class="form-control rounded-3" value="<?= htmlspecialchars($settings['currency_symbol'] ?? 'Rp') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Zona Waktu Sistem</label>
                                <input type="text" name="timezone" class="form-control rounded-3" value="<?= htmlspecialchars($settings['timezone'] ?? 'Asia/Jakarta') ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Google Maps API Key (Opsional)</label>
                                <input type="password" name="google_maps_api_key" class="form-control rounded-3" value="<?= htmlspecialchars($settings['google_maps_api_key'] ?? '') ?>" placeholder="AIzaSy...">
                                <small class="text-muted" style="font-size: 10px;">Jika kosong, sistem menggunakan OpenStreetMap (Leaflet JS) secara gratis.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. PENGATURAN TARIFF BIAYA & KOMISI -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100 p-4">
                        <h6 class="fw-bold text-dark mb-3 border-bottom pb-2"><i class="bi bi-cash-stack me-2 text-danger"></i>Tarif Ongkir & Komisi Platform</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Komisi Platform per Order (%)</label>
                                <input type="number" step="0.1" name="admin_commission_percent" class="form-control rounded-3" value="<?= htmlspecialchars($settings['admin_commission_percent'] ?? '15') ?>" required>
                                <small class="text-muted" style="font-size: 10px;">Potongan komisi platform dari ongkir driver.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Pajak Resto / Toko (%)</label>
                                <input type="number" step="0.1" name="tax_percent" class="form-control rounded-3" value="<?= htmlspecialchars($settings['tax_percent'] ?? '0') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Tarif Ongkir Minimal (Rp)</label>
                                <input type="number" name="delivery_charge_min" class="form-control rounded-3" value="<?= htmlspecialchars($settings['delivery_charge_min'] ?? '5000') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Tarif Ongkir per KM (Rp)</label>
                                <input type="number" name="delivery_charge_per_km" class="form-control rounded-3" value="<?= htmlspecialchars($settings['delivery_charge_per_km'] ?? '2500') ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Email Helpdesk / CS</label>
                                <input type="email" name="support_email" class="form-control rounded-3" value="<?= htmlspecialchars($settings['support_email'] ?? 'bantuan@cicalengkago.id') ?>" required>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 5. FITUR & SAKLAR OPERASIONAL -->
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-4 p-4">
                        <h6 class="fw-bold text-dark mb-3 border-bottom pb-2"><i class="bi bi-sliders me-2 text-danger"></i>Saklar Operasional & Keamanan Transaksi</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded-3">
                                    <div>
                                        <div class="fw-bold small text-dark">Mode Pemeliharaan (Maintenance Mode)</div>
                                        <small class="text-muted">Jika aktif, pelanggan tidak dapat melakukan checkout order baru.</small>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="maintenance_mode" value="1" <?= (!empty($settings['maintenance_mode']) && $settings['maintenance_mode'] == '1') ? 'checked' : '' ?> style="width: 44px; height: 22px;">
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded-3">
                                    <div>
                                        <div class="fw-bold small text-dark">Wajibkan Verifikasi OTP Pengantaran Driver</div>
                                        <small class="text-muted">Driver wajib memasukkan 4-digit OTP sebelum pesanan diselesaikan.</small>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="otp_delivery_verification" value="1" <?= (!empty($settings['otp_delivery_verification']) && $settings['otp_delivery_verification'] == '1') ? 'checked' : '' ?> style="width: 44px; height: 22px;">
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded-3">
                                    <div>
                                        <div class="fw-bold small text-dark">Pembayaran Saldo CicalengkaPay (Wallet)</div>
                                        <small class="text-muted">Izinkan pembayaran belanjaan langsung menggunakan saldo dompet.</small>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="wallet_payment_status" value="1" <?= (!empty($settings['wallet_payment_status']) && $settings['wallet_payment_status'] == '1') ? 'checked' : '' ?> style="width: 44px; height: 22px;">
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded-3">
                                    <div>
                                        <div class="fw-bold small text-dark">Pembatasan 1 Order Aktif per Driver</div>
                                        <small class="text-muted">Mencegah driver mengambil orderan baru sebelum pengantaran selesai.</small>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="single_active_order_driver" value="1" <?= (!empty($settings['single_active_order_driver']) || !isset($settings['single_active_order_driver'])) ? 'checked' : '' ?> style="width: 44px; height: 22px;">
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="d-flex align-items-center justify-content-between p-3 rounded-3 border border-danger-subtle" style="background: #fff5f5;">
                                    <div>
                                        <div class="fw-bold small text-danger"><i class="bi bi-shield-lock-fill me-1"></i> Wajibkan OTP saat Login Admin</div>
                                        <small class="text-muted">Jika aktif, login Admin/Vendor/Driver selalu memerlukan verifikasi kode OTP via email.</small>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="require_login_otp" id="toggle-login-otp" value="1" <?= (!empty($settings['require_login_otp']) && $settings['require_login_otp'] == '1') ? 'checked' : '' ?> style="width: 44px; height: 22px;">
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="d-flex align-items-center justify-content-between p-3 rounded-3 border border-primary-subtle" style="background: #f0f5ff;">
                                    <div>
                                        <div class="fw-bold small text-primary"><i class="bi bi-person-check-fill me-1"></i> Wajibkan OTP saat Login Customer</div>
                                        <small class="text-muted">Jika aktif, setiap login pelanggan (customer) memerlukan verifikasi OTP via email.</small>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="require_customer_otp" id="toggle-customer-otp" value="1" <?= (!empty($settings['require_customer_otp']) && $settings['require_customer_otp'] == '1') ? 'checked' : '' ?> style="width: 44px; height: 22px;">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Button Bar -->
                <div class="col-12 text-end mt-2">
                    <button type="submit" class="btn text-white rounded-pill px-5 py-2.5 fw-bold shadow-sm fs-6" style="background: #EE2737;">
                        <i class="bi bi-floppy2-fill me-2"></i> Simpan Semua Setup & Pengaturan System
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
async function testMidtransConnection() {
    const btn = document.getElementById('btn-test-midtrans');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Menghubungi Server Midtrans...';

    try {
        const res = await fetch(window.BASE_URL + '/admin/midtrans/test-connection', {
            method: 'POST'
        });
        const data = await res.json();

        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Midtrans API Terhubung! 🎉',
                html: `<div class="text-start small">
                    <p class="mb-1"><b>Status:</b> ${data.message}</p>
                    <p class="mb-1"><b>Environment Mode:</b> <span class="badge bg-success">${data.environment}</span></p>
                    <p class="mb-0"><b>Merchant ID:</b> <code>${data.merchant_id}</code></p>
                </div>`
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Koneksi Midtrans Gagal',
                text: data.message || 'Gagal terhubung ke Midtrans. Periksa kembali Server Key Anda.'
            });
        }
    } catch (err) {
        Swal.fire('Error', 'Terjadi kesalahan saat menguji koneksi API.', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

async function testEmailConnection() {
    const { value: email } = await Swal.fire({
        title: 'Kirim Email OTP Test',
        input: 'email',
        inputLabel: 'Masukkan alamat email penerima pengujian:',
        inputPlaceholder: 'admin@cicalengkago.id',
        showCancelButton: true,
        confirmButtonText: '<i class="bi bi-send-fill me-1"></i> Kirim Email',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#EE2737'
    });

    if (!email) return;

    const btn = document.getElementById('btn-test-email');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Mengirim Email via Server SMTP...';

    try {
        const formData = new FormData();
        formData.append('test_email', email);

        const res = await fetch(window.BASE_URL + '/admin/email/test-send', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Email Pengujian Terkirim! 🎉',
                text: data.message
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Pengiriman Email Gagal',
                text: data.message || 'Gagal terhubung ke SMTP server.'
            });
        }
    } catch (err) {
        Swal.fire('Error', 'Terjadi kesalahan saat menguji gateway email.', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}
</script>

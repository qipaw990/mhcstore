<?php
$title = 'Setup Pembayaran Otomatis & Bank - CicalengkaGO Admin';
$active_tab = 'payment_methods';
?>

<div class="content-wrapper p-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1" style="color: #0f172a;">
                <i class="bi bi-bank2 text-danger me-2"></i>Setup Pembayaran Mandiri (0% Fee)
            </h3>
            <p class="text-muted mb-0 small">Kelola rekening bank transfer, QRIS dinamis otomatis, webhook mutasi, dan verifikasi invoice.</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-danger btn-sm rounded-3 px-3" data-bs-toggle="modal" data-bs-target="#webhookTesterModal">
                <i class="bi bi-lightning-charge-fill me-1"></i>Test Webhook Scraper
            </button>
            <button class="btn btn-danger btn-sm rounded-3 px-3" data-bs-toggle="modal" data-bs-target="#addBankModal">
                <i class="bi bi-plus-circle me-1"></i>Tambah Rekening Bank
            </button>
        </div>
    </div>

    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-3 small" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($_SESSION['flash_success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-3 small" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($_SESSION['flash_error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <!-- Nav Tabs -->
    <ul class="nav nav-pills custom-pills mb-4 bg-white p-2 rounded-4 border" id="paymentTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active rounded-3 px-3 py-2 fw-semibold small" id="banks-tab" data-bs-toggle="tab" data-bs-target="#banks" type="button">
                <i class="bi bi-credit-card-2-front me-2"></i>Rekening Bank & E-Wallet
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-3 px-3 py-2 fw-semibold small" id="qris-tab" data-bs-toggle="tab" data-bs-target="#qris" type="button">
                <i class="bi bi-qr-code-scan me-2"></i>QRIS Dinamis Otomatis
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-3 px-3 py-2 fw-semibold small" id="invoices-tab" data-bs-toggle="tab" data-bs-target="#invoices" type="button">
                <i class="bi bi-receipt me-2"></i>Monitor Tagihan & Invoice
                <?php if ($pendingCount > 0): ?>
                    <span class="badge bg-danger ms-1"><?= $pendingCount ?></span>
                <?php endif; ?>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-3 px-3 py-2 fw-semibold small" id="webhook-tab" data-bs-toggle="tab" data-bs-target="#webhook" type="button">
                <i class="bi bi-broadcast-pin me-2"></i>Integrasi Webhook & MacroDroid
            </button>
        </li>
    </ul>

    <div class="tab-content" id="paymentTabContent">
        <!-- Tab 1: Rekening Bank & E-Wallet -->
        <div class="tab-pane fade show active" id="banks" role="tabpanel">
            <div class="row g-4">
                <?php foreach ($banks as $idx => $b): ?>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <span class="badge <?= $b['type'] === 'qris' ? 'bg-danger' : ($b['type'] === 'ewallet' ? 'bg-primary' : 'bg-dark') ?> px-2 py-1 rounded-2 text-uppercase fw-bold" style="font-size: 10px;">
                                    <?= htmlspecialchars($b['type']) ?>
                                </span>
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-2" style="font-size: 11px;">
                                    <i class="bi bi-check-circle-fill me-1"></i>Aktif
                                </span>
                            </div>
                            <h5 class="fw-bold mb-1 text-dark"><?= htmlspecialchars($b['name']) ?></h5>
                            <p class="text-muted small mb-3"><?= htmlspecialchars($b['description'] ?? '') ?></p>

                            <div class="bg-light p-3 rounded-3 mb-3 border border-dashed">
                                <div class="text-muted small mb-1">Nomor Rekening / Akun:</div>
                                <div class="fw-bold fs-5 text-danger font-monospace"><?= htmlspecialchars($b['account_number']) ?></div>
                                <div class="text-secondary small mt-1">a.n <?= htmlspecialchars($b['account_name']) ?></div>
                            </div>

                            <div class="mt-auto d-flex gap-2">
                                <button class="btn btn-outline-secondary btn-sm w-100 rounded-3" onclick="editBank(<?= htmlspecialchars(json_encode($b)) ?>)">
                                    <i class="bi bi-pencil me-1"></i>Ubah
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Tab 2: QRIS Dinamis Otomatis -->
        <div class="tab-pane fade" id="qris" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <div class="row g-4 align-items-center">
                    <div class="col-md-7">
                        <h5 class="fw-bold text-dark mb-2">QRIS Standar Nasional (EMVCo Dynamic Generator)</h5>
                        <p class="text-muted small">
                            Sistem secara otomatis menyisipkan nominal tagihan pas (+ kode unik) ke dalam kode QRIS. Pelanggan cukup scan dan nominal langsung terisi otomatis di aplikasi m-Banking / E-Wallet mereka.
                        </p>
                        <form action="<?= $baseUrl ?>/admin/payment-methods/save-qris" method="POST">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Nama Merchant QRIS</label>
                                <input type="text" name="qris_merchant_name" class="form-control rounded-3" value="<?= htmlspecialchars($qrisSettings['merchant_name'] ?? 'CICALENGKAGO') ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Kota Merchant</label>
                                <input type="text" name="qris_city" class="form-control rounded-3" value="<?= htmlspecialchars($qrisSettings['city'] ?? 'KAB BANDUNG') ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">NMID / ID National Merchant</label>
                                <input type="text" name="qris_nmid" class="form-control rounded-3" value="<?= htmlspecialchars($qrisSettings['nmid'] ?? 'ID1024328492048') ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Payload QRIS Statis Dasar (EMVCo String)</label>
                                <textarea name="qris_static_payload" rows="3" class="form-control rounded-3 font-monospace small" placeholder="000201010211..."><?= htmlspecialchars($qrisSettings['static_payload'] ?? '00020101021226670014ID.GO.CICAGO.WWW01189360091800000000000215ID10243284920480303UMI51440014ID.CO.QRIS.WWW0215ID10243284920480303UMI5204581253033605802ID5914CICALENGKAGO6010KAB BANDUNG610540395') ?></textarea>
                            </div>
                            <button type="submit" class="btn btn-danger btn-sm rounded-3 px-4 fw-bold">
                                <i class="bi bi-save me-1"></i>Simpan Pengaturan QRIS
                            </button>
                        </form>
                    </div>
                    <div class="col-md-5 text-center">
                        <div class="p-3 bg-light rounded-4 border d-inline-block">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=<?= urlencode($qrisSettings['static_payload'] ?? 'CICALENGKAGO') ?>" class="img-fluid rounded-3 mb-2" style="max-width: 200px;">
                            <div class="small fw-bold text-dark"><?= htmlspecialchars($qrisSettings['merchant_name'] ?? 'CICALENGKAGO') ?></div>
                            <div class="text-muted" style="font-size: 11px;">Mendukung BCA, Mandiri, BRI, GoPay, DANA, ShopeePay</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab 3: Monitor Tagihan & Invoice -->
        <div class="tab-pane fade" id="invoices" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark">Daftar Tagihan Pembayaran Mandiri</h6>
                    <span class="text-muted small">Total: <?= count($invoices) ?> invoice</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th>Invoice</th>
                                <th>Pelanggan</th>
                                <th>Tipe</th>
                                <th>Metode / Bank</th>
                                <th>Nominal Asli</th>
                                <th>Kode Unik</th>
                                <th>Total Transfer</th>
                                <th>Status</th>
                                <th>Waktu</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($invoices)): ?>
                                <tr>
                                    <td colspan="10" class="text-center py-4 text-muted">Belum ada invoice pembayaran.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($invoices as $inv): ?>
                                    <tr>
                                        <td class="font-monospace fw-bold"><?= htmlspecialchars($inv['invoice_code']) ?></td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= htmlspecialchars($inv['user_name'] ?? 'User #' . $inv['user_id']) ?></div>
                                            <div class="text-muted" style="font-size: 11px;"><?= htmlspecialchars($inv['user_phone'] ?? '') ?></div>
                                        </td>
                                        <td><span class="badge bg-secondary-subtle text-secondary px-2 py-1"><?= strtoupper($inv['type']) ?></span></td>
                                        <td><span class="badge bg-danger-subtle text-danger fw-bold"><?= htmlspecialchars($inv['bank_name']) ?></span></td>
                                        <td>Rp <?= number_format($inv['base_amount'], 0, ',', '.') ?></td>
                                        <td><span class="badge bg-success-subtle text-success fw-bold">+<?= $inv['unique_code'] ?></span></td>
                                        <td class="fw-bold text-danger fs-6 font-monospace">Rp <?= number_format($inv['total_amount'], 0, ',', '.') ?></td>
                                        <td>
                                            <?php if ($inv['status'] === 'paid'): ?>
                                                <span class="badge bg-success px-2 py-1"><i class="bi bi-check-circle me-1"></i>Lunas</span>
                                            <?php elseif ($inv['status'] === 'expired'): ?>
                                                <span class="badge bg-secondary px-2 py-1">Kedaluwarsa</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark px-2 py-1"><i class="bi bi-clock me-1"></i>Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-muted" style="font-size: 11px;"><?= date('d M Y H:i', strtotime($inv['created_at'])) ?></td>
                                        <td class="text-center">
                                            <?php if ($inv['status'] === 'pending'): ?>
                                                <form action="<?= $baseUrl ?>/admin/payment-invoices/approve" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin mengkonfirmasi pembayaran invoice ini secara manual?')">
                                                    <input type="hidden" name="invoice_code" value="<?= htmlspecialchars($inv['invoice_code']) ?>">
                                                    <button type="submit" class="btn btn-success btn-sm rounded-3 px-2 py-1" title="Konfirmasi Manual">
                                                        <i class="bi bi-check-lg"></i> Approve
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab 4: Integrasi Webhook & MacroDroid -->
        <div class="tab-pane fade" id="webhook" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                <h5 class="fw-bold text-dark mb-3"><i class="bi bi-robot text-danger me-2"></i>Panduan Setup Auto-Approve Mutasi (MacroDroid / SMS Forwarder)</h5>
                <p class="text-muted small">
                    Dengan memasang aplikasi <strong>MacroDroid</strong> di HP Android Anda, setiap ada notifikasi SMS Banking atau notifikasi m-Banking (BCA Mobile, Livin, BRImo, DANA, GoPay) yang masuk, tagihan akan otomatis lunas dalam hitungan detik.
                </p>

                <div class="bg-light p-3 rounded-3 border mb-4">
                    <label class="form-label small fw-bold text-dark">URL Webhook Notifikasi Server CicalengkaGO:</label>
                    <div class="input-group">
                        <input type="text" id="webhookUrlInput" class="form-control font-monospace bg-white text-danger fw-bold small" value="<?= $baseUrl ?>/api/payment/auto-webhook" readonly>
                        <button class="btn btn-outline-danger" onclick="copyWebhookUrl()"><i class="bi bi-clipboard me-1"></i>Salin URL</button>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="p-3 border rounded-3 h-100 bg-white">
                            <div class="badge bg-danger mb-2">Langkah 1</div>
                            <h6 class="fw-bold text-dark">Pasang MacroDroid</h6>
                            <p class="text-muted small mb-0">Install aplikasi MacroDroid di HP Android yang terpasang SIM card SMS Banking atau aplikasi BCA/Mandiri/BRI.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 border rounded-3 h-100 bg-white">
                            <div class="badge bg-danger mb-2">Langkah 2</div>
                            <h6 class="fw-bold text-dark">Set Trigger Notifikasi</h6>
                            <p class="text-muted small mb-0">Pilih <em>Notification Received</em> untuk aplikasi Bank Anda atau <em>SMS Received</em>.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 border rounded-3 h-100 bg-white">
                            <div class="badge bg-danger mb-2">Langkah 3</div>
                            <h6 class="fw-bold text-dark">Set HTTP POST Request</h6>
                            <p class="text-muted small mb-0">Kirim HTTP POST ke Webhook URL di atas dengan Body JSON: <code>{"text": "[notif_message]"}</code>.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit / Add Bank -->
<div class="modal fade" id="addBankModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="bankModalTitle">Tambah Rekening Bank</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= $baseUrl ?>/admin/payment-methods/save-bank" method="POST">
                <input type="hidden" name="bank_id" id="bankIdInput" value="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Kode Bank (ID)</label>
                        <input type="text" name="code" id="bankCodeInput" class="form-control rounded-3" placeholder="Misal: BCA, BRI, BNI, MANDIRI, DANA" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nama Bank / Metode</label>
                        <input type="text" name="name" id="bankNameInput" class="form-control rounded-3" placeholder="Misal: Bank Central Asia (BCA)" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nomor Rekening / No. E-Wallet</label>
                        <input type="text" name="account_number" id="bankAccNumInput" class="form-control rounded-3" placeholder="Misal: 1380721839" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Atas Nama Pemilik Rekening</label>
                        <input type="text" name="account_name" id="bankAccNameInput" class="form-control rounded-3" placeholder="Misal: CICALENGKA MEDIA SOLUSI" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Tipe Metode</label>
                        <select name="type" id="bankTypeInput" class="form-select rounded-3">
                            <option value="bank">Transfer Bank</option>
                            <option value="ewallet">E-Wallet (DANA, GoPay, OVO)</option>
                            <option value="qris">QRIS Standar</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light rounded-3 btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger rounded-3 btn-sm px-4">Simpan Rekening</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Webhook Tester -->
<div class="modal fade" id="webhookTesterModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-lightning-charge-fill text-danger me-2"></i>Simulator Webhook Notifikasi Bank</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= $baseUrl ?>/admin/payment-methods/test-webhook" method="POST">
                <div class="modal-body">
                    <p class="text-muted small">Gunakan form ini untuk mensimulasikan notifikasi transfer masuk dan memastikan sistem auto-approve bekerja dengan benar.</p>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nominal Transfer Masuk (Beserta Kode Unik)</label>
                        <input type="number" name="amount" class="form-control rounded-3 fw-bold text-danger font-monospace" placeholder="Misal: 50284" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nama Pengirim / Bank</label>
                        <input type="text" name="sender" class="form-control rounded-3" value="AHMAD SYAFIQ - BCA" placeholder="Misal: AHMAD - BCA">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Contoh Teks Notifikasi SMS / Push Notif (Opsional)</label>
                        <textarea name="text" rows="2" class="form-control rounded-3 small font-monospace" placeholder="Transfer masuk Rp 50.284 dari REK BCA 123456 AHMAD"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light rounded-3 btn-sm" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-danger rounded-3 btn-sm px-4">Kirim Simulasi Webhook</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function copyWebhookUrl() {
    var copyText = document.getElementById("webhookUrlInput");
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(copyText.value);
    alert("URL Webhook berhasil disalin: " + copyText.value);
}

function editBank(bank) {
    document.getElementById('bankModalTitle').innerText = 'Edit Rekening ' + bank.name;
    document.getElementById('bankIdInput').value = bank.id || bank.code;
    document.getElementById('bankCodeInput').value = bank.code;
    document.getElementById('bankNameInput').value = bank.name;
    document.getElementById('bankAccNumInput').value = bank.account_number;
    document.getElementById('bankAccNameInput').value = bank.account_name;
    document.getElementById('bankTypeInput').value = bank.type;
    var myModal = new bootstrap.Modal(document.getElementById('addBankModal'));
    myModal.show();
}
</script>

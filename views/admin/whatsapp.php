<?php
$waGatewayUrl = \App\Models\BusinessSetting::get('whatsapp_gateway_url', 'http://localhost:3001');
$waSecret     = \App\Models\BusinessSetting::get('whatsapp_gateway_secret', 'cicago_wa_secret_2024');
$waEnabled    = \App\Models\BusinessSetting::get('whatsapp_otp_enabled', '1') === '1';
$casaosUrl    = \App\Models\BusinessSetting::get('whatsapp_casaos_url', '');
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1 d-flex align-items-center gap-2">
            <span class="rounded-3 p-2 d-inline-flex" style="background:#dcfce7">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="#16a34a"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg>
            </span>
            WhatsApp Gateway
        </h4>
        <p class="text-muted small mb-0">Monitor koneksi, scan QR Code, dan kirim OTP melalui WhatsApp.</p>
    </div>
    <div class="d-flex gap-2">
        <button onclick="refreshStatus()" class="btn btn-outline-secondary btn-sm rounded-3">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh Status
        </button>
        <?php if (!empty($casaosUrl)): ?>
        <a href="<?= htmlspecialchars($casaosUrl) ?>" target="_blank" class="btn btn-sm rounded-3 text-white fw-bold" style="background:#2563eb">
            <i class="bi bi-server me-1"></i> Buka CasaOS
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Status Cards Row -->
<div class="row g-3 mb-4">
    <!-- Connection Status Card -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 bg-white h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div id="status-icon-wrap" class="rounded-4 p-3 fs-3" style="background:#f1f5f9">
                        <i class="bi bi-wifi-off text-muted"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold mb-1">Status Koneksi</div>
                        <div id="status-badge" class="badge rounded-pill px-3 py-1 fw-bold" style="background:#e2e8f0;color:#64748b;font-size:12px">
                            Memeriksa...
                        </div>
                    </div>
                </div>
                <div id="status-detail" class="text-muted small">Menghubungi gateway...</div>
                <hr class="my-3">
                <div class="d-flex gap-2 flex-wrap">
                    <a href="<?= htmlspecialchars($waGatewayUrl) ?>/qr" target="_blank" 
                       class="btn btn-sm btn-success rounded-3 fw-bold" style="font-size:12px">
                        <i class="bi bi-qr-code me-1"></i> Scan QR Code
                    </a>
                    <button onclick="restartGateway()" class="btn btn-sm btn-outline-warning rounded-3 fw-bold" style="font-size:12px">
                        <i class="bi bi-arrow-repeat me-1"></i> Restart
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- OTP Setting Card -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 bg-white h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-4 p-3 fs-3 bg-primary-subtle text-primary">
                        <i class="bi bi-shield-lock-fill"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold mb-1">OTP via WhatsApp</div>
                        <div class="fw-bold <?= $waEnabled ? 'text-success' : 'text-danger' ?>">
                            <?= $waEnabled ? '✅ Aktif' : '❌ Nonaktif' ?>
                        </div>
                    </div>
                </div>
                <p class="small text-muted mb-3">
                    Jika aktif, sistem akan mengirim kode OTP via WhatsApp ke nomor HP pengguna. Jika gagal, otomatis fallback ke Email.
                </p>
                <form method="POST" action="<?= $baseUrl ?>/admin/whatsapp/toggle-otp" class="d-inline">
                    <button type="submit" class="btn btn-sm rounded-3 fw-bold <?= $waEnabled ? 'btn-outline-danger' : 'btn-success' ?>" style="font-size:12px">
                        <i class="bi bi-<?= $waEnabled ? 'toggle-off' : 'toggle-on' ?> me-1"></i>
                        <?= $waEnabled ? 'Nonaktifkan OTP WA' : 'Aktifkan OTP WA' ?>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Test Message Card -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 bg-white h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-4 p-3 fs-3 bg-warning-subtle text-warning">
                        <i class="bi bi-send-fill"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold mb-1">Test Kirim OTP</div>
                        <div class="text-dark fw-bold small">Uji coba pengiriman</div>
                    </div>
                </div>
                <div class="mb-2">
                    <input type="text" id="test-phone" class="form-control form-control-sm rounded-3" 
                           placeholder="Nomor WA (contoh: 08123456789)" style="font-size:13px">
                </div>
                <button onclick="sendTestOtp()" class="btn btn-sm btn-warning rounded-3 fw-bold w-100" style="font-size:12px">
                    <i class="bi bi-whatsapp me-1"></i> Kirim OTP Test
                </button>
            </div>
        </div>
    </div>
</div>

<!-- QR Code Embed + Settings Row -->
<div class="row g-3 mb-4">
    <!-- QR Code Embed -->
    <div class="col-md-5">
        <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
            <div class="card-header bg-white border-0 py-3 px-4 d-flex align-items-center justify-content-between">
                <div class="fw-bold d-flex align-items-center gap-2">
                    <i class="bi bi-qr-code fs-5 text-dark"></i> QR Code Scanner
                </div>
                <a href="<?= htmlspecialchars($waGatewayUrl) ?>/qr" target="_blank" class="btn btn-sm btn-outline-secondary rounded-3" style="font-size:12px">
                    <i class="bi bi-box-arrow-up-right me-1"></i> Buka Fullscreen
                </a>
            </div>
            <div class="card-body p-0">
                <iframe id="wa-qr-frame"
                        src="<?= htmlspecialchars($waGatewayUrl) ?>/qr"
                        style="width:100%;height:380px;border:none"
                        title="WhatsApp QR Code">
                </iframe>
            </div>
            <div class="card-footer bg-light border-top py-2 px-4">
                <small class="text-muted">
                    <i class="bi bi-info-circle me-1"></i>
                    Buka WhatsApp → Menu ⋮ → <strong>Perangkat Tertaut</strong> → Tautkan Perangkat → Scan QR di atas
                </small>
            </div>
        </div>
    </div>

    <!-- Gateway Settings -->
    <div class="col-md-7">
        <div class="card border-0 shadow-sm rounded-4 bg-white">
            <div class="card-header bg-white border-0 py-3 px-4">
                <div class="fw-bold d-flex align-items-center gap-2">
                    <i class="bi bi-sliders fs-5 text-primary"></i> Konfigurasi Gateway
                </div>
            </div>
            <div class="card-body px-4 pb-4">
                <form method="POST" action="<?= $baseUrl ?>/admin/whatsapp/save-settings">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">URL Gateway</label>
                        <input type="url" name="whatsapp_gateway_url" class="form-control rounded-3" 
                               value="<?= htmlspecialchars($waGatewayUrl) ?>"
                               placeholder="http://localhost:3001">
                        <div class="form-text">URL Node.js gateway yang berjalan. Lokal: <code>http://localhost:3001</code> | CasaOS: <code>http://&lt;ip-server&gt;:3001</code></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Secret Key</label>
                        <div class="input-group">
                            <input type="text" name="whatsapp_gateway_secret" id="wa-secret-input"
                                   class="form-control rounded-start-3 font-monospace" 
                                   value="<?= htmlspecialchars($waSecret) ?>">
                            <button type="button" class="btn btn-outline-secondary rounded-end-3" onclick="regenerateSecret()">
                                <i class="bi bi-arrow-repeat"></i>
                            </button>
                        </div>
                        <div class="form-text">Harus sama dengan <code>WA_GATEWAY_SECRET</code> di file <code>.env</code> gateway</div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold small">URL CasaOS Dashboard <span class="text-muted fw-normal">(opsional)</span></label>
                        <input type="url" name="whatsapp_casaos_url" class="form-control rounded-3" 
                               value="<?= htmlspecialchars($casaosUrl) ?>"
                               placeholder="http://192.168.x.x:8080">
                        <div class="form-text">Untuk tombol shortcut "Buka CasaOS" di header halaman ini</div>
                    </div>
                    <button type="submit" class="btn btn-primary rounded-3 fw-bold px-4">
                        <i class="bi bi-save me-1"></i> Simpan Konfigurasi
                    </button>
                </form>
            </div>
        </div>

        <!-- CasaOS Deploy Guide -->
        <div class="card border-0 shadow-sm rounded-4 mt-3" style="background: linear-gradient(135deg,#0f172a,#1e293b)">
            <div class="card-body p-4">
                <h6 class="fw-bold text-white mb-3">
                    <i class="bi bi-server me-2 text-primary"></i> Deploy ke CasaOS
                </h6>
                <div class="small text-slate-300" style="color:#94a3b8;line-height:1.8">
                    <strong class="text-white">1.</strong> Upload folder <code style="color:#38bdf8">whatsapp-gateway/</code> ke server CasaOS<br>
                    <strong class="text-white">2.</strong> Di CasaOS → App Store → <em>Custom Install</em><br>
                    <strong class="text-white">3.</strong> Paste docker-compose dari file <code style="color:#38bdf8">docker-compose.yml</code><br>
                    <strong class="text-white">4.</strong> Ubah URL Gateway di atas menjadi <code style="color:#38bdf8">http://&lt;ip-casaos&gt;:3001</code><br>
                    <strong class="text-white">5.</strong> Scan QR Code sekali → session tersimpan permanen
                </div>
                <div class="mt-3 d-flex gap-2 flex-wrap">
                    <a href="<?= $baseUrl ?>/admin/whatsapp/download-compose" 
                       class="btn btn-sm btn-primary rounded-3 fw-bold" style="font-size:12px">
                        <i class="bi bi-download me-1"></i> Download docker-compose.yml
                    </a>
                    <a href="<?= $baseUrl ?>/admin/whatsapp/download-dockerfile"
                       class="btn btn-sm btn-outline-light rounded-3 fw-bold" style="font-size:12px">
                        <i class="bi bi-file-code me-1"></i> Download Dockerfile
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Send Custom Message -->
<div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
    <div class="card-header bg-white border-0 py-3 px-4 d-flex align-items-center gap-2">
        <i class="bi bi-chat-dots-fill text-success fs-5"></i>
        <span class="fw-bold">Kirim Pesan WhatsApp Manual</span>
    </div>
    <div class="card-body px-4 pb-4">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label fw-semibold small">Nomor HP Tujuan</label>
                <input type="text" id="manual-phone" class="form-control rounded-3" placeholder="08123456789">
            </div>
            <div class="col-md-8">
                <label class="form-label fw-semibold small">Pesan <span class="text-muted fw-normal">(bisa *bold*, _italic_)</span></label>
                <div class="input-group">
                    <textarea id="manual-message" class="form-control rounded-start-3" rows="1" 
                              placeholder="Tulis pesan WhatsApp..."></textarea>
                    <button onclick="sendManualMessage()" class="btn btn-success rounded-end-3 fw-bold px-4">
                        <i class="bi bi-send-fill me-1"></i> Kirim
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const WA_GATEWAY_URL = '<?= htmlspecialchars($waGatewayUrl) ?>';
const WA_SECRET = '<?= htmlspecialchars($waSecret) ?>';
const ADMIN_BASE = window.BASE_URL || '';

// ── Status Polling ───────────────────────────────────────
function refreshStatus() {
    const badge = document.getElementById('status-badge');
    const detail = document.getElementById('status-detail');
    const iconWrap = document.getElementById('status-icon-wrap');

    badge.textContent = 'Memeriksa...';
    badge.style.cssText = 'background:#e2e8f0;color:#64748b;font-size:12px';

    fetch(ADMIN_BASE + '/admin/whatsapp/status')
        .then(r => r.json())
        .then(data => {
            if (data.ready) {
                badge.textContent = '🟢 Terhubung & Siap';
                badge.style.cssText = 'background:#dcfce7;color:#16a34a;font-size:12px';
                iconWrap.style.background = '#dcfce7';
                iconWrap.innerHTML = '<i class="bi bi-whatsapp" style="color:#16a34a"></i>';
                detail.textContent = 'WhatsApp gateway aktif dan siap mengirim pesan OTP.';
            } else if (data.status === 'QR_READY') {
                badge.textContent = '🟡 Menunggu Scan QR';
                badge.style.cssText = 'background:#fef9c3;color:#b45309;font-size:12px';
                iconWrap.style.background = '#fef9c3';
                iconWrap.innerHTML = '<i class="bi bi-qr-code" style="color:#b45309"></i>';
                detail.textContent = 'Silakan scan QR Code di panel kiri untuk menghubungkan WhatsApp.';
                // Refresh iframe
                document.getElementById('wa-qr-frame').src = WA_GATEWAY_URL + '/qr?' + Date.now();
            } else if (data.status === 'INITIALIZING') {
                badge.textContent = '⏳ Menginisialisasi';
                badge.style.cssText = 'background:#fef9c3;color:#b45309;font-size:12px';
                detail.textContent = 'Gateway sedang startup, harap tunggu beberapa saat...';
            } else {
                badge.textContent = '🔴 ' + (data.status || 'Offline');
                badge.style.cssText = 'background:#fee2e2;color:#dc2626;font-size:12px';
                iconWrap.style.background = '#fee2e2';
                iconWrap.innerHTML = '<i class="bi bi-wifi-off" style="color:#dc2626"></i>';
                detail.textContent = 'Gateway tidak tersedia. Pastikan Node.js gateway sudah berjalan.';
            }
        })
        .catch(() => {
            badge.textContent = '🔴 Gateway Offline';
            badge.style.cssText = 'background:#fee2e2;color:#dc2626;font-size:12px';
            iconWrap.style.background = '#fee2e2';
            iconWrap.innerHTML = '<i class="bi bi-wifi-off" style="color:#dc2626"></i>';
            detail.textContent = 'Tidak bisa terhubung ke gateway. Jalankan start.bat atau Docker container.';
        });
}

// ── Test OTP ─────────────────────────────────────────────
async function sendTestOtp() {
    const phone = document.getElementById('test-phone').value.trim();
    if (!phone) { Swal.fire('Peringatan','Masukkan nomor HP tujuan.','warning'); return; }

    const { isConfirmed } = await Swal.fire({
        title: 'Kirim OTP Test?',
        html: `Kode OTP test akan dikirim ke: <strong>${phone}</strong>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Kirim',
        confirmButtonColor: '#16a34a',
        cancelButtonText: 'Batal'
    });
    if (!isConfirmed) return;

    Swal.fire({ title: 'Mengirim...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    try {
        const res = await fetch(ADMIN_BASE + '/admin/whatsapp/send-test', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ phone })
        });
        const data = await res.json();
        if (data.success) {
            Swal.fire({ title: 'Berhasil!', text: 'OTP test berhasil dikirim ke ' + phone, icon: 'success', confirmButtonColor: '#16a34a' });
        } else {
            Swal.fire('Gagal', data.message || 'Gagal mengirim OTP.', 'error');
        }
    } catch (e) {
        Swal.fire('Error', 'Koneksi ke gateway gagal.', 'error');
    }
}

// ── Manual Message ────────────────────────────────────────
async function sendManualMessage() {
    const phone   = document.getElementById('manual-phone').value.trim();
    const message = document.getElementById('manual-message').value.trim();
    if (!phone || !message) { Swal.fire('Peringatan', 'Nomor HP dan pesan wajib diisi.', 'warning'); return; }

    Swal.fire({ title: 'Mengirim...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    try {
        const res = await fetch(ADMIN_BASE + '/admin/whatsapp/send-message', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ phone, message })
        });
        const data = await res.json();
        if (data.success) {
            Swal.fire({ title: 'Terkirim!', text: 'Pesan berhasil dikirim ke ' + phone, icon: 'success', confirmButtonColor: '#16a34a' });
            document.getElementById('manual-message').value = '';
        } else {
            Swal.fire('Gagal', data.message || 'Gagal mengirim pesan.', 'error');
        }
    } catch (e) {
        Swal.fire('Error', 'Koneksi ke gateway gagal.', 'error');
    }
}

// ── Restart Gateway ───────────────────────────────────────
async function restartGateway() {
    const { isConfirmed } = await Swal.fire({
        title: 'Restart Gateway?',
        text: 'WhatsApp akan terputus sementara dan memerlukan scan QR ulang jika sesi hilang.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Restart',
        confirmButtonColor: '#f59e0b'
    });
    if (!isConfirmed) return;

    try {
        const res = await fetch(ADMIN_BASE + '/admin/whatsapp/restart', { method: 'POST' });
        const data = await res.json();
        Swal.fire({ title: 'Berhasil', text: data.message || 'Gateway sedang restart...', icon: 'success' });
        setTimeout(refreshStatus, 5000);
    } catch {
        Swal.fire('Error', 'Gagal me-restart gateway.', 'error');
    }
}

// ── Regenerate Secret ─────────────────────────────────────
function regenerateSecret() {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    let s = 'cicago_';
    for (let i = 0; i < 24; i++) s += chars[Math.floor(Math.random() * chars.length)];
    document.getElementById('wa-secret-input').value = s;
}

// Auto refresh status on load
document.addEventListener('DOMContentLoaded', refreshStatus);
</script>

<div class="text-center py-3">
    <div class="d-inline-flex align-items-center justify-content-center bg-success-subtle text-success rounded-circle mb-3 shadow-sm" style="width: 64px; height: 64px;">
        <i class="bi bi-clock-history fs-2 text-success"></i>
    </div>
    
    <h5 class="fw-bold text-dark mb-2">Pendaftaran Toko Diterima!</h5>
    <div class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-3 py-1.5 rounded-pill mb-3" style="font-size: 11.5px;">
        ⏳ Menunggu Review Tim Admin
    </div>

    <div class="p-3 bg-light rounded-4 border text-start mb-3" style="font-size: 12px; line-height: 1.5; color: #334155;">
        <p class="mb-2">
            Terima kasih telah mendaftarkan <strong><?= htmlspecialchars($storeName ?? 'Toko Anda') ?></strong> sebagai Mitra Merchant di CicalengkaGO.
        </p>
        <p class="mb-0 text-muted">
            Data Anda saat ini sedang dalam proses peninjauan oleh Tim Admin CicalengkaGO. Anda akan menerima pesan konfirmasi WhatsApp dan dapat langsung login untuk membuka toko setelah akun disetujui.
        </p>
    </div>

    <div class="d-grid gap-2">
        <a href="https://wa.me/6285158397756?text=Halo%20Admin%20CicalengkaGO%2C%20saya%20baru%20saja%20mendaftarkan%20toko%20'<?= urlencode($storeName ?? 'Toko') ?>'%20dan%20ingin%20mengonfirmasi%20pendaftaran." 
           target="_blank" 
           class="btn btn-success rounded-pill fw-bold py-2 shadow-sm" style="font-size: 12.5px;">
            <i class="bi bi-whatsapp me-1"></i> Konfirmasi ke CS Admin WhatsApp
        </a>
        
        <a href="<?= $baseUrl ?>/login" class="btn btn-outline-secondary rounded-pill fw-bold py-2" style="font-size: 12.5px;">
            <i class="bi bi-box-arrow-in-right me-1"></i> Kembali ke Halaman Masuk
        </a>
    </div>
</div>

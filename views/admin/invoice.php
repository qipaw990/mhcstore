<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Pesanan #<?= htmlspecialchars($order['order_code']) ?> - CicalengkaGO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background-color: #f8fafc;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            color: #1e293b;
        }
        .invoice-card {
            max-width: 800px;
            margin: 30px auto;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            padding: 40px;
        }
        @media print {
            body { background: #fff; }
            .invoice-card { box-shadow: none; padding: 0; margin: 0; max-width: 100%; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="container my-4">
    <!-- Action Bar -->
    <div class="d-flex justify-content-between align-items-center mb-3 no-print max-w-800 mx-auto" style="max-width: 800px;">
        <a href="<?= $baseUrl ?>/admin/orders" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
        </a>
        <button onclick="window.print()" class="btn btn-primary btn-sm rounded-pill px-4 fw-bold shadow-sm">
            <i class="bi bi-printer-fill me-1"></i> Cetak / Simpan PDF
        </button>
    </div>

    <!-- Invoice Card -->
    <div class="invoice-card">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-start border-bottom pb-4 mb-4">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="fs-4 fw-black text-primary">⚡ Cicalengka<span class="text-dark">GO</span></span>
                    <span class="badge bg-primary-subtle text-primary fw-bold px-2 py-1" style="font-size: 11px;">OFFICIAL RECEIPT</span>
                </div>
                <div class="text-muted small">Platform On-Demand Super-App Terpadu Cicalengka</div>
                <div class="text-muted small">support@cicalengkago.id • +62 812-3456-7890</div>
            </div>
            <div class="text-end">
                <h5 class="fw-bold text-dark mb-1">INVOICE</h5>
                <div class="fw-bold text-primary fs-6">#<?= htmlspecialchars($order['order_code']) ?></div>
                <div class="small text-muted">Tanggal: <?= date('d F Y, H:i', strtotime($order['created_at'])) ?> WIB</div>
                <div class="mt-2">
                    <span class="badge <?= $order['payment_status'] === 'paid' ? 'bg-success' : 'bg-warning text-dark' ?> px-3 py-1">
                        <?= strtoupper($order['payment_status']) ?> (<?= strtoupper($order['payment_method']) ?>)
                    </span>
                </div>
            </div>
        </div>

        <!-- Order Parties Details -->
        <div class="row g-4 mb-4">
            <div class="col-sm-6">
                <div class="p-3 bg-light rounded-3 h-100">
                    <div class="text-muted small fw-bold text-uppercase mb-2"><i class="bi bi-person-fill text-primary me-1"></i> Informasi Penerima</div>
                    <div class="fw-bold fs-6 text-dark"><?= htmlspecialchars($order['customer_name']) ?></div>
                    <div class="small text-muted mb-1"><?= htmlspecialchars($order['customer_phone'] ?: '-') ?></div>
                    <div class="small text-muted"><b>Alamat Antar:</b> <?= htmlspecialchars($delAddress['address'] ?? 'Kec. Cicalengka, Kab. Bandung') ?></div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="p-3 bg-light rounded-3 h-100">
                    <div class="text-muted small fw-bold text-uppercase mb-2"><i class="bi bi-shop text-primary me-1"></i> Mitra Toko / Pengirim</div>
                    <div class="fw-bold fs-6 text-dark"><?= htmlspecialchars($order['store_name'] ?? 'Cicalengka Parcel Hub') ?></div>
                    <div class="small text-muted mb-1"><?= htmlspecialchars($order['store_phone'] ?: '-') ?></div>
                    <div class="small text-muted"><b>Lokasi Toko:</b> <?= htmlspecialchars($order['store_address'] ?? 'Cicalengka, Jawa Barat') ?></div>
                </div>
            </div>
        </div>

        <!-- Driver / Delivery Info -->
        <div class="p-3 border rounded-3 mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <small class="text-muted d-block">Mitra Kurir Pengantar:</small>
                <span class="fw-bold text-primary"><i class="bi bi-bicycle me-1"></i><?= htmlspecialchars($order['dm_name'] ?? 'Dalam Penugasan Otomatis') ?></span>
                <?php if (!empty($order['vehicle_number'])): ?>
                    <span class="badge bg-light text-dark border ms-1"><?= htmlspecialchars($order['vehicle_type']) ?> (<?= htmlspecialchars($order['vehicle_number']) ?>)</span>
                <?php endif; ?>
            </div>
            <div>
                <small class="text-muted d-block text-end">Kode OTP Verifikasi:</small>
                <span class="fw-bold fs-5 text-dark letter-spacing-1"><b><?= htmlspecialchars($order['otp'] ?? '----') ?></b></span>
            </div>
        </div>

        <!-- Items Table -->
        <div class="table-responsive mb-4">
            <table class="table table-borderless align-middle mb-0">
                <thead class="border-bottom">
                    <tr class="text-muted small fw-bold">
                        <th>ITEM / MENU</th>
                        <th class="text-center">QTY</th>
                        <th class="text-end">HARGA SATUAN</th>
                        <th class="text-end">TOTAL</th>
                    </tr>
                </thead>
                <tbody class="border-bottom">
                    <?php if (!empty($items)): ?>
                        <?php foreach ($items as $it): ?>
                            <tr>
                                <td class="py-3">
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($it['product_name'] ?? 'Item') ?></div>
                                </td>
                                <td class="text-center py-3"><?= $it['quantity'] ?? 1 ?></td>
                                <td class="text-end py-3"><?= format_rupiah($it['price'] ?? 0) ?></td>
                                <td class="text-end py-3 fw-bold text-dark"><?= format_rupiah($it['total_price'] ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="py-3">
                                <div class="fw-bold text-dark">Layanan Pengiriman Cicalengka Parcel Kilat</div>
                                <small class="text-muted">Kategori: <?= htmlspecialchars($parcelDetails['category'] ?? 'Dokumen / Paket') ?> (Berat: <?= htmlspecialchars($parcelDetails['weight'] ?? '1') ?> Kg)</small>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Price Breakdown -->
        <div class="row justify-content-end mb-4">
            <div class="col-md-6">
                <div class="d-flex justify-content-between py-1 small">
                    <span class="text-muted">Subtotal Produk</span>
                    <span class="fw-semibold text-dark"><?= format_rupiah($order['order_amount'] ?? 0) ?></span>
                </div>
                <div class="d-flex justify-content-between py-1 small">
                    <span class="text-muted">Ongkos Kirim Delivery</span>
                    <span class="fw-semibold text-dark"><?= format_rupiah($order['delivery_charge'] ?? 0) ?></span>
                </div>
                <div class="d-flex justify-content-between py-1 small">
                    <span class="text-muted">Pajak Resto / Layanan</span>
                    <span class="fw-semibold text-dark"><?= format_rupiah($order['tax_amount'] ?? 0) ?></span>
                </div>
                <?php if (!empty($order['coupon_discount']) && $order['coupon_discount'] > 0): ?>
                    <div class="d-flex justify-content-between py-1 small text-danger">
                        <span>Diskon Promo Kupon</span>
                        <span>-<?= format_rupiah($order['coupon_discount']) ?></span>
                    </div>
                <?php endif; ?>
                <div class="d-flex justify-content-between py-2 border-top mt-2 fs-5 fw-bold text-primary">
                    <span>Total Pembayaran</span>
                    <span><?= format_rupiah($order['total_amount'] ?? 0) ?></span>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="border-top pt-4 text-center text-muted small">
            <p class="mb-1">Terima kasih telah mempercayakan pengantaran kepada mitra <b>CicalengkaGO</b>!</p>
            <p class="mb-0 text-secondary" style="font-size: 11px;">Struk resmi ini dicetak secara digital oleh sistem CicalengkaGO. Valid tanpa tanda tangan basah.</p>
        </div>
    </div>
</div>

</body>
</html>

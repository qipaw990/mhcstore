<div class="container-fluid py-3">
<div class="d-flex justify-content-between align-items-center mb-3">
<div><h4 class="fw-bold mb-0">🧾 Riwayat PPOB</h4><div class="text-muted small">100 transaksi terakhir • Refund otomatis saat gagal</div></div>
<a href="<?= $baseUrl ?>/admin/ppob/products" class="btn btn-outline-secondary btn-sm">← Katalog</a>
</div>
<div class="card"><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover mb-0 small">
<thead class="table-light"><tr><th>ID</th><th>Ref</th><th>User</th><th>Produk</th><th>Tujuan</th><th>Harga</th><th>Status</th><th>Waktu</th></tr></thead>
<tbody>
<?php foreach($rows as $t): ?>
<tr>
<td><?= $t['id'] ?></td><td><code><?= htmlspecialchars($t['ref_id']) ?></code></td>
<td><?= htmlspecialchars($t['name']??'-') ?><br><span class="text-muted"><?= htmlspecialchars($t['phone']??'') ?></span></td>
<td><?= htmlspecialchars($t['product_name']) ?><br><span class="text-muted"><?= htmlspecialchars($t['buyer_sku_code']) ?></span></td>
<td><?= htmlspecialchars($t['customer_no']) ?></td>
<td>Rp <?= number_format($t['selling_price'],0,',','.') ?></td>
<td><?= $t['status']==='sukses'?'<span class="badge bg-success">Sukses</span>':($t['status']==='gagal'?'<span class="badge bg-danger">Gagal</span>':'<span class="badge bg-warning text-dark">Pending</span>') ?></td>
<td><?= htmlspecialchars($t['created_at']) ?></td>
</tr>
<?php endforeach; ?>
</tbody></table></div></div></div>
</div>

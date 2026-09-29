<div class="container-fluid py-3">
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
<div><h4 class="fw-bold mb-0">📱 Pulsa & PPOB (Digiflazz)</h4><div class="text-muted small">Katalog 20 SKU seed aktif • Markup default Rp <?= number_format($markup??1500,0,',','.') ?></div></div>
<div class="d-flex gap-2">
<a href="<?= $baseUrl ?>/admin/ppob/transactions" class="btn btn-outline-secondary btn-sm">Riwayat Transaksi</a>
<button class="btn btn-outline-info btn-sm" onclick="cekSaldo()">Cek Saldo Digiflazz</button>
<button class="btn btn-admin-primary btn-sm" onclick="syncPrice()">🔄 Sync dari Digiflazz</button>
</div></div>

<div class="card mb-3"><div class="card-body d-flex gap-2 align-items-center flex-wrap">
<label class="fw-bold small mb-0">Markup default (Rp):</label>
<input id="markupInput" type="number" class="form-control form-control-sm" style="width:140px" value="<?= (int)($markup??1500) ?>">
<button class="btn btn-sm btn-success" onclick="saveMarkup()">Simpan & Terapkan ke Semua</button>
<span class="text-muted small">Sinkron harga jual = modal + markup (tunggu rate-limit reda untuk sync penuh).</span>
<span id="saldoBox" class="badge bg-info ms-auto" style="display:none"></span>
</div></div>

<div class="card"><div class="card-body p-0">
<div class="table-responsive"><table class="table table-hover mb-0 small">
<thead class="table-light"><tr><th>SKU</th><th>Produk</th><th>Kategori</th><th>Modal</th><th>Jual</th><th>Margin</th><th>Status</th><th>Aksi</th></tr></thead>
<tbody>
<?php foreach($products as $p): ?>
<tr>
<td><code><?= htmlspecialchars($p['buyer_sku_code']) ?></code></td>
<td><?= htmlspecialchars($p['product_name']) ?><br><span class="text-muted"><?= htmlspecialchars($p['brand']) ?> • <?= htmlspecialchars($p['seller_name']) ?></span></td>
<td><span class="badge bg-light text-dark"><?= htmlspecialchars($p['category']) ?></span></td>
<td>Rp <?= number_format($p['price'],0,',','.') ?></td>
<td class="fw-bold">Rp <?= number_format($p['selling_price'],0,',','.') ?></td>
<td class="text-success">+Rp <?= number_format($p['selling_price']-$p['price'],0,',','.') ?></td>
<td><?= $p['is_active']?'<span class="badge bg-success">Aktif</span>':'<span class="badge bg-secondary">Nonaktif</span>' ?></td>
<td><button class="btn btn-sm btn-outline-primary" onclick="toggleSku('<?= htmlspecialchars($p['buyer_sku_code']) ?>')"><?= $p['is_active']?'Matikan':'Aktifkan' ?></button></td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
</div></div>
</div>
<script>
async function syncPrice(){ if(!confirm('Sync katalog dari Digiflazz? (rate-limit mungkin rc83, coba lagi 10 mnt)')) return; const r=await fetch('<?= $baseUrl ?>/admin/ppob/sync-price',{method:'POST'}); const j=await r.json(); alert(j.success?('Tersinkron '+j.synced+' dari '+j.total):('Gagal: '+(j.message||JSON.stringify(j)))); if(j.success) location.reload(); }
async function cekSaldo(){ const r=await fetch('<?= $baseUrl ?>/admin/ppob/cek-saldo'); const j=await r.json(); const box=document.getElementById('saldoBox'); box.style.display='inline-block'; box.textContent='Deposit: Rp '+(j.data?.data?.deposit?.toLocaleString?.('id-ID') ?? JSON.stringify(j)); }
async function toggleSku(sku){ const fd=new FormData(); fd.append('sku',sku); const r=await fetch('<?= $baseUrl ?>/admin/ppob/toggle',{method:'POST',body:fd}); const j=await r.json(); if(j.success) location.reload(); else alert('Gagal'); }
async function saveMarkup(){ const m=document.getElementById('markupInput').value; const fd=new FormData(); fd.append('markup',m); const r=await fetch('<?= $baseUrl ?>/admin/ppob/save-markup',{method:'POST',body:fd}); const j=await r.json(); alert(j.success?('Markup Rp '+m+' diterapkan'):('Gagal')); if(j.success) location.reload(); }
</script>

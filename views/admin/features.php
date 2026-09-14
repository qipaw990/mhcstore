<?php
/**
 * Admin — Fitur & Layanan Aplikasi Flutter
 * Mengelola semua fitur yang tampil di home screen Flutter:
 *   - Service Grid (kategori kuliner 4x2)
 *   - Filter Chips (horizontal scroll)
 *   - Trending Chips (search suggestions)
 *   - Quick Actions (wallet card buttons)
 *   - Home Sections (toggle section visibility)
 */

$typeLabels = [
    'service_grid'  => ['label' => 'Service Grid',     'icon' => 'bi-grid-1x2-fill', 'color' => '#2563EB', 'desc' => 'Grid kategori 4 kolom di bawah search bar'],
    'filter_chip'   => ['label' => 'Filter Chips',     'icon' => 'bi-tags-fill',      'color' => '#7C3AED', 'desc' => 'Chip filter scroll horizontal di atas toko'],
    'trending_chip' => ['label' => 'Trending Search',  'icon' => 'bi-fire',           'color' => '#EF4444', 'desc' => 'Saran pencarian trending di search bar'],
    'quick_action'  => ['label' => 'Quick Actions',    'icon' => 'bi-lightning-fill', 'color' => '#F59E0B', 'desc' => 'Tombol aksi di kartu CicalengkaPay'],
    'home_section'  => ['label' => 'Home Sections',    'icon' => 'bi-layout-split',   'color' => '#10B981', 'desc' => 'Toggle visibilitas section di halaman home'],
];
$activeTypes = array_keys($typeLabels);
?>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-left">
        <h4 class="page-title"><i class="bi bi-toggles text-dark me-2"></i>Fitur & Layanan Aplikasi</h4>
        <p class="page-subtitle">Kelola semua layanan dan fitur yang ditampilkan di aplikasi Flutter — tanpa perlu rebuild app.</p>
    </div>
    <div class="page-header-right d-flex gap-2">
        <?php if (!$tableExists): ?>
        <button type="button" class="btn btn-sm btn-warning rounded-pill px-3" onclick="runMigration()">
            <i class="bi bi-database-add me-1"></i> Inisialisasi DB
        </button>
        <?php endif; ?>
        <button type="button" class="btn btn-sm btn-admin-primary rounded-pill px-3" onclick="openAddModal()">
            <i class="bi bi-plus-lg me-1"></i> Tambah Fitur
        </button>
    </div>
</div>

<?php if (!$tableExists): ?>
<div class="alert alert-warning rounded-4 border-0 shadow-sm mb-4" role="alert">
    <div class="d-flex align-items-center gap-2">
        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
        <div>
            <strong>Tabel belum ada.</strong> Klik tombol <b>Inisialisasi DB</b> di kanan atas untuk membuat tabel <code>app_features</code> dan mengisi data awal secara otomatis.
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Type Tabs -->
<ul class="nav nav-pills gap-2 mb-4 flex-wrap" id="featureTabs" role="tablist">
    <?php foreach ($typeLabels as $type => $meta): ?>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-semibold px-3 py-2 rounded-pill <?= $type === 'service_grid' ? 'active' : '' ?>"
                id="tab-<?= $type ?>" data-bs-toggle="pill" data-bs-target="#panel-<?= $type ?>"
                type="button" role="tab" style="font-size:13px;">
            <i class="bi <?= $meta['icon'] ?> me-1"></i>
            <?= $meta['label'] ?>
            <span class="badge bg-white text-dark ms-1" style="font-size:10px;"><?= count($featuresByType[$type] ?? []) ?></span>
        </button>
    </li>
    <?php endforeach; ?>
</ul>

<!-- Tab Panels -->
<div class="tab-content" id="featureTabContent">
    <?php foreach ($typeLabels as $type => $meta): ?>
    <?php $items = $featuresByType[$type] ?? []; ?>
    <div class="tab-pane fade <?= $type === 'service_grid' ? 'show active' : '' ?>" id="panel-<?= $type ?>" role="tabpanel">

        <!-- Section Description -->
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="d-flex align-items-center gap-2">
                <div class="rounded-3 d-flex align-items-center justify-content-center" style="width:36px;height:36px;background:<?= $meta['color'] ?>15">
                    <i class="bi <?= $meta['icon'] ?>" style="color:<?= $meta['color'] ?>;font-size:16px;"></i>
                </div>
                <div>
                    <p class="mb-0 text-muted small"><?= $meta['desc'] ?></p>
                </div>
            </div>
            <button class="btn btn-sm btn-light rounded-3 border" onclick="openAddModal('<?= $type ?>')">
                <i class="bi bi-plus me-1"></i>Tambah ke <?= $meta['label'] ?>
            </button>
        </div>

        <?php if (empty($items)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
            <p class="mb-0">Belum ada fitur untuk tipe ini.</p>
            <button class="btn btn-sm btn-admin-primary mt-3 rounded-pill px-3" onclick="openAddModal('<?= $type ?>')">
                <i class="bi bi-plus me-1"></i>Tambah Pertama
            </button>
        </div>
        <?php else: ?>
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="table-<?= $type ?>">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4" style="width:40px;"><i class="bi bi-grip-vertical text-muted"></i></th>
                                <th>Icon</th>
                                <th>Nama Fitur</th>
                                <th>Aksi</th>
                                <th>Nilai Aksi</th>
                                <th class="text-center">Urutan</th>
                                <th class="text-center">Status</th>
                                <th class="text-end pe-4">Kelola</th>
                            </tr>
                        </thead>
                        <tbody id="sortable-<?= $type ?>">
                            <?php foreach ($items as $item): ?>
                            <tr data-id="<?= $item['id'] ?>">
                                <td class="ps-4 drag-handle text-muted" style="cursor:grab;"><i class="bi bi-grip-vertical"></i></td>
                                <td>
                                    <div class="rounded-2 d-flex align-items-center justify-content-center fw-bold"
                                         style="width:36px;height:36px;font-size:20px;background:<?= htmlspecialchars($item['bg_color'] ?: '#F1F5F9') ?>;color:<?= htmlspecialchars($item['color'] ?: '#333') ?>">
                                        <?php if ($item['icon_type'] === 'emoji'): ?>
                                            <?= htmlspecialchars($item['icon'] ?? '?') ?>
                                        <?php else: ?>
                                            <i class="bi bi-<?= htmlspecialchars($item['icon'] ?? 'star') ?>" style="font-size:16px;"></i>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="fw-semibold"><?= htmlspecialchars($item['name']) ?></td>
                                <td>
                                    <span class="badge bg-light text-dark border" style="font-size:11px;"><?= htmlspecialchars($item['action_type']) ?></span>
                                </td>
                                <td class="text-muted small"><?= htmlspecialchars($item['action_value'] ?? '-') ?></td>
                                <td class="text-center">
                                    <span class="badge bg-secondary-subtle text-secondary"><?= (int)$item['sort_order'] ?></span>
                                </td>
                                <td class="text-center">
                                    <div class="form-check form-switch d-flex justify-content-center mb-0">
                                        <input class="form-check-input" type="checkbox" role="switch"
                                               id="toggle-<?= $item['id'] ?>"
                                               <?= $item['is_active'] ? 'checked' : '' ?>
                                               onchange="toggleFeature(<?= $item['id'] ?>, this)">
                                    </div>
                                </td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-light btn-sm rounded-circle me-1" style="width:32px;height:32px;"
                                            onclick='openEditModal(<?= json_encode($item) ?>)' title="Edit">
                                        <i class="bi bi-pencil text-primary" style="font-size:12px;"></i>
                                    </button>
                                    <button class="btn btn-light btn-sm rounded-circle" style="width:32px;height:32px;"
                                            onclick="deleteFeature(<?= $item['id'] ?>, '<?= addslashes($item['name']) ?>')" title="Hapus">
                                        <i class="bi bi-trash text-danger" style="font-size:12px;"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
    <?php endforeach; ?>
</div>

<!-- ===== Modal: Add / Edit Feature ===== -->
<div class="modal fade" id="featureModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom py-3">
                <h6 class="modal-title fw-bold" id="featureModalTitle">
                    <i class="bi bi-toggles me-2 text-primary"></i>Tambah Fitur Aplikasi
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="featureForm" onsubmit="saveFeature(event)">
                <input type="hidden" name="id" id="featId" value="0">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <!-- Tipe -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Tipe Fitur <span class="text-danger">*</span></label>
                            <select name="feature_type" id="featType" class="form-select rounded-3">
                                <option value="service_grid">Service Grid (Kategori 4x2)</option>
                                <option value="filter_chip">Filter Chip (Horizontal Scroll)</option>
                                <option value="trending_chip">Trending Search</option>
                                <option value="quick_action">Quick Action (Wallet Card)</option>
                                <option value="home_section">Home Section (Toggle)</option>
                            </select>
                        </div>
                        <!-- Nama -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Nama Tampilan <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="featName" class="form-control rounded-3"
                                   placeholder="Contoh: Ayam & Bebek" required>
                        </div>
                        <!-- Icon -->
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Icon / Emoji</label>
                            <input type="text" name="icon" id="featIcon" class="form-control rounded-3"
                                   placeholder="🍗 atau bi-egg-fried" maxlength="20">
                            <div class="form-text">Emoji Unicode, ikon Bootstrap Icons (bi-xxx), atau material</div>
                        </div>
                        <!-- Icon Type -->
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Tipe Icon</label>
                            <select name="icon_type" id="featIconType" class="form-select rounded-3">
                                <option value="emoji">Emoji</option>
                                <option value="bi">Bootstrap Icons (bi-xxx)</option>
                                <option value="material">Material Icons (Flutter)</option>
                            </select>
                        </div>
                        <!-- Urutan -->
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Urutan</label>
                            <input type="number" name="sort_order" id="featSortOrder" class="form-control rounded-3"
                                   value="0" min="0">
                        </div>
                        <!-- Warna Icon -->
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Warna Icon</label>
                            <div class="input-group">
                                <input type="color" name="color" id="featColor" class="form-control form-control-color rounded-start-3" value="#2563EB">
                                <input type="text" id="featColorText" class="form-control rounded-end-3" placeholder="#2563EB" style="max-width:100px;"
                                       oninput="document.getElementById('featColor').value = this.value">
                            </div>
                        </div>
                        <!-- Warna BG -->
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Warna Background</label>
                            <div class="input-group">
                                <input type="color" name="bg_color" id="featBgColor" class="form-control form-control-color rounded-start-3" value="#DBEAFE">
                                <input type="text" id="featBgColorText" class="form-control rounded-end-3" placeholder="#DBEAFE" style="max-width:100px;"
                                       oninput="document.getElementById('featBgColor').value = this.value">
                            </div>
                        </div>
                        <!-- Tipe Aksi -->
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Tipe Aksi</label>
                            <select name="action_type" id="featActionType" class="form-select rounded-3">
                                <option value="search">Search (cari produk/toko)</option>
                                <option value="module">Module (buka modul)</option>
                                <option value="route">Route (halaman app)</option>
                                <option value="url">URL (buka browser)</option>
                                <option value="none">None (tidak ada aksi)</option>
                            </select>
                        </div>
                        <!-- Nilai Aksi -->
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Nilai Aksi</label>
                            <input type="text" name="action_value" id="featActionValue" class="form-control rounded-3"
                                   placeholder="Kata kunci / nama route / URL">
                        </div>
                        <!-- Status -->
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="featIsActive" checked>
                                <label class="form-check-label small fw-bold" for="featIsActive">Aktifkan Fitur</label>
                            </div>
                        </div>
                        <!-- Preview -->
                        <div class="col-12">
                            <label class="form-label small fw-bold text-muted">Preview Ikon</label>
                            <div class="d-flex align-items-center gap-3 p-3 rounded-3 bg-light">
                                <div id="iconPreview" class="rounded-2 d-flex align-items-center justify-content-center"
                                     style="width:48px;height:48px;background:#DBEAFE;font-size:24px;">🍗</div>
                                <div>
                                    <div id="namePreview" class="fw-semibold" style="font-size:13px;">Nama Fitur</div>
                                    <div class="text-muted small">Tap untuk membuka hasil pencarian</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top py-3">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="featSaveBtn" class="btn btn-admin-primary rounded-pill px-4">
                        <i class="bi bi-check-lg me-1"></i>Simpan Fitur
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const baseUrl = '<?= $baseUrl ?>';

// ── Preview live ─────────────────────────────────────────────────────────────
function updatePreview() {
    const icon    = document.getElementById('featIcon').value || '?';
    const name    = document.getElementById('featName').value || 'Nama Fitur';
    const color   = document.getElementById('featColor').value || '#2563EB';
    const bgColor = document.getElementById('featBgColor').value || '#DBEAFE';
    document.getElementById('iconPreview').textContent = icon;
    document.getElementById('iconPreview').style.background = bgColor;
    document.getElementById('iconPreview').style.color = color;
    document.getElementById('namePreview').textContent = name;
}
['featIcon','featName','featColor','featBgColor'].forEach(id => {
    document.getElementById(id)?.addEventListener('input', updatePreview);
});
document.getElementById('featColor')?.addEventListener('input', e => {
    document.getElementById('featColorText').value = e.target.value;
    updatePreview();
});
document.getElementById('featBgColor')?.addEventListener('input', e => {
    document.getElementById('featBgColorText').value = e.target.value;
    updatePreview();
});

// ── Open modals ──────────────────────────────────────────────────────────────
function openAddModal(type = 'service_grid') {
    document.getElementById('featureModalTitle').innerHTML = '<i class="bi bi-plus-circle me-2 text-primary"></i>Tambah Fitur Aplikasi';
    document.getElementById('featureForm').reset();
    document.getElementById('featId').value = '0';
    document.getElementById('featType').value = type;
    document.getElementById('featIsActive').checked = true;
    document.getElementById('featColor').value = '#2563EB';
    document.getElementById('featColorText').value = '#2563EB';
    document.getElementById('featBgColor').value = '#DBEAFE';
    document.getElementById('featBgColorText').value = '#DBEAFE';
    updatePreview();
    new bootstrap.Modal(document.getElementById('featureModal')).show();
}

function openEditModal(item) {
    document.getElementById('featureModalTitle').innerHTML = '<i class="bi bi-pencil me-2 text-primary"></i>Edit Fitur';
    document.getElementById('featId').value       = item.id;
    document.getElementById('featType').value     = item.feature_type;
    document.getElementById('featName').value     = item.name;
    document.getElementById('featIcon').value     = item.icon || '';
    document.getElementById('featIconType').value = item.icon_type || 'emoji';
    document.getElementById('featColor').value    = item.color || '#2563EB';
    document.getElementById('featColorText').value= item.color || '#2563EB';
    document.getElementById('featBgColor').value  = item.bg_color || '#DBEAFE';
    document.getElementById('featBgColorText').value = item.bg_color || '#DBEAFE';
    document.getElementById('featActionType').value  = item.action_type || 'search';
    document.getElementById('featActionValue').value = item.action_value || '';
    document.getElementById('featSortOrder').value   = item.sort_order || 0;
    document.getElementById('featIsActive').checked  = item.is_active == 1;
    updatePreview();
    new bootstrap.Modal(document.getElementById('featureModal')).show();
}

// ── Save ─────────────────────────────────────────────────────────────────────
async function saveFeature(e) {
    e.preventDefault();
    const btn = document.getElementById('featSaveBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...';

    const fd = new FormData(document.getElementById('featureForm'));
    if (!document.getElementById('featIsActive').checked) {
        fd.delete('is_active');
    }
    // sync color text fields
    fd.set('color',    document.getElementById('featColor').value);
    fd.set('bg_color', document.getElementById('featBgColor').value);

    try {
        const r = await fetch(`${baseUrl}/admin/features/save`, {method:'POST',body:fd});
        const d = await r.json();
        if (d.success) {
            bootstrap.Modal.getInstance(document.getElementById('featureModal'))?.hide();
            showToast('✅ Fitur berhasil disimpan!', 'success');
            setTimeout(() => location.reload(), 700);
        } else {
            showToast('❌ ' + (d.message || 'Gagal menyimpan'), 'danger');
        }
    } catch (err) {
        showToast('❌ Error: ' + err.message, 'danger');
    }
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Simpan Fitur';
}

// ── Toggle ───────────────────────────────────────────────────────────────────
async function toggleFeature(id, cb) {
    const fd = new FormData();
    fd.append('id', id);
    try {
        const r = await fetch(`${baseUrl}/admin/features/toggle`, {method:'POST',body:fd});
        const d = await r.json();
        if (!d.success) {
            cb.checked = !cb.checked;
            showToast('Gagal mengubah status', 'danger');
        } else {
            showToast(d.data?.is_active ? '✅ Fitur diaktifkan' : '⚪ Fitur dinonaktifkan', 'info');
        }
    } catch {
        cb.checked = !cb.checked;
    }
}

// ── Delete ───────────────────────────────────────────────────────────────────
async function deleteFeature(id, name) {
    if (!confirm(`Hapus fitur "${name}"? Tindakan ini tidak bisa dibatalkan.`)) return;
    const fd = new FormData();
    fd.append('id', id);
    try {
        const r = await fetch(`${baseUrl}/admin/features/delete`, {method:'POST',body:fd});
        const d = await r.json();
        if (d.success) {
            showToast('🗑️ Fitur berhasil dihapus', 'success');
            setTimeout(() => location.reload(), 600);
        } else {
            showToast('Gagal menghapus: ' + d.message, 'danger');
        }
    } catch (err) {
        showToast('Error: ' + err.message, 'danger');
    }
}

// ── Migration ─────────────────────────────────────────────────────────────────
async function runMigration() {
    if (!confirm('Buat tabel app_features dan isi dengan data default? Proses ini aman dan tidak akan menghapus data yang sudah ada.')) return;
    try {
        const r = await fetch(`${baseUrl}/admin/features/migrate`, {method:'POST'});
        const d = await r.json();
        if (d.success) {
            showToast('✅ ' + d.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast('❌ ' + d.message, 'danger');
        }
    } catch (err) {
        showToast('Error: ' + err.message, 'danger');
    }
}

// ── Drag-and-drop Reorder (Sortable.js CDN) ──────────────────────────────────
function initSortable(type) {
    const el = document.getElementById(`sortable-${type}`);
    if (!el || typeof Sortable === 'undefined') return;

    Sortable.create(el, {
        handle: '.drag-handle',
        animation: 150,
        onEnd: async () => {
            const rows = el.querySelectorAll('tr[data-id]');
            const items = [...rows].map((r, i) => ({id: r.dataset.id, sort_order: i + 1}));
            await fetch(`${baseUrl}/admin/features/reorder`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(items)
            });
            showToast('Urutan berhasil disimpan', 'success');
        }
    });
}

// Initialize Sortable for all types
<?php foreach ($activeTypes as $type): ?>
initSortable('<?= $type ?>');
<?php endforeach; ?>

// ── Toast helper ─────────────────────────────────────────────────────────────
function showToast(msg, type = 'success') {
    let container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        container.style.cssText = 'position:fixed;bottom:1.5rem;right:1.5rem;z-index:9999;display:flex;flex-direction:column;gap:8px;';
        document.body.appendChild(container);
    }
    const el = document.createElement('div');
    el.className = `toast align-items-center text-white bg-${type} border-0 show`;
    el.setAttribute('role', 'alert');
    el.innerHTML = `<div class="d-flex"><div class="toast-body fw-semibold">${msg}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" onclick="this.closest('.toast').remove()"></button></div>`;
    container.appendChild(el);
    setTimeout(() => el.remove(), 3500);
}
</script>

<!-- Sortable.js CDN for drag-and-drop -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"></script>

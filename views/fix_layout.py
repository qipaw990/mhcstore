import sys

# Read existing file and keep only lines 1-220
with open('views/layouts/customer_layout.php', 'r', encoding='utf-8') as f:
    lines = f.readlines()

kept = ''.join(lines[:220])  # lines 1-220 (index 0-219)

footer = r"""
<!-- Native Mobile App Bottom Sheet Alert & Toast -->
<div id="mobile-app-sheet-backdrop" class="mobile-app-sheet-backdrop" onclick="AppAlert.closeSheet()"></div>
<div id="mobile-app-sheet" class="mobile-app-sheet">
    <div class="mobile-sheet-drag-handle"></div>
    <div class="mobile-sheet-icon-box info" id="mobile-sheet-icon-box">
        <i class="bi bi-info-circle-fill" id="mobile-sheet-icon"></i>
    </div>
    <h5 class="mobile-sheet-title" id="mobile-sheet-title">Informasi</h5>
    <p class="mobile-sheet-message" id="mobile-sheet-message"></p>
    <div class="mobile-sheet-actions">
        <button type="button" class="btn-mobile-sheet-primary" id="mobile-sheet-btn-confirm" onclick="AppAlert.closeSheet()">Mengerti</button>
        <button type="button" class="btn-mobile-sheet-secondary" id="mobile-sheet-btn-cancel" onclick="AppAlert.cancelSheet()" style="display:none;">Batal</button>
    </div>
</div>
<div id="mobile-app-toast" class="mobile-app-toast">
    <i class="bi bi-check-circle-fill" id="mobile-toast-icon" style="font-size:13px;color:#22C55E;"></i>
    <span id="mobile-toast-message">Berhasil</span>
</div>

<script>
/* AppAlert - Native Mobile Bottom Sheet & Toast for CicalengkaGO */
const AppAlert = {
    confirmCb: null,
    cancelCb: null,
    show: function(options) {
        var type    = options.type || options.icon || 'info';
        var title   = options.title || 'Informasi';
        var message = options.text  || options.message || '';
        var confirmText = options.confirmButtonText || 'Mengerti';
        var cancelText  = options.cancelButtonText  || 'Batal';
        var showCancel  = options.showCancelButton  || false;
        var iconBox    = document.getElementById('mobile-sheet-icon-box');
        var iconEl     = document.getElementById('mobile-sheet-icon');
        var titleEl    = document.getElementById('mobile-sheet-title');
        var msgEl      = document.getElementById('mobile-sheet-message');
        var btnConfirm = document.getElementById('mobile-sheet-btn-confirm');
        var btnCancel  = document.getElementById('mobile-sheet-btn-cancel');
        var backdrop   = document.getElementById('mobile-app-sheet-backdrop');
        var sheet      = document.getElementById('mobile-app-sheet');
        if (!sheet || !backdrop) { return; }
        var safeType = type === 'error' ? 'danger' : type;
        iconBox.className = 'mobile-sheet-icon-box ' + safeType;
        var iconMap = { success:'bi-check-circle-fill', warning:'bi-exclamation-triangle-fill', danger:'bi-x-circle-fill', error:'bi-x-circle-fill', info:'bi-info-circle-fill' };
        iconEl.className = 'bi ' + (iconMap[safeType] || iconMap[type] || 'bi-info-circle-fill');
        titleEl.textContent   = title;
        msgEl.textContent     = message;
        btnConfirm.textContent = confirmText;
        btnCancel.textContent  = cancelText;
        btnCancel.style.display = showCancel ? 'block' : 'none';
        this.confirmCb = options.onConfirm || null;
        this.cancelCb  = options.onCancel  || null;
        backdrop.classList.add('active');
        sheet.classList.add('active');
    },
    confirm: function(title, message, onConfirm, confirmText) {
        this.show({ type:'warning', title:title, message:message, showCancelButton:true, confirmButtonText:confirmText||'Ya, Lanjutkan', cancelButtonText:'Batal', onConfirm:onConfirm });
    },
    toast: function(message, type) {
        var toastEl = document.getElementById('mobile-app-toast');
        var iconEl  = document.getElementById('mobile-toast-icon');
        var msgEl   = document.getElementById('mobile-toast-message');
        if (!toastEl) return;
        type = type || 'success';
        var colors  = { success:'#22C55E', error:'#EE2737', danger:'#EE2737', warning:'#F59E0B', info:'#3B82F6' };
        var iconMap = { success:'bi-check-circle-fill', error:'bi-x-circle-fill', danger:'bi-x-circle-fill', warning:'bi-exclamation-triangle-fill', info:'bi-info-circle-fill' };
        iconEl.className  = 'bi ' + (iconMap[type] || 'bi-check-circle-fill');
        iconEl.style.color = colors[type] || '#22C55E';
        msgEl.textContent  = message;
        toastEl.classList.add('active');
        setTimeout(function() { toastEl.classList.remove('active'); }, 2800);
    },
    closeSheet: function() {
        document.getElementById('mobile-app-sheet-backdrop').classList.remove('active');
        document.getElementById('mobile-app-sheet').classList.remove('active');
        if (this.confirmCb) { var cb = this.confirmCb; this.confirmCb = null; cb(); }
    },
    cancelSheet: function() {
        document.getElementById('mobile-app-sheet-backdrop').classList.remove('active');
        document.getElementById('mobile-app-sheet').classList.remove('active');
        if (this.cancelCb)  { var cb = this.cancelCb;  this.cancelCb  = null; cb(); }
    }
};

/* Override window.Swal -> AppAlert (backwards compat for all existing Swal.fire calls) */
window.Swal = {
    fire: function(a1, a2, a3) {
        return new Promise(function(resolve) {
            var opts = (typeof a1 === 'object') ? a1 : { title: a1||'Informasi', text: a2||'', type: a3||'info' };
            opts.onConfirm = function() { resolve({ isConfirmed:true, value:true }); };
            opts.onCancel  = function() { resolve({ isConfirmed:false, isDismissed:true }); };
            AppAlert.show(opts);
        });
    },
    showLoading:            function() {},
    hideLoading:            function() {},
    showValidationMessage:  function(msg) { console.warn(msg); },
    close:                  function() { AppAlert.closeSheet(); }
};
</script>

<!-- Midtrans Snap JS -->
<?php
$midtransServiceInst = new \App\Services\MidtransService();
$resolvedSnapKey = $client_key ?? $midtransServiceInst->getClientKey() ?? 'Mid-client-fa_UX3R3BzD4wXXl';
$resolvedSnapUrl = $snap_url ?? $midtransServiceInst->getSnapUrl() ?? 'https://app.sandbox.midtrans.com/snap/snap.js';
?>
<script type="text/javascript" src="<?= $resolvedSnapUrl ?>" data-client-key="<?= htmlspecialchars($resolvedSnapKey) ?>"></script>
<script src="<?= $baseUrl ?>/assets/js/pwa-install.js"></script>
<script src="<?= $baseUrl ?>/assets/js/customer-pwa.js?v=<?= time() ?>"></script>

<?php if (!empty($_SESSION['success'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            AppAlert.toast('<?= addslashes($_SESSION['success']) ?>', 'success');
        });
    </script>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['error'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            AppAlert.show({ type:'error', title:'Perhatian', text:'<?= addslashes($_SESSION['error']) ?>' });
        });
    </script>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

</body>
</html>
"""

result = kept + footer
with open('views/layouts/customer_layout.php', 'w', encoding='utf-8', newline='\n') as f:
    f.write(result)

print('Done! Lines:', result.count('\n')+1)

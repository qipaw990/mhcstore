/**
 * Customer PWA Engine
 */

const API_BASE = window.BASE_URL || '';

// Add to Cart
async function addToCart(productId, quantity = 1, variationId = null, addons = [], notes = '', forceSwitch = false) {
  try {
    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('quantity', quantity);
    if (variationId) formData.append('variation_id', variationId);
    if (addons.length) {
      addons.forEach(a => formData.append('addons[]', a));
    }
    if (notes) formData.append('item_notes', notes);
    if (forceSwitch) formData.append('force_switch_store', '1');

    const res = await fetch(API_BASE + '/cart/add', {
      method: 'POST',
      body: formData
    });

    const data = await res.json();

    if (res.status === 409 && data.is_store_conflict) {
      // Store conflict dialog
      Swal.fire({
        title: 'Ganti Toko?',
        text: data.message,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#0d6efd',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Ganti Menu',
        cancelButtonText: 'Batal'
      }).then((result) => {
        if (result.isConfirmed) {
          addToCart(productId, quantity, variationId, addons, notes, true);
        }
      });
      return;
    }

    if (data.success) {
      Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: data.message,
        showConfirmButton: false,
        timer: 1500
      });

      updateFloatingCart(data.data.cart_count, data.data.subtotal_fmt);
    } else {
      Swal.fire('Gagal', data.message || 'Terjadi kesalahan.', 'error');
    }
  } catch (err) {
    console.error(err);
  }
}

// Update Cart Quantity
async function updateCartQty(cartId, delta) {
  try {
    const formData = new FormData();
    formData.append('cart_id', cartId);
    formData.append('delta', delta);

    const res = await fetch(API_BASE + '/cart/update-qty', {
      method: 'POST',
      body: formData
    });

    const data = await res.json();
    if (data.success) {
      location.reload();
    }
  } catch (err) {
    console.error(err);
  }
}

// Remove from Cart
async function removeFromCart(cartId) {
  try {
    const formData = new FormData();
    formData.append('cart_id', cartId);

    const res = await fetch(API_BASE + '/cart/remove', {
      method: 'POST',
      body: formData
    });

    const data = await res.json();
    if (data.success) {
      location.reload();
    }
  } catch (err) {
    console.error(err);
  }
}

// Update Floating Cart Pill
function updateFloatingCart(count, totalFmt) {
  const pill = document.getElementById('floating-cart-pill');
  if (!pill) return;

  if (count > 0) {
    const numEl = document.getElementById('floating-cart-count-num');
    if (numEl) {
      numEl.textContent = count;
    } else {
      const countEl = document.getElementById('floating-cart-count');
      if (countEl) countEl.innerHTML = `<i class="bi bi-bag-fill" style="font-size:9.5px;"></i> <span id="floating-cart-count-num">${count}</span> Menu`;
    }
    const priceEl = document.getElementById('floating-cart-price');
    if (priceEl) priceEl.textContent = totalFmt;
    pill.classList.remove('d-none');
  } else {
    pill.classList.add('d-none');
  }
}

// Location Permission & GPS Detection Logic
document.addEventListener('DOMContentLoaded', () => {
  const isHome = document.querySelector('.gojek-header') !== null;
  const isRealGps = localStorage.getItem('user_is_real_gps') === 'true';

  if (isHome) {
    if (isRealGps) {
      updateHeaderLocationText(localStorage.getItem('user_gps_address') || 'Cicalengka (GPS)');
    } else {
      // Jika GPS asli belum aktif, SELALU tampilkan Pop-up Lokasi setiap kali Beranda direfresh!
      updateHeaderLocationText('📍 Aktifkan Lokasi');
      setTimeout(() => {
        locateCustomerHomeGps();
      }, 300);
    }
  }
});

function requestCustomerGpsLocation() {
  const btn = document.getElementById('btn-request-location');
  const origText = btn ? btn.innerHTML : '';
  if (btn) {
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Mendeteksi GPS...';
  }

  // Cek apakah koneksi non-HTTPS (Insecure Context)
  const isLocalhost = location.hostname === 'localhost' || location.hostname === '127.0.0.1';
  if (!window.isSecureContext && !isLocalhost) {
    showLocationInsecureWarning();
    if (btn) { btn.disabled = false; btn.innerHTML = origText; }
    return;
  }

  if (!navigator.geolocation) {
    showLocationDeniedInstructions('Browser Anda tidak mendukung Geolocation.');
    if (btn) { btn.disabled = false; btn.innerHTML = origText; }
    return;
  }

  navigator.geolocation.getCurrentPosition(
    async (position) => {
      const lat = position.coords.latitude;
      const lng = position.coords.longitude;

      localStorage.setItem('user_is_real_gps', 'true');
      localStorage.setItem('user_gps_lat', lat);
      localStorage.setItem('user_gps_lng', lng);

      let addrName = 'Cicalengka (GPS Aktif)';
      try {
        const geoRes = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`);
        const geoData = await geoRes.json();
        if (geoData && geoData.address) {
          const road = geoData.address.road || geoData.address.suburb || geoData.address.village || 'Cicalengka';
          addrName = road;
        }
      } catch (e) {
        console.log('Geocode fallback');
      }

      localStorage.setItem('user_gps_address', addrName);
      updateHeaderLocationText(addrName);
      dismissLocationPrompt();

      Swal.fire({
        toast: true,
        position: 'top',
        icon: 'success',
        title: '📍 Lokasi Berhasil Terdeteksi!',
        text: addrName,
        showConfirmButton: false,
        timer: 2500
      });

      if (btn) { btn.disabled = false; btn.innerHTML = origText; }
    },
    (error) => {
      console.warn('GPS Error/Denied:', error);
      localStorage.removeItem('user_is_real_gps');
      showLocationDeniedInstructions();
      if (btn) { btn.disabled = false; btn.innerHTML = origText; }
    },
    { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
  );
}

function showLocationInsecureWarning() {
  const bodyEl = document.getElementById('location-modal-body');
  if (!bodyEl) return;

  bodyEl.innerHTML = `
    <div class="text-center py-2">
      <div class="rounded-circle d-flex align-items-center justify-content-center text-danger mx-auto mb-3 bg-danger-subtle" style="width: 68px; height: 68px;">
        <i class="bi bi-shield-slash-fill" style="font-size: 32px;"></i>
      </div>
      <h6 class="fw-bold text-dark mb-1">Peramban Menggunakan HTTP (Non-HTTPS)</h6>
      <p class="text-muted small mb-3" style="font-size: 12px; line-height: 1.5;">
        Browser modern (Chrome, Safari, Edge) <b>membatasi fitur GPS otomatis</b> hanya pada situs dengan protokol aman <b>HTTPS / SSL</b> atau <b>localhost</b>.
      </p>

      <div class="p-3 bg-light rounded-3 text-start small mb-3 border">
        <div class="fw-bold mb-1 text-dark" style="font-size: 12px;"><i class="bi bi-info-circle-fill me-1 text-primary"></i> Solusi Penggunaan:</div>
        <ul class="ps-3 mb-0 text-muted" style="font-size: 11.5px; line-height: 1.5;">
          <li>Gunakan alamat <code>http://localhost/CicalengkaGO</code> jika menguji di komputer lokal.</li>
          <li>Gunakan sertifikat HTTPS / Domain SSL saat di-deploy ke server live.</li>
          <li>Atau gunakan lokasi default <b>Cicalengka</b> di bawah.</li>
        </ul>
      </div>

      <div class="d-flex flex-column gap-2">
        <button type="button" onclick="useFallbackCicalengkaLocation()" class="btn text-white rounded-pill py-2.5 fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2" style="background: #EE2737; font-size: 13px;">
          <i class="bi bi-geo-alt-fill fs-6"></i> Gunakan Area Cicalengka (Default)
        </button>
        <button type="button" onclick="dismissLocationPrompt()" class="btn btn-link text-muted fw-semibold text-decoration-none small py-1" style="font-size: 11px;">
          Tutup
        </button>
      </div>
    </div>
  `;

  locateCustomerHomeGps();
}

function showLocationDeniedInstructions(reason = null) {
  const bodyEl = document.getElementById('location-modal-body');
  if (!bodyEl) return;

  bodyEl.innerHTML = `
    <div class="text-center py-2">
      <div class="rounded-circle d-flex align-items-center justify-content-center text-warning mx-auto mb-3 bg-warning-subtle" style="width: 68px; height: 68px;">
        <i class="bi bi-shield-lock-fill" style="font-size: 32px;"></i>
      </div>
      <h6 class="fw-bold text-dark mb-1">Izin Lokasi Ditolak Peramban</h6>
      <p class="text-muted small mb-3" style="font-size: 12px; line-height: 1.5;">
        ${reason || 'Akses GPS diblokir oleh browser. Ikuti langkah di bawah ini untuk mengaktifkan:'}
      </p>

      <div class="p-3 bg-light rounded-3 text-start small mb-3 border">
        <div class="fw-bold mb-2 text-dark"><i class="bi bi-gear-fill me-1 text-danger"></i> Cara Mengaktifkan Izin Lokasi:</div>
        <ol class="ps-3 mb-0 text-muted" style="font-size: 11.5px; line-height: 1.6;">
          <li>Klik ikon <b>🔒 (Gembok)</b> atau <b>⚙️ (Setelan Site)</b> di samping URL peramban Anda.</li>
          <li>Cari menu <b>Lokasi / Geolocation</b>, lalu ubah ke <b>Izinkan / Allow</b>.</li>
          <li>Klik tombol <b>Coba Deteksi Lagi</b> di bawah.</li>
        </ol>
      </div>

      <div class="d-flex flex-column gap-2">
        <button type="button" onclick="requestCustomerGpsLocation()" class="btn text-white rounded-pill py-2.5 fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2" style="background: #EE2737; font-size: 13px;" id="btn-request-location">
          <i class="bi bi-arrow-clockwise fs-6"></i> Coba Deteksi Lagi
        </button>
        <button type="button" onclick="useFallbackCicalengkaLocation()" class="btn btn-outline-danger rounded-pill py-2 fw-semibold small">
          <i class="bi bi-geo-alt me-1"></i> Gunakan Alun-Alun Cicalengka (Default)
        </button>
        <button type="button" onclick="dismissLocationPrompt()" class="btn btn-link text-muted fw-semibold text-decoration-none small py-1" style="font-size: 11px;">
          Tutup
        </button>
      </div>
    </div>
  `;

  locateCustomerHomeGps();
}

function useFallbackCicalengkaLocation() {
  localStorage.setItem('user_gps_lat', '-6.9840');
  localStorage.setItem('user_gps_lng', '107.8340');
  localStorage.setItem('user_gps_address', 'Cicalengka (Pusat Kota)');

  updateHeaderLocationText('Cicalengka (Pusat Kota)');
  dismissLocationPrompt();

  Swal.fire({
    toast: true,
    position: 'top',
    icon: 'info',
    title: '📍 Menggunakan Alun-Alun Cicalengka sebagai lokasi default',
    showConfirmButton: false,
    timer: 2500
  });
}

function dismissLocationPrompt() {
  const locModalEl = document.getElementById('locationPermissionModal');
  if (locModalEl) {
    const modal = bootstrap.Modal.getInstance(locModalEl) || new bootstrap.Modal(locModalEl);
    modal.hide();
  }
}

function updateHeaderLocationText(text) {
  const locTextEl = document.querySelector('.gojek-location-text span');
  if (locTextEl) {
    locTextEl.textContent = text;
  }
}

function locateCustomerHomeGps() {
  const locModalEl = document.getElementById('locationPermissionModal');
  if (locModalEl) {
    let modal = bootstrap.Modal.getInstance(locModalEl);
    if (!modal) {
      modal = new bootstrap.Modal(locModalEl);
    }
    modal.show();
  }
}


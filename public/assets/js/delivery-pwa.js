/**
 * Delivery Driver PWA Real-Time Sync Engine
 * CicalengkaGO Delivery Platform
 */

const API_BASE = window.BASE_URL || '';
let prevAvailableCount = -1;
let prevActiveOrderStatus = null;
let audioCtx = null;

// SweetAlert Toast Notification Engine for Driver PWA
const Toast = (typeof Swal !== 'undefined') ? Swal.mixin({
  toast: true,
  position: 'top-end',
  showConfirmButton: false,
  timer: 3000,
  timerProgressBar: true,
  didOpen: (toast) => {
    toast.addEventListener('mouseenter', Swal.stopTimer);
    toast.addEventListener('mouseleave', Swal.resumeTimer);
  }
}) : {
  fire: (opts) => alert((opts.title || '') + ': ' + (opts.text || ''))
};
window.Toast = Toast;

// Synthesized pleasant chime for new order alert without external asset dependency
function playNewOrderChime() {
  try {
    const AudioContext = window.AudioContext || window.webkitAudioContext;
    if (!AudioContext) return;
    if (!audioCtx) audioCtx = new AudioContext();
    if (audioCtx.state === 'suspended') {
      audioCtx.resume();
    }

    const now = audioCtx.currentTime;
    const osc1 = audioCtx.createOscillator();
    const osc2 = audioCtx.createOscillator();
    const gainNode = audioCtx.createGain();

    osc1.type = 'sine';
    osc1.frequency.setValueAtTime(587.33, now); // D5
    osc1.frequency.exponentialRampToValueAtTime(880, now + 0.15); // A5

    osc2.type = 'triangle';
    osc2.frequency.setValueAtTime(880, now + 0.15);
    osc2.frequency.exponentialRampToValueAtTime(1174.66, now + 0.35); // D6

    gainNode.gain.setValueAtTime(0.01, now);
    gainNode.gain.linearRampToValueAtTime(0.3, now + 0.05);
    gainNode.gain.exponentialRampToValueAtTime(0.001, now + 0.5);

    osc1.connect(gainNode);
    osc2.connect(gainNode);
    gainNode.connect(audioCtx.destination);

    osc1.start(now);
    osc2.start(now + 0.15);
    osc1.stop(now + 0.2);
    osc2.stop(now + 0.55);
  } catch (e) {
    console.warn('Audio alert error:', e);
  }
}

// Rupiah Formatter
function formatRupiahJs(number) {
  return 'Rp ' + Number(number).toLocaleString('id-ID');
}
window.formatRupiahJs = formatRupiahJs;
window.playNewOrderChime = playNewOrderChime;

// Toggle Driver Online Status
async function toggleDriverStatus() {
  try {
    const res = await fetch(API_BASE + '/delivery/toggle-online', { method: 'POST' });
    const data = await res.json();
    if (data.success) {
      Toast.fire({
        icon: 'info',
        title: 'Status Diperbarui',
        text: data.message || 'Status online driver berhasil diubah.'
      });
      syncDriverLiveDashboard();
      setTimeout(() => {
        location.reload();
      }, 400);
    }
  } catch (err) {
    console.error(err);
  }
}

// Accept Incoming Order
async function acceptDriverOrder(orderId) {
  const cardEl = document.getElementById('avail-order-' + orderId);
  let btnEl = null;
  if (cardEl) {
    btnEl = cardEl.querySelector('button');
    if (btnEl) {
      btnEl.disabled = true;
      btnEl.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Mengambil...';
    }
  }

  try {
    const formData = new FormData();
    formData.append('order_id', orderId);

    const res = await fetch(API_BASE + '/delivery/accept-order', {
      method: 'POST',
      body: formData
    });

    const data = await res.json();
    if (data.success) {
      Toast.fire({
        icon: 'success',
        title: 'Pesanan Diterima! 🚀',
        text: data.message || 'Pesanan berhasil diambil.'
      });
      setTimeout(() => {
        location.reload();
      }, 500);
    } else {
      Toast.fire({
        icon: 'error',
        title: 'Gagal Mengambil Pesanan',
        text: data.message || 'Pesanan sudah diambil oleh driver lain.'
      });
      if (cardEl) cardEl.remove();
      syncDriverLiveDashboard();
    }
  } catch (err) {
    console.error(err);
    Toast.fire({
      icon: 'error',
      title: 'Koneksi Terputus',
      text: 'Gagal terhubung ke server.'
    });
    if (btnEl) {
      btnEl.disabled = false;
      btnEl.innerHTML = '<i class="bi bi-check-lg"></i> Ambil Order';
    }
  }
}

// Update Delivery Status (Picked up or Delivered with OTP)
async function updateDeliveryStep(orderId, status) {
  let otp = '';

  if (status === 'delivered') {
    const { value: inputOtp } = await Swal.fire({
      title: 'Verifikasi Kode OTP',
      text: 'Minta 4 digit kode OTP yang ada di HP customer untuk menyelesaikan pengantaran:',
      input: 'text',
      inputPlaceholder: 'Contoh: 4829',
      showCancelButton: true,
      confirmButtonText: 'Selesaikan Order',
      cancelButtonText: 'Batal',
      confirmButtonColor: '#EE2737',
      inputValidator: (value) => {
        if (!value || value.length < 4) {
          return 'Masukkan 4 digit OTP dengan benar!';
        }
      }
    });

    if (!inputOtp) return;
    otp = inputOtp;
  }

  try {
    const formData = new FormData();
    formData.append('order_id', orderId);
    formData.append('status', status);
    formData.append('otp', otp);

    const res = await fetch(API_BASE + '/delivery/update-status', {
      method: 'POST',
      body: formData
    });

    const data = await res.json();
    if (data.success) {
      Toast.fire({
        icon: 'success',
        title: status === 'delivered' ? 'Pengantaran Selesai! 🎉' : 'Pesanan Dijemput! 🛵',
        text: data.message || 'Status pengantaran berhasil diperbarui.'
      });
      setTimeout(() => {
        location.reload();
      }, 500);
    } else {
      Toast.fire({
        icon: 'error',
        title: 'Gagal Memperbarui Status',
        text: data.message || 'Terjadi kesalahan pada server.'
      });
    }
  } catch (err) {
    console.error(err);
    Toast.fire({
      icon: 'error',
      title: 'Koneksi Terputus',
      text: 'Gagal terhubung ke server.'
    });
  }
}

window.toggleDriverStatus = toggleDriverStatus;
window.acceptDriverOrder = acceptDriverOrder;
window.updateDeliveryStep = updateDeliveryStep;

// Background GPS Broadcasting with smart 12-second throttle
let lastGpsSentTime = 0;
function startDriverGpsTracking() {
  if ('geolocation' in navigator) {
    navigator.geolocation.watchPosition((pos) => {
      const lat = pos.coords.latitude;
      const lng = pos.coords.longitude;
      const now = Date.now();

      // Update in-memory driver coordinates for map
      if (typeof window.updateDriverLiveLocation === 'function') {
        window.updateDriverLiveLocation(lat, lng, false);
      } else {
        if (window.myDriverMarker) {
          window.myDriverMarker.setLatLng([lat, lng]);
        }
        window.driverLat = lat;
        window.driverLng = lng;

        // Throttle server HTTP post to at most once per 8 seconds
        if (now - lastGpsSentTime >= 8000) {
          lastGpsSentTime = now;
          const fd = new FormData();
          fd.append('lat', lat);
          fd.append('lng', lng);
          fetch(API_BASE + '/delivery/update-location', {
            method: 'POST',
            body: fd
          }).catch(() => {});
        }
      }
    }, (err) => {
      console.warn('Geolocation watch error:', err);
    }, {
      enableHighAccuracy: true,
      maximumAge: 10000,
      timeout: 8000
    });
  }
}

// Live Driver Dashboard Real-Time Polling Engine
async function syncDriverLiveDashboard() {
  try {
    const res = await fetch(API_BASE + '/delivery/live-dashboard');
    if (!res.ok) {
      console.warn(`[📡 Radar Sync] Response status error: ${res.status}`);
      return;
    }
    const contentType = res.headers.get('content-type');
    if (!contentType || !contentType.includes('application/json')) {
      console.warn('[📡 Radar Sync] Non-JSON response received');
      return;
    }
    const json = await res.json();
    if (!json.success || !json.data) {
      console.warn('[📡 Radar Sync] Invalid JSON response:', json);
      return;
    }

    const data = json.data;
    const timeStr = new Date().toLocaleTimeString('id-ID');
    const availableCount = data.available_count || 0;

    console.log(
      `%c[📡 Radar Sync ${timeStr}]%c Status: ${data.is_online ? 'ONLINE' : 'OFFLINE'} | Aktif: ${data.active_order ? '#' + data.active_order.order_code : 'Kosong'} | Order Radar: ${availableCount}`,
      'color: #EE2737; font-weight: bold;',
      'color: #0284c7; font-weight: 500;',
      data.available_orders || []
    );

    if (availableCount === 0 && data.debug_db_orders && data.debug_db_orders.length > 0) {
      console.log('📋 [Database 5 Order Terakhir]:', data.debug_db_orders);
    }

    window.HAS_ACTIVE_ORDER = !!data.has_active_order;

    // 1. Sync Wallet & Completed Orders & Driver Rating
    const walletEl = document.getElementById('driverWalletBalanceText');
    if (walletEl) {
      walletEl.textContent = formatRupiahJs(data.wallet_balance || 0);
    }
    const ordersCountEl = document.getElementById('driverTotalOrdersText');
    if (ordersCountEl) {
      ordersCountEl.textContent = data.total_orders || 0;
    }
    const headerRatingEl = document.getElementById('headerDriverRatingText');
    if (headerRatingEl && data.rating !== undefined) {
      headerRatingEl.textContent = Number(data.rating).toFixed(1);
    }
    const cardRatingEl = document.getElementById('driverRatingValueHeader');
    if (cardRatingEl && data.rating !== undefined) {
      cardRatingEl.textContent = Number(data.rating).toFixed(1);
    }
    const cardReviewsEl = document.getElementById('driverReviewsCountHeader');
    if (cardReviewsEl && data.reviews_count !== undefined) {
      cardReviewsEl.textContent = data.reviews_count;
    }

    // 2. Sync Chat unread dot
    const unreadDots = [document.getElementById('driverChatUnreadDot1'), document.getElementById('driverChatUnreadDot2')];
    unreadDots.forEach(dot => {
      if (dot) {
        if (data.unread_chats > 0 && !window.isChatModalOpen) {
          dot.classList.remove('d-none');
        } else {
          dot.classList.add('d-none');
        }
      }
    });

    // 3. Detect changes in Active Order status
    const currentActiveStatus = data.active_order ? data.active_order.order_status : null;
    if (prevActiveOrderStatus !== null && prevActiveOrderStatus !== currentActiveStatus) {
      // Status transition happened (e.g. order finished, canceled, or updated) -> reload UI state cleanly
      location.reload();
      return;
    }
    prevActiveOrderStatus = currentActiveStatus;

    // 4. Sync Available Orders in Radar
    const badgeEl = document.getElementById('radarOrderCountBadge');
    if (badgeEl) {
      if (!data.has_active_order && data.is_online && availableCount > 0) {
        badgeEl.textContent = `${availableCount} Order Siap`;
        badgeEl.classList.remove('d-none');
      } else {
        badgeEl.classList.add('d-none');
      }
    }

    // Check for incoming new order alert
    if (prevAvailableCount !== -1 && availableCount > prevAvailableCount && !data.has_active_order && data.is_online) {
      playNewOrderChime();
      if (typeof Swal !== 'undefined') {
        const Toast = Swal.mixin({
          toast: true,
          position: 'top-end',
          showConfirmButton: false,
          timer: 4000,
          timerProgressBar: true
        });
        Toast.fire({
          icon: 'info',
          title: `🔔 Ada ${availableCount - prevAvailableCount} pesanan baru masuk di Cicalengka!`
        });
      }
    }
    prevAvailableCount = availableCount;

    // Render available orders dynamic list if no active order
    const radarContainer = document.getElementById('driverRadarOrderSection');
    if (radarContainer && !data.has_active_order && data.is_online) {
      if (availableCount === 0) {
        radarContainer.innerHTML = `
          <div class="p-4 bg-white rounded-4 border text-center text-muted small shadow-sm">
              <div class="radar-scan-box">
                  <i class="bi bi-broadcast fs-3" style="color: #EE2737;"></i>
              </div>
              <div class="fw-bold text-dark mb-1">Memindai orderan baru...</div>
              <div class="text-muted" style="font-size: 11px;">Radar aktif di area Cicalengka. Pesanan terdekat akan otomatis muncul di sini.</div>
          </div>
        `;
      } else if (data.available_orders && data.available_orders.length > 0) {
        let cardsHtml = '<div class="d-flex flex-column gap-3" id="availableOrdersList">';
        data.available_orders.forEach(ord => {
          const comm = formatRupiahJs(Number(ord.delivery_charge || 0) * 0.85);
          const storeName = ord.store_name || 'Cicalengka Resto / Toko';
          const storeAddr = ord.store_address || 'Pusat Cicalengka';
          const custAddr = (ord.delivery_address && ord.delivery_address.address) ? ord.delivery_address.address : 'Cicalengka';
          const dist = ord.distance_km || '1.5';
          const ordCode = ord.order_code || ord.id;
          const storeNames = ord.store_names || [];

          let storeHtml = '';
          if (storeNames.length > 1) {
            let listHtml = storeNames.map((sn, idx) => `
              <div class="d-flex align-items-center gap-2">
                <span class="badge rounded-circle text-white d-inline-flex align-items-center justify-content-center flex-shrink-0"
                      style="width: 18px; height: 18px; font-size: 9.5px; background: #EE2737;">${idx + 1}</span>
                <span class="fw-bold text-dark text-truncate" style="font-size: 11.5px;">${sn}</span>
              </div>
            `).join('');

            storeHtml = `
              <div class="mb-2">
                <div class="d-flex align-items-center gap-1.5 mb-1.5">
                  <span class="badge rounded-pill px-2.5 py-1 text-white" style="font-size: 10px; font-weight: 700; background: #0284C7;">
                    <i class="bi bi-shop-window me-1"></i> Multi-Store Order (${storeNames.length} Toko)
                  </span>
                </div>
                <div class="p-2.5 bg-light rounded-3 border d-flex flex-column gap-1.5" style="font-size: 11px;">
                  ${listHtml}
                </div>
              </div>
            `;
          } else {
            storeHtml = `
              <div class="d-flex align-items-start gap-2 mb-2">
                <i class="bi bi-shop" style="font-size: 15px; margin-top: 1px; color: #EE2737;"></i>
                <div>
                  <div class="fw-bold small text-dark">${storeName}</div>
                  <div class="text-muted" style="font-size: 11px;">${storeAddr}</div>
                </div>
              </div>
            `;
          }

          let itemsHtml = '';
          if (ord.items && ord.items.length > 0) {
            const visibleItems = ord.items.slice(0, 3);
            const itemList = visibleItems.map(it => `
              <div class="d-flex align-items-center justify-content-between text-dark">
                <span><b class="text-danger">${it.quantity || 1}x</b> ${it.product_name || it.item_name || it.name || 'Menu'}</span>
                <span class="text-muted" style="font-size: 10px;">${formatRupiahJs(it.price || 0)}</span>
              </div>
            `).join('');
            const moreCount = ord.items.length > 3 ? `<div class="text-muted fst-italic" style="font-size: 10px;">+ ${ord.items.length - 3} menu lainnya</div>` : '';

            itemsHtml = `
              <div class="p-2 bg-light rounded-3 border-start border-3 border-danger mb-2.5" style="font-size: 11px;">
                <div class="text-muted fw-bold mb-1" style="font-size: 9.5px; text-transform: uppercase;">
                  <i class="bi bi-bag-check me-1"></i> Detail Pesanan:
                </div>
                <div class="d-flex flex-column gap-1">
                  ${itemList}
                  ${moreCount}
                </div>
              </div>
            `;
          }

          cardsHtml += `
            <div class="p-3 bg-white rounded-4 border shadow-sm order-incoming-card" id="avail-order-${ord.id}">
                <div class="d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom">
                    <div class="d-flex align-items-center gap-1.5">
                        <span class="badge rounded-pill px-2.5 py-1 text-white" style="font-size: 10px; font-weight: 700; background: #EE2737;">
                            <i class="bi bi-box-seam me-1"></i> Order Masuk
                        </span>
                        <span class="text-muted" style="font-size: 11px;">#${ordCode}</span>
                    </div>
                    <div class="text-end">
                        <div class="text-muted" style="font-size: 10px; font-weight: 600;">Est. Komisi:</div>
                        <span class="fw-bold text-success" style="font-size: 14px;">+ ${comm}</span>
                    </div>
                </div>

                ${storeHtml}
                ${itemsHtml}

                <div class="d-flex align-items-start gap-2 mb-3">
                    <i class="bi bi-geo-alt-fill" style="font-size: 15px; margin-top: 1px; color: #EE2737;"></i>
                    <div>
                        <div class="fw-bold small text-dark">Antar ke:</div>
                        <div class="text-muted" style="font-size: 11px;">${custAddr}</div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                    <span class="small text-muted fw-semibold" style="font-size: 11px;">
                        <i class="bi bi-signpost-2 me-1" style="color: #EE2737;"></i> Est. Jarak: ${dist} Km
                    </span>
                    <button onclick="acceptDriverOrder(${ord.id})" class="btn text-white btn-sm fw-bold px-4 rounded-pill shadow-sm" style="background: #EE2737; font-size: 11px;">
                        <i class="bi bi-check-lg me-1"></i> Ambil Order
                    </button>
                </div>
            </div>
          `;
        });
        cardsHtml += '</div>';
        radarContainer.innerHTML = cardsHtml;
      }
    }
  } catch (e) {
    console.warn('Driver dashboard sync poll error:', e);
  }
}

function initDeliveryPwaApp() {
  try {
    startDriverGpsTracking();
  } catch (e) {
    console.warn('GPS tracking init error:', e);
  }

  // Start 3-second live auto-sync polling on driver dashboard
  if (document.getElementById('driver-radar-map') || document.getElementById('driverRadarOrderSection') || document.getElementById('driverWalletBalanceText')) {
    console.log('[CicalengkaGO] Mitra Driver real-time radar sync started.');
    syncDriverLiveDashboard();
    setInterval(syncDriverLiveDashboard, 3000);
  }
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initDeliveryPwaApp);
} else {
  initDeliveryPwaApp();
}


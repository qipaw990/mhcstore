/**
 * Delivery Driver PWA Real-Time Sync Engine
 * CicalengkaGO Delivery Platform
 */

const API_BASE = window.BASE_URL || '';
let prevAvailableCount = -1;
let prevActiveOrderStatus = null;
let audioCtx = null;

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

// Toggle Driver Online Status
async function toggleDriverStatus() {
  try {
    const res = await fetch(API_BASE + '/delivery/toggle-online', { method: 'POST' });
    const data = await res.json();
    if (data.success) {
      // Sync live instead of full reload if available
      syncDriverLiveDashboard();
      location.reload();
    }
  } catch (err) {
    console.error(err);
  }
}

// Accept Incoming Order
async function acceptDriverOrder(orderId) {
  if (window.HAS_ACTIVE_ORDER) {
    Swal.fire({
      title: 'Pesanan Masih Aktif!',
      text: 'Anda sedang menjalankan pengantaran pesanan. Harap selesaikan pengantaran saat ini terlebih dahulu sebelum mengambil pesanan baru!',
      icon: 'warning',
      confirmButtonColor: '#EE2737'
    });
    return;
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
      Swal.fire({
        title: 'Pesanan Diterima!',
        text: data.message,
        icon: 'success',
        confirmButtonColor: '#EE2737'
      }).then(() => {
        location.reload();
      });
    } else {
      Swal.fire({
        title: 'Gagal',
        text: data.message,
        icon: 'error',
        confirmButtonColor: '#EE2737'
      });
    }
  } catch (err) {
    console.error(err);
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
      Swal.fire({
        title: 'Berhasil!',
        text: data.message,
        icon: 'success',
        confirmButtonColor: '#EE2737'
      }).then(() => {
        location.reload();
      });
    } else {
      Swal.fire({
        title: 'Gagal',
        text: data.message,
        icon: 'error',
        confirmButtonColor: '#EE2737'
      });
    }
  } catch (err) {
    console.error(err);
  }
}

// Background GPS Broadcasting (every 8 seconds while online)
function startDriverGpsTracking() {
  if ('geolocation' in navigator) {
    navigator.geolocation.watchPosition((pos) => {
      const lat = pos.coords.latitude;
      const lng = pos.coords.longitude;

      const fd = new FormData();
      fd.append('lat', lat);
      fd.append('lng', lng);

      fetch(API_BASE + '/delivery/update-location', {
        method: 'POST',
        body: fd
      }).catch(() => {});
    }, (err) => {
      console.warn('Geolocation watch error:', err);
    }, {
      enableHighAccuracy: true,
      maximumAge: 10000,
      timeout: 5000
    });
  }
}

// Live Driver Dashboard Real-Time Polling Engine
async function syncDriverLiveDashboard() {
  try {
    const res = await fetch(API_BASE + '/delivery/live-dashboard');
    if (!res.ok) return;
    const json = await res.json();
    if (!json.success || !json.data) return;

    const data = json.data;
    window.HAS_ACTIVE_ORDER = !!data.has_active_order;

    // 1. Sync Wallet & Completed Orders
    const walletEl = document.getElementById('driverWalletBalanceText');
    if (walletEl) {
      walletEl.textContent = formatRupiahJs(data.wallet_balance || 0);
    }
    const ordersCountEl = document.getElementById('driverTotalOrdersText');
    if (ordersCountEl) {
      ordersCountEl.textContent = data.total_orders || 0;
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
    const availableCount = data.available_count || 0;
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
                        <div class="text-muted" style="font-size: 10px; font-weight: 600;">Komisi Kurir:</div>
                        <span class="fw-bold text-success" style="font-size: 14px;">+ ${comm}</span>
                    </div>
                </div>

                <div class="d-flex align-items-start gap-2 mb-2">
                    <i class="bi bi-shop" style="font-size: 15px; margin-top: 1px; color: #EE2737;"></i>
                    <div>
                        <div class="fw-bold small text-dark">${storeName}</div>
                        <div class="text-muted" style="font-size: 11px;">${storeAddr}</div>
                    </div>
                </div>

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
                    <button onclick="acceptDriverOrder(${ord.id})" class="btn text-white btn-sm fw-bold px-4 rounded-pill shadow-sm" style="background: #EE2737;">
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

document.addEventListener('DOMContentLoaded', () => {
  startDriverGpsTracking();
  // Start 3-second live auto-sync polling on driver dashboard
  if (document.getElementById('driver-radar-map') || document.getElementById('driverRadarOrderSection')) {
    syncDriverLiveDashboard();
    setInterval(syncDriverLiveDashboard, 3000);
  }
});

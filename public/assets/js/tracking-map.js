/**
 * Live GPS Order Tracking Engine (Leaflet + OpenStreetMap)
 * Realtime Fleet & Order Monitoring for CicalengkaGO
 * Provides instant reactive synchronization for Order Status, Driver Location & Stepper UI
 */

let trackingMap = null;
let driverMarker = null;
let storeMarker = null;
let customerMarker = null;
let routePolyline = null;
let currentTrackingData = null;
let pollTimer = null;
let animationFrameId = null;
let lastDeliveredNotified = false;

// Smooth marker interpolation state
let animStartPos = null;
let animTargetPos = null;
let animStartTime = null;
const ANIMATION_DURATION = 1800; // 1.8 seconds smooth easing

function calculateHaversineDistance(lat1, lon1, lat2, lon2) {
  const R = 6371; // Earth radius in km
  const dLat = (lat2 - lat1) * Math.PI / 180;
  const dLon = (lon2 - lon1) * Math.PI / 180;
  const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
            Math.sin(dLon / 2) * Math.sin(dLon / 2);
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  return R * c; // Distance in km
}

function createDriverIcon() {
  return L.divIcon({
    className: 'custom-map-icon driver-pulse-container',
    html: `<div style="position:relative;width:52px;height:52px;display:flex;align-items:center;justify-content:center;">
             <div class="pulse-ring-outer" style="position:absolute;width:100%;height:100%;border-radius:50%;background:rgba(13,110,253,0.25);animation:radar-pulse 2s infinite ease-out;"></div>
             <div class="pulse-ring-inner" style="position:absolute;width:75%;height:75%;border-radius:50%;background:rgba(13,110,253,0.35);animation:radar-pulse 2s infinite ease-out 0.5s;"></div>
             <div style="background:linear-gradient(135deg, #0d6efd, #1e40af);color:white;width:38px;height:38px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:3px solid white;box-shadow:0 6px 18px rgba(13,110,253,0.6);z-index:3;">
               <i class="bi bi-bicycle" style="font-size:20px;"></i>
             </div>
           </div>`,
    iconSize: [52, 52],
    iconAnchor: [26, 26]
  });
}

function initOrderTrackingMap(orderCode, initialData) {
  currentTrackingData = initialData;
  if (initialData.order_status === 'delivered') {
    lastDeliveredNotified = true;
  }

  const defaultCenter = [
    initialData.destination?.lat || initialData.store?.lat || -6.9840,
    initialData.destination?.lng || initialData.store?.lng || 107.8340
  ];

  if (trackingMap) {
    trackingMap.remove();
    trackingMap = null;
  }

  const mapContainer = document.getElementById('tracking-map');
  if (!mapContainer) return;

  trackingMap = L.map('tracking-map', {
    zoomControl: true,
    attributionControl: false
  }).setView(defaultCenter, 15);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19
  }).addTo(trackingMap);

  // Store Marker
  const storeIcon = L.divIcon({
    className: 'custom-map-icon',
    html: `<div style="box-sizing:border-box;background:linear-gradient(135deg, #ef4444, #b91c1c);color:white;width:38px;height:38px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:3px solid white;box-shadow:0 6px 16px rgba(239,68,68,0.4);">
             <i class="bi bi-shop" style="font-size:18px;"></i>
           </div>`,
    iconSize: [38, 38],
    iconAnchor: [19, 19]
  });

  // Customer Destination Marker (Always strictly locked from checkout coordinates)
  const customerIcon = L.divIcon({
    className: 'custom-map-icon',
    html: `<div style="box-sizing:border-box;background:linear-gradient(135deg, #10b981, #047857);color:white;width:38px;height:38px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:3px solid white;box-shadow:0 6px 16px rgba(16,185,129,0.4);">
             <i class="bi bi-geo-alt-fill" style="font-size:18px;"></i>
           </div>`,
    iconSize: [38, 38],
    iconAnchor: [19, 19]
  });

  // Place Store Marker
  if (initialData.store && initialData.store.lat) {
    storeMarker = L.marker([initialData.store.lat, initialData.store.lng], { icon: storeIcon })
      .bindPopup(`<div class="p-1"><b>${escapeHtml(initialData.store.name)}</b><br><span class="badge bg-danger-subtle text-danger small mt-1">Titik Penjemputan</span></div>`)
      .addTo(trackingMap);
  }

  // Place Customer Destination Marker
  if (initialData.destination && initialData.destination.lat) {
    customerMarker = L.marker([initialData.destination.lat, initialData.destination.lng], { icon: customerIcon })
      .bindPopup(`<div class="p-1"><b>Tujuan Pengantaran</b><br><small class="text-muted">${escapeHtml(initialData.destination.address || 'Cicalengka')}</small></div>`)
      .addTo(trackingMap);
  }

  // Place Driver Marker if driver is assigned
  if (initialData.driver && initialData.driver.assigned && initialData.driver.lat) {
    driverMarker = L.marker([initialData.driver.lat, initialData.driver.lng], { icon: createDriverIcon() })
      .bindPopup(`<div class="p-1"><b>Kurir: ${escapeHtml(initialData.driver.name)}</b><br><span class="badge bg-primary-subtle text-primary small mt-1"><i class="bi bi-broadcast me-1"></i> Live GPS Aktif</span></div>`)
      .addTo(trackingMap);
  }

  try { updateTrackingStatusUI(initialData.order_status, initialData); } catch (e) { console.error('Initial status UI sync error:', e); }
  try { updateLiveMetrics(initialData); } catch (e) { console.error('Initial metrics error:', e); }
  try { updateDriverCardUI(initialData.driver); } catch (e) { console.error('Initial driver card error:', e); }
  try { drawRoutePolylines(initialData); } catch (e) { console.error('Initial route polyline error:', e); }
  try { fitAllMarkers(); } catch (e) { console.error('Initial fit bounds error:', e); }

  if (!initialData.driver?.assigned && initialData.order_status !== 'canceled' && initialData.order_status !== 'delivered') {
    const remaining = typeof initialData.remaining_seconds === 'number' ? initialData.remaining_seconds : 60;
    startDriverSearchTimer(remaining);
  }

  // Clear any existing poll
  if (pollTimer) clearInterval(pollTimer);

  // Poll real-time live location & reactive order status every 2.5 seconds
  pollTimer = setInterval(() => {
    pollLiveTracking(orderCode);
  }, 2500);
}

function updateLiveMetrics(data) {
  if (!data) return;

  if (data.order_status === 'delivered') {
    const distBadge = document.getElementById('live-distance-text');
    if (distBadge) {
      distBadge.innerHTML = `<i class="bi bi-check-circle-fill text-success me-1"></i> Pesanan Sampai di Tujuan`;
    }
    const etaBadge = document.getElementById('live-eta-text');
    if (etaBadge) {
      etaBadge.innerHTML = `<i class="bi bi-shield-check text-white me-1"></i> Selesai`;
    }
    const radarStatus = document.getElementById('live-radar-status');
    if (radarStatus) {
      radarStatus.innerHTML = `<span class="live-dot me-1" style="background:#10B981;"></span> Pengantaran Selesai`;
    }
    return;
  }

  const dLat = data.driver?.lat;
  const dLng = data.driver?.lng;
  const cLat = data.destination?.lat;
  const cLng = data.destination?.lng;
  const sLat = data.store?.lat;
  const sLng = data.store?.lng;

  // Determine target for driver based on status
  let targetLat = cLat;
  let targetLng = cLng;
  let targetLabel = "ke Alamat Anda";

  if (data.order_status === 'confirmed' || data.order_status === 'processing') {
    targetLat = sLat;
    targetLng = sLng;
    targetLabel = "ke Lokasi Resto";
  }

  if (dLat && dLng && targetLat && targetLng) {
    const distKm = calculateHaversineDistance(dLat, dLng, targetLat, targetLng);
    const distText = distKm < 1 ? `${Math.round(distKm * 1000)} Meter` : `${distKm.toFixed(1)} Km`;
    
    // Calculate ETA (average speed 25 km/h in Cicalengka)
    const etaMinutes = Math.max(1, Math.round((distKm / 25) * 60));

    const distBadge = document.getElementById('live-distance-text');
    if (distBadge) {
      distBadge.innerHTML = `<i class="bi bi-pin-map-fill text-danger me-1"></i> ${distText} ${targetLabel}`;
    }

    const etaBadge = document.getElementById('live-eta-text');
    if (etaBadge) {
      etaBadge.innerHTML = `<i class="bi bi-stopwatch-fill text-white me-1"></i> Estimasi ±${etaMinutes} Menit`;
    }

    const radarStatus = document.getElementById('live-radar-status');
    if (radarStatus) {
      radarStatus.innerHTML = `<span class="live-dot me-1"></span> Live GPS Terhubung`;
    }
  }
}

function drawRoutePolylines(data) {
  if (routePolyline && trackingMap) {
    trackingMap.removeLayer(routePolyline);
    routePolyline = null;
  }

  if (!trackingMap) return;

  const sLat = data.store?.lat;
  const sLng = data.store?.lng;
  const cLat = data.destination?.lat;
  const cLng = data.destination?.lng;
  const dLat = data.driver?.lat || sLat;
  const dLng = data.driver?.lng || sLng;

  const points = [];

  if (data.order_status === 'confirmed' || data.order_status === 'processing') {
    if (dLat && sLat) {
      points.push([dLat, dLng], [sLat, sLng]);
    }
  } else {
    if (dLat && cLat) {
      points.push([dLat, dLng], [cLat, cLng]);
    }
  }

  if (points.length >= 2) {
    routePolyline = L.polyline(points, {
      color: '#EE2737',
      weight: 4,
      dashArray: '6, 8',
      opacity: 0.85,
      lineCap: 'round',
      lineJoin: 'round'
    }).addTo(trackingMap);
  }
}

function smoothMoveMarker(targetLat, targetLng) {
  if (!driverMarker) return;

  const currentLatLng = driverMarker.getLatLng();
  if (Math.abs(currentLatLng.lat - targetLat) < 0.000001 && Math.abs(currentLatLng.lng - targetLng) < 0.000001) return;

  animStartPos = [currentLatLng.lat, currentLatLng.lng];
  animTargetPos = [targetLat, targetLng];
  animStartTime = performance.now();

  if (animationFrameId) {
    cancelAnimationFrame(animationFrameId);
  }

  function step(timestamp) {
    const elapsed = timestamp - animStartTime;
    const progress = Math.min(elapsed / ANIMATION_DURATION, 1);

    // Ease-out cubic formula
    const ease = 1 - Math.pow(1 - progress, 3);

    const lat = animStartPos[0] + (animTargetPos[0] - animStartPos[0]) * ease;
    const lng = animStartPos[1] + (animTargetPos[1] - animStartPos[1]) * ease;

    driverMarker.setLatLng([lat, lng]);

    if (progress < 1) {
      animationFrameId = requestAnimationFrame(step);
    }
  }

  animationFrameId = requestAnimationFrame(step);
}

async function pollLiveTracking(orderCode) {
  try {
    const res = await fetch(`${window.BASE_URL}/orders/${orderCode}/live-tracking`);
    if (!res.ok) return;
    const json = await res.json();

    if (json.success && json.data) {
      const d = json.data;
      const prevStatus = currentTrackingData ? currentTrackingData.order_status : null;
      currentTrackingData = d;

      // Celebrate when transitioning to delivered state
      if (d.order_status === 'delivered' && !lastDeliveredNotified && prevStatus !== 'delivered') {
        lastDeliveredNotified = true;
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            title: 'Pesanan Selesai Diantar! 🎉',
            text: 'Pesanan Anda telah berhasil diterima. Terima kasih telah menggunakan CicalengkaGO!',
            icon: 'success',
            confirmButtonColor: '#EE2737'
          });
        }
      }

      // Update Driver Marker smoothly
      if (d.driver && d.driver.assigned && d.driver.lat && d.driver.lng) {
        if (driverMarker) {
          smoothMoveMarker(d.driver.lat, d.driver.lng);
        } else if (trackingMap) {
          driverMarker = L.marker([d.driver.lat, d.driver.lng], { icon: createDriverIcon() })
            .bindPopup(`<div class="p-1"><b>Kurir: ${escapeHtml(d.driver.name)}</b><br><span class="badge bg-primary-subtle text-primary small mt-1"><i class="bi bi-broadcast me-1"></i> Live GPS Aktif</span></div>`)
            .addTo(trackingMap);
        }
      }

      // Auto-sync Chat Unread Dot
      const unreadDot = document.getElementById('chatUnreadDot');
      if (unreadDot) {
        if (d.unread_chats > 0 && typeof isChatModalOpen !== 'undefined' && !isChatModalOpen) {
          unreadDot.classList.remove('d-none');
        } else if (d.unread_chats === 0) {
          unreadDot.classList.add('d-none');
        }
      }

      // Update status and UI components reliably
      try { updateTrackingStatusUI(d.order_status, d); } catch (e) { console.error('Status UI sync error:', e); }
      try { updateLiveMetrics(d); } catch (e) { console.error('Live metrics error:', e); }
      try { updateDriverCardUI(d.driver); } catch (e) { console.error('Driver card sync error:', e); }
      try { drawRoutePolylines(d); } catch (e) { console.error('Polyline sync error:', e); }
    }
  } catch (err) {
    console.warn('Live tracking update failed:', err);
  }
}

function fitAllMarkers() {
  if (!trackingMap) return;
  const group = [];
  if (storeMarker) group.push(storeMarker);
  if (customerMarker) group.push(customerMarker);
  if (driverMarker) group.push(driverMarker);

  if (group.length > 0) {
    const featureGroup = L.featureGroup(group);
    trackingMap.fitBounds(featureGroup.getBounds().pad(0.2));
  }
}

function centerOnDriver() {
  if (driverMarker && trackingMap) {
    trackingMap.flyTo(driverMarker.getLatLng(), 16, {
      duration: 1.2
    });
  } else {
    fitAllMarkers();
  }
}

let searchTimerInterval = null;
let currentTimerSeconds = 60;

function startDriverSearchTimer(initialRemainingSecs) {
  if (searchTimerInterval) clearInterval(searchTimerInterval);
  if (typeof initialRemainingSecs === 'number') {
    currentTimerSeconds = Math.max(0, initialRemainingSecs);
  }

  function updateTimer() {
    const clockEl = document.getElementById('search-timer-clock');
    const secEl = document.getElementById('search-timer-sec');
    const progressEl = document.getElementById('search-timer-progress');

    const remaining = Math.max(0, currentTimerSeconds);
    const mins = Math.floor(remaining / 60);
    const secs = remaining % 60;
    const formattedTime = String(mins).padStart(2, '0') + ':' + String(secs).padStart(2, '0');

    if (clockEl) clockEl.textContent = formattedTime;
    if (secEl) secEl.textContent = remaining + ' detik';
    if (progressEl) {
      const percent = Math.min(100, Math.max(0, (remaining / 60) * 100));
      progressEl.style.width = percent + '%';
    }

    if (currentTimerSeconds <= 0) {
      if (searchTimerInterval) {
        clearInterval(searchTimerInterval);
        searchTimerInterval = null;
      }
      if (typeof currentTrackingData !== 'undefined' && currentTrackingData?.order_code) {
        pollLiveTracking(currentTrackingData.order_code);
      }
    } else {
      currentTimerSeconds--;
    }
  }

  updateTimer();
  searchTimerInterval = setInterval(updateTimer, 1000);
}

function updateDriverCardUI(driver) {
  const assignedCard = document.getElementById('driver-assigned-card');
  const searchingCard = document.getElementById('driver-searching-card');
  const canceledCard = document.getElementById('order-canceled-card');
  const currentStatus = currentTrackingData ? currentTrackingData.order_status : null;

  if (currentStatus === 'canceled') {
    if (assignedCard) assignedCard.classList.add('d-none');
    if (searchingCard) searchingCard.classList.add('d-none');
    if (canceledCard) canceledCard.classList.remove('d-none');
    if (searchTimerInterval) {
      clearInterval(searchTimerInterval);
      searchTimerInterval = null;
    }
    return;
  }

  if (driver && driver.assigned) {
    if (assignedCard) assignedCard.classList.remove('d-none');
    if (searchingCard) searchingCard.classList.add('d-none');
    if (canceledCard) canceledCard.classList.add('d-none');
    if (searchTimerInterval) {
      clearInterval(searchTimerInterval);
      searchTimerInterval = null;
    }

    const avatarImg = document.getElementById('driver-avatar-img');
    const nameText = document.getElementById('driver-name-text');
    const vehicleText = document.getElementById('driver-vehicle-text');
    const callBtn = document.getElementById('driver-call-btn');

    if (avatarImg) {
      const avatarSrc = driver.avatar ? (driver.avatar.startsWith('http') ? driver.avatar : window.BASE_URL + '/' + driver.avatar) : (window.BASE_URL + '/assets/images/users/driver.png');
      avatarImg.src = avatarSrc;
    }

    if (nameText) {
      nameText.textContent = driver.name || 'Mitra Kurir Cicalengka';
    }

    if (vehicleText) {
      vehicleText.innerHTML = `<i class="bi bi-bicycle me-1 text-danger"></i>${escapeHtml(driver.vehicle || 'Motor')} • <b>${escapeHtml(driver.plate || 'D 1234 CCG')}</b>`;
    }

    if (callBtn) {
      if (driver.phone) {
        callBtn.href = 'tel:' + driver.phone;
        callBtn.classList.remove('d-none');
      } else {
        callBtn.classList.add('d-none');
      }
    }
  } else {
    if (assignedCard) assignedCard.classList.add('d-none');
    if (searchingCard) searchingCard.classList.remove('d-none');
    if (canceledCard) canceledCard.classList.add('d-none');
  }
}

function updateTrackingStatusUI(status, data) {
  // Top Badge Auto-Sync
  const badge = document.getElementById('order-status-badge');
  if (badge) {
    const statusMap = {
      'pending': { label: 'Menunggu Pembayaran', class: 'bg-warning text-dark' },
      'confirmed': { label: 'Pesanan Dikonfirmasi', class: 'bg-info text-dark' },
      'processing': { label: 'Sedang Disiapkan Resto', class: 'bg-warning text-dark' },
      'handover': { label: 'Diserahkan ke Kurir', class: 'bg-primary text-white' },
      'picked_up': { label: 'Pesanan Diambil Kurir', class: 'bg-primary text-white' },
      'on_the_way': { label: 'Kurir Menuju Lokasi Anda', class: 'bg-primary text-white' },
      'delivered': { label: 'Pesanan Selesai', class: 'bg-success text-white' },
      'canceled': { label: 'Pesanan Dibatalkan', class: 'bg-danger text-white' }
    };
    const s = statusMap[status] || { label: status.toUpperCase(), class: 'bg-secondary text-white' };
    badge.className = `badge px-3 py-1 text-uppercase fw-bold ${s.class}`;
    badge.textContent = s.label;
  }

  // Stepper Auto-Sync
  const s1 = document.querySelector('.stepper-container .step-1');
  const s2 = document.querySelector('.stepper-container .step-2');
  const s3 = document.querySelector('.stepper-container .step-3');
  const s4 = document.querySelector('.stepper-container .step-4');

  if (s1 && s2 && s3 && s4) {
    const icon1 = s1.querySelector('.step-dot i');
    const icon2 = s2.querySelector('.step-dot i');
    const icon3 = s3.querySelector('.step-dot i');
    const icon4 = s4.querySelector('.step-dot i');

    // Step 1: Confirmed
    s1.className = 'step-item step-1 completed';
    if (icon1) icon1.className = 'bi bi-check-lg';

    // Step 2: Processing & Prepared
    const isStep2Done = ['processing', 'handover', 'picked_up', 'on_the_way', 'delivered'].includes(status);
    const isStep2Active = (status === 'confirmed');
    s2.className = `step-item step-2 ${isStep2Done ? 'completed' : (isStep2Active ? 'active' : '')}`;
    if (icon2) icon2.className = isStep2Done ? 'bi bi-check-lg' : 'bi bi-egg-fried';

    // Step 3: Courier on the way
    const isStep3Done = ['on_the_way', 'delivered'].includes(status);
    const isStep3Active = ['processing', 'handover', 'picked_up'].includes(status);
    s3.className = `step-item step-3 ${isStep3Done ? 'completed' : (isStep3Active ? 'active' : '')}`;
    if (icon3) icon3.className = isStep3Done ? 'bi bi-check-lg' : 'bi bi-bicycle';

    // Step 4: Delivered
    const isStep4Done = (status === 'delivered');
    const isStep4Active = (status === 'on_the_way');
    s4.className = `step-item step-4 ${isStep4Done ? 'completed' : (isStep4Active ? 'active' : '')}`;
    if (icon4) icon4.className = isStep4Done ? 'bi bi-check-lg' : 'bi bi-geo-alt-fill';
  }

  // OTP Card vs Celebration Card vs Canceled Card Auto-Sync
  const otpCard = document.getElementById('otp-banner-card');
  const completedCard = document.getElementById('order-completed-card');
  const searchingCard = document.getElementById('driver-searching-card');
  const assignedCard = document.getElementById('driver-assigned-card');
  const canceledCard = document.getElementById('order-canceled-card');
  const paymentBadge = document.getElementById('payment-status-text');

  if (status === 'canceled') {
    if (otpCard) otpCard.classList.add('d-none');
    if (completedCard) completedCard.classList.add('d-none');
    if (searchingCard) searchingCard.classList.add('d-none');
    if (assignedCard) assignedCard.classList.add('d-none');
    if (canceledCard) canceledCard.classList.remove('d-none');
    if (data && data.cancellation_reason) {
      const reasonEl = document.getElementById('canceled-reason-text');
      if (reasonEl) reasonEl.textContent = data.cancellation_reason;
    }
    if (paymentBadge) {
      paymentBadge.textContent = 'DIBATALKAN';
      paymentBadge.className = 'fw-bold text-danger';
    }
  } else if (status === 'delivered') {
    if (otpCard) otpCard.classList.add('d-none');
    if (completedCard) completedCard.classList.remove('d-none');
    if (canceledCard) canceledCard.classList.add('d-none');
    if (paymentBadge) {
      paymentBadge.textContent = 'LUNAS';
      paymentBadge.className = 'fw-bold text-success';
    }
  } else {
    if (otpCard) otpCard.classList.remove('d-none');
    if (completedCard) completedCard.classList.add('d-none');
    if (canceledCard) canceledCard.classList.add('d-none');
  }

  // Stop polling if delivered or canceled to conserve resources
  if ((status === 'delivered' || status === 'canceled') && pollTimer) {
    clearInterval(pollTimer);
    pollTimer = null;
  }
}

function openGoogleMapsNav() {
  if (!currentTrackingData) return;
  const destLat = currentTrackingData.destination?.lat || -6.9840;
  const destLng = currentTrackingData.destination?.lng || 107.8340;
  const originLat = currentTrackingData.driver?.lat || currentTrackingData.store?.lat || -6.9835;
  const originLng = currentTrackingData.driver?.lng || currentTrackingData.store?.lng || 107.8335;

  const url = `https://www.google.com/maps/dir/?api=1&origin=${originLat},${originLng}&destination=${destLat},${destLng}&travelmode=two_wheeler`;
  window.open(url, '_blank');
}

/**
 * Interactive Driver GPS Simulator for Testing
 */
let simInterval = null;
let simStep = 0;
function toggleDriverSimulation(orderCode) {
  if (simInterval) {
    clearInterval(simInterval);
    simInterval = null;
    if (typeof Swal !== 'undefined') {
      Swal.fire({ icon: 'info', title: 'Simulasi Dihentikan', timer: 1500, showConfirmButton: false });
    }
    return;
  }

  if (!currentTrackingData) return;

  const sLat = currentTrackingData.store?.lat || -6.9835;
  const sLng = currentTrackingData.store?.lng || 107.8335;
  const cLat = currentTrackingData.destination?.lat || -6.9855;
  const cLng = currentTrackingData.destination?.lng || 107.8350;

  const totalSteps = 20;
  simStep = 0;

  if (typeof Swal !== 'undefined') {
    Swal.fire({
      icon: 'success',
      title: 'Simulasi GPS Aktif! 🚀',
      text: 'Kurir sedang bergerak live menuju titik tujuan.',
      timer: 2000,
      showConfirmButton: false
    });
  }

  simInterval = setInterval(async () => {
    simStep++;
    const t = simStep / totalSteps;
    const currentLat = sLat + (cLat - sLat) * t;
    const currentLng = sLng + (cLng - sLng) * t;

    // Send coordinates to server
    try {
      const fd = new FormData();
      fd.append('lat', currentLat);
      fd.append('lng', currentLng);
      await fetch(`${window.BASE_URL}/delivery/update-location`, {
        method: 'POST',
        body: fd
      });
    } catch (e) {}

    // Update local marker directly
    smoothMoveMarker(currentLat, currentLng);
    if (currentTrackingData.driver) {
      currentTrackingData.driver.lat = currentLat;
      currentTrackingData.driver.lng = currentLng;
      currentTrackingData.driver.assigned = true;
    }
    updateLiveMetrics(currentTrackingData);
    drawRoutePolylines(currentTrackingData);

    if (simStep >= totalSteps) {
      clearInterval(simInterval);
      simInterval = null;
    }
  }, 1800);
}

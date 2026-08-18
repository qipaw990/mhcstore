/**
 * Live GPS Order Tracking Engine (Leaflet + OpenStreetMap)
 * Realtime Fleet & Order Monitoring for CicalengkaGO
 * Provides instant reactive synchronization for Order Status, Driver Location & Stepper UI
 */

let trackingMap = null;
let driverMarker = null;
let storeMarkers = [];
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
  const svg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 46" width="32" height="46">
    <defs>
      <linearGradient id="dg" x1="0%" y1="0%" x2="100%" y2="100%">
        <stop offset="0%" stop-color="#3b82f6"/>
        <stop offset="100%" stop-color="#1d4ed8"/>
      </linearGradient>
    </defs>
    <path d="M16 2C9 2 3 8 3 15c0 10 13 29 13 29S29 25 29 15C29 8 23 2 16 2z" fill="url(#dg)" stroke="white" stroke-width="2"/>
    <circle cx="11" cy="17" r="3.5" fill="none" stroke="white" stroke-width="1.8"/>
    <circle cx="21" cy="17" r="3.5" fill="none" stroke="white" stroke-width="1.8"/>
    <polyline points="11,17 15,12 21,17" fill="none" stroke="white" stroke-width="1.8" stroke-linejoin="round" stroke-linecap="round"/>
    <line x1="15" y1="12" x2="15" y2="17" stroke="white" stroke-width="1.8" stroke-linecap="round"/>
    <circle cx="17" cy="10" r="2" fill="white"/>
  </svg>`;
  return L.icon({
    iconUrl: 'data:image/svg+xml,' + encodeURIComponent(svg),
    iconSize: [32, 46],
    iconAnchor: [16, 46],
    popupAnchor: [0, -46]
  });
}

function createStoreIcon(seq) {
  const badgeHtml = seq ? `<circle cx="24" cy="8" r="7.5" fill="#1e293b" stroke="white" stroke-width="1.5"/><text x="24" y="11" font-size="9" font-weight="800" fill="white" text-anchor="middle">${seq}</text>` : '';
  const storeSvg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 46" width="32" height="46">
    <defs>
      <linearGradient id="sg_${seq || 0}" x1="0%" y1="0%" x2="100%" y2="100%">
        <stop offset="0%" stop-color="#f87171"/>
        <stop offset="100%" stop-color="#b91c1c"/>
      </linearGradient>
    </defs>
    <path d="M16 2C9 2 3 8 3 15c0 10 13 29 13 29S29 25 29 15C29 8 23 2 16 2z" fill="url(#sg_${seq || 0})" stroke="white" stroke-width="2"/>
    <path d="M9 14 L9 12 Q9 10 16 10 Q23 10 23 12 L23 14 Q19.5 17 16 16 Q12.5 17 9 14z" fill="white"/>
    <rect x="11" y="14.5" width="10" height="6" rx="0.5" fill="white" opacity="0.25"/>
    <rect x="13" y="15" width="6" height="5.5" fill="white"/>
    <rect x="14.5" y="16" width="3" height="4.5" fill="#b91c1c"/>
    ${badgeHtml}
  </svg>`;
  return L.icon({
    iconUrl: 'data:image/svg+xml,' + encodeURIComponent(storeSvg),
    iconSize: [32, 46],
    iconAnchor: [16, 46],
    popupAnchor: [0, -46]
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

  // Customer Destination Marker — Green teardrop with person symbol
  const customerSvg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 46" width="32" height="46">
    <defs>
      <linearGradient id="cg" x1="0%" y1="0%" x2="100%" y2="100%">
        <stop offset="0%" stop-color="#34d399"/>
        <stop offset="100%" stop-color="#047857"/>
      </linearGradient>
    </defs>
    <path d="M16 2C9 2 3 8 3 15c0 10 13 29 13 29S29 25 29 15C29 8 23 2 16 2z" fill="url(#cg)" stroke="white" stroke-width="2"/>
    <circle cx="16" cy="11" r="3.8" fill="white"/>
    <path d="M9 22 Q9 17 16 17 Q23 17 23 22" fill="white"/>
  </svg>`;
  const customerIcon = L.icon({
    iconUrl: 'data:image/svg+xml,' + encodeURIComponent(customerSvg),
    iconSize: [32, 46],
    iconAnchor: [16, 46],
    popupAnchor: [0, -46]
  });

  // Place Store Markers (Support Multi-Store)
  storeMarkers.forEach(m => { if (trackingMap) trackingMap.removeLayer(m); });
  storeMarkers = [];

  const storesToRender = (initialData.batch_stores && initialData.batch_stores.length > 0)
    ? initialData.batch_stores
    : (initialData.store && initialData.store.lat ? [initialData.store] : []);

  storesToRender.forEach((st, idx) => {
    if (st && st.lat && st.lng) {
      const isMulti = storesToRender.length > 1;
      const seqNum = isMulti ? (idx + 1) : null;
      const popupText = `<div class="p-1"><b>${escapeHtml(st.name)}</b><br><span class="badge bg-danger-subtle text-danger small mt-1">Penjemputan ${isMulti ? 'Toko #' + (idx + 1) : 'Toko'}</span></div>`;
      const m = L.marker([st.lat, st.lng], { icon: createStoreIcon(seqNum), zIndexOffset: 100 + idx })
        .bindPopup(popupText)
        .addTo(trackingMap);
      storeMarkers.push(m);
    }
  });

  // Place Customer Destination Marker
  if (initialData.destination && initialData.destination.lat) {
    customerMarker = L.marker([initialData.destination.lat, initialData.destination.lng], { icon: customerIcon, zIndexOffset: 500 })
      .bindPopup(`<div class="p-1"><b>Tujuan Pengantaran</b><br><small class="text-muted">${escapeHtml(initialData.destination.address || 'Cicalengka')}</small></div>`)
      .addTo(trackingMap);
  }

  // Place Customer Destination Marker
  if (initialData.destination && initialData.destination.lat) {
    customerMarker = L.marker([initialData.destination.lat, initialData.destination.lng], { icon: customerIcon, zIndexOffset: 500 })
      .bindPopup(`<div class="p-1"><b>Tujuan Pengantaran</b><br><small class="text-muted">${escapeHtml(initialData.destination.address || 'Cicalengka')}</small></div>`)
      .addTo(trackingMap);
  }

  // Place Driver Marker if driver is assigned
  if (initialData.driver && initialData.driver.assigned && initialData.driver.lat) {
    driverMarker = L.marker([initialData.driver.lat, initialData.driver.lng], { icon: createDriverIcon(), zIndexOffset: 1000 })
      .bindPopup(`<div class="ccg-map-popup"><div class="popup-title">Kurir: ${escapeHtml(initialData.driver.name)}</div><span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2 py-0.5 mt-1" style="font-size:10px; font-weight: 600;"><i class="bi bi-broadcast me-1"></i> Live GPS Aktif</span></div>`)
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

  const storesToRender = (data.batch_stores && data.batch_stores.length > 0)
    ? data.batch_stores
    : (data.store && data.store.lat ? [data.store] : []);

  let totalDistKm = 0;

  if (dLat && dLng && data.driver?.assigned) {
    if (['on_the_way'].includes(data.order_status)) {
      // Driver directly heading to customer
      if (cLat && cLng) {
        totalDistKm = calculateHaversineDistance(dLat, dLng, cLat, cLng);
      }
    } else {
      // Driver heading to store(s) then customer
      let currentLat = dLat;
      let currentLng = dLng;
      storesToRender.forEach(st => {
        if (st && st.lat && st.lng) {
          totalDistKm += calculateHaversineDistance(currentLat, currentLng, st.lat, st.lng);
          currentLat = st.lat;
          currentLng = st.lng;
        }
      });
      if (cLat && cLng) {
        totalDistKm += calculateHaversineDistance(currentLat, currentLng, cLat, cLng);
      }
    }
  } else {
    // No driver assigned yet: calculate store(s) -> customer route distance
    if (storesToRender.length > 0) {
      let currentLat = storesToRender[0].lat;
      let currentLng = storesToRender[0].lng;
      for (let i = 1; i < storesToRender.length; i++) {
        const st = storesToRender[i];
        if (st && st.lat && st.lng) {
          totalDistKm += calculateHaversineDistance(currentLat, currentLng, st.lat, st.lng);
          currentLat = st.lat;
          currentLng = st.lng;
        }
      }
      if (cLat && cLng) {
        totalDistKm += calculateHaversineDistance(currentLat, currentLng, cLat, cLng);
      }
    } else if (cLat && cLng && data.store?.lat) {
      totalDistKm = calculateHaversineDistance(data.store.lat, data.store.lng, cLat, cLng);
    }
  }

  // Fallback to official order recorded distance if available
  if ((!totalDistKm || isNaN(totalDistKm)) && data.distance_km) {
    totalDistKm = parseFloat(data.distance_km);
  }

  const distKm = Math.max(0.3, Math.round(totalDistKm * 100) / 100);
  const distText = distKm < 1 ? `${Math.round(distKm * 1000)} Meter` : `${distKm} Km`;
  
  // Calculate ETA (average speed 25 km/h in Cicalengka)
  const etaMinutes = Math.max(1, Math.round((distKm / 25) * 60));

  const distBadge = document.getElementById('live-distance-text');
  if (distBadge) {
    distBadge.innerHTML = `<i class="bi bi-pin-map-fill text-danger me-1"></i> Jarak: ${distText}`;
  }

  const etaBadge = document.getElementById('live-eta-text');
  if (etaBadge) {
    etaBadge.innerHTML = `<i class="bi bi-stopwatch-fill text-white me-1"></i> Est. Tiba ±${etaMinutes} Menit`;
  }

  const radarStatus = document.getElementById('live-radar-status');
  if (radarStatus) {
    radarStatus.innerHTML = `<span class="live-dot me-1"></span> Live GPS Terhubung`;
  }
}

function drawRoutePolylines(data) {
  if (routePolyline && trackingMap) {
    trackingMap.removeLayer(routePolyline);
    routePolyline = null;
  }

  if (!trackingMap) return;

  const points = [];
  const dLat = data.driver?.lat;
  const dLng = data.driver?.lng;
  const cLat = data.destination?.lat;
  const cLng = data.destination?.lng;

  const storesToRender = (data.batch_stores && data.batch_stores.length > 0)
    ? data.batch_stores
    : (data.store && data.store.lat ? [data.store] : []);

  if (dLat && dLng && data.driver?.assigned) {
    points.push([dLat, dLng]);
    if (!['on_the_way'].includes(data.order_status)) {
      storesToRender.forEach(st => {
        if (st && st.lat && st.lng) {
          points.push([st.lat, st.lng]);
        }
      });
    }
  } else {
    storesToRender.forEach(st => {
      if (st && st.lat && st.lng) {
        points.push([st.lat, st.lng]);
      }
    });
  }

  if (cLat && cLng) {
    points.push([cLat, cLng]);
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
      
      if (d.batch_info && d.batch_info.stores && d.batch_info.stores.length > 0) {
        d.batch_stores = d.batch_info.stores;
      }
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

      // Sync store markers dynamically if count mismatch
      const storesToRender = (d.batch_stores && d.batch_stores.length > 0)
        ? d.batch_stores
        : (d.store && d.store.lat ? [d.store] : []);

      if (storesToRender.length !== storeMarkers.length && trackingMap) {
        storeMarkers.forEach(m => trackingMap.removeLayer(m));
        storeMarkers = [];
        storesToRender.forEach((st, idx) => {
          if (st && st.lat && st.lng) {
            const isMulti = storesToRender.length > 1;
            const seqNum = isMulti ? (idx + 1) : null;
            const m = L.marker([st.lat, st.lng], { icon: createStoreIcon(seqNum), zIndexOffset: 100 + idx })
              .bindPopup(`<div class="p-1"><b>${escapeHtml(st.name)}</b><br><span class="badge bg-danger-subtle text-danger small mt-1">Penjemputan ${isMulti ? 'Toko #' + (idx + 1) : 'Toko'}</span></div>`)
              .addTo(trackingMap);
            storeMarkers.push(m);
          }
        });
      }

      // Update Driver Marker smoothly
      if (d.driver && d.driver.assigned && d.driver.lat && d.driver.lng) {
        if (driverMarker) {
          smoothMoveMarker(d.driver.lat, d.driver.lng);
        } else if (trackingMap) {
          driverMarker = L.marker([d.driver.lat, d.driver.lng], { icon: createDriverIcon(), zIndexOffset: 1000 })
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
  storeMarkers.forEach(m => group.push(m));
  if (customerMarker) group.push(customerMarker);
  if (driverMarker) group.push(driverMarker);

  if (group.length > 0) {
    const featureGroup = L.featureGroup(group);
    trackingMap.fitBounds(featureGroup.getBounds().pad(0.25));
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

  // OTP Card vs Celebration Card vs Canceled Card vs Batch Notice Auto-Sync
  const otpCard = document.getElementById('otp-banner-card');
  const completedCard = document.getElementById('order-completed-card');
  const searchingCard = document.getElementById('driver-searching-card');
  const assignedCard = document.getElementById('driver-assigned-card');
  const canceledCard = document.getElementById('order-canceled-card');
  const paymentBadge = document.getElementById('payment-status-text');
  const batchNoticeCard = document.getElementById('batch-pickup-notice-card');

  if (status === 'canceled') {
    if (otpCard) otpCard.classList.add('d-none');
    if (completedCard) completedCard.classList.add('d-none');
    if (searchingCard) searchingCard.classList.add('d-none');
    if (assignedCard) assignedCard.classList.add('d-none');
    if (canceledCard) canceledCard.classList.remove('d-none');
    if (batchNoticeCard) batchNoticeCard.classList.add('d-none');
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
    if (batchNoticeCard) batchNoticeCard.classList.add('d-none');
    if (paymentBadge) {
      paymentBadge.textContent = 'LUNAS';
      paymentBadge.className = 'fw-bold text-success';
    }
  } else {
    if (otpCard) otpCard.classList.remove('d-none');
    if (completedCard) completedCard.classList.add('d-none');
    if (canceledCard) canceledCard.classList.add('d-none');
    if (batchNoticeCard) batchNoticeCard.classList.remove('d-none');
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

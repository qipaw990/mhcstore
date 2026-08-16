/**
 * Live GPS Order Tracking Engine (Leaflet + OpenStreetMap)
 * Realtime Fleet & Order Monitoring for CicalengkaGO
 */

let trackingMap = null;
let driverMarker = null;
let storeMarker = null;
let customerMarker = null;
let routePolyline = null;
let currentTrackingData = null;
let pollTimer = null;
let animationFrameId = null;

// Target position and start position for smooth interpolation
let animStartPos = null;
let animTargetPos = null;
let animStartTime = null;
const ANIMATION_DURATION = 2000; // 2 seconds smooth easing

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

function initOrderTrackingMap(orderCode, initialData) {
  currentTrackingData = initialData;

  const defaultCenter = [
    initialData.driver?.lat || initialData.store?.lat || -6.9840,
    initialData.driver?.lng || initialData.store?.lng || 107.8340
  ];

  if (trackingMap) {
    trackingMap.remove();
  }

  trackingMap = L.map('tracking-map', {
    zoomControl: true,
    attributionControl: false
  }).setView(defaultCenter, 15);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19
  }).addTo(trackingMap);

  // Store Icon (Red Gradient with shop pin)
  const storeIcon = L.divIcon({
    className: 'custom-map-icon',
    html: `<div style="background:linear-gradient(135deg, #ef4444, #b91c1c);color:white;width:38px;height:38px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:3px solid white;box-shadow:0 6px 16px rgba(239,68,68,0.4);">
             <i class="bi bi-shop" style="font-size:18px;"></i>
           </div>`,
    iconSize: [38, 38],
    iconAnchor: [19, 19]
  });

  // Driver Icon with Multi-ring Pulsating Radar Wave
  const driverIcon = L.divIcon({
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

  // Customer Destination Icon (Green with home pin)
  const customerIcon = L.divIcon({
    className: 'custom-map-icon',
    html: `<div style="background:linear-gradient(135deg, #10b981, #047857);color:white;width:38px;height:38px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:3px solid white;box-shadow:0 6px 16px rgba(16,185,129,0.4);">
             <i class="bi bi-geo-alt-fill" style="font-size:18px;"></i>
           </div>`,
    iconSize: [38, 38],
    iconAnchor: [19, 19]
  });

  // Place Store Marker
  if (initialData.store && initialData.store.lat) {
    storeMarker = L.marker([initialData.store.lat, initialData.store.lng], { icon: storeIcon })
      .bindPopup(`<div class="p-1"><b>${initialData.store.name}</b><br><span class="badge bg-danger-subtle text-danger small mt-1">Titik Penjemputan</span></div>`)
      .addTo(trackingMap);
  }

  // Place Destination Marker
  if (initialData.destination && initialData.destination.lat) {
    customerMarker = L.marker([initialData.destination.lat, initialData.destination.lng], { icon: customerIcon })
      .bindPopup(`<div class="p-1"><b>Tujuan Pengantaran</b><br><small class="text-muted">${initialData.destination.address || 'Cicalengka'}</small></div>`)
      .addTo(trackingMap);
  }

  // Place Driver Marker
  if (initialData.driver && initialData.driver.lat) {
    driverMarker = L.marker([initialData.driver.lat, initialData.driver.lng], { icon: driverIcon })
      .bindPopup(`<div class="p-1"><b>Kurir: ${initialData.driver.name}</b><br><span class="badge bg-primary-subtle text-primary small mt-1"><i class="bi bi-broadcast me-1"></i> Live GPS Aktif</span></div>`)
      .addTo(trackingMap);
  }

  drawRoutePolylines(initialData);
  fitAllMarkers();
  updateLiveMetrics(initialData);

  // Clear any existing poll
  if (pollTimer) clearInterval(pollTimer);

  // Poll real-time live location every 3 seconds
  pollTimer = setInterval(() => {
    pollLiveTracking(orderCode);
  }, 3000);
}

function updateLiveMetrics(data) {
  if (!data) return;

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
      etaBadge.innerHTML = `<i class="bi bi-stopwatch-fill text-primary me-1"></i> Tiba dalam ±${etaMinutes} Menit`;
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
  }

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

  if (points.length >= 2 && trackingMap) {
    routePolyline = L.polyline(points, {
      color: '#0d6efd',
      weight: 5,
      dashArray: '8, 10',
      opacity: 0.85,
      lineCap: 'round',
      lineJoin: 'round'
    }).addTo(trackingMap);
  }
}

function smoothMoveMarker(targetLat, targetLng) {
  if (!driverMarker) return;

  const currentLatLng = driverMarker.getLatLng();
  if (currentLatLng.lat === targetLat && currentLatLng.lng === targetLng) return;

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
      currentTrackingData = d;

      // Update Driver Marker smoothly
      if (d.driver && d.driver.lat && d.driver.lng) {
        if (driverMarker) {
          smoothMoveMarker(d.driver.lat, d.driver.lng);
        } else {
          const driverIcon = L.divIcon({
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
          driverMarker = L.marker([d.driver.lat, d.driver.lng], { icon: driverIcon }).addTo(trackingMap);
        }
      }

      drawRoutePolylines(d);
      updateLiveMetrics(d);
      updateTrackingStatusUI(d.order_status, d);
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

function updateTrackingStatusUI(status, data) {
  const badge = document.getElementById('order-status-badge');
  if (badge) {
    let label = status;
    let bg = 'bg-primary';
    if (status === 'pending') { label = 'Menunggu Konfirmasi'; bg = 'bg-secondary'; }
    if (status === 'confirmed') { label = 'Pesanan Dikonfirmasi'; bg = 'bg-info text-dark'; }
    if (status === 'processing') { label = 'Sedang Disiapkan Resto'; bg = 'bg-warning text-dark'; }
    if (status === 'handover') { label = 'Diserahkan ke Kurir'; bg = 'bg-primary'; }
    if (status === 'on_the_way') { label = 'Kurir Menuju Lokasi Anda'; bg = 'bg-primary'; }
    if (status === 'delivered') { label = 'Pesanan Selesai'; bg = 'bg-success'; }
    if (status === 'canceled') { label = 'Dibatalkan'; bg = 'bg-danger'; }
    badge.className = `badge ${bg} px-2 py-1`;
    badge.textContent = label;
  }

  // Update Stepper visually
  const steps = document.querySelectorAll('.stepper-container .step-item');
  if (steps.length >= 4) {
    steps[0].className = 'step-item completed';
    steps[1].className = `step-item ${['processing', 'handover', 'on_the_way', 'delivered'].includes(status) ? 'completed' : ''}`;
    steps[2].className = `step-item ${['on_the_way', 'delivered'].includes(status) ? 'completed' : (['processing', 'handover'].includes(status) ? 'active' : '')}`;
    steps[3].className = `step-item ${status === 'delivered' ? 'completed' : ''}`;
  }

  // If delivered, stop polling and show celebration
  if (status === 'delivered' && pollTimer) {
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
 * Developer / Interactive GPS Simulation Tool
 * Simulates driver movement along the route step-by-step for live testing!
 */
let simInterval = null;
let simStep = 0;
function toggleDriverSimulation(orderCode) {
  if (simInterval) {
    clearInterval(simInterval);
    simInterval = null;
    alert('Simulasi GPS Kurir Dihentikan.');
    return;
  }

  if (!currentTrackingData) return;

  const sLat = currentTrackingData.store?.lat || -6.9835;
  const sLng = currentTrackingData.store?.lng || 107.8335;
  const cLat = currentTrackingData.destination?.lat || -6.9855;
  const cLng = currentTrackingData.destination?.lng || 107.8350;

  const totalSteps = 20;
  simStep = 0;

  alert('🚀 Simulasi GPS Kurir Aktif! Kurir akan bergerak live menuju lokasi tujuan.');

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
    }
    updateLiveMetrics(currentTrackingData);

    if (simStep >= totalSteps) {
      clearInterval(simInterval);
      simInterval = null;
    }
  }, 1800);
}

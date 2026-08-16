/**
 * Delivery Driver PWA Engine
 */

const API_BASE = window.BASE_URL || '';

// Toggle Driver Online Status
async function toggleDriverStatus() {
  try {
    const res = await fetch(API_BASE + '/delivery/toggle-online', { method: 'POST' });
    const data = await res.json();
    if (data.success) {
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

// Background GPS Broadcasting (every 10 seconds while active)
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

document.addEventListener('DOMContentLoaded', () => {
  startDriverGpsTracking();
});

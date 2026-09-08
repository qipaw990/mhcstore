/**
 * API client helper with dynamic backend target
 */
const getApiBase = () => {
  // Jika dibuka di dev Vite port 5173
  if (window.location.port === '5173') {
    return 'http://localhost/CicalengkaGO/public';
  }
  // Jika dibuka di Apache XAMPP subfolder /CicalengkaGO
  if (window.location.pathname.startsWith('/CicalengkaGO')) {
    return window.location.origin + '/CicalengkaGO/public';
  }
  // Di container Docker terpisah (port 8096), Nginx langsung mem-proxy /api/ ke container backend
  return '';
};

const API_BASE = getApiBase();

export const fetchApi = async (endpoint, options = {}) => {
  const url = `${API_BASE}${endpoint.startsWith('/') ? '' : '/'}${endpoint}`;
  
  const token = localStorage.getItem('cicago_admin_token');
  const headers = {
    'Accept': 'application/json',
    ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
    ...(options.headers || {})
  };

  if (options.body && !(options.body instanceof FormData)) {
    headers['Content-Type'] = 'application/json';
    options.body = JSON.stringify(options.body);
  }

  try {
    const response = await fetch(url, { credentials: 'include', credentials: 'same-origin', ...options, headers });
    const data = await response.json();
    return data;
  } catch (err) {
    console.error(`API Error on ${endpoint}:`, err);
    return { success: false, message: err.message || 'Koneksi ke server gagal' };
  }
};

export const formatRupiah = (val) => {
  const num = Number(val) || 0;
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(num);
};

export const formatNumber = (val) => {
  return new Intl.NumberFormat('id-ID').format(Number(val) || 0);
};

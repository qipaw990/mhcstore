import React, { useState, useEffect } from 'react';
import { Store, Search, CheckCircle2, XCircle, Power, RefreshCw, MapPin, Phone } from 'lucide-react';
import { fetchApi } from '../utils/api';

export default function StoresView() {
  const [stores, setStores] = useState([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');

  useEffect(() => {
    loadStores();
  }, []);

  const loadStores = async () => {
    setLoading(true);
    const res = await fetchApi(`/api/admin/stores?search=${encodeURIComponent(search)}`);
    if (res?.data?.stores) {
      setStores(res.data.stores);
    }
    setLoading(false);
  };

  const handleToggle = async (storeId, field) => {
    const formData = new FormData();
    formData.append('store_id', storeId);
    formData.append('field', field);

    const res = await fetchApi('/api/admin/stores/toggle', {
      method: 'POST',
      body: formData
    });

    if (res.success) {
      setStores(prev => prev.map(s => {
        if (s.id === storeId) {
          return {
            ...s,
            [field === 'is_open' ? 'is_open' : 'active']: field === 'is_open' ? (s.is_open ? 0 : 1) : (s.active ? 0 : 1)
          };
        }
        return s;
      }));
    }
  };

  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: '24px' }}>
      {/* Header */}
      <div className="glass-card" style={{ padding: '20px 24px', display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: '16px' }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
          <div style={{ background: 'rgba(16, 185, 129, 0.15)', padding: '10px', borderRadius: '12px', color: '#10B981' }}>
            <Store size={22} />
          </div>
          <div>
            <h2 style={{ fontSize: '18px', fontWeight: 800, color: '#F8FAFC' }}>Manajemen Mitra Toko & Resto</h2>
            <p style={{ fontSize: '12.5px', color: '#94A3B8' }}>Total {stores.length} merchant multi-vendor terdaftar</p>
          </div>
        </div>

        <div style={{ display: 'flex', gap: '12px' }}>
          <input 
            type="text" 
            placeholder="Cari toko, no hp, alamat..." 
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            onKeyDown={(e) => e.key === 'Enter' && loadStores()}
            style={{
              background: 'rgba(0,0,0,0.3)',
              border: '1px solid var(--border-color)',
              borderRadius: '999px',
              padding: '8px 18px',
              fontSize: '13px',
              color: '#F8FAFC',
              width: '240px'
            }}
          />
          <button onClick={loadStores} className="btn-modern-secondary" style={{ padding: '8px 14px' }}>
            <RefreshCw size={15} />
          </button>
        </div>
      </div>

      {/* Stores Grid / Table */}
      <div className="glass-card" style={{ padding: '24px', overflowX: 'auto' }}>
        {loading ? (
          <div style={{ textAlign: 'center', padding: '50px', color: '#94A3B8' }}>
            <RefreshCw size={24} style={{ animation: 'spin 1s linear infinite' }} />
          </div>
        ) : (
          <table className="modern-table">
            <thead>
              <tr>
                <th>Toko / Resto</th>
                <th>Kategori Layanan</th>
                <th>Katalog & Pesanan</th>
                <th>Kontak & Alamat</th>
                <th>Buka / Tutup</th>
                <th>Status Akun</th>
              </tr>
            </thead>
            <tbody>
              {stores.map(s => (
                <tr key={s.id}>
                  <td>
                    <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                      <div style={{ 
                        width: '40px', 
                        height: '40px', 
                        borderRadius: '10px', 
                        background: 'rgba(255,255,255,0.06)', 
                        display: 'flex', 
                        alignItems: 'center', 
                        justifyContent: 'center',
                        color: '#EE2737',
                        fontWeight: 700
                      }}>
                        {s.name?.substring(0, 2).toUpperCase()}
                      </div>
                      <div>
                        <div style={{ fontWeight: 700, color: '#F8FAFC' }}>{s.name}</div>
                        <div style={{ fontSize: '11.5px', color: '#64748B' }}>Vendor: {s.vendor_email || '-'}</div>
                      </div>
                    </div>
                  </td>
                  <td>
                    <span className="badge-pill badge-danger">{s.module_name || 'Food Delivery'}</span>
                  </td>
                  <td>
                    <div style={{ fontSize: '13px', color: '#CBD5E1' }}>{s.product_count || 0} Produk</div>
                    <div style={{ fontSize: '11.5px', color: '#10B981', fontWeight: 600 }}>{s.order_count || 0} Total Pesanan</div>
                  </td>
                  <td>
                    <div style={{ display: 'flex', alignItems: 'center', gap: '6px', fontSize: '12.5px', color: '#94A3B8' }}>
                      <Phone size={12} /> {s.phone || '-'}
                    </div>
                    <div style={{ display: 'flex', alignItems: 'center', gap: '6px', fontSize: '11.5px', color: '#64748B' }}>
                      <MapPin size={12} /> {s.address?.substring(0, 30)}...
                    </div>
                  </td>
                  <td>
                    <label className="switch-container">
                      <input 
                        type="checkbox" 
                        checked={Number(s.is_open) === 1} 
                        onChange={() => handleToggle(s.id, 'is_open')} 
                      />
                      <span className="switch-slider"></span>
                    </label>
                  </td>
                  <td>
                    <button 
                      onClick={() => handleToggle(s.id, 'active')}
                      className={`badge-pill ${Number(s.active) === 1 ? 'badge-success' : 'badge-danger'}`}
                      style={{ cursor: 'pointer', border: 'none' }}
                    >
                      {Number(s.active) === 1 ? 'Aktif' : 'Nonaktif'}
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>
    </div>
  );
}

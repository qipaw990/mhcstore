import React, { useState, useEffect } from 'react';
import { Package, Search, Tag, RefreshCw } from 'lucide-react';
import { fetchApi, formatRupiah } from '../utils/api';

export default function ProductsView() {
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');

  useEffect(() => {
    loadProducts();
  }, []);

  const loadProducts = async () => {
    setLoading(true);
    const res = await fetchApi(`/api/admin/products?search=${encodeURIComponent(search)}`);
    if (res?.data?.products) {
      setProducts(res.data.products);
    }
    setLoading(false);
  };

  const handleToggleStatus = async (id) => {
    const formData = new FormData();
    formData.append('id', id);

    const res = await fetchApi('/api/admin/products/toggle', {
      method: 'POST',
      body: formData
    });

    if (res.success) {
      setProducts(prev => prev.map(p => p.id === id ? { ...p, status: p.status ? 0 : 1 } : p));
    }
  };

  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: '24px' }}>
      <div className="glass-card" style={{ padding: '20px 24px', display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: '16px' }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
          <div style={{ background: 'rgba(59, 130, 246, 0.15)', padding: '10px', borderRadius: '12px', color: '#3B82F6' }}>
            <Package size={22} />
          </div>
          <div>
            <h2 style={{ fontSize: '18px', fontWeight: 800, color: '#F8FAFC' }}>Katalog Menu & Produk</h2>
            <p style={{ fontSize: '12.5px', color: '#94A3B8' }}>Daftar item kuliner, mart, dan produk mitra</p>
          </div>
        </div>

        <div style={{ display: 'flex', gap: '12px' }}>
          <input 
            type="text" 
            placeholder="Cari nama produk, resto..." 
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            onKeyDown={(e) => e.key === 'Enter' && loadProducts()}
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
          <button onClick={loadProducts} className="btn-modern-secondary" style={{ padding: '8px 14px' }}>
            <RefreshCw size={15} />
          </button>
        </div>
      </div>

      <div className="glass-card" style={{ padding: '24px', overflowX: 'auto' }}>
        {loading ? (
          <div style={{ textAlign: 'center', padding: '50px', color: '#94A3B8' }}>
            <RefreshCw size={24} style={{ animation: 'spin 1s linear infinite' }} />
          </div>
        ) : (
          <table className="modern-table">
            <thead>
              <tr>
                <th>Produk</th>
                <th>Resto / Merchant</th>
                <th>Kategori</th>
                <th>Harga Satuan</th>
                <th>Status Penjualan</th>
              </tr>
            </thead>
            <tbody>
              {products.map(p => (
                <tr key={p.id}>
                  <td>
                    <div style={{ fontWeight: 700, color: '#F8FAFC' }}>{p.name}</div>
                    <div style={{ fontSize: '11.5px', color: '#64748B' }}>{p.description?.substring(0, 40) || '-'}</div>
                  </td>
                  <td>
                    <div style={{ color: '#CBD5E1' }}>{p.store_name}</div>
                  </td>
                  <td>
                    <span className="badge-pill badge-info">{p.category_name || 'Umum'}</span>
                  </td>
                  <td>
                    <div style={{ fontWeight: 700, color: '#EE2737' }}>{formatRupiah(p.price)}</div>
                  </td>
                  <td>
                    <label className="switch-container">
                      <input 
                        type="checkbox" 
                        checked={Boolean(Number(p.status))} 
                        onChange={() => handleToggleStatus(p.id)} 
                      />
                      <span className="switch-slider"></span>
                    </label>
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

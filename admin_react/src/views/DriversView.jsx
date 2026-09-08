import React, { useState, useEffect } from 'react';
import { Bike, Phone, Wallet, CheckCircle2, XCircle, RefreshCw } from 'lucide-react';
import { fetchApi, formatRupiah } from '../utils/api';

export default function DriversView() {
  const [drivers, setDrivers] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadDrivers();
  }, []);

  const loadDrivers = async () => {
    setLoading(true);
    const res = await fetchApi('/api/admin/drivers');
    if (res?.data?.drivers) {
      setDrivers(res.data.drivers);
    }
    setLoading(false);
  };

  const handleToggleStatus = async (id) => {
    const formData = new FormData();
    formData.append('id', id);

    const res = await fetchApi('/api/admin/drivers/toggle', {
      method: 'POST',
      body: formData
    });

    if (res.success) {
      setDrivers(prev => prev.map(d => d.id === id ? { ...d, is_active: d.is_active ? 0 : 1 } : d));
    }
  };

  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: '24px' }}>
      <div className="glass-card" style={{ padding: '20px 24px', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
          <div style={{ background: 'rgba(139, 92, 246, 0.15)', padding: '10px', borderRadius: '12px', color: '#8B5CF6' }}>
            <Bike size={22} />
          </div>
          <div>
            <h2 style={{ fontSize: '18px', fontWeight: 800, color: '#F8FAFC' }}>Armada Driver Kurir CicalengkaGO</h2>
            <p style={{ fontSize: '12.5px', color: '#94A3B8' }}>Manajemen kurir, saldo dompet, dan ketersediaan dispatch</p>
          </div>
        </div>

        <button onClick={loadDrivers} className="btn-modern-secondary" style={{ padding: '8px 14px' }}>
          <RefreshCw size={15} />
        </button>
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
                <th>Driver Kurir</th>
                <th>Kontak & Akun</th>
                <th>Saldo Dompet Driver</th>
                <th>Pesanan Selesai</th>
                <th>Status Siap Dispatch</th>
              </tr>
            </thead>
            <tbody>
              {drivers.map(d => (
                <tr key={d.id}>
                  <td>
                    <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                      <div style={{ 
                        width: '40px', 
                        height: '40px', 
                        borderRadius: '50%', 
                        background: 'rgba(139, 92, 246, 0.2)', 
                        display: 'flex', 
                        alignItems: 'center', 
                        justifyContent: 'center',
                        color: '#A78BFA'
                      }}>
                        <Bike size={20} />
                      </div>
                      <div>
                        <div style={{ fontWeight: 700, color: '#F8FAFC' }}>{d.name}</div>
                        <div style={{ fontSize: '11px', color: '#10B981' }}>
                          {d.current_order_id ? `Sedang Antar #${d.current_order_id}` : 'Standby / Kosong'}
                        </div>
                      </div>
                    </div>
                  </td>
                  <td>
                    <div style={{ fontSize: '13px', color: '#F8FAFC' }}>{d.phone}</div>
                    <div style={{ fontSize: '11.5px', color: '#64748B' }}>{d.email}</div>
                  </td>
                  <td>
                    <div style={{ fontWeight: 700, color: '#34D399', fontSize: '14px' }}>
                      {formatRupiah(d.wallet_balance || 0)}
                    </div>
                  </td>
                  <td>
                    <span className="badge-pill badge-info">
                      {d.completed_orders || 0} Delivered
                    </span>
                  </td>
                  <td>
                    <label className="switch-container">
                      <input 
                        type="checkbox" 
                        checked={Boolean(Number(d.is_active))} 
                        onChange={() => handleToggleStatus(d.id)} 
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

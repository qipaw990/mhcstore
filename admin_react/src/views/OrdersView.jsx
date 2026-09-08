import React, { useState, useEffect } from 'react';
import { 
  Crosshair, 
  Search, 
  Filter, 
  Bike, 
  MapPin, 
  Clock, 
  CheckCircle2, 
  XCircle, 
  AlertCircle, 
  RefreshCw,
  Phone,
  UserCheck
} from 'lucide-react';
import { fetchApi, formatRupiah } from '../utils/api';

export default function OrdersView() {
  const [orders, setOrders] = useState([]);
  const [drivers, setDrivers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [statusFilter, setStatusFilter] = useState('all');
  const [search, setSearch] = useState('');
  const [assignModal, setAssignModal] = useState(null); // Selected order for driver assign
  const [selectedDriver, setSelectedDriver] = useState('');
  const [actionLoading, setActionLoading] = useState(false);

  useEffect(() => {
    loadOrders();
  }, [statusFilter]);

  const loadOrders = async () => {
    setLoading(true);
    const res = await fetchApi(`/api/admin/orders?status=${statusFilter}&search=${encodeURIComponent(search)}`);
    if (res?.data) {
      setOrders(res.data.orders || []);
      setDrivers(res.data.drivers || []);
    }
    setLoading(false);
  };

  const handleSearchSubmit = (e) => {
    e.preventDefault();
    loadOrders();
  };

  const handleStatusUpdate = async (orderId, newStatus) => {
    if (!confirm(`Ubah status pesanan #${orderId} menjadi ${newStatus}?`)) return;

    setActionLoading(true);
    const formData = new FormData();
    formData.append('order_id', orderId);
    formData.append('status', newStatus);

    const res = await fetchApi('/api/admin/orders/update-status', {
      method: 'POST',
      body: formData
    });

    setActionLoading(false);
    if (res.success) {
      loadOrders();
    } else {
      alert(res.message || 'Gagal mengubah status pesanan');
    }
  };

  const handleAssignDriver = async () => {
    if (!selectedDriver) {
      alert('Pilih salah satu kurir');
      return;
    }

    setActionLoading(true);
    const formData = new FormData();
    formData.append('order_id', assignModal.id);
    formData.append('driver_id', selectedDriver);

    const res = await fetchApi('/api/admin/orders/assign-driver', {
      method: 'POST',
      body: formData
    });

    setActionLoading(false);
    if (res.success) {
      setAssignModal(null);
      setSelectedDriver('');
      loadOrders();
    } else {
      alert(res.message || 'Gagal menugaskan kurir');
    }
  };

  const statusBadges = {
    pending: { label: 'Menunggu', class: 'badge-warning' },
    confirmed: { label: 'Dikonfirmasi', class: 'badge-info' },
    processing: { label: 'Diproses Toko', class: 'badge-purple' },
    on_the_way: { label: 'Sedang Diantar', class: 'badge-info' },
    delivered: { label: 'Terkirim Selesai', class: 'badge-success' },
    canceled: { label: 'Dibatalkan', class: 'badge-danger' }
  };

  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: '24px' }}>
      
      {/* Header & Controls */}
      <div className="glass-card" style={{ padding: '20px 24px', display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: '16px' }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
          <div style={{ background: 'rgba(238, 39, 55, 0.15)', padding: '10px', borderRadius: '12px', color: '#EE2737' }}>
            <Crosshair size={22} />
          </div>
          <div>
            <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
              <h2 style={{ fontSize: '18px', fontWeight: 800, color: '#F8FAFC' }}>Pusat Dispatch Radar & Live Orders</h2>
              <span className="badge-pill badge-danger"><span className="live-dot"></span> LIVE RADAR</span>
            </div>
            <p style={{ fontSize: '12.5px', color: '#94A3B8' }}>Monitor pergerakan pengantaran dan alokasi driver kurir</p>
          </div>
        </div>

        {/* Search & Filter */}
        <div style={{ display: 'flex', gap: '12px', flexWrap: 'wrap', alignItems: 'center' }}>
          <form onSubmit={handleSearchSubmit} style={{ position: 'relative' }}>
            <input 
              type="text" 
              placeholder="Cari kode order, customer, resto..." 
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              style={{
                background: 'rgba(0,0,0,0.3)',
                border: '1px solid var(--border-color)',
                borderRadius: '999px',
                padding: '8px 16px 8px 36px',
                fontSize: '13px',
                color: '#F8FAFC',
                width: '240px'
              }}
            />
            <Search size={15} style={{ position: 'absolute', left: '14px', top: '10px', color: '#64748B' }} />
          </form>

          <select 
            value={statusFilter} 
            onChange={(e) => setStatusFilter(e.target.value)}
            style={{
              background: 'rgba(0,0,0,0.3)',
              border: '1px solid var(--border-color)',
              borderRadius: '999px',
              padding: '8px 16px',
              fontSize: '13px',
              color: '#F8FAFC',
              cursor: 'pointer'
            }}
          >
            <option value="all">Semua Status</option>
            <option value="pending">Menunggu Konfirmasi</option>
            <option value="processing">Sedang Diproses</option>
            <option value="on_the_way">Sedang Diantar</option>
            <option value="delivered">Terkirim</option>
            <option value="canceled">Batal</option>
          </select>

          <button onClick={loadOrders} className="btn-modern-secondary" style={{ padding: '8px 14px' }}>
            <RefreshCw size={15} />
          </button>
        </div>
      </div>

      {/* Orders List Table */}
      <div className="glass-card" style={{ padding: '24px', overflowX: 'auto' }}>
        {loading ? (
          <div style={{ textAlign: 'center', padding: '50px', color: '#94A3B8' }}>
            <RefreshCw size={24} style={{ animation: 'spin 1s linear infinite' }} />
            <div style={{ marginTop: '10px', fontSize: '13px' }}>Memuat radar pesanan...</div>
          </div>
        ) : orders.length === 0 ? (
          <div style={{ textAlign: 'center', padding: '50px', color: '#94A3B8' }}>
            Tidak ada pesanan yang sesuai filter.
          </div>
        ) : (
          <table className="modern-table">
            <thead>
              <tr>
                <th>Order Code</th>
                <th>Pemesan</th>
                <th>Merchant</th>
                <th>Tagihan</th>
                <th>Kurir Ditugaskan</th>
                <th>Status</th>
                <th>Aksi Dispatch</th>
              </tr>
            </thead>
            <tbody>
              {orders.map(o => (
                <tr key={o.id}>
                  <td>
                    <span style={{ fontFamily: 'monospace', fontWeight: 700, color: '#EE2737' }}>
                      #{o.order_code}
                    </span>
                    <div style={{ fontSize: '11px', color: '#64748B' }}>{o.created_at}</div>
                  </td>
                  <td>
                    <div style={{ fontWeight: 600, color: '#F8FAFC' }}>{o.customer_name}</div>
                    <div style={{ fontSize: '11.5px', color: '#64748B' }}>{o.customer_phone}</div>
                  </td>
                  <td>
                    <div style={{ color: '#CBD5E1' }}>{o.store_name || 'Parcel Hub'}</div>
                  </td>
                  <td>
                    <div style={{ fontWeight: 700, color: '#F8FAFC' }}>{formatRupiah(o.total_amount)}</div>
                    <span className="badge-pill badge-info" style={{ fontSize: '10px', padding: '2px 8px' }}>
                      {o.payment_method?.toUpperCase()}
                    </span>
                  </td>
                  <td>
                    {o.driver_name ? (
                      <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                        <div style={{ background: 'rgba(16, 185, 129, 0.15)', padding: '6px', borderRadius: '8px', color: '#10B981' }}>
                          <Bike size={16} />
                        </div>
                        <div>
                          <div style={{ fontSize: '13px', fontWeight: 600, color: '#34D399' }}>{o.driver_name}</div>
                          <div style={{ fontSize: '11px', color: '#64748B' }}>{o.driver_phone}</div>
                        </div>
                      </div>
                    ) : (
                      <button 
                        onClick={() => { setAssignModal(o); setSelectedDriver(drivers[0]?.id || ''); }}
                        className="btn-modern-primary"
                        style={{ padding: '4px 12px', fontSize: '11.5px' }}
                      >
                        <UserCheck size={13} /> Tugaskan Driver
                      </button>
                    )}
                  </td>
                  <td>
                    <span className={`badge-pill ${statusBadges[o.order_status]?.class || 'badge-info'}`}>
                      {statusBadges[o.order_status]?.label || o.order_status}
                    </span>
                  </td>
                  <td>
                    <div style={{ display: 'flex', gap: '6px' }}>
                      {o.order_status === 'pending' && (
                        <button 
                          onClick={() => handleStatusUpdate(o.id, 'confirmed')} 
                          className="btn-modern-secondary" 
                          style={{ padding: '4px 10px', fontSize: '11px', color: '#3B82F6' }}
                        >
                          Konfirmasi
                        </button>
                      )}
                      {o.order_status === 'confirmed' && (
                        <button 
                          onClick={() => handleStatusUpdate(o.id, 'processing')} 
                          className="btn-modern-secondary" 
                          style={{ padding: '4px 10px', fontSize: '11px', color: '#8B5CF6' }}
                        >
                          Proses Toko
                        </button>
                      )}
                      {o.order_status === 'on_the_way' && (
                        <button 
                          onClick={() => handleStatusUpdate(o.id, 'delivered')} 
                          className="btn-modern-secondary" 
                          style={{ padding: '4px 10px', fontSize: '11px', color: '#10B981' }}
                        >
                          Selesaikan
                        </button>
                      )}
                      {o.order_status !== 'delivered' && o.order_status !== 'canceled' && (
                        <button 
                          onClick={() => handleStatusUpdate(o.id, 'canceled')} 
                          className="btn-modern-secondary" 
                          style={{ padding: '4px 10px', fontSize: '11px', color: '#F87171' }}
                        >
                          Batal
                        </button>
                      )}
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>

      {/* Driver Assignment Modal */}
      {assignModal && (
        <div style={{
          position: 'fixed',
          top: 0,
          left: 0,
          right: 0,
          bottom: 0,
          background: 'rgba(0,0,0,0.7)',
          backdropFilter: 'blur(8px)',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          zIndex: 9999,
          padding: '20px'
        }}>
          <div className="glass-card" style={{ width: '100%', maxWidth: '460px', padding: '24px', border: '1px solid rgba(255,255,255,0.15)' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '16px' }}>
              <h3 style={{ fontSize: '16px', fontWeight: 700, color: '#F8FAFC' }}>
                Tugaskan Kurir untuk #{assignModal.order_code}
              </h3>
              <button 
                onClick={() => setAssignModal(null)} 
                style={{ background: 'none', border: 'none', color: '#94A3B8', cursor: 'pointer', fontSize: '18px' }}
              >
                ✕
              </button>
            </div>

            <p style={{ fontSize: '13px', color: '#94A3B8', marginBottom: '16px' }}>
              Pilih salah satu armada kurir CicalengkaGO yang sedang online & siap mengantar.
            </p>

            <div style={{ display: 'flex', flexDirection: 'column', gap: '10px', maxHeight: '250px', overflowY: 'auto', marginBottom: '20px' }}>
              {drivers.map(d => (
                <div 
                  key={d.id} 
                  onClick={() => setSelectedDriver(d.id)}
                  style={{
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'space-between',
                    padding: '12px 14px',
                    borderRadius: '10px',
                    cursor: 'pointer',
                    background: selectedDriver === d.id ? 'rgba(238,39,55,0.15)' : 'rgba(255,255,255,0.03)',
                    border: selectedDriver === d.id ? '1px solid #EE2737' : '1px solid rgba(255,255,255,0.06)'
                  }}
                >
                  <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                    <div style={{ background: 'rgba(16, 185, 129, 0.2)', padding: '6px', borderRadius: '8px', color: '#10B981' }}>
                      <Bike size={18} />
                    </div>
                    <div>
                      <div style={{ fontSize: '13.5px', fontWeight: 600, color: '#F8FAFC' }}>{d.name}</div>
                      <div style={{ fontSize: '11.5px', color: '#64748B' }}>{d.phone}</div>
                    </div>
                  </div>
                  <span className="badge-pill badge-success">Online</span>
                </div>
              ))}
            </div>

            <div style={{ display: 'flex', gap: '12px', justifyContent: 'flex-end' }}>
              <button onClick={() => setAssignModal(null)} className="btn-modern-secondary" style={{ padding: '8px 16px' }}>
                Batal
              </button>
              <button 
                onClick={handleAssignDriver} 
                disabled={actionLoading} 
                className="btn-modern-primary" 
                style={{ padding: '8px 18px' }}
              >
                {actionLoading ? 'Menugaskan...' : 'Konfirmasi Alokasi'}
              </button>
            </div>
          </div>
        </div>
      )}

    </div>
  );
}

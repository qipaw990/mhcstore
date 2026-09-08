import React, { useState, useEffect } from 'react';
import { 
  DollarSign, 
  TrendingUp, 
  ShoppingBag, 
  Store, 
  Bike, 
  Users, 
  CheckCircle, 
  Clock, 
  AlertTriangle, 
  ArrowUpRight, 
  Layers, 
  CreditCard, 
  Flame, 
  Activity,
  ChevronRight
} from 'lucide-react';
import { 
  AreaChart, 
  Area, 
  XAxis, 
  YAxis, 
  Tooltip, 
  ResponsiveContainer, 
  BarChart, 
  Bar, 
  PieChart, 
  Pie, 
  Cell 
} from 'recharts';
import { fetchApi, formatRupiah, formatNumber } from '../utils/api';

const COLORS = ['#EE2737', '#10B981', '#3B82F6', '#F59E0B', '#8B5CF6', '#EC4899'];

export default function DashboardView({ onNavigate }) {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadMetrics();
  }, []);

  const loadMetrics = async () => {
    setLoading(true);
    const res = await fetchApi('/api/admin/metrics');
    if (res?.data) {
      setData(res.data);
    }
    setLoading(false);
  };

  if (loading || !data) {
    return (
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', minHeight: '400px', color: '#94A3B8' }}>
        <Activity size={24} style={{ animation: 'spin 1.5s linear infinite', marginRight: '10px' }} />
        <span>Menghubungkan ke Real-Time Analytics CicalengkaGO...</span>
      </div>
    );
  }

  const { kpi, pipeline, revenue_trend, payments_breakdown, modules_performance, top_products, top_stores, recent_orders } = data;

  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: '28px' }}>
      
      {/* 1. TOP STATS CARDS (4 KPI Utama) */}
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(240px, 1fr))', gap: '20px' }}>
        
        {/* Total Omset GMV */}
        <div className="glass-card glass-card-interactive" style={{ padding: '22px 24px', position: 'relative', overflow: 'hidden' }}>
          <div style={{ position: 'absolute', right: '-15px', top: '-15px', width: '90px', height: '90px', background: 'radial-gradient(circle, rgba(238,39,55,0.2) 0%, transparent 70%)', borderRadius: '50%' }}></div>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start' }}>
            <div>
              <span style={{ fontSize: '12.5px', fontWeight: 600, color: '#94A3B8', textTransform: 'uppercase', letterSpacing: '0.5px' }}>Total Omset (GMV)</span>
              <h3 style={{ fontSize: '24px', fontWeight: 800, color: '#F8FAFC', marginTop: '6px' }}>
                {formatRupiah(kpi.total_revenue)}
              </h3>
              <div style={{ display: 'flex', alignItems: 'center', gap: '6px', marginTop: '8px', fontSize: '12px', color: '#10B981', fontWeight: 600 }}>
                <ArrowUpRight size={15} />
                <span>Laba Platform: {formatRupiah(kpi.platform_profit)} ({kpi.commission_rate}%)</span>
              </div>
            </div>
            <div style={{ background: 'rgba(238, 39, 55, 0.15)', padding: '12px', borderRadius: '14px', color: '#EE2737' }}>
              <DollarSign size={22} />
            </div>
          </div>
        </div>

        {/* Total Transaksi */}
        <div className="glass-card glass-card-interactive" style={{ padding: '22px 24px' }}>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start' }}>
            <div>
              <span style={{ fontSize: '12.5px', fontWeight: 600, color: '#94A3B8', textTransform: 'uppercase', letterSpacing: '0.5px' }}>Total Pesanan</span>
              <h3 style={{ fontSize: '24px', fontWeight: 800, color: '#F8FAFC', marginTop: '6px' }}>
                {formatNumber(kpi.total_orders)} <span style={{ fontSize: '14px', fontWeight: 500, color: '#94A3B8' }}>Orders</span>
              </h3>
              <div style={{ display: 'flex', alignItems: 'center', gap: '6px', marginTop: '8px', fontSize: '12px', color: '#3B82F6', fontWeight: 600 }}>
                <CheckCircle size={15} />
                <span>Success Rate: {kpi.success_rate}%</span>
              </div>
            </div>
            <div style={{ background: 'rgba(59, 130, 246, 0.15)', padding: '12px', borderRadius: '14px', color: '#3B82F6' }}>
              <ShoppingBag size={22} />
            </div>
          </div>
        </div>

        {/* Mitra Toko & Resto */}
        <div className="glass-card glass-card-interactive" style={{ padding: '22px 24px' }}>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start' }}>
            <div>
              <span style={{ fontSize: '12.5px', fontWeight: 600, color: '#94A3B8', textTransform: 'uppercase', letterSpacing: '0.5px' }}>Mitra Toko Terdaftar</span>
              <h3 style={{ fontSize: '24px', fontWeight: 800, color: '#F8FAFC', marginTop: '6px' }}>
                {formatNumber(kpi.total_stores)} <span style={{ fontSize: '14px', fontWeight: 500, color: '#94A3B8' }}>Merchants</span>
              </h3>
              <div style={{ display: 'flex', alignItems: 'center', gap: '6px', marginTop: '8px', fontSize: '12px', color: '#10B981', fontWeight: 600 }}>
                <span className="live-dot"></span>
                <span>Multi-Vendor Ekosistem Aktif</span>
              </div>
            </div>
            <div style={{ background: 'rgba(16, 185, 129, 0.15)', padding: '12px', borderRadius: '14px', color: '#10B981' }}>
              <Store size={22} />
            </div>
          </div>
        </div>

        {/* Armada Kurir Driver */}
        <div className="glass-card glass-card-interactive" style={{ padding: '22px 24px' }}>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start' }}>
            <div>
              <span style={{ fontSize: '12.5px', fontWeight: 600, color: '#94A3B8', textTransform: 'uppercase', letterSpacing: '0.5px' }}>Armada Driver Kurir</span>
              <h3 style={{ fontSize: '24px', fontWeight: 800, color: '#F8FAFC', marginTop: '6px' }}>
                {formatNumber(kpi.active_drivers)} <span style={{ fontSize: '14px', fontWeight: 500, color: '#94A3B8' }}>/ {kpi.total_drivers} Siap</span>
              </h3>
              <div style={{ display: 'flex', alignItems: 'center', gap: '6px', marginTop: '8px', fontSize: '12px', color: '#8B5CF6', fontWeight: 600 }}>
                <Bike size={15} />
                <span>Standby Live Radar</span>
              </div>
            </div>
            <div style={{ background: 'rgba(139, 92, 246, 0.15)', padding: '12px', borderRadius: '14px', color: '#8B5CF6' }}>
              <Bike size={22} />
            </div>
          </div>
        </div>

      </div>

      {/* 2. CHARTS INSIGHT ROW (Revenue Trend & Pipeline) */}
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(400px, 1fr))', gap: '24px' }}>
        
        {/* Revenue Volume Curve */}
        <div className="glass-card" style={{ padding: '24px' }}>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px' }}>
            <div>
              <h3 style={{ fontSize: '16px', fontWeight: 700, color: '#F8FAFC' }}>Tren Omset & Transaksi Harian</h3>
              <p style={{ fontSize: '12px', color: '#94A3B8' }}>Volume penjualan 7 hari terakhir (IDR)</p>
            </div>
            <span className="badge-pill badge-danger">Live Trend</span>
          </div>

          <div style={{ width: '100%', height: '240px' }}>
            <ResponsiveContainer width="100%" height="100%">
              <AreaChart data={revenue_trend} margin={{ top: 10, right: 10, left: -20, bottom: 0 }}>
                <defs>
                  <linearGradient id="colorRev" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="5%" stopColor="#EE2737" stopOpacity={0.4}/>
                    <stop offset="95%" stopColor="#EE2737" stopOpacity={0}/>
                  </linearGradient>
                </defs>
                <XAxis dataKey="date_val" stroke="#64748B" fontSize={11} tickLine={false} />
                <YAxis stroke="#64748B" fontSize={11} tickLine={false} tickFormatter={(v) => `Rp${v/1000}k`} />
                <Tooltip 
                  contentStyle={{ backgroundColor: '#111827', border: '1px solid rgba(255,255,255,0.1)', borderRadius: '10px', fontSize: '12px' }}
                  formatter={(val) => [formatRupiah(val), 'Omset']}
                />
                <Area type="monotone" dataKey="revenue" stroke="#EE2737" strokeWidth={3} fillOpacity={1} fill="url(#colorRev)" />
              </AreaChart>
            </ResponsiveContainer>
          </div>
        </div>

        {/* Order Status Pipeline */}
        <div className="glass-card" style={{ padding: '24px' }}>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px' }}>
            <div>
              <h3 style={{ fontSize: '16px', fontWeight: 700, color: '#F8FAFC' }}>Pipeline Status Pengantaran</h3>
              <p style={{ fontSize: '12px', color: '#94A3B8' }}>Sirkulasi pesanan dari antrian hingga diterima</p>
            </div>
            <button 
              onClick={() => onNavigate('orders')} 
              style={{ background: 'none', border: 'none', color: '#EE2737', cursor: 'pointer', fontSize: '12px', fontWeight: 700, display: 'flex', alignItems: 'center', gap: '4px' }}
            >
              Dispatch Hub <ChevronRight size={14} />
            </button>
          </div>

          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '14px', marginTop: '10px' }}>
            <div style={{ background: 'rgba(245, 158, 11, 0.1)', border: '1px solid rgba(245, 158, 11, 0.25)', borderRadius: '12px', padding: '14px', textAlign: 'center' }}>
              <div style={{ fontSize: '11px', color: '#FBBF24', fontWeight: 600 }}>MENUNGGU</div>
              <div style={{ fontSize: '22px', fontWeight: 800, color: '#FBBF24', marginTop: '4px' }}>{pipeline.pending}</div>
            </div>
            <div style={{ background: 'rgba(139, 92, 246, 0.1)', border: '1px solid rgba(139, 92, 246, 0.25)', borderRadius: '12px', padding: '14px', textAlign: 'center' }}>
              <div style={{ fontSize: '11px', color: '#A78BFA', fontWeight: 600 }}>DIPROSES</div>
              <div style={{ fontSize: '22px', fontWeight: 800, color: '#A78BFA', marginTop: '4px' }}>{pipeline.processing + pipeline.confirmed}</div>
            </div>
            <div style={{ background: 'rgba(59, 130, 246, 0.1)', border: '1px solid rgba(59, 130, 246, 0.25)', borderRadius: '12px', padding: '14px', textAlign: 'center' }}>
              <div style={{ fontSize: '11px', color: '#60A5FA', fontWeight: 600 }}>DI JALAN</div>
              <div style={{ fontSize: '22px', fontWeight: 800, color: '#60A5FA', marginTop: '4px' }}>{pipeline.on_the_way}</div>
            </div>
            <div style={{ background: 'rgba(16, 185, 129, 0.1)', border: '1px solid rgba(16, 185, 129, 0.25)', borderRadius: '12px', padding: '14px', textAlign: 'center' }}>
              <div style={{ fontSize: '11px', color: '#34D399', fontWeight: 600 }}>TERKIRIM</div>
              <div style={{ fontSize: '22px', fontWeight: 800, color: '#34D399', marginTop: '4px' }}>{pipeline.delivered}</div>
            </div>
            <div style={{ background: 'rgba(238, 39, 55, 0.1)', border: '1px solid rgba(238, 39, 55, 0.25)', borderRadius: '12px', padding: '14px', textAlign: 'center' }}>
              <div style={{ fontSize: '11px', color: '#F87171', fontWeight: 600 }}>BATAL</div>
              <div style={{ fontSize: '22px', fontWeight: 800, color: '#F87171', marginTop: '4px' }}>{pipeline.canceled}</div>
            </div>
            <div style={{ background: 'rgba(255, 255, 255, 0.04)', border: '1px solid var(--border-color)', borderRadius: '12px', padding: '14px', textAlign: 'center' }}>
              <div style={{ fontSize: '11px', color: '#94A3B8', fontWeight: 600 }}>TOTAL</div>
              <div style={{ fontSize: '22px', fontWeight: 800, color: '#F8FAFC', marginTop: '4px' }}>{kpi.total_orders}</div>
            </div>
          </div>
        </div>

      </div>

      {/* 3. BREAKDOWN INSIGHTS (Modules & Payment Distribution) */}
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(350px, 1fr))', gap: '24px' }}>
        
        {/* Module Performance Bar */}
        <div className="glass-card" style={{ padding: '24px' }}>
          <h3 style={{ fontSize: '16px', fontWeight: 700, color: '#F8FAFC', marginBottom: '4px' }}>Distribusi Layanan Multi-Vendor</h3>
          <p style={{ fontSize: '12px', color: '#94A3B8', marginBottom: '18px' }}>Porsi omset berdasarkan kategori modul</p>

          <div style={{ display: 'flex', flexDirection: 'column', gap: '14px' }}>
            {modules_performance.map((m, idx) => (
              <div key={m.id} style={{ display: 'flex', flexDirection: 'column', gap: '6px' }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '13px' }}>
                  <span style={{ fontWeight: 600, color: '#F8FAFC' }}>{m.name}</span>
                  <span style={{ color: '#94A3B8' }}>{m.store_count} Toko • {formatRupiah(m.total_sales)}</span>
                </div>
                <div style={{ width: '100%', height: '8px', background: 'rgba(255,255,255,0.06)', borderRadius: '999px', overflow: 'hidden' }}>
                  <div style={{ 
                    width: `${Math.min(100, Math.max(15, (Number(m.total_sales) / (kpi.total_revenue || 1)) * 100))}%`, 
                    height: '100%', 
                    background: COLORS[idx % COLORS.length],
                    borderRadius: '999px' 
                  }}></div>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Top 5 Best Seller Items */}
        <div className="glass-card" style={{ padding: '24px' }}>
          <h3 style={{ fontSize: '16px', fontWeight: 700, color: '#F8FAFC', marginBottom: '4px' }}>Menu & Produk Terlaris</h3>
          <p style={{ fontSize: '12px', color: '#94A3B8', marginBottom: '18px' }}>Top items dengan frekuensi order tertinggi</p>

          <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
            {top_products.map((p, idx) => (
              <div key={p.id} style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '10px 14px', background: 'rgba(255,255,255,0.02)', borderRadius: '10px' }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                  <div style={{ 
                    width: '28px', 
                    height: '28px', 
                    borderRadius: '50%', 
                    background: idx === 0 ? '#EE2737' : 'rgba(255,255,255,0.1)', 
                    color: 'white', 
                    fontWeight: 700, 
                    fontSize: '12px', 
                    display: 'flex', 
                    alignItems: 'center', 
                    justifyContent: 'center' 
                  }}>
                    #{idx + 1}
                  </div>
                  <div>
                    <div style={{ fontSize: '13.5px', fontWeight: 600, color: '#F8FAFC' }}>{p.name}</div>
                    <div style={{ fontSize: '11.5px', color: '#64748B' }}>{p.store_name}</div>
                  </div>
                </div>
                <div style={{ textAlign: 'right' }}>
                  <div style={{ fontSize: '13.5px', fontWeight: 700, color: '#EE2737' }}>{formatRupiah(p.price)}</div>
                  <div style={{ fontSize: '11px', color: '#10B981', fontWeight: 600 }}>{p.total_sold || 0} Terjual</div>
                </div>
              </div>
            ))}
          </div>
        </div>

      </div>

      {/* 4. RECENT ORDERS TABLE */}
      <div className="glass-card" style={{ padding: '24px', overflowX: 'auto' }}>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px' }}>
          <div>
            <h3 style={{ fontSize: '16px', fontWeight: 700, color: '#F8FAFC' }}>10 Pesanan Terakhir (Live Feed)</h3>
            <p style={{ fontSize: '12px', color: '#94A3B8' }}>Transaksi masuk secara real-time</p>
          </div>
          <button 
            onClick={() => onNavigate('orders')}
            className="btn-modern-secondary" 
            style={{ fontSize: '12px', padding: '6px 14px' }}
          >
            Lihat Semua Pesanan
          </button>
        </div>

        <table className="modern-table">
          <thead>
            <tr>
              <th>Order ID</th>
              <th>Pelanggan</th>
              <th>Merchant / Vendor</th>
              <th>Metode Bayar</th>
              <th>Total Tagihan</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            {recent_orders.map(o => (
              <tr key={o.id}>
                <td>
                  <span style={{ fontFamily: 'monospace', fontWeight: 700, color: '#EE2737' }}>
                    #{o.order_code}
                  </span>
                </td>
                <td>
                  <div style={{ fontWeight: 600, color: '#F8FAFC' }}>{o.customer_name}</div>
                  <div style={{ fontSize: '11.5px', color: '#64748B' }}>{o.customer_phone}</div>
                </td>
                <td>
                  <div style={{ color: '#CBD5E1' }}>{o.store_name || 'Cicalengka Parcel Hub'}</div>
                </td>
                <td>
                  <span className="badge-pill badge-info">
                    {o.payment_method?.toUpperCase() || 'COD'}
                  </span>
                </td>
                <td>
                  <span style={{ fontWeight: 700, color: '#F8FAFC' }}>
                    {formatRupiah(o.total_amount)}
                  </span>
                </td>
                <td>
                  <span className={`badge-pill ${
                    o.order_status === 'delivered' ? 'badge-success' :
                    o.order_status === 'canceled' ? 'badge-danger' :
                    o.order_status === 'on_the_way' ? 'badge-info' : 'badge-warning'
                  }`}>
                    {o.order_status}
                  </span>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

    </div>
  );
}

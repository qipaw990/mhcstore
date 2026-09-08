import React, { useState, useEffect } from 'react';
import { 
  LayoutDashboard, 
  Crosshair, 
  Store, 
  Package, 
  Bike, 
  Sliders, 
  ExternalLink,
  ChevronRight,
  ShieldAlert,
  Sparkles,
  RefreshCw,
  LogOut
} from 'lucide-react';
import DashboardView from './views/DashboardView';
import OrdersView from './views/OrdersView';
import StoresView from './views/StoresView';
import ProductsView from './views/ProductsView';
import DriversView from './views/DriversView';
import SettingsView from './views/SettingsView';
import LoginModal from './components/LoginModal';

export default function App() {
  const [activeTab, setActiveTab] = useState('dashboard');
  const [adminUser, setAdminUser] = useState(null);
  const [checkingAuth, setCheckingAuth] = useState(true);

  useEffect(() => {
    // Check saved token and user
    const savedUser = localStorage.getItem('cicago_admin_user');
    const savedToken = localStorage.getItem('cicago_admin_token');

    if (savedToken && savedUser) {
      try {
        setAdminUser(JSON.parse(savedUser));
      } catch (e) {
        setAdminUser(null);
      }
    }
    setCheckingAuth(false);
  }, []);

  const handleLogout = () => {
    localStorage.removeItem('cicago_admin_token');
    localStorage.removeItem('cicago_admin_user');
    setAdminUser(null);
  };

  const navItems = [
    { id: 'dashboard', label: 'Ringkasan & Analytics', icon: LayoutDashboard, badge: 'INSIGHTS' },
    { id: 'orders', label: 'Dispatch Radar Live', icon: Crosshair, badge: 'LIVE' },
    { id: 'stores', label: 'Mitra Toko & Resto', icon: Store },
    { id: 'products', label: 'Katalog Menu & Produk', icon: Package },
    { id: 'drivers', label: 'Armada Driver Kurir', icon: Bike },
    { id: 'settings', label: 'Skema Bisnis & Payment', icon: Sliders, badge: 'CONFIG' },
  ];

  return (
    <div style={{ display: 'flex', minHeight: '100vh', backgroundColor: 'var(--bg-main)' }}>
      
      {/* SIDEBAR NAVIGATION */}
      <aside style={{
        width: '270px',
        backgroundColor: 'var(--bg-sidebar)',
        borderRight: '1px solid var(--border-color)',
        display: 'flex',
        flexDirection: 'column',
        position: 'sticky',
        top: 0,
        height: '100vh',
        zIndex: 100
      }}>
        {/* Brand Header */}
        <div style={{ padding: '24px', borderBottom: '1px solid var(--border-color)', display: 'flex', alignItems: 'center', gap: '12px' }}>
          <div style={{
            width: '42px',
            height: '42px',
            borderRadius: '12px',
            background: 'linear-gradient(135deg, #EE2737 0%, #C61524 100%)',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            boxShadow: '0 4px 16px rgba(238, 39, 55, 0.4)',
            color: 'white',
            fontWeight: 800,
            fontSize: '20px'
          }}>
            C
          </div>
          <div>
            <div style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
              <span style={{ fontSize: '17px', fontWeight: 800, color: '#F8FAFC', letterSpacing: '-0.3px' }}>
                Cicalengka<span style={{ color: '#EE2737' }}>GO</span>
              </span>
              <span className="badge-pill badge-danger" style={{ fontSize: '9px', padding: '2px 6px' }}>PRO</span>
            </div>
            <div style={{ fontSize: '11px', color: '#64748B', marginTop: '2px' }}>Enterprise Super-App Admin</div>
          </div>
        </div>

        {/* Menu Items */}
        <div style={{ padding: '20px 14px', display: 'flex', flexDirection: 'column', gap: '6px', flex: 1, overflowY: 'auto' }}>
          <div style={{ fontSize: '11px', fontWeight: 700, textTransform: 'uppercase', color: '#475569', letterSpacing: '0.8px', padding: '6px 12px', marginBottom: '4px' }}>
            Operasional & Ekosistem
          </div>

          {navItems.map(item => {
            const Icon = item.icon;
            const isActive = activeTab === item.id;
            return (
              <button
                key={item.id}
                onClick={() => setActiveTab(item.id)}
                style={{
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'space-between',
                  padding: '12px 14px',
                  borderRadius: '12px',
                  border: 'none',
                  background: isActive ? 'linear-gradient(135deg, rgba(238,39,55,0.18) 0%, rgba(238,39,55,0.06) 100%)' : 'transparent',
                  color: isActive ? '#F8FAFC' : '#94A3B8',
                  cursor: 'pointer',
                  fontWeight: isActive ? 700 : 500,
                  fontSize: '13.5px',
                  transition: 'all 0.15s ease',
                  borderLeft: isActive ? '3px solid #EE2737' : '3px solid transparent'
                }}
              >
                <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                  <Icon size={18} color={isActive ? '#EE2737' : '#94A3B8'} />
                  <span>{item.label}</span>
                </div>
                {item.badge && (
                  <span className={`badge-pill ${item.badge === 'LIVE' ? 'badge-danger' : item.badge === 'INSIGHTS' ? 'badge-info' : 'badge-warning'}`} style={{ fontSize: '9.5px', padding: '2px 7px' }}>
                    {item.badge}
                  </span>
                )}
              </button>
            );
          })}
        </div>

        {/* Footer info */}
        <div style={{ padding: '16px 20px', borderTop: '1px solid var(--border-color)', display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
            <span className="live-dot"></span>
            <span style={{ fontSize: '12px', color: '#94A3B8', fontWeight: 500 }}>System Online</span>
          </div>
          <a href="/CicalengkaGO/public" target="_blank" rel="noreferrer" style={{ color: '#64748B', textDecoration: 'none', display: 'flex', alignItems: 'center', gap: '4px', fontSize: '11px' }}>
            Lihat Web <ExternalLink size={12} />
          </a>
        </div>
      </aside>

      {/* MAIN CONTENT AREA */}
      <main style={{ flex: 1, display: 'flex', flexDirection: 'column', minWidth: 0, overflowY: 'auto' }}>
        
        {/* Top Navbar */}
        <header style={{
          padding: '16px 32px',
          borderBottom: '1px solid var(--border-color)',
          background: 'rgba(11, 15, 25, 0.8)',
          backdropFilter: 'blur(12px)',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'space-between',
          position: 'sticky',
          top: 0,
          zIndex: 90
        }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
            <span style={{ fontSize: '14px', color: '#64748B' }}>CicalengkaGO Admin</span>
            <span style={{ color: '#334155' }}>/</span>
            <span style={{ fontSize: '14px', fontWeight: 700, color: '#F8FAFC' }}>
              {navItems.find(i => i.id === activeTab)?.label}
            </span>
          </div>

          <div style={{ display: 'flex', alignItems: 'center', gap: '16px' }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: '8px', background: 'rgba(255,255,255,0.05)', padding: '6px 14px', borderRadius: '999px', border: '1px solid var(--border-color)' }}>
              <div style={{ width: '8px', height: '8px', borderRadius: '50%', background: '#10B981' }}></div>
              <span style={{ fontSize: '12.5px', color: '#CBD5E1', fontWeight: 600 }}>
                {adminUser?.name || 'Super Admin'}
              </span>
            </div>

            {adminUser && (
              <button
                onClick={handleLogout}
                title="Keluar / Logout"
                style={{
                  background: 'rgba(238, 39, 55, 0.1)',
                  border: '1px solid rgba(238, 39, 55, 0.25)',
                  color: '#F87171',
                  borderRadius: '999px',
                  padding: '6px 12px',
                  cursor: 'pointer',
                  display: 'flex',
                  alignItems: 'center',
                  gap: '6px',
                  fontSize: '12px',
                  fontWeight: 600
                }}
              >
                <LogOut size={14} />
                <span>Keluar</span>
              </button>
            )}
          </div>
        </header>

        {/* Dynamic Views Rendering */}
        <div style={{ padding: '32px', maxWidth: '1440px', width: '100%', margin: '0 auto' }}>
          {activeTab === 'dashboard' && <DashboardView onNavigate={(tab) => setActiveTab(tab)} />}
          {activeTab === 'orders' && <OrdersView />}
          {activeTab === 'stores' && <StoresView />}
          {activeTab === 'products' && <ProductsView />}
          {activeTab === 'drivers' && <DriversView />}
          {activeTab === 'settings' && <SettingsView />}
        </div>

      </main>

      {/* Render Login Modal if Not Authenticated */}
      {!checkingAuth && !adminUser && (
        <LoginModal onLoginSuccess={(user) => setAdminUser(user)} />
      )}

    </div>
  );
}

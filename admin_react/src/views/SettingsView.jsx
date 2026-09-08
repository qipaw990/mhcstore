import React, { useState, useEffect } from 'react';
import { 
  CreditCard, 
  ShieldCheck, 
  Settings2, 
  Truck, 
  Percent, 
  Smartphone, 
  CheckCircle2, 
  Save, 
  RefreshCw, 
  QrCode, 
  Building2, 
  AlertCircle,
  ToggleLeft,
  ToggleRight,
  Flame,
  Power
} from 'lucide-react';
import { fetchApi, formatRupiah } from '../utils/api';

export default function SettingsView() {
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [saveSuccess, setSaveSuccess] = useState(false);
  
  // Settings state
  const [settings, setSettings] = useState({
    business_name: 'CicalengkaGO',
    phone: '',
    email: '',
    admin_commission_percent: '10',
    delivery_man_commission_percent: '80.00',
    delivery_charge_min: '5000',
    delivery_charge_per_km: '2500',
    free_delivery_over: '100000',
    single_active_order_driver: '1',
    maintenance_mode: '0',
    require_login_otp: '0',
    require_customer_otp: '0',
    otp_delivery_verification: '1',
    otp_mode: 'real',
    // Payments
    midtrans_environment: 'sandbox',
    midtrans_server_key: '',
    midtrans_client_key: '',
    doku_enabled: '1',
    doku_environment: 'sandbox',
    doku_client_id: '',
    doku_secret_key: '',
    wallet_payment_status: '1',
    cod_payment_status: '1',
    qris_payment_status: '1',
    bank_transfer_status: '1',
    support_whatsapp: ''
  });

  const [banks, setBanks] = useState([]);

  useEffect(() => {
    loadSettings();
  }, []);

  const loadSettings = async () => {
    setLoading(true);
    const res = await fetchApi('/api/admin/settings');
    if (res?.data?.settings) {
      setSettings(prev => ({ ...prev, ...res.data.settings }));
    }
    if (res?.data?.banks) {
      setBanks(res.data.banks);
    }
    setLoading(false);
  };

  const handleToggle = (key) => {
    setSettings(prev => ({
      ...prev,
      [key]: prev[key] === '1' ? '0' : '1'
    }));
  };

  const handleChange = (key, value) => {
    setSettings(prev => ({ ...prev, [key]: value }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSaving(true);
    setSaveSuccess(false);

    const formData = new FormData();
    Object.keys(settings).forEach(k => {
      formData.append(k, settings[k]);
    });

    const res = await fetchApi('/api/admin/settings', {
      method: 'POST',
      body: formData
    });

    setSaving(false);
    if (res.success) {
      setSaveSuccess(true);
      setTimeout(() => setSaveSuccess(false), 3500);
    } else {
      alert(res.message || 'Gagal menyimpan pengaturan');
    }
  };

  if (loading) {
    return (
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', minHeight: '400px', color: '#94A3B8', gap: '12px' }}>
        <RefreshCw size={24} style={{ animation: 'spin 1s linear infinite' }} />
        <span>Memuat Konfigurasi Bisnis & Payment Switch...</span>
      </div>
    );
  }

  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: '28px' }}>
      {/* Top Banner */}
      <div className="glass-card" style={{ 
        padding: '24px 30px', 
        display: 'flex', 
        justifyContent: 'space-between', 
        alignItems: 'center',
        flexWrap: 'wrap',
        gap: '20px',
        borderLeft: '4px solid #EE2737'
      }}>
        <div>
          <div style={{ display: 'flex', alignItems: 'center', gap: '10px', marginBottom: '6px' }}>
            <Settings2 size={24} color="#EE2737" />
            <h2 style={{ fontSize: '20px', fontWeight: 800, color: '#F8FAFC' }}>
              Engine Bisnis Fleksibel & Payment Gateway Master Switch
            </h2>
          </div>
          <p style={{ fontSize: '13.5px', color: '#94A3B8' }}>
            Kontrol penuh skema komisi, switch aktif/nonaktif metode bayar, tarif delivery, dan keamanan aplikasi CicalengkaGO secara instan.
          </p>
        </div>

        <div style={{ display: 'flex', gap: '12px', alignItems: 'center' }}>
          {saveSuccess && (
            <div style={{ 
              display: 'flex', 
              alignItems: 'center', 
              gap: '6px', 
              color: '#34D399', 
              background: 'rgba(16, 185, 129, 0.15)', 
              padding: '8px 16px', 
              borderRadius: '999px',
              fontSize: '13px',
              fontWeight: 600,
              border: '1px solid rgba(16, 185, 129, 0.3)'
            }}>
              <CheckCircle2 size={16} /> Tersimpan Sukses!
            </div>
          )}
          <button 
            type="button" 
            onClick={handleSubmit} 
            disabled={saving}
            className="btn-modern-primary"
          >
            {saving ? <RefreshCw size={16} style={{ animation: 'spin 1s linear infinite' }} /> : <Save size={16} />}
            <span>{saving ? 'Menyimpan...' : 'Simpan Perubahan'}</span>
          </button>
        </div>
      </div>

      {/* Grid Sections */}
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(420px, 1fr))', gap: '24px' }}>
        
        {/* 1. PAYMENT GATEWAY MASTER TOGGLES */}
        <div className="glass-card" style={{ padding: '24px' }}>
          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '20px', borderBottom: '1px solid var(--border-color)', paddingBottom: '14px' }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
              <div style={{ background: 'rgba(238, 39, 55, 0.15)', padding: '8px', borderRadius: '10px', color: '#EE2737' }}>
                <CreditCard size={20} />
              </div>
              <div>
                <h3 style={{ fontSize: '16px', fontWeight: 700, color: '#F8FAFC' }}>Master Saklar Metode Pembayaran</h3>
                <p style={{ fontSize: '12px', color: '#94A3B8' }}>Pilih metode yang diizinkan untuk customer saat checkout</p>
              </div>
            </div>
          </div>

          <div style={{ display: 'flex', flexDirection: 'column', gap: '16px' }}>
            {/* Midtrans Snap */}
            <div style={{ 
              display: 'flex', 
              alignItems: 'center', 
              justifyContent: 'space-between', 
              padding: '14px 18px', 
              background: 'rgba(255,255,255,0.03)', 
              borderRadius: '12px',
              border: '1px solid rgba(255,255,255,0.06)'
            }}>
              <div>
                <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                  <span style={{ fontWeight: 700, fontSize: '14px', color: '#F8FAFC' }}>Midtrans Gateway (Snap Popup)</span>
                  <span className="badge-pill badge-info">
                    {settings.midtrans_environment === 'production' ? 'PROD' : 'SANDBOX'}
                  </span>
                </div>
                <div style={{ fontSize: '12px', color: '#94A3B8', marginTop: '3px' }}>Virtual Account BCA, Mandiri, BNI, Permata, GoPay & ShopeePay</div>
              </div>
              <label className="switch-container">
                <input 
                  type="checkbox" 
                  checked={Boolean(settings.midtrans_server_key)} 
                  onChange={() => {
                    if (settings.midtrans_server_key) {
                      handleChange('midtrans_server_key_backup', settings.midtrans_server_key);
                      handleChange('midtrans_server_key', '');
                    } else {
                      handleChange('midtrans_server_key', settings.midtrans_server_key_backup || 'SB-Mid-server-demo');
                    }
                  }} 
                />
                <span className="switch-slider"></span>
              </label>
            </div>

            {/* DOKU Checkout */}
            <div style={{ 
              display: 'flex', 
              alignItems: 'center', 
              justifyContent: 'space-between', 
              padding: '14px 18px', 
              background: 'rgba(255,255,255,0.03)', 
              borderRadius: '12px',
              border: '1px solid rgba(255,255,255,0.06)'
            }}>
              <div>
                <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                  <span style={{ fontWeight: 700, fontSize: '14px', color: '#F8FAFC' }}>DOKU Payment Gateway</span>
                  <span className="badge-pill badge-purple">
                    {settings.doku_environment === 'production' ? 'PROD' : 'SANDBOX'}
                  </span>
                </div>
                <div style={{ fontSize: '12px', color: '#94A3B8', marginTop: '3px' }}>Gerbang alternatif QRIS, OVO, Alfa/Indomaret & Kartu Kredit</div>
              </div>
              <label className="switch-container">
                <input 
                  type="checkbox" 
                  checked={settings.doku_enabled === '1'} 
                  onChange={() => handleToggle('doku_enabled')} 
                />
                <span className="switch-slider"></span>
              </label>
            </div>

            {/* QRIS Otomatis Mandiri */}
            <div style={{ 
              display: 'flex', 
              alignItems: 'center', 
              justifyContent: 'space-between', 
              padding: '14px 18px', 
              background: 'rgba(255,255,255,0.03)', 
              borderRadius: '12px',
              border: '1px solid rgba(255,255,255,0.06)'
            }}>
              <div>
                <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                  <span style={{ fontWeight: 700, fontSize: '14px', color: '#F8FAFC' }}>QRIS Mandiri Dinamis (0% MDR Fee)</span>
                  <span className="badge-pill badge-success">GRATIS BIAYA</span>
                </div>
                <div style={{ fontSize: '12px', color: '#94A3B8', marginTop: '3px' }}>Scan instan semua e-wallet dengan auto kode unik</div>
              </div>
              <label className="switch-container">
                <input 
                  type="checkbox" 
                  checked={settings.qris_payment_status !== '0'} 
                  onChange={() => handleToggle('qris_payment_status')} 
                />
                <span className="switch-slider"></span>
              </label>
            </div>

            {/* In-House Bank Transfer */}
            <div style={{ 
              display: 'flex', 
              alignItems: 'center', 
              justifyContent: 'space-between', 
              padding: '14px 18px', 
              background: 'rgba(255,255,255,0.03)', 
              borderRadius: '12px',
              border: '1px solid rgba(255,255,255,0.06)'
            }}>
              <div>
                <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                  <span style={{ fontWeight: 700, fontSize: '14px', color: '#F8FAFC' }}>Transfer Bank Manual (In-House)</span>
                  <span className="badge-pill badge-warning">{banks.length} Rekening Aktif</span>
                </div>
                <div style={{ fontSize: '12px', color: '#94A3B8', marginTop: '3px' }}>BCA, Mandiri, BRI dengan verifikasi bukti transfer</div>
              </div>
              <label className="switch-container">
                <input 
                  type="checkbox" 
                  checked={settings.bank_transfer_status !== '0'} 
                  onChange={() => handleToggle('bank_transfer_status')} 
                />
                <span className="switch-slider"></span>
              </label>
            </div>

            {/* Dompet CicalengkaPay */}
            <div style={{ 
              display: 'flex', 
              alignItems: 'center', 
              justifyContent: 'space-between', 
              padding: '14px 18px', 
              background: 'rgba(255,255,255,0.03)', 
              borderRadius: '12px',
              border: '1px solid rgba(255,255,255,0.06)'
            }}>
              <div>
                <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                  <span style={{ fontWeight: 700, fontSize: '14px', color: '#F8FAFC' }}>Dompet Saldo (CicalengkaPay)</span>
                  <span className="badge-pill badge-info">1-Klik Bayar</span>
                </div>
                <div style={{ fontSize: '12px', color: '#94A3B8', marginTop: '3px' }}>Potong langsung dari saldo dompet digital customer</div>
              </div>
              <label className="switch-container">
                <input 
                  type="checkbox" 
                  checked={settings.wallet_payment_status !== '0'} 
                  onChange={() => handleToggle('wallet_payment_status')} 
                />
                <span className="switch-slider"></span>
              </label>
            </div>

            {/* Cash on Delivery (COD) */}
            <div style={{ 
              display: 'flex', 
              alignItems: 'center', 
              justifyContent: 'space-between', 
              padding: '14px 18px', 
              background: 'rgba(255,255,255,0.03)', 
              borderRadius: '12px',
              border: '1px solid rgba(255,255,255,0.06)'
            }}>
              <div>
                <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                  <span style={{ fontWeight: 700, fontSize: '14px', color: '#F8FAFC' }}>Bayar Tunai di Tempat (COD)</span>
                </div>
                <div style={{ fontSize: '12px', color: '#94A3B8', marginTop: '3px' }}>Customer membayar tunai langsung ke kurir saat barang tiba</div>
              </div>
              <label className="switch-container">
                <input 
                  type="checkbox" 
                  checked={settings.cod_payment_status !== '0'} 
                  onChange={() => handleToggle('cod_payment_status')} 
                />
                <span className="switch-slider"></span>
              </label>
            </div>
          </div>
        </div>

        {/* 2. SKEMA BISNIS & BAGI HASIL KOMISI */}
        <div className="glass-card" style={{ padding: '24px' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '10px', marginBottom: '20px', borderBottom: '1px solid var(--border-color)', paddingBottom: '14px' }}>
            <div style={{ background: 'rgba(16, 185, 129, 0.15)', padding: '8px', borderRadius: '10px', color: '#10B981' }}>
              <Percent size={20} />
            </div>
            <div>
              <h3 style={{ fontSize: '16px', fontWeight: 700, color: '#F8FAFC' }}>Skema Komisi & Pembagian Hasil</h3>
              <p style={{ fontSize: '12px', color: '#94A3B8' }}>Atur porsi keuntungan platform, driver, dan merchant</p>
            </div>
          </div>

          <div style={{ display: 'flex', flexDirection: 'column', gap: '18px' }}>
            <div>
              <label style={{ fontSize: '13px', fontWeight: 600, color: '#CBD5E1', display: 'block', marginBottom: '8px' }}>
                Komisi Platform Super Admin (%)
              </label>
              <div style={{ position: 'relative' }}>
                <input 
                  type="number" 
                  step="0.5" 
                  min="0" 
                  max="100"
                  value={settings.admin_commission_percent} 
                  onChange={(e) => handleChange('admin_commission_percent', e.target.value)}
                  style={{
                    width: '100%',
                    padding: '12px 16px',
                    background: 'rgba(0,0,0,0.3)',
                    border: '1px solid var(--border-color)',
                    borderRadius: '10px',
                    color: '#F8FAFC',
                    fontSize: '15px',
                    fontWeight: 700
                  }}
                />
                <span style={{ position: 'absolute', right: '16px', top: '12px', color: '#94A3B8', fontWeight: 700 }}>%</span>
              </div>
              <small style={{ fontSize: '11.5px', color: '#64748B', display: 'block', marginTop: '5px' }}>
                Potongan omset dari setiap pesanan merchant yang masuk ke kas CicalengkaGO.
              </small>
            </div>

            <div>
              <label style={{ fontSize: '13px', fontWeight: 600, color: '#CBD5E1', display: 'block', marginBottom: '8px' }}>
                Bagi Hasil Kurir Delivery (%)
              </label>
              <div style={{ position: 'relative' }}>
                <input 
                  type="number" 
                  step="0.5" 
                  min="0" 
                  max="100"
                  value={settings.delivery_man_commission_percent} 
                  onChange={(e) => handleChange('delivery_man_commission_percent', e.target.value)}
                  style={{
                    width: '100%',
                    padding: '12px 16px',
                    background: 'rgba(0,0,0,0.3)',
                    border: '1px solid var(--border-color)',
                    borderRadius: '10px',
                    color: '#F8FAFC',
                    fontSize: '15px',
                    fontWeight: 700
                  }}
                />
                <span style={{ position: 'absolute', right: '16px', top: '12px', color: '#94A3B8', fontWeight: 700 }}>%</span>
              </div>
              <small style={{ fontSize: '11.5px', color: '#64748B', display: 'block', marginTop: '5px' }}>
                Persentase ongkir yang otomatis masuk ke saldo dompet driver setelah pesanan 'Delivered'.
              </small>
            </div>

            {/* Visual Calculator Preview */}
            <div style={{ 
              background: 'rgba(238, 39, 55, 0.08)', 
              border: '1px dashed rgba(238, 39, 55, 0.3)', 
              borderRadius: '12px', 
              padding: '16px' 
            }}>
              <div style={{ fontSize: '12px', fontWeight: 700, color: '#EE2737', marginBottom: '8px', textTransform: 'uppercase' }}>
                Simulasi Pembagian Pesanan Rp 50.000 (Ongkir Rp 10.000)
              </div>
              <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '13px', marginBottom: '4px', color: '#CBD5E1' }}>
                <span>Mitra Toko / Resto:</span>
                <strong style={{ color: '#F8FAFC' }}>
                  {formatRupiah(50000 * (1 - (Number(settings.admin_commission_percent) || 10) / 100))}
                </strong>
              </div>
              <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '13px', marginBottom: '4px', color: '#CBD5E1' }}>
                <span>Driver Kurir (Ongkir):</span>
                <strong style={{ color: '#34D399' }}>
                  {formatRupiah(10000 * ((Number(settings.delivery_man_commission_percent) || 80) / 100))}
                </strong>
              </div>
              <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '13px', borderTop: '1px solid rgba(255,255,255,0.1)', paddingTop: '6px', color: '#F87171' }}>
                <span>Total Laba Platform CicalengkaGO:</span>
                <strong style={{ color: '#F87171' }}>
                  {formatRupiah((50000 * ((Number(settings.admin_commission_percent) || 10) / 100)) + (10000 * (1 - (Number(settings.delivery_man_commission_percent) || 80) / 100)))}
                </strong>
              </div>
            </div>
          </div>
        </div>

        {/* 3. SKEMA ONGKIR & RADIUS DELIVERY */}
        <div className="glass-card" style={{ padding: '24px' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '10px', marginBottom: '20px', borderBottom: '1px solid var(--border-color)', paddingBottom: '14px' }}>
            <div style={{ background: 'rgba(59, 130, 246, 0.15)', padding: '8px', borderRadius: '10px', color: '#3B82F6' }}>
              <Truck size={20} />
            </div>
            <div>
              <h3 style={{ fontSize: '16px', fontWeight: 700, color: '#F8FAFC' }}>Skema Ongkir & Tarif Jarak</h3>
              <p style={{ fontSize: '12px', color: '#94A3B8' }}>Biaya pengantaran kurir motor & mobil</p>
            </div>
          </div>

          <div style={{ display: 'flex', flexDirection: 'column', gap: '18px' }}>
            <div>
              <label style={{ fontSize: '13px', fontWeight: 600, color: '#CBD5E1', display: 'block', marginBottom: '8px' }}>
                Tarif Dasar / Ongkir Minimum (Rp)
              </label>
              <input 
                type="number" 
                value={settings.delivery_charge_min} 
                onChange={(e) => handleChange('delivery_charge_min', e.target.value)}
                style={{
                  width: '100%',
                  padding: '12px 16px',
                  background: 'rgba(0,0,0,0.3)',
                  border: '1px solid var(--border-color)',
                  borderRadius: '10px',
                  color: '#F8FAFC',
                  fontSize: '14px',
                  fontWeight: 600
                }}
              />
              <small style={{ fontSize: '11.5px', color: '#64748B', display: 'block', marginTop: '5px' }}>
                Tarif awal untuk jarak 1 - 2 KM pertama.
              </small>
            </div>

            <div>
              <label style={{ fontSize: '13px', fontWeight: 600, color: '#CBD5E1', display: 'block', marginBottom: '8px' }}>
                Tarif Per Kilometer Berikutnya (Rp/KM)
              </label>
              <input 
                type="number" 
                value={settings.delivery_charge_per_km} 
                onChange={(e) => handleChange('delivery_charge_per_km', e.target.value)}
                style={{
                  width: '100%',
                  padding: '12px 16px',
                  background: 'rgba(0,0,0,0.3)',
                  border: '1px solid var(--border-color)',
                  borderRadius: '10px',
                  color: '#F8FAFC',
                  fontSize: '14px',
                  fontWeight: 600
                }}
              />
            </div>

            <div>
              <label style={{ fontSize: '13px', fontWeight: 600, color: '#CBD5E1', display: 'block', marginBottom: '8px' }}>
                Ambang Batas Promo Gratis Ongkir (Rp)
              </label>
              <input 
                type="number" 
                value={settings.free_delivery_over} 
                onChange={(e) => handleChange('free_delivery_over', e.target.value)}
                style={{
                  width: '100%',
                  padding: '12px 16px',
                  background: 'rgba(0,0,0,0.3)',
                  border: '1px solid var(--border-color)',
                  borderRadius: '10px',
                  color: '#F8FAFC',
                  fontSize: '14px',
                  fontWeight: 600
                }}
              />
              <small style={{ fontSize: '11.5px', color: '#64748B', display: 'block', marginTop: '5px' }}>
                Belanja di atas nominal ini ongkos kirim disubsidi / gratis bagi customer.
              </small>
            </div>
          </div>
        </div>

        {/* 4. OPERASIONAL FLEKSIBEL & KEAMANAN */}
        <div className="glass-card" style={{ padding: '24px' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '10px', marginBottom: '20px', borderBottom: '1px solid var(--border-color)', paddingBottom: '14px' }}>
            <div style={{ background: 'rgba(245, 158, 11, 0.15)', padding: '8px', borderRadius: '10px', color: '#F59E0B' }}>
              <ShieldCheck size={20} />
            </div>
            <div>
              <h3 style={{ fontSize: '16px', fontWeight: 700, color: '#F8FAFC' }}>Operasional & Keamanan Aplikasi</h3>
              <p style={{ fontSize: '12px', color: '#94A3B8' }}>Mode OTP, Single Order Driver, dan Maintenance</p>
            </div>
          </div>

          <div style={{ display: 'flex', flexDirection: 'column', gap: '16px' }}>
            {/* Single active order */}
            <div style={{ 
              display: 'flex', 
              alignItems: 'center', 
              justifyContent: 'space-between', 
              padding: '14px 18px', 
              background: 'rgba(255,255,255,0.03)', 
              borderRadius: '12px' 
            }}>
              <div>
                <span style={{ fontWeight: 700, fontSize: '14px', color: '#F8FAFC' }}>1 Driver = 1 Order Aktif</span>
                <p style={{ fontSize: '12px', color: '#94A3B8', marginTop: '3px' }}>
                  Driver tidak bisa menerima order baru sampai order berjalan selesai diantar
                </p>
              </div>
              <label className="switch-container">
                <input 
                  type="checkbox" 
                  checked={settings.single_active_order_driver === '1'} 
                  onChange={() => handleToggle('single_active_order_driver')} 
                />
                <span className="switch-slider"></span>
              </label>
            </div>

            {/* OTP Handover saat Delivery */}
            <div style={{ 
              display: 'flex', 
              alignItems: 'center', 
              justifyContent: 'space-between', 
              padding: '14px 18px', 
              background: 'rgba(255,255,255,0.03)', 
              borderRadius: '12px' 
            }}>
              <div>
                <span style={{ fontWeight: 700, fontSize: '14px', color: '#F8FAFC' }}>Verifikasi PIN/OTP Saat Serah Terima</span>
                <p style={{ fontSize: '12px', color: '#94A3B8', marginTop: '3px' }}>
                  Driver wajib memasukkan PIN dari customer saat menyelesaikan pesanan
                </p>
              </div>
              <label className="switch-container">
                <input 
                  type="checkbox" 
                  checked={settings.otp_delivery_verification === '1'} 
                  onChange={() => handleToggle('otp_delivery_verification')} 
                />
                <span className="switch-slider"></span>
              </label>
            </div>

            {/* Maintenance Mode */}
            <div style={{ 
              display: 'flex', 
              alignItems: 'center', 
              justifyContent: 'space-between', 
              padding: '14px 18px', 
              background: 'rgba(238, 39, 55, 0.08)', 
              border: '1px solid rgba(238, 39, 55, 0.25)', 
              borderRadius: '12px' 
            }}>
              <div>
                <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                  <Power size={18} color="#EE2737" />
                  <span style={{ fontWeight: 700, fontSize: '14px', color: '#F87171' }}>Maintenance Mode (Tutup Sementara)</span>
                </div>
                <p style={{ fontSize: '12px', color: '#94A3B8', marginTop: '3px' }}>
                  Kunci aplikasi dengan halaman pemeliharaan sistem
                </p>
              </div>
              <label className="switch-container">
                <input 
                  type="checkbox" 
                  checked={settings.maintenance_mode === '1'} 
                  onChange={() => handleToggle('maintenance_mode')} 
                />
                <span className="switch-slider danger"></span>
              </label>
            </div>

            {/* CS WhatsApp */}
            <div style={{ marginTop: '10px' }}>
              <label style={{ fontSize: '13px', fontWeight: 600, color: '#CBD5E1', display: 'block', marginBottom: '8px' }}>
                Nomor WhatsApp Helpdesk / CS Resmi
              </label>
              <input 
                type="text" 
                value={settings.support_whatsapp} 
                onChange={(e) => handleChange('support_whatsapp', e.target.value)}
                placeholder="081234567890"
                style={{
                  width: '100%',
                  padding: '12px 16px',
                  background: 'rgba(0,0,0,0.3)',
                  border: '1px solid var(--border-color)',
                  borderRadius: '10px',
                  color: '#F8FAFC',
                  fontSize: '14px'
                }}
              />
            </div>
          </div>
        </div>

      </div>
    </div>
  );
}

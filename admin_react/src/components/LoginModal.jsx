import React, { useState } from 'react';
import { Lock, Mail, ShieldAlert, ArrowRight, RefreshCw, KeyRound } from 'lucide-react';
import { fetchApi } from '../utils/api';

export default function LoginModal({ onLoginSuccess }) {
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [errorMsg, setErrorMsg] = useState('');

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!username || !password) {
      setErrorMsg('Harap isi username/email dan password.');
      return;
    }

    setLoading(true);
    setErrorMsg('');

    const formData = new FormData();
    formData.append('username', username);
    formData.append('password', password);

    const res = await fetchApi('/api/login', {
      method: 'POST',
      body: formData
    });

    setLoading(false);

    if (res.success && res.data) {
      const role = res.data.user?.role;
      if (role !== 'admin' && role !== 'super_admin') {
        setErrorMsg('Akses ditolak. Akun Anda bukan administrator.');
        return;
      }

      // Save token and user info
      localStorage.setItem('cicago_admin_token', res.data.token);
      localStorage.setItem('cicago_admin_user', JSON.stringify(res.data.user));
      onLoginSuccess(res.data.user);
    } else {
      setErrorMsg(res.message || 'Login gagal. Periksa username dan password.');
    }
  };

  return (
    <div style={{
      position: 'fixed',
      inset: 0,
      background: 'radial-gradient(circle at center, rgba(15, 23, 42, 0.95) 0%, #030712 100%)',
      backdropFilter: 'blur(16px)',
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center',
      zIndex: 99999,
      padding: '20px'
    }}>
      <div className="glass-card" style={{
        width: '100%',
        maxWidth: '420px',
        padding: '36px 32px',
        border: '1px solid rgba(255, 255, 255, 0.12)',
        borderRadius: '24px',
        boxShadow: '0 25px 50px -12px rgba(0, 0, 0, 0.7), 0 0 40px -10px rgba(238, 39, 55, 0.3)'
      }}>
        {/* Brand Icon */}
        <div style={{ textAlign: 'center', marginBottom: '24px' }}>
          <div style={{
            width: '56px',
            height: '56px',
            borderRadius: '16px',
            background: 'linear-gradient(135deg, #EE2737 0%, #991B1B 100%)',
            display: 'inline-flex',
            alignItems: 'center',
            justifyContent: 'center',
            boxShadow: '0 8px 24px rgba(238, 39, 55, 0.4)',
            marginBottom: '14px',
            color: 'white'
          }}>
            <Lock size={26} />
          </div>
          <h2 style={{ fontSize: '22px', fontWeight: 800, color: '#F8FAFC', letterSpacing: '-0.4px' }}>
            Cicalengka<span style={{ color: '#EE2737' }}>GO</span>
          </h2>
          <p style={{ fontSize: '13px', color: '#94A3B8', marginTop: '4px' }}>
            Portal Masuk Super Administrator
          </p>
        </div>

        {errorMsg && (
          <div style={{
            display: 'flex',
            alignItems: 'center',
            gap: '8px',
            background: 'rgba(238, 39, 55, 0.15)',
            border: '1px solid rgba(238, 39, 55, 0.3)',
            borderRadius: '12px',
            padding: '12px 14px',
            color: '#F87171',
            fontSize: '13px',
            marginBottom: '20px'
          }}>
            <ShieldAlert size={18} style={{ flexShrink: 0 }} />
            <span>{errorMsg}</span>
          </div>
        )}

        <form onSubmit={handleSubmit} style={{ display: 'flex', flexDirection: 'column', gap: '18px' }}>
          <div>
            <label style={{ fontSize: '12.5px', fontWeight: 600, color: '#CBD5E1', display: 'block', marginBottom: '6px' }}>
              Username atau Email Admin
            </label>
            <div style={{ position: 'relative' }}>
              <input
                type="text"
                placeholder="admin@cicalengkago.id"
                value={username}
                onChange={(e) => setUsername(e.target.value)}
                autoFocus
                style={{
                  width: '100%',
                  padding: '12px 16px 12px 42px',
                  background: 'rgba(0, 0, 0, 0.4)',
                  border: '1px solid var(--border-color)',
                  borderRadius: '12px',
                  color: '#F8FAFC',
                  fontSize: '14px',
                  outline: 'none',
                  transition: 'border-color 0.2s'
                }}
              />
              <Mail size={17} style={{ position: 'absolute', left: '14px', top: '14px', color: '#64748B' }} />
            </div>
          </div>

          <div>
            <label style={{ fontSize: '12.5px', fontWeight: 600, color: '#CBD5E1', display: 'block', marginBottom: '6px' }}>
              Kata Sandi (Password)
            </label>
            <div style={{ position: 'relative' }}>
              <input
                type="password"
                placeholder="••••••••••••"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                style={{
                  width: '100%',
                  padding: '12px 16px 12px 42px',
                  background: 'rgba(0, 0, 0, 0.4)',
                  border: '1px solid var(--border-color)',
                  borderRadius: '12px',
                  color: '#F8FAFC',
                  fontSize: '14px',
                  outline: 'none',
                  transition: 'border-color 0.2s'
                }}
              />
              <KeyRound size={17} style={{ position: 'absolute', left: '14px', top: '14px', color: '#64748B' }} />
            </div>
          </div>

          <button
            type="submit"
            disabled={loading}
            className="btn-modern-primary"
            style={{
              width: '100%',
              justifyContent: 'center',
              padding: '13px',
              fontSize: '14px',
              marginTop: '6px'
            }}
          >
            {loading ? (
              <>
                <RefreshCw size={17} style={{ animation: 'spin 1s linear infinite' }} />
                <span>Memverifikasi Akses...</span>
              </>
            ) : (
              <>
                <span>Masuk Dashboard Admin</span>
                <ArrowRight size={17} />
              </>
            )}
          </button>
        </form>

        <div style={{ marginTop: '24px', textAlign: 'center', fontSize: '11.5px', color: '#64748B' }}>
          Sesi terenkripsi token aman CicalengkaGO Enterprise.
        </div>
      </div>
    </div>
  );
}

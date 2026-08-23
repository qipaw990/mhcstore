/**
 * =====================================================
 * CicalengkaGO - WhatsApp OTP Gateway
 * Self-hosted WhatsApp Gateway menggunakan whatsapp-web.js
 * 
 * Endpoints:
 *   GET  /              - Dashboard status & QR
 *   GET  /status        - JSON status koneksi
 *   GET  /qr            - Halaman scan QR
 *   POST /send-otp      - Kirim kode OTP ke nomor WA
 *   POST /send-message  - Kirim pesan bebas
 * =====================================================
 */

require('dotenv').config();
const express    = require('express');
const { Client, LocalAuth } = require('whatsapp-web.js');
const qrcode     = require('qrcode');
const cors       = require('cors');
const path       = require('path');

const app    = express();
const PORT   = process.env.PORT || 3005;
const SECRET = process.env.WA_GATEWAY_SECRET || 'cicago_wa_secret_2024';

// ─── Middleware ───────────────────────────────────────────────────────────────
app.use(cors({ origin: '*' }));
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// ─── State ────────────────────────────────────────────────────────────────────
let waStatus   = 'INITIALIZING'; // INITIALIZING | QR_READY | AUTHENTICATED | READY | DISCONNECTED
let currentQR  = null;
let waClient   = null;
let qrBase64   = null;
let lastError  = null;

// ─── WhatsApp Client Init ─────────────────────────────────────────────────────
function initWhatsApp() {
    console.log('[WA-Gateway] Menginisialisasi WhatsApp client...');
    waStatus  = 'INITIALIZING';
    currentQR = null;
    qrBase64  = null;

    const puppeteerArgs = [
        '--no-sandbox',
        '--disable-setuid-sandbox',
        '--disable-dev-shm-usage',
        '--disable-accelerated-2d-canvas',
        '--no-first-run',
        '--no-zygote',
        '--disable-gpu'
    ];

    // Detect Chrome/Chromium across Linux (Docker/CasaOS) and Windows
    const chromePaths = [
        process.env.PUPPETEER_EXECUTABLE_PATH,
        process.env.CHROMIUM_PATH,
        '/usr/bin/chromium',
        '/usr/bin/chromium-browser',
        '/usr/bin/google-chrome',
        'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe'
    ].filter(Boolean);

    const puppeteerOptions = {
        headless: true,
        args: puppeteerArgs
    };

    // Try to find Chrome executable
    const fs = require('fs');
    for (const cp of chromePaths) {
        if (cp && fs.existsSync(cp)) {
            puppeteerOptions.executablePath = cp;
            console.log(`[WA-Gateway] Menggunakan Chromium/Chrome: ${cp}`);
            break;
        }
    }

    if (!puppeteerOptions.executablePath) {
        console.warn('[WA-Gateway] ⚠️ Path Chromium tidak ditemukan secara eksplisit, mengandalkan Puppeteer default...');
    }

    waClient = new Client({
        authStrategy: new LocalAuth({
            clientId: 'cicago-gateway',
            dataPath: path.join(__dirname, '.wwebjs_auth')
        }),
        webVersionCache: {
            type: 'remote',
            remotePath: 'https://raw.githubusercontent.com/wppconnect-team/wa-version/main/html/2.2412.54.html',
        },
        puppeteer: puppeteerOptions
    });

    // ── Events ──
    waClient.on('qr', async (qr) => {
        waStatus  = 'QR_READY';
        currentQR = qr;
        qrBase64  = await qrcode.toDataURL(qr);
        console.log('[WA-Gateway] QR Code siap — buka http://localhost:' + PORT + '/qr untuk scan');
    });

    waClient.on('authenticated', () => {
        waStatus  = 'AUTHENTICATED';
        currentQR = null;
        qrBase64  = null;
        console.log('[WA-Gateway] ✅ Autentikasi berhasil!');
    });

    waClient.on('ready', () => {
        waStatus = 'READY';
        console.log('[WA-Gateway] ✅ WhatsApp SIAP mengirim pesan!');
    });

    waClient.on('auth_failure', (msg) => {
        waStatus = 'AUTH_FAILED';
        console.error('[WA-Gateway] ❌ Autentikasi gagal:', msg);
    });

    waClient.on('disconnected', (reason) => {
        waStatus = 'DISCONNECTED';
        console.warn('[WA-Gateway] ⚠️ Terputus:', reason);
        // Auto reconnect setelah 10 detik
        setTimeout(() => {
            console.log('[WA-Gateway] Mencoba reconnect...');
            initWhatsApp();
        }, 10000);
    });

    waClient.initialize().catch(err => {
        console.error('[WA-Gateway] ❌ Gagal init:', err.message);
        waStatus  = 'ERROR';
        lastError = err.message;
    });
}

// ─── Auth Middleware ──────────────────────────────────────────────────────────
function authMiddleware(req, res, next) {
    const secret = req.headers['x-wa-secret'] || req.body?.secret;
    if (secret !== SECRET) {
        return res.status(401).json({ success: false, message: 'Unauthorized: Invalid secret key.' });
    }
    next();
}

// ─── Helper: Format nomor HP Indonesia ───────────────────────────────────────
function formatPhoneNumber(phone) {
    let p = String(phone).replace(/\D/g, '');
    if (p.startsWith('0'))   p = '62' + p.slice(1);
    if (p.startsWith('+'))   p = p.slice(1);
    if (!p.startsWith('62')) p = '62' + p;
    return p + '@c.us'; // format WhatsApp
}

// ─── Routes ──────────────────────────────────────────────────────────────────

/**
 * GET / — Dashboard HTML
 */
app.get('/', (req, res) => {
    const statusColor = {
        'INITIALIZING' : '#f59e0b',
        'QR_READY'     : '#3b82f6',
        'AUTHENTICATED': '#10b981',
        'READY'        : '#16a34a',
        'DISCONNECTED' : '#ef4444',
        'AUTH_FAILED'  : '#ef4444',
        'ERROR'        : '#ef4444'
    }[waStatus] || '#6b7280';

    const statusLabel = {
        'INITIALIZING' : '⏳ Menginisialisasi...',
        'QR_READY'     : '📱 Menunggu Scan QR Code',
        'AUTHENTICATED': '✅ Autentikasi Berhasil',
        'READY'        : '🟢 Siap Mengirim Pesan',
        'DISCONNECTED' : '🔴 Terputus',
        'AUTH_FAILED'  : '❌ Autentikasi Gagal',
        'ERROR'        : '❌ Error'
    }[waStatus] || waStatus;

    res.send(`<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CicalengkaGO - WhatsApp Gateway</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #0f172a; color: #e2e8f0; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card { background: #1e293b; border-radius: 16px; padding: 40px; max-width: 480px; width: 90%; box-shadow: 0 25px 50px rgba(0,0,0,0.5); text-align: center; }
        .logo { font-size: 28px; font-weight: 900; margin-bottom: 8px; }
        .logo span { color: #EE2737; }
        .subtitle { color: #94a3b8; font-size: 13px; margin-bottom: 32px; }
        .badge { display: inline-block; padding: 8px 20px; border-radius: 999px; font-weight: 700; font-size: 14px; background: ${statusColor}22; color: ${statusColor}; border: 2px solid ${statusColor}; margin-bottom: 28px; }
        .btn { display: inline-block; margin: 8px; padding: 12px 28px; border-radius: 8px; font-weight: 700; text-decoration: none; font-size: 14px; transition: opacity .2s; }
        .btn:hover { opacity: .85; }
        .btn-green  { background: #16a34a; color: white; }
        .btn-blue   { background: #2563eb; color: white; }
        .btn-red    { background: #dc2626; color: white; }
        .info { background: #0f172a; border-radius: 8px; padding: 16px; margin-top: 24px; text-align: left; font-size: 12px; color: #64748b; }
        .info code { color: #38bdf8; background: #1e3a5f; padding: 2px 6px; border-radius: 4px; }
        .divider { border: none; border-top: 1px solid #334155; margin: 24px 0; }
        h1 { font-size: 20px; margin-bottom: 16px; }
        .meta-refresh { animation: spin 1s linear infinite; display: inline-block; }
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    </style>
    ${waStatus === 'INITIALIZING' || waStatus === 'QR_READY' ? '<meta http-equiv="refresh" content="5">' : ''}
</head>
<body>
<div class="card">
    <div class="logo">Cicalengka<span>GO</span></div>
    <div class="subtitle">WhatsApp OTP Gateway v1.0</div>
    
    <h1>Status Koneksi</h1>
    <div class="badge">${statusLabel}</div>
    
    <div>
        <a href="/qr" class="btn btn-blue">📱 Scan QR Code</a>
        <a href="/status" class="btn btn-green">🔄 Cek Status API</a>
    </div>

    <hr class="divider">
    <div class="info">
        <b>Endpoints tersedia:</b><br><br>
        <code>POST /send-otp</code> — Kirim OTP<br>
        <code>POST /send-message</code> — Kirim pesan<br>
        <code>GET /status</code> — Cek status JSON<br>
        <code>GET /qr</code> — Halaman QR scan<br>
        <br>
        <b>Header required:</b> <code>X-WA-Secret: ${SECRET}</code><br>
        <br>
        <b>Port:</b> <code>${PORT}</code>
    </div>
</div>
</body>
</html>`);
});

/**
 * GET /qr — Halaman QR Code untuk scan
 */
app.get('/qr', (req, res) => {
    if (waStatus === 'READY' || waStatus === 'AUTHENTICATED') {
        return res.send(`<!DOCTYPE html><html><head><meta charset="UTF-8"><title>WA Gateway - Terhubung</title>
        <style>body{font-family:sans-serif;background:#0f172a;color:#e2e8f0;display:flex;align-items:center;justify-content:center;min-height:100vh;} .card{background:#1e293b;border-radius:16px;padding:40px;text-align:center;} .ok{color:#16a34a;font-size:48px;margin-bottom:16px;} h2{margin-bottom:8px;}</style></head>
        <body><div class="card"><div class="ok">✅</div><h2>WhatsApp Sudah Terhubung!</h2><p style="color:#94a3b8">Gateway siap mengirim pesan OTP.</p></div></body></html>`);
    }

    if (!qrBase64) {
        return res.send(`<!DOCTYPE html><html><head><meta charset="UTF-8"><meta http-equiv="refresh" content="3"><title>WA Gateway - Loading</title>
        <style>body{font-family:sans-serif;background:#0f172a;color:#e2e8f0;display:flex;align-items:center;justify-content:center;min-height:100vh;} .card{background:#1e293b;border-radius:16px;padding:40px;text-align:center;}</style></head>
        <body><div class="card"><h2>⏳ QR Code sedang disiapkan...</h2><p style="color:#94a3b8;margin-top:8px">Halaman otomatis refresh dalam 3 detik</p></div></body></html>`);
    }

    res.send(`<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="30">
    <title>Scan QR - CicalengkaGO WA Gateway</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #0f172a; color: #e2e8f0; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card { background: #1e293b; border-radius: 16px; padding: 40px; max-width: 400px; width: 90%; box-shadow: 0 25px 50px rgba(0,0,0,0.5); text-align: center; }
        .logo { font-size: 24px; font-weight: 900; margin-bottom: 4px; }
        .logo span { color: #EE2737; }
        h2 { font-size: 18px; margin: 20px 0 8px; }
        p { color: #94a3b8; font-size: 13px; line-height: 1.6; margin-bottom: 16px; }
        .qr-wrap { background: white; border-radius: 12px; padding: 16px; display: inline-block; margin: 16px 0; }
        .qr-wrap img { display: block; width: 220px; height: 220px; }
        .step { background: #0f172a; border-radius: 8px; padding: 12px 16px; text-align: left; font-size: 12px; color: #64748b; margin-top: 16px; }
        .step ol { padding-left: 16px; }
        .step li { margin-bottom: 4px; }
    </style>
</head>
<body>
<div class="card">
    <div class="logo">Cicalengka<span>GO</span></div>
    <h2>📱 Scan QR Code WhatsApp</h2>
    <p>Buka WhatsApp di HP Anda, lalu scan QR code ini untuk menghubungkan gateway.</p>
    <div class="qr-wrap">
        <img src="${qrBase64}" alt="QR Code WhatsApp">
    </div>
    <p style="font-size:11px;color:#475569">QR Code expire dalam ~60 detik. Halaman otomatis refresh tiap 30 detik.</p>
    <div class="step">
        <ol>
            <li>Buka WhatsApp → Menu (⋮) → Perangkat Tertaut</li>
            <li>Ketuk "Tautkan Perangkat"</li>
            <li>Arahkan kamera ke QR Code di atas</li>
        </ol>
    </div>
</div>
</body>
</html>`);
});

/**
 * GET /status — JSON status
 */
app.get('/status', (req, res) => {
    res.json({
        success : true,
        status  : waStatus,
        ready   : waStatus === 'READY',
        error   : lastError,
        message : waStatus === 'READY' ? 'Gateway siap mengirim pesan.' : (lastError || 'Gateway belum siap.')
    });
});

/**
 * POST /send-otp — Kirim kode OTP
 * Body: { phone, name, otp, secret }
 * Header: X-WA-Secret: <secret>
 */
app.post('/send-otp', authMiddleware, async (req, res) => {
    const { phone, name, otp } = req.body;

    if (!phone || !otp) {
        return res.status(400).json({ success: false, message: 'Parameter phone dan otp wajib diisi.' });
    }

    if (waStatus !== 'READY') {
        return res.status(503).json({ success: false, message: `Gateway belum siap. Status: ${waStatus}` });
    }

    const chatId  = formatPhoneNumber(phone);
    const message = `🔐 *CicalengkaGO - Kode OTP*\n\nHalo, *${name || 'Pengguna'}*!\n\nKode verifikasi Anda adalah:\n\n*${otp}*\n\nKode ini berlaku selama *10 menit*.\nJangan bagikan kode ini kepada siapapun.\n\n_CicalengkaGO - Super App Cicalengka_ 🏍️`;

    try {
        await waClient.sendMessage(chatId, message);
        console.log(`[WA-Gateway] ✅ OTP terkirim ke ${phone}`);
        res.json({ success: true, message: `OTP berhasil dikirim ke ${phone}`, phone: chatId });
    } catch (err) {
        console.error(`[WA-Gateway] ❌ Gagal kirim ke ${phone}:`, err.message);
        res.status(500).json({ success: false, message: `Gagal mengirim pesan: ${err.message}` });
    }
});

/**
 * POST /send-message — Kirim pesan bebas
 * Body: { phone, message, secret }
 * Header: X-WA-Secret: <secret>
 */
app.post('/send-message', authMiddleware, async (req, res) => {
    const { phone, message } = req.body;

    if (!phone || !message) {
        return res.status(400).json({ success: false, message: 'Parameter phone dan message wajib diisi.' });
    }

    if (waStatus !== 'READY') {
        return res.status(503).json({ success: false, message: `Gateway belum siap. Status: ${waStatus}` });
    }

    const chatId = formatPhoneNumber(phone);

    try {
        await waClient.sendMessage(chatId, message);
        console.log(`[WA-Gateway] ✅ Pesan terkirim ke ${phone}`);
        res.json({ success: true, message: `Pesan berhasil dikirim ke ${phone}` });
    } catch (err) {
        console.error(`[WA-Gateway] ❌ Gagal kirim ke ${phone}:`, err.message);
        res.status(500).json({ success: false, message: `Gagal mengirim pesan: ${err.message}` });
    }
});

/**
 * POST /restart — Restart koneksi WA
 */
app.post('/restart', authMiddleware, async (req, res) => {
    console.log('[WA-Gateway] Restarting client...');
    try {
        if (waClient) {
            await waClient.destroy();
        }
    } catch (e) { /* ignore */ }
    initWhatsApp();
    res.json({ success: true, message: 'Gateway sedang di-restart.' });
});

// ─── Start Server ─────────────────────────────────────────────────────────────
app.listen(PORT, () => {
    console.log('');
    console.log('╔════════════════════════════════════════╗');
    console.log('║   CicalengkaGO WhatsApp OTP Gateway    ║');
    console.log('╠════════════════════════════════════════╣');
    console.log(`║  Running on: http://localhost:${PORT}       ║`);
    console.log(`║  Secret Key: ${SECRET.slice(0, 10)}...          ║`);
    console.log('╚════════════════════════════════════════╝');
    console.log('');
    console.log('📌 Buka http://localhost:' + PORT + '/qr untuk scan QR Code');
    console.log('');
    initWhatsApp();
});

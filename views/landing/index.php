<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- SEO -->
    <title>CicalengkaGO — Super App On-Demand Cicalengka</title>
    <meta name="description" content="CicalengkaGO adalah platform super app on-demand pertama di Cicalengka. Pesan makanan, belanja sembako, farmasi, olshop lokal, dan kirim paket — semua dalam satu aplikasi!">
    <meta name="keywords" content="CicalengkaGO, cicago, cicalengka, delivery, ojek online, pesan makanan, sembako, farmasi, kirim paket, bandung">
    <meta name="author" content="CicalengkaGO">
    <meta property="og:title" content="CicalengkaGO — Super App On-Demand Cicalengka">
    <meta property="og:description" content="Pesan makanan, belanja sembako, farmasi, dan kirim paket di Cicalengka langsung lewat browser di market.cicago.store!">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://cicago.store">
    <meta name="theme-color" content="#ffffff">

    <!-- Favicon & Brand Icons -->
    <link rel="icon" type="image/png" sizes="32x32" href="<?= htmlspecialchars($publicUrl ?? '') ?>/assets/icons/favicon.png">
    <link rel="icon" type="image/png" sizes="192x192" href="<?= htmlspecialchars($publicUrl ?? '') ?>/assets/icons/icon-192.png">
    <link rel="apple-touch-icon" href="<?= htmlspecialchars($publicUrl ?? '') ?>/assets/images/app_logo.png">
    <meta property="og:image" content="<?= htmlspecialchars($publicUrl ?? '') ?>/assets/images/app_logo.png">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        /* ========== RESET & BASE ========== */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --red:            #e8232a;
            --red-dark:       #b91c22;
            --red-light:      #fff1f2;
            --red-glow:       rgba(232,35,42,0.18);
            --orange:         #f59e0b;
            --bg:             #ffffff;
            --bg-subtle:      #f8fafc;
            --bg-muted:       #f1f5f9;
            --card:           #ffffff;
            --border:         #e2e8f0;
            --border-light:   #f1f5f9;
            --text:           #0f172a;
            --text-secondary: #334155;
            --muted:          #64748b;
            --radius:         18px;
            --font:           'Plus Jakarta Sans', system-ui, sans-serif;
            --shadow-sm:      0 1px 3px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.03);
            --shadow-card:    0 4px 20px -2px rgba(0,0,0,0.05), 0 2px 6px -1px rgba(0,0,0,0.02);
            --shadow-hover:   0 20px 35px -5px rgba(232,35,42,0.1), 0 8px 16px -4px rgba(0,0,0,0.04);
        }
        html { scroll-behavior: smooth; }
        body {
            font-family: var(--font);
            background: var(--bg);
            color: var(--text);
            overflow-x: hidden;
            line-height: 1.6;
        }
        a { text-decoration: none; color: inherit; }
        img { max-width: 100%; height: auto; }

        /* ========== SCROLLBAR ========== */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: var(--bg-muted); }
        ::-webkit-scrollbar-thumb { background: var(--red); border-radius: 3px; }

        /* ========== NAVBAR ========== */
        .navbar {
            position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
            display: flex; align-items: center; justify-content: space-between;
            gap: 16px;
            padding: 16px 5%;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border);
            transition: padding 0.3s ease, background 0.3s ease, box-shadow 0.3s ease;
            box-sizing: border-box;
            width: 100%;
        }
        .navbar.scrolled { padding: 12px 5%; box-shadow: 0 4px 30px rgba(0,0,0,0.07); }
        .nav-logo {
            display: inline-flex; align-items: center; gap: 10px;
            font-size: 20px; font-weight: 800; letter-spacing: -0.5px;
            text-decoration: none; color: var(--text);
            white-space: nowrap;
            flex-shrink: 0;
        }
        .nav-logo .logo-img {
            width: 36px; height: 36px; border-radius: 10px;
            object-fit: cover; display: block; flex-shrink: 0;
            box-shadow: 0 0 16px var(--red-glow), 0 2px 8px rgba(0,0,0,0.1);
            border: 1.5px solid rgba(232,35,42,0.25);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }
        .nav-logo:hover .logo-img {
            transform: scale(1.08) rotate(-2deg);
            box-shadow: 0 0 22px rgba(232,35,42,0.35);
        }
        .nav-logo span { color: var(--red); }
        .nav-links { display: flex; align-items: center; gap: 32px; }
        .nav-links a {
            font-size: 14px; font-weight: 600; color: var(--text-secondary);
            transition: color 0.2s;
        }
        .nav-links a:hover { color: var(--red); }
        .nav-cta {
            background: var(--red); color: #fff;
            padding: 10px 22px; border-radius: 10px;
            font-size: 14px; font-weight: 700;
            box-shadow: 0 4px 18px var(--red-glow);
            transition: transform 0.2s, box-shadow 0.2s;
            white-space: nowrap;
            display: inline-flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .nav-cta:hover { transform: translateY(-2px); box-shadow: 0 6px 25px var(--red-glow); }
        .nav-mobile { display: none; }

        /* ========== HERO ========== */
        .hero {
            min-height: 100vh;
            display: flex; align-items: center;
            position: relative; overflow: hidden;
            padding: 125px 5% 80px;
            background: #ffffff;
        }
        .hero-bg {
            position: absolute; inset: 0; z-index: 0;
            background:
                radial-gradient(ellipse 65% 55% at 75% 35%, rgba(232,35,42,0.07) 0%, transparent 65%),
                radial-gradient(ellipse 45% 45% at 15% 75%, rgba(245,158,11,0.05) 0%, transparent 60%),
                linear-gradient(180deg, #ffffff 0%, #fbfcfe 100%);
        }
        .hero-grid {
            position: absolute; inset: 0; z-index: 0;
            background-image:
                linear-gradient(rgba(0,0,0,0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0,0,0,0.03) 1px, transparent 1px);
            background-size: 50px 50px;
            mask-image: radial-gradient(ellipse 80% 80% at 50% 50%, black 30%, transparent 85%);
        }
        .hero-content { position: relative; z-index: 1; max-width: 620px; }
        .hero-badge {
            display: inline-flex; align-items: center; gap: 8px;
            background: #fff1f2; border: 1px solid rgba(232,35,42,0.25);
            color: var(--red); padding: 6px 14px; border-radius: 100px;
            font-size: 12px; font-weight: 700; letter-spacing: 0.5px;
            margin-bottom: 24px;
        }
        .hero-badge .dot {
            width: 7px; height: 7px; background: var(--red);
            border-radius: 50%; animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.8); }
        }
        .hero h1 {
            font-size: clamp(38px, 5.5vw, 68px);
            font-weight: 900; line-height: 1.08;
            letter-spacing: -2px; margin-bottom: 22px;
            color: var(--text);
        }
        .hero h1 .highlight {
            background: linear-gradient(135deg, var(--red) 0%, #ea580c 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .hero p {
            font-size: 17px; color: var(--text-secondary); line-height: 1.7;
            max-width: 520px; margin-bottom: 36px;
        }
        .hero-actions { display: flex; gap: 14px; flex-wrap: wrap; }
        .btn-primary {
            display: inline-flex; align-items: center; gap: 10px;
            background: var(--red); color: #fff;
            padding: 15px 28px; border-radius: 14px;
            font-size: 15px; font-weight: 700;
            box-shadow: 0 6px 25px var(--red-glow);
            transition: all 0.25s ease;
        }
        .btn-primary:hover { transform: translateY(-3px); box-shadow: 0 10px 32px var(--red-glow); }
        .btn-secondary {
            display: inline-flex; align-items: center; gap: 10px;
            background: #ffffff; color: var(--text);
            border: 1.5px solid var(--border);
            padding: 15px 28px; border-radius: 14px;
            font-size: 15px; font-weight: 700;
            box-shadow: var(--shadow-sm);
            transition: all 0.25s ease;
        }
        .btn-secondary:hover { background: var(--bg-subtle); border-color: #cbd5e1; transform: translateY(-3px); }

        /* Floating phone mockup */
        .hero-visual {
            position: absolute; right: 3%; top: 50%;
            transform: translateY(-50%);
            z-index: 1;
            width: 420px;
        }
        /* City backdrop photo behind phone */
        .city-backdrop {
            position: absolute;
            right: -20px; top: 50%; transform: translateY(-50%);
            width: 340px; height: 480px;
            border-radius: 24px; overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.18);
            z-index: 0;
        }
        .city-backdrop img {
            width: 100%; height: 100%; object-fit: cover;
            filter: brightness(0.75) saturate(1.1);
        }
        .city-backdrop::after {
            content: '';
            position: absolute; inset: 0;
            background: linear-gradient(135deg, rgba(232,35,42,0.25) 0%, rgba(15,23,42,0.5) 100%);
        }
        /* Floating city label tag */
        .city-label {
            position: absolute;
            bottom: 24px; left: 50%; transform: translateX(-50%);
            z-index: 2;
            background: rgba(255,255,255,0.92);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255,255,255,0.6);
            border-radius: 100px;
            padding: 8px 16px;
            display: flex; align-items: center; gap: 8px;
            font-size: 12px; font-weight: 700; color: #0f172a;
            white-space: nowrap;
            box-shadow: 0 4px 20px rgba(0,0,0,0.12);
        }
        .city-label .cl-dot {
            width: 8px; height: 8px; border-radius: 50%;
            background: #22c55e;
            box-shadow: 0 0 8px rgba(34,197,94,0.6);
            flex-shrink: 0;
        }
        /* Floating mini stat cards */
        .hero-float-card {
            position: absolute;
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255,255,255,0.7);
            border-radius: 14px;
            padding: 10px 14px;
            display: flex; align-items: center; gap: 10px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.1);
            z-index: 3;
            font-size: 12px; font-weight: 700; color: #0f172a;
        }
        .hero-float-card .fc-icon { font-size: 22px; }
        .hero-float-card .fc-val { font-size: 16px; font-weight: 900; color: var(--red); }
        .hero-float-card .fc-sub { font-size: 10px; color: var(--muted); font-weight: 500; }
        .float-top-left {
            top: 30px; left: -30px;
            animation: floatCard1 5s ease-in-out infinite;
        }
        .float-bottom-right {
            bottom: 50px; right: -20px;
            animation: floatCard2 6s ease-in-out infinite;
        }
        @keyframes floatCard1 {
            0%,100% { transform: translateY(0px) rotate(-1deg); }
            50% { transform: translateY(-10px) rotate(1deg); }
        }
        @keyframes floatCard2 {
            0%,100% { transform: translateY(0px) rotate(1deg); }
            50% { transform: translateY(-8px) rotate(-1deg); }
        }
        .phone-mockup {
            width: 260px;
            position: relative; z-index: 2;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 38px;
            border: 3px solid #0f172a;
            box-shadow:
                0 30px 70px rgba(0,0,0,0.18),
                0 10px 25px rgba(232,35,42,0.1),
                inset 0 0 0 2px #e2e8f0;
            padding: 14px 12px;
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-14px); }
        }
        .phone-screen { border-radius: 26px; overflow: hidden; background: #ffffff; border: 1px solid #e2e8f0; }
        .phone-header {
            background: linear-gradient(135deg, #e8232a, #ff4757); padding: 14px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .phone-header-title { font-size: 13px; font-weight: 700; color: #fff; }
        .phone-header-sub { font-size: 10px; color: rgba(255,255,255,0.85); }
        .phone-greeting {
            padding: 14px 14px 8px;
            font-size: 12px; color: var(--muted);
        }
        .phone-greeting strong { color: var(--text); font-size: 14px; display: block; }
        .phone-services {
            display: grid; grid-template-columns: repeat(4, 1fr);
            gap: 8px; padding: 0 14px 14px;
        }
        .phone-svc {
            display: flex; flex-direction: column; align-items: center; gap: 4px;
            background: var(--bg-subtle); border-radius: 12px; padding: 10px 4px;
            font-size: 9px; color: var(--text-secondary); font-weight: 600;
            border: 1px solid var(--border);
        }
        .phone-svc span:first-child { font-size: 20px; }
        /* City photo in phone banner */
        .phone-city-photo {
            margin: 0 14px 14px;
            border-radius: 12px; overflow: hidden;
            position: relative;
            height: 100px;
        }
        .phone-city-photo img {
            width: 100%; height: 100%; object-fit: cover;
            border-radius: 12px;
        }
        .phone-city-photo::after {
            content: '';
            position: absolute; inset: 0;
            background: linear-gradient(to top, rgba(15,23,42,0.7) 0%, transparent 55%);
            border-radius: 12px;
        }
        .phone-city-label {
            position: absolute; bottom: 8px; left: 10px; right: 10px;
            z-index: 2; color: #fff;
            font-size: 9.5px; font-weight: 700;
            display: flex; align-items: center; gap: 6px;
        }
        .phone-city-label span.tag {
            background: var(--red);
            padding: 2px 6px; border-radius: 100px; font-size: 8.5px;
        }
        .phone-banner {
            margin: 0 14px 14px;
            background: #fff1f2;
            border-radius: 12px; padding: 12px;
            border: 1px solid rgba(232,35,42,0.2);
            font-size: 10px; color: var(--text-secondary);
        }
        .phone-banner strong { color: var(--red); display: block; font-size: 12px; margin-bottom: 2px; }

        /* ========== STATS ========== */
        .stats-bar {
            background: #ffffff;
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
            padding: 36px 5%;
            box-shadow: var(--shadow-sm);
        }
        .stats-inner {
            max-width: 1100px; margin: 0 auto;
            display: grid; grid-template-columns: repeat(4, 1fr);
            gap: 24px; text-align: center;
        }
        .stat-item { }
        .stat-num {
            font-size: 38px; font-weight: 900;
            background: linear-gradient(135deg, #0f172a 0%, #e8232a 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -1px;
        }
        .stat-num .stat-suffix { font-size: 22px; }
        .stat-label { font-size: 13.5px; color: var(--text-secondary); font-weight: 600; margin-top: 4px; }

        /* ========== SECTIONS COMMON ========== */
        section { padding: 90px 5%; }
        .section-label {
            display: inline-flex; align-items: center; gap: 8px;
            font-size: 12px; font-weight: 800; letter-spacing: 2px;
            text-transform: uppercase; color: var(--red);
            margin-bottom: 16px;
        }
        .section-title {
            font-size: clamp(28px, 4vw, 44px);
            font-weight: 800; letter-spacing: -1px;
            line-height: 1.15; margin-bottom: 16px;
            color: var(--text);
        }
        .section-subtitle { font-size: 16px; color: var(--text-secondary); max-width: 540px; }
        .section-header { margin-bottom: 60px; }
        .max-w { max-width: 1100px; margin: 0 auto; }

        /* ========== SERVICES ========== */
        .services-bg { background: #f8fafc; }
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        .service-card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 28px 24px;
            transition: all 0.3s ease;
            position: relative; overflow: hidden;
            box-shadow: var(--shadow-card);
            cursor: default;
        }
        .service-card::before {
            content: '';
            position: absolute; inset: 0;
            background: radial-gradient(circle at top left, var(--svc-color, var(--red))18 0%, transparent 60%);
            opacity: 0; transition: opacity 0.3s;
        }
        .service-card:hover { transform: translateY(-6px); border-color: rgba(232,35,42,0.3); box-shadow: var(--shadow-hover); }
        .service-card:hover::before { opacity: 1; }
        .svc-icon {
            width: 52px; height: 52px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 26px; margin-bottom: 16px;
        }
        .svc-name { font-size: 17px; font-weight: 800; color: var(--text); margin-bottom: 8px; }
        .svc-desc { font-size: 13.5px; color: var(--text-secondary); line-height: 1.6; }
        .svc-badge {
            display: inline-block; margin-top: 14px;
            font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 6px;
        }

        /* Service Colors */
        .svc-food .svc-icon   { background: rgba(239,68,68,0.12); }
        .svc-grocery .svc-icon { background: rgba(16,185,129,0.12); }
        .svc-pharma .svc-icon  { background: rgba(6,182,212,0.12); }
        .svc-shop .svc-icon    { background: rgba(139,92,246,0.12); }
        .svc-parcel .svc-icon  { background: rgba(245,158,11,0.12); }

        .svc-food .svc-badge   { background: rgba(239,68,68,0.12); color: #dc2626; }
        .svc-grocery .svc-badge { background: rgba(16,185,129,0.12); color: #059669; }
        .svc-pharma .svc-badge  { background: rgba(6,182,212,0.12); color: #0891b2; }
        .svc-shop .svc-badge    { background: rgba(139,92,246,0.12); color: #7c3aed; }
        .svc-parcel .svc-badge  { background: rgba(245,158,11,0.12); color: #d97706; }

        /* ========== FEATURES ========== */
        .features-grid {
            display: grid; grid-template-columns: 1fr 1fr;
            gap: 24px;
        }
        .feature-card {
            background: #ffffff; border: 1px solid var(--border);
            border-radius: var(--radius); padding: 32px;
            box-shadow: var(--shadow-card);
            transition: all 0.3s; position: relative; overflow: hidden;
        }
        .feature-card.featured {
            grid-column: span 2;
            display: grid; grid-template-columns: 1fr 1fr; gap: 40px;
            align-items: center;
            background: linear-gradient(135deg, #ffffff 0%, #fff7f7 100%);
            border-color: rgba(232,35,42,0.25);
            box-shadow: 0 10px 30px rgba(232,35,42,0.06);
        }
        .feature-card:hover { transform: translateY(-4px); border-color: rgba(232,35,42,0.35); box-shadow: var(--shadow-hover); }
        .feat-icon {
            width: 56px; height: 56px; border-radius: 16px;
            background: linear-gradient(135deg, var(--red), #ff6b35);
            display: flex; align-items: center; justify-content: center;
            font-size: 28px; margin-bottom: 20px;
            box-shadow: 0 8px 24px var(--red-glow);
        }
        .feature-card h3 { font-size: 20px; font-weight: 800; color: var(--text); margin-bottom: 10px; }
        .feature-card p  { font-size: 14px; color: var(--text-secondary); line-height: 1.7; }
        .feat-visual {
            background: #ffffff; border: 1px solid var(--border);
            border-radius: 16px; padding: 24px;
            box-shadow: var(--shadow-sm);
            display: flex; flex-direction: column; gap: 12px;
        }
        .feat-track-item {
            display: flex; align-items: center; gap: 12px;
            background: var(--bg-subtle); border: 1px solid var(--border-light);
            border-radius: 10px; padding: 12px 16px;
        }
        .track-dot {
            width: 10px; height: 10px; border-radius: 50%;
            background: var(--red); flex-shrink: 0;
            box-shadow: 0 0 8px var(--red-glow);
        }
        .track-line { width: 1px; height: 24px; background: var(--border); margin-left: 4px; }
        .track-label { font-size: 12px; color: var(--muted); }
        .track-label strong { color: var(--text); display: block; font-size: 13px; font-weight: 700; }

        /* ========== HOW IT WORKS ========== */
        .how-bg { background: #ffffff; }
        .steps-grid {
            display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px;
            position: relative;
        }
        .steps-grid::before {
            content: '';
            position: absolute; top: 32px; left: calc(12.5% + 16px); right: calc(12.5% + 16px);
            height: 2px;
            background: linear-gradient(90deg, var(--red) 0%, var(--orange) 100%);
            opacity: 0.3;
        }
        .step-card {
            text-align: center; padding: 0 16px;
        }
        .step-num {
            width: 64px; height: 64px; border-radius: 50%;
            background: linear-gradient(135deg, var(--red), #ff4d4d);
            display: flex; align-items: center; justify-content: center;
            font-size: 24px; font-weight: 900; color: #fff;
            margin: 0 auto 20px;
            box-shadow: 0 8px 24px var(--red-glow);
            position: relative; z-index: 1;
        }
        .step-card h3 { font-size: 16px; font-weight: 800; color: var(--text); margin-bottom: 8px; }
        .step-card p  { font-size: 13.5px; color: var(--text-secondary); }

        /* ========== PAYMENT ========== */
        #pembayaran { background: #f8fafc; }
        .payment-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
            gap: 16px;
        }
        @media (max-width: 480px) {
            .payment-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }
        }
        .payment-card {
            background: #ffffff; border: 1px solid var(--border);
            border-radius: 16px; padding: 18px 12px;
            display: flex; flex-direction: column; align-items: center; gap: 12px;
            box-shadow: var(--shadow-card);
            transition: transform 0.25s ease, border-color 0.25s ease, box-shadow 0.25s ease;
            text-align: center;
        }
        .payment-card:hover {
            transform: translateY(-4px);
            border-color: rgba(232,35,42,0.4);
            box-shadow: var(--shadow-hover);
        }
        .payment-badge-wrap {
            width: 100%;
            max-width: 135px;
            height: 46px;
            background: #ffffff;
            border-radius: 10px;
            border: 1px solid #edf2f7;
            display: flex; align-items: center; justify-content: center;
            padding: 5px 12px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
            transition: transform 0.2s ease;
        }
        .payment-card:hover .payment-badge-wrap {
            transform: scale(1.04);
        }
        .payment-img {
            max-width: 100%;
            max-height: 34px;
            width: auto;
            height: auto;
            object-fit: contain;
            display: block;
        }
        .payment-name { font-size: 13px; font-weight: 800; color: var(--text); line-height: 1.3; }
        .payment-desc { font-size: 11px; color: var(--muted); font-weight: 500; }

        /* ========== PESONA CICALENGKA GALLERY ========== */
        .cicalengka-section {
            padding: 90px 5%;
            background: #ffffff;
            position: relative;
        }
        .cicalengka-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 24px;
            margin-top: 48px;
        }
        .cicalengka-card {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            background: #ffffff;
            border: 1px solid var(--border);
            height: 320px;
            box-shadow: var(--shadow-card);
            transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.35s ease, border-color 0.35s ease;
        }
        .cicalengka-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-hover);
            border-color: rgba(232, 35, 42, 0.45);
        }
        .cicalengka-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .cicalengka-card:hover img {
            transform: scale(1.08);
        }
        .cicalengka-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(0,0,0,0) 0%, rgba(15,23,42,0.4) 40%, rgba(15,23,42,0.92) 100%);
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 24px;
            pointer-events: none;
        }
        .cicalengka-tag {
            align-self: flex-start;
            background: rgba(232, 35, 42, 0.95);
            backdrop-filter: blur(8px);
            color: #fff;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            text-transform: uppercase;
        }
        .cicalengka-title {
            font-size: 19px;
            font-weight: 800;
            color: #fff;
            line-height: 1.3;
            margin-bottom: 6px;
        }
        .cicalengka-desc {
            font-size: 13px;
            color: rgba(255,255,255,0.85);
            line-height: 1.5;
        }

        /* ========== BUKA APLIKASI WEB APP CTA ========== */
        .app-cta-section {
            padding: 90px 5%;
            background: #f8fafc;
            position: relative;
            overflow: hidden;
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
        }
        .app-cta-inner {
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            gap: 50px;
            align-items: center;
            background: linear-gradient(135deg, #b91c22 0%, #e8232a 60%, #ea580c 100%);
            border: 1px solid rgba(255,255,255,0.25);
            border-radius: 32px;
            padding: 56px;
            box-shadow: 0 20px 50px rgba(232,35,42,0.25);
            position: relative;
            color: #ffffff;
        }
        .app-cta-title {
            font-size: clamp(26px, 3.5vw, 40px);
            font-weight: 900;
            line-height: 1.2;
            letter-spacing: -1px;
            margin: 16px 0;
            color: #fff;
        }
        .app-cta-sub {
            font-size: 15px;
            color: rgba(255,255,255,0.9);
            line-height: 1.6;
            margin-bottom: 28px;
        }
        .app-pills {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 28px;
        }
        .app-pill {
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 10px;
            padding: 8px 14px;
            font-size: 12.5px;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .app-cta-btn-main {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            background: #ffffff;
            color: var(--red);
            padding: 16px 32px;
            border-radius: 14px;
            font-size: 16px;
            font-weight: 800;
            text-decoration: none;
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
            transition: all 0.25s ease;
        }
        .app-cta-btn-main:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 12px 35px rgba(0,0,0,0.3);
            background: #fdfdfd;
            color: var(--red-dark);
        }
        .app-browser-card {
            background: #0d0e15;
            border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.15);
            box-shadow: 0 20px 50px rgba(0,0,0,0.35);
            overflow: hidden;
        }
        .browser-bar {
            background: #161822;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        .browser-dots { display: flex; gap: 6px; }
        .browser-dot { width: 10px; height: 10px; border-radius: 50%; }
        .browser-address {
            flex: 1;
            background: rgba(255,255,255,0.08);
            border-radius: 8px;
            padding: 6px 14px;
            font-size: 12.5px;
            color: #d4d4d8;
            display: flex;
            align-items: center;
            gap: 8px;
            font-family: monospace;
        }
        .browser-body {
            padding: 24px;
            display: flex;
            flex-direction: column;
            gap: 16px;
            text-align: center;
        }

        /* ========== FAQ ========== */
        .faq-list { display: flex; flex-direction: column; gap: 12px; }
        .faq-item {
            background: #ffffff; border: 1px solid var(--border);
            border-radius: 14px; overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        .faq-item:hover { border-color: #cbd5e1; }
        .faq-item.open {
            border-color: rgba(232,35,42,0.4);
            box-shadow: 0 8px 25px rgba(232,35,42,0.06);
        }
        .faq-question {
            display: flex; justify-content: space-between; align-items: center;
            padding: 20px 24px; cursor: pointer;
            font-size: 15px; font-weight: 700; user-select: none;
            color: var(--text);
        }
        .faq-question:hover { color: var(--red); }
        .faq-icon {
            width: 28px; height: 28px; border-radius: 8px;
            background: #fff1f2; color: var(--red);
            display: flex; align-items: center; justify-content: center;
            font-size: 16px; flex-shrink: 0; transition: transform 0.3s;
        }
        .faq-item.open .faq-icon { transform: rotate(45deg); background: var(--red); color: #fff; }
        .faq-answer {
            max-height: 0; overflow: hidden;
            transition: max-height 0.4s ease, padding 0.3s ease;
            font-size: 14px; color: var(--text-secondary); line-height: 1.7;
            padding: 0 24px;
        }
        .faq-item.open .faq-answer { max-height: 400px; padding: 0 24px 20px; }

        /* ========== FOOTER ========== */
        footer {
            background: #f8fafc;
            border-top: 1px solid var(--border);
            padding: 60px 5% 30px;
            color: var(--text-secondary);
        }
        .footer-inner {
            max-width: 1100px; margin: 0 auto;
        }
        .footer-top {
            display: grid; grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 48px; margin-bottom: 48px;
        }
        .footer-brand p { font-size: 14px; color: var(--muted); margin: 16px 0 24px; max-width: 300px; }
        .social-links { display: flex; gap: 12px; }
        .social-btn {
            width: 40px; height: 40px; border-radius: 10px;
            background: #ffffff; border: 1px solid var(--border);
            display: flex; align-items: center; justify-content: center;
            font-size: 18px; transition: all 0.2s;
            box-shadow: var(--shadow-sm);
        }
        .social-btn:hover { background: var(--red); border-color: var(--red); color:#fff; transform: translateY(-2px); }
        .footer-col h4 { font-size: 13px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase; color: var(--text); margin-bottom: 20px; }
        .footer-col ul { list-style: none; display: flex; flex-direction: column; gap: 10px; }
        .footer-col ul li a { font-size: 14px; color: var(--muted); font-weight: 500; transition: color 0.2s; }
        .footer-col ul li a:hover { color: var(--red); }
        .footer-bottom {
            border-top: 1px solid var(--border); padding-top: 24px;
            display: flex; justify-content: space-between; align-items: center;
            flex-wrap: wrap; gap: 16px;
        }
        .footer-bottom p { font-size: 13px; color: var(--muted); }
        .footer-badge {
            display: inline-flex; align-items: center; gap: 6px;
            font-size: 12px; color: var(--muted);
        }

        /* ========== AREA COVERAGE ========== */
        .area-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 12px;
        }
        .area-chip {
            background: #ffffff; border: 1px solid var(--border);
            border-radius: 10px; padding: 12px 16px;
            display: flex; align-items: center; gap: 8px;
            font-size: 13px; font-weight: 600; color: var(--text-secondary);
            box-shadow: var(--shadow-sm);
            transition: all 0.2s;
        }
        .area-chip:hover { border-color: rgba(232,35,42,0.4); color: var(--red); transform: translateY(-2px); }
        .area-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--red); flex-shrink: 0; }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 1024px) {
            .hero {
                flex-direction: column;
                min-height: auto;
                padding: 100px 5% 40px;
                align-items: flex-start;
            }
            .hero-content { max-width: 100%; }
            .hero-visual {
                position: relative;
                right: auto; top: auto;
                transform: none;
                width: 100%;
                margin-top: 40px;
                height: 340px;
            }
            /* On tablet/mobile: hide phone frame, show only city backdrop full-width */
            .phone-mockup { display: none; }
            .hero-float-card { display: none; }
            .city-backdrop {
                position: relative;
                right: auto; top: auto; transform: none;
                width: 100%; height: 340px;
                border-radius: 20px;
            }
            .features-grid { grid-template-columns: 1fr; }
            .feature-card.featured { grid-column: span 1; grid-template-columns: 1fr; }
            .footer-top { grid-template-columns: 1fr 1fr; }
            .app-cta-inner { grid-template-columns: 1fr; padding: 36px 24px; }
            .cicalengka-grid { grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); }
        }
        @media (max-width: 768px) {
            .hero {
                padding: 90px 5% 32px;
            }
            .hero-visual { height: 260px; margin-top: 28px; }
            .city-backdrop { height: 260px; border-radius: 16px; }
            .navbar { padding: 12px 16px; gap: 10px; }
            .navbar.scrolled { padding: 10px 16px; }
            .nav-logo { font-size: 18px; gap: 8px; flex-shrink: 0; }
            .nav-logo .logo-img { width: 32px; height: 32px; border-radius: 9px; }
            .nav-links { display: none !important; }
            .nav-mobile { display: flex; align-items: center; flex-shrink: 0; }
            .nav-mobile .nav-cta { padding: 8px 14px; font-size: 13px; font-weight: 700; border-radius: 8px; }
            .stats-inner { grid-template-columns: repeat(2, 1fr); }
            .steps-grid { grid-template-columns: repeat(2, 1fr); }
            .steps-grid::before { display: none; }
            .footer-top { grid-template-columns: 1fr; }
            .footer-bottom { flex-direction: column; text-align: center; }
        }
        @media (max-width: 480px) {
            .hero { padding: 80px 4% 24px; }
            .hero-visual { height: 220px; margin-top: 24px; }
            .city-backdrop { height: 220px; border-radius: 14px; }
            .navbar { padding: 10px 12px; gap: 8px; }
            .nav-logo { font-size: 16px; gap: 6px; }
            .nav-logo .logo-img { width: 28px; height: 28px; border-radius: 8px; }
            .nav-mobile .nav-cta { padding: 7px 11px; font-size: 12px; border-radius: 7px; }
            .stats-inner { grid-template-columns: repeat(2, 1fr); }
            .steps-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 360px) {
            .hero { padding: 75px 4% 20px; }
            .hero-visual { height: 190px; margin-top: 20px; }
            .city-backdrop { height: 190px; border-radius: 12px; }
            .navbar { padding: 8px 10px; gap: 6px; }
            .nav-logo { font-size: 14.5px; gap: 5px; }
            .nav-logo .logo-img { width: 25px; height: 25px; border-radius: 6px; }
            .nav-mobile .nav-cta { padding: 6px 9px; font-size: 11px; }
        }

        /* ========== ANIMATIONS ========== */
        .fade-in {
            opacity: 0; transform: translateY(24px);
            transition: opacity 0.6s cubic-bezier(0.16, 1, 0.3, 1), transform 0.6s cubic-bezier(0.16, 1, 0.3, 1);
            will-change: opacity, transform;
        }
        .fade-in.visible { opacity: 1; transform: translateY(0); }
        .fade-in-delay-1 { transition-delay: 0.1s; }
        .fade-in-delay-2 { transition-delay: 0.2s; }
        .fade-in-delay-3 { transition-delay: 0.3s; }

        /* ========== TOAST / BACK TO TOP ========== */
        .back-top {
            position: fixed; bottom: 30px; right: 30px; z-index: 999;
            width: 48px; height: 48px; border-radius: 50%;
            background: var(--red); color: #fff; border: none; cursor: pointer;
            font-size: 20px; display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 20px var(--red-glow);
            opacity: 0; transform: scale(0.8); transition: all 0.3s;
        }
        .back-top.show { opacity: 1; transform: scale(1); }
        .back-top:hover { transform: scale(1.1); }
    </style>
</head>
<body>

<!-- ========== NAVBAR ========== -->
<nav class="navbar" id="navbar">
    <a href="#home" class="nav-logo">
        <img src="<?= htmlspecialchars($publicUrl ?? '') ?>/assets/images/app_logo.png" alt="CicalengkaGO Logo" class="logo-img">
        Cicalengka<span>GO</span>
    </a>
    <div class="nav-links">
        <a href="#layanan">Layanan</a>
        <a href="#cicalengka">Pesona Cicalengka</a>
        <a href="#fitur">Fitur</a>
        <a href="#cara-kerja">Cara Pesan</a>
        <a href="#pembayaran">Pembayaran</a>
        <a href="#faq">FAQ</a>
        <a href="https://market.cicago.store" target="_blank" rel="noopener" class="nav-cta" style="background:var(--red);">🚀 Buka Aplikasi</a>
    </div>
    <div class="nav-mobile">
        <a href="https://market.cicago.store" target="_blank" rel="noopener" class="nav-cta">Buka Aplikasi</a>
    </div>
</nav>

<!-- ========== HERO ========== -->
<section class="hero" id="home">
    <div class="hero-bg"></div>
    <div class="hero-grid"></div>

    <div class="hero-content">
        <div class="hero-badge">
            <span class="dot"></span>
            🏡 Super App Pertama di Cicalengka
        </div>
        <h1>
            Semua Kebutuhan<br>
            Kamu Ada di<br>
            <span class="highlight">CicalengkaGO</span>
        </h1>
        <p>
            Dari pesan makanan, belanja sembako, obat-obatan, olshop lokal, hingga kirim paket —
            semuanya dalam satu web app. Cepat, mudah, langsung buka di browser tanpa install!
        </p>
        <div class="hero-actions">
            <a href="https://market.cicago.store" target="_blank" rel="noopener" class="btn-primary">
                🚀 Buka Aplikasi Sekarang
            </a>
            <a href="#layanan" class="btn-secondary">
                Lihat Layanan →
            </a>
        </div>
        <div style="margin-top:16px; font-size:13px; color:var(--muted); display:flex; align-items:center; gap:8px;">
            <span style="color:#22c55e;">●</span> Langsung dibuka di browser HP & PC — Tanpa perlu download atau instal!
        </div>
    </div>

    <!-- Hero Visual: Phone + City Photo -->
    <div class="hero-visual">

        <!-- City backdrop photo -->
        <div class="city-backdrop">
            <img src="<?= htmlspecialchars($publicUrl ?? '') ?>/assets/images/cicalengka/alun_alun_cicalengka.jpg" alt="Alun-alun Cicalengka malam hari" loading="lazy">
            <div class="city-label">
                <span class="cl-dot"></span>
                📍 Cicalengka, Jawa Barat
            </div>
        </div>

        <!-- Floating stat card top-left -->
        <div class="hero-float-card float-top-left">
            <span class="fc-icon">🏪</span>
            <div>
                <div class="fc-val"><?= (int)($stats['stores'] ?? 0) ?>+</div>
                <div class="fc-sub">Toko Mitra</div>
            </div>
        </div>

        <!-- Floating stat card bottom-right -->
        <div class="hero-float-card float-bottom-right">
            <span class="fc-icon">🛵</span>
            <div>
                <div class="fc-val"><?= (int)($stats['drivers'] ?? 0) ?>+</div>
                <div class="fc-sub">Driver Siap</div>
            </div>
        </div>

        <!-- Phone Mockup -->
        <div class="phone-mockup">
            <div class="phone-screen">
                <div class="phone-header">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <img src="<?= htmlspecialchars($publicUrl ?? '') ?>/assets/images/app_logo.png" alt="CicalengkaGO" style="width:32px; height:32px; border-radius:9px; object-fit:cover; border:1px solid rgba(255,255,255,0.2); box-shadow:0 2px 8px rgba(0,0,0,0.4);">
                        <div>
                            <div class="phone-header-title">Cicalengka<span style="color:rgba(255,200,200,1);">GO</span></div>
                            <div class="phone-header-sub">Selamat datang! 👋</div>
                        </div>
                    </div>
                    <span style="font-size:20px;">🔔</span>
                </div>
                <div class="phone-greeting">
                    <strong>Mau pesan apa hari ini?</strong>
                    Cicalengka & sekitarnya
                </div>
                <div class="phone-services">
                    <div class="phone-svc"><span>🍜</span>Makanan</div>
                    <div class="phone-svc"><span>🛒</span>Sembako</div>
                    <div class="phone-svc"><span>💊</span>Farmasi</div>
                    <div class="phone-svc"><span>🛍️</span>Olshop</div>
                    <div class="phone-svc"><span>📦</span>Paket</div>
                    <div class="phone-svc"><span>💳</span>Dompet</div>
                    <div class="phone-svc"><span>⭐</span>Promo</div>
                    <div class="phone-svc"><span>📍</span>Lacak</div>
                </div>
                <!-- City photo inside phone -->
                <div class="phone-city-photo">
                    <img src="<?= htmlspecialchars($publicUrl ?? '') ?>/assets/images/cicalengka/alun_alun_cicalengka.jpg" alt="Alun-alun Cicalengka" loading="lazy">
                    <div class="phone-city-label">
                        <span class="tag">🏙️ Kota</span>
                        Alun-alun Cicalengka
                    </div>
                </div>
                <a href="https://market.cicago.store" target="_blank" rel="noopener" style="display:block;margin:0 14px 14px;background:var(--red);color:#fff;text-align:center;padding:10px;border-radius:10px;font-size:11.5px;font-weight:700;text-decoration:none;box-shadow:0 2px 10px rgba(232,35,42,0.4);">🚀 Buka market.cicago.store</a>
            </div>
        </div>

    </div>
</section>

<!-- ========== STATS BAR ========== -->
<div class="stats-bar">
    <div class="stats-inner">
        <?php
        $stores  = (int)($stats['stores']  ?? 0);
        $orders  = (int)($stats['orders']  ?? 0);
        $users   = (int)($stats['users']   ?? 0);
        $drivers = (int)($stats['drivers'] ?? 0);
        ?>
        <div class="stat-item fade-in">
            <div class="stat-num" data-target="<?= $stores ?>" data-suffix="+">
                <?= $stores ?><span class="stat-suffix">+</span>
            </div>
            <div class="stat-label">🏪 Toko Mitra Aktif</div>
        </div>
        <div class="stat-item fade-in fade-in-delay-1">
            <div class="stat-num" data-target="<?= $orders ?>" data-suffix="+">
                <?php if ($orders >= 1000): ?>
                    <?= number_format($orders/1000, 1) ?><span class="stat-suffix">K+</span>
                <?php else: ?>
                    <?= $orders ?><span class="stat-suffix">+</span>
                <?php endif; ?>
            </div>
            <div class="stat-label">📦 Pesanan Terselesaikan</div>
        </div>
        <div class="stat-item fade-in fade-in-delay-2">
            <div class="stat-num" data-target="<?= $users ?>" data-suffix="+">
                <?php if ($users >= 1000): ?>
                    <?= number_format($users/1000, 1) ?><span class="stat-suffix">K+</span>
                <?php else: ?>
                    <?= $users ?><span class="stat-suffix">+</span>
                <?php endif; ?>
            </div>
            <div class="stat-label">👥 Pengguna Terdaftar</div>
        </div>
        <div class="stat-item fade-in fade-in-delay-3">
            <div class="stat-num" data-target="<?= $drivers ?>" data-suffix="+">
                <?= $drivers ?><span class="stat-suffix">+</span>
            </div>
            <div class="stat-label">🛵 Driver Aktif</div>
        </div>
    </div>
</div>

<!-- ========== LAYANAN ========== -->
<section id="layanan" class="services-bg">
    <div class="max-w">
        <div class="section-header fade-in">
            <div class="section-label">⚡ Layanan Kami</div>
            <h2 class="section-title">5 Layanan Lengkap<br>Dalam 1 Aplikasi</h2>
            <p class="section-subtitle">Apapun yang kamu butuhkan, CicalengkaGO siap mengantarkan ke pintu rumahmu.</p>
        </div>
        <div class="services-grid">
            <div class="service-card svc-food fade-in" style="--svc-color: #ef4444">
                <div class="svc-icon">🍜</div>
                <div class="svc-name">Kuliner & Makanan</div>
                <div class="svc-desc">Pesan langsung dari warung, restoran, dan kedai makanan favoritmu di Cicalengka. Diantar panas sampai rumah!</div>
                <div class="svc-badge">Food Delivery</div>
            </div>
            <div class="service-card svc-grocery fade-in fade-in-delay-1" style="--svc-color: #10b981">
                <div class="svc-icon">🛒</div>
                <div class="svc-name">Sembako & Mart</div>
                <div class="svc-desc">Belanja kebutuhan sehari-hari dari toko kelontong dan minimarket terdekat. Hemat waktu tanpa keluar rumah.</div>
                <div class="svc-badge">Grocery</div>
            </div>
            <div class="service-card svc-pharma fade-in fade-in-delay-2" style="--svc-color: #06b6d4">
                <div class="svc-icon">💊</div>
                <div class="svc-name">Farmasi & Apotek</div>
                <div class="svc-desc">Beli obat, vitamin, dan produk kesehatan dari apotek terpercaya. Resep dokter? Kami bantu proses dengan mudah.</div>
                <div class="svc-badge">Pharmacy</div>
            </div>
            <div class="service-card svc-shop fade-in fade-in-delay-1" style="--svc-color: #8b5cf6">
                <div class="svc-icon">🛍️</div>
                <div class="svc-name">Olshop Cicalengka</div>
                <div class="svc-desc">Dukung UMKM lokal! Belanja produk fashion, elektronik, dan berbagai barang dari toko online warga Cicalengka.</div>
                <div class="svc-badge">E-Commerce</div>
            </div>
            <div class="service-card svc-parcel fade-in fade-in-delay-2" style="--svc-color: #f59e0b">
                <div class="svc-icon">📦</div>
                <div class="svc-name">Kirim Paket</div>
                <div class="svc-desc">Kirim dokumen, barang, atau bingkisan ke mana saja di area Cicalengka. Cepat, aman, dan terlacak real-time.</div>
                <div class="svc-badge">Parcel Delivery</div>
            </div>
        </div>
    </div>
</section>

<!-- ========== PESONA CICALENGKA ========== -->
<section id="cicalengka" class="cicalengka-section">
    <div class="max-w">
        <div class="section-header fade-in" style="text-align:center;">
            <div class="section-label" style="justify-content:center;">🌄 Dari Cicalengka, Untuk Warga Cicalengka</div>
            <h2 class="section-title">Kenali Lebih Dekat Cicalengka<br>Bersama Ekosistem CicalengkaGO</h2>
            <p class="section-subtitle" style="margin:0 auto; max-width:680px;">
                Dari ikon stasiun modern, bukit hijau yang asri, hingga air terjun legendaris — CicalengkaGO hadir menghubungkan setiap warga, pedagang, dan sudut indah di Cicalengka.
            </p>
        </div>

        <div class="cicalengka-grid">
            <!-- Card 1: Stasiun Cicalengka -->
            <div class="cicalengka-card fade-in">
                <img src="<?= htmlspecialchars($publicUrl ?? '') ?>/assets/images/cicalengka/stasiun_cicalengka.jpg" alt="Stasiun Cicalengka Baru" loading="lazy">
                <div class="cicalengka-overlay">
                    <span class="cicalengka-tag">Transportasi & Ikon Kota</span>
                    <div class="cicalengka-title">Stasiun Cicalengka Modern</div>
                    <div class="cicalengka-desc">Bangunan megah kebanggaan Cicalengka, gerbang mobilitas Commuter Line Bandung Raya dan Garut.</div>
                </div>
            </div>


            <!-- Card 4: Bukit Teletubbies -->
            <div class="cicalengka-card fade-in">
                <img src="<?= htmlspecialchars($publicUrl ?? '') ?>/assets/images/cicalengka/bukit_teletubbies.jpg" alt="Bukit Teletubbies Cicalengka" loading="lazy">
                <div class="cicalengka-overlay">
                    <span class="cicalengka-tag" style="background:#10b981;">Dataran Tinggi Asri</span>
                    <div class="cicalengka-title">Bukit Teletubbies Cicalengka</div>
                    <div class="cicalengka-desc">Hamparan sabana hijau bergelombang di atas perbukitan Cicalengka, tempat terbaik menikmati sunset.</div>
                </div>
            </div>

            <!-- Card 5: Gunung Geulis & Kerenceng -->
            <div class="cicalengka-card fade-in fade-in-delay-1">
                <img src="<?= htmlspecialchars($publicUrl ?? '') ?>/assets/images/cicalengka/gunung_geulis.jpg" alt="Lanskap Gunung Geulis Cicalengka" loading="lazy">
                <div class="cicalengka-overlay">
                    <span class="cicalengka-tag" style="background:#f59e0b;">Panorama Pegunungan</span>
                    <div class="cicalengka-title">Gunung Geulis & Kerenceng</div>
                    <div class="cicalengka-desc">Latar pegunungan megah nan menawan yang memayungi kawasan Cicalengka dan sekitarnya.</div>
                </div>
            </div>


        </div>
    </div>
</section>

<!-- ========== FITUR UNGGULAN ========== -->
<section id="fitur">
    <div class="max-w">
        <div class="section-header fade-in">
            <div class="section-label">💎 Fitur Unggulan</div>
            <h2 class="section-title">Teknologi Canggih<br>untuk Kenyamanan Kamu</h2>
            <p class="section-subtitle">CicalengkaGO dibangun dengan teknologi terkini untuk pengalaman berbelanja terbaik.</p>
        </div>
        <div class="features-grid">
            <!-- Featured: GPS Tracking -->
            <div class="feature-card featured fade-in">
                <div>
                    <div class="feat-icon">📍</div>
                    <h3>Lacak Pesanan Real-Time</h3>
                    <p>Pantau posisi driver secara langsung di peta. Sistem GPS kami memperbarui lokasi setiap detik sehingga kamu tahu persis di mana pesananmu berada.</p>
                </div>
                <div class="feat-visual">
                    <div class="feat-track-item">
                        <div class="track-dot"></div>
                        <div><div class="track-label"><strong>Pesanan Dikonfirmasi</strong>Toko sedang menyiapkan</div></div>
                    </div>
                    <div style="width:2px;height:20px;background:var(--border);margin-left:4px;"></div>
                    <div class="feat-track-item">
                        <div class="track-dot" style="background:#10b981;box-shadow:0 0 8px #10b981aa;"></div>
                        <div><div class="track-label"><strong>Driver Menjemput</strong>500m dari toko</div></div>
                    </div>
                    <div style="width:2px;height:20px;background:var(--border);margin-left:4px;"></div>
                    <div class="feat-track-item">
                        <div class="track-dot" style="background:#f59e0b;box-shadow:0 0 8px #f59e0baa;"></div>
                        <div><div class="track-label"><strong>Dalam Perjalanan</strong>Estimasi 8 menit lagi</div></div>
                    </div>
                </div>
            </div>
            <!-- Voice Call -->
            <div class="feature-card fade-in">
                <div class="feat-icon">📞</div>
                <h3>Telepon In-App Gratis</h3>
                <p>Hubungi driver atau toko langsung dari aplikasi menggunakan teknologi WebRTC — tanpa biaya pulsa sama sekali.</p>
            </div>
            <!-- CicalengkaPay -->
            <div class="feature-card fade-in fade-in-delay-1">
                <div class="feat-icon">💳</div>
                <h3>CicalengkaPay Wallet</h3>
                <p>Dompet digital terintegrasi untuk semua transaksi. Top up sekali, bayar semua layanan lebih cepat dan praktis.</p>
            </div>
            <!-- OTP WhatsApp -->
            <div class="feature-card fade-in">
                <div class="feat-icon">💬</div>
                <h3>Login via WhatsApp OTP</h3>
                <p>Tidak perlu ingat password! Login cukup dengan kode OTP yang dikirim langsung ke WhatsApp kamu dalam hitungan detik.</p>
            </div>
            <!-- Batch Delivery -->
            <div class="feature-card fade-in fade-in-delay-1">
                <div class="feat-icon">⚡</div>
                <h3>Pengiriman Lebih Cepat</h3>
                <p>Sistem batch delivery pintar kami memungkinkan satu driver mengambil beberapa pesanan sekaligus — lebih efisien, lebih cepat sampai!</p>
            </div>
        </div>
    </div>
</section>

<!-- ========== CARA KERJA ========== -->
<section id="cara-kerja" class="how-bg">
    <div class="max-w">
        <div class="section-header fade-in" style="text-align:center;">
            <div class="section-label" style="justify-content:center;">🔄 Cara Kerja</div>
            <h2 class="section-title">Pesan dalam 4 Langkah Mudah</h2>
            <p class="section-subtitle" style="margin:0 auto;">Dari pilih produk hingga pesanan sampai, semua bisa dilakukan dalam hitungan menit.</p>
        </div>
        <div class="steps-grid">
            <div class="step-card fade-in">
                <div class="step-num">1</div>
                <h3>Buka Web App</h3>
                <p>Kunjungi <a href="https://market.cicago.store" target="_blank" rel="noopener" style="color:#b45309;font-weight:700;text-decoration:underline;text-underline-offset:2px;">market.cicago.store</a> langsung di browser HP Anda tanpa perlu download.</p>
            </div>
            <div class="step-card fade-in fade-in-delay-1">
                <div class="step-num">2</div>
                <h3>Pilih Layanan</h3>
                <p>Pilih dari 5 modul layanan: Makanan, Sembako, Farmasi, Olshop, atau Kirim Paket.</p>
            </div>
            <div class="step-card fade-in fade-in-delay-2">
                <div class="step-num">3</div>
                <h3>Bayar & Konfirmasi</h3>
                <p>Bayar via COD, QRIS, transfer bank, e-wallet, atau CicalengkaPay. Mudah dan aman.</p>
            </div>
            <div class="step-card fade-in fade-in-delay-3">
                <div class="step-num">4</div>
                <h3>Lacak & Terima</h3>
                <p>Pantau driver di peta secara real-time. Pesananmu sampai dalam hitungan menit!</p>
            </div>
        </div>
    </div>
</section>


<!-- ========== AREA LAYANAN ========== -->
<section id="area" style="background:#f8fafc; padding:70px 5%; border-top:1px solid var(--border); border-bottom:1px solid var(--border);">
    <div class="max-w">
        <div class="section-header fade-in">
            <div class="section-label">📍 Area Layanan</div>
            <h2 class="section-title">Melayani Cicalengka<br>& Sekitarnya</h2>
            <p class="section-subtitle">Saat ini kami beroperasi di wilayah Cicalengka dan terus berkembang ke kecamatan sekitarnya.</p>
        </div>
        <div class="area-grid">
            <?php
            $areas = [
                'Cicalengka Kota', 'Cicalengka Wetan', 'Cicalengka Kulon',
                'Nagreg', 'Cikancung', 'Haurpugur', 'Tanjungwangi',
                'Margaasih', 'Panenjoan', 'Dampit', 'Cihanyir', 'Neglasari'
            ];
            foreach ($areas as $area): ?>
                <div class="area-chip fade-in">
                    <span class="area-dot"></span>
                    <?= htmlspecialchars($area) ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ========== BUKA APLIKASI WEB APP ========== -->
<section id="buka-aplikasi" class="app-cta-section">
    <div class="max-w">
        <div class="app-cta-inner fade-in">
            <div>
                <div class="section-label" style="color:rgba(255,255,255,0.92); background:rgba(255,255,255,0.18); border-color:rgba(255,255,255,0.35);">⚡ Akses Instan Tanpa Install</div>
                <div class="app-cta-title">
                    Langsung Buka CicalengkaGO<br>
                    di HP & Laptop Kamu!<br>
                    <span style="color: #fde68a;">Tanpa Perlu Download</span>
                </div>
                <p class="app-cta-sub">
                    Hemat kuota dan memori ponsel! CicalengkaGO hadir sebagai Progressive Web App modern.
                    Cukup buka alamat webnya, Anda langsung bisa berbelanja makanan, sembako, farmasi, dan kirim paket secepat aplikasi biasa.
                </p>

                <div class="app-pills">
                    <div class="app-pill">🚀 <span>Buka Instan via Browser</span></div>
                    <div class="app-pill">💾 <span>Hemat 100% Memori HP</span></div>
                    <div class="app-pill">📌 <span>Bisa 'Add to Home Screen'</span></div>
                </div>

                <div style="display:flex; gap:16px; flex-wrap:wrap; align-items:center;">
                    <a href="https://market.cicago.store" target="_blank" rel="noopener" class="app-cta-btn-main">
                        🚀 Buka Aplikasi Sekarang (market.cicago.store) ➔
                    </a>
                </div>

                <div style="margin-top:20px; font-size:13px; color:rgba(255,255,255,0.82); font-weight:500;">
                    ✅ Aman & Resmi · ✅ Bebas Biaya Langganan · ✅ Terhubung Langsung ke Merchant Lokal
                </div>
            </div>

            <div class="app-browser-card fade-in fade-in-delay-1">
                <div class="browser-bar">
                    <div class="browser-dots">
                        <div class="browser-dot" style="background:#ef4444;"></div>
                        <div class="browser-dot" style="background:#f59e0b;"></div>
                        <div class="browser-dot" style="background:#10b981;"></div>
                    </div>
                    <div class="browser-address">
                        <span>🔒</span> https://market.cicago.store
                    </div>
                </div>
                <div class="browser-body">
                    <img src="<?= htmlspecialchars($publicUrl ?? '') ?>/assets/images/app_logo.png" alt="CicalengkaGO" style="width:64px; height:64px; border-radius:16px; margin:0 auto; box-shadow:0 6px 24px rgba(232,35,42,0.55);">
                    <div>
                        <div style="font-size:18px; font-weight:800; color:#ffffff;">Cicalengka<span style="color:#ff6b6b;">GO</span> Web App</div>
                        <div style="font-size:13px; color:#a1a1aa; margin-top:4px;">Platform Belanja &amp; On-Demand Cicalengka</div>
                    </div>
                    <div style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.12); border-radius:12px; padding:14px; text-align:left; font-size:12.5px; color:#d4d4d8; line-height:1.6;">
                        💡 <strong style="color:#ffffff;">Tips Praktis:</strong> Buka di Google Chrome atau Safari di HP, lalu ketuk menu opsi (titik tiga atau tombol Share) dan pilih <strong style="color:#ffffff;">"Tambahkan ke Layar Utama" (Add to Home Screen)</strong> untuk pengalaman seperti aplikasi native!
                    </div>
                    <a href="https://market.cicago.store" target="_blank" rel="noopener" class="btn-primary" style="padding:12px 20px; font-size:14px; width:100%; text-align:center;">
                        Mulai Belanja Sekarang ➔
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ========== FAQ ========== -->
<section id="faq">
    <div class="max-w">
        <div class="section-header fade-in" style="text-align:center;">
            <div class="section-label" style="justify-content:center;">❓ FAQ</div>
            <h2 class="section-title">Pertanyaan yang<br>Sering Ditanyakan</h2>
        </div>
        <div style="max-width:760px; margin:0 auto;">
            <div class="faq-list">
                <?php
                $faqs = [
                    [
                        'q' => 'Apa itu CicalengkaGO?',
                        'a' => 'CicalengkaGO adalah platform super app on-demand pertama di Cicalengka, Kabupaten Bandung. Kami menyediakan layanan pesan antar makanan, belanja sembako, farmasi, olshop lokal, dan kirim paket dalam satu aplikasi. Misi kami adalah memberdayakan UMKM lokal dan memudahkan kehidupan sehari-hari warga Cicalengka.'
                    ],
                    [
                        'q' => 'Bagaimana cara mendaftar dan mulai memesan?',
                        'a' => 'Tidak perlu unduh aplikasi dari Play Store atau App Store! Cukup buka website https://market.cicago.store di browser HP Anda, masukkan nomor WhatsApp aktif, dan verifikasi kode OTP. Anda langsung bisa memilih toko mitra favorit dan melakukan pemesanan.'
                    ],
                    [
                        'q' => 'Berapa biaya pengiriman?',
                        'a' => 'Biaya pengiriman mulai dari Rp 5.000 dengan tarif Rp 2.500 per kilometer. Gratis ongkir untuk pembelian di atas Rp 100.000! Biaya pengiriman akan ditampilkan transparan sebelum kamu konfirmasi pesanan.'
                    ],
                    [
                        'q' => 'Metode pembayaran apa saja yang tersedia?',
                        'a' => 'Kami menerima berbagai metode pembayaran: COD (bayar tunai ke driver), QRIS, transfer bank (BCA, BRI, Mandiri), e-wallet (DANA, GoPay), dan CicalengkaPay (dompet digital internal platform). Semua transaksi diproses dengan aman melalui gateway DOKU yang terpercaya.'
                    ],
                    [
                        'q' => 'Bagaimana cara melacak pesanan saya?',
                        'a' => 'Setelah pesanan dikonfirmasi oleh toko dan driver menjemput, kamu bisa memantau posisi driver secara real-time langsung di peta dalam aplikasi. Sistem GPS kami memperbarui lokasi setiap detik. Kamu juga bisa menelepon driver langsung dari aplikasi tanpa biaya!'
                    ],
                    [
                        'q' => 'Saya ingin mendaftarkan toko/warung saya. Caranya bagaimana?',
                        'a' => 'Daftarkan tokomu melalui menu "Daftar Mitra" di aplikasi atau kunjungi halaman pendaftaran merchant di website kami. Siapkan foto toko, logo, KTP pemilik, dan titik lokasi toko. Tim kami akan mereview dalam 1x24 jam. Tidak ada biaya pendaftaran — gratis!'
                    ],
                    [
                        'q' => 'Bagaimana cara menjadi driver CicalengkaGO?',
                        'a' => 'Untuk menjadi driver, kamu perlu memiliki kendaraan (motor), SIM C aktif, dan KTP. Daftarkan dirimu melalui aplikasi di menu "Daftar Driver". Setelah verifikasi dokumen disetujui, kamu langsung bisa mulai menerima order. Komisi driver sebesar 80% dari biaya pengiriman!'
                    ],
                    [
                        'q' => 'Apakah CicalengkaGO beroperasi 24 jam?',
                        'a' => 'Platform kami beroperasi mengikuti jam operasional masing-masing toko mitra. Sebagian besar toko buka mulai pukul 07.00 hingga 22.00 WIB. Ketersediaan driver juga dapat bervariasi. Kamu bisa melihat status buka/tutup toko langsung di aplikasi.'
                    ],
                    [
                        'q' => 'Bagaimana keamanan pembayaran di CicalengkaGO?',
                        'a' => 'Keamanan transaksi adalah prioritas kami. Semua pembayaran online diproses melalui DOKU yang telah bersertifikat PCI-DSS. Untuk keamanan pengiriman, setiap pesanan dilengkapi dengan OTP konfirmasi 6 digit yang harus dimasukkan driver saat serah terima paket kepada pelanggan.'
                    ],
                    [
                        'q' => 'Apa itu CicalengkaPay?',
                        'a' => 'CicalengkaPay adalah dompet digital internal CicalengkaGO. Kamu bisa top up saldo melalui transfer bank atau QRIS, lalu gunakan saldo tersebut untuk bayar semua layanan dengan lebih cepat. Saldo CicalengkaPay juga bisa ditransfer ke sesama pengguna CicalengkaGO tanpa biaya admin.'
                    ],
                    [
                        'q' => 'Bagaimana jika pesanan bermasalah atau tidak sesuai?',
                        'a' => 'Jika ada masalah dengan pesanan, kamu bisa menghubungi toko atau driver langsung melalui fitur chat dan telepon in-app. Untuk komplain lebih lanjut, hubungi tim customer service kami melalui WhatsApp di nomor yang tertera di aplikasi. Kami berkomitmen menyelesaikan setiap keluhan dalam 24 jam.'
                    ],
                ];
                foreach ($faqs as $i => $faq): ?>
                    <div class="faq-item fade-in" id="faq-<?= $i ?>">
                        <div class="faq-question" onclick="toggleFaq(<?= $i ?>)">
                            <span><?= htmlspecialchars($faq['q']) ?></span>
                            <div class="faq-icon">+</div>
                        </div>
                        <div class="faq-answer"><?= htmlspecialchars($faq['a']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<!-- ========== FOOTER ========== -->
<footer>
    <div class="footer-inner">
        <div class="footer-top">
            <div class="footer-brand">
                <a href="#home" class="nav-logo" style="margin-bottom:0;">
                    <img src="<?= htmlspecialchars($publicUrl ?? '') ?>/assets/images/app_logo.png" alt="CicalengkaGO Logo" class="logo-img">
                    Cicalengka<span>GO</span>
                </a>
                <p>Platform super app on-demand pertama di Cicalengka. Menghubungkan pelanggan, pedagang lokal, dan driver dalam satu ekosistem digital yang mudah dan terpercaya.</p>
                <div class="social-links">
                    <a href="#" class="social-btn" title="Instagram">📸</a>
                    <a href="#" class="social-btn" title="Facebook">📘</a>
                    <a href="#" class="social-btn" title="WhatsApp">💬</a>
                    <a href="#" class="social-btn" title="YouTube">▶️</a>
                </div>
            </div>
            <div class="footer-col">
                <h4>Layanan</h4>
                <ul>
                    <li><a href="#layanan">🍜 Kuliner & Makanan</a></li>
                    <li><a href="#layanan">🛒 Sembako & Mart</a></li>
                    <li><a href="#layanan">💊 Farmasi & Apotek</a></li>
                    <li><a href="#layanan">🛍️ Olshop Cicalengka</a></li>
                    <li><a href="#layanan">📦 Kirim Paket</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Bergabung</h4>
                <ul>
                    <li><a href="https://market.cicago.store" target="_blank" rel="noopener">🚀 Buka Web App (market.cicago.store)</a></li>
                    <li><a href="https://market.cicago.store" target="_blank" rel="noopener">Daftar sebagai Pelanggan</a></li>
                    <li><a href="#">Daftar Toko Mitra</a></li>
                    <li><a href="#">Daftar sebagai Driver</a></li>
                    <li><a href="/vendor">Portal Vendor</a></li>
                    <li><a href="/admin">Admin Panel</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Info</h4>
                <ul>
                    <li><a href="#faq">FAQ</a></li>
                    <li><a href="#cara-kerja">Cara Kerja</a></li>
                    <li><a href="#pembayaran">Metode Pembayaran</a></li>
                    <li><a href="#">Syarat & Ketentuan</a></li>
                    <li><a href="#">Kebijakan Privasi</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>© <?= date('Y') ?> CicalengkaGO. Hak cipta dilindungi.</p>
            <div class="footer-badge">
                🔒 SSL Secured &nbsp;•&nbsp; 💳 DOKU Certified &nbsp;•&nbsp; 🇮🇩 Made in Cicalengka
            </div>
        </div>
    </div>
</footer>

<!-- Back to Top -->
<button class="back-top" id="backTop" onclick="window.scrollTo({top:0,behavior:'smooth'})" aria-label="Kembali ke atas">↑</button>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Intersection Observer for Scroll Fade-in Animations
    const fadeElements = document.querySelectorAll('.fade-in');
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries, obs) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    obs.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

        fadeElements.forEach(el => observer.observe(el));
    } else {
        fadeElements.forEach(el => el.classList.add('visible'));
    }

    // 2. Animated Stats Numbers Count-Up
    const statsContainer = document.querySelector('.stats-inner');
    let statsAnimated = false;
    if (statsContainer && 'IntersectionObserver' in window) {
        const statsObserver = new IntersectionObserver((entries, obs) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && !statsAnimated) {
                    statsAnimated = true;
                    obs.unobserve(entry.target);
                    animateCounters();
                }
            });
        }, { threshold: 0.3 });
        statsObserver.observe(statsContainer);
    }

    function animateCounters() {
        document.querySelectorAll('.stat-num').forEach(numEl => {
            const rawText = numEl.textContent.trim();
            const isK = rawText.includes('K');
            const targetVal = parseFloat(rawText.replace(/[^0-9.]/g, '')) || 0;
            const suffix = isK ? 'K+' : (rawText.includes('+') ? '+' : '');
            const duration = 1600;
            const startTime = performance.now();

            function updateCount(currentTime) {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                // Ease out exponential curve
                const easeProgress = progress === 1 ? 1 : 1 - Math.pow(2, -10 * progress);
                const currentVal = easeProgress * targetVal;

                if (isK) {
                    numEl.innerHTML = currentVal.toFixed(1) + '<span class="stat-suffix">' + suffix + '</span>';
                } else if (Number.isInteger(targetVal)) {
                    numEl.innerHTML = Math.round(currentVal) + '<span class="stat-suffix">' + suffix + '</span>';
                } else {
                    numEl.innerHTML = currentVal.toFixed(1) + '<span class="stat-suffix">' + suffix + '</span>';
                }

                if (progress < 1) {
                    requestAnimationFrame(updateCount);
                } else {
                    numEl.innerHTML = (isK ? targetVal.toFixed(1) : targetVal) + '<span class="stat-suffix">' + suffix + '</span>';
                }
            }
            requestAnimationFrame(updateCount);
        });
    }

    // 3. Accordion FAQ
    window.toggleFaq = function(id) {
        const item = document.getElementById('faq-' + id);
        if (!item) return;
        const isOpen = item.classList.contains('open');

        // Close other items
        document.querySelectorAll('.faq-item.open').forEach(openItem => {
            if (openItem !== item) {
                openItem.classList.remove('open');
            }
        });

        // Toggle current item
        if (isOpen) {
            item.classList.remove('open');
        } else {
            item.classList.add('open');
        }
    };

    // 4. Navbar Scroll Effect & Back to Top Button
    const navbar = document.getElementById('navbar');
    const backTop = document.getElementById('backTop');
    window.addEventListener('scroll', () => {
        const scrolled = window.scrollY > 40;
        if (navbar) navbar.classList.toggle('scrolled', scrolled);
        if (backTop) backTop.classList.toggle('show', window.scrollY > 350);
    }, { passive: true });

    // 5. Smooth Scroll for Anchor Links with Header Offset
    document.querySelectorAll('a[href^="#"]').forEach(a => {
        a.addEventListener('click', e => {
            const targetId = a.getAttribute('href');
            if (targetId === '#' || targetId === '') return;
            const targetEl = document.querySelector(targetId);
            if (targetEl) {
                e.preventDefault();
                const offset = 75;
                const top = targetEl.getBoundingClientRect().top + window.scrollY - offset;
                window.scrollTo({ top, behavior: 'smooth' });
            }
        });
    });
});
</script>
</body>
</html>

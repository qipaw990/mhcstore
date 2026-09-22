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
    <meta property="og:description" content="Pesan makanan, belanja, farmasi, dan kirim paket di Cicalengka. Unduh sekarang!">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://cicago.store">
    <meta name="theme-color" content="#e8232a">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        /* ========== RESET & BASE ========== */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --red:      #e8232a;
            --red-dark: #b91c22;
            --red-glow: rgba(232,35,42,0.25);
            --orange:   #f59e0b;
            --dark:     #0a0a0f;
            --dark2:    #111118;
            --dark3:    #1a1a26;
            --card:     #16161f;
            --border:   rgba(255,255,255,0.07);
            --text:     #f0f0f5;
            --muted:    #8888a0;
            --radius:   18px;
            --font:     'Plus Jakarta Sans', system-ui, sans-serif;
        }
        html { scroll-behavior: smooth; }
        body {
            font-family: var(--font);
            background: var(--dark);
            color: var(--text);
            overflow-x: hidden;
            line-height: 1.6;
        }
        a { text-decoration: none; color: inherit; }
        img { max-width: 100%; height: auto; }

        /* ========== SCROLLBAR ========== */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: var(--dark2); }
        ::-webkit-scrollbar-thumb { background: var(--red); border-radius: 3px; }

        /* ========== NAVBAR ========== */
        .navbar {
            position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
            display: flex; align-items: center; justify-content: space-between;
            padding: 18px 5%;
            background: rgba(10,10,15,0.8);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border);
            transition: all 0.3s;
        }
        .navbar.scrolled { padding: 12px 5%; box-shadow: 0 4px 40px rgba(0,0,0,0.4); }
        .nav-logo {
            display: flex; align-items: center; gap: 10px;
            font-size: 22px; font-weight: 800; letter-spacing: -0.5px;
        }
        .nav-logo .logo-icon {
            width: 38px; height: 38px; background: var(--red);
            border-radius: 10px; display: flex; align-items: center; justify-content: center;
            font-size: 20px; box-shadow: 0 0 16px var(--red-glow);
        }
        .nav-logo span { color: var(--red); }
        .nav-links { display: flex; align-items: center; gap: 32px; }
        .nav-links a {
            font-size: 14px; font-weight: 500; color: var(--muted);
            transition: color 0.2s;
        }
        .nav-links a:hover { color: var(--text); }
        .nav-cta {
            background: var(--red); color: #fff;
            padding: 10px 22px; border-radius: 10px;
            font-size: 14px; font-weight: 600;
            box-shadow: 0 0 20px var(--red-glow);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .nav-cta:hover { transform: translateY(-2px); box-shadow: 0 4px 30px var(--red-glow); }
        .nav-mobile { display: none; }

        /* ========== HERO ========== */
        .hero {
            min-height: 100vh;
            display: flex; align-items: center;
            position: relative; overflow: hidden;
            padding: 120px 5% 80px;
        }
        .hero-bg {
            position: absolute; inset: 0; z-index: 0;
            background:
                radial-gradient(ellipse 70% 60% at 60% 40%, rgba(232,35,42,0.12) 0%, transparent 70%),
                radial-gradient(ellipse 40% 40% at 10% 80%, rgba(245,158,11,0.06) 0%, transparent 60%),
                linear-gradient(180deg, var(--dark) 0%, var(--dark2) 100%);
        }
        /* Animated grid */
        .hero-grid {
            position: absolute; inset: 0; z-index: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
            background-size: 60px 60px;
            mask-image: radial-gradient(ellipse 80% 80% at 50% 50%, black 20%, transparent 80%);
        }
        .hero-content { position: relative; z-index: 1; max-width: 620px; }
        .hero-badge {
            display: inline-flex; align-items: center; gap: 8px;
            background: rgba(232,35,42,0.15); border: 1px solid rgba(232,35,42,0.3);
            color: #ff6b70; padding: 6px 14px; border-radius: 100px;
            font-size: 12px; font-weight: 600; letter-spacing: 0.5px;
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
            font-size: clamp(40px, 6vw, 72px);
            font-weight: 900; line-height: 1.05;
            letter-spacing: -2px; margin-bottom: 24px;
        }
        .hero h1 .highlight {
            background: linear-gradient(135deg, var(--red) 0%, #ff6b35 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .hero p {
            font-size: 18px; color: var(--muted); line-height: 1.7;
            max-width: 520px; margin-bottom: 40px;
        }
        .hero-actions { display: flex; gap: 14px; flex-wrap: wrap; }
        .btn-primary {
            display: inline-flex; align-items: center; gap: 10px;
            background: var(--red); color: #fff;
            padding: 16px 28px; border-radius: 14px;
            font-size: 15px; font-weight: 700;
            box-shadow: 0 0 30px var(--red-glow);
            transition: all 0.3s;
        }
        .btn-primary:hover { transform: translateY(-3px); box-shadow: 0 8px 40px var(--red-glow); }
        .btn-secondary {
            display: inline-flex; align-items: center; gap: 10px;
            background: rgba(255,255,255,0.06); color: var(--text);
            border: 1px solid var(--border);
            padding: 16px 28px; border-radius: 14px;
            font-size: 15px; font-weight: 600;
            transition: all 0.3s;
        }
        .btn-secondary:hover { background: rgba(255,255,255,0.10); transform: translateY(-3px); }

        /* Floating phone mockup */
        .hero-visual {
            position: absolute; right: 5%; top: 50%;
            transform: translateY(-50%);
            z-index: 1;
        }
        .phone-mockup {
            width: 280px;
            background: linear-gradient(145deg, #1e1e2e, #2a2a3e);
            border-radius: 36px;
            border: 1.5px solid rgba(255,255,255,0.1);
            box-shadow:
                0 40px 80px rgba(0,0,0,0.6),
                0 0 0 1px rgba(255,255,255,0.05),
                inset 0 1px 0 rgba(255,255,255,0.1);
            padding: 20px 16px;
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(-50%) translateY(0px); }
            50% { transform: translateY(-50%) translateY(-18px); }
        }
        .phone-screen { border-radius: 22px; overflow: hidden; background: #0f0f1a; }
        .phone-header {
            background: var(--red); padding: 16px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .phone-header-title { font-size: 13px; font-weight: 700; color: #fff; }
        .phone-header-sub { font-size: 10px; color: rgba(255,255,255,0.7); }
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
            background: rgba(255,255,255,0.04); border-radius: 12px; padding: 10px 4px;
            font-size: 9px; color: var(--muted);
            border: 1px solid var(--border);
        }
        .phone-svc span:first-child { font-size: 20px; }
        .phone-banner {
            margin: 0 14px 14px;
            background: linear-gradient(135deg, #1a1a2e, #e8232a22);
            border-radius: 12px; padding: 12px;
            border: 1px solid rgba(232,35,42,0.2);
            font-size: 10px; color: var(--muted);
        }
        .phone-banner strong { color: var(--red); display: block; font-size: 12px; margin-bottom: 2px; }

        /* ========== STATS ========== */
        .stats-bar {
            background: var(--dark3);
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
            padding: 32px 5%;
        }
        .stats-inner {
            max-width: 1100px; margin: 0 auto;
            display: grid; grid-template-columns: repeat(4, 1fr);
            gap: 24px; text-align: center;
        }
        .stat-item { }
        .stat-num {
            font-size: 36px; font-weight: 900;
            background: linear-gradient(135deg, #fff 0%, var(--muted) 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -1px;
        }
        .stat-num .stat-suffix { font-size: 22px; }
        .stat-label { font-size: 13px; color: var(--muted); margin-top: 4px; }

        /* ========== SECTIONS COMMON ========== */
        section { padding: 90px 5%; }
        .section-label {
            display: inline-flex; align-items: center; gap: 8px;
            font-size: 12px; font-weight: 700; letter-spacing: 2px;
            text-transform: uppercase; color: var(--red);
            margin-bottom: 16px;
        }
        .section-title {
            font-size: clamp(28px, 4vw, 44px);
            font-weight: 800; letter-spacing: -1px;
            line-height: 1.15; margin-bottom: 16px;
        }
        .section-subtitle { font-size: 16px; color: var(--muted); max-width: 540px; }
        .section-header { margin-bottom: 60px; }
        .max-w { max-width: 1100px; margin: 0 auto; }

        /* ========== SERVICES ========== */
        .services-bg { background: var(--dark2); }
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        .service-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 28px 24px;
            transition: all 0.3s;
            position: relative; overflow: hidden;
            cursor: default;
        }
        .service-card::before {
            content: '';
            position: absolute; inset: 0;
            background: radial-gradient(circle at top left, var(--svc-color, var(--red))22 0%, transparent 60%);
            opacity: 0; transition: opacity 0.3s;
        }
        .service-card:hover { transform: translateY(-6px); border-color: rgba(255,255,255,0.14); }
        .service-card:hover::before { opacity: 1; }
        .svc-icon {
            width: 52px; height: 52px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 26px; margin-bottom: 16px;
        }
        .svc-name { font-size: 17px; font-weight: 700; margin-bottom: 8px; }
        .svc-desc { font-size: 13px; color: var(--muted); line-height: 1.6; }
        .svc-badge {
            display: inline-block; margin-top: 14px;
            font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 6px;
        }

        /* Service Colors */
        .svc-food .svc-icon   { background: rgba(239,68,68,0.15); }
        .svc-grocery .svc-icon { background: rgba(16,185,129,0.15); }
        .svc-pharma .svc-icon  { background: rgba(6,182,212,0.15); }
        .svc-shop .svc-icon    { background: rgba(139,92,246,0.15); }
        .svc-parcel .svc-icon  { background: rgba(245,158,11,0.15); }

        .svc-food .svc-badge   { background: rgba(239,68,68,0.15); color: #ef4444; }
        .svc-grocery .svc-badge { background: rgba(16,185,129,0.15); color: #10b981; }
        .svc-pharma .svc-badge  { background: rgba(6,182,212,0.15); color: #06b6d4; }
        .svc-shop .svc-badge    { background: rgba(139,92,246,0.15); color: #8b5cf6; }
        .svc-parcel .svc-badge  { background: rgba(245,158,11,0.15); color: #f59e0b; }

        /* ========== FEATURES ========== */
        .features-grid {
            display: grid; grid-template-columns: 1fr 1fr;
            gap: 24px;
        }
        .feature-card {
            background: var(--card); border: 1px solid var(--border);
            border-radius: var(--radius); padding: 32px;
            transition: all 0.3s; position: relative; overflow: hidden;
        }
        .feature-card.featured {
            grid-column: span 2;
            display: grid; grid-template-columns: 1fr 1fr; gap: 40px;
            align-items: center;
            background: linear-gradient(135deg, var(--card) 0%, rgba(232,35,42,0.06) 100%);
            border-color: rgba(232,35,42,0.2);
        }
        .feature-card:hover { transform: translateY(-4px); border-color: rgba(255,255,255,0.14); }
        .feat-icon {
            width: 56px; height: 56px; border-radius: 16px;
            background: linear-gradient(135deg, var(--red), #ff6b35);
            display: flex; align-items: center; justify-content: center;
            font-size: 28px; margin-bottom: 20px;
            box-shadow: 0 8px 24px var(--red-glow);
        }
        .feature-card h3 { font-size: 20px; font-weight: 700; margin-bottom: 10px; }
        .feature-card p  { font-size: 14px; color: var(--muted); line-height: 1.7; }
        .feat-visual {
            background: var(--dark3); border-radius: 14px; padding: 24px;
            display: flex; flex-direction: column; gap: 12px;
        }
        .feat-track-item {
            display: flex; align-items: center; gap: 12px;
            background: rgba(255,255,255,0.04); border-radius: 10px; padding: 12px 16px;
        }
        .track-dot {
            width: 10px; height: 10px; border-radius: 50%;
            background: var(--red); flex-shrink: 0;
            box-shadow: 0 0 8px var(--red-glow);
        }
        .track-line { width: 1px; height: 24px; background: var(--border); margin-left: 4px; }
        .track-label { font-size: 12px; color: var(--muted); }
        .track-label strong { color: var(--text); display: block; font-size: 13px; }

        /* ========== HOW IT WORKS ========== */
        .how-bg { background: var(--dark2); }
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
            background: linear-gradient(135deg, var(--red), #ff6b35);
            display: flex; align-items: center; justify-content: center;
            font-size: 24px; font-weight: 900; color: #fff;
            margin: 0 auto 20px;
            box-shadow: 0 0 30px var(--red-glow);
            position: relative; z-index: 1;
        }
        .step-card h3 { font-size: 16px; font-weight: 700; margin-bottom: 8px; }
        .step-card p  { font-size: 13px; color: var(--muted); }

        /* ========== PAYMENT ========== */
        .payment-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 14px;
        }
        .payment-card {
            background: var(--card); border: 1px solid var(--border);
            border-radius: 14px; padding: 20px 16px;
            display: flex; flex-direction: column; align-items: center; gap: 10px;
            transition: all 0.3s; text-align: center;
        }
        .payment-card:hover { transform: translateY(-4px); border-color: rgba(232,35,42,0.3); }
        .payment-icon { font-size: 30px; }
        .payment-name { font-size: 13px; font-weight: 600; }
        .payment-desc { font-size: 11px; color: var(--muted); }

        /* ========== DOWNLOAD CTA ========== */
        .download-section {
            background: linear-gradient(135deg, var(--dark3) 0%, rgba(232,35,42,0.06) 100%);
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
        }
        .download-inner {
            display: grid; grid-template-columns: 1fr 1fr;
            gap: 60px; align-items: center;
        }
        .dl-title {
            font-size: clamp(28px, 3.5vw, 42px);
            font-weight: 800; letter-spacing: -1px;
            line-height: 1.2; margin-bottom: 16px;
        }
        .dl-subtitle { font-size: 16px; color: var(--muted); margin-bottom: 32px; }
        .store-buttons { display: flex; gap: 14px; flex-wrap: wrap; }
        .store-btn {
            display: flex; align-items: center; gap: 12px;
            background: rgba(255,255,255,0.06); border: 1px solid var(--border);
            padding: 14px 20px; border-radius: 14px;
            transition: all 0.3s; min-width: 160px;
        }
        .store-btn:hover { background: rgba(255,255,255,0.1); transform: translateY(-3px); }
        .store-btn .store-icon { font-size: 28px; }
        .store-btn .store-text { font-size: 10px; color: var(--muted); }
        .store-btn .store-name { font-size: 15px; font-weight: 700; }
        .dl-qr {
            display: flex; flex-direction: column; align-items: center; gap: 16px;
        }
        .qr-box {
            width: 160px; height: 160px;
            background: #fff; border-radius: 18px; padding: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 12px; color: #333; text-align: center;
            box-shadow: 0 8px 32px rgba(0,0,0,0.4);
        }
        .qr-label { font-size: 13px; color: var(--muted); text-align: center; }

        /* ========== FAQ ========== */
        .faq-list { display: flex; flex-direction: column; gap: 12px; }
        .faq-item {
            background: var(--card); border: 1px solid var(--border);
            border-radius: 14px; overflow: hidden;
            transition: border-color 0.3s;
        }
        .faq-item.open { border-color: rgba(232,35,42,0.3); }
        .faq-question {
            display: flex; justify-content: space-between; align-items: center;
            padding: 20px 24px; cursor: pointer;
            font-size: 15px; font-weight: 600; user-select: none;
        }
        .faq-question:hover { color: #fff; }
        .faq-icon {
            width: 28px; height: 28px; border-radius: 8px;
            background: rgba(232,35,42,0.15); color: var(--red);
            display: flex; align-items: center; justify-content: center;
            font-size: 16px; flex-shrink: 0; transition: transform 0.3s;
        }
        .faq-item.open .faq-icon { transform: rotate(45deg); }
        .faq-answer {
            max-height: 0; overflow: hidden;
            transition: max-height 0.4s ease, padding 0.3s ease;
            font-size: 14px; color: var(--muted); line-height: 1.7;
            padding: 0 24px;
        }
        .faq-item.open .faq-answer { max-height: 400px; padding: 0 24px 20px; }

        /* ========== FOOTER ========== */
        footer {
            background: var(--dark2);
            border-top: 1px solid var(--border);
            padding: 60px 5% 30px;
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
            background: rgba(255,255,255,0.05); border: 1px solid var(--border);
            display: flex; align-items: center; justify-content: center;
            font-size: 18px; transition: all 0.2s;
        }
        .social-btn:hover { background: var(--red); border-color: var(--red); transform: translateY(-2px); }
        .footer-col h4 { font-size: 13px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: var(--muted); margin-bottom: 20px; }
        .footer-col ul { list-style: none; display: flex; flex-direction: column; gap: 10px; }
        .footer-col ul li a { font-size: 14px; color: var(--muted); transition: color 0.2s; }
        .footer-col ul li a:hover { color: var(--text); }
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
            background: var(--card); border: 1px solid var(--border);
            border-radius: 10px; padding: 12px 16px;
            display: flex; align-items: center; gap: 8px;
            font-size: 13px; font-weight: 500;
            transition: all 0.2s;
        }
        .area-chip:hover { border-color: rgba(232,35,42,0.3); color: var(--red); }
        .area-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--red); flex-shrink: 0; }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 1024px) {
            .hero-visual { display: none; }
            .features-grid { grid-template-columns: 1fr; }
            .feature-card.featured { grid-column: span 1; grid-template-columns: 1fr; }
            .footer-top { grid-template-columns: 1fr 1fr; }
            .download-inner { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .stats-inner { grid-template-columns: repeat(2, 1fr); }
            .steps-grid { grid-template-columns: repeat(2, 1fr); }
            .steps-grid::before { display: none; }
            .nav-links { display: none; }
            .nav-mobile { display: flex; gap: 10px; align-items: center; }
            .footer-top { grid-template-columns: 1fr; }
            .footer-bottom { flex-direction: column; text-align: center; }
        }
        @media (max-width: 480px) {
            .stats-inner { grid-template-columns: repeat(2, 1fr); }
            .steps-grid { grid-template-columns: 1fr; }
        }

        /* ========== ANIMATIONS ========== */
        .fade-in {
            opacity: 0; transform: translateY(30px);
            transition: opacity 0.7s ease, transform 0.7s ease;
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
    <div class="nav-logo">
        <div class="logo-icon">🚀</div>
        Cicalengka<span>GO</span>
    </div>
    <div class="nav-links">
        <a href="#layanan">Layanan</a>
        <a href="#fitur">Fitur</a>
        <a href="#cara-kerja">Cara Kerja</a>
        <a href="#pembayaran">Pembayaran</a>
        <a href="#faq">FAQ</a>
        <a href="/admin" class="nav-cta">Admin Panel</a>
    </div>
    <div class="nav-mobile">
        <a href="#download" class="nav-cta">Unduh App</a>
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
            semuanya dalam satu aplikasi. Cepat, mudah, dan lokal banget.
        </p>
        <div class="hero-actions">
            <a href="#download" class="btn-primary">
                📱 Unduh Aplikasi
            </a>
            <a href="#layanan" class="btn-secondary">
                Lihat Layanan →
            </a>
        </div>
    </div>

    <!-- Phone Mockup -->
    <div class="hero-visual">
        <div class="phone-mockup">
            <div class="phone-screen">
                <div class="phone-header">
                    <div>
                        <div class="phone-header-title">CicalengkaGO</div>
                        <div class="phone-header-sub">Selamat datang! 👋</div>
                    </div>
                    <span style="font-size:22px;">🔔</span>
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
                <div class="phone-banner">
                    <strong>🎉 Gratis Ongkir!</strong>
                    Belanja di atas Rp 100.000
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ========== STATS BAR ========== -->
<div class="stats-bar">
    <div class="stats-inner">
        <div class="stat-item fade-in">
            <div class="stat-num">
                <?php
                $stores = (int)($stats['stores'] ?? 0);
                echo $stores > 0 ? $stores . '<span class="stat-suffix">+</span>' : '50<span class="stat-suffix">+</span>';
                ?>
            </div>
            <div class="stat-label">🏪 Toko Mitra Aktif</div>
        </div>
        <div class="stat-item fade-in fade-in-delay-1">
            <div class="stat-num">
                <?php
                $orders = (int)($stats['orders'] ?? 0);
                if ($orders >= 1000) echo round($orders/1000, 1) . '<span class="stat-suffix">K+</span>';
                else echo ($orders > 0 ? $orders : '500') . '<span class="stat-suffix">+</span>';
                ?>
            </div>
            <div class="stat-label">📦 Pesanan Terselesaikan</div>
        </div>
        <div class="stat-item fade-in fade-in-delay-2">
            <div class="stat-num">
                <?php
                $users = (int)($stats['users'] ?? 0);
                if ($users >= 1000) echo round($users/1000, 1) . '<span class="stat-suffix">K+</span>';
                else echo ($users > 0 ? $users : '1') . '<span class="stat-suffix">K+</span>';
                ?>
            </div>
            <div class="stat-label">👥 Pengguna Terdaftar</div>
        </div>
        <div class="stat-item fade-in fade-in-delay-3">
            <div class="stat-num">
                <?php
                $drivers = (int)($stats['drivers'] ?? 0);
                echo ($drivers > 0 ? $drivers : '20') . '<span class="stat-suffix">+</span>';
                ?>
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
                <h3>Unduh & Daftar</h3>
                <p>Download aplikasi CicalengkaGO, daftar dengan nomor WhatsApp, dan verifikasi OTP.</p>
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

<!-- ========== PEMBAYARAN ========== -->
<section id="pembayaran">
    <div class="max-w">
        <div class="section-header fade-in">
            <div class="section-label">💰 Pembayaran</div>
            <h2 class="section-title">Beragam Metode<br>Pembayaran Tersedia</h2>
            <p class="section-subtitle">Bayar sesuka hati — dari tunai, dompet digital, hingga transfer bank. Semua aman & terpercaya.</p>
        </div>
        <div class="payment-grid">
            <div class="payment-card fade-in">
                <div class="payment-icon">💵</div>
                <div class="payment-name">COD</div>
                <div class="payment-desc">Bayar Tunai ke Driver</div>
            </div>
            <div class="payment-card fade-in fade-in-delay-1">
                <div class="payment-icon">📲</div>
                <div class="payment-name">QRIS</div>
                <div class="payment-desc">Scan & Bayar Instan</div>
            </div>
            <div class="payment-card fade-in fade-in-delay-2">
                <div class="payment-icon">🏦</div>
                <div class="payment-name">BCA</div>
                <div class="payment-desc">Transfer Bank</div>
            </div>
            <div class="payment-card fade-in">
                <div class="payment-icon">🏦</div>
                <div class="payment-name">BRI</div>
                <div class="payment-desc">Transfer Bank</div>
            </div>
            <div class="payment-card fade-in fade-in-delay-1">
                <div class="payment-icon">🏦</div>
                <div class="payment-name">Mandiri</div>
                <div class="payment-desc">Transfer Bank</div>
            </div>
            <div class="payment-card fade-in fade-in-delay-2">
                <div class="payment-icon">💙</div>
                <div class="payment-name">DANA</div>
                <div class="payment-desc">E-Wallet</div>
            </div>
            <div class="payment-card fade-in">
                <div class="payment-icon">💚</div>
                <div class="payment-name">GoPay</div>
                <div class="payment-desc">E-Wallet</div>
            </div>
            <div class="payment-card fade-in fade-in-delay-1">
                <div class="payment-icon">⭐</div>
                <div class="payment-name">CicalengkaPay</div>
                <div class="payment-desc">Dompet Internal</div>
            </div>
        </div>
    </div>
</section>

<!-- ========== AREA LAYANAN ========== -->
<section style="background:var(--dark2); padding:70px 5%;">
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

<!-- ========== DOWNLOAD ========== -->
<section id="download" class="download-section">
    <div class="max-w">
        <div class="download-inner">
            <div class="fade-in">
                <div class="section-label">📱 Download</div>
                <div class="dl-title">
                    Mulai Gunakan<br>
                    CicalengkaGO<br>
                    <span style="color: var(--red);">Sekarang!</span>
                </div>
                <p class="dl-subtitle">Ribuan warga Cicalengka sudah merasakan kemudahan berbelanja lewat CicalengkaGO. Kapan giliranmu?</p>
                <div class="store-buttons">
                    <a href="#" class="store-btn">
                        <div class="store-icon">🤖</div>
                        <div>
                            <div class="store-text">Tersedia di</div>
                            <div class="store-name">Google Play</div>
                        </div>
                    </a>
                    <a href="#" class="store-btn">
                        <div class="store-icon">🍎</div>
                        <div>
                            <div class="store-text">Tersedia di</div>
                            <div class="store-name">App Store</div>
                        </div>
                    </a>
                </div>
                <div style="margin-top:20px; font-size:13px; color:var(--muted);">
                    ✅ Gratis · ✅ Tanpa biaya berlangganan · ✅ Aman & terpercaya
                </div>
            </div>
            <div class="dl-qr fade-in fade-in-delay-1">
                <div class="qr-box">
                    <div>
                        <div style="font-size:40px;margin-bottom:8px;">📱</div>
                        <div style="font-weight:700;color:#333;font-size:13px;">Scan QR Code</div>
                        <div style="color:#666;font-size:11px;margin-top:4px;">untuk download aplikasi</div>
                    </div>
                </div>
                <div class="qr-label">📲 Scan dengan kamera HP kamu</div>
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
                        'q' => 'Bagaimana cara mendaftar sebagai pelanggan?',
                        'a' => 'Cukup unduh aplikasi CicalengkaGO dari Google Play Store atau App Store, masukkan nomor WhatsApp aktif, dan verifikasi dengan kode OTP yang dikirim ke WhatsApp kamu. Proses registrasi selesai dalam hitungan detik!'
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
                <div class="nav-logo" style="margin-bottom:0;">
                    <div class="logo-icon">🚀</div>
                    Cicalengka<span style="color:var(--red);">GO</span>
                </div>
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
                    <li><a href="#">Daftar sebagai Pelanggan</a></li>
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
    // ========== NAVBAR SCROLL ==========
    const navbar = document.getElementById('navbar');
    window.addEventListener('scroll', () => {
        navbar.classList.toggle('scrolled', window.scrollY > 50);
        document.getElementById('backTop').classList.toggle('show', window.scrollY > 400);
    });

    // ========== FAQ TOGGLE ==========
    function toggleFaq(id) {
        const item = document.getElementById('faq-' + id);
        const isOpen = item.classList.contains('open');
        // Close all
        document.querySelectorAll('.faq-item.open').forEach(el => el.classList.remove('open'));
        // Open clicked if it was closed
        if (!isOpen) item.classList.add('open');
    }

    // ========== INTERSECTION OBSERVER (fade-in) ==========
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

    document.querySelectorAll('.fade-in').forEach(el => observer.observe(el));

    // ========== SMOOTH NAV LINKS ==========
    document.querySelectorAll('a[href^="#"]').forEach(a => {
        a.addEventListener('click', e => {
            const target = document.querySelector(a.getAttribute('href'));
            if (target) {
                e.preventDefault();
                const offset = 80;
                const top = target.getBoundingClientRect().top + window.scrollY - offset;
                window.scrollTo({ top, behavior: 'smooth' });
            }
        });
    });

    // ========== STATS COUNTER ANIMATION ==========
    function animateCounter(el) {
        const target = parseInt(el.textContent.replace(/[^0-9]/g, ''));
        if (!target) return;
        const suffix = el.querySelector('.stat-suffix')?.outerHTML || '';
        const isK = el.textContent.includes('K');
        let current = 0;
        const step = Math.max(1, Math.floor(target / 60));
        const interval = setInterval(() => {
            current = Math.min(current + step, target);
            el.innerHTML = (isK ? current + '<span class="stat-suffix">K+</span>' : current + suffix);
            if (current >= target) clearInterval(interval);
        }, 25);
    }

    const statsObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.querySelectorAll('.stat-num').forEach(animateCounter);
                statsObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.5 });

    document.querySelectorAll('.stats-inner').forEach(el => statsObserver.observe(el));
</script>
</body>
</html>

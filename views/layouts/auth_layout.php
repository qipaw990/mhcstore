<?php
$appConfig = require APP_PATH . '/config/app.php';
$baseUrl = $appConfig['public_url'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title><?= $title ?? 'Masuk - CicalengkaGO' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/admin.css">
    <style>
        * { box-sizing: border-box; }
        body {
            background: #090A0F;
            background-image: radial-gradient(circle at 50% 0%, rgba(39, 39, 42, 0.4) 0%, #090A0F 70%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            color: #F8FAFC;
        }
        .auth-card {
            background: #FFFFFF;
            border-radius: 24px;
            max-width: 410px;
            width: 100%;
            padding: 32px 28px 28px 28px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5), 0 4px 12px rgba(0,0,0,0.15);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #090A0F;
        }
        .auth-logo-box {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            background: #090A0F;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25);
            border: 1.5px solid #27272A;
            margin-bottom: 12px;
            overflow: hidden;
            padding: 4px;
        }
        .auth-logo-img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        /* Flash alert banners */
        .auth-flash-error {
            background: #FEF2F2;
            border: 1px solid #FECACA;
            border-radius: 12px;
            padding: 11px 14px;
            font-size: 12px;
            color: #DC2626;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 18px;
        }
        .auth-flash-success {
            background: #F0FDF4;
            border: 1px solid #BBF7D0;
            border-radius: 12px;
            padding: 11px 14px;
            font-size: 12px;
            color: #16A34A;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 18px;
        }
    </style>
</head>
<body>
<?php require_once dirname(__DIR__) . '/partials/preloader.php'; ?>

<div class="auth-card">
    <!-- Top Security Badge -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="badge bg-dark text-white rounded-pill px-2.5 py-1" style="font-size: 10px; letter-spacing: 0.5px; font-weight: 700;">
            <i class="bi bi-shield-lock-fill me-1 text-light"></i> HQ ACCESS
        </span>
        <span class="text-muted" style="font-size: 11px; font-weight: 600;">CicalengkaGO HQ</span>
    </div>

    <!-- Logo & Brand Header -->
    <div class="text-center mb-4">
        <div class="auth-logo-box">
            <img src="<?= $baseUrl ?>/assets/images/app_logo.png" alt="CicalengkaGO Logo" class="auth-logo-img">
        </div>
        <h5 class="fw-extrabold m-0 text-dark" style="letter-spacing: -0.5px; font-size: 20px;">
            Cicalengka<span class="fw-black text-dark">GO</span>
        </h5>
        <div class="text-muted mt-1" style="font-size: 11.5px; font-weight: 500;">
            Enterprise Admin & Merchant Portal
        </div>
    </div>

    <!-- Flash Error Banner -->
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="auth-flash-error">
            <i class="bi bi-exclamation-circle-fill fs-6 flex-shrink-0"></i>
            <span><?= htmlspecialchars($_SESSION['error']) ?></span>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Flash Success Banner -->
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="auth-flash-success">
            <i class="bi bi-check-circle-fill fs-6 flex-shrink-0"></i>
            <span><?= htmlspecialchars($_SESSION['success']) ?></span>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?= $content ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

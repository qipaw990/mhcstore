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
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/mobile.css">
    <style>
        * { box-sizing: border-box; }
        body {
            background: #F8FAFC;
            background-image: radial-gradient(circle at 50% 0%, rgba(238, 39, 55, 0.07) 0%, transparent 55%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 16px;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        }
        .auth-card {
            background: #FFFFFF;
            border-radius: 22px;
            max-width: 390px;
            width: 100%;
            padding: 28px 24px 24px 24px;
            box-shadow: 0 12px 40px rgba(16, 24, 32, 0.08), 0 2px 8px rgba(0,0,0,0.04);
            border: 1px solid #E2E8F0;
        }
        .auth-logo-img {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            box-shadow: 0 4px 14px rgba(238, 39, 55, 0.28);
            object-fit: cover;
        }

        /* Flash alert banner */
        .auth-flash-error {
            background: #FEE2E2;
            border: 1px solid #FECACA;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 11.5px;
            color: #DC2626;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
        }
        .auth-flash-success {
            background: #F0FDF4;
            border: 1px solid #BBF7D0;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 11.5px;
            color: #16A34A;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
        }
    </style>
</head>
<body>
<?php require_once dirname(__DIR__) . '/partials/preloader.php'; ?>

<div class="auth-card">
    <!-- Logo & Brand -->
    <div style="text-align: center; margin-bottom: 24px;">
        <a href="<?= $baseUrl ?>/" style="display: inline-block; text-decoration: none; margin-bottom: 10px;">
            <img src="<?= $baseUrl ?>/assets/images/logo-icon.svg" alt="CicalengkaGO Logo" class="auth-logo-img">
        </a>
        <h5 style="font-size: 18px; font-weight: 800; color: #0F172A; margin: 0 0 4px; letter-spacing: -0.4px;">
            Cicalengka<span style="color: #EE2737;">GO</span>
        </h5>
        <div style="font-size: 11px; color: #64748B; font-weight: 500;">Super App On-Demand & Kuliner Cicalengka</div>
    </div>

    <!-- Flash Error Banner -->
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="auth-flash-error">
            <i class="bi bi-exclamation-circle-fill" style="font-size: 14px; flex-shrink: 0;"></i>
            <span><?= htmlspecialchars($_SESSION['error']) ?></span>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Flash Success Banner -->
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="auth-flash-success">
            <i class="bi bi-check-circle-fill" style="font-size: 14px; flex-shrink: 0;"></i>
            <span><?= htmlspecialchars($_SESSION['success']) ?></span>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?= $content ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

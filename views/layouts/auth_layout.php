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
        body {
            background: #F8FAFC;
            background-image: radial-gradient(circle at 50% 0%, rgba(238, 39, 55, 0.08) 0%, transparent 60%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            position: relative;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        }
        .auth-card {
            background: #ffffff;
            border-radius: 20px;
            max-width: 390px;
            width: 100%;
            padding: 24px 20px;
            box-shadow: 0 12px 36px rgba(16, 24, 32, 0.07);
            border: 1px solid #E2E8F0;
            position: relative;
            z-index: 1;
        }
        .auth-logo-img {
            width: 54px;
            height: 54px;
            border-radius: 14px;
            box-shadow: 0 4px 12px rgba(238, 39, 55, 0.25);
            object-fit: cover;
        }
        .form-control:focus, .input-group-text:focus {
            box-shadow: none;
            border-color: #EE2737 !important;
        }
    </style>
</head>
<body>
<?php require_once dirname(__DIR__) . '/partials/preloader.php'; ?>

<div class="auth-card">
    <div class="text-center mb-3.5">
        <a href="<?= $baseUrl ?>/" class="d-inline-block text-decoration-none mb-1.5">
            <img src="<?= $baseUrl ?>/assets/images/logo-icon.svg" alt="CicalengkaGO Logo" class="auth-logo-img">
        </a>
        <h5 class="fw-bold mb-0 text-dark" style="letter-spacing: -0.3px;">Cicalengka<span style="color: #EE2737;">GO</span></h5>
        <div class="text-muted" style="font-size: 11px; font-weight: 500;">Super App On-Demand & Kuliner Cicalengka</div>
    </div>

    <?= $content ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php if (!empty($_SESSION['error'])): ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Gagal',
            text: '<?= addslashes($_SESSION['error']) ?>',
            confirmButtonColor: '#EE2737'
        });
    </script>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

</body>
</html>


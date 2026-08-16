<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Halaman Tidak Ditemukan | CicalengkaGO</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; background: #f8fafc; }
        .error-card { max-width: 440px; margin: 80px auto; padding: 32px; background: #fff; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-card">
            <div class="rounded-circle bg-danger-subtle text-danger d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 72px; height: 72px; font-size: 32px;">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>
            <h4 class="fw-bold mb-2">Halaman Tidak Ditemukan</h4>
            <p class="text-muted small mb-4">Halaman <code><?= htmlspecialchars($uri ?? '') ?></code> tidak tersedia atau telah dipindahkan.</p>
            <a href="<?= $baseUrl ?? '/CicalengkaGO' ?>" class="btn btn-primary w-100 fw-bold py-2 rounded-3">Kembali ke Beranda</a>
        </div>
    </div>
</body>
</html>

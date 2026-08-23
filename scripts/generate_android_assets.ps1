
# ============================================================
# CicalengkaGO - Android Asset Generator
# Menggunakan .NET System.Drawing untuk resize gambar
# Jalankan: powershell -ExecutionPolicy Bypass -File .\scripts\generate_android_assets.ps1
# ============================================================

Add-Type -AssemblyName System.Drawing

$basePath = Split-Path -Parent $PSScriptRoot

# Source images
$splashSrc = "C:\Users\smkmu\.gemini\antigravity\brain\5fe2fa74-fd43-4b59-8efa-e6c97a30143d\cicago_splash_screen_1787492171523.png"
$iconSrc   = "C:\Users\smkmu\.gemini\antigravity\brain\5fe2fa74-fd43-4b59-8efa-e6c97a30143d\cicago_app_icon_1787492205657.png"

$resPath = "$basePath\android\app\src\main\res"

# ---- App Icon Sizes (mipmap) ----
$iconSizes = @{
    "mipmap-mdpi"    = 48
    "mipmap-hdpi"    = 72
    "mipmap-xhdpi"   = 96
    "mipmap-xxhdpi"  = 144
    "mipmap-xxxhdpi" = 192
}

# ---- Splash Screen Sizes (drawable-port) ----
$splashSizes = @{
    "drawable"               = @(480, 800)
    "drawable-port-mdpi"     = @(480, 800)
    "drawable-port-hdpi"     = @(720, 1280)
    "drawable-port-xhdpi"    = @(1080, 1920)
    "drawable-port-xxhdpi"   = @(1440, 2560)
    "drawable-port-xxxhdpi"  = @(1440, 2960)
    "drawable-land-mdpi"     = @(800, 480)
    "drawable-land-hdpi"     = @(1280, 720)
    "drawable-land-xhdpi"    = @(1920, 1080)
    "drawable-land-xxhdpi"   = @(2560, 1440)
    "drawable-land-xxxhdpi"  = @(2960, 1440)
}

function Resize-Image {
    param(
        [string]$srcPath,
        [string]$dstPath,
        [int]$width,
        [int]$height
    )

    $src = [System.Drawing.Image]::FromFile($srcPath)
    $dst = New-Object System.Drawing.Bitmap($width, $height)
    $g   = [System.Drawing.Graphics]::FromImage($dst)
    $g.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
    $g.SmoothingMode     = [System.Drawing.Drawing2D.SmoothingMode]::HighQuality
    $g.PixelOffsetMode   = [System.Drawing.Drawing2D.PixelOffsetMode]::HighQuality
    $g.DrawImage($src, 0, 0, $width, $height)
    $dst.Save($dstPath, [System.Drawing.Imaging.ImageFormat]::Png)
    $g.Dispose()
    $dst.Dispose()
    $src.Dispose()

    Write-Host "  [OK] $dstPath ($width x $height)" -ForegroundColor Green
}

Write-Host ""
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host " CicalengkaGO Android Asset Generator" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""

# ---- Generate App Icons ----
Write-Host "[1/2] Generating App Icons..." -ForegroundColor Yellow
foreach ($entry in $iconSizes.GetEnumerator()) {
    $dir  = "$resPath\$($entry.Key)"
    $size = $entry.Value

    # ic_launcher.png
    Resize-Image -srcPath $iconSrc -dstPath "$dir\ic_launcher.png" -width $size -height $size
    # ic_launcher_round.png
    Resize-Image -srcPath $iconSrc -dstPath "$dir\ic_launcher_round.png" -width $size -height $size
    # ic_launcher_foreground.png
    Resize-Image -srcPath $iconSrc -dstPath "$dir\ic_launcher_foreground.png" -width $size -height $size
}

Write-Host ""
Write-Host "[2/2] Generating Splash Screens..." -ForegroundColor Yellow
foreach ($entry in $splashSizes.GetEnumerator()) {
    $dir = "$resPath\$($entry.Key)"
    $w   = $entry.Value[0]
    $h   = $entry.Value[1]

    if (-not (Test-Path $dir)) {
        New-Item -ItemType Directory -Path $dir | Out-Null
    }

    Resize-Image -srcPath $splashSrc -dstPath "$dir\splash.png" -width $w -height $h
}

Write-Host ""
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host " SELESAI! Semua aset berhasil di-generate." -ForegroundColor Green
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Langkah selanjutnya:" -ForegroundColor White
Write-Host "  1. Buka Android Studio"
Write-Host "  2. Build & Run ulang project"
Write-Host "  3. Ikon dan splash screen CicaGO akan tampil!"
Write-Host ""

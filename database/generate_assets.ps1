Add-Type -AssemblyName System.Drawing

function Create-ImageCard($path, $width, $height, $bgHex, $text, $subtext = "") {
    $dir = [System.IO.Path]::GetDirectoryName($path)
    if (-not (Test-Path $dir)) { New-Item -ItemType Directory -Path $dir -Force | Out-Null }

    $bmp = New-Object System.Drawing.Bitmap $width, $height
    $g = [System.Drawing.Graphics]::FromImage($bmp)
    $g.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::AntiAlias

    $color = [System.Drawing.ColorTranslator]::FromHtml($bgHex)
    $brush = New-Object System.Drawing.SolidBrush($color)
    $g.FillRectangle($brush, 0, 0, $width, $height)

    # Accent decorative banner stripe
    $overlayBrush = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(40, 0, 0, 0))
    $g.FillRectangle($overlayBrush, 0, [int]($height * 0.65), $width, [int]($height * 0.35))

    # Font
    $fontFamily = New-Object System.Drawing.FontFamily("Arial")
    $fontSize = [Math]::Max(12, [int]($height * 0.10))
    $font = New-Object System.Drawing.Font($fontFamily, $fontSize, [System.Drawing.FontStyle]::Bold)
    $textBrush = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::White)
    $sf = New-Object System.Drawing.StringFormat
    $sf.Alignment = [System.Drawing.StringAlignment]::Center
    $sf.LineAlignment = [System.Drawing.StringAlignment]::Center

    $rect = New-Object System.Drawing.RectangleF(10, 10, ($width - 20), ($height - 20))
    $g.DrawString($text, $font, $textBrush, $rect, $sf)

    $bmp.Save($path, [System.Drawing.Imaging.ImageFormat]::Jpeg)
    $g.Dispose()
    $bmp.Dispose()
}

$base = "c:\xampp2\htdocs\CicalengkaGO\public\assets\images"

# Banners
Create-ImageCard "$base\banners\banner1.jpg" 800 360 "#ef4444" "KULINER CICALENGKA DISKON 20%"
Create-ImageCard "$base\banners\banner2.jpg" 800 360 "#f59e0b" "SATE MARANGGI ALUN-ALUN"
Create-ImageCard "$base\banners\banner3.jpg" 800 360 "#10b981" "SEMBAKO CEPAT SAMPAI"

# Store Covers & Logos
Create-ImageCard "$base\stores\geprek_cover.jpg" 600 300 "#dc2626" "AYAM GEPREK JUARA"
Create-ImageCard "$base\stores\geprek_logo.png" 150 150 "#ef4444" "GEPREK"
Create-ImageCard "$base\stores\maranggi_cover.jpg" 600 300 "#b45309" "SATE MARANGGI"
Create-ImageCard "$base\stores\maranggi_logo.png" 150 150 "#f59e0b" "SATE"
Create-ImageCard "$base\stores\sembako_cover.jpg" 600 300 "#059669" "SEMBAKO BERKAH"
Create-ImageCard "$base\stores\sembako_logo.png" 150 150 "#10b981" "SEMBAKO"
Create-ImageCard "$base\stores\apotek_cover.jpg" 600 300 "#0891b2" "APOTEK MEDIKA"
Create-ImageCard "$base\stores\apotek_logo.png" 150 150 "#06b6d4" "APOTEK"
Create-ImageCard "$base\stores\fashion_cover.jpg" 600 300 "#7c3aed" "CICALENGKA FASHION"
Create-ImageCard "$base\stores\fashion_logo.png" 150 150 "#8b5cf6" "FASHION"

# Products
Create-ImageCard "$base\products\geprek_sambal.jpg" 400 300 "#ea580c" "Ayam Geprek Sambal Bawang"
Create-ImageCard "$base\products\geprek_mozza.jpg" 400 300 "#f59e0b" "Ayam Geprek Mozzarella"
Create-ImageCard "$base\products\es_jeruk.jpg" 400 300 "#f97316" "Es Jeruk Segar"
Create-ImageCard "$base\products\es_teh.jpg" 400 300 "#b45309" "Es Teh Manis Jumbo"
Create-ImageCard "$base\products\sate_maranggi.jpg" 400 300 "#78350f" "Sate Maranggi Sapi"
Create-ImageCard "$base\products\sop_iga.jpg" 400 300 "#92400e" "Sop Iga Sapi Rempah"
Create-ImageCard "$base\products\minyak_sania.jpg" 400 300 "#047857" "Minyak Goreng 2L"
Create-ImageCard "$base\products\beras_pandan.jpg" 400 300 "#065f46" "Beras Pandan Wangi 5kg"
Create-ImageCard "$base\products\enervon_c.jpg" 400 300 "#0e7490" "Enervon-C Multivitamin"
Create-ImageCard "$base\products\pashmina.jpg" 400 300 "#6d28d9" "Pashmina Silk Premium"
Create-ImageCard "$base\products\default.jpg" 400 300 "#64748b" "Menu CicalengkaGO"

# Users
Create-ImageCard "$base\users\default.png" 150 150 "#3b82f6" "USER"
Create-ImageCard "$base\users\admin.png" 150 150 "#1e293b" "ADMIN"
Create-ImageCard "$base\users\vendor1.png" 150 150 "#ef4444" "MITRA"
Create-ImageCard "$base\users\vendor2.png" 150 150 "#10b981" "MITRA"
Create-ImageCard "$base\users\driver.png" 150 150 "#0284c7" "DRIVER"
Create-ImageCard "$base\users\customer.png" 150 150 "#3b82f6" "BUDI"

Write-Host "Demo assets generated successfully."

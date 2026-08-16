Add-Type -AssemblyName System.Drawing

$sizes = @(72, 96, 128, 144, 192, 512)
$dir = "c:\xampp2\htdocs\CicalengkaGO\public\assets\icons"
if (-not (Test-Path $dir)) { New-Item -ItemType Directory -Path $dir -Force }

foreach ($size in $sizes) {
    $bmp = New-Object System.Drawing.Bitmap $size, $size
    $g = [System.Drawing.Graphics]::FromImage($bmp)
    $g.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::AntiAlias
    
    # Fill background
    $brush = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(13, 110, 253))
    $g.FillRectangle($brush, 0, 0, $size, $size)
    
    # Draw Inner Gold Ring
    $pen = New-Object System.Drawing.Pen([System.Drawing.Color]::FromArgb(251, 191, 36), [Math]::Max(2, [int]($size/20)))
    $margin = [int]($size * 0.1)
    $g.DrawEllipse($pen, $margin, $margin, ($size - 2*$margin), ($size - 2*$margin))
    
    # Draw Text
    $fontFamily = New-Object System.Drawing.FontFamily("Arial")
    $fontSize = [Math]::Max(10, [int]($size * 0.20))
    $font = New-Object System.Drawing.Font($fontFamily, $fontSize, [System.Drawing.FontStyle]::Bold)
    $textBrush = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::White)
    $sf = New-Object System.Drawing.StringFormat
    $sf.Alignment = [System.Drawing.StringAlignment]::Center
    $sf.LineAlignment = [System.Drawing.StringAlignment]::Center
    
    $rect = New-Object System.Drawing.RectangleF(0, 0, $size, $size)
    $g.DrawString("CGO", $font, $textBrush, $rect, $sf)
    
    $path = Join-Path $dir ("icon-" + $size + ".png")
    $bmp.Save($path, [System.Drawing.Imaging.ImageFormat]::Png)
    $g.Dispose()
    $bmp.Dispose()
}

Write-Host "Icons generated successfully."

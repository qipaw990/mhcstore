@echo off
title CicalengkaGO - WhatsApp Gateway
color 0A
echo.
echo  ============================================
echo   CicalengkaGO WhatsApp OTP Gateway v1.0
echo  ============================================
echo.
echo  [1] Memulai server gateway...
echo  [2] Setelah QR muncul, buka browser:
echo      http://localhost:3005/qr
echo  [3] Scan QR Code dengan WhatsApp HP Anda
echo.
echo  Tekan CTRL+C untuk menghentikan server.
echo  ============================================
echo.
cd /d "%~dp0"
set PUPPETEER_SKIP_DOWNLOAD=true
node server.js
pause

#!/bin/bash
# =======================================================
# Script Auto-Pull & Rebuild CicalengkaGO di CasaOS
# =======================================================

echo "======================================================="
echo " 🚀 MEMULAI UPDATE CICALENGKAGO DARI GITHUB..."
echo "======================================================="

# Pull commit terbaru dari GitHub branch main
git pull origin main

# Rebuild dan jalankan ulang container Docker
docker compose up -d --build

echo "======================================================="
echo " ✅ UPDATE SESELESAI! CicalengkaGO Siap Akses di Port 8090"
echo "======================================================="

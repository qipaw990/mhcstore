#!/bin/bash
# =======================================================
# Script Auto-Pull & Rebuild CicalengkaGO di CasaOS
# =======================================================

echo "======================================================="
echo " 🚀 MEMULAI UPDATE CICALENGKAGO DARI GITHUB..."
echo "======================================================="

# Pull commit terbaru dari GitHub branch main (auto-overwrite local conflicts)
git fetch origin main
git reset --hard origin/main

# Pastikan folder uploads ada dan memiliki permission write
mkdir -p public/uploads/profiles \
         public/uploads/stores \
         public/uploads/products \
         public/uploads/banners \
         public/uploads/general

chmod -R 777 public/uploads

# Matikan BuildKit gRPC daemon jika kehabisan memori / crash RPC EOF
export DOCKER_BUILDKIT=0
export COMPOSE_DOCKER_CLI_BUILD=0

# Rebuild dan jalankan ulang container Docker (App, DB, & WhatsApp Gateway)
echo "📦 Membangun ulang container Docker..."
if ! docker compose up -d --build; then
    echo "⚠️ Build reguler gagal (cache/snapshot korup). Menjalankan build bersih tanpa cache (--no-cache)..."
    docker builder prune -f 2>/dev/null || true
    DOCKER_BUILDKIT=0 docker compose build --no-cache
    docker compose up -d
fi

# Pastikan permission di dalam container dan host aman
docker compose exec -u root cicalengkago_app chmod -R 777 /var/www/html/public/uploads 2>/dev/null || true

# Jalankan migrasi database otomatis & indeks performa
echo "🗄️ Menjalankan migrasi database otomatis & indeks performa..."
docker compose exec -T cicalengkago_app php database/run_casaos_migration.php 2>/dev/null || php database/run_casaos_migration.php 2>/dev/null || true
docker compose exec -T cicalengkago_app php database/optimize_performance_indexes.php 2>/dev/null || php database/optimize_performance_indexes.php 2>/dev/null || true

# Seed variasi & topping produk
echo "🍧 Memastikan variasi & topping produk tersedia..."
docker compose exec -T cicalengkago_app php database/seed_product_variations_addons.php 2>/dev/null || php database/seed_product_variations_addons.php 2>/dev/null || true

# Bersihkan gambar sampah / tidak terpakai
echo "🧹 Membersihkan gambar yang tidak terpakai..."
docker compose exec -T cicalengkago_app php database/clean_unused_images.php 2>/dev/null || php database/clean_unused_images.php 2>/dev/null || true

# Auto-reconnect Cloudflare Tunnel jika service terhenti
systemctl restart cloudflared 2>/dev/null || docker restart cloudflared 2>/dev/null || true

echo "======================================================="
echo " ✅ UPDATE SELESAI!"
echo " 🌐 CicalengkaGO Web App  : Port 8090 (https://cicago.store)"
echo " 📱 WhatsApp Gateway     : Port 3005 (http://<ip-casaos>:3005/qr)"
echo "======================================================="

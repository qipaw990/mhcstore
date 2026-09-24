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

# -------------------------------------------------------
# Build Flutter Web App (jika Flutter terinstall di host)
# Output: cicalengkago_mobile/build/web → public/flutter_web/
# -------------------------------------------------------
if command -v flutter &> /dev/null; then
    echo "📱 Membangun Flutter Web App..."
    cd cicalengkago_mobile
    flutter pub get
    if flutter build web --release; then
        echo "✅ Flutter web build berhasil!"
        # Salin hasil build ke folder public agar terbaca Docker
        mkdir -p ../public/flutter_web
        cp -rf build/web/* ../public/flutter_web/
        echo "📁 Output disalin ke public/flutter_web/"
    else
        echo "⚠️ Flutter web build gagal, melanjutkan dengan versi sebelumnya..."
    fi
    cd ..
else
    echo "⚠️ Flutter tidak terinstall di host, skip build Flutter web."
    echo "   Gunakan: sudo snap install flutter --classic  (lalu jalankan update.sh lagi)"
fi

# Pastikan folder uploads ada dan memiliki permission write
mkdir -p public/uploads/profiles \
         public/uploads/stores \
         public/uploads/products \
         public/uploads/banners \
         public/uploads/general

chmod -R 777 public/uploads

# Matikan BuildKit gRPC daemon jika kehabisan memori / crash RPC EOF di low-spec host
export DOCKER_BUILDKIT=0
export COMPOSE_DOCKER_CLI_BUILD=0

# Opsi pembaruan:
# 1. ./update.sh quick / --quick : Hanya restart PHP backend (~1 detik)
# 2. ./update.sh web / --web     : Rebuild seluruh container termasuk Flutter Web (~12 menit jika compile di Docker)
# 3. ./update.sh                 : Update default: Rebuild container backend (cicalengkago_app & cicago_wa_gateway ~3 detik)
if [ "$1" == "quick" ] || [ "$1" == "--quick" ]; then
    echo "⚡ Mode Update Cepat: Me-restart container backend..."
    docker compose restart cicalengkago_app
elif [ "$1" == "web" ] || [ "$1" == "--web" ] || [ "$1" == "all" ] || [ "$1" == "--all" ]; then
    echo "📦 Mode Full: Membangun ulang seluruh container termasuk Flutter Web..."
    docker rm -f cicalengkago_web 2>/dev/null || true
    if ! docker compose up -d --build --remove-orphans; then
        echo "⚠️ Build reguler gagal. Menjalankan build bersih tanpa cache (--no-cache)..."
        docker builder prune -f 2>/dev/null || true
        DOCKER_BUILDKIT=0 docker compose build --no-cache
        docker compose up -d --remove-orphans
    fi
else
    # Rebuild cepat hanya untuk container backend (App & WhatsApp Gateway).
    # Container Flutter Web (cicalengkago_web) dan DB tetap berjalan tanpa harus di-compile ulang 750+ detik.
    echo "📦 Membangun container backend (cicalengkago_app & cicago_wa_gateway)..."
    if ! docker compose build cicalengkago_app cicago_wa_gateway; then
        echo "⚠️ Build backend gagal, mencoba build bersih..."
        DOCKER_BUILDKIT=0 docker compose build --no-cache cicalengkago_app cicago_wa_gateway
    fi
    echo "🚀 Menjalankan container dengan docker compose up..."
    docker compose up -d --remove-orphans
fi

# Pastikan permission di dalam container dan host aman
docker compose exec -u root cicalengkago_app chmod -R 777 /var/www/html/public/uploads 2>/dev/null || true

# Jalankan migrasi database otomatis & indeks performa
echo "🗄️ Menjalankan migrasi database otomatis & indeks performa..."
docker compose exec -T cicalengkago_app php database/run_casaos_migration.php 2>/dev/null || php database/run_casaos_migration.php 2>/dev/null || true

echo "📱 Menjalankan migrasi tabel app_features (Fitur & Layanan Dinamis)..."
docker compose exec -T cicalengkago_app php database/migrate_app_features.php || true
if [ -f "database/migrate_app_features.sql" ]; then
    # Gunakan cat + pipe agar berjalan di semua shell/Docker environment
    # Coba root user dulu (mariadb / mysql), lalu fallback ke user biasa
    cat database/migrate_app_features.sql | docker compose exec -T cicalengkago_db mariadb -u root -prootpassword cicalengkago 2>/dev/null || \
    cat database/migrate_app_features.sql | docker compose exec -T cicalengkago_db mysql  -u root -prootpassword cicalengkago 2>/dev/null || \
    cat database/migrate_app_features.sql | docker compose exec -T cicalengkago_db mariadb -u cicalengka_user -pcicalengka_pass cicalengkago 2>/dev/null || \
    cat database/migrate_app_features.sql | docker compose exec -T cicalengkago_db mysql  -u cicalengka_user -pcicalengka_pass cicalengkago 2>/dev/null || true
    echo "   ↳ SQL app_features selesai dieksekusi ke database."
fi

docker compose exec -T cicalengkago_app php database/optimize_performance_indexes.php 2>/dev/null || php database/optimize_performance_indexes.php 2>/dev/null || true
docker compose exec -T cicalengkago_app php database/add_doku_settings.php 2>/dev/null || php database/add_doku_settings.php 2>/dev/null || true

# KRITIS: Perbaiki UNIQUE KEY wallet agar driver punya wallet terpisah (fix saldo tidak masuk)
echo "💰 Memperbaiki struktur wallet driver (fix komisi tidak masuk ke saldo)..."
docker compose exec -T cicalengkago_app php database/fix_wallet_unique_key.php 2>/dev/null || php database/fix_wallet_unique_key.php 2>/dev/null || true

docker compose exec -T cicalengkago_app php database/repair_driver_commissions.php 2>/dev/null || php database/repair_driver_commissions.php 2>/dev/null || true

# Perbaiki topup Rp 0 dan kembalikan saldo
echo "💳 Memperbaiki log top-up bernilai Rp 0 & menambahkan saldo..."
docker compose exec -T cicalengkago_app php database/fix_zero_topups.php 2>/dev/null || php database/fix_zero_topups.php 2>/dev/null || true

# Seed variasi & topping produk
echo "🍧 Memastikan variasi & topping produk tersedia..."
docker compose exec -T cicalengkago_app php database/seed_product_variations_addons.php 2>/dev/null || php database/seed_product_variations_addons.php 2>/dev/null || true

# Seed master bahan baku, resep produk & kalkulasi HPP otomatis
echo "🌾 Menanam master bahan baku, resep produk & kalkulasi HPP otomatis..."
docker compose exec -T cicalengkago_app php database/seed_raw_materials_and_recipes.php 2>/dev/null || php database/seed_raw_materials_and_recipes.php 2>/dev/null || true

# Bersihkan gambar sampah / tidak terpakai
echo "🧹 Membersihkan gambar yang tidak terpakai..."
docker compose exec -T cicalengkago_app php database/clean_unused_images.php 2>/dev/null || php database/clean_unused_images.php 2>/dev/null || true

# Auto-reconnect Cloudflare Tunnel jika service terhenti
systemctl restart cloudflared 2>/dev/null || docker restart cloudflared 2>/dev/null || true

echo "======================================================="
echo " ✅ UPDATE SELESAI!"
echo " 🌐 CicalengkaGO Backend API     : Port 8090 (https://cicago.store)"
echo " ⚡ Super Admin React Panel      : Port 8096 (http://<ip-casaos>:8096)"
echo " 💻 CicalengkaGO Flutter Web     : Port 8095 (http://<ip-casaos>:8095)"
echo " 📱 WhatsApp Gateway            : Port 3005 (http://<ip-casaos>:3005/qr)"
echo "======================================================="

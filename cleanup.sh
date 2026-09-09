#!/bin/bash
# =======================================================
# Script Bersihkan Data Transaksi CicalengkaGO (Docker)
# =======================================================
# Yang DIHAPUS: orders, order_items, reviews, chats,
#   voice_calls, notifications, carts, wallet_transactions,
#   delivery_trackings, topup_logs, withdraw_requests
#
# Yang DIRESET: wallets (balance=0), delivery_men (state trip),
#   stores/products (rating & counter), coupons (usage)
#
# Yang TIDAK BERUBAH:
#   users, stores (profil), delivery_men (profil),
#   products, categories, zones, modules, banners, dll.
# =======================================================

echo "======================================================="
echo " 🧹 MEMBERSIHKAN DATA TRANSAKSI CICALENGKAGO..."
echo "======================================================="

# Jalankan script cleanup SQL di dalam container DB
# Kredensial sesuai docker-compose.yml
docker compose exec -T cicalengkago_db \
  mysql -u cicalengka_user -pcicalengka_pass cicalengkago \
  < database/cleanup_transactional_data.sql

# Cek apakah berhasil
if [ $? -eq 0 ]; then
  echo ""
  echo "✅ BERHASIL! Data transaksi sudah dibersihkan."
  echo "   - orders, order_items, reviews, chats   → DIHAPUS"
  echo "   - voice_calls, notifications, carts     → DIHAPUS"
  echo "   - wallet_transactions, topup_logs       → DIHAPUS"
  echo "   - withdraw_requests, delivery_trackings → DIHAPUS"
  echo "   - wallets balance                       → DIRESET ke 0"
  echo "   - driver state (batch/trip)             → DIRESET"
  echo "   - store/product rating & counter        → DIRESET"
  echo "   - coupon usage_count                    → DIRESET"
  echo ""
  echo "   Data yang TETAP ADA:"
  echo "   ✓ users (customer, admin, vendor, driver)"
  echo "   ✓ stores, products, categories, modules"
  echo "   ✓ zones, banners, business_settings"
  echo "   ✓ customer_addresses, coupons (data)"
  echo "======================================================="
else
  echo ""
  echo "❌ GAGAL! Coba jalankan manual:"
  echo ""
  echo "   docker compose exec cicalengkago_db mysql \\"
  echo "     -u cicalengka_user -pcicalengka_pass cicalengkago"
  echo ""
  echo "   Kemudian paste isi file database/cleanup_transactional_data.sql"
  echo ""
  echo "   Atau via phpMyAdmin di: http://<ip-casaos>:8085"
  echo "   (Login: root / rootpassword)"
  echo "======================================================="
  exit 1
fi

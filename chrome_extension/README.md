# 🧩 CicalengkaGO - GrabFood Scraper & Importer (Chrome Extension)

Ekstensi Google Chrome (Manifest V3) resmi untuk meng-scrape data toko dan menu makanan dari GrabFood lalu mengimpornya secara otomatis ke database platform **CicalengkaGO**.

---

## 🚀 Cara Menginstall Ekstensi di Google Chrome

1. Buka browser **Google Chrome**.
2. Masuk ke halaman ekstensi dengan mengetik **`chrome://extensions`** di address bar.
3. Aktifkan **Developer Mode** (Sakelar di pojok kanan atas).
4. Klik tombol **`Load unpacked`** (Muat ekstensi yang tidak dikemas).
5. Pilih folder: **`c:\xampp2\htdocs\CicalengkaGO\chrome_extension`**.
6. Ekstensi **CicalengkaGO Scraper** akan muncul dan siap digunakan!

---

## 📌 Cara Penggunaan:

1. Buka halaman restoran GrabFood di browser Chrome, misalnya:
   `https://food.grab.com/id/id/restaurant/cfc-griya-cicalengka-delivery/6-CYLEAFVTBJ6FTX`
2. Klik ikon puzzle/ekstensi **CicalengkaGO Scraper** di toolbar Chrome.
3. Pastikan URL Endpoint mengarah ke server CicalengkaGO Anda:
   - Lokal XAMPP: `http://localhost/CicalengkaGO/api/import-store`
   - Server CasaOS/Docker: `http://<IP_SERVER>/api/import-store`
4. Klik tombol **`🚀 Scrape & Impor Toko Ini`**.
5. Ekstensi akan secara otomatis membaca:
   - Nama Toko & Alamat
   - Koordinat GPS Cicalengka
   - Foto Logo & Banner Header
   - Kategori & Seluruh Daftar Menu Makanan (Nama, Deskripsi, Harga, Gambar)
6. Data langsung ter-impor secara instan ke database **CicalengkaGO**!

---

## 🛠️ Endpoint API Penerima:
- **Route**: `POST /api/import-store`
- **Controller**: `App\Controllers\ApiController::importStore()`
- **Tabel Terpengaruh**: `stores`, `users` (vendor), `store_schedules`, `categories`, `products`, `modules`.

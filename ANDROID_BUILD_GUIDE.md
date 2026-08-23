# 📱 Panduan Pembuatan Aplikasi Android CicalengkaGO (Capacitor JS)

Aplikasi Android **CicalengkaGO** telah berhasil dikonfigurasi menggunakan **Capacitor JS (Native Hybrid Runtime by Ionic)**. Dengan Capacitor JS, seluruh fitur PWA (Pelanggan, Mitra Resto, & Kurir) langsung berjalan secara **Native Android** dengan performa tinggi dan akses ke fitur hardware HP (GPS Location, Kamera, Vibrasi, & Notification).

---

## 🛠️ Modul & Plugin Native yang Terpasang

1. **`@capacitor/geolocation`**: Akses GPS fisik berpresisi tinggi untuk pelacakan kurir & lokasi pengantaran.
2. **`@capacitor/camera`**: Pengambilan foto langsung dari kamera HP untuk bukti pengantaran kurir & upload menu resto.
3. **`@capacitor/haptics` & `@capacitor/toast`**: Efek getar & notifikasi *toast* native saat ada orderan masuk.
4. **`@capacitor/app`**: Penanganan tombol *Back Button* fisik Android.
5. **`@capacitor/status-bar` & `@capacitor/splash-screen`**: Warna status bar khas CicalengkaGO (`#EE2737`) dan layar *Splash Screen* native.

---

## 🚀 Cara Menjalankan & Build APK Android

### 1. Buka Proyek di Android Studio
Jalankan perintah berikut di terminal / Command Prompt untuk membuka proyek Android secara otomatis di Android Studio:

```bash
npx cap open android
```

### 2. Jalankan Langsung ke HP Android / Emulator
Tancapkan HP Android (aktifkan USB Debugging) atau buka Emulator Android, lalu jalankan:

```bash
npx cap run android
```

### 3. Generate File `.apk` (Debug / Production Release)
Di dalam **Android Studio**:
1. Pilih menu **Build** > **Build Bundle(s) / APK(s)** > **Build APK(s)**.
2. File APK akan terbentuk di folder:
   `android/app/build/outputs/apk/debug/app-debug.apk`
3. Install file `app-debug.apk` tersebut di HP Android Anda.

---

## 🌐 Mengatur Server URL (Lokal vs Production)

Pengaturan URL utama aplikasi dikelola melalui file **`capacitor.config.json`**:

### **A. Mode Production (Live Domain)**
```json
{
  "appId": "com.cicalengkago.app",
  "appName": "CicalengkaGO",
  "webDir": "public",
  "server": {
    "url": "https://cicago.store",
    "cleartext": true
  }
}
```

### **B. Mode Pengujian Lokal (WiFi XAMPP)**
Jika ingin menguji aplikasi Android di HP yang terhubung ke WiFi lokal laptop/PC XAMPP:
1. Cek IP Laptop Anda di CMD (`ipconfig`), misal: `192.168.1.10`.
2. Ubah `server.url` pada `capacitor.config.json`:
   ```json
   "url": "http://192.168.1.10/CicalengkaGO"
   ```
3. Sync kembali dengan perintah:
   ```bash
   npx cap sync android
   ```

---

## 📌 Perintah Penting (Command Cheat-Sheet)

- **Sync Perubahan Web ke Android**:
  ```bash
  npx cap sync
  ```
- **Buka Android Studio**:
  ```bash
  npx cap open android
  ```

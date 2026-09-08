# ==========================================
# Stage 1: Build Flutter Web dari Source Code
# ==========================================
FROM ghcr.io/cirruslabs/flutter:stable AS builder

WORKDIR /app

# Ambil dependencies terlebih dahulu agar dicache oleh Docker
COPY pubspec.yaml pubspec.lock ./
RUN flutter pub get

# Salin source code project
COPY . .

# Build aplikasi web release
RUN flutter build web --release --no-wasm-dry-run --no-native-null-assertions --source-maps

# ==========================================
# Stage 2: Nginx Alpine (Hasil akhir ~25MB)
# ==========================================
FROM nginx:alpine

LABEL maintainer="CicalengkaGO"
LABEL description="CicalengkaGO Flutter Web Application"

# Bersihkan direktori default nginx
RUN rm -rf /usr/share/nginx/html/*

# Ambil hasil build dari Stage 1
COPY --from=builder /app/build/web /usr/share/nginx/html

# Salin konfigurasi Nginx SPA
COPY nginx.conf /etc/nginx/conf.d/default.conf

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]

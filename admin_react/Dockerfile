# ==========================================
# Stage 1: Build React App dari Source Code
# ==========================================
FROM node:20-alpine AS builder

WORKDIR /app

# Copy dependency definition
COPY package.json package-lock.json* ./
RUN npm install

# Copy source code dan build bundle
COPY . .
RUN npm run build

# ==========================================
# Stage 2: Nginx Alpine Super Ringan (~20MB)
# ==========================================
FROM nginx:alpine

LABEL maintainer="CicalengkaGO"
LABEL description="CicalengkaGO Super Admin React SPA"

RUN rm -rf /usr/share/nginx/html/*

# Salin hasil build dari Stage 1
COPY --from=builder /app/dist /usr/share/nginx/html

# Salin konfigurasi Nginx SPA
COPY nginx.conf /etc/nginx/conf.d/default.conf

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]

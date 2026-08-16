#!/bin/bash
set -e

# Pastikan direktori uploads tersedia dan writable untuk web server
mkdir -p /var/www/html/public/uploads/profiles \
         /var/www/html/public/uploads/stores \
         /var/www/html/public/uploads/products \
         /var/www/html/public/uploads/banners \
         /var/www/html/public/uploads/general

chown -R www-data:www-data /var/www/html/public/uploads
chmod -R 777 /var/www/html/public/uploads

# Jalankan perintah utama Docker (apache2-foreground)
exec "$@"

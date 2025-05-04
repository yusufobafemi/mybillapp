#!/bin/bash

# Navigate to your project directory
cd /home/mybifqgl/main.mybillapp.com || exit

echo "Starting deployment..."

# Pull latest code
git pull origin main

# Install PHP dependencies
composer install --no-interaction --prefer-dist --optimize-autoloader

# Run Laravel commands
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set permissions (optional but recommended)
chown -R mybifqgl:mybifqgl .
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;

echo "Deployment complete."

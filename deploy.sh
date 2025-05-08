#!/bin/bash

# Exit on any error
set -e

# Project directory
PROJECT_DIR="/home/mybifqgl/main.mybillapp.com"

# Full path to PHP and Composer
PHP="/usr/local/bin/php"              # adjust this if `which php` gives a different result
COMPOSER="/opt/cpanel/composer/bin/composer"    # replace with actual path (use `which composer` to find it)

# Set environment variables for Composer
export HOME="/home/mybifqgl"  # Set the HOME environment variable for Composer
export COMPOSER_HOME="$HOME/.composer"  # Set the COMPOSER_HOME variable for Composer

cd "$PROJECT_DIR" || exit 1

echo "🚀 Starting deployment..."

# Pull latest code from main branch
git pull origin main

# Check if composer exists
if [ ! -x "$COMPOSER" ]; then
  echo "⚠️ Composer not found at $COMPOSER"
  echo "Trying to use composer.phar instead..."
  $PHP composer.phar install --no-interaction --prefer-dist --optimize-autoloader || {
    echo "❌ Composer failed. Exiting."
    exit 1
  }
else
  # Install PHP dependencies
  $COMPOSER install --no-interaction --prefer-dist --optimize-autoloader
fi

# Laravel maintenance and optimization
$PHP artisan migrate --force
$PHP artisan config:cache
$PHP artisan route:cache
$PHP artisan view:cache

# Set permissions (if needed)
chown -R mybifqgl:mybifqgl .
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;

echo "✅ Deployment complete."

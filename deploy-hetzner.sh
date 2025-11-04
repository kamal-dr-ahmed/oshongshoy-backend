#!/bin/bash

# Hetzner Server Deployment Script for Oshongshoy Backend
# This script deploys the Laravel application to Hetzner server

set -e

# Configuration
SERVER_USER="root"
SERVER_HOST="91.98.230.133"
APP_NAME="oshongshoy"
REMOTE_DIR="/var/www/${APP_NAME}/backend"
SSH_KEY="$HOME/.ssh/id_ed25519"

echo "🚀 Starting backend deployment to Hetzner server..."

# Create deployment archive
echo "📦 Creating deployment package..."
tar -czf /tmp/${APP_NAME}-backend.tar.gz \
  --exclude='vendor' \
  --exclude='node_modules' \
  --exclude='storage/logs/*.log' \
  --exclude='.git' \
  --exclude='.env' \
  .

# Upload to server
echo "📤 Uploading to server..."
scp -i "${SSH_KEY}" /tmp/${APP_NAME}-backend.tar.gz ${SERVER_USER}@${SERVER_HOST}:/tmp/

# Deploy on server
echo "🔧 Deploying on server..."
ssh -i "${SSH_KEY}" ${SERVER_USER}@${SERVER_HOST} << 'ENDSSH'
set -e

APP_NAME="oshongshoy"
REMOTE_DIR="/var/www/${APP_NAME}/backend"

# Create directory structure
sudo mkdir -p ${REMOTE_DIR}
cd ${REMOTE_DIR}

# Backup current .env if exists
if [ -f .env ]; then
  cp .env /tmp/.env.backup
fi

# Extract archive
sudo tar -xzf /tmp/${APP_NAME}-backend.tar.gz -C ${REMOTE_DIR}

# Restore .env if backup exists
if [ -f /tmp/.env.backup ]; then
  cp /tmp/.env.backup .env
  rm /tmp/.env.backup
fi

# Install Composer dependencies
echo "📦 Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader

# Set permissions
echo "🔐 Setting permissions..."
sudo chown -R www-data:www-data ${REMOTE_DIR}
sudo chmod -R 755 ${REMOTE_DIR}
sudo chmod -R 775 ${REMOTE_DIR}/storage
sudo chmod -R 775 ${REMOTE_DIR}/bootstrap/cache

# Run migrations
echo "🗄️  Running migrations..."
php artisan migrate --force

# Clear and cache config
echo "🧹 Clearing and caching config..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart PHP-FPM
echo "🔄 Restarting PHP-FPM..."
sudo systemctl restart php8.2-fpm

# Clean up
rm /tmp/${APP_NAME}-backend.tar.gz

echo "✅ Backend deployment completed!"
ENDSSH

# Clean up local archive
rm /tmp/${APP_NAME}-backend.tar.gz

echo "✅ Backend deployment completed successfully!"

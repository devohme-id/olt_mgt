#!/bin/bash
###############################################################################
# OLT NMS — Deployment Script
# Run as oltnms user: bash docker/production/deploy.sh
###############################################################################

set -euo pipefail

APP_DIR="/var/www/oltnms"

echo "═══════════════════════════════════════════════════════"
echo "  OLT NMS — Deploying..."
echo "═══════════════════════════════════════════════════════"

cd "$APP_DIR"

# ── 1. Pull latest code ──
echo "[1/8] Pulling latest code..."
git pull origin main

# ── 2. Install PHP dependencies ──
echo "[2/8] Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# ── 3. Install & Build frontend ──
echo "[3/8] Building frontend assets..."
npm ci --production=false
npm run build

# ── 4. Run migrations ──
echo "[4/8] Running migrations..."
php artisan migrate --force

# ── 5. Cache configuration ──
echo "[5/8] Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan icons:cache 2>/dev/null || true

# ── 6. Restart queue workers ──
echo "[6/8] Restarting Horizon..."
php artisan horizon:terminate
sleep 2

# ── 7. Restart Reverb WebSocket ──
echo "[7/8] Restarting Reverb..."
sudo systemctl restart oltnms-reverb

# ── 8. Restart PHP-FPM ──
echo "[8/8] Restarting PHP-FPM..."
sudo systemctl restart php8.4-fpm

echo ""
echo "═══════════════════════════════════════════════════════"
echo "  ✅ Deployment complete!"
echo "═══════════════════════════════════════════════════════"

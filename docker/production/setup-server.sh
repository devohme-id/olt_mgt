#!/bin/bash
###############################################################################
# OLT NMS — Ubuntu Server Setup Script
# Target: Ubuntu 22.04/24.04 LTS
# Run as root: sudo bash docker/production/setup-server.sh
###############################################################################

set -euo pipefail

echo "═══════════════════════════════════════════════════════"
echo "  OLT NMS — Ubuntu Server Setup"
echo "═══════════════════════════════════════════════════════"

# ── 1. System Update ──
echo "[1/8] Updating system packages..."
apt update && apt upgrade -y

# ── 2. Install PHP 8.4 + Extensions ──
echo "[2/8] Installing PHP 8.4..."
apt install -y software-properties-common
add-apt-repository -y ppa:ondrej/php
apt update
apt install -y \
    php8.4-fpm php8.4-cli php8.4-pgsql php8.4-redis \
    php8.4-curl php8.4-xml php8.4-mbstring php8.4-zip \
    php8.4-gd php8.4-intl php8.4-bcmath php8.4-snmp \
    php8.4-readline php8.4-tokenizer php8.4-sockets

# ── 3. Install PostgreSQL 16 + TimescaleDB ──
echo "[3/8] Installing PostgreSQL 16 + TimescaleDB..."
apt install -y gnupg postgresql-common apt-transport-https lsb-release wget

# Add PostgreSQL repo
sh -c 'echo "deb http://apt.postgresql.org/pub/repos/apt $(lsb_release -cs)-pgdg main" > /etc/apt/sources.list.d/pgdg.list'
wget --quiet -O - https://www.postgresql.org/media/keys/ACCC4CF8.asc | apt-key add -

# Add TimescaleDB repo
echo "deb https://packagecloud.io/timescale/timescaledb/ubuntu/ $(lsb_release -cs) main" > /etc/apt/sources.list.d/timescaledb.list
wget --quiet -O - https://packagecloud.io/timescale/timescaledb/gpgkey | apt-key add -

apt update
apt install -y postgresql-16 timescaledb-2-postgresql-16

# Configure TimescaleDB
timescaledb-tune --quiet --yes

# Start PostgreSQL
systemctl enable postgresql
systemctl restart postgresql

# Create database and user
sudo -u postgres psql <<EOF
CREATE USER oltnms WITH PASSWORD 'CHANGE_ME_STRONG_PASSWORD';
CREATE DATABASE olt_nms OWNER oltnms;
\c olt_nms
CREATE EXTENSION IF NOT EXISTS timescaledb CASCADE;
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS pg_trgm;
EOF

echo "  ✓ PostgreSQL + TimescaleDB ready"

# ── 4. Install Redis ──
echo "[4/8] Installing Redis..."
apt install -y redis-server
sed -i 's/^# requirepass .*/requirepass CHANGE_ME_REDIS_PASSWORD/' /etc/redis/redis.conf
sed -i 's/^# maxmemory .*/maxmemory 512mb/' /etc/redis/redis.conf
sed -i 's/^# maxmemory-policy .*/maxmemory-policy allkeys-lru/' /etc/redis/redis.conf
systemctl enable redis-server
systemctl restart redis-server
echo "  ✓ Redis ready"

# ── 5. Install Nginx ──
echo "[5/8] Installing Nginx..."
apt install -y nginx
systemctl enable nginx
echo "  ✓ Nginx ready"

# ── 6. Install Node.js 22 LTS ──
echo "[6/8] Installing Node.js..."
curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
apt install -y nodejs
echo "  ✓ Node.js $(node -v) ready"

# ── 7. Install Composer ──
echo "[7/8] Installing Composer..."
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
echo "  ✓ Composer ready"

# ── 8. Install SNMP tools ──
echo "[8/8] Installing SNMP tools..."
apt install -y snmp snmp-mibs-downloader
# Enable MIB loading
sed -i 's/^mibs :$/# mibs :/' /etc/snmp/snmp.conf
echo "  ✓ SNMP ready"

# ── Create application user ──
if ! id "oltnms" &>/dev/null; then
    useradd -m -s /bin/bash oltnms
    echo "  ✓ User 'oltnms' created"
fi

# ── Create directories ──
mkdir -p /var/www/oltnms
chown oltnms:oltnms /var/www/oltnms

echo ""
echo "═══════════════════════════════════════════════════════"
echo "  ✅ Server setup complete!"
echo ""
echo "  Next steps:"
echo "  1. Clone your repo to /var/www/oltnms"
echo "  2. Copy .env.example to .env and configure"
echo "  3. Run: bash docker/production/deploy.sh"
echo "═══════════════════════════════════════════════════════"

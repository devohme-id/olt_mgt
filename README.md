# Enterprise OLT & ONU Network Management System

A high-performance, industrial-grade Web NMS designed specifically for ISP/FTTH operations to monitor, provision, and analyze Optical Line Terminals (OLT) and Optical Network Units (ONU) via SNMP.

![Dashboard Preview](dashboard_preview.png)

## Core Features
- 🚀 **Real-time Monitoring**: Polling engine built to track CPU, RAM, Temperature, and PON port statuses in real-time.
- ⚡ **ONU Provisioning**: Sync, reboot, and apply profiles to ONUs directly from the dashboard.
- 📊 **Optical Power Analytics**: Track Rx/Tx optical power for all ONUs to proactively find degraded connections.
- 🚨 **Alarm Management**: Detect Loss of Signal (LOS) and hardware faults instantly. Integrated with Telegram bot for NOC notifications.
- 🛠 **Raw OID Explorer**: Advanced diagnostic tool to run SNMP GET/WALK directly from the web interface.

## Tech Stack
* **Backend**: Laravel 11 (PHP 8.2+)
* **Frontend**: Vue 3 + Inertia.js + Tailwind CSS (Dark Mode default)
* **Database**: PostgreSQL with TimescaleDB extension for metric time-series data.
* **Real-time**: Laravel Reverb (WebSockets)
* **SNMP Engine**: FreeDSx/Snmp (Native PHP SNMP implementation, no `snmpd` dependency required).

## Development Setup (Mac/Local)

The local development environment defaults to **SQLite** for rapid iteration.

```bash
# 1. Install Dependencies
composer install
npm install

# 2. Setup Environment
cp .env.example .env
php artisan key:generate

# 3. Setup Database (SQLite)
touch database/database.sqlite
php artisan migrate --seed

# 4. Run Servers (Run in separate terminal tabs)
php artisan serve
npm run dev
php artisan reverb:start
php artisan queue:work
```

## Production Deployment
Please read the [DEPLOYMENT.md](DEPLOYMENT.md) for detailed instructions on deploying to an Ubuntu Server with PostgreSQL/TimescaleDB and Systemd Daemons.

# OLT NMS - Production Deployment Guide 🚀

This guide explains how to deploy the OLT NMS application to an Ubuntu Server for production use. It utilizes the automated scripts provided in the `docker/production` directory.

## Prerequisites
* **OS:** Ubuntu 22.04 LTS or 24.04 LTS.
* **Hardware:** Minimum Xeon 6 core, 16GB RAM (as specified).
* **Network:** Must be able to reach OLTs via SNMP (UDP 161) and ICMP (Ping).
* **User:** A user with `sudo` privileges.

## Step 1: Upload Source Code
Clone or SCP this repository to the server. The recommended path is `/var/www/olt_mgt`.

```bash
# Example via SCP from your local mac
scp -r /Users/opusoctopus/Documents/www/olt_mgt user@your-server-ip:/var/www/
```

## Step 2: Configure Environment
Copy the production environment example and fill it out:

```bash
cd /var/www/olt_mgt
cp .env.example .env
nano .env
```

**Crucial Settings to Update:**
1. `APP_ENV=production`
2. `APP_DEBUG=false`
3. `APP_URL=http://your-domain-or-ip`
4. `DB_CONNECTION=pgsql`
5. `DB_HOST=127.0.0.1` (If using the dockerized TimescaleDB)
6. `DB_PORT=5432`
7. `DB_DATABASE=olt_nms`
8. `DB_USERNAME=nms_user`
9. `DB_PASSWORD=SecurePasswordHere`
10. `TELEGRAM_BOT_TOKEN` & `TELEGRAM_CHAT_ID` (For alarms)

## Step 3: Run the Setup Script
We have provided an automated setup script that installs Docker, Node, PHP dependencies, and configures the `systemd` services for the SNMP Poller and Reverb Websockets.

```bash
cd /var/www/olt_mgt/docker/production
chmod +x setup-server.sh deploy.sh

# Run the initial setup script (Requires Sudo)
sudo ./setup-server.sh
```

**What `setup-server.sh` does:**
- Installs `docker` and `docker-compose`.
- Spins up PostgreSQL (TimescaleDB) and Redis containers.
- Installs PHP Composer & NPM dependencies.
- Builds Vite production assets.
- Runs database migrations & seeders.
- Registers and starts the Laravel Queue Worker (`olt-worker.service`).
- Registers and starts the SNMP Poller daemon (`olt-poller.service`).
- Registers and starts Laravel Reverb WebSockets (`olt-reverb.service`).

## Step 4: Web Server Configuration (Nginx)
The app is a standard Laravel 11 + Vue Inertia stack. You need Nginx to serve it.
Install Nginx and PHP-FPM:

```bash
sudo apt install nginx php8.2-fpm
```

Create a new site configuration: `/etc/nginx/sites-available/olt_mgt`

```nginx
server {
    listen 80;
    server_name your-domain-or-ip;
    root /var/www/olt_mgt/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    # Websocket proxy for Laravel Reverb
    location /app {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header Host $host;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable it:
```bash
sudo ln -s /etc/nginx/sites-available/olt_mgt /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

## Future Updates
When pushing new code changes to the server, you only need to run the deployment script:

```bash
sudo ./docker/production/deploy.sh
```
This script safely caches routes, runs migrations, restarts the queue/poller workers without downtime.

## Troubleshooting
**1. I can't reach OLTs?**
Check your server's routing or firewall. Ensure UDP port 161 is open for outbound traffic.

**2. Where are the daemon logs?**
Since we use `systemd` to manage our background workers, you can view real-time logs via `journalctl`:
- Poller: `journalctl -u olt-poller -f`
- WebSockets: `journalctl -u olt-reverb -f`
- Queue/Alarms: `journalctl -u olt-worker -f`

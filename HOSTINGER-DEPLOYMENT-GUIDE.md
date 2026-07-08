# Hostinger Deployment Guide for Teazy

This guide walks you through deploying your Laravel app to Hostinger and setting the Telegram webhook to use your Hostinger domain.

## Why Hostinger over Railway free plan?

Hostinger's shared hosting / VPS plans don't have the same execution-time limits as Railway's free tier. For a Laravel app with web scraping, Telegram bot, and scheduled tasks, Hostinger is often more reliable.

## Recommended Hostinger Plan

- **Shared Hosting Premium/Business** (cheapest, for small apps)
- **VPS Hosting** (best if you need SSH, cron jobs, and full control)

This guide covers both options.

---

# Option A: Shared Hosting (Easiest)

## Step 1: Prepare files locally

On your Windows machine, run these commands in the project folder:

```bash
# Install production dependencies
composer install --no-dev --optimize-autoloader

# Build assets (if needed)
npm install
npm run build

# Generate application key
php artisan key:generate --show
```

Copy the generated key (looks like `base64:...`).

## Step 2: Create a database

1. Log in to Hostinger hPanel
2. Go to **Websites → Manage** → **Databases → MySQL Databases**
3. Create a new database and user
4. Save the database name, username, and password

## Step 3: Upload files to Hostinger

### Method 1: File Manager (simplest)

1. In hPanel, go to **Files → File Manager**
2. Open `public_html` (or your domain folder)
3. Upload your Laravel project files using the file manager or FTP

### Method 2: FTP

Use FileZilla or any FTP client with Hostinger credentials:
- Host: your FTP host from hPanel
- Username: your FTP username
- Password: your FTP password
- Port: 21

Upload the entire project folder.

## Step 4: Set the document root to `public`

This is the most important step for Laravel security.

1. In hPanel, go to **Websites → Manage** → your domain
2. Look for **Document Root** or **Root Directory**
3. Change it from `public_html` to `public_html/public`
4. Save

If Hostinger doesn't let you change the document root, use the workaround below.

### Workaround: Move `public` contents to `public_html`

If you cannot change the document root:

1. In File Manager, navigate to `public_html`
2. Upload all files from your local `public` folder into `public_html`
3. Upload the rest of the Laravel project to a folder like `public_html/teazy-app` (one level above `public_html`)
4. Edit `public_html/index.php` and update the paths:

```php
require __DIR__ . '/teazy-app/vendor/autoload.php;
$app = require_once __DIR__ . '/teazy-app/bootstrap/app.php';
```

This is less secure but works if Hostinger restricts document root changes.

## Step 5: Configure `.env`

In File Manager, edit the `.env` file at the project root (create it from `.env.example` if missing).

```
APP_NAME=Teazy
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:your_generated_key
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_hostinger_db_name
DB_USERNAME=your_hostinger_db_user
DB_PASSWORD=your_hostinger_db_password

OPENWEATHER_API_KEY=your_new_key_here
TELEGRAM_BOT_TOKEN=your_bot_token
TELEGRAM_BOT_USERNAME=TeazyTeaBot
TELEGRAM_WEBHOOK_URL=
```

## Step 6: Run migrations and seeders

### If you have SSH access (Business plan or higher):

Connect via SSH and run:

```bash
cd ~/public_html
php artisan migrate --force
php artisan db:seed --force
```

### If no SSH access:

Use Hostinger's **Terminal** feature in hPanel, or run migrations locally and export/import the database via phpMyAdmin.

Alternatively, create a temporary PHP file in your project root:

```php
<?php
// migrate.php - delete after use
exec('php artisan migrate --force 2>&1', $output, $return);
echo implode("\n", $output);
```

Visit `https://your-domain.com/migrate.php` once, then delete the file.

## Step 7: Set permissions

In File Manager:
- `storage/` → 755
- `bootstrap/cache/` → 755
- `storage/logs/` → 775

If you have SSH:
```bash
chmod -R 755 storage
chmod -R 755 bootstrap/cache
```

## Step 8: Set Telegram webhook

After your site is live, visit this URL in your browser:

```
https://your-domain.com/api/telegram/set-webhook
```

Or run via SSH/terminal:
```bash
php artisan telegram:set-webhook
```

You should see:
```json
{"ok": true, "url": "https://your-domain.com/api/telegram/webhook"}
```

---

# Option B: VPS Hosting (Best for full control)

## Step 1: Connect to VPS

Use SSH from your terminal:
```bash
ssh root@your-vps-ip
```

## Step 2: Install prerequisites

```bash
apt update && apt upgrade -y
apt install -y nginx mysql-server php8.1-fpm php8.1-mysql php8.1-mbstring php8.1-xml php8.1-zip php8.1-curl php8.1-bcmath php8.1-ctype php8.1-json php8.1-openssl composer git
```

## Step 3: Clone your project

```bash
cd /var/www
git clone https://github.com/daphnejane015-alt/teazy.git
cd teazy
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
```

## Step 4: Configure Nginx

Create `/etc/nginx/sites-available/teazy`:

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/teazy/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable it:
```bash
ln -s /etc/nginx/sites-available/teazy /etc/nginx/sites-enabled/
nginx -t
systemctl restart nginx
```

## Step 5: Set up SSL (HTTPS)

```bash
apt install certbot python3-certbot-nginx
certbot --nginx -d your-domain.com
```

## Step 6: Configure MySQL

```bash
mysql -u root -p
```

```sql
CREATE DATABASE teazy;
CREATE USER 'teazy'@'localhost' IDENTIFIED BY 'your_db_password';
GRANT ALL PRIVILEGES ON teazy.* TO 'teazy'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

Update `.env` with the database credentials and `APP_URL=https://your-domain.com`.

## Step 7: Run migrations

```bash
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Step 8: Set Telegram webhook

```bash
php artisan telegram:set-webhook
```

Or visit:
```
https://your-domain.com/api/telegram/set-webhook
```

## Step 9: Set up cron jobs (optional)

If you have scheduled tasks (like daily quotes or reminders), add this to crontab:

```bash
crontab -e
```

Add this line:
```
* * * * * cd /var/www/teazy && php artisan schedule:run >> /dev/null 2>&1
```

---

# Telegram Webhook Troubleshooting

## Webhook not receiving messages

1. Check that your domain is HTTPS (Telegram requires HTTPS for webhooks)
2. Verify webhook URL is set:
   ```bash
   curl "https://api.telegram.org/bot<YOUR_BOT_TOKEN>/getWebhookInfo"
   ```
3. Check Hostinger error logs in hPanel

## Mixed content / HTTPS issues

Your `TrustProxies` middleware is already set to `'*'`, so this should work. If assets still load as HTTP, add this to `.env`:

```
ASSET_URL=https://your-domain.com
```

## 500 errors after deployment

1. Check `storage/logs/laravel.log`
2. Make sure `storage/` and `bootstrap/cache/` are writable
3. Verify database connection in `.env`
4. Ensure `APP_KEY` is set

---

# Important Notes

- **Shared hosting**: You may not have full SSH or cron support. Use hPanel's Terminal or the temporary PHP script method.
- **VPS**: Best for Telegram bots, web scraping, and scheduled tasks because you have full control.
- **HTTPS is required** for Telegram webhooks. Hostinger provides free SSL.
- **Webhook URL**: After deployment, it will be `https://your-domain.com/api/telegram/webhook`. You can set it automatically via the `/api/telegram/set-webhook` endpoint.


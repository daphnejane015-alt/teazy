# Railway Deployment Guide

## Prerequisites
- Railway account (https://railway.app)
- GitHub repository with this project
- Telegram bot token from @BotFather
- OpenWeatherMap API key (https://openweathermap.org/api)

## Important: Keep Secrets Out of Git
Your `.env` file is already listed in `.gitignore` and should **never** be committed. Make sure it stays untracked before pushing:

```bash
git status
```

You should not see `.env` staged.

## Step 1: Push code to GitHub

```bash
git add .
git commit -m "Prepare for Railway deployment"
git push origin main
```

## Step 2: Create a Railway Project

1. Go to https://railway.app and log in.
2. Click **New Project** → **Deploy from GitHub repo**.
3. Select your repository.
4. Railway will read `railway.toml` and build the project with PHP 8.1 + Node.js.

## Step 3: Add a MySQL Database

1. In your Railway project, click **New** → **Database** → **Add MySQL**.
2. Railway will generate connection variables automatically.
3. Wait for the database to finish provisioning before deploying.

## Step 4: Configure Environment Variables

In the Railway dashboard, go to your **app service** → **Variables**. Add the variables below. Railway can interpolate other variables using `${{VARIABLE_NAME}}` syntax.

| Variable | Suggested Value | Notes |
|---|---|---|
| `APP_NAME` | `Teazy` | |
| `APP_ENV` | `production` | |
| `APP_DEBUG` | `false` | |
| `APP_KEY` | *(your key)* | Copy from your local `.env` or run `php artisan key:generate --show` |
| `APP_URL` | `https://${{RAILWAY_PUBLIC_DOMAIN}}` | Auto-updates with your Railway domain |
| `DB_CONNECTION` | `mysql` | |
| `DATABASE_URL` | `${{MYSQL_URL}}` | Provided by Railway MySQL |
| `CACHE_DRIVER` | `file` | |
| `SESSION_DRIVER` | `file` | |
| `QUEUE_CONNECTION` | `sync` | |
| `OPENWEATHER_API_KEY` | *(your key)* | |
| `TELEGRAM_BOT_TOKEN` | *(your token)* | From @BotFather |
| `TELEGRAM_BOT_USERNAME` | `Teazy_bot` | Your bot's username **without** the `@` |
| `TELEGRAM_WEBHOOK_URL` | `https://${{RAILWAY_PUBLIC_DOMAIN}}/api/telegram/webhook` | |
| `VITE_APP_NAME` | `Teazy` | |

You do **not** need to set `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, or `DB_PASSWORD` manually when using `DATABASE_URL`.

## Step 5: Deploy

1. Click **Deploy** in Railway (or push a new commit to trigger a deploy).
2. Wait for the build to complete (~3–5 minutes).
   - Railway will run `npm install`, `npm run build`, and `composer install`.
3. On every deploy, the `preDeployCommand` will automatically run migrations and cache config/views.
4. Railway will provide a domain like `https://your-app.railway.app`.

## Step 6: Set the Telegram Webhook

After the app is running, tell Telegram where to send updates.

**Option A: Using Railway Console (Recommended)**
1. In the Railway dashboard, open your app service.
2. Click the **Console** tab.
3. Run:

```bash
php artisan telegram:set-webhook
```

**Option B: Using the HTTP endpoint**

Visit this URL in your browser (replace the domain):

```
https://your-railway-domain/api/telegram/set-webhook
```

**Option C: Manual cURL**

```bash
curl "https://api.telegram.org/bot<YOUR_BOT_TOKEN>/setWebhook?url=https://your-railway-domain/api/telegram/webhook"
```

## Step 7: Verify

1. Open your Railway domain in a browser and confirm the welcome page loads.
2. Test your bot in Telegram: send `/start` or `/recommend`.
3. Check Railway logs if something fails.
4. Verify the webhook is set:

```bash
curl "https://api.telegram.org/bot<YOUR_BOT_TOKEN>/getWebhookInfo"
```

## How It Works

- `railway.toml` tells Railway to use Nixpacks, install PHP 8.1 + Node.js, build the Vite assets, and run the Laravel app.
- Migrations are run automatically on every deploy via `preDeployCommand`, so the database stays up to date.
- The scheduler worker runs continuously via `[deploy.workers.scheduler]`, so `php artisan schedule:run` is executed every minute.
- `TrustProxies` is already configured to trust Railway's load balancers (`protected $proxies = '*'`), so HTTPS and generated URLs work correctly.
- `route:cache` is intentionally **not** used because some routes (e.g., `/`) use closures.

## Troubleshooting

- **Build fails / npm errors**: Check the Railway build logs. Make sure `package.json` and `vite.config.js` are committed and not corrupted.
- **Database connection error**: Confirm that the MySQL service is provisioned and `DATABASE_URL=${{MYSQL_URL}}` is set in the app service variables.
- **Migrations fail**: Ensure the MySQL service is ready before the first deploy. You can redeploy after the database is provisioned.
- **Webhook not working**: Make sure `TELEGRAM_BOT_TOKEN` and `TELEGRAM_WEBHOOK_URL` are correct and the domain is HTTPS.
- **502 / app not responding**: Check the deploy logs and confirm `APP_KEY` is set.

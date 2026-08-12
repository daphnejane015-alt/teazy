# Teazy — Tea Scraping & Recommendation System

Teazy is a Laravel-based web application that scrapes tea data from multiple online sources, stores it in a database, and recommends teas to users using a content-based filtering engine (flavor, caffeine, health benefit, and weather scoring). It also includes a Telegram bot for conversational recommendations and Gemini AI integration for auto-generated tea descriptions.

## Features

- **Automated tea scraping** with a multi-tier fallback strategy (direct sources → alternative sources → curated knowledge base), retry logic, and HTTP response caching.
- **Content-based recommendation engine** that scores teas by flavor similarity (with fuzzy/Levenshtein matching), caffeine level, health benefit keyword matching, and current weather conditions.
- **Weather-aware suggestions** using the OpenWeatherMap API.
- **AI-generated tea descriptions** using Google Gemini.
- **Telegram bot integration** for browsing recommendations via chat.
- **Admin dashboard** for managing teas, users, ratings, and triggering scrapes.

## Tech Stack

- **Backend:** PHP 8.1+, Laravel 10, Eloquent ORM, MySQL
- **Frontend:** Blade, Alpine.js, Tailwind CSS, Vite
- **Scraping:** Goutte, Symfony HttpClient, Guzzle
- **External APIs:** OpenWeatherMap, Google Gemini, Telegram Bot API

## Prerequisites

- PHP >= 8.1 with extensions: `pdo_mysql`, `mbstring`, `xml`, `curl`, `openssl`, `bcmath`, `fileinfo`
- Composer
- Node.js + npm
- MySQL (or compatible database)
- [ngrok](https://ngrok.com/) (for exposing your local server to the internet, required for the Telegram webhook)

## Installation

```bash
# Clone the repository
git clone https://github.com/daphnejane015-alt/teazy.git
cd teazy

# Install PHP dependencies
composer install

# Install frontend dependencies
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure your database in .env, then run migrations
php artisan migrate

# Build frontend assets
npm run build
```

## Environment Configuration

Edit `.env` and set the following:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=teazy
DB_USERNAME=root
DB_PASSWORD=

# OpenWeatherMap (https://openweathermap.org/api)
OPENWEATHER_API_KEY=your_openweather_api_key_here

# Google Gemini (https://ai.google.dev/)
GEMINI_API_KEY=your_gemini_api_key_here
GEMINI_MODEL=gemini-flash-latest

# Telegram Bot (create via @BotFather on Telegram)
TELEGRAM_BOT_TOKEN=your_bot_token_here
TELEGRAM_BOT_USERNAME=YourBotUsername
TELEGRAM_WEBHOOK_URL=
```

## Running the App Locally

```bash
# Start the Laravel dev server
php artisan serve

# In a separate terminal, run Vite for frontend hot-reload (optional in dev)
npm run dev
```

The app will be available at `http://127.0.0.1:8000`.

## Deploying with ngrok (for Telegram Webhook)

Since Telegram requires a public HTTPS URL to deliver webhook updates, use ngrok to tunnel your local server:

```bash
# 1. Start the Laravel server
php artisan serve

# 2. In a separate terminal, start an ngrok tunnel to the same port
ngrok http 8000
```

ngrok will give you a public HTTPS URL, e.g. `https://abcd1234.ngrok-free.app`.

```bash
# 3. Update your .env with the ngrok URL
TELEGRAM_WEBHOOK_URL=https://abcd1234.ngrok-free.app/api/telegram/webhook

# 4. Register the webhook with Telegram
php artisan telegram:set-webhook
```

Your bot is now reachable at `https://t.me/YourBotUsername` and Telegram will forward all messages to your local app through the ngrok tunnel.

> **Note:** Free ngrok URLs change every time you restart the tunnel. You'll need to re-run `php artisan telegram:set-webhook` (or hit `POST /api/telegram/set-webhook`) whenever the URL changes.

## Scraping Commands

```bash
# Rich scraper used by the admin "Force Scrape" button
php artisan scrape:tea-data
php artisan scrape:tea-data --fresh   # clears HTTP cache, then merges fresh data

# Fallback-heavy scraper (used by the scheduler)
php artisan scrape:robust-tea
php artisan scrape:robust-tea --force --delay=10

# Cache management
php artisan scrape:cache status
php artisan scrape:cache clear
php artisan scrape:cache clear-expired
```

## Task Scheduler

The scraping automation relies on Laravel's scheduler (`app/Console/Kernel.php`). To run it locally, either:

- **Windows:** Set up a Task Scheduler job to run `php artisan schedule:run` every minute, or
- **Linux/macOS:** Add a cron entry:

```
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

## Project Structure

```
app/Console/Commands/     Scraping & utility Artisan commands
app/Services/             RecommendationService, WeatherService, GeminiService, TelegramBotService
app/Http/Controllers/     Web/API controllers (Admin, User, Telegram)
resources/views/          Blade templates
routes/web.php            Server-rendered web routes
routes/api.php            REST API routes (Telegram webhook, Sanctum user)
```

## License

This project is open-sourced under the [MIT license](https://opensource.org/licenses/MIT).

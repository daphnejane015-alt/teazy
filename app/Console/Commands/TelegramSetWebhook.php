<?php

namespace App\Console\Commands;

use App\Services\TelegramBotService;
use Illuminate\Console\Command;

class TelegramSetWebhook extends Command
{
    protected $signature = 'telegram:set-webhook {--url=}';

    protected $description = 'Set the Telegram bot webhook URL';

    public function handle(TelegramBotService $bot): int
    {
        $url = $this->option('url') ?: config('services.telegram.webhook_url');

        if (empty($url)) {
            $this->error('No webhook URL configured. Set TELEGRAM_WEBHOOK_URL or use --url=https://...');
            return Command::FAILURE;
        }

        $ok = $bot->setWebhook($url);

        if ($ok) {
            $this->info("Telegram webhook set to: {$url}");
            return Command::SUCCESS;
        }

        $this->error('Failed to set Telegram webhook.');
        return Command::FAILURE;
    }
}

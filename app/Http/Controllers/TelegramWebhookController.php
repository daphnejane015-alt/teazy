<?php

namespace App\Http\Controllers;

use App\Services\TelegramBotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    public function __construct(private TelegramBotService $bot) {}

    public function handle(Request $request)
    {
        $update = $request->all();

        Log::debug('Telegram webhook received', ['update' => $update]);

        $this->bot->handleUpdate($update);

        return response()->json(['ok' => true]);
    }

    public function setWebhook(Request $request)
    {
        $url = $request->input('url', config('services.telegram.webhook_url'));

        if (empty($url)) {
            return response()->json([
                'ok' => false,
                'error' => 'No webhook URL configured. Set TELEGRAM_WEBHOOK_URL or pass ?url=...',
            ], 422);
        }

        $ok = $this->bot->setWebhook($url);

        return response()->json(['ok' => $ok, 'url' => $url]);
    }
}

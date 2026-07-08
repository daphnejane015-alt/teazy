<?php

namespace App\Services;

use App\Models\User;
use App\Models\Tea;
use App\Models\Preference;
use App\Models\TelegramChat;
use App\Models\TelegramConversation;
use App\Models\Favourite;
use App\Models\Rating;
use App\Http\Controllers\RatingController;
use App\Services\WeatherService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TelegramBotService
{
    private const STEP_IDLE = 'idle';
    private const STEP_AWAITING_EMAIL = 'awaiting_email';
    private const STEP_FLAVOR = 'flavor';
    private const STEP_CAFFEINE = 'caffeine';
    private const STEP_HEALTH_GOAL = 'health_goal';
    private const STEP_POST_RECOMMENDATION = 'post_recommendation';
    private const STEP_AWAITING_RATING = 'awaiting_rating';
    private const STEP_WEATHER = 'weather';

    // Synced with the website Find Tea form (resources/views/user/find-tea.blade.php)
    private const FLAVOR_OPTIONS = [
        'Floral' => 'floral',
        'Fruity' => 'fruity',
        'Earthy' => 'earthy',
        'Sweet' => 'sweet',
        'Bitter' => 'bitter',
        'Minty' => 'minty',
        'Any' => 'any',
    ];

    private const CAFFEINE_OPTIONS = [
        'Low' => 'low',
        'Medium' => 'medium',
        'High' => 'high',
        'Caffeine Free' => 'caffeine_free',
    ];

    private const HEALTH_OPTIONS = [
        'Relaxation/Calming' => 'relax_calm',
        'Digestion' => 'digest',
        'Stress Relief' => 'stress',
        'Weight Loss' => 'weight_loss',
        'Blood Circulation' => 'blood_circulation',
        'Body Relief' => 'body_relief',
    ];

    private const ACTION_FAVOURITE = '❤️ Add to Favourites';
    private const ACTION_RATE = '⭐ Rate this Tea';
    private const ACTION_NEW = '🔄 New Recommendation';

    private const WEATHER_SKIP = '🚫 Skip weather';
    private const WEATHER_CITY_OPTIONS = ['Kuala Lumpur', 'Penang', 'Johor Bahru', 'Ipoh', 'Kota Kinabalu'];

    private RecommendationService $recommendationService;

    public function __construct(RecommendationService $recommendationService)
    {
        $this->recommendationService = $recommendationService;
    }

    public function handleUpdate(array $update): void
    {
        try {
            if (empty($update['message'])) {
                return;
            }

            $message = $update['message'];
            $chatId = (string) ($message['chat']['id'] ?? '');
            $text = trim($message['text'] ?? '');

            if (empty($chatId) || empty($text)) {
                return;
            }

            $chatInfo = $this->resolveChatInfo($message);
            $chat = $this->findOrCreateChat($chatId, $chatInfo);

            if (Str::startsWith($text, '/')) {
                $this->handleCommand($chatId, $text, $chatInfo, $chat);
                return;
            }

            $this->handleMessage($chatId, $text, $chat);
        } catch (\Throwable $e) {
            Log::error('Telegram bot error', [
                'update' => $update,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if (isset($chatId) && !empty($chatId)) {
                $this->sendMessage($chatId, 'Sorry, something went wrong. Please try again later.');
            }
        }
    }

    private function resolveChatInfo(array $message): array
    {
        $from = $message['from'] ?? [];

        return [
            'username' => $from['username'] ?? null,
            'first_name' => $from['first_name'] ?? null,
            'last_name' => $from['last_name'] ?? null,
        ];
    }

    private function findOrCreateChat(string $chatId, array $info): TelegramChat
    {
        $chat = TelegramChat::where('chat_id', $chatId)->first();

        if (!$chat) {
            $chat = TelegramChat::create([
                'chat_id' => $chatId,
                'username' => $info['username'] ?? null,
                'first_name' => $info['first_name'] ?? null,
                'last_name' => $info['last_name'] ?? null,
            ]);
        }

        return $chat;
    }

    private function getConversation(string $chatId, ?int $userId = null): TelegramConversation
    {
        $conversation = TelegramConversation::where('chat_id', $chatId)->first();

        if (!$conversation) {
            $conversation = TelegramConversation::create([
                'chat_id' => $chatId,
                'user_id' => $userId,
                'step' => self::STEP_IDLE,
            ]);
        }

        return $conversation;
    }

    private function resetConversation(TelegramConversation $conversation): void
    {
        $conversation->update([
            'step' => self::STEP_IDLE,
            'flavor' => null,
            'caffeine' => null,
            'health_goal' => null,
            'context' => null,
        ]);
    }

    private function handleCommand(string $chatId, string $text, array $info, TelegramChat $chat): void
    {
        $parts = explode(' ', $text, 2);
        $command = strtolower($this->normalizeCommand($parts[0]));
        $argument = $parts[1] ?? '';

        switch ($command) {
            case '/start':
                $this->resetConversation($this->getConversation($chatId, $chat->user_id));
                $this->sendWelcome($chatId, $chat);
                break;

            case '/recommend':
                $this->startRecommendation($chatId);
                break;

            case '/favorites':
            case '/favourites':
                $this->showFavorites($chatId, $chat);
                break;

            case '/toptea':
                $this->showTopTeas($chatId);
                break;

            case '/rate':
                $this->askForRating($chatId, $this->getConversation($chatId, $chat->user_id), $chat);
                break;

            case '/link':
                if (!empty($argument)) {
                    $this->linkAccount($chatId, trim($argument), $chat);
                } else {
                    $this->askForEmail($chatId, $chat);
                }
                break;

            default:
                $this->sendMessage(
                    $chatId,
                    "I don't recognize that command. Try /start, /recommend, /favorites, or /toptea."
                );
        }
    }

    private function sendWelcome(string $chatId, TelegramChat $chat): void
    {
        $name = $chat->first_name ?: 'there';
        $linked = $chat->user_id ? true : false;

        $text = "Hello, {$name}! I'm your Tea Recommendation Assistant.";
        $text .= "\n\nI can help you discover the perfect tea based on your taste, caffeine preference, and health goals.";

        if ($linked) {
            $text .= "\n\nYour account is linked. You can use /favorites to see saved teas.";
        } else {
            $text .= "\n\nTo view your saved favourites from the website, link your account with:\n`/link your@email.com`";
        }

        $text .= "\n\nLet's get started! Use /recommend to find your tea.";
        $text .= "\nAfter a recommendation you can add it to favourites or rate it (1-5 stars).";

        $this->sendMessage($chatId, $text);
    }

    private function startRecommendation(string $chatId): void
    {
        $chat = $this->findOrCreateChat($chatId, []);
        $conversation = $this->getConversation($chatId, $chat->user_id);
        $conversation->update([
            'step' => self::STEP_FLAVOR,
            'flavor' => null,
            'caffeine' => null,
            'health_goal' => null,
            'context' => null,
        ]);

        $this->sendMessage(
            $chatId,
            "What flavor do you prefer?",
            $this->buildKeyboard(array_keys(self::FLAVOR_OPTIONS))
        );
    }

    private function handleMessage(string $chatId, string $text, TelegramChat $chat): void
    {
        $conversation = $this->getConversation($chatId, $chat->user_id);

        switch ($conversation->step) {
            case self::STEP_FLAVOR:
                $this->processFlavor($chatId, $text, $conversation);
                break;

            case self::STEP_CAFFEINE:
                $this->processCaffeine($chatId, $text, $conversation);
                break;

            case self::STEP_HEALTH_GOAL:
                $this->processHealthGoal($chatId, $text, $conversation, $chat);
                break;

            case self::STEP_WEATHER:
                $this->processWeather($chatId, $text, $conversation, $chat);
                break;

            case self::STEP_POST_RECOMMENDATION:
                $this->processPostRecommendationAction($chatId, $text, $conversation, $chat);
                break;

            case self::STEP_AWAITING_RATING:
                $this->processRating($chatId, $text, $conversation, $chat);
                break;

            case self::STEP_AWAITING_EMAIL:
                $this->linkAccount($chatId, $text, $chat);
                break;

            case self::STEP_IDLE:
            default:
                $this->sendMessage(
                    $chatId,
                    "I'm not sure what you mean. Type /recommend to find a tea, or /start to see options."
                );
        }
    }

    private function processFlavor(string $chatId, string $text, TelegramConversation $conversation): void
    {
        $flavor = $this->matchOption($text, self::FLAVOR_OPTIONS);

        if (!$flavor) {
            $this->sendMessage(
                $chatId,
                "Please choose one of the listed flavors.",
                $this->buildKeyboard(array_keys(self::FLAVOR_OPTIONS))
            );
            return;
        }

        $conversation->update([
            'step' => self::STEP_CAFFEINE,
            'flavor' => $flavor,
        ]);

        $this->sendMessage(
            $chatId,
            "What caffeine level do you prefer?",
            $this->buildKeyboard(array_keys(self::CAFFEINE_OPTIONS))
        );
    }

    private function processCaffeine(string $chatId, string $text, TelegramConversation $conversation): void
    {
        $caffeine = $this->matchOption($text, self::CAFFEINE_OPTIONS);

        if (!$caffeine) {
            $this->sendMessage(
                $chatId,
                "Please choose Low, Medium, or High.",
                $this->buildKeyboard(array_keys(self::CAFFEINE_OPTIONS))
            );
            return;
        }

        $conversation->update([
            'step' => self::STEP_HEALTH_GOAL,
            'caffeine' => $caffeine,
        ]);

        $this->sendMessage(
            $chatId,
            "What is your health goal?",
            $this->buildKeyboard(array_keys(self::HEALTH_OPTIONS))
        );
    }

    private function processHealthGoal(string $chatId, string $text, TelegramConversation $conversation, TelegramChat $chat): void
    {
        $healthGoal = $this->matchOption($text, self::HEALTH_OPTIONS);

        if (!$healthGoal) {
            $this->sendMessage(
                $chatId,
                "Please choose one of the listed health goals.",
                $this->buildKeyboard(array_keys(self::HEALTH_OPTIONS))
            );
            return;
        }

        $conversation->update([
            'step' => self::STEP_WEATHER,
            'health_goal' => $healthGoal,
        ]);

        $this->askForWeather($chatId);
    }

    private function askForWeather(string $chatId): void
    {
        $options = array_merge(self::WEATHER_CITY_OPTIONS, [self::WEATHER_SKIP]);

        $this->sendMessage(
            $chatId,
            "Last step! Want weather-based recommendations?\n\nSend your city (or pick one below), or tap \"" . self::WEATHER_SKIP . "\" to use only your taste preferences.",
            $this->buildKeyboard($options)
        );
    }

    private function processWeather(string $chatId, string $text, TelegramConversation $conversation, TelegramChat $chat): void
    {
        $normalized = strtolower(trim($text));
        $context = $conversation->context ?? [];

        if ($text === self::WEATHER_SKIP || Str::contains($normalized, ['skip', 'no weather', 'none'])) {
            $context['weather'] = false;
            $context['city'] = null;
            $conversation->update(['context' => $context]);
            $this->generateRecommendations($chatId, $conversation, $chat);
            return;
        }

        $city = trim($text);

        // Fetch & store live weather so the scoring engine can read it via Weather::forCity()
        try {
            app(WeatherService::class)->getCurrentWeather($city);
        } catch (\Throwable $e) {
            Log::warning('Telegram weather fetch failed', [
                'city' => $city,
                'error' => $e->getMessage(),
            ]);
        }

        $context['weather'] = true;
        $context['city'] = $city;
        $conversation->update(['context' => $context]);

        $this->generateRecommendations($chatId, $conversation, $chat);
    }

    private function matchOption(string $text, array $options): ?string
    {
        $normalized = strtolower(trim($text));

        foreach ($options as $label => $value) {
            if ($normalized === strtolower($label) || $normalized === $value) {
                return $value;
            }
        }

        return null;
    }

    private function generateRecommendations(string $chatId, TelegramConversation $conversation, TelegramChat $chat): void
    {
        try {
            $user = $this->buildUserForRecommendation($conversation, $chat);
            $recommendations = $this->recommendationService->recommend($user);

            if ($recommendations->isEmpty()) {
                $this->sendMessage(
                    $chatId,
                    "I couldn't find any matching teas. Try different preferences with /recommend."
                );
                $this->resetConversation($conversation);
                return;
            }

            $top = $recommendations->first();

            $context = $conversation->context ?? [];
            $context['last_tea_id'] = $top['tea']->id;

            $conversation->update([
                'step' => self::STEP_POST_RECOMMENDATION,
                'context' => $context,
            ]);

            $this->sendRecommendation($chatId, $top, $conversation, $chat);
        } catch (\Throwable $e) {
            Log::error('Telegram recommendation error', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->sendMessage(
                $chatId,
                "I couldn't generate recommendations right now. Please try /recommend again."
            );

            $this->resetConversation($conversation);
        }
    }

    private function buildUserForRecommendation(TelegramConversation $conversation, TelegramChat $chat): User
    {
        $context = $conversation->context ?? [];
        $weatherEnabled = !empty($context['weather']) && !empty($context['city']);

        $attributes = [
            'preferred_flavor' => $conversation->flavor,
            'preferred_caffeine' => $conversation->caffeine,
            'health_goal' => $conversation->health_goal,
            'weather_based_recommendations' => $weatherEnabled,
            'city' => $weatherEnabled ? $context['city'] : null,
            'weather_preference' => 'auto',
        ];

        if ($chat->user_id) {
            $user = User::find($chat->user_id);

            if ($user) {
                Preference::updateOrCreate(
                    ['user_id' => $user->id],
                    $attributes
                );

                $user->refresh();
                $user->load('preference');

                return $user;
            }
        }

        $user = new User();
        $user->setRelation('preference', new Preference($attributes));

        return $user;
    }

    private function sendRecommendation(string $chatId, array $recommendation, TelegramConversation $conversation, TelegramChat $chat): void
    {
        $tea = $recommendation['tea'];
        $text = $this->buildRecommendationText($recommendation, $conversation);

        if ($tea->image && $this->looksLikeUrl($tea->image)) {
            $this->sendPhoto($chatId, $tea->image, $text);
        } else {
            if ($tea->image) {
                $text .= "\n\n[Tea image]({$tea->image})";
            }

            $this->sendMessage($chatId, $text);
        }

        $prompt = $chat->user_id
            ? "What would you like to do next?"
            : "What would you like to do next?\n\n_Tip: link your account with_ `/link your@email.com` _to save favourites and ratings._";

        $this->sendMessage(
            $chatId,
            $prompt,
            $this->buildKeyboard([self::ACTION_FAVOURITE, self::ACTION_RATE, self::ACTION_NEW])
        );
    }

    private function processPostRecommendationAction(string $chatId, string $text, TelegramConversation $conversation, TelegramChat $chat): void
    {
        $normalized = strtolower(trim($text));

        if (Str::contains($normalized, ['favourite', 'favorite', '❤'])) {
            $this->addFavourite($chatId, $conversation, $chat);
            return;
        }

        if (Str::contains($normalized, ['rate', 'star', '⭐'])) {
            $this->askForRating($chatId, $conversation, $chat);
            return;
        }

        if (Str::contains($normalized, ['new', 'another', 'recommend', '🔄'])) {
            $this->startRecommendation($chatId);
            return;
        }

        $this->sendMessage(
            $chatId,
            "Please choose an option below.",
            $this->buildKeyboard([self::ACTION_FAVOURITE, self::ACTION_RATE, self::ACTION_NEW])
        );
    }

    private function getLastRecommendedTea(TelegramConversation $conversation): ?Tea
    {
        $context = $conversation->context ?? [];
        $teaId = $context['last_tea_id'] ?? null;

        return $teaId ? Tea::find($teaId) : null;
    }

    private function addFavourite(string $chatId, TelegramConversation $conversation, TelegramChat $chat): void
    {
        if (!$chat->user_id) {
            $this->sendMessage(
                $chatId,
                "To save favourites, link your account first:\n`/link your@email.com`"
            );
            return;
        }

        $tea = $this->getLastRecommendedTea($conversation);

        if (!$tea) {
            $this->sendMessage($chatId, "I don't have a tea to save yet. Use /recommend first.");
            return;
        }

        $alreadySaved = Favourite::where('user_id', $chat->user_id)
            ->where('tea_id', $tea->id)
            ->exists();

        if ($alreadySaved) {
            $this->sendMessage(
                $chatId,
                "*{$tea->name}* is already in your favourites.",
                $this->buildKeyboard([self::ACTION_RATE, self::ACTION_NEW])
            );
            return;
        }

        Favourite::create([
            'user_id' => $chat->user_id,
            'tea_id' => $tea->id,
        ]);

        $this->sendMessage(
            $chatId,
            "Added *{$tea->name}* to your favourites! View them anytime with /favorites.",
            $this->buildKeyboard([self::ACTION_RATE, self::ACTION_NEW])
        );
    }

    private function askForRating(string $chatId, TelegramConversation $conversation, TelegramChat $chat): void
    {
        if (!$chat->user_id) {
            $this->sendMessage(
                $chatId,
                "To rate teas, link your account first:\n`/link your@email.com`"
            );
            return;
        }

        $tea = $this->getLastRecommendedTea($conversation);

        if (!$tea) {
            $this->sendMessage($chatId, "I don't have a tea to rate yet. Use /recommend first.");
            return;
        }

        $conversation->update(['step' => self::STEP_AWAITING_RATING]);

        $this->sendMessage(
            $chatId,
            "How would you rate *{$tea->name}*? Choose 1 to 5 stars.",
            $this->buildKeyboard(['1', '2', '3', '4', '5'])
        );
    }

    private function processRating(string $chatId, string $text, TelegramConversation $conversation, TelegramChat $chat): void
    {
        $rating = (int) preg_replace('/[^0-9]/', '', $text);

        if ($rating < 1 || $rating > 5) {
            $this->sendMessage(
                $chatId,
                "Please choose a rating from 1 to 5.",
                $this->buildKeyboard(['1', '2', '3', '4', '5'])
            );
            return;
        }

        if (!$chat->user_id) {
            $this->sendMessage(
                $chatId,
                "To rate teas, link your account first:\n`/link your@email.com`"
            );
            $this->resetConversation($conversation);
            return;
        }

        $tea = $this->getLastRecommendedTea($conversation);

        if (!$tea) {
            $this->sendMessage($chatId, "I don't have a tea to rate yet. Use /recommend first.");
            $this->resetConversation($conversation);
            return;
        }

        $existing = Rating::where('user_id', $chat->user_id)
            ->where('tea_id', $tea->id)
            ->first();

        Rating::updateOrCreate(
            ['user_id' => $chat->user_id, 'tea_id' => $tea->id],
            ['rating' => $rating]
        );

        $verb = $existing ? 'updated' : 'submitted';
        $stars = str_repeat('⭐', $rating);

        $conversation->update(['step' => self::STEP_POST_RECOMMENDATION]);

        $this->sendMessage(
            $chatId,
            "Your rating for *{$tea->name}* was {$verb}: {$stars} ({$rating}/5).",
            $this->buildKeyboard([self::ACTION_FAVOURITE, self::ACTION_NEW])
        );
    }

    private function buildRecommendationText(array $recommendation, TelegramConversation $conversation): string
    {
        $tea = $recommendation['tea'];
        $flavorLabel = $this->labelForValue($conversation->flavor, self::FLAVOR_OPTIONS);
        $caffeineLabel = $this->labelForValue($conversation->caffeine, self::CAFFEINE_OPTIONS);
        $healthLabel = $this->labelForValue($conversation->health_goal, self::HEALTH_OPTIONS);

        $text = "Recommended Tea:\n*{$tea->name}*";

        if ($tea->health_benefit) {
            $text .= "\n\nHealth Benefits:\n{$tea->health_benefit}";
        }

        $text .= "\n\n📊 Match Score Breakdown:";
        $text .= "\nFlavor: " . round($recommendation['flavor_score'] * 100) . "%";
        $text .= "\nCaffeine: " . round($recommendation['caffeine_score'] * 100) . "%";
        $text .= "\nHealth: " . round($recommendation['health_score'] * 100) . "%";
        $text .= "\n\nOverall Match: " . round($recommendation['contextual_score'] ?? $recommendation['score']) . "/100";

        $text .= "\n\nWhy Recommended:";
        $text .= "\nMatches your {$flavorLabel} preference";
        $text .= "\n{$caffeineLabel} caffeine level";
        $text .= "\nSupports {$healthLabel}";

        $context = $conversation->context ?? [];
        if (!empty($context['weather']) && !empty($context['city'])) {
            $text .= "\nSuited to current weather in {$context['city']}";
        }

        return $text;
    }

    private function labelForValue(?string $value, array $options): string
    {
        if (empty($value)) {
            return 'your preference';
        }

        foreach ($options as $label => $optionValue) {
            if ($optionValue === $value) {
                return $label;
            }
        }

        return $value;
    }

    private function normalizeCommand(string $command): string
    {
        if (Str::contains($command, '@')) {
            return explode('@', $command)[0];
        }

        return $command;
    }

    private function showFavorites(string $chatId, TelegramChat $chat): void
    {
        if (!$chat->user_id) {
            $this->sendMessage(
                $chatId,
                "Your Telegram account isn't linked to a website account. Use `/link your@email.com` to connect."
            );
            return;
        }

        $user = User::with('favourites')->find($chat->user_id);

        if (!$user || $user->favourites->isEmpty()) {
            $this->sendMessage(
                $chatId,
                "You don't have any favourite teas saved yet. Find teas on the website and favourite them."
            );
            return;
        }

        $text = "Your favourite teas:\n\n";

        foreach ($user->favourites as $index => $tea) {
            $text .= ($index + 1) . ". *{$tea->name}*\n";
            $text .= "   Flavor: {$tea->flavor} | Caffeine: {$tea->caffeine_level}\n";

            if ($tea->health_benefit) {
                $text .= "   Benefits: " . Str::limit($tea->health_benefit, 80) . "\n";
            }

            $text .= "\n";
        }

        $this->sendMessage($chatId, $text);
    }

    private function showTopTeas(string $chatId): void
    {
        try {
            $topTeas = RatingController::getTopRatedTeas(5);

            if ($topTeas->isEmpty()) {
                $this->sendMessage($chatId, "No top-rated teas are available right now.");
                return;
            }

            $text = "Top-rated teas:\n\n";

            foreach ($topTeas as $index => $tea) {
                $text .= ($index + 1) . ". *{$tea->name}*\n";
                $text .= "   Flavor: {$tea->flavor} | Caffeine: {$tea->caffeine_level}\n";

                $rating = $tea->averageRating();
                $count = $tea->totalRatings();

                $text .= "   Rating: " . number_format($rating, 1) . "/5 ({$count} votes)\n\n";
            }

            $this->sendMessage($chatId, $text);
        } catch (\Throwable $e) {
            Log::error('Telegram top teas error', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);

            $this->sendMessage($chatId, "Unable to load top teas. Please try again.");
        }
    }

    private function askForEmail(string $chatId, TelegramChat $chat): void
    {
        $conversation = $this->getConversation($chatId, $chat->user_id);
        $conversation->update(['step' => self::STEP_AWAITING_EMAIL]);

        $this->sendMessage(
            $chatId,
            "Please send me the email address you use on the website.\n\nOr cancel with /start."
        );
    }

    private function linkAccount(string $chatId, string $email, TelegramChat $chat): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->sendMessage($chatId, "That doesn't look like a valid email. Please try again.");
            return;
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->sendMessage(
                $chatId,
                "I couldn't find an account with that email. Please check and try again."
            );
            return;
        }

        $chat->update([
            'user_id' => $user->id,
            'linked_at' => now(),
        ]);

        TelegramConversation::updateOrCreate(
            ['chat_id' => $chatId],
            [
                'user_id' => $user->id,
                'step' => self::STEP_IDLE,
            ]
        );

        $this->sendMessage(
            $chatId,
            "Great! Your Telegram account is now linked to *{$user->email}*.\n\nYou can use /favorites to see your saved teas."
        );
    }

    private function sendMessage(string $chatId, string $text, ?array $keyboard = null): void
    {
        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
        ];

        if ($keyboard) {
            $payload['reply_markup'] = json_encode($keyboard);
        }

        $response = Http::post($this->baseUrl() . '/sendMessage', $payload);

        if (!$response->successful()) {
            Log::warning('Telegram sendMessage failed', [
                'chat_id' => $chatId,
                'response' => $response->body(),
            ]);
        }
    }

    private function sendPhoto(string $chatId, string $photoUrl, string $caption, ?array $keyboard = null): void
    {
        $payload = [
            'chat_id' => $chatId,
            'photo' => $photoUrl,
            'caption' => $caption,
            'parse_mode' => 'Markdown',
        ];

        if ($keyboard) {
            $payload['reply_markup'] = json_encode($keyboard);
        }

        $response = Http::post($this->baseUrl() . '/sendPhoto', $payload);

        if (!$response->successful()) {
            Log::warning('Telegram sendPhoto failed, falling back to text', [
                'chat_id' => $chatId,
                'response' => $response->body(),
            ]);

            $text = $caption . "\n\n[Tea image]({$photoUrl})";
            $this->sendMessage($chatId, $text, $keyboard);
        }
    }

    private function buildKeyboard(array $options): array
    {
        $rows = [];

        foreach ($options as $option) {
            $rows[] = [['text' => $option]];
        }

        return [
            'keyboard' => $rows,
            'resize_keyboard' => true,
            'one_time_keyboard' => true,
        ];
    }

    private function looksLikeUrl(string $value): bool
    {
        return Str::startsWith($value, ['http://', 'https://']);
    }

    private function token(): string
    {
        $token = config('services.telegram.bot_token');

        if (empty($token)) {
            throw new \RuntimeException('Telegram bot token is not configured.');
        }

        return $token;
    }

    private function baseUrl(): string
    {
        return 'https://api.telegram.org/bot' . $this->token();
    }

    public function setWebhook(string $url): bool
    {
        $response = Http::post($this->baseUrl() . '/setWebhook', [
            'url' => $url,
            'allowed_updates' => ['message'],
        ]);

        return $response->successful();
    }

    public function deleteWebhook(): bool
    {
        $response = Http::post($this->baseUrl() . '/deleteWebhook');

        return $response->successful();
    }

    public function getMe(): ?array
    {
        $response = Http::get($this->baseUrl() . '/getMe');

        if ($response->successful()) {
            return $response->json('result');
        }

        return null;
    }
}

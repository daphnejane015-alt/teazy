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
    private const STEP_STATE = 'state';
    private const STEP_CITY = 'city';
    private const STEP_WEATHER_PREF = 'weather_pref';

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

    private const MALAYSIAN_STATES = [
        'Kuala Lumpur'     => ['Kuala Lumpur'],
        'Selangor'         => ['Shah Alam', 'Petaling Jaya', 'Subang Jaya', 'Klang', 'Kajang', 'Puchong', 'Damansara', 'Rawang', 'Ampang', 'Cheras', 'Bangi', 'Putrajaya', 'Sunway'],
        'Penang'           => ['George Town', 'Bayan Lepas', 'Bukit Mertajam'],
        'Johor'            => ['Johor Bahru', 'Batu Pahat', 'Muar', 'Kulai', 'Skudai', 'Kluang'],
        'Perak'            => ['Ipoh', 'Taiping'],
        'Negeri Sembilan'  => ['Seremban', 'Port Dickson'],
        'Kedah'            => ['Alor Setar', 'Sungai Petani'],
        'Kelantan'         => ['Kota Bharu'],
        'Terengganu'       => ['Kuala Terengganu'],
        'Sarawak'          => ['Kuching', 'Miri', 'Sibu', 'Bintulu'],
        'Sabah'            => ['Kota Kinabalu', 'Sandakan', 'Tawau'],
        'Malacca'          => ['Malacca'],
        'Pahang'           => ['Kuantan'],
        'Labuan'           => ['Labuan'],
    ];

    private const WEATHER_PREFS = [
        'Auto (current weather)'    => 'auto',
        'Hot & Humid (28-35°C)'     => 'malaysian_hot_humid',
        'Rainy / Monsoon'           => 'malaysian_rainy',
        'Haze Season'               => 'malaysian_haze',
        'Cool Morning (18-22°C)'    => 'malaysian_cool_morning',
        'Afternoon Heat (32-38°C)'  => 'malaysian_afternoon_heat',
        'Thunderstorm'              => 'malaysian_thunderstorm',
        'Air-Cond Indoors'          => 'malaysian_aircond',
    ];

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
                try {
                    $this->handleCommand($chatId, $text, $chatInfo, $chat);
                } catch (\Throwable $e) {
                    Log::error('Telegram handleCommand error', ['cmd' => $text, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                    $this->sendMessage($chatId, "⚠️ That didn't work as expected. Please try again or use /start to return to the menu.");
                }
                return;
            }

            try {
                $this->handleMessage($chatId, $text, $chat);
            } catch (\Throwable $e) {
                Log::error('Telegram handleMessage error', ['text' => $text, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                $this->sendMessage($chatId, "⚠️ Something didn't go through. Tap /start to reset and try again.", $this->buildKeyboard(['/start', '/recommend']));
            }
        } catch (\Throwable $e) {
            Log::error('Telegram bot unhandled error', [
                'update' => $update,
                'error'  => $e->getMessage(),
                'trace'  => $e->getTraceAsString(),
            ]);
            // Silent — individual handlers send their own contextual error messages
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
                'chat_id'    => $chatId,
                'username'   => $info['username'] ?? null,
                'first_name' => $info['first_name'] ?? null,
                'last_name'  => $info['last_name'] ?? null,
            ]);

            // Auto-link: try to match by Telegram username if user has one
            if (!empty($info['username'])) {
                $user = User::where('username', $info['username'])->first();
                if ($user) {
                    $chat->update(['user_id' => $user->id, 'linked_at' => now()]);
                    TelegramConversation::updateOrCreate(
                        ['chat_id' => $chatId],
                        ['user_id' => $user->id, 'step' => self::STEP_IDLE]
                    );
                }
            }
        }

        return $chat->fresh();
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
        $parts    = explode(' ', $text, 2);
        $command  = strtolower($this->normalizeCommand($parts[0]));
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
                    "I don't recognise that command. Here's what I can do:"
                    . "\n/recommend — Get a tea recommendation"
                    . "\n/toptea — Top-rated teas"
                    . "\n/favorites — Your saved favourites"
                    . "\n/rate — Rate the last recommended tea"
                    . "\n/link — Link your website account"
                    . "\n/start — Show full menu"
                );
        }
    }

    private function sendWelcome(string $chatId, TelegramChat $chat): void
    {
        $name   = $chat->first_name ?: 'there';
        $linked = (bool) $chat->user_id;

        $text  = "👋 Hello, {$name}! I'm *Teazy* — your Malaysian Tea Recommendation Bot.";
        $text .= "\n\n*What I can do:*";
        $text .= "\n/recommend — Get a personalised tea recommendation";
        $text .= "\n/toptea — See the top-rated teas";
        $text .= "\n/favorites — View your saved favourite teas";
        $text .= "\n/rate — Rate the last recommended tea";
        $text .= "\n/link — Link your Teazy website account";
        $text .= "\n/start — Show this menu again";

        if ($linked) {
            $user  = User::find($chat->user_id);
            $email = $user ? $user->email : 'your account';
            $text .= "\n\n✅ Account linked as *{$email}*. All your favourites and ratings sync with the website.";
        } else {
            $text .= "\n\n⚠️ Your account is *not linked* yet.";
            $text .= "\nLink it to sync favourites & ratings:\n`/link your@email.com`";
        }

        $text .= "\n\nTap /recommend to get started! 🍵";

        $this->sendMessage($chatId, $text,
            $this->buildKeyboard(['/recommend', '/toptea', '/favorites'])
        );
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

            case self::STEP_STATE:
                $this->processState($chatId, $text, $conversation, $chat);
                break;

            case self::STEP_CITY:
                $this->processCity($chatId, $text, $conversation, $chat);
                break;

            case self::STEP_WEATHER_PREF:
                $this->processWeatherPref($chatId, $text, $conversation, $chat);
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
                    "Not sure what to do? Here's what I can help with:",
                    $this->buildKeyboard(['/recommend', '/toptea', '/favorites'])
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

        try {
            $conversation->update([
                'step'   => self::STEP_CAFFEINE,
                'flavor' => $flavor,
            ]);

            $this->sendMessage(
                $chatId,
                "What caffeine level do you prefer?",
                $this->buildKeyboard(array_keys(self::CAFFEINE_OPTIONS))
            );
        } catch (\Throwable $e) {
            Log::error('Telegram processFlavor error', ['error' => $e->getMessage()]);
            $this->sendMessage($chatId, "Please choose your flavor preference:",
                $this->buildKeyboard(array_keys(self::FLAVOR_OPTIONS)));
        }
    }

    private function processCaffeine(string $chatId, string $text, TelegramConversation $conversation): void
    {
        $caffeine = $this->matchOption($text, self::CAFFEINE_OPTIONS);

        if (!$caffeine) {
            $this->sendMessage(
                $chatId,
                "Please choose one of the caffeine options:",
                $this->buildKeyboard(array_keys(self::CAFFEINE_OPTIONS))
            );
            return;
        }

        try {
            $conversation->update([
                'step'     => self::STEP_HEALTH_GOAL,
                'caffeine' => $caffeine,
            ]);

            $this->sendMessage(
                $chatId,
                "What is your health goal?",
                $this->buildKeyboard(array_keys(self::HEALTH_OPTIONS))
            );
        } catch (\Throwable $e) {
            Log::error('Telegram processCaffeine error', ['error' => $e->getMessage()]);
            $this->sendMessage($chatId, "Please choose your caffeine level:",
                $this->buildKeyboard(array_keys(self::CAFFEINE_OPTIONS)));
        }
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

        try {
            $conversation->update([
                'step'        => self::STEP_STATE,
                'health_goal' => $healthGoal,
            ]);

            $this->askForState($chatId);
        } catch (\Throwable $e) {
            Log::error('Telegram processHealthGoal error', ['error' => $e->getMessage()]);
            $this->sendMessage($chatId, "Please choose your health goal:",
                $this->buildKeyboard(array_keys(self::HEALTH_OPTIONS)));
        }
    }

    private function askForState(string $chatId): void
    {
        $states = array_keys(self::MALAYSIAN_STATES);
        // 2 per row to keep the keyboard compact and avoid Telegram payload limits
        $this->sendMessage(
            $chatId,
            "Want weather-based recommendations? 🌤️\n\nChoose your *state* in Malaysia, or tap \"" . self::WEATHER_SKIP . "\" to skip.",
            $this->buildKeyboard(array_merge($states, [self::WEATHER_SKIP]), 2)
        );
    }

    private function processState(string $chatId, string $text, TelegramConversation $conversation, TelegramChat $chat): void
    {
        $normalized = strtolower(trim($text));
        $context    = $conversation->context ?? [];

        if ($text === self::WEATHER_SKIP || Str::contains($normalized, ['skip', 'no weather', 'none'])) {
            $context['weather'] = false;
            $context['city']    = null;
            $conversation->update(['context' => $context]);
            $this->generateRecommendations($chatId, $conversation, $chat);
            return;
        }

        // Match state
        $matchedState = null;
        foreach (array_keys(self::MALAYSIAN_STATES) as $state) {
            if (strtolower($state) === $normalized) {
                $matchedState = $state;
                break;
            }
        }

        if (!$matchedState) {
            $this->sendMessage(
                $chatId,
                "Please choose a state from the list.",
                $this->buildKeyboard(array_merge(array_keys(self::MALAYSIAN_STATES), [self::WEATHER_SKIP]), 2)
            );
            return;
        }

        try {
            $context['state'] = $matchedState;
            $conversation->update(['step' => self::STEP_CITY, 'context' => $context]);

            $cities = array_merge(self::MALAYSIAN_STATES[$matchedState], [self::WEATHER_SKIP]);
            $this->sendMessage(
                $chatId,
                "Great! Now choose your *city* in {$matchedState}:",
                $this->buildKeyboard($cities, 2)
            );
        } catch (\Throwable $e) {
            Log::error('Telegram processState error', ['error' => $e->getMessage()]);
            $this->askForState($chatId);
        }
    }

    private function processCity(string $chatId, string $text, TelegramConversation $conversation, TelegramChat $chat): void
    {
        $normalized = strtolower(trim($text));
        $context    = $conversation->context ?? [];

        if ($text === self::WEATHER_SKIP || Str::contains($normalized, ['skip'])) {
            $context['weather'] = false;
            $context['city']    = null;
            $conversation->update(['context' => $context]);
            $this->generateRecommendations($chatId, $conversation, $chat);
            return;
        }

        $state  = $context['state'] ?? null;
        $cities = $state ? (self::MALAYSIAN_STATES[$state] ?? []) : [];

        $matchedCity = null;
        foreach ($cities as $city) {
            if (strtolower($city) === $normalized) {
                $matchedCity = $city;
                break;
            }
        }

        if (!$matchedCity) {
            $this->sendMessage(
                $chatId,
                "Please choose a city from the list.",
                $this->buildKeyboard(array_merge($cities, [self::WEATHER_SKIP]), 2)
            );
            return;
        }

        try {
            $context['city']    = $matchedCity;
            $context['weather'] = true;
            $conversation->update(['step' => self::STEP_WEATHER_PREF, 'context' => $context]);

            $this->askForWeatherPref($chatId);
        } catch (\Throwable $e) {
            Log::error('Telegram processCity error', ['error' => $e->getMessage()]);
            $this->sendMessage($chatId, "Please choose your city:",
                $this->buildKeyboard(array_merge($cities, [self::WEATHER_SKIP]), 2));
        }
    }

    private function askForWeatherPref(string $chatId): void
    {
        $this->sendMessage(
            $chatId,
            "Last step! Choose your *weather preference* or let the bot use current weather automatically:",
            $this->buildKeyboard(array_keys(self::WEATHER_PREFS))
        );
    }

    private function processWeatherPref(string $chatId, string $text, TelegramConversation $conversation, TelegramChat $chat): void
    {
        $pref    = $this->matchOption($text, self::WEATHER_PREFS);
        $context = $conversation->context ?? [];

        if (!$pref) {
            $this->sendMessage(
                $chatId,
                "Please choose a weather preference from the list.",
                $this->buildKeyboard(array_keys(self::WEATHER_PREFS))
            );
            return;
        }

        $context['weather_pref'] = $pref;
        $conversation->update(['context' => $context]);

        $city = $context['city'] ?? null;
        if ($city) {
            try {
                app(WeatherService::class)->getCurrentWeather($city, 'MY', false);
            } catch (\Throwable $e) {
                Log::warning('Telegram weather fetch failed', ['city' => $city, 'error' => $e->getMessage()]);
            }
        }

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
        $context        = $conversation->context ?? [];
        $weatherEnabled = !empty($context['weather']) && !empty($context['city']);

        $attributes = [
            'preferred_flavor'              => $conversation->flavor,
            'preferred_caffeine'            => $conversation->caffeine,
            'health_goal'                   => $conversation->health_goal,
            'weather_based_recommendations' => $weatherEnabled,
            'city'                          => $weatherEnabled ? $context['city'] : null,
            'weather_preference'            => $context['weather_pref'] ?? 'auto',
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
        $tea  = $recommendation['tea'];
        $text = $this->buildRecommendationText($recommendation, $conversation);

        if ($tea->image && $this->looksLikeUrl($tea->image)) {
            $this->sendPhoto($chatId, $tea->image, $text);
        } else {
            $this->sendMessage($chatId, $text);
        }

        if ($chat->user_id) {
            $prompt = "What would you like to do next?";
        } else {
            $prompt = "What would you like to do next?"
                . "\n\n🔗 *Tip:* Link your Teazy account to save favourites & ratings:"
                . "\n`/link your@email.com`"
                . "\n\nYou can still get a new recommendation without linking.";
        }

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
            "Please tap one of the buttons below to continue.",
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
        // Guards first — no try/catch needed, these are safe reads
        if (!$chat->user_id) {
            $context = $conversation->context ?? [];
            $context['pending_action'] = 'favourite';
            $conversation->update(['step' => self::STEP_AWAITING_EMAIL, 'context' => $context]);
            $this->sendMessage(
                $chatId,
                "❤️ To save this tea as a favourite, I need to link your Teazy account first."
                . "\n\nPlease send me the *email address* you use on the Teazy website:"
                . "\n_(Or tap /start to cancel)_"
            );
            return;
        }

        $tea = $this->getLastRecommendedTea($conversation);

        if (!$tea) {
            $this->sendMessage(
                $chatId,
                "Hmm, I don't have a tea to save yet. Use /recommend to get a suggestion first!",
                $this->buildKeyboard(['/recommend'])
            );
            return;
        }

        $alreadySaved = Favourite::where('user_id', $chat->user_id)
            ->where('tea_id', $tea->id)
            ->exists();

        if ($alreadySaved) {
            $this->sendMessage(
                $chatId,
                "✅ *{$tea->name}* is already in your favourites! View them with /favorites.",
                $this->buildKeyboard([self::ACTION_RATE, self::ACTION_NEW])
            );
            return;
        }

        // Only wrap the DB write + success message
        try {
            Favourite::create([
                'user_id' => $chat->user_id,
                'tea_id'  => $tea->id,
            ]);

            $this->sendMessage(
                $chatId,
                "❤️ Added *{$tea->name}* to your favourites!\nView all your favourites anytime with /favorites.",
                $this->buildKeyboard([self::ACTION_RATE, self::ACTION_NEW])
            );
        } catch (\Throwable $e) {
            Log::error('Telegram addFavourite error', ['error' => $e->getMessage()]);
            $this->sendMessage($chatId, "❤️ Couldn't save that favourite right now. Please try again.", $this->buildKeyboard([self::ACTION_FAVOURITE, self::ACTION_NEW]));
        }
    }

    private function askForRating(string $chatId, TelegramConversation $conversation, TelegramChat $chat): void
    {
        if (!$chat->user_id) {
            $context = $conversation->context ?? [];
            $context['pending_action'] = 'rate';
            $conversation->update(['step' => self::STEP_AWAITING_EMAIL, 'context' => $context]);
            $this->sendMessage(
                $chatId,
                "⭐ To rate this tea, I need to link your Teazy account first."
                . "\n\nPlease send me the *email address* you use on the Teazy website:"
                . "\n_(Or tap /start to cancel)_"
            );
            return;
        }

        $tea = $this->getLastRecommendedTea($conversation);

        if (!$tea) {
            $this->sendMessage(
                $chatId,
                "Hmm, I don't have a tea to rate yet. Use /recommend to get a suggestion first!",
                $this->buildKeyboard(['/recommend'])
            );
            return;
        }

        $conversation->update(['step' => self::STEP_AWAITING_RATING]);

        $this->sendMessage(
            $chatId,
            "⭐ How would you rate *{$tea->name}*?\nTap a number from 1 (poor) to 5 (excellent):",
            $this->buildKeyboard(['1 ⭐', '2 ⭐⭐', '3 ⭐⭐⭐', '4 ⭐⭐⭐⭐', '5 ⭐⭐⭐⭐⭐'])
        );
    }

    private function processRating(string $chatId, string $text, TelegramConversation $conversation, TelegramChat $chat): void
    {
        $rating = (int) preg_replace('/[^0-9]/', '', $text);

        if ($rating < 1 || $rating > 5) {
            $this->sendMessage(
                $chatId,
                "Please tap one of the star buttons to give your rating:",
                $this->buildKeyboard(['1 ⭐', '2 ⭐⭐', '3 ⭐⭐⭐', '4 ⭐⭐⭐⭐', '5 ⭐⭐⭐⭐⭐'])
            );
            return;
        }

        $tea = $this->getLastRecommendedTea($conversation);

        if (!$tea) {
            $this->sendMessage(
                $chatId,
                "Hmm, I lost track of which tea to rate. Use /recommend to get a new suggestion!",
                $this->buildKeyboard(['/recommend'])
            );
            $this->resetConversation($conversation);
            return;
        }

        // Only wrap the DB write + success message
        try {
            $existing = Rating::where('user_id', $chat->user_id)
                ->where('tea_id', $tea->id)
                ->first();

            Rating::updateOrCreate(
                ['user_id' => $chat->user_id, 'tea_id' => $tea->id],
                ['rating' => $rating]
            );

            $verb  = $existing ? 'updated to' : 'saved as';
            $stars = str_repeat('⭐', $rating);

            $conversation->update(['step' => self::STEP_POST_RECOMMENDATION]);

            $this->sendMessage(
                $chatId,
                "🍵 Your rating for *{$tea->name}* was {$verb} {$stars} ({$rating}/5). Thank you!\nThis also updates your rating on the Teazy website.",
                $this->buildKeyboard([self::ACTION_FAVOURITE, self::ACTION_NEW])
            );
        } catch (\Throwable $e) {
            Log::error('Telegram processRating error', ['error' => $e->getMessage()]);
            $this->sendMessage($chatId, "⭐ Couldn't save that rating right now. Please try again.", $this->buildKeyboard([self::ACTION_RATE, self::ACTION_NEW]));
        }
    }

    private function buildRecommendationText(array $recommendation, TelegramConversation $conversation): string
    {
        $tea           = $recommendation['tea'];
        $flavorLabel   = $this->labelForValue($conversation->flavor, self::FLAVOR_OPTIONS);
        $caffeineLabel = $this->labelForValue($conversation->caffeine, self::CAFFEINE_OPTIONS);
        $healthLabel   = $this->labelForValue($conversation->health_goal, self::HEALTH_OPTIONS);
        $context       = $conversation->context ?? [];

        $text  = "🍵 *Your Tea Recommendation*";
        $text .= "\n\n*{$tea->name}*";
        $text .= "\nFlavor: {$tea->flavor} | Caffeine: {$tea->caffeine_level}";

        if ($tea->health_benefit) {
            $text .= "\n\n💚 *Health Benefits*\n" . Str::limit($tea->health_benefit, 200);
        }

        $text .= "\n\n📊 *Match Score*: " . round($recommendation['contextual_score'] ?? $recommendation['score']) . "/100";
        $text .= "\n• Flavor match: " . round($recommendation['flavor_score'] * 100) . "%";
        $text .= "\n• Caffeine match: " . round($recommendation['caffeine_score'] * 100) . "%";
        $text .= "\n• Health match: " . round($recommendation['health_score'] * 100) . "%";

        $text .= "\n\n✅ *Why this tea?*";
        $text .= "\n• {$flavorLabel} flavor preference";
        $text .= "\n• {$caffeineLabel} caffeine level";
        $text .= "\n• Supports {$healthLabel}";

        if (!empty($context['weather']) && !empty($context['city'])) {
            $prefLabel = $this->labelForValue($context['weather_pref'] ?? 'auto', self::WEATHER_PREFS);
            $text .= "\n• Weather-matched for {$context['city']} ({$prefLabel})";
        }

        $text .= "\n\n🛒 *Shop this tea:*";
        $text .= "\n[🟠 Buy on Shopee](" . $tea->shopeeShopUrl() . ")";
        $text .= "  |  [🔵 Buy on Lazada](" . $tea->lazadaShopUrl() . ")";

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
        try {
        if (!$chat->user_id) {
            $conversation = $this->getConversation($chatId, null);
            $conversation->update(['step' => self::STEP_AWAITING_EMAIL]);
            $this->sendMessage(
                $chatId,
                "❤️ To view your favourites, I need to link your Teazy account first."
                . "\n\nPlease send me the *email address* you use on the Teazy website:"
                . "\n_(Or tap /start to cancel)_"
            );
            return;
        }

        $user = User::with('favourites')->find($chat->user_id);

        if (!$user || $user->favourites->isEmpty()) {
            $this->sendMessage(
                $chatId,
                "🍵 You don't have any favourite teas saved yet."
                . "\n\nUse /recommend to find teas, then tap \"" . self::ACTION_FAVOURITE . "\" to save them!",
                $this->buildKeyboard(['/recommend', '/toptea'])
            );
            return;
        }

        $count = $user->favourites->count();
        $text  = "❤️ *Your Favourite Teas* ({$count})\n\n";

        foreach ($user->favourites as $index => $tea) {
            $text .= ($index + 1) . ". *{$tea->name}*\n";
            $text .= "   Flavor: {$tea->flavor} | Caffeine: {$tea->caffeine_level}\n";

            if ($tea->health_benefit) {
                $text .= "   " . Str::limit($tea->health_benefit, 60) . "\n";
            }

            $text .= "   🛒 [Shopee](" . $tea->shopeeShopUrl() . ") | [Lazada](" . $tea->lazadaShopUrl() . ")\n";
            $text .= "\n";
        }

        $this->sendMessage($chatId, $text, $this->buildKeyboard(['/recommend', '/toptea']));
        } catch (\Throwable $e) {
            Log::error('Telegram showFavorites error', ['error' => $e->getMessage()]);
            $this->sendMessage($chatId, "❤️ Couldn't load your favourites right now. Please try again in a moment.", $this->buildKeyboard(['/recommend']));
        }
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
            $this->sendMessage(
                $chatId,
                "❌ That doesn't look like a valid email address. Please try again, or tap /start to cancel."
            );
            return;
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->sendMessage(
                $chatId,
                "❌ I couldn't find a Teazy account with that email."
                . "\n\nDouble-check the address and try again, or tap /start to cancel."
            );
            return;
        }

        $chat->update([
            'user_id'   => $user->id,
            'linked_at' => now(),
        ]);

        // Preserve existing context (last_tea_id, pending_action) while updating user_id
        $existing       = TelegramConversation::where('chat_id', $chatId)->first();
        $existingContext = $existing ? ($existing->context ?? []) : [];
        $pendingAction   = $existingContext['pending_action'] ?? null;
        unset($existingContext['pending_action']);

        $conversation = TelegramConversation::updateOrCreate(
            ['chat_id' => $chatId],
            ['user_id' => $user->id, 'step' => self::STEP_POST_RECOMMENDATION, 'context' => $existingContext]
        );

        $this->sendMessage(
            $chatId,
            "✅ *Account linked!* Welcome, {$user->name}!"
            . "\n\nYour Telegram is now connected to *{$user->email}*."
            . "\nAll your favourites and ratings sync with the Teazy website."
        );

        $chat->refresh();

        // Resume whatever the user was trying to do before linking
        if ($pendingAction === 'favourite') {
            $this->addFavourite($chatId, $conversation->fresh(), $chat);
        } elseif ($pendingAction === 'rate') {
            $this->askForRating($chatId, $conversation->fresh(), $chat);
        } else {
            $conversation->update(['step' => self::STEP_IDLE]);
            $this->sendMessage(
                $chatId,
                "What would you like to do?",
                $this->buildKeyboard(['/recommend', '/favorites', '/toptea'])
            );
        }
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

        $response = $this->telegramPost('/sendMessage', $payload);

        if (!$response || !$response->successful()) {
            Log::warning('Telegram sendMessage failed', [
                'chat_id'  => $chatId,
                'response' => $response ? $response->body() : 'no response (network error)',
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

        $response = $this->telegramPost('/sendPhoto', $payload);

        if (!$response || !$response->successful()) {
            Log::warning('Telegram sendPhoto failed, falling back to text', [
                'chat_id'  => $chatId,
                'response' => $response ? $response->body() : 'no response (network error)',
            ]);

            $text = $caption . "\n\n[Tea image]({$photoUrl})";
            $this->sendMessage($chatId, $text, $keyboard);
        }
    }

    /**
     * Resilient POST to the Telegram API.
     * Handles transient network failures common on local/Windows setups
     * (e.g. cURL error 56 "connection reset") by forcing IPv4, setting
     * sane timeouts, and retrying a few times before giving up.
     */
    private function telegramPost(string $endpoint, array $payload): ?\Illuminate\Http\Client\Response
    {
        try {
            return Http::asForm()
                ->connectTimeout(10)
                ->timeout(20)
                ->retry(3, 800, function ($exception) {
                    // Retry only on connection-level errors, not HTTP 4xx/5xx
                    return $exception instanceof \Illuminate\Http\Client\ConnectionException;
                }, throw: false)
                ->withOptions([
                    'curl' => [
                        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                    ],
                ])
                ->post($this->baseUrl() . $endpoint, $payload);
        } catch (\Throwable $e) {
            Log::warning('Telegram API request failed after retries', [
                'endpoint' => $endpoint,
                'error'    => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function buildKeyboard(array $options, int $perRow = 1): array
    {
        $rows   = [];
        $chunks = array_chunk(array_values($options), $perRow);

        foreach ($chunks as $chunk) {
            $rows[] = array_map(fn($o) => ['text' => $o], $chunk);
        }

        return [
            'keyboard'          => $rows,
            'resize_keyboard'   => true,
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

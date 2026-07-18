<?php

namespace App\Services;

use App\Models\Tea;
use App\Models\TeaAiDescription;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    private ?string $apiKey;
    private string $model;
    private string $baseUrl;
    private ?int $lastFailureStatus = null;
    private array $lastSources = [];

    public function __construct(private RecommendationService $recommendationService)
    {
        $this->apiKey = config('services.gemini.api_key');
        $configuredModel = config('services.gemini.model', 'gemini-flash-latest');
        $this->model = in_array($configuredModel, [
            'gemini-1.5-flash',
            'gemini-2.0-flash',
            'gemini-2.5-flash',
        ], true)
            ? 'gemini-flash-latest'
            : $configuredModel;
        $this->baseUrl = rtrim(config('services.gemini.base_url'), '/');
    }

    /**
     * Whether the Gemini integration is configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    public function availabilityMessage(): string
    {
        return match ($this->lastFailureStatus) {
            429 => 'AI summaries are temporarily unavailable because the Gemini API quota has been reached.',
            503 => 'AI summaries are temporarily busy. Please try again shortly.',
            default => 'The AI summary could not be generated right now. Please try again shortly.',
        };
    }

    /**
     * Return the AI description for a tea, generating and persisting it if missing.
     * Returns null only when generation fails and nothing is cached.
     */
    public function descriptionFor(Tea $tea, bool $force = false, ?User $user = null): ?string
    {
        if ($user) {
            $signature = $this->preferenceSignature($user);
            $cached = TeaAiDescription::where('tea_id', $tea->id)
                ->where('user_id', $user->id)
                ->first();

            if (!$force && $cached && $cached->preference_signature === $signature) {
                return $cached->description;
            }

            $generated = $this->generate($tea, $user);

            if ($generated) {
                TeaAiDescription::updateOrCreate(
                    ['tea_id' => $tea->id, 'user_id' => $user->id],
                    [
                        'description' => $generated,
                        'sources' => $this->lastSources,
                        'preference_signature' => $signature,
                        'generated_at' => now(),
                    ]
                );

                return $generated;
            }

            return $cached?->description;
        }

        if (!$force && !empty($tea->ai_description)) {
            return $tea->ai_description;
        }

        $generated = $this->generate($tea);

        if ($generated) {
            $tea->forceFill([
                'ai_description' => $generated,
                'ai_description_generated_at' => now(),
            ])->save();

            return $generated;
        }

        return $tea->ai_description;
    }

    /**
     * Call the Gemini API and return a friendly description string, or null on failure.
     */
    public function generate(Tea $tea, ?User $user = null): ?string
    {
        if (!$this->isConfigured()) {
            Log::warning('GeminiService: API key not configured.');
            return null;
        }

        try {
            $this->lastFailureStatus = null;
            $this->lastSources = [];
            $prompt = $this->buildPrompt($tea, $user);
            $useSearchGrounding = $user
                && $this->recommendationService->healthMatchScore($tea, $user->preference?->health_goal) === 0.0;

            $payload = [
                'contents' => [[
                    'parts' => [['text' => $prompt]],
                ]],
                'generationConfig' => [
                    'temperature' => 0.8,
                    'maxOutputTokens' => 300,
                ],
            ];

            if ($useSearchGrounding) {
                $payload['tools'] = [['google_search' => (object) []]];
            }

            $models = array_values(array_unique([
                $this->model,
                'gemini-3.1-flash-lite',
            ]));

            $groundingFallbackUsed = false;

            foreach ($models as $model) {
                // Retry a couple of times on transient "high demand" (503) responses
                // before moving on to the next fallback model.
                for ($attempt = 1; $attempt <= 2; $attempt++) {
                    $response = Http::timeout(20)
                        ->withHeaders(['Content-Type' => 'application/json'])
                        ->post("{$this->baseUrl}/models/{$model}:generateContent?key={$this->apiKey}", $payload);

                    if ($response->successful()) {
                        break;
                    }

                    $this->lastFailureStatus = $response->status();
                    Log::warning("GeminiService API request failed for {$model}", [
                        'status' => $response->status(),
                        'attempt' => $attempt,
                        'tea_id' => $tea->id,
                    ]);

                    if ($this->lastFailureStatus === 429) {
                        if ($useSearchGrounding && !$groundingFallbackUsed) {
                            unset($payload['tools']);
                            $payload['contents'][0]['parts'][0]['text'] = $this->buildPrompt($tea, $user, false);
                            $groundingFallbackUsed = true;
                            continue;
                        }

                        return null;
                    }

                    if ($this->lastFailureStatus === 503 && $attempt < 2) {
                        usleep(750_000);
                        continue;
                    }

                    break;
                }

                if (!$response->successful()) {
                    continue;
                }

                $text = data_get($response->json(), 'candidates.0.content.parts.0.text');

                if ($text) {
                    $this->lastSources = collect(data_get($response->json(), 'candidates.0.groundingMetadata.groundingChunks', []))
                        ->map(fn (array $chunk) => data_get($chunk, 'web'))
                        ->filter(fn ($source) => filled(data_get($source, 'uri')))
                        ->map(fn (array $source) => [
                            'title' => data_get($source, 'title', 'Source'),
                            'url' => data_get($source, 'uri'),
                        ])
                        ->unique('url')
                        ->take(3)
                        ->values()
                        ->all();

                    return trim($text);
                }

                Log::warning("GeminiService returned an empty response for {$model}", [
                    'tea_id' => $tea->id,
                ]);
            }

            return null;
        } catch (\Throwable $e) {
            Log::error('GeminiService exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate actionable tea uses/recipes as a structured JSON array.
     */
    public function usesFor(Tea $tea): ?array
    {
        if (!$this->isConfigured()) {
            Log::warning('GeminiService: API key not configured.');
            return null;
        }

        try {
            $this->lastFailureStatus = null;
            $prompt = $this->buildUsesPrompt($tea);

            $payload = [
                'contents' => [[
                    'parts' => [['text' => $prompt]],
                ]],
                'generationConfig' => [
                    'temperature' => 0.8,
                    'maxOutputTokens' => 1200,
                    'responseMimeType' => 'application/json',
                ],
            ];

            $models = array_values(array_unique([
                $this->model,
                'gemini-3.1-flash-lite',
            ]));

            foreach ($models as $model) {
                for ($attempt = 1; $attempt <= 2; $attempt++) {
                    $response = Http::timeout(20)
                        ->withHeaders(['Content-Type' => 'application/json'])
                        ->post("{$this->baseUrl}/models/{$model}:generateContent?key={$this->apiKey}", $payload);

                    if ($response->successful()) {
                        break;
                    }

                    $this->lastFailureStatus = $response->status();
                    Log::warning("GeminiService uses request failed for {$model}", [
                        'status' => $response->status(),
                        'attempt' => $attempt,
                        'tea_id' => $tea->id,
                    ]);

                    if ($this->lastFailureStatus === 503 && $attempt < 2) {
                        usleep(750_000);
                        continue;
                    }

                    break;
                }

                if (!$response->successful()) {
                    continue;
                }

                $text = data_get($response->json(), 'candidates.0.content.parts.0.text');

                if ($text) {
                    $text = trim($text);
                    $text = preg_replace('/^```(?:json)?\s*|\s*```$/', '', $text);
                    $decoded = json_decode($text, true);

                    if (json_last_error() === JSON_ERROR_NONE) {
                        return $decoded;
                    }

                    Log::warning("GeminiService returned non-JSON uses for {$model}", [
                        'tea_id' => $tea->id,
                        'response' => $text,
                    ]);
                }

                Log::warning("GeminiService returned an empty uses response for {$model}", [
                    'tea_id' => $tea->id,
                ]);
            }

            return null;
        } catch (\Throwable $e) {
            Log::error('GeminiService uses exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Build the prompt for tea uses.
     */
    private function buildUsesPrompt(Tea $tea): string
    {
        $name = $tea->name ?: 'this tea';
        $flavor = $tea->flavor ?: 'not specified';
        $caffeine = $tea->caffeine_level ?: 'not specified';
        $benefit = $tea->health_benefit ?: 'general wellness';

        return <<<PROMPT
You are a creative, friendly tea-use assistant for a tea app. Given the tea details below, return ONLY a valid JSON object (no markdown, no explanation, no code fences) with exactly these keys:
- "drink_variations": array of 3-4 objects, each with "title" (3-6 words), "description" (one short sentence), "ingredients" (array of 3-6 ingredient strings), and "steps" (array of 3-5 short steps).
- "mixer_ideas": array of 2-3 objects with "title" and "description".
- "food_pairings": array of 2-3 objects with "title" and "description".
- "wellness_rituals": array of 2-3 objects with "title" and "description".
- "diy_uses": array of 2-3 objects with "title" and "description".

Keep suggestions accurate, practical, and appealing for this specific tea.

Tea name: {$name}
Tea flavor profile: {$flavor}
Tea caffeine level: {$caffeine}
Tea listed wellness information: {$benefit}
PROMPT;
    }

    /**
     * Build a friendly, user-oriented prompt describing why the tea is a good choice.
     */
    private function buildPrompt(Tea $tea, ?User $user = null, bool $useGroundedBenefits = true): string
    {
        $name = $tea->name ?: 'this tea';
        $flavor = $tea->flavor ?: 'not specified';
        $caffeine = $tea->caffeine_level ?: 'not specified';
        $benefit = $tea->health_benefit ?: 'general wellness';
        $preference = $user?->preference;
        $preferredFlavor = $preference?->preferred_flavor ?: 'no specific flavor preference';
        $preferredCaffeine = $preference?->preferred_caffeine ?: 'no specific caffeine preference';
        $healthGoal = $preference?->health_goal ?: 'general wellness';
        $healthMatchPercent = $preference
            ? (int) round($this->recommendationService->healthMatchScore($tea, $healthGoal) * 100)
            : null;
        $healthMatchContext = $healthMatchPercent === null
            ? 'No health-match score is available because this is a general description.'
            : "The recommendation engine's health-goal match is {$healthMatchPercent}% based only on the listed wellness information.";
        $healthGuidance = $useGroundedBenefits
            ? 'If the health-goal match is above 0%, make the connection to the user\'s goal inviting and specific. If the health-goal match is 0% and Google Search returns sources, add exactly two additional source-supported wellness points that are relevant to the tea. Clearly say the tea\'s listed benefit is not a direct match for the user\'s selected goal, then present the two additional points as possible general wellness interests, not guarantees or medical advice. If no search sources are available, explain the honest preference fit without adding unverified benefits.'
            : 'If the health-goal match is above 0%, make the connection to the user\'s goal inviting and specific. If the health-goal match is 0%, clearly say the tea\'s listed benefit is not a direct match for the selected goal, then explain its honest flavor or caffeine fit. Do not add any extra health benefits, facts, research, or mechanisms beyond the tea data in this request.';
        $funFactGuidance = $useGroundedBenefits
            ? 'Put the fourth sentence on a new line and begin it exactly with "• Fun fact: ". It must share one short, genuinely interesting fun fact about this specific tea, supported by a grounded source. Do not invent facts. Keep the tone varied, conversational, and fresh.'
            : 'Use exactly 3 short sentences. Do not add a fun fact, extra health benefits, research, or mechanisms because no grounded sources are available. Keep the tone varied, conversational, and fresh.';
        $teaTypeGuidance = 'Start the response with exactly one line in the format "Tea type: <category>". Choose the most accurate common tea type (e.g., Black, Green, White, Oolong, Pu-erh, Herbal, Rooibos, Matcha, etc.) based on the tea name and data.';
        $formatGuidance = $useGroundedBenefits
            ? 'After the tea type line, write exactly 4 short sentences, maximum 120 words total, with no heading, markdown, or emojis. The only permitted bullet is the required "• Fun fact:" line.'
            : 'After the tea type line, write exactly 3 short sentences, maximum 90 words total, with no heading, markdown, bullet points, or emojis.';

        return <<<PROMPT
You write warm, lively, trustworthy tea recommendations for a tea app.
{$formatGuidance}
{$teaTypeGuidance}
Sentence 1 explains naturally why this tea suits the user's flavor or caffeine preference.
Sentence 2 explains the tea's listed wellness benefit with gentle "may support" language and never presents it as treatment, prevention, or a cure.
{$healthGuidance}
{$funFactGuidance}

Tea name: {$name}
Tea flavor profile: {$flavor}
Tea caffeine level: {$caffeine}
Tea listed wellness information: {$benefit}
User preferred flavor: {$preferredFlavor}
User preferred caffeine: {$preferredCaffeine}
User health goal: {$healthGoal}
Health-goal match context: {$healthMatchContext}
PROMPT;
    }

    private function preferenceSignature(User $user): string
    {
        $preference = $user->preference;

        return hash('sha256', json_encode([
            'prompt_version' => 9,
            'preferred_flavor' => $preference?->preferred_flavor,
            'preferred_caffeine' => $preference?->preferred_caffeine,
            'health_goal' => $preference?->health_goal,
        ]));
    }
}

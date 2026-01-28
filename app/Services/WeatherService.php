<?php

namespace App\Services;

use App\Models\Weather;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WeatherService
{
    private $apiKey;
    private $baseUrl = 'https://api.openweathermap.org/data/2.5';
    
    // API Rate Limiting (Free tier: 1000 calls/day, 60 calls/minute)
    private const DAILY_LIMIT = 1000;
    private const MINUTE_LIMIT = 60;
    private const CACHE_DURATION_MINUTES = 30; // Cache for 30 minutes
    private const SAFETY_BUFFER = 0.8; // Use only 80% of daily limit

    public function __construct()
    {
        $this->apiKey = config('services.openweather.api_key');
    }

    /**
     * Get current weather for a city
     */
    public function getCurrentWeather($city, $country = null)
    {
        try {
            // Check rate limits first
            if (!$this->canMakeApiCall()) {
                Log::warning('API rate limit reached, using cached data');
                return $this->getCachedWeather($city, $country);
            }

            // Check cache first
            $cacheKey = $this->getCacheKey('current', $city, $country);
            $cached = cache()->get($cacheKey);
            if ($cached) {
                Log::info('Using cached weather data for ' . $city);
                return $cached;
            }

            // Default to Malaysia if no country specified and city is Malaysian
            if (!$country && $this->isMalaysianCity($city)) {
                $country = 'MY';
            }
            
            $query = $country ? "{$city},{$country}" : $city;
            
            $response = Http::get("{$this->baseUrl}/weather", [
                'q' => $query,
                'appid' => $this->apiKey,
                'units' => 'metric'
            ]);

            if (!$response->successful()) {
                Log::error('OpenWeather API Error: ' . $response->body());
                return $this->getCachedWeather($city, $country);
            }

            $data = $response->json();
            
            // Increment API call counter
            $this->incrementApiCallCount();
            
            $weather = $this->storeWeatherData($data);
            
            // Cache the result
            cache()->put($cacheKey, $weather, now()->addMinutes(self::CACHE_DURATION_MINUTES));
            
            return $weather;

        } catch (\Exception $e) {
            Log::error('Weather Service Error: ' . $e->getMessage());
            return $this->getCachedWeather($city, $country);
        }
    }

    /**
     * Get 7-day forecast for a city
     */
    public function getWeeklyForecast($city, $country = null)
    {
        try {
            // Check rate limits first
            if (!$this->canMakeApiCall()) {
                Log::warning('API rate limit reached, using cached forecast data');
                return $this->getCachedForecast($city, $country);
            }

            // Check cache first
            $cacheKey = $this->getCacheKey('forecast', $city, $country);
            $cached = cache()->get($cacheKey);
            if ($cached) {
                Log::info('Using cached forecast data for ' . $city);
                return $cached;
            }

            // Default to Malaysia if no country specified and city is Malaysian
            if (!$country && $this->isMalaysianCity($city)) {
                $country = 'MY';
            }
            
            $query = $country ? "{$city},{$country}" : $city;
            
            $response = Http::get("{$this->baseUrl}/forecast", [
                'q' => $query,
                'appid' => $this->apiKey,
                'units' => 'metric'
            ]);

            if (!$response->successful()) {
                Log::error('OpenWeather API Error: ' . $response->body());
                return $this->getCachedForecast($city, $country);
            }

            $data = $response->json();
            
            // Increment API call counter
            $this->incrementApiCallCount();
            
            $forecast = $this->storeForecastData($data);
            
            // Cache the result (forecast can be cached longer)
            cache()->put($cacheKey, $forecast, now()->addHours(2));
            
            return $forecast;

        } catch (\Exception $e) {
            Log::error('Weather Service Error: ' . $e->getMessage());
            return $this->getCachedForecast($city, $country);
        }
    }

    /**
     * Store current weather data
     */
    private function storeWeatherData($data)
    {
        $weather = Weather::updateOrCreate(
            [
                'city' => $data['name'],
                'date' => now()->toDateString()
            ],
            [
                'country' => $data['sys']['country'] ?? null,
                'latitude' => $data['coord']['lat'],
                'longitude' => $data['coord']['lon'],
                'temperature' => $data['main']['temp'],
                'feels_like' => $data['main']['feels_like'],
                'humidity' => $data['main']['humidity'],
                'wind_speed' => $data['wind']['speed'] ?? 0,
                'pressure' => $data['main']['pressure'],
                'main_condition' => $data['weather'][0]['main'],
                'description' => $data['weather'][0]['description'],
                'icon_code' => $data['weather'][0]['icon'],
                'forecast_data' => $data
            ]
        );

        return $weather;
    }

    /**
     * Store forecast data (daily aggregation)
     */
    private function storeForecastData($data)
    {
        $forecasts = [];
        $dailyData = [];

        // Group forecast data by day
        foreach ($data['list'] as $item) {
            $date = date('Y-m-d', $item['dt']);
            
            if (!isset($dailyData[$date])) {
                $dailyData[$date] = [
                    'temps' => [],
                    'humidity' => [],
                    'wind_speeds' => [],
                    'pressure' => [],
                    'conditions' => [],
                    'descriptions' => [],
                    'icons' => []
                ];
            }

            $dailyData[$date]['temps'][] = $item['main']['temp'];
            $dailyData[$date]['humidity'][] = $item['main']['humidity'];
            $dailyData[$date]['wind_speeds'][] = $item['wind']['speed'] ?? 0;
            $dailyData[$date]['pressure'][] = $item['main']['pressure'];
            $dailyData[$date]['conditions'][] = $item['weather'][0]['main'];
            $dailyData[$date]['descriptions'][] = $item['weather'][0]['description'];
            $dailyData[$date]['icons'][] = $item['weather'][0]['icon'];
        }

        // Store daily aggregated data
        foreach ($dailyData as $date => $dayData) {
            $avgTemp = array_sum($dayData['temps']) / count($dayData['temps']);
            $avgHumidity = array_sum($dayData['humidity']) / count($dayData['humidity']);
            $avgWindSpeed = array_sum($dayData['wind_speeds']) / count($dayData['wind_speeds']);
            $avgPressure = array_sum($dayData['pressure']) / count($dayData['pressure']);
            
            // Get most common condition
            $conditions = array_count_values($dayData['conditions']);
            $mainCondition = array_keys($conditions, max($conditions))[0];
            
            // Get most common description
            $descriptions = array_count_values($dayData['descriptions']);
            $description = array_keys($descriptions, max($descriptions))[0];
            
            // Get most common icon
            $icons = array_count_values($dayData['icons']);
            $icon = array_keys($icons, max($icons))[0];

            $weather = Weather::updateOrCreate(
                [
                    'city' => $data['city']['name'],
                    'date' => $date
                ],
                [
                    'country' => $data['city']['country'] ?? null,
                    'latitude' => $data['city']['coord']['lat'],
                    'longitude' => $data['city']['coord']['lon'],
                    'temperature' => round($avgTemp, 2),
                    'feels_like' => round($avgTemp, 2), // Use avg temp as feels like for forecast
                    'humidity' => round($avgHumidity),
                    'wind_speed' => round($avgWindSpeed, 2),
                    'pressure' => round($avgPressure),
                    'main_condition' => $mainCondition,
                    'description' => $description,
                    'icon_code' => $icon,
                    'forecast_data' => $dayData
                ]
            );

            $forecasts[] = $weather;
        }

        return collect($forecasts);
    }

    /**
     * Get weather-based tea recommendations
     */
    public function getWeatherBasedTeaRecommendations($city, $userPreferences = null)
    {
        $weather = $this->getCurrentWeather($city);
        
        if (!$weather) {
            return null;
        }

        $category = $weather->getTeaCategory();
        
        return $this->getTeasForWeatherCategory($category, $userPreferences);
    }

    /**
     * Get teas suitable for specific weather category
     */
    private function getTeasForWeatherCategory($category, $userPreferences = null)
    {
        $teaRecommendations = [
            'hot' => [
                'preferred_flavors' => ['minty', 'fruity', 'sweet'],
                'preferred_caffeine' => ['low', 'caffeine_free'],
                'health_goals' => ['relax_calm', 'energy'],
                'reasoning' => 'Cooling and refreshing teas for hot weather'
            ],
            'cold' => [
                'preferred_flavors' => ['spicy', 'earthy', 'sweet'],
                'preferred_caffeine' => ['medium', 'high'],
                'health_goals' => ['energy', 'body_relief'],
                'reasoning' => 'Warming and energizing teas for cold weather'
            ],
            'rainy' => [
                'preferred_flavors' => ['herbal', 'sweet', 'fruity'],
                'preferred_caffeine' => ['medium', 'low'],
                'health_goals' => ['relax_calm', 'digest'],
                'reasoning' => 'Comforting and soothing teas for rainy days'
            ],
            'cloudy' => [
                'preferred_flavors' => ['earthy', 'spicy', 'herbal'],
                'preferred_caffeine' => ['medium', 'low'],
                'health_goals' => ['energy', 'relax_calm'],
                'reasoning' => 'Balanced teas for cloudy weather'
            ],
            'moderate' => [
                'preferred_flavors' => ['any'],
                'preferred_caffeine' => ['any'],
                'health_goals' => ['any'],
                'reasoning' => 'Any tea is suitable for moderate weather'
            ]
        ];

        $recommendations = $teaRecommendations[$category] ?? $teaRecommendations['moderate'];

        // Override with user preferences if available
        if ($userPreferences) {
            if ($userPreferences->weather_preference && $userPreferences->weather_preference !== 'auto') {
                $recommendations = $this->getUserWeatherPreference($userPreferences->weather_preference);
            }
        }

        return $recommendations;
    }

    /**
     * Get user's specific weather preference
     */
    private function getUserWeatherPreference($preference)
    {
        $preferences = [
            'hot_weather' => [
                'preferred_flavors' => ['minty', 'fruity'],
                'preferred_caffeine' => ['low', 'caffeine_free'],
                'health_goals' => ['relax_calm'],
                'reasoning' => 'Cooling teas for hot weather preference'
            ],
            'cold_weather' => [
                'preferred_flavors' => ['spicy', 'earthy'],
                'preferred_caffeine' => ['medium', 'high'],
                'health_goals' => ['energy', 'body_relief'],
                'reasoning' => 'Warming teas for cold weather preference'
            ],
            'rainy_days' => [
                'preferred_flavors' => ['herbal', 'sweet'],
                'preferred_caffeine' => ['medium', 'low'],
                'health_goals' => ['relax_calm', 'digest'],
                'reasoning' => 'Comforting teas for rainy days'
            ]
        ];

        return $preferences[$preference] ?? $preferences['hot_weather'];
    }

    /**
     * Get weather icon URL
     */
    public static function getWeatherIconUrl($iconCode)
    {
        return "https://openweathermap.org/img/wn/{$iconCode}@2x.png";
    }

    /**
     * Check if city is a Malaysian city
     */
    public function isMalaysianCity($city)
    {
        $malaysianCities = [
            'kuala lumpur', 'kl', 'george town', 'penang', 'johor bahru', 'jb',
            'ipoh', 'shah alam', 'petaling jaya', 'pj', 'seremban', 'kuching',
            'kota kinabalu', 'kk', 'malacca', 'melaka', 'alor setar', 'miri',
            'klang', 'kota bharu', 'kuala terengganu', 'sandakan', 'sibu',
            'taiping', 'seberang perai', 'subang jaya', 'putrajaya', 'cyberjaya',
            'rawang', 'kajang', 'bangi', 'senawang', 'ampang', 'cheras',
            'gombak', 'batu pahat', 'kulim', 'banting', 'sepang', 'salak tinggi'
        ];

        return in_array(strtolower(trim($city)), $malaysianCities);
    }

    /**
     * Get Malaysian cities list for autocomplete/suggestions
     */
    public static function getMalaysianCities()
    {
        return [
            'Kuala Lumpur',
            'George Town',
            'Johor Bahru', 
            'Ipoh',
            'Shah Alam',
            'Petaling Jaya',
            'Seremban',
            'Kuching',
            'Kota Kinabalu',
            'Malacca',
            'Alor Setar',
            'Miri',
            'Klang',
            'Kota Bharu',
            'Kuala Terengganu',
            'Sandakan',
            'Sibu',
            'Taiping',
            'Subang Jaya',
            'Putrajaya'
        ];
    }

    /**
     * Get Malaysia-specific weather recommendations
     */
    public function getMalaysianWeatherRecommendations($city, $preference = null)
    {
        $weather = $this->getCurrentWeather($city, 'MY');
        
        if (!$weather) {
            return null;
        }

        // Malaysia-specific weather patterns with enhanced options
        $malaysianRecommendations = [
            'malaysian_hot_humid' => [
                'condition' => $weather->temperature >= 28 && $weather->humidity >= 70,
                'teas' => ['lemongrass', 'ginger', 'mint', 'honey lemon', 'green tea', 'barley grass'],
                'reason' => 'Perfect for Malaysian hot & humid weather (28-35°C) - cooling and refreshing teas',
                'caffeine_preference' => ['low', 'caffeine_free']
            ],
            'malaysian_rainy' => [
                'condition' => $weather->isRainy() || $weather->humidity >= 80,
                'teas' => ['ginger tea', 'lemongrass', 'pandan', 'ginseng', 'oolong', 'tongkat ali'],
                'reason' => 'Ideal for Malaysian monsoon season - warming and immunity-boosting teas',
                'caffeine_preference' => ['medium', 'low']
            ],
            'malaysian_haze' => [
                'condition' => strtolower($weather->description) === 'haze' || $weather->humidity <= 60,
                'teas' => ['green tea', 'honey lemon', 'ginger', 'peppermint', 'chamomile', 'mangosteen'],
                'reason' => 'Great for Malaysian haze season - cleansing and soothing teas for air quality',
                'caffeine_preference' => ['low', 'medium']
            ],
            'malaysian_cool_morning' => [
                'condition' => $weather->temperature >= 18 && $weather->temperature <= 22,
                'teas' => ['jasmine', 'white tea', 'light oolong', 'rose', 'chrysanthemum'],
                'reason' => 'Perfect for Malaysian cool mornings (18-22°C) - gentle and aromatic teas',
                'caffeine_preference' => ['low', 'medium']
            ],
            'malaysian_afternoon_heat' => [
                'condition' => $weather->temperature >= 32 && $weather->temperature <= 38,
                'teas' => ['iced green tea', 'mint', 'lemongrass', 'barley', 'wintermelon', 'lychee'],
                'reason' => 'Ideal for Malaysian afternoon heat (32-38°C) - extra cooling and hydrating teas',
                'caffeine_preference' => ['low', 'caffeine_free']
            ],
            'malaysian_thunderstorm' => [
                'condition' => $weather->isRainy() && $weather->wind_speed >= 10,
                'teas' => ['ginger', 'cinnamon', 'cardamom', 'ginseng', 'turmeric', 'kopi'],
                'reason' => 'Perfect for Malaysian thunderstorms - warming and comforting teas',
                'caffeine_preference' => ['medium', 'high']
            ],
            'malaysian_aircond' => [
                'condition' => true, // Always available for air-conditioned environments
                'teas' => ['oolong', 'pu-erh', 'black tea', 'tie guan yin', 'english breakfast'],
                'reason' => 'Ideal for air-conditioned Malaysian indoors - warming teas for cold environments',
                'caffeine_preference' => ['medium', 'high']
            ]
        ];

        // If user has specific preference, use that
        if ($preference && isset($malaysianRecommendations[$preference])) {
            $recommendation = $malaysianRecommendations[$preference];
            return [
                'weather' => $weather,
                'recommended_teas' => $recommendation['teas'],
                'reason' => $recommendation['reason'],
                'caffeine_preference' => $recommendation['caffeine_preference'],
                'region_specific' => true,
                'country' => 'Malaysia',
                'preference_used' => $preference
            ];
        }

        foreach ($malaysianRecommendations as $recommendation) {
            if ($recommendation['condition']) {
                return [
                    'weather' => $weather,
                    'recommended_teas' => $recommendation['teas'],
                    'reason' => $recommendation['reason'],
                    'caffeine_preference' => $recommendation['caffeine_preference'],
                    'region_specific' => true,
                    'country' => 'Malaysia'
                ];
            }
        }

        // Default recommendation
        return [
            'weather' => $weather,
            'recommended_teas' => ['green tea', 'oolong', 'jasmine'],
            'reason' => 'Great for Malaysian weather - balanced local favorites',
            'caffeine_preference' => ['medium', 'low'],
            'region_specific' => true,
            'country' => 'Malaysia'
        ];
    }

    /**
     * Get Malaysian timezone
     */
    public static function getMalaysianTimezone()
    {
        return 'Asia/Kuala_Lumpur';
    }

    /**
     * Format Malaysian time
     */
    public static function formatMalaysianTime($datetime)
    {
        return \Carbon\Carbon::parse($datetime)->setTimezone('Asia/Kuala_Lumpur')->format('Y-m-d H:i:s');
    }

    /**
     * Check if we can make an API call based on rate limits
     */
    private function canMakeApiCall()
    {
        $dailyKey = 'openweather_daily_calls_' . date('Y-m-d');
        $minuteKey = 'openweather_minute_calls_' . date('Y-m-d-H:i');
        
        $dailyCalls = cache()->get($dailyKey, 0);
        $minuteCalls = cache()->get($minuteKey, 0);
        
        $dailyLimit = self::DAILY_LIMIT * self::SAFETY_BUFFER;
        
        // Check if we're approaching the January 31 deadline
        $daysUntilJan31 = now()->diffInDays(\Carbon\Carbon::createFromFormat('Y-m-d', '2026-01-31'));
        if ($daysUntilJan31 <= 3) {
            // Be extra conservative in the last 3 days
            $dailyLimit = self::DAILY_LIMIT * 0.5; // Use only 50% of limit
            Log::warning("Approaching Jan 31 deadline - using conservative API limit: {$dailyLimit} calls/day");
        }
        
        if ($dailyCalls >= $dailyLimit) {
            Log::error("Daily API limit reached: {$dailyCalls}/{$dailyLimit}");
            return false;
        }
        
        if ($minuteCalls >= self::MINUTE_LIMIT) {
            Log::warning("Minute API limit reached: {$minuteCalls}/" . self::MINUTE_LIMIT);
            return false;
        }
        
        return true;
    }

    /**
     * Increment API call counter
     */
    private function incrementApiCallCount()
    {
        $dailyKey = 'openweather_daily_calls_' . date('Y-m-d');
        $minuteKey = 'openweather_minute_calls_' . date('Y-m-d-H:i');
        
        cache()->increment($dailyKey);
        cache()->increment($minuteKey);
        
        // Set expiration for counters
        cache()->put($dailyKey, cache()->get($dailyKey, 0), now()->endOfDay());
        cache()->put($minuteKey, cache()->get($minuteKey, 0), now()->addMinute());
        
        $dailyCalls = cache()->get($dailyKey);
        $dailyLimit = self::DAILY_LIMIT * self::SAFETY_BUFFER;
        
        Log::info("API call made. Daily: {$dailyCalls}/{$dailyLimit}");
        
        // Log warning if approaching limit
        if ($dailyCalls >= $dailyLimit * 0.9) {
            Log::warning("Approaching daily API limit: {$dailyCalls}/{$dailyLimit}");
        }
    }

    /**
     * Generate cache key for weather data
     */
    private function getCacheKey($type, $city, $country = null)
    {
        $location = $country ? "{$city}_{$country}" : $city;
        return "weather_{$type}_" . strtolower(str_replace(' ', '_', $location));
    }

    /**
     * Get cached weather data
     */
    private function getCachedWeather($city, $country = null)
    {
        $cacheKey = $this->getCacheKey('current', $city, $country);
        return cache()->get($cacheKey);
    }

    /**
     * Get cached forecast data
     */
    private function getCachedForecast($city, $country = null)
    {
        $cacheKey = $this->getCacheKey('forecast', $city, $country);
        return cache()->get($cacheKey);
    }

    /**
     * Get API usage statistics
     */
    public function getApiUsageStats()
    {
        $dailyKey = 'openweather_daily_calls_' . date('Y-m-d');
        $dailyCalls = cache()->get($dailyKey, 0);
        $dailyLimit = self::DAILY_LIMIT * self::SAFETY_BUFFER;
        
        $daysUntilJan31 = now()->diffInDays(\Carbon\Carbon::createFromFormat('Y-m-d', '2026-01-31'));
        
        return [
            'daily_calls' => $dailyCalls,
            'daily_limit' => $dailyLimit,
            'daily_percentage' => round(($dailyCalls / $dailyLimit) * 100, 2),
            'days_until_jan31' => $daysUntilJan31,
            'is_conservative_mode' => $daysUntilJan31 <= 3,
            'cache_duration_minutes' => self::CACHE_DURATION_MINUTES,
            'recommendation' => $this->getRecommendation($dailyCalls, $dailyLimit, $daysUntilJan31)
        ];
    }

    /**
     * Get usage recommendation
     */
    private function getRecommendation($dailyCalls, $dailyLimit, $daysUntilJan31)
    {
        if ($daysUntilJan31 <= 0) {
            return "⚠️ Deadline passed! Use cached data only.";
        } elseif ($daysUntilJan31 <= 1) {
            return "🚨 Emergency: Use cached data exclusively. No API calls recommended.";
        } elseif ($daysUntilJan31 <= 3) {
            return "⚠️ Conservative mode: Use cached data when possible. Limit API calls to essential updates only.";
        } elseif ($dailyCalls >= $dailyLimit * 0.9) {
            return "⚠️ Daily limit nearly reached. Use cached data for the rest of the day.";
        } elseif ($dailyCalls >= $dailyLimit * 0.7) {
            return "⚡ Moderate usage: Consider increasing cache duration to reduce API calls.";
        } else {
            return "✅ Safe to use API normally. Current usage is well within limits.";
        }
    }

    /**
     * Clear API usage cache (for testing)
     */
    public function clearApiUsageCache()
    {
        $dailyKey = 'openweather_daily_calls_' . date('Y-m-d');
        $minuteKey = 'openweather_minute_calls_' . date('Y-m-d-H:i');
        
        cache()->forget($dailyKey);
        cache()->forget($minuteKey);
        
        Log::info('API usage cache cleared');
    }
}

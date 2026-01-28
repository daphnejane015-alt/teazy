<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tea;
use App\Models\Weather;
use App\Models\RecommendationInteraction;
use App\Services\RecommendationService;
use App\Services\WeatherService;
use App\Services\RecommendationTrackingService;

class TopTeaController extends Controller
{
    public function index(RecommendationService $recommendationService)
    {
        $user = auth()->user();
        $weeklyWeatherTeas = null;
        $weatherInfo = null;
        
        // Check if user has weather preferences enabled
        if ($user && $user->preference && $user->preference->weather_based_recommendations && $user->preference->city) {
            // Get weekly weather forecast
            $weeklyForecast = Weather::weeklyForecast($user->preference->city);
            
            // If no weather data exists, try to fetch it
            if ($weeklyForecast->isEmpty()) {
                $weatherService = app(WeatherService::class);
                $weatherService->getCurrentWeather($user->preference->city);
                $weatherService->getWeeklyForecast($user->preference->city);
                
                // Try again after fetching
                $weeklyForecast = Weather::weeklyForecast($user->preference->city);
            }
            
            if ($weeklyForecast->isNotEmpty()) {
                // Get weather-based recommendations for the week
                $weeklyWeatherTeas = $this->getWeeklyWeatherRecommendations($weeklyForecast, $user->preference, $recommendationService);
                
                // Get current weather for display
                $currentWeather = Weather::forCity($user->preference->city);
                $weatherInfo = [
                    'current' => $currentWeather,
                    'forecast' => $weeklyForecast,
                    'city' => $user->preference->city
                ];
            }
        }
        
        // Get top 5 rated teas using shared method from RatingController
        // This ensures consistency with the dedicated top-rated page
        // Both sections now show identical results: top 5 teas by average rating
        $topTeas = \App\Http\Controllers\RatingController::getTopRatedTeas(5);

        return view('user.top-tea', compact('topTeas', 'weeklyWeatherTeas', 'weatherInfo'));
    }
    
    /**
     * Get weather-based tea recommendations for the week
     */
    private function getWeeklyWeatherRecommendations($weeklyForecast, $preferences, $recommendationService)
    {
        $weeklyRecommendations = collect();
        $usedTeas = []; // Track used teas to ensure variety
        $flavorHistory = []; // Track flavor variety
        $caffeineHistory = []; // Track caffeine variety
        
        foreach ($weeklyForecast as $index => $day) {
            $weatherCategory = $day->getTeaCategory();
            
            // Get top teas for this weather category
            $weatherTeas = $this->getTeasForWeatherCategory($weatherCategory, $preferences);
            
            // Apply diversity scoring to avoid duplicates and ensure variety
            $diverseTeas = $weatherTeas->map(function($tea) use ($usedTeas, $flavorHistory, $caffeineHistory) {
                $diversityScore = 0;
                
                // Avoid duplicate teas (highest priority)
                if (!in_array($tea->id, $usedTeas)) {
                    $diversityScore += 0.5;
                } else {
                    $diversityScore -= 0.3; // Heavy penalty for duplicates
                }
                
                // Encourage flavor variety
                if (!in_array($tea->flavor, $flavorHistory)) {
                    $diversityScore += 0.3;
                }
                
                // Encourage caffeine variety
                if (!in_array($tea->caffeine_level, $caffeineHistory)) {
                    $diversityScore += 0.2;
                }
                
                // Combine weather score with diversity score
                $tea->diversity_score = $tea->weather_score + $diversityScore;
                return $tea;
            })->sortByDesc('diversity_score');
            
            // Select the best tea (highest combined score)
            $selectedTea = $diverseTeas->first();
            
            // Track used items for variety
            $usedTeas[] = $selectedTea->id;
            $flavorHistory[] = $selectedTea->flavor;
            $caffeineHistory[] = $selectedTea->caffeine_level;
            
            // Track recommendation for future learning
            $this->trackRecommendation($selectedTea, $day, $preferences, $index);
            
            // Add detailed weather information
            $weatherDetails = $this->getDetailedWeatherInfo($day);
            
            // Debug: Log weather details to check data structure
            \Log::info("Weather details for day {$index}", [
                'weather_details' => $weatherDetails,
                'day_data' => $day->toArray()
            ]);
            
            $weeklyRecommendations->push([
                'day_number' => $index + 1,
                'date' => $day->date,
                'day_name' => \Carbon\Carbon::parse($day->date)->format('l'),
                'short_date' => \Carbon\Carbon::parse($day->date)->format('M j'),
                'weather' => $day,
                'weather_details' => $weatherDetails,
                'category' => $weatherCategory,
                'teas' => collect([$selectedTea]), // Single diverse tea per day
                'recommendation_reason' => $this->getWeatherRecommendationReason($weatherCategory, $day),
                'tea_count' => $weatherTeas->count(),
                'diversity_info' => [
                    'algorithm' => 'rule-based-diversity',
                    'variety_score' => round($selectedTea->diversity_score - $selectedTea->weather_score, 2),
                    'is_unique' => !in_array($selectedTea->id, array_slice($usedTeas, 0, -1)),
                    'flavor_variety' => count(array_unique($flavorHistory)),
                    'caffeine_variety' => count(array_unique($caffeineHistory))
                ]
            ]);
        }
        
        return $weeklyRecommendations;
    }
    
    /**
     * Get detailed weather information
     */
    private function getDetailedWeatherInfo($weather)
    {
        return [
            'temperature' => round($weather->temperature),
            'feels_like' => round($weather->feels_like),
            'humidity' => $weather->humidity,
            'wind_speed' => round($weather->wind_speed, 1),
            'pressure' => $weather->pressure,
            'main_condition' => $weather->main_condition,
            'description' => $weather->description,
            'icon_code' => $weather->icon_code,
            'is_hot' => $weather->isHot(),
            'is_cold' => $weather->isCold(),
            'is_rainy' => $weather->isRainy(),
            'is_cloudy' => $weather->isCloudy(),
            'is_clear' => $weather->isClear(),
            'comfort_level' => $this->getComfortLevel($weather),
            'tea_emoji' => $this->getTeaEmoji($weather->getTeaCategory())
        ];
    }
    
    /**
     * Get comfort level based on weather
     */
    private function getComfortLevel($weather)
    {
        $temp = $weather->temperature;
        
        if ($temp >= 20 && $temp <= 25) {
            return ['level' => 'Perfect', 'color' => 'green', 'emoji' => '😊'];
        } elseif ($temp >= 15 && $temp <= 19) {
            return ['level' => 'Comfortable', 'color' => 'blue', 'emoji' => '🙂'];
        } elseif ($temp >= 10 && $temp <= 14) {
            return ['level' => 'Cool', 'color' => 'cyan', 'emoji' => '🧥'];
        } elseif ($temp >= 26 && $temp <= 30) {
            return ['level' => 'Warm', 'color' => 'orange', 'emoji' => '☀️'];
        } elseif ($temp > 30) {
            return ['level' => 'Hot', 'color' => 'red', 'emoji' => '🔥'];
        } else {
            return ['level' => 'Cold', 'color' => 'blue', 'emoji' => '❄️'];
        }
    }
    
    /**
     * Get tea emoji based on weather category
     */
    private function getTeaEmoji($category)
    {
        $emojis = [
            'hot' => '🧊',
            'cold' => '🔥',
            'rainy' => '☕',
            'cloudy' => '🍵',
            'moderate' => '🌤️'
        ];
        
        return $emojis[$category] ?? '🍵';
    }
    
    /**
     * Get teas suitable for specific weather category
     */
    private function getTeasForWeatherCategory($category, $preferences)
    {
        $weatherMappings = [
            'hot' => [
                'preferred_flavors' => ['minty', 'fruity', 'sweet'],
                'preferred_caffeine' => ['low', 'caffeine_free', 'none'],
                'preferred_health' => ['relax_calm', 'energy']
            ],
            'cold' => [
                'preferred_flavors' => ['spicy', 'earthy', 'sweet', 'herbal'],
                'preferred_caffeine' => ['medium', 'high', 'medium_high'],
                'preferred_health' => ['energy', 'body_relief', 'blood_circulation']
            ],
            'rainy' => [
                'preferred_flavors' => ['herbal', 'sweet', 'fruity', 'spicy'],
                'preferred_caffeine' => ['medium', 'low', 'medium_high'],
                'preferred_health' => ['relax_calm', 'digest', 'stress']
            ],
            'cloudy' => [
                'preferred_flavors' => ['earthy', 'spicy', 'herbal', 'fruity'],
                'preferred_caffeine' => ['medium', 'low', 'medium_high'],
                'preferred_health' => ['energy', 'relax_calm', 'immune']
            ],
            'moderate' => [
                'preferred_flavors' => ['any'],
                'preferred_caffeine' => ['any'],
                'preferred_health' => ['any']
            ]
        ];
        
        $mapping = $weatherMappings[$category] ?? $weatherMappings['moderate'];
        
        // Override with user's weather preference if set
        if ($preferences->weather_preference && $preferences->weather_preference !== 'auto') {
            $mapping = $this->getUserWeatherMapping($preferences->weather_preference);
        }
        
        // Get teas that match the weather criteria
        $teas = Tea::all();
        $scoredTeas = collect();
        
        foreach ($teas as $tea) {
            $score = 0;
            
            // Score based on flavor
            if (!in_array('any', $mapping['preferred_flavors'])) {
                $flavorMatch = $this->isFlavorMatch($tea->flavor, $mapping['preferred_flavors']);
                if ($flavorMatch) {
                    $score += 0.4;
                }
            } else {
                $score += 0.3;
            }
            
            // Score based on caffeine
            if (!in_array('any', $mapping['preferred_caffeine'])) {
                foreach ($mapping['preferred_caffeine'] as $caffeine) {
                    if (strtolower($tea->caffeine_level) === strtolower($caffeine)) {
                        $score += 0.3;
                        break;
                    }
                }
            } else {
                $score += 0.2;
            }
            
            // Score based on health benefits
            if (!in_array('any', $mapping['preferred_health'])) {
                foreach ($mapping['preferred_health'] as $health) {
                    if (stripos($tea->health_benefit, str_replace('_', ' ', $health)) !== false) {
                        $score += 0.3;
                        break;
                    }
                }
            } else {
                $score += 0.2;
            }
            
            // Add rating bonus
            if ($tea->averageRating() > 0) {
                $score += ($tea->averageRating() / 5) * 0.2;
            }
            
            $tea->weather_score = $score;
            
            // Debug: Log scoring details for troubleshooting
            if ($score == 0) {
                \Log::info("Zero weather score for tea {$tea->id} ({$tea->name}) in category {$category}", [
                    'flavor' => $tea->flavor,
                    'caffeine' => $tea->caffeine_level,
                    'health' => $tea->health_benefit,
                    'mapping' => $mapping
                ]);
            }
            
            $scoredTeas->push($tea);
        }
        
        return $scoredTeas->sortByDesc('weather_score');
    }
    
    /**
     * Get user's specific weather mapping
     */
    private function getUserWeatherMapping($preference)
    {
        $mappings = [
            'hot_weather' => [
                'preferred_flavors' => ['minty', 'fruity'],
                'preferred_caffeine' => ['low', 'caffeine_free'],
                'preferred_health' => ['relax_calm']
            ],
            'cold_weather' => [
                'preferred_flavors' => ['spicy', 'earthy'],
                'preferred_caffeine' => ['medium', 'high'],
                'preferred_health' => ['energy', 'body_relief']
            ],
            'rainy_days' => [
                'preferred_flavors' => ['herbal', 'sweet'],
                'preferred_caffeine' => ['medium', 'low'],
                'preferred_health' => ['relax_calm', 'digest']
            ],
            'malaysian_hot_humid' => [
                'preferred_flavors' => ['minty', 'fruity', 'sweet'],
                'preferred_caffeine' => ['low', 'caffeine_free'],
                'preferred_health' => ['relax_calm', 'energy']
            ],
            'malaysian_rainy' => [
                'preferred_flavors' => ['spicy', 'herbal', 'sweet'],
                'preferred_caffeine' => ['medium', 'low'],
                'preferred_health' => ['relax_calm', 'digest', 'immune']
            ],
            'malaysian_haze' => [
                'preferred_flavors' => ['herbal', 'minty', 'fruity'],
                'preferred_caffeine' => ['low', 'medium'],
                'preferred_health' => ['relax_calm', 'immune', 'stress']
            ]
        ];
        
        return $mappings[$preference] ?? $mappings['hot_weather'];
    }
    
    /**
     * Get weather recommendation reason
     */
    private function getWeatherRecommendationReason($category, $weather)
    {
        $reasons = [
            'hot' => "Perfect for hot weather ({$weather->temperature}°C) - cooling and refreshing teas",
            'cold' => "Ideal for cold weather ({$weather->temperature}°C) - warming and energizing teas", 
            'rainy' => "Great for rainy weather - comforting and soothing teas",
            'cloudy' => "Perfect for cloudy weather - balanced and enjoyable teas",
            'moderate' => "Great for moderate weather ({$weather->temperature}°C) - any tea will be perfect"
        ];
        
        return $reasons[$category] ?? $reasons['moderate'];
    }
    
    /**
     * Track recommendation for future machine learning
     */
    private function trackRecommendation($tea, $day, $preferences, $dayIndex)
    {
        try {
            $trackingService = app(RecommendationTrackingService::class);
            
            $context = [
                'weather_category' => $day->getTeaCategory(),
                'temperature' => $day->temperature,
                'humidity' => $day->humidity,
                'day_of_week' => \Carbon\Carbon::parse($day->date)->format('l'),
                'time_of_day' => $this->getTimeOfDayForIndex($dayIndex),
                'season' => RecommendationInteraction::getSeasonFromDate($day->date),
                'user_preferences' => [
                    'flavor' => $preferences->preferred_flavor ?? null,
                    'caffeine' => $preferences->preferred_caffeine ?? null,
                    'health' => $preferences->health_goal ?? null,
                    'weather_preference' => $preferences->weather_preference ?? 'auto',
                ],
                'algorithm' => 'rule-based-diversity',
                'confidence' => 0.7,
                'prediction_score' => $tea->diversity_score ?? $tea->weather_score ?? 0.5,
            ];
            
            $trackingService->trackInteraction(
                auth()->id(),
                $tea->id,
                'recommended',
                $context
            );
        } catch (\Exception $e) {
            // Silent fail - tracking shouldn't break recommendations
            \Log::warning('Failed to track recommendation: ' . $e->getMessage());
        }
    }
    
    /**
     * Get time of day based on day index
     */
    private function getTimeOfDayForIndex($index)
    {
        $times = ['morning', 'afternoon', 'evening', 'night', 'morning', 'afternoon', 'evening'];
        return $times[$index % 7] ?? 'morning';
    }
    
    /**
     * Check if flavor matches any of the preferred flavors using similarity mapping
     */
    private function isFlavorMatch($teaFlavor, $preferredFlavors)
    {
        // Flavor similarity mapping (same as RecommendationService)
        $flavorSimilarityMap = [
            'fruity' => ['fruity', 'fruit', 'berry', 'citrus', 'sweet'],
            'herbal' => ['herbal', 'herb', 'earthy', 'plant', 'natural', 'bitter'],
            'bitter' => ['bitter', 'herbal', 'herb', 'earthy', 'plant', 'natural'],
            'sweet' => ['sweet', 'honey', 'sugary', 'caramel', 'fruity'],
            'various' => ['various', 'mixed', 'blend', 'combination', 'any'],
            'any' => ['any', 'various', 'mixed', 'blend', 'combination'],
            'n/a' => ['n/a', 'none', 'neutral', 'mild', 'subtle']
        ];
        
        $teaFlavor = strtolower(trim($teaFlavor));
        
        foreach ($preferredFlavors as $preferredFlavor) {
            $preferredFlavor = strtolower(trim($preferredFlavor));
            
            // Exact match
            if ($teaFlavor === $preferredFlavor) {
                return true;
            }
            
            // Check similarity mapping
            $teaSimilarFlavors = $flavorSimilarityMap[$teaFlavor] ?? [$teaFlavor];
            $prefSimilarFlavors = $flavorSimilarityMap[$preferredFlavor] ?? [$preferredFlavor];
            
            // Check if tea flavor matches user preference or similar flavors
            foreach ($prefSimilarFlavors as $prefSimilar) {
                if (in_array($prefSimilar, $teaSimilarFlavors)) {
                    return true;
                }
            }
        }
        
        return false;
    }
}

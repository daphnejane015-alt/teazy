<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Preference;
use App\Models\Tea;
use App\Services\RecommendationService;

class FindTeaController extends Controller
{
    public function create()
    {
        return view('user.find-tea');
    }

    public function store(Request $request, RecommendationService $recommendationService)
    {
        Preference::updateOrCreate(
            ['user_id' => auth()->id()],
            [
                'preferred_flavor' => $request->flavor,
                'preferred_caffeine' => $request->caffeine,
                'health_goal' => $request->health_goal,
                'city' => $request->city,
                'weather_based_recommendations' => $request->has('weather_based_recommendations'),
                'weather_preference' => $request->weather_preference ?? 'auto',
            ]
        );

        // If weather-based recommendations are enabled, fetch weather data
        if ($request->has('weather_based_recommendations') && $request->city) {
            $weatherService = app(\App\Services\WeatherService::class);
            
            // Use Malaysian-specific recommendations if it's a Malaysian city
            if ($weatherService->isMalaysianCity($request->city)) {
                $malaysianRecommendations = $weatherService->getMalaysianWeatherRecommendations(
                    $request->city, 
                    $request->weather_preference
                );
                // Store Malaysian-specific data for later use
                session(['malaysian_weather_recommendations' => $malaysianRecommendations]);
            }
            
            $weatherService->getCurrentWeather($request->city);
            $weatherService->getWeeklyForecast($request->city);
        }

        $recommendations = $recommendationService
            ->recommend(auth()->user());

        // Get user's current preferences for display
        $preferences = auth()->user()->preference;

        return view('user.recommendation-result', compact('recommendations', 'preferences'));
    }

    public function search(Request $request)
    {
        $query = $request->get('query');
        
        if (empty($query)) {
            return redirect()->route('user.dashboard');
        }

        // Enhanced search with keyword mapping
        $searchTerms = $this->expandSearchTerms($query);
        
        $teas = Tea::where(function($q) use ($searchTerms) {
                // Search original query
                $q->where('name', 'LIKE', "%{$searchTerms['original']}%")
                  ->orWhere('flavor', 'LIKE', "%{$searchTerms['original']}%")
                  ->orWhere('caffeine_level', 'LIKE', "%{$searchTerms['original']}%")
                  ->orWhere('health_benefit', 'LIKE', "%{$searchTerms['original']}%");
                
                // Search expanded terms
                foreach ($searchTerms['expanded'] as $term) {
                    $q->orWhere('name', 'LIKE', "%{$term}%")
                      ->orWhere('flavor', 'LIKE', "%{$term}%")
                      ->orWhere('caffeine_level', 'LIKE', "%{$term}%")
                      ->orWhere('health_benefit', 'LIKE', "%{$term}%");
                }
                
                // Search individual words (for compound terms like "strawberry tea")
                foreach ($searchTerms['words'] as $word) {
                    if (strlen($word) > 2) { // Skip very short words
                        $q->orWhere('name', 'LIKE', "%{$word}%")
                          ->orWhere('flavor', 'LIKE', "%{$word}%")
                          ->orWhere('caffeine_level', 'LIKE', "%{$word}%")
                          ->orWhere('health_benefit', 'LIKE', "%{$word}%");
                    }
                }
            })
            ->get()
            ->map(function($tea) {
                // Add average rating to each tea
                $tea->average_rating = $tea->averageRating();
                $tea->total_ratings = $tea->totalRatings();
                $tea->user_rating = $tea->userRating(auth()->id());
                return $tea;
            });

        return view('user.search-results', compact('teas', 'query'));
    }

    /**
     * Expand search terms with synonyms and related concepts
     */
    private function expandSearchTerms($query)
    {
        $keywordMap = [
            // Blood circulation related terms
            'blood circulation' => ['blood pressure', 'blood sugar', 'cholesterol', 'heart health', 'cardiovascular'],
            'circulation' => ['blood pressure', 'blood flow', 'heart health', 'cardiovascular'],
            'blood pressure' => ['blood pressure', 'heart health', 'cardiovascular', 'circulation'],
            'heart' => ['heart health', 'blood pressure', 'cardiovascular'],
            
            // Stress and relaxation
            'stress' => ['stress relief', 'relax', 'calm', 'anxiety', 'sedative'],
            'relax' => ['relaxation', 'calm', 'sedative', 'stress relief'],
            'anxiety' => ['stress relief', 'calm', 'sedative', 'relax'],
            
            // Digestion
            'digest' => ['digestion', 'digestive', 'stomach', 'gut'],
            'stomach' => ['digestion', 'digestive', 'stomach', 'gut'],
            
            // Energy
            'energy' => ['boost', 'vitality', 'stimulate', 'awake'],
            'focus' => ['mental', 'concentration', 'brain'],
            
            // Weight
            'weight' => ['weight loss', 'metabolism', 'burn', 'slim', 'fat'],
            'metabolism' => ['weight loss', 'metabolism', 'burn'],
            
            // Sleep
            'sleep' => ['sleep', 'insomnia', 'rest'],
            'insomnia' => ['sleep', 'rest', 'sedative'],
            
            // Pain
            'pain' => ['pain relief', 'ache', 'relief', 'soothe'],
            'headache' => ['pain', 'headache', 'migraine'],
            
            // General wellness
            'health' => ['health', 'wellness', 'immune', 'antioxidant'],
            'immune' => ['immune system', 'immunity', 'antioxidant'],
            
            // Flavor mappings (bitter = herbal)
            'bitter' => ['herbal', 'herb', 'earthy', 'plant', 'natural', 'medicinal'],
            'herbal' => ['bitter', 'herb', 'earthy', 'plant', 'natural', 'medicinal'],
            
            // Flavor mappings (any = various)
            'any' => ['various', 'mixed', 'blend', 'combination'],
            'various' => ['any', 'mixed', 'blend', 'combination'],
        ];

        // Tea-specific compound terms and patterns
        $teaPatterns = [
            // Common tea compound patterns
            'tea' => [], // This will be handled separately
            'green tea' => ['green'],
            'black tea' => ['black'],
            'herbal tea' => ['herbal', 'bitter'],
            'bitter tea' => ['bitter', 'herbal'],
            'fruit tea' => ['fruit', 'fruity'],
            'mint tea' => ['mint', 'minty'],
            'chamomile tea' => ['chamomile'],
            'ginger tea' => ['ginger'],
            'lemon tea' => ['lemon'],
            'strawberry tea' => ['strawberry'],
            'berry tea' => ['berry', 'berries'],
            'spicy tea' => ['spicy', 'spice'],
            'sweet tea' => ['sweet', 'honey', 'sugary'],
            'any tea' => ['any', 'various', 'mixed', 'blend'],
            'various tea' => ['various', 'any', 'mixed', 'blend'],
        ];

        $expanded = [];
        $lowerQuery = strtolower($query);
        
        // 1. Extract individual words from the query
        $words = $this->extractWords($lowerQuery);
        
        // 2. Handle compound tea patterns
        foreach ($teaPatterns as $compound => $components) {
            if (str_contains($lowerQuery, $compound)) {
                $expanded = array_merge($expanded, $components);
            }
        }
        
        // 3. Check for exact matches in keyword map
        if (isset($keywordMap[$lowerQuery])) {
            $expanded = array_merge($expanded, $keywordMap[$lowerQuery]);
        }
        
        // 4. Check for partial matches in keyword map
        foreach ($keywordMap as $key => $terms) {
            if (str_contains($lowerQuery, $key) || str_contains($key, $lowerQuery)) {
                $expanded = array_merge($expanded, $terms);
            }
        }
        
        // 5. Check each word individually
        foreach ($words as $word) {
            if (strlen($word) > 2) { // Skip very short words
                // Check keyword map for individual words
                if (isset($keywordMap[$word])) {
                    $expanded = array_merge($expanded, $keywordMap[$word]);
                }
                
                // Check partial matches for individual words
                foreach ($keywordMap as $key => $terms) {
                    if (str_contains($word, $key) || str_contains($key, $word)) {
                        $expanded = array_merge($expanded, $terms);
                    }
                }
            }
        }
        
        // 6. Remove duplicates and original query
        $expanded = array_unique(array_diff($expanded, [$lowerQuery]));
        
        return [
            'original' => $query,
            'words' => $words,
            'expanded' => $expanded
        ];
    }
    
    /**
     * Extract meaningful words from search query
     */
    private function extractWords($query)
    {
        // Remove common stop words and split into words
        $stopWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'is', 'are', 'was', 'were', 'be', 'been', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should', 'may', 'might', 'must', 'can', 'this', 'that', 'these', 'those', 'i', 'you', 'he', 'she', 'it', 'we', 'they', 'me', 'him', 'her', 'us', 'them'];
        
        // Split query into words and filter out stop words
        $words = preg_split('/\s+/', $query);
        $meaningfulWords = array_filter($words, function($word) use ($stopWords) {
            return !in_array(strtolower($word), $stopWords) && strlen(trim($word)) > 0;
        });
        
        return array_values($meaningfulWords);
    }
}


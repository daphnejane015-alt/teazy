<?php

namespace App\Services;

use App\Models\User;
use App\Models\Tea;
use App\Models\Rating;
use App\Models\Weather;

class RecommendationService
{
    // Weight constants for scoring
    private const FLAVOR_WEIGHT = 0.35; 
    private const CAFFEINE_WEIGHT = 0.15; 
    private const HEALTH_WEIGHT = 0.25; 
    private const WEATHER_WEIGHT = 0.25;

    // Enhanced health synonyms for semantic matching
    private $healthSynonyms = [
        'relax_calm' => [
            'relax', 'calm', 'peaceful', 'soothing', 'stress', 'anxiety', 'sleep', 
            'sedative', 'rest', 'tranquil', 'serene', 'meditation', 'unwind', 'de-stress',
            'headache relief', 'anti-anxiety', 'nervous', 'tension'
        ],
        'stress' => [
            'stress relief', 'relax', 'calm', 'peaceful', 'soothing', 'anxiety', 
            'tension', 'pressure', 'overwhelm', 'burnout', 'mental', 'emotional',
            'nervous', 'anti-anxiety', 'de-stress', 'unwind'
        ],
        'weight_loss' => [
            'weight', 'metabolism', 'burn', 'slim', 'fat', 'loss', 'reduce', 
            'manage', 'control', 'diet', 'calorie', 'slimming', 'trim', 'shed'
        ],
        'digest' => [
            'digestion', 'digestive', 'stomach', 'gut', 'bloating', 'comfort', 
            'aid', 'promote', 'regulate', 'soothe', 'settled', 'balance', 'health'
        ],
        'blood_circulation' => [
            'circulation', 'blood', 'heart', 'flow', 'cardiovascular', 'pressure', 
            'cholesterol', 'sugar', 'vessels', 'pump', 'arteries', 'veins', 'cardiac'
        ],
        'body_relief' => [
            'pain', 'ache', 'relief', 'soothe', 'comfort', 'muscle', 'joint', 
            'inflammation', 'sore', 'tension', 'headache', 'migraine', 'body'
        ],
        'energy' => [
            'energy', 'boost', 'vitality', 'alertness', 'awake', 'stimulate', 
            'focus', 'concentration', 'power', 'stamina', 'endurance', 'refresh'
        ],
        'immune' => [
            'immune', 'immunity', 'antioxidant', 'defense', 'protect', 'resist', 
            'fight', 'system', 'strength', 'support', 'enhance', 'boost'
        ]
    ];

    // Enhanced flavor hierarchy for category matching
    private $flavorHierarchy = [
        'fruity' => [
            'berry', 'citrus', 'tropical', 'stone_fruit', 'apple', 'pear', 'fruit',
            'strawberry', 'blueberry', 'raspberry', 'orange', 'lemon', 'lime',
            'mango', 'pineapple', 'peach', 'plum', 'cherry', 'grape'
        ],
        'floral' => [
            'jasmine', 'rose', 'lavender', 'chamomile', 'hibiscus', 'flower',
            'blossom', 'gardenia', 'orchid', 'lily', 'daisy', 'elderflower'
        ],
        'earthy' => [
            'woody', 'nutty', 'mossy', 'soil', 'mushroom', 'earth', 'ground',
            'root', 'bark', 'leaf', 'grass', 'herb', 'spice', 'warm'
        ],
        'sweet' => [
            'honey', 'sugary', 'caramel', 'vanilla', 'sweet', 'candy', 'dessert',
            'molasses', 'maple', 'syrup', 'rich', 'smooth', 'creamy'
        ],
        'minty' => [
            'peppermint', 'spearmint', 'cool', 'fresh', 'mint', 'icy', 'sharp',
            'refreshing', 'clean', 'crisp', 'invigorating'
        ],
        'bitter' => [
            'sharp', 'strong', 'intense', 'bold', 'bitter', 'robust', 'dark',
            'deep', 'rich', 'complex', 'astringent', 'tangy', 'herbal', 'herb',
            'plant', 'botanical', 'natural', 'medicinal', 'therapeutic'
        ],
        'herbal' => [
            'herbal', 'herb', 'plant', 'botanical', 'natural', 'green', 'fresh',
            'aromatic', 'medicinal', 'therapeutic', 'healing', 'organic', 'bitter'
        ],
        'spicy' => [
            'spicy', 'spice', 'warm', 'hot', 'zing', 'kick', 'fire', 'heat',
            'cinnamon', 'ginger', 'clove', 'cardamom', 'pepper', 'zesty'
        ]
    ];

    // Flavor similarity mapping for database values
    private $flavorSimilarityMap = [
        // Database flavor -> Similar flavors
        'fruity' => ['fruity', 'fruit', 'berry', 'citrus', 'sweet'],
        'herbal' => ['herbal', 'herb', 'earthy', 'plant', 'natural', 'bitter'],
        'bitter' => ['bitter', 'herbal', 'herb', 'earthy', 'plant', 'natural'],
        'sweet' => ['sweet', 'honey', 'sugary', 'caramel', 'fruity'],
        'various' => ['various', 'mixed', 'blend', 'combination', 'any'],
        'any' => ['any', 'various', 'mixed', 'blend', 'combination'],
        'n/a' => ['n/a', 'none', 'neutral', 'mild', 'subtle']
    ];

    // Weather-based tea recommendations
    private $weatherTeaMapping = [
        'hot' => [
            'best_flavors' => ['minty', 'fruity', 'sweet'],
            'best_caffeine' => ['low', 'caffeine_free', 'none'],
            'best_health' => ['relax_calm', 'energy'],
            'avoid_caffeine' => ['high', 'medium_high']
        ],
        'cold' => [
            'best_flavors' => ['spicy', 'earthy', 'sweet', 'herbal'],
            'best_caffeine' => ['medium', 'high', 'medium_high'],
            'best_health' => ['energy', 'body_relief', 'blood_circulation'],
            'avoid_caffeine' => ['caffeine_free', 'none']
        ],
        'rainy' => [
            'best_flavors' => ['herbal', 'sweet', 'fruity', 'spicy'],
            'best_caffeine' => ['medium', 'low', 'medium_high'],
            'best_health' => ['relax_calm', 'digest', 'stress'],
            'avoid_caffeine' => ['high']
        ],
        'cloudy' => [
            'best_flavors' => ['earthy', 'spicy', 'herbal', 'fruity'],
            'best_caffeine' => ['medium', 'low', 'medium_high'],
            'best_health' => ['energy', 'relax_calm', 'immune'],
            'avoid_caffeine' => []
        ],
        'moderate' => [
            'best_flavors' => ['any'],
            'best_caffeine' => ['any'],
            'best_health' => ['any'],
            'avoid_caffeine' => []
        ]
    ];

    public function recommend(User $user)
    {
        $pref = $user->preference;

        if (!$pref) {
            return collect(); // no preference yet
        }

        $teas = Tea::all();
        $results = [];

        foreach ($teas as $tea) {
            $score = $this->calculateNormalizedScore($tea, $pref);
            
            $results[] = [
                'tea' => $tea,
                'score' => round($score, 2),
                'flavor_score' => round($this->calculateFlavorScore($tea->flavor, $pref->preferred_flavor), 2),
                'caffeine_score' => round($this->calculateCaffeineScore($tea->caffeine_level, $pref->preferred_caffeine), 2),
                'health_score' => round($this->calculateHealthScore($tea->health_benefit, $pref->health_goal), 2)
            ];
        }

        // Apply diversity algorithm
        $diverseRecommendations = $this->getDiverseRecommendations(collect($results), 5);
        
        // Apply contextual scoring
        return $diverseRecommendations->map(function($rec) {
            $contextualScore = $this->calculateContextualScore($rec['tea'], $rec['score']);
            $rec['contextual_score'] = round($contextualScore, 2);
            return $rec;
        })->sortByDesc('contextual_score')->values();
    }

    private function calculateNormalizedScore($tea, $preferences)
    {
        $flavorScore = $this->calculateFlavorScore($tea->flavor, $preferences->preferred_flavor);
        $caffeineScore = $this->calculateCaffeineScore($tea->caffeine_level, $preferences->preferred_caffeine);
        $healthScore = $this->calculateHealthScore($tea->health_benefit, $preferences->health_goal);

        $weatherActive = $preferences->weather_based_recommendations && $preferences->city;

        if ($weatherActive) {
            $weatherScore = $this->calculateWeatherScore($tea, $preferences);

            // Weighted average (0-100 scale) including the weather factor
            $finalScore = ($flavorScore * 100 * self::FLAVOR_WEIGHT) +
                         ($caffeineScore * 100 * self::CAFFEINE_WEIGHT) +
                         ($healthScore * 100 * self::HEALTH_WEIGHT) +
                         ($weatherScore * 100 * self::WEATHER_WEIGHT);
        } else {
            // Weather not chosen (e.g. Telegram, or website with weather disabled):
            // redistribute the weather weight across the remaining factors so they still sum to 1.0
            $base = self::FLAVOR_WEIGHT + self::CAFFEINE_WEIGHT + self::HEALTH_WEIGHT;

            $finalScore = ($flavorScore * 100 * (self::FLAVOR_WEIGHT / $base)) +
                         ($caffeineScore * 100 * (self::CAFFEINE_WEIGHT / $base)) +
                         ($healthScore * 100 * (self::HEALTH_WEIGHT / $base));
        }

        return min(100, max(0, $finalScore));
    }

    private function calculateFlavorScore($teaFlavor, $prefFlavor)
    {
        if ($prefFlavor === 'any') return 1.0;
        
        // Normalize both flavors for comparison
        $normalizedTeaFlavor = strtolower(trim($teaFlavor));
        $normalizedPrefFlavor = strtolower(trim($prefFlavor));
        
        // Exact match
        if ($normalizedTeaFlavor === $normalizedPrefFlavor) {
            return 1.0;
        }
        
        // Check flavor similarity mapping
        $teaSimilarFlavors = $this->flavorSimilarityMap[$normalizedTeaFlavor] ?? [$normalizedTeaFlavor];
        $prefSimilarFlavors = $this->flavorSimilarityMap[$normalizedPrefFlavor] ?? [$normalizedPrefFlavor];
        
        // Check if tea flavor matches user preference or similar flavors
        foreach ($prefSimilarFlavors as $prefSimilar) {
            if (in_array($prefSimilar, $teaSimilarFlavors)) {
                return 0.9; // High score for similar flavors
            }
        }
        
        // Direct fuzzy string matching
        $directSimilarity = $this->calculateStringSimilarity($teaFlavor, $prefFlavor);
        if ($directSimilarity > 0.7) {
            return $directSimilarity;
        }
        
        // Check hierarchy matching
        $subCategories = $this->flavorHierarchy[$prefFlavor] ?? [];
        foreach ($subCategories as $subCategory) {
            $similarity = $this->calculateStringSimilarity($teaFlavor, $subCategory);
            if ($similarity > 0.6) {
                return 0.7; // Good score for sub-category match
            }
        }
        
        // Check if tea flavor is in preference hierarchy
        $teaSubCategories = $this->flavorHierarchy[$teaFlavor] ?? [];
        foreach ($teaSubCategories as $teaSubCategory) {
            $similarity = $this->calculateStringSimilarity($prefFlavor, $teaSubCategory);
            if ($similarity > 0.6) {
                return 0.6; // Moderate score for reverse hierarchy match
            }
        }
        
        // Partial matching for broader compatibility
        if (str_contains($normalizedTeaFlavor, $normalizedPrefFlavor) || 
            str_contains($normalizedPrefFlavor, $normalizedTeaFlavor)) {
            return 0.5;
        }
        
        return 0.0;
    }

    private function calculateCaffeineScore($teaCaffeine, $prefCaffeine)
    {
        // Normalize caffeine levels for accurate comparison
        $normalizedTeaCaffeine = $this->normalizeCaffeineLevel($teaCaffeine);
        $normalizedPrefCaffeine = $this->normalizeCaffeineLevel($prefCaffeine);
        
        // Caffeine level hierarchy for scoring
        $caffeineHierarchy = [
            'caffeine_free' => ['none', 'caffeine-free', 'n/a', 'low'],
            'low' => ['low', 'low-medium', 'caffeine-free', 'none', 'n/a'],
            'medium' => ['medium', 'low-medium', 'medium-high', 'low'],
            'high' => ['high', 'medium-high', 'medium'],
        ];
        
        // Get the hierarchy levels for comparison
        $teaLevel = $this->getCaffeineHierarchyLevel($normalizedTeaCaffeine);
        $prefLevel = $this->getCaffeineHierarchyLevel($normalizedPrefCaffeine);
        
        // Calculate score based on hierarchy proximity
        return $this->calculateCaffeineHierarchyScore($teaLevel, $prefLevel);
    }
    
    /**
     * Normalize caffeine level names to standard format
     */
    private function normalizeCaffeineLevel($caffeine)
    {
        $normalized = strtolower(trim($caffeine));
        
        // Map various formats to standard names
        $mappings = [
            'caffeine-free' => 'caffeine_free',
            'caffeine free' => 'caffeine_free',
            'n/a' => 'none',
            'low-medium' => 'low_medium',
            'medium-high' => 'medium_high',
            'medium high' => 'medium_high',
            'low medium' => 'low_medium',
        ];
        
        return $mappings[$normalized] ?? $normalized;
    }
    
    /**
     * Get hierarchy level for caffeine level
     */
    private function getCaffeineHierarchyLevel($caffeine)
    {
        $hierarchy = [
            'caffeine_free' => 0,
            'none' => 0,
            'n/a' => 0,
            'low' => 1,
            'low_medium' => 2,
            'medium' => 3,
            'medium_high' => 4,
            'high' => 5,
        ];
        
        return $hierarchy[$caffeine] ?? 3; // Default to medium if unknown
    }
    
    /**
     * Calculate caffeine score based on hierarchy proximity
     */
    private function calculateCaffeineHierarchyScore($teaLevel, $prefLevel)
    {
        $difference = abs($teaLevel - $prefLevel);
        
        // Score based on how close the levels are
        switch ($difference) {
            case 0: // Exact match
                return 1.0;
            case 1: // Very close (one level difference)
                return 0.8;
            case 2: // Close (two levels difference)
                return 0.6;
            case 3: // Moderate (three levels difference)
                return 0.4;
            case 4: // Far (four levels difference)
                return 0.2;
            default: // Very far (five+ levels difference)
                return 0.1;
        }
    }

    private function calculateHealthScore($teaBenefit, $userGoal)
    {
        // Get expanded keywords for the health goal
        $keywords = $this->healthSynonyms[$userGoal] ?? [$this->getHealthKeyword($userGoal)];
        
        // Normalize tea benefit for comparison
        $normalizedBenefit = strtolower($teaBenefit);
        
        $matches = 0;
        $totalKeywords = count($keywords);
        $weightedMatches = 0;
        
        foreach ($keywords as $keyword) {
            $keywordLower = strtolower($keyword);
            
            // Exact phrase match (highest weight)
            if (str_contains($normalizedBenefit, $keywordLower)) {
                $matches++;
                $weightedMatches += 2.0; // Double weight for exact matches
                continue;
            }
            
            // Word-level matching
            $benefitWords = preg_split('/\s+/', $normalizedBenefit);
            $keywordWords = preg_split('/\s+/', $keywordLower);
            
            $wordMatches = 0;
            foreach ($keywordWords as $word) {
                if (strlen($word) > 2) { // Skip very short words
                    foreach ($benefitWords as $benefitWord) {
                        if ($benefitWord === $word) {
                            $wordMatches++;
                            break;
                        }
                    }
                }
            }
            
            // Partial word matching with fuzzy string similarity
            if ($wordMatches === 0) {
                foreach ($keywordWords as $word) {
                    if (strlen($word) > 2) {
                        foreach ($benefitWords as $benefitWord) {
                            $similarity = $this->calculateStringSimilarity($word, $benefitWord);
                            if ($similarity > 0.7) {
                                $wordMatches++;
                                break 2;
                            }
                        }
                    }
                }
            }
            
            // Score based on word matches
            if ($wordMatches > 0) {
                $matchRatio = $wordMatches / count($keywordWords);
                $matches++;
                $weightedMatches += $matchRatio;
            }
        }
        
        // Calculate final score with weighted average
        if ($totalKeywords === 0) {
            return 0.0;
        }
        
        $baseScore = $matches / $totalKeywords;
        $weightedScore = $weightedMatches / ($totalKeywords * 2); // Normalize by max possible weight
        
        // Combine base and weighted scores, favoring weighted matches
        $finalScore = ($baseScore * 0.3) + ($weightedScore * 0.7);
        
        return min(1.0, max(0.0, $finalScore));
    }

    private function calculateStringSimilarity($str1, $str2)
    {
        $str1 = strtolower(trim($str1));
        $str2 = strtolower(trim($str2));
        
        if ($str1 === $str2) return 1.0;
        if (empty($str1) || empty($str2)) return 0.0;
        
        $levenshtein = levenshtein($str1, $str2);
        $maxLength = max(strlen($str1), strlen($str2));
        
        return 1 - ($levenshtein / $maxLength);
    }

    private function calculateContextualScore($tea, $baseScore)
    {
        $contextMultiplier = 1.0;
        
        // Time of day preferences
        $hour = now()->hour;
        if ($hour < 12 && $tea->caffeine_level === 'high') {
            $contextMultiplier *= 1.2; // Boost high caffeine in morning
        } elseif ($hour > 20 && str_contains(strtolower($tea->caffeine_level), 'caffeine-free')) {
            $contextMultiplier *= 1.3; // Boost caffeine-free in evening
        }
        
        // Seasonal preferences
        $season = $this->getCurrentSeason();
        if ($season === 'winter' && str_contains(strtolower($tea->flavor), 'spicy')) {
            $contextMultiplier *= 1.1;
        } elseif ($season === 'summer' && str_contains(strtolower($tea->flavor), 'minty')) {
            $contextMultiplier *= 1.1;
        }
        
        return $baseScore * $contextMultiplier;
    }

    private function getCurrentSeason()
    {
        $month = now()->month;
        if ($month >= 3 && $month <= 5) return 'spring';
        if ($month >= 6 && $month <= 8) return 'summer';
        if ($month >= 9 && $month <= 11) return 'autumn';
        return 'winter';
    }

    private function getDiverseRecommendations($recommendations, $limit = 5)
    {
        $diverseRecs = [];
        $usedCategories = [];
        
        foreach ($recommendations->sortByDesc('score') as $rec) {
            $category = $rec['tea']->flavor;
            
            // Ensure diversity in flavor categories
            if (!in_array($category, $usedCategories) || count($diverseRecs) < $limit) {
                $diverseRecs[] = $rec;
                $usedCategories[] = $category;
                
                if (count($diverseRecs) >= $limit) break;
            }
        }
        
        return collect($diverseRecs);
    }

    /**
     * Get personalized recommendations based on user's rating history
     */
    public function personalizedRecommendations(User $user, $limit = 5)
    {
        // Get user's highly rated teas (4-5 stars)
        $highRatedTeas = Rating::where('user_id', $user->id)
            ->where('rating', '>=', 4)
            ->with('tea')
            ->get()
            ->pluck('tea');

        if ($highRatedTeas->isEmpty()) {
            // Fallback to preference-based recommendations
            return $this->recommend($user);
        }

        // Find similar teas based on attributes of highly rated teas
        $similarTeas = collect();
        
        foreach ($highRatedTeas as $likedTea) {
            // Find teas with similar flavor, caffeine, or health benefits
            $similar = Tea::where('id', '!=', $likedTea->id)
                ->where(function($query) use ($likedTea) {
                    $query->where('flavor', $likedTea->flavor)
                          ->orWhere('caffeine_level', $likedTea->caffeine_level)
                          ->orWhere('health_benefit', 'like', '%' . substr($likedTea->health_benefit, 0, 20) . '%');
                })
                ->whereDoesntHave('ratings', function($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->get();
            
            $similarTeas = $similarTeas->merge($similar);
        }

        // Score and rank similar teas
        $results = [];
        foreach ($similarTeas as $tea) {
            $score = 0;
            
            // Compare with user's liked teas
            foreach ($highRatedTeas as $likedTea) {
                if ($tea->flavor === $likedTea->flavor) $score += 2;
                if ($tea->caffeine_level === $likedTea->caffeine_level) $score += 1;
                if (str_contains(strtolower($tea->health_benefit), 
                               strtolower(substr($likedTea->health_benefit, 0, 20)))) $score += 1;
            }
            
            // Add popularity bonus (average rating)
            $score += $tea->averageRating() * 0.5;
            
            $results[] = [
                'tea' => $tea,
                'score' => $score,
                'type' => 'personalized'
            ];
        }

        return collect($results)
            ->unique('tea.id')
            ->sortByDesc('score')
            ->take($limit);
    }

    /**
     * Get collaborative filtering recommendations based on similar users
     */
    public function collaborativeRecommendations(User $user, $limit = 5)
    {
        // Find users with similar rating patterns
        $userRatings = Rating::where('user_id', $user->id)->pluck('rating', 'tea_id');
        
        if ($userRatings->isEmpty()) {
            return collect();
        }

        $similarUsers = Rating::whereIn('tea_id', $userRatings->keys())
            ->where('user_id', '!=', $user->id)
            ->get()
            ->groupBy('user_id')
            ->map(function($ratings) use ($userRatings) {
                $similarity = 0;
                foreach ($ratings as $rating) {
                    if (isset($userRatings[$rating->tea_id])) {
                        // Simple similarity: same rating = +1, different rating = -1
                        $similarity += ($rating->rating == $userRatings[$rating->tea_id]) ? 1 : -1;
                    }
                }
                return $similarity;
            })
            ->filter(function($similarity) {
                return $similarity > 0; // Only keep positively similar users
            })
            ->sortDesc()
            ->take(5);

        if ($similarUsers->isEmpty()) {
            return collect();
        }

        // Get teas liked by similar users that current user hasn't rated
        $recommendations = Rating::whereIn('user_id', $similarUsers->keys())
            ->where('rating', '>=', 4)
            ->whereNotIn('tea_id', $userRatings->keys())
            ->with('tea')
            ->get()
            ->groupBy('tea_id')
            ->map(function($ratings) {
                return [
                    'tea' => $ratings->first()->tea,
                    'score' => $ratings->avg('rating') * $ratings->count(), // Weight by rating and frequency
                    'type' => 'collaborative'
                ];
            })
            ->sortByDesc('score')
            ->take($limit);

        return collect($recommendations->values());
    }

    /**
     * Map health goal form values to search keywords
     */
    private function getHealthKeyword(string $healthGoal): string
    {
        $keywords = [
            'relax_calm' => 'relax',
            'digest' => 'digest',
            'stress' => 'stress',
            'weight_loss' => 'weight',
            'blood_circulation' => 'circulation',
            'body_relief' => 'pain',
        ];

        return $keywords[$healthGoal] ?? $healthGoal;
    }

    /**
     * Calculate weather-based tea score
     */
    private function calculateWeatherScore($tea, $preferences)
    {
        // If weather-based recommendations are disabled, return neutral score
        if (!$preferences->weather_based_recommendations || !$preferences->city) {
            return 0.5; // Neutral score
        }

        // Get current weather for user's city
        $weather = Weather::forCity($preferences->city);
        
        if (!$weather) {
            return 0.5; // Neutral score if no weather data
        }

        $weatherCategory = $weather->getTeaCategory();
        $weatherMapping = $this->weatherTeaMapping[$weatherCategory] ?? $this->weatherTeaMapping['moderate'];

        // Override with user's weather preference if set
        if ($preferences->weather_preference && $preferences->weather_preference !== 'auto') {
            $weatherMapping = $this->getUserWeatherMapping($preferences->weather_preference);
        }

        $score = 0.5; // Base score
        $factors = 0;

        // Check flavor compatibility
        if (in_array('any', $weatherMapping['best_flavors'])) {
            $score += 0.2;
            $factors++;
        } else {
            foreach ($weatherMapping['best_flavors'] as $flavor) {
                if ($this->isFlavorMatch($tea->flavor, $flavor)) {
                    $score += 0.2;
                    $factors++;
                    break;
                }
            }
        }

        // Check caffeine compatibility
        if (in_array('any', $weatherMapping['best_caffeine'])) {
            $score += 0.2;
            $factors++;
        } else {
            foreach ($weatherMapping['best_caffeine'] as $caffeine) {
                if ($this->isCaffeineMatch($tea->caffeine_level, $caffeine)) {
                    $score += 0.2;
                    $factors++;
                    break;
                }
            }
        }

        // Check health goal compatibility
        if (in_array('any', $weatherMapping['best_health'])) {
            $score += 0.2;
            $factors++;
        } else {
            foreach ($weatherMapping['best_health'] as $health) {
                if ($this->calculateHealthScore($tea->health_benefit, $health) > 0.5) {
                    $score += 0.2;
                    $factors++;
                    break;
                }
            }
        }

        // Penalty for caffeine to avoid in certain weather
        foreach ($weatherMapping['avoid_caffeine'] as $avoidCaffeine) {
            if ($this->isCaffeineMatch($tea->caffeine_level, $avoidCaffeine)) {
                $score -= 0.3;
                break;
            }
        }

        // Normalize score based on factors checked
        if ($factors > 0) {
            $score = min(1.0, max(0.0, $score));
        }

        return $score;
    }

    /**
     * Get user's specific weather mapping
     */
    private function getUserWeatherMapping($preference)
    {
        $mappings = [
            'hot_weather' => $this->weatherTeaMapping['hot'],
            'cold_weather' => $this->weatherTeaMapping['cold'],
            'rainy_days' => $this->weatherTeaMapping['rainy'],
            'auto' => null // Will use actual weather
        ];

        return $mappings[$preference] ?? $this->weatherTeaMapping['moderate'];
    }

    /**
     * Check if flavor matches weather recommendation
     */
    private function isFlavorMatch($teaFlavor, $recommendedFlavor)
    {
        $teaFlavor = strtolower(trim($teaFlavor));
        $recommendedFlavor = strtolower(trim($recommendedFlavor));

        if ($teaFlavor === $recommendedFlavor) {
            return true;
        }

        // Check similarity mapping
        $similarFlavors = $this->flavorSimilarityMap[$teaFlavor] ?? [$teaFlavor];
        return in_array($recommendedFlavor, $similarFlavors);
    }

    /**
     * Check if caffeine level matches weather recommendation
     */
    private function isCaffeineMatch($teaCaffeine, $recommendedCaffeine)
    {
        $teaCaffeine = $this->normalizeCaffeineLevel($teaCaffeine);
        $recommendedCaffeine = $this->normalizeCaffeineLevel($recommendedCaffeine);

        return $teaCaffeine === $recommendedCaffeine;
    }
}

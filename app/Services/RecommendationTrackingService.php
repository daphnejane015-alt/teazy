<?php

namespace App\Services;

use App\Models\RecommendationInteraction;
use App\Models\User;
use App\Models\Tea;
use Carbon\Carbon;

class RecommendationTrackingService
{
    /**
     * Track user interaction with a tea recommendation.
     */
    public function trackInteraction($userId, $teaId, $action, $context = [])
    {
        $interaction = [
            'user_id' => $userId,
            'tea_id' => $teaId,
            'action' => $action,
            'weather_category' => $context['weather_category'] ?? null,
            'temperature' => $context['temperature'] ?? null,
            'humidity' => $context['humidity'] ?? null,
            'day_of_week' => $context['day_of_week'] ?? Carbon::now()->format('l'),
            'time_of_day' => $context['time_of_day'] ?? RecommendationInteraction::getTimeOfDayFromDate(now()),
            'season' => $context['season'] ?? RecommendationInteraction::getSeasonFromDate(now()),
            'user_flavor_preference' => $context['user_preferences']['flavor'] ?? null,
            'user_caffeine_preference' => $context['user_preferences']['caffeine'] ?? null,
            'user_health_goal' => $context['user_preferences']['health'] ?? null,
            'user_weather_preference' => $context['user_preferences']['weather_preference'] ?? null,
            'rating' => $context['rating'] ?? null,
            'algorithm_used' => $context['algorithm'] ?? 'rule-based-diversity',
            'confidence_score' => $context['confidence'] ?? null,
            'prediction_score' => $context['prediction_score'] ?? null,
            'features' => $context['features'] ?? null,
            'feature_importance' => $context['feature_importance'] ?? null,
            'model_version' => $context['model_version'] ?? null,
        ];

        return RecommendationInteraction::create($interaction);
    }

    /**
     * Track when a user views a recommended tea.
     */
    public function trackView($userId, $teaId, $context = [])
    {
        return $this->trackInteraction($userId, $teaId, 'viewed', $context);
    }

    /**
     * Track when a user rates a tea.
     */
    public function trackRating($userId, $teaId, $rating, $context = [])
    {
        $context['rating'] = $rating;
        return $this->trackInteraction($userId, $teaId, 'rated', $context);
    }

    /**
     * Track when a user likes a tea recommendation.
     */
    public function trackLike($userId, $teaId, $context = [])
    {
        return $this->trackInteraction($userId, $teaId, 'liked', $context);
    }

    /**
     * Track when a user dislikes a tea recommendation.
     */
    public function trackDislike($userId, $teaId, $context = [])
    {
        return $this->trackInteraction($userId, $teaId, 'disliked', $context);
    }

    /**
     * Track when a user ignores a tea recommendation.
     */
    public function trackIgnore($userId, $teaId, $context = [])
    {
        return $this->trackInteraction($userId, $teaId, 'ignored', $context);
    }

    /**
     * Get user behavior data for ML training.
     */
    public function getUserBehaviorData($userId, $days = 30)
    {
        return RecommendationInteraction::where('user_id', $userId)
            ->recent($days)
            ->with(['tea', 'user'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get similar users based on preferences and behavior.
     */
    public function getSimilarUsers($userId, $limit = 100)
    {
        $user = User::with('preferences')->find($userId);
        
        if (!$user || !$user->preferences) {
            return collect();
        }

        // Find users with similar preferences
        $similarUsers = User::whereHas('preferences', function($query) use ($user) {
            $query->where('preferred_flavor', $user->preferences->preferred_flavor)
                  ->orWhere('preferred_caffeine', $user->preferences->preferred_caffeine)
                  ->orWhere('health_goal', $user->preferences->health_goal);
        })->where('id', '!=', $userId)
        ->limit($limit)
        ->get();

        return $similarUsers;
    }

    /**
     * Get collaborative filtering data for similar users.
     */
    public function getSimilarUserChoices($similarUsers, $weatherCategory, $days = 30)
    {
        return RecommendationInteraction::whereIn('user_id', $similarUsers->pluck('id'))
            ->byWeatherCategory($weatherCategory)
            ->recent($days)
            ->with(['tea', 'user'])
            ->get();
    }

    /**
     * Get popularity data for teas in specific weather conditions.
     */
    public function getTeaPopularityByWeather($weatherCategory, $days = 30)
    {
        return RecommendationInteraction::byWeatherCategory($weatherCategory)
            ->recent($days)
            ->positive() // Only positive interactions
            ->selectRaw('tea_id, COUNT(*) as interaction_count, AVG(rating) as avg_rating')
            ->with('tea')
            ->groupBy('tea_id')
            ->orderBy('interaction_count', 'desc')
            ->get();
    }

    /**
     * Check if we have enough data to upgrade to next algorithm phase.
     */
    public function canUpgradeToNextPhase()
    {
        $totalInteractions = RecommendationInteraction::count();
        $uniqueUsers = RecommendationInteraction::distinct('user_id')->count('user_id');
        $avgInteractionsPerUser = $totalInteractions / max($uniqueUsers, 1);

        // Phase requirements
        $phases = [
            'behavior-learning' => ['min_interactions' => 1000, 'min_users' => 100, 'avg_per_user' => 5],
            'collaborative' => ['min_interactions' => 10000, 'min_users' => 1000, 'avg_per_user' => 10],
            'machine-learning' => ['min_interactions' => 100000, 'min_users' => 10000, 'avg_per_user' => 10],
        ];

        foreach ($phases as $phase => $requirements) {
            if ($totalInteractions >= $requirements['min_interactions'] &&
                $uniqueUsers >= $requirements['min_users'] &&
                $avgInteractionsPerUser >= $requirements['avg_per_user']) {
                return $phase;
            }
        }

        return 'rule-based';
    }

    /**
     * Get data quality score for ML model assessment.
     */
    public function getDataQualityScore()
    {
        $totalInteractions = RecommendationInteraction::count();
        $uniqueUsers = RecommendationInteraction::distinct('user_id')->count('user_id');
        $avgInteractionsPerUser = $totalInteractions / max($uniqueUsers, 1);

        // Quality factors
        $volumeScore = min($totalInteractions / 10000, 1.0); // Normalize to 0-1
        $varietyScore = min($avgInteractionsPerUser / 10, 1.0); // Normalize to 0-1
        $recencyScore = $this->getRecencyScore(); // How recent is the data

        return ($volumeScore + $varietyScore + $recencyScore) / 3;
    }

    /**
     * Get recency score based on recent activity.
     */
    private function getRecencyScore()
    {
        $recentInteractions = RecommendationInteraction::recent(7)->count();
        $totalInteractions = RecommendationInteraction::count();

        return $totalInteractions > 0 ? $recentInteractions / $totalInteractions : 0;
    }

    /**
     * Export training data for ML models.
     */
    public function exportTrainingData($format = 'json')
    {
        $interactions = RecommendationInteraction::with(['user.preferences', 'tea'])
            ->orderBy('created_at')
            ->get();

        $trainingData = $interactions->map(function($interaction) {
            return [
                'user_id' => $interaction->user_id,
                'tea_id' => $interaction->tea_id,
                'weather_category' => $interaction->weather_category,
                'temperature' => $interaction->temperature,
                'humidity' => $interaction->humidity,
                'day_of_week' => $interaction->day_of_week,
                'time_of_day' => $interaction->time_of_day,
                'season' => $interaction->season,
                'user_flavor_preference' => $interaction->user_flavor_preference,
                'user_caffeine_preference' => $interaction->user_caffeine_preference,
                'user_health_goal' => $interaction->user_health_goal,
                'user_weather_preference' => $interaction->user_weather_preference,
                'action' => $interaction->action,
                'rating' => $interaction->rating,
                'tea_flavor' => $interaction->tea->flavor,
                'tea_caffeine' => $interaction->tea->caffeine_level,
                'tea_health_benefit' => $interaction->tea->health_benefit,
                'tea_avg_rating' => $interaction->tea->averageRating(),
                'timestamp' => $interaction->created_at,
            ];
        });

        if ($format === 'json') {
            return $trainingData->toJson();
        } elseif ($format === 'csv') {
            // Implement CSV export if needed
            return $trainingData->toArray();
        }

        return $trainingData;
    }
}

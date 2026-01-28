<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecommendationInteraction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'tea_id',
        'weather_category',
        'temperature',
        'humidity',
        'day_of_week',
        'time_of_day',
        'season',
        'user_flavor_preference',
        'user_caffeine_preference',
        'user_health_goal',
        'user_weather_preference',
        'action',
        'rating',
        'algorithm_used',
        'confidence_score',
        'prediction_score',
        'features',
        'feature_importance',
        'model_version',
    ];

    protected $casts = [
        'temperature' => 'decimal:2',
        'humidity' => 'decimal:2',
        'confidence_score' => 'decimal:2',
        'prediction_score' => 'decimal:2',
        'features' => 'array',
        'feature_importance' => 'array',
        'rating' => 'integer',
    ];

    /**
     * Get the user that owns the interaction.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the tea that was interacted with.
     */
    public function tea()
    {
        return $this->belongsTo(Tea::class);
    }

    /**
     * Scope for getting interactions by action type.
     */
    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope for getting interactions by weather category.
     */
    public function scopeByWeatherCategory($query, $category)
    {
        return $query->where('weather_category', $category);
    }

    /**
     * Scope for getting recent interactions.
     */
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope for getting positive interactions (ratings >= 4).
     */
    public function scopePositive($query)
    {
        return $query->where(function($q) {
            $q->where('rating', '>=', 4)
              ->orWhere('action', 'liked');
        });
    }

    /**
     * Scope for getting negative interactions (ratings <= 2).
     */
    public function scopeNegative($query)
    {
        return $query->where(function($q) {
            $q->where('rating', '<=', 2)
              ->orWhere('action', 'disliked');
        });
    }

    /**
     * Get the season based on date.
     */
    public static function getSeasonFromDate($date)
    {
        $month = \Carbon\Carbon::parse($date)->month;
        
        if ($month >= 3 && $month <= 5) {
            return 'spring';
        } elseif ($month >= 6 && $month <= 8) {
            return 'summer';
        } elseif ($month >= 9 && $month <= 11) {
            return 'autumn';
        } else {
            return 'winter';
        }
    }

    /**
     * Get time of day based on hour.
     */
    public static function getTimeOfDayFromDate($date)
    {
        $hour = \Carbon\Carbon::parse($date)->hour;
        
        if ($hour >= 5 && $hour < 12) {
            return 'morning';
        } elseif ($hour >= 12 && $hour < 17) {
            return 'afternoon';
        } elseif ($hour >= 17 && $hour < 21) {
            return 'evening';
        } else {
            return 'night';
        }
    }
}

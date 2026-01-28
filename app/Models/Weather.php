<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Weather extends Model
{
    use HasFactory;

    protected $fillable = [
        'city',
        'country',
        'latitude',
        'longitude',
        'date',
        'temperature',
        'feels_like',
        'humidity',
        'wind_speed',
        'pressure',
        'main_condition',
        'description',
        'icon_code',
        'forecast_data'
    ];

    protected $casts = [
        'date' => 'date',
        'temperature' => 'decimal:2',
        'feels_like' => 'decimal:2',
        'wind_speed' => 'decimal:2',
        'forecast_data' => 'array'
    ];

    /**
     * Get weather for a specific city and date
     */
    public static function forCity($city, $date = null)
    {
        $date = $date ?: now()->toDateString();
        
        return self::where('city', $city)
                   ->where('date', $date)
                   ->first();
    }

    /**
     * Get weekly forecast for a city
     */
    public static function weeklyForecast($city)
    {
        $startDate = now()->toDateString();
        $endDate = now()->addDays(7)->toDateString();
        
        return self::where('city', $city)
                   ->whereBetween('date', [$startDate, $endDate])
                   ->orderBy('date')
                   ->get();
    }

    /**
     * Determine if weather is hot
     */
    public function isHot()
    {
        return $this->temperature >= 25;
    }

    /**
     * Determine if weather is cold
     */
    public function isCold()
    {
        return $this->temperature <= 10;
    }

    /**
     * Determine if weather is rainy
     */
    public function isRainy()
    {
        return in_array(strtolower($this->main_condition), ['rain', 'drizzle', 'thunderstorm']);
    }

    /**
     * Determine if weather is cloudy
     */
    public function isCloudy()
    {
        return in_array(strtolower($this->main_condition), ['clouds', 'mist', 'fog']);
    }

    /**
     * Determine if weather is clear
     */
    public function isClear()
    {
        return strtolower($this->main_condition) === 'clear';
    }

    /**
     * Get weather category for tea recommendations
     */
    public function getTeaCategory()
    {
        if ($this->isHot()) return 'hot';
        if ($this->isCold()) return 'cold';
        if ($this->isRainy()) return 'rainy';
        if ($this->isCloudy()) return 'cloudy';
        return 'moderate';
    }
}

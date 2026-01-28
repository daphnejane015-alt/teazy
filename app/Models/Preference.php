<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Preference extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id',
        'preferred_flavor',
        'preferred_caffeine',
        'health_goal',
        'city',
        'country',
        'latitude',
        'longitude',
        'weather_based_recommendations',
        'weather_preference',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }
}

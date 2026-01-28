<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Rating;

class Tea extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'flavor',
        'caffeine_level',
        'health_benefit',
        'image',
        'source',
        'source_url',
        'shop_link',
    ];

    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }

    public function averageRating()
    {
        return $this->ratings()->avg('rating') ?? 0;
    }

    public function totalRatings()
    {
        return $this->ratings()->count();
    }

    public function userRating($userId)
    {
        return $this->ratings()->where('user_id', $userId)->first();
    }

    public function favouritedBy()
    {
        return $this->belongsToMany(User::class, 'favourites')->withTimestamps();
    }

    public function isFavourite($userId)
    {
        return $this->favouritedBy()->where('user_id', $userId)->exists();
    }

}

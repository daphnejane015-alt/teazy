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
        'ai_description',
        'ai_description_generated_at',
        'image',
        'source',
        'source_url',
        'shop_link',
        'shopee_link',
        'lazada_link',
    ];

    protected $casts = [
        'ai_description_generated_at' => 'datetime',
    ];

    /**
     * Clean up related records when a tea is deleted.
     */
    protected static function booted(): void
    {
        static::deleting(function (Tea $tea) {
            $tea->ratings()->delete();
            $tea->favouritedBy()->detach();
        });
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }

    public function averageRating()
    {
        return $this->ratings_avg_rating ?? ($this->ratings()->avg('rating') ?? 0);
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

    public function shopeeShopUrl(): string
    {
        return $this->shopee_link ?: 'https://shopee.com.my/search?keyword=' . urlencode($this->name);
    }

    public function lazadaShopUrl(): string
    {
        return $this->lazada_link ?: 'https://www.lazada.com.my/catalog/?q=' . urlencode($this->name);
    }

}

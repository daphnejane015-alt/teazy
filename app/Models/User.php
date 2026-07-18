<?php

namespace App\Models;

use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Rating;
use App\Models\Preference;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, MustVerifyEmailTrait, Notifiable;

    /**
     * Clean up related records when a user is deleted to avoid orphaned data.
     */
    protected static function booted(): void
    {
        static::deleting(function (User $user) {
            $user->ratings()->delete();
            $user->favourites()->detach();
            $user->preference()->delete();
            $user->recommendations()->delete();
            $user->telegramChats()->delete();
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'phone',
        'phone_number',
        'bio',
        'favorite_tea_type',
        'caffeine_preference',
        'role',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function ratings() {
        return $this->hasMany(Rating::class);
    }

    public function preference() {
        return $this->hasOne(Preference::class);
    }

    public function recommendations() {
        return $this->hasMany(\App\Models\RecommendationInteraction::class);
    }

    public function favourites() {
        return $this->belongsToMany(Tea::class, 'favourites')->withTimestamps();
    }

    public function telegramChats() {
        return $this->hasMany(TelegramChat::class);
    }
}

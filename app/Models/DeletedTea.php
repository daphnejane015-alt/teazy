<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeletedTea extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'normalized_name',
        'source',
        'deleted_by',
        'original_data',
    ];

    protected $casts = [
        'original_data' => 'array',
    ];

    /**
     * Check if a tea name was deleted by admin
     */
    public static function wasDeleted(string $name): bool
    {
        $normalized = strtolower(trim($name));
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        
        return self::where('normalized_name', $normalized)->exists();
    }

    /**
     * Record a deleted tea
     */
    public static function recordDeletion(Tea $tea, ?int $adminId = null): void
    {
        $normalized = strtolower(trim($tea->name));
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        
        self::create([
            'name' => $tea->name,
            'normalized_name' => $normalized,
            'source' => $tea->source,
            'deleted_by' => $adminId,
            'original_data' => [
                'flavor' => $tea->flavor,
                'caffeine_level' => $tea->caffeine_level,
                'health_benefit' => $tea->health_benefit,
                'source_url' => $tea->source_url,
                'shop_link' => $tea->shop_link,
                'image' => $tea->image,
            ],
        ]);
    }
}

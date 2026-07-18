<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeaAiDescription extends Model
{
    use HasFactory;

    protected $fillable = [
        'tea_id',
        'user_id',
        'description',
        'sources',
        'preference_signature',
        'generated_at',
    ];

    protected $casts = [
        'sources' => 'array',
        'generated_at' => 'datetime',
    ];

    public function tea()
    {
        return $this->belongsTo(Tea::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

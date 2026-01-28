<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'tea_id', 'rating', 'description'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tea()
    {
        return $this->belongsTo(Tea::class);
    }
}

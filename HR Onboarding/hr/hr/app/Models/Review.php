<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'goal_achievement' => 'array',
        'initiatives' => 'array',
        'next_steps' => 'array',
        'improvements' => 'array',
        'manager_feedback' => 'array',
        'date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

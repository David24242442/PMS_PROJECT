<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'year',
        'status',
        'emp_code',
        'name',
        'division',
        'position',
        'date',
        'goal_achievement',
        'initiatives',
        'next_steps',
        'improvements',
        'manager_feedback'
    ];

    protected $casts = [
        'goal_achievement' => 'array',
        'initiatives' => 'array',
        'next_steps' => 'array',
        'improvements' => 'array',
        'manager_feedback' => 'array',
        'date' => 'date'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

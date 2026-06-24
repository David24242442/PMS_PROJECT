<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Goal extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'description' => 'array',
        'purposes' => 'array',
        'challenges' => 'array',
        'smart_criteria' => 'array',
        'quarterly_tracking' => 'array',
        'appraisal_data' => 'array',
        'due_date' => 'date',
        'completion_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

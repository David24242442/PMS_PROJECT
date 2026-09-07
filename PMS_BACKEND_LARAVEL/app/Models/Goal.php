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
        'weight' => 'decimal:2',
        'rating' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getProgressPercentageAttribute()
    {
        if (!$this->target || $this->target == 0) return 0;
        return min(100, round(($this->actual / $this->target) * 100));
    }
}

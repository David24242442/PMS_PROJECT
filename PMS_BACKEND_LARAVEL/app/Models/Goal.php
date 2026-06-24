<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Goal extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'purposes',
        'challenges',
        'category',
        'weight',
        'target',
        'actual',
        'rating',
        'status',
        'due_date',
        'completion_date',
        'smart_criteria',
        'quarterly_tracking',
        'year',
        'created_by',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'rating' => 'integer',
        'due_date' => 'date',
        'completion_date' => 'date',
        'smart_criteria' => 'array',
        'quarterly_tracking' => 'array',
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

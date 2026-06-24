<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appraisal extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'year',
        'status',
        'overall_rating',
        'goals_data',
        'competencies_data',
        'self_assessment',
        'achievements',
        'improvements',
        'development_plan',
        'manager_comments',
        'hr_comments',
        'submitted_at',
        'reviewed_at',
        'approved_at',
        'reviewed_by',
        'approved_by',
        'candidate_name',
        'manager_signature_name',
        'candidate_signature_name',
        'signature_date',
    ];

    protected $casts = [
        'goals_data' => 'array',
        'competencies_data' => 'array',
        'overall_rating' => 'decimal:2',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeByYear($query, $year)
    {
        return $query->where('year', $year);
    }
}

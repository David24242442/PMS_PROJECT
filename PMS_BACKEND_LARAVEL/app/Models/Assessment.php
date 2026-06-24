<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assessment extends Model
{
    use HasFactory;

    protected $table = 'user_assessments';

    protected $fillable = [
        'user_id',
        'assessed_by',
        'checklist_items',
        'attendance_rating',
        'task_completion_rating',
        'quality_rating',
        'teamwork_rating',
        'initiative_rating',
        'strengths',
        'improvements',
        'action_items',
        'overall_notes',
        'assessment_date',
    ];

    protected $casts = [
        'checklist_items' => 'array',
        'attendance_rating' => 'integer',
        'task_completion_rating' => 'integer',
        'quality_rating' => 'integer',
        'teamwork_rating' => 'integer',
        'initiative_rating' => 'integer',
        'assessment_date' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assessor()
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }

    public static function getRatingLabels()
    {
        return [
            'attendance' => ['Excellent', 'Good', 'Needs Improvement', 'Poor'],
            'task_completion' => ['On Time', 'Slightly Delayed', 'Significantly Delayed', 'Incomplete'],
            'quality' => ['Outstanding', 'Above Average', 'Average', 'Below Average'],
            'teamwork' => ['Excellent Collaborator', 'Good Team Player', 'Works Independently', 'Needs Improvement'],
            'initiative' => ['Highly Proactive', 'Proactive', 'Reactive', 'Passive'],
        ];
    }

    public static function getChecklistItems()
    {
        return [
            'goals_set' => 'Goals have been set for this period',
            'training_completed' => 'Completed required training',
            'feedback_given' => 'Received performance feedback',
            'one_on_one' => 'One-on-one meeting conducted',
            'documentation_updated' => 'Documentation is up to date',
            'kpis_reviewed' => 'KPIs have been reviewed',
            'development_plan' => 'Development plan in place',
            'recognition_given' => 'Recognition given for achievements',
        ];
    }
}

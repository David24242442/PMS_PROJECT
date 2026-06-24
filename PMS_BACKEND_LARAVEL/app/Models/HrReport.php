<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'type',
        'department_id',
        'date_from',
        'date_to',
        'parameters',
        'data',
        'file_path',
        'generated_by',
        'status',
    ];

    protected $casts = [
        'parameters' => 'array',
        'data' => 'array',
        'date_from' => 'date',
        'date_to' => 'date',
    ];

    public function generator()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public static function getReportTypes()
    {
        return [
            'performance_summary' => 'Performance Summary',
            'department_metrics' => 'Department Metrics',
            'appraisal_status' => 'Appraisal Status Report',
            'goal_completion' => 'Goal Completion Report',
            'employee_assessment' => 'Employee Assessment Report',
            'monthly_review' => 'Monthly Performance Review',
            'quarterly_review' => 'Quarterly Performance Review',
            'annual_review' => 'Annual Performance Review',
        ];
    }
}

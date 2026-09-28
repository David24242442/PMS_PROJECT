<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OnlineOnboarding extends Model
{
    use HasFactory;

    protected $table = 'online_onboardings';

    protected $guarded = [];

    protected $casts = [
        'submission_data' => 'array',
        'approved_at' => 'datetime',
    ];

    public function approvingUser()
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'synced_employee_id');
    }
}

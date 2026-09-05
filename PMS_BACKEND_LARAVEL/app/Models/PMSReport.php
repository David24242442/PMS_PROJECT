<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PMSReport extends Model
{
    use HasFactory;

    protected $table = 'pms_reports';

    protected $guarded = [];

    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

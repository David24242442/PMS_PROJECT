<?php

namespace App\Models;

use App\Models\History;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BankSocial extends Model
{
    use HasFactory;

    protected $guarded = [];


    public function histories()
    {
        return $this->morphMany(History::class, 'histos')->latest();
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'emp_id');
    }

    // public function allremaininghistories()
    // {
    //     return $this->morphMany(History::class, 'histos')->latest()->offset(10)->get();
    // }

    public function lasthistory()
    {
        return $this->morphMany(History::class, 'histos')->take(10)->latest();
    }

    
}

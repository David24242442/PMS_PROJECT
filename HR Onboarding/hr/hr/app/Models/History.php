<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class History extends Model
{
    use HasFactory;

    protected $guarded = [];
    
    public function histos()
    {
        return $this->morphTo();
    }


    public function creator(){
        return $this->belongsTo(User::class,'user_id');
    }

}

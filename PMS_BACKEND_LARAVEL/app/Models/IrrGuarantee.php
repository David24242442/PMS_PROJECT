<?php

namespace App\Models;

use App\Models\IrrGuarWitness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class IrrGuarantee extends Model
{
    use HasFactory;

    protected $guarded = [];

    
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'emp_id');
    }

    public function witnesses(){
        return $this->hasMany(IrrGuarWitness::class,'irrguar_id');
    }

    public function uploads()
    {
        return $this->morphMany(Upload::class, 'uploadable');
    }

    public function idcard()
    {
        return $this->morphOne(Upload::class, 'uploadable')->where('type', 'idcard');
    }

    public function guarforms()
    {
        return $this->morphMany(Upload::class, 'uploadable')->where('type', 'guarform');
    }

    public function profilepicture()
    {
        return $this->morphOne(Upload::class, 'uploadable')->where('type', 'profilepicture');
    }
    
    public function signature()
    {
        return $this->morphOne(Upload::class, 'uploadable')->where('type', 'signature');
    }

    public function histories()
    {
        return $this->morphMany(History::class, 'histos');
    }

    public function lasthistory()
    {
        return $this->morphMany(History::class, 'histos')->take(10)->latest();
    }
}

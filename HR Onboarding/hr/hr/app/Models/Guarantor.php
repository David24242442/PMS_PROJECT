<?php

namespace App\Models;

use App\Models\Upload;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Guarantor extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function files()
    {
        return $this->morphMany(Upload::class, 'uploadable');
    }
}

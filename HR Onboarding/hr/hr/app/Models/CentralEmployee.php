<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class CentralEmployee extends Model
{
    use HasFactory;

    protected $connection = 'central';
    protected $table = 'employees';

    protected $guarded = [];

    /**
     * Override morph class so polymorphic relationships map to App\Models\Employee 
     * instead of App\Models\CentralEmployee on the central database.
     */
    public function getMorphClass()
    {
        return 'App\Models\Employee';
    }

    /**
     * Prevent creating records on the central database.
     */
    public function save(array $options = [])
    {
        throw new RuntimeException("Cannot save on a read-only central database connection.");
    }

    /**
     * Prevent updating records on the central database.
     */
    public function update(array $attributes = [], array $options = [])
    {
        throw new RuntimeException("Cannot update on a read-only central database connection.");
    }

    /**
     * Prevent deleting records on the central database.
     */
    public function delete()
    {
        throw new RuntimeException("Cannot delete on a read-only central database connection.");
    }

    public function uploads()
    {
        return $this->morphMany(CentralUpload::class, 'uploadable', 'uploadable_type', 'uploadable_id')
                    ->where('uploadable_type', 'App\Models\Employee');
    }

    public function profilepicture()
    {
        return $this->morphMany(CentralUpload::class, 'uploadable', 'uploadable_type', 'uploadable_id')
                    ->where('uploadable_type', 'App\Models\Employee')
                    ->where('type', 'profilepicture');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class CentralUpload extends Model
{
    use HasFactory;

    protected $connection = 'central';
    protected $table = 'uploads';

    protected $guarded = [];

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

    public function uploadable()
    {
        return $this->morphTo();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyEmployee extends Model
{
    use HasFactory;

    protected $table = 'Monthly_Employees';

    protected $guarded = [];

    /**
     * Dynamically resolve table name to match database casing.
     */
    public function getTable()
    {
        return \App\Http\Controllers\MonthlyEmployeeController::getActualTableName();
    }

    /**
     * Scope for searching by Emp ID, Employee Name, or Designation.
     */
    public function scopeSearch($query, $term)
    {
        if (!empty($term)) {
            $term = trim($term);
            return $query->where(function ($q) use ($term) {
                $q->where('emp_id', 'like', "%{$term}%")
                  ->orWhere('employee_name', 'like', "%{$term}%")
                  ->orWhere('designation', 'like', "%{$term}%")
                  ->orWhere('location', 'like', "%{$term}%");
            });
        }
        return $query;
    }

    /**
     * Scope for filtering by location.
     */
    public function scopeLocation($query, $loc)
    {
        if (!empty($loc) && $loc !== 'all') {
            return $query->where('location', $loc);
        }
        return $query;
    }

    /**
     * Scope for filtering by category (CONTRACT, PERMANENT, OUTSOURCE).
     */
    public function scopeCategory($query, $category)
    {
        if (!empty($category) && $category !== 'all') {
            return $query->where('category', $category);
        }
        return $query;
    }

    /**
     * Scope for filtering by month_year snapshot.
     */
    public function scopeMonthYear($query, $monthYear)
    {
        if (!empty($monthYear) && $monthYear !== 'all') {
            return $query->where('month_year', $monthYear);
        }
        return $query;
    }
}

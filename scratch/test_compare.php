<?php
require __DIR__ . '/../PMS_BACKEND_LARAVEL/vendor/autoload.php';
$app = require_once __DIR__ . '/../PMS_BACKEND_LARAVEL/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

try {
    echo "Default DB: " . config('database.default') . "\n";
    echo "DB Host: " . config('database.connections.mysql.host') . "\n";
    echo "DB Database: " . config('database.connections.mysql.database') . "\n";
    
    // Check tables
    $hasEmployees = Schema::hasTable('employees');
    $hasMonthly = Schema::hasTable('Monthly_Employees') || Schema::hasTable('monthly_employees');
    echo "Has employees: " . ($hasEmployees ? 'YES' : 'NO') . "\n";
    echo "Has Monthly_Employees: " . ($hasMonthly ? 'YES' : 'NO') . "\n";
    
    if ($hasEmployees) {
        echo "employees count: " . DB::table('employees')->count() . "\n";
        $statuses = DB::table('employees')->select('status', DB::raw('count(*) as count'))->groupBy('status')->get();
        echo "Status distribution in employees table:\n";
        foreach ($statuses as $s) {
            echo "  Status '{$s->status}': {$s->count}\n";
        }
    }
    
    if ($hasMonthly) {
        $mTable = Schema::hasTable('Monthly_Employees') ? 'Monthly_Employees' : 'monthly_employees';
        echo "Monthly_Employees count: " . DB::table($mTable)->count() . "\n";
        $batches = DB::table($mTable)->select('month_year', DB::raw('count(*) as count'))->groupBy('month_year')->get();
        echo "Batches in monthly employees:\n";
        foreach ($batches as $b) {
            echo "  Batch '{$b->month_year}': {$b->count}\n";
        }
    }
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

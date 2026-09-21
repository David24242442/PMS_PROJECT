<?php
require __DIR__ . '/../HR Onboarding/hr/hr/vendor/autoload.php';
$app = require_once __DIR__ . '/../HR Onboarding/hr/hr/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "hasTable Monthly_Employees: " . (Illuminate\Support\Facades\Schema::hasTable('Monthly_Employees') ? "YES" : "NO") . "\n";
    echo "hasTable monthly_employees: " . (Illuminate\Support\Facades\Schema::hasTable('monthly_employees') ? "YES" : "NO") . "\n";
    
    // Check if table exists in DB directly
    $tables = Illuminate\Support\Facades\DB::select("SHOW TABLES LIKE '%employee%'");
    print_r($tables);
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\DB;

echo "Scanning Users and Reporting Lines...\n";

// 1. Reset everyone to NOT a manager (except admins)
DB::table('users')->update(['is_manager' => false]);
DB::table('users')->where('admin', 1)->update(['is_manager' => true]);

// 2. Find all unique line_manager_id values from employees table
$managerIds = DB::table('employees')->whereNotNull('line_manager_id')->distinct()->pluck('line_manager_id')->toArray();

echo "Found " . count($managerIds) . " unique manager IDs in employees table.\n";

// 3. Mark these users as managers
$updated = DB::table('users')->whereIn('id', $managerIds)->update(['is_manager' => true]);

echo "Activated " . $updated . " managers based on reporting lines.\n";

// 4. List all users who are now managers
$managers = User::where('is_manager', true)->get();
foreach ($managers as $m) {
    echo "MANAGER: " . $m->id . " | " . $m->name . " (Admin: " . ($m->admin ? 'YES' : 'NO') . ")\n";
}

// 5. Special check for TEST 2
$test2 = User::where('name', 'like', '%TEST 2%')->first();
if ($test2) {
    echo "TEST 2 Status: ID=" . $test2->id . ", is_manager=" . ($test2->is_manager ? 'YES' : 'NO') . ", admin=" . ($test2->admin ? 'YES' : 'NO') . "\n";
    if (!$test2->admin && !in_array($test2->id, $managerIds)) {
        echo "TEST 2 should be hidden (not admin and no subordinates).\n";
    }
}

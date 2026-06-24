<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\User;

echo "--- DATA RECOVERY & REPAIR START ---\n";

// 1. Reset is_manager for safety - only admins stay managers by default
DB::table('users')->update(['is_manager' => false]);
DB::table('users')->where('admin', 1)->update(['is_manager' => true]);

// 2. Identify ACTIVE Managers based on CURRENT reporting lines in employees table
// We look for ANY user who appears in the 'line_manager_id' column
$activeManagerIds = DB::table('employees')
    ->whereNotNull('line_manager_id')
    ->distinct()
    ->pluck('line_manager_id')
    ->toArray();

echo "Found " . count($activeManagerIds) . " users who are actively managing teams.\n";

// 3. Mark these users as Managers
if (!empty($activeManagerIds)) {
    DB::table('users')->whereIn('id', $activeManagerIds)->update(['is_manager' => true]);
}

// 4. Special Force Activation for Samuel and Nana (just in case IDs changed)
$knownManagers = ['Samuel Nana Elegba', 'Nana Ama Dwum Bediako', 'Prashant Dudhane', 'Saurabh Bhartia', 'Sandesh Mayekar', 'Rajesh Tambe', 'Mahendar Konkati', 'Silju Matthews', 'Ajay Singh'];
foreach ($knownManagers as $name) {
    $updated = DB::table('users')->where('name', 'like', '%' . $name . '%')->update(['is_manager' => true]);
    if ($updated) echo "Restored Manager: $name\n";
}

// 5. Explicitly HIDE TEST 2 unless he has a team
$test2 = DB::table('users')->where('name', 'like', '%TEST 2%')->first();
if ($test2) {
    $hasTeam = DB::table('employees')->where('line_manager_id', $test2->id)->exists();
    if (!$hasTeam && !$test2->admin) {
        DB::table('users')->where('id', $test2->id)->update(['is_manager' => false]);
        echo "Hidden TEST 2 (No subordinates found).\n";
    }
}

echo "--- REPAIR COMPLETE ---\n";
echo "Managers currently in Console:\n";
$current = User::where('is_manager', true)->pluck('name')->toArray();
foreach($current as $c) echo " - $c\n";

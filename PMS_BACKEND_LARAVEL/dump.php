<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$emp = App\Models\Employee::where('firstname', 'like', '%CLI%')->with('profilepicture')->first();
if ($emp) {
    print_r($emp->toArray());
} else {
    echo "No matching employee found.\n";
}

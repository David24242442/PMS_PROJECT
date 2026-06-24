<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    \DB::connection()->getPdo();
    echo "CONNECTED SUCCESS\n";
    $tables = \DB::select('SHOW TABLES');
    echo "TABLES FOUND: " . count($tables) . "\n";
} catch (\Exception $e) {
    echo "CONNECTION FAILED: " . $e->getMessage() . "\n";
}

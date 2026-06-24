<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$user = User::where('username', 'admin')->first();
if ($user) {
    $user->password = Hash::make('admin@321');
    $user->save();
    echo "Password for user 'admin' updated successfully.\n";
} else {
    echo "User 'admin' not found.\n";
}

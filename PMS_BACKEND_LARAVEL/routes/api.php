<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use App\Http\Controllers\APIUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/login', [APIUserController::class, 'login']);

// Emergency route to fix DB
Route::get('/fix-db', function() {
    if (!Schema::hasColumn('users', 'department')) {
        Schema::table('users', function (Blueprint $table) {
            $table->string('department')->nullable()->after('email');
        });
        return 'Fixed: Added department column';
    }
    return 'Database already okay (department column exists)';
});

Route::get('/run-migrate', function() {
    try {
        \Illuminate\Support\Facades\Artisan::call('migrate');
        return 'Migration Success: <pre>' . \Illuminate\Support\Facades\Artisan::output() . '</pre>';
    } catch (\Exception $e) {
        return 'Migration Failed: ' . $e->getMessage();
    }
});

Route::get('/run-storage-link', function() {
    try {
        \Illuminate\Support\Facades\Artisan::call('storage:link');
        return 'Storage Link created: <pre>' . \Illuminate\Support\Facades\Artisan::output() . '</pre>';
    } catch (\Exception $e) {
        return 'Storage Link Failed: ' . $e->getMessage();
    }
});

Route::get('/dump-emp', function() {
    return App\Models\Employee::where('firstname', 'like', '%CLI%')->with('profilepicture')->first();
});

Route::get('/run-route-clear', function() {
    try {
        \Illuminate\Support\Facades\Artisan::call('route:clear');
        return 'Route Cache Cleared: <pre>' . \Illuminate\Support\Facades\Artisan::output() . '</pre>';
    } catch (\Exception $e) {
        return 'Route Clear Failed: ' . $e->getMessage();
    }
});

Route::get('/file/{folder}/{filename}', function($folder, $filename) {
    set_time_limit(30); // Prevent timeout
    $path = $folder . '/' . $filename;
    
    // 1) Direct Candidates (current backend storage)
    $paths = [
        storage_path('app/public/' . $path),
        storage_path('app/' . $path),
        storage_path($path),
        public_path('storage/' . $path),
    ];

    // 1b) Also check the OLD PMS backend storage paths on Server 20
    //     Files uploaded before migration live in the old pms_backend location
    $oldPmsPaths = [
        'C:/xampp/htdocs/PMS/pms_backend/storage/app/public/' . $path,
        'C:/xampp/htdocs/PMS/pms_backend/public/storage/' . $path,
        'C:/xampp/htdocs/PMS/pms_backend/storage/app/' . $path,
        'C:/xampp/htdocs/PMS/pms_backend/storage/' . $path,
        // Also check goal_attachments folder (old PMS used this name)
        'C:/xampp/htdocs/PMS/pms_backend/storage/app/public/goal_attachments/' . $filename,
    ];
    $paths = array_merge($paths, $oldPmsPaths);

    // 1c) Check any Backups directory on Server 20
    $backupBase = 'C:/Backups';
    if (is_dir($backupBase)) {
        $backupDirs = glob($backupBase . '/PMS_Server20_*', GLOB_ONLYDIR);
        // Sort descending to check most recent backup first
        rsort($backupDirs);
        foreach ($backupDirs as $bDir) {
            $paths[] = $bDir . '/PMS/pms_backend/storage/app/public/' . $path;
            $paths[] = $bDir . '/PMS/pms_backend/storage/app/public/goal_attachments/' . $filename;
        }
    }

    foreach ($paths as $filePath) {
        if (file_exists($filePath)) {
            return response()->file($filePath);
        }
    }

    // 2) DEEP Recursive Crawler fallback Search!
    $searchDir = storage_path();
    if (is_dir($searchDir)) {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($searchDir),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isDir() && $file->getFilename() === $filename) {
                return response()->file($file->getRealPath());
            }
        }
    }

    $dirToCheck = storage_path('app/public/' . $folder);
    $existing = is_dir($dirToCheck) ? array_diff(scandir($dirToCheck), ['.', '..']) : ['folder-absent'];

    // 3) Proxy Fallback to Server 17!
    try {
        $server17Urls = [
            "http://192.168.0.17/hr/public/storage/{$folder}/{$filename}",
            "http://192.168.0.17/hr/api/file/{$folder}/{$filename}"
        ];

        foreach ($server17Urls as $url) {
            $response = \Illuminate\Support\Facades\Http::timeout(5)->get($url);
            if ($response->successful()) {
                $contentType = $response->header('Content-Type') ?: 'application/octet-stream';

                // Cache the file locally so future requests don't need to proxy
                $localDir = storage_path('app/public/' . $folder);
                if (!is_dir($localDir)) {
                    mkdir($localDir, 0755, true);
                }
                file_put_contents($localDir . '/' . $filename, $response->body());

                return response($response->body(), 200)->header('Content-Type', $contentType);
            }
        }
    } catch (\Exception $e) {
        \Log::error('Central Image Proxy Error: ' . $e->getMessage());
    }

    return response('File not found. Paths checked: ' . implode(', ', array_slice($paths, 0, 4)) . ' | Dir Content: ' . implode(', ', $existing), 404);
});

Route::get('/create-admin', function () {
    $user = App\Models\User::updateOrCreate(
        ['id' => 1], // Force update ID 1
        [
            'username' => 'Admin',
            'name' => 'Administrator',
            'email' => 'admin@email.com',
            'password' => bcrypt('Password'),
            'admin' => 1,
            'department' => 'IT HEAD', // Ensuring permissions for PMS
            'position_id' => 1 // Dummy ID
        ]
    );
    return response()->json(['message' => 'Admin password reset to: Password', 'user' => $user]);
});


// Additional routes found in APIUserController
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/users', [APIUserController::class, 'index']);
    Route::post('/users', [APIUserController::class, 'store']);
    Route::post('/updateuser', [APIUserController::class, 'updateuser']); // Assuming this is the endpoint
    Route::post('/deleteuser', [APIUserController::class, 'destroy']);
    Route::delete('/users/{id}', [APIUserController::class, 'destroy']);
    Route::post('/updatepassword', [APIUserController::class, 'updatepassword']);
    
    // Dashboard Routes
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::post('/loadEmpDepts', [DashboardController::class, 'loadEmpDepts']);

    // Employee Routes
    Route::post('/fetchemployees', [EmployeeController::class, 'index']);
    Route::post('/fetchemployeesforexport', [EmployeeController::class, 'fetchemployeesforexport']);
    Route::post('/fetchemployeesdumpforexport', [EmployeeController::class, 'fetchemployeesdumpforexport']);
    Route::post('/searchemp', [EmployeeController::class, 'search']);
    Route::post('/employee', [EmployeeController::class, 'show']);
    Route::post('/updateempstatus', [EmployeeController::class, 'updateempstatus']);

    // PMS Routes
    Route::prefix('pms')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\PMSDashboardController::class, 'index']);
        Route::get('/leaderboard', [\App\Http\Controllers\PMSDashboardController::class, 'leaderboard']);
        
        Route::get('/employee-master', [\App\Http\Controllers\EmployeeMasterController::class, 'index']);
        Route::patch('/employee-master/{id}', [\App\Http\Controllers\EmployeeMasterController::class, 'update']);
        Route::get('/my-team', [\App\Http\Controllers\EmployeeMasterController::class, 'myTeam']);
        Route::get('/get-employees', [\App\Http\Controllers\EmployeeMasterController::class, 'getEmployees']);
        Route::post('/sync-team', [\App\Http\Controllers\EmployeeMasterController::class, 'syncTeam']);
        Route::post('/parse-team-csv', [\App\Http\Controllers\EmployeeMasterController::class, 'parseTeamCsv']);
        
        Route::get('/goals', [\App\Http\Controllers\GoalController::class, 'index']);
        Route::post('/goals', [\App\Http\Controllers\GoalController::class, 'store']);
        Route::post('/goals/assign', [\App\Http\Controllers\GoalController::class, 'assign']);
        Route::patch('/goals/{id}', [\App\Http\Controllers\GoalController::class, 'update']);
        Route::delete('/goals/{id}', [\App\Http\Controllers\GoalController::class, 'destroy']);
        Route::post('/goals/upload-attachment', [\App\Http\Controllers\GoalController::class, 'uploadAttachment']);

        // Review Routes
        Route::get('/reviews', [\App\Http\Controllers\ReviewController::class, 'index']);
        Route::post('/reviews', [\App\Http\Controllers\ReviewController::class, 'store']);

        // Appraisal Routes
        Route::get('/appraisals', [\App\Http\Controllers\GoalController::class, 'appraisals']);
        Route::get('/appraisals/all', [\App\Http\Controllers\GoalController::class, 'allAppraisals']);
        Route::patch('/appraisals/{id}/review', [\App\Http\Controllers\GoalController::class, 'reviewAppraisal']);
        Route::get('/manager-template', [\App\Http\Controllers\GoalController::class, 'getManagerTemplate']);
        Route::post('/manager-template', [\App\Http\Controllers\GoalController::class, 'saveManagerTemplate']);

        // Reports Routes
        Route::get('/reports', [\App\Http\Controllers\ReportsController::class, 'index']);
        Route::post('/reports', [\App\Http\Controllers\ReportsController::class, 'store']);
        Route::delete('/reports/{id}', [\App\Http\Controllers\ReportsController::class, 'destroy']);
    });
});

<?php


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\APIUserController;

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

Route::middleware('auth:sanctum')->group(function(){

    // User Management
    Route::post('fetchusers', [APIUserController::class,'index']);
    Route::post('fetchemployees', [APIUserController::class,'fetchEmployees']);
    Route::post('fetchuser', [APIUserController::class,'show']);
    Route::post('fetchemployee', [APIUserController::class,'fetchEmployee']);
    Route::post('adduser', [APIUserController::class,'store']);
    Route::post('updateuser', [APIUserController::class,'updateuser']);
    Route::post('updatepassword', [APIUserController::class,'updatepassword']);

    // --- PMS Routes ---
    Route::prefix('pms')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\PMSDashboardController::class, 'index']);
        
        Route::apiResource('goals', \App\Http\Controllers\GoalController::class);
        Route::post('goals/upload-attachment', [\App\Http\Controllers\GoalController::class, 'uploadAttachment']);
        
        Route::get('/appraisals', [\App\Http\Controllers\AppraisalController::class, 'show']);
        Route::get('/appraisals/all', [\App\Http\Controllers\AppraisalController::class, 'index']);
        Route::post('/appraisals', [\App\Http\Controllers\AppraisalController::class, 'store']);
        Route::patch('/appraisals/{appraisal}/review', [\App\Http\Controllers\AppraisalController::class, 'review']);
        
        Route::get('/assessments', [\App\Http\Controllers\AssessmentController::class, 'index']);
        Route::post('/assessments', [\App\Http\Controllers\AssessmentController::class, 'store']);

        // Reviews
        Route::get('/reviews', [\App\Http\Controllers\ReviewController::class, 'show']);
        Route::post('/reviews', [\App\Http\Controllers\ReviewController::class, 'store']);
        
        Route::apiResource('reports', \App\Http\Controllers\HrReportController::class);

        // Drafts
        Route::post('/drafts', [\App\Http\Controllers\DraftController::class, 'store']);
        Route::get('/drafts', [\App\Http\Controllers\DraftController::class, 'show']);
        Route::delete('/drafts', [\App\Http\Controllers\DraftController::class, 'destroy']);

        // Employee Master & Hierarchy
        Route::get('/employee-master', [\App\Http\Controllers\EmployeeMasterController::class, 'index']);
        Route::patch('/employee-master/{id}', [\App\Http\Controllers\EmployeeMasterController::class, 'update']);
        Route::get('/my-team', [\App\Http\Controllers\EmployeeMasterController::class, 'myTeam']);
    });
});

Route::post('/login', [APIUserController::class, 'login'])->name('login');




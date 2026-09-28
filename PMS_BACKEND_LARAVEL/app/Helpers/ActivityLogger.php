<?php

namespace App\Helpers;

use App\Models\UserSessionLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class ActivityLogger
{
    /**
     * Auto-ensure user_sessions_logs table exists in database.
     */
    public static function ensureTableExists()
    {
        try {
            if (!Schema::hasTable('user_sessions_logs')) {
                Schema::create('user_sessions_logs', function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('user_id')->nullable()->index();
                    $table->string('user_name')->nullable();
                    $table->string('employee_code')->nullable()->index();
                    $table->string('action', 50)->index();
                    $table->string('module', 50)->index();
                    $table->text('description');
                    $table->string('ip_address', 50)->nullable();
                    $table->text('user_agent')->nullable();
                    $table->longText('details')->nullable();
                    $table->timestamps();
                });
            }
        } catch (\Throwable $e) {
            \Log::warning('ActivityLogger ensureTableExists error: ' . $e->getMessage());
        }
    }

    /**
     * Record an activity or session event across the system.
     *
     * @param string $action LOGIN, LOGOUT, SUBMIT, CREATE, UPDATE, DELETE, APPROVE, REJECT, SYNC
     * @param string $module AUTH, ONBOARDING, PMS_GOALS, PMS_APPRAISAL, PMS_REVIEW, EMPLOYEE_MASTER, USERS
     * @param string $description Human-readable event description
     * @param array|null $details Additional context/payload
     * @param \App\Models\User|null $user Specific user override
     * @return \App\Models\UserSessionLog|null
     */
    public static function log($action, $module, $description, $details = null, $user = null)
    {
        try {
            self::ensureTableExists();

            if (!$user) {
                $user = Auth::user();
            }

            $userId = $user ? $user->id : null;
            $userName = $user ? ($user->name ?: ($user->username ?: 'User #' . $userId)) : 'System / Guest';
            $empCode = $user ? ($user->employee_code ?: ($user->emp_id ?? null)) : null;

            $ip = Request::ip();
            $userAgent = Request::header('User-Agent');

            return UserSessionLog::create([
                'user_id' => $userId,
                'user_name' => $userName,
                'employee_code' => $empCode,
                'action' => strtoupper(trim($action)),
                'module' => strtoupper(trim($module)),
                'description' => $description,
                'ip_address' => $ip,
                'user_agent' => $userAgent ? substr($userAgent, 0, 255) : null,
                'details' => is_array($details) || is_object($details) ? $details : null,
            ]);
        } catch (\Throwable $e) {
            \Log::warning('ActivityLogger error: ' . $e->getMessage());
            return null;
        }
    }
}

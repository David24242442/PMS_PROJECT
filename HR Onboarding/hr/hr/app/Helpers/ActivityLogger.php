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

            // 1. Check passed user object
            if (!$user) {
                $user = Auth::user() ?: Request::user();
            }

            // 2. Check Bearer token if user is still not resolved
            if (!$user) {
                try {
                    $bearer = Request::bearerToken();
                    if ($bearer && class_exists('\Laravel\Sanctum\PersonalAccessToken')) {
                        $tokenModel = \Laravel\Sanctum\PersonalAccessToken::findToken($bearer);
                        if ($tokenModel && $tokenModel->tokenable) {
                            $user = $tokenModel->tokenable;
                        }
                    }
                } catch (\Throwable $te) {}
            }

            // 3. Check request inputs if still not resolved
            if (!$user) {
                $reqUserId = Request::input('user_id') ?: Request::input('auth_user_id');
                $reqEmpCode = Request::input('employee_code') ?: Request::input('emp_code') ?: Request::input('username');
                if ($reqUserId) {
                    $user = \App\Models\User::find($reqUserId);
                } elseif ($reqEmpCode) {
                    $user = \App\Models\User::where('employee_code', $reqEmpCode)
                        ->orWhere('username', $reqEmpCode)
                        ->first();
                }
            }

            $userId = $user ? $user->id : (Request::input('user_id') ?: null);
            $userName = $user ? ($user->name ?: ($user->username ?: 'User #' . $userId)) : (Request::input('user_name') ?: Request::input('candidate_name') ?: 'Portal User');
            $empCode = $user ? ($user->employee_code ?: ($user->emp_id ?? null)) : (Request::input('employee_code') ?: Request::input('emp_code') ?: null);

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

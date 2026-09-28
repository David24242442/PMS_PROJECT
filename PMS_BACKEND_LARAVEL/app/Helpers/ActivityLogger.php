<?php

namespace App\Helpers;

use App\Models\UserSessionLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
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
            // Ensure table exists safely
            if (!\Illuminate\Support\Facades\Schema::hasTable('user_sessions_logs')) {
                return null;
            }

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

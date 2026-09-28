<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserSessionLog;
use App\Helpers\ActivityLogger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SessionLogController extends Controller
{
    /**
     * List user activity and session logs with filtering, search, and summary stats.
     */
    public function index(Request $request)
    {
        try {
            // Auto-create table if not exists
            ActivityLogger::ensureTableExists();

            $authUser = $request->user() ?: Auth::user();
            $today = Carbon::today();

            // If current user is authenticated, ensure their active session is logged for today
            if ($authUser) {
                $hasLoggedToday = UserSessionLog::where('user_id', $authUser->id)
                    ->whereDate('created_at', $today)
                    ->exists();

                if (!$hasLoggedToday) {
                    $empCode = $authUser->employee_code ?: ($authUser->emp_id ?? 'USR-' . $authUser->id);
                    ActivityLogger::log('LOGIN', 'AUTH', "User {$authUser->name} ({$empCode}) active session verified on PMS.", [
                        'username' => $authUser->username,
                        'employee_code' => $empCode,
                        'department' => $authUser->department,
                        'role' => $authUser->admin ? 'Admin' : ($authUser->is_manager ? 'Manager' : 'Employee')
                    ], $authUser);
                }
            }

            $query = UserSessionLog::query()->orderBy('id', 'desc');

            // Search query across multiple fields
            if ($request->filled('search')) {
                $search = trim($request->input('search'));
                $query->where(function ($q) use ($search) {
                    $q->where('user_name', 'like', "%{$search}%")
                      ->orWhere('employee_code', 'like', "%{$search}%")
                      ->orWhere('action', 'like', "%{$search}%")
                      ->orWhere('module', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('ip_address', 'like', "%{$search}%");
                });
            }

            // Action filter
            if ($request->filled('action') && $request->input('action') !== 'ALL') {
                $query->where('action', strtoupper(trim($request->input('action'))));
            }

            // Module filter
            if ($request->filled('module') && $request->input('module') !== 'ALL') {
                $query->where('module', strtoupper(trim($request->input('module'))));
            }

            // Date Range filters
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', Carbon::parse($request->input('date_from'))->startOfDay());
            }
            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', Carbon::parse($request->input('date_to'))->endOfDay());
            }

            $perPage = (int)($request->input('per_page', 20));
            if ($perPage < 5) $perPage = 5;
            if ($perPage > 200) $perPage = 200;

            $logs = $query->paginate($perPage);

            // Compute summary metrics
            $stats = [
                'total_logs' => UserSessionLog::count(),
                'today_logins' => UserSessionLog::whereDate('created_at', $today)->where('action', 'LOGIN')->count(),
                'today_activities' => UserSessionLog::whereDate('created_at', $today)->count(),
                'unique_users_today' => UserSessionLog::whereDate('created_at', $today)->whereNotNull('user_id')->distinct('user_id')->count('user_id'),
                'data_changes_today' => UserSessionLog::whereDate('created_at', $today)
                    ->whereIn('action', ['CREATE', 'UPDATE', 'DELETE', 'APPROVE', 'REJECT', 'SYNC', 'SUBMIT'])
                    ->count()
            ];

            return response()->json([
                'status' => 'success',
                'data' => $logs,
                'stats' => $stats
            ]);
        } catch (\Throwable $e) {
            \Log::error('SessionLogController index error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load activity logs: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear logs by date filter or clear all logs.
     */
    public function clear(Request $request)
    {
        try {
            ActivityLogger::ensureTableExists();
            $user = $request->user() ?: Auth::user();
            $type = $request->input('type', 'all'); // 'all', 'date_range', 'older_than'
            $deletedCount = 0;

            if ($type === 'date_range') {
                $from = $request->input('date_from');
                $to = $request->input('date_to');

                if (!$from || !$to) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Please provide both start date and end date.'
                    ], 422);
                }

                $query = UserSessionLog::whereDate('created_at', '>=', Carbon::parse($from)->startOfDay())
                                       ->whereDate('created_at', '<=', Carbon::parse($to)->endOfDay());
                $deletedCount = $query->count();
                $query->delete();
                $msg = "Deleted {$deletedCount} log records between {$from} and {$to}.";

            } elseif ($type === 'older_than') {
                $date = $request->input('older_than_date');
                if (!$date) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Please specify a cutoff date.'
                    ], 422);
                }

                $query = UserSessionLog::whereDate('created_at', '<', Carbon::parse($date)->startOfDay());
                $deletedCount = $query->count();
                $query->delete();
                $msg = "Deleted {$deletedCount} log records older than {$date}.";

            } else {
                // Clear all logs
                $deletedCount = UserSessionLog::count();
                UserSessionLog::truncate();
                $msg = "Cleared all {$deletedCount} activity log records.";
            }

            // Write an audit log entry of who cleared the logs
            ActivityLogger::log('DELETE', 'AUTH', "Activity logs cleared by " . ($user ? $user->name : 'Admin') . " ({$msg})", [
                'type' => $type,
                'deleted_records' => $deletedCount,
            ], $user);

            return response()->json([
                'status' => 'success',
                'message' => $msg,
                'deleted_count' => $deletedCount
            ]);
        } catch (\Throwable $e) {
            \Log::error('SessionLogController clear error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to clear logs: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Record a manual client-side action log.
     */
    public function store(Request $request)
    {
        ActivityLogger::ensureTableExists();
        $user = $request->user() ?: Auth::user();
        $action = $request->input('action', 'LOGIN');
        $module = $request->input('module', 'AUTH');
        $description = $request->input('description', 'User active session recorded.');
        $details = $request->input('details');

        $log = ActivityLogger::log($action, $module, $description, $details, $user);

        return response()->json([
            'status' => 'success',
            'data' => $log
        ]);
    }
}

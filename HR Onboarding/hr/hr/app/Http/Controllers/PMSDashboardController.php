<?php

namespace App\Http\Controllers;

use App\Models\Goal;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PMSDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $isAdmin = $user && $user->admin;

        if ($isAdmin) {
            // Admin sees global stats
            try {
                $totalEmployees = \App\Models\CentralEmployee::count();
            } catch (\Exception $e) {
                \Log::error('Central DB Fallback Error: ' . $e->getMessage());
                $totalEmployees = User::count();
            }
            $goalQuery = Goal::query();
            $leaderboardUsers = null;
        } else {
            $isManager = $user->is_manager || $user->position_id === 1 || $user->admin;
            if (!$isManager) {
                try {
                    $isManager = \Schema::hasTable('employees') && \Schema::hasColumn('employees', 'line_manager_id') && Employee::where('line_manager_id', $user->id)->exists();
                } catch (\Throwable $e) {}
            }

            if ($isManager) {
                $teamUserIds = [];
                $teamEmpCodes = [];
                try {
                    if (\Schema::hasTable('users') && \Schema::hasColumn('users', 'line_manager_id')) {
                        $teamUserIds = User::where('line_manager_id', $user->id)->pluck('id')->toArray();
                    }
                    if (\Schema::hasTable('employees') && \Schema::hasColumn('employees', 'line_manager_id')) {
                        if (\Schema::hasColumn('employees', 'employeeid')) {
                            $teamEmpCodes = Employee::where('line_manager_id', $user->id)->pluck('employeeid')->filter()->toArray();
                        }
                        if (empty($teamEmpCodes) && \Schema::hasColumn('employees', 'emp_code')) {
                            $teamEmpCodes = Employee::where('line_manager_id', $user->id)->pluck('emp_code')->filter()->toArray();
                        }
                    }
                } catch (\Throwable $e) {}

                $hasCreatedBy = \Schema::hasColumn('goals', 'created_by');
                $hasManagerName = \Schema::hasColumn('goals', 'manager_name');
                $hasEmployeeCode = \Schema::hasColumn('goals', 'employee_code');

                $goalQuery = Goal::where(function ($q) use ($user, $teamUserIds, $teamEmpCodes, $hasCreatedBy, $hasManagerName, $hasEmployeeCode) {
                    $q->where('user_id', $user->id);
                    if ($hasCreatedBy) {
                        $q->orWhere('created_by', $user->id);
                    }
                    if ($hasManagerName && !empty($user->name)) {
                        $q->orWhere('manager_name', $user->name)
                          ->orWhere('manager_name', 'like', '%' . $user->name . '%');
                    }
                    if (!empty($teamUserIds)) {
                        $q->orWhereIn('user_id', $teamUserIds);
                    }
                    if (!empty($teamEmpCodes) && $hasEmployeeCode) {
                        $q->orWhereIn('employee_code', $teamEmpCodes);
                    }
                });

                $totalEmployees = max(1, count($teamEmpCodes) ?: count($teamUserIds) ?: 1);
                $leaderboardUsers = array_values(array_unique(array_merge([$user->id], $teamUserIds)));
            } else {
                $totalEmployees = 1;
                $hasEmployeeCode = \Schema::hasColumn('goals', 'employee_code');
                $goalQuery = Goal::where(function ($q) use ($user, $hasEmployeeCode) {
                    $q->where('user_id', $user->id);
                    if (!empty($user->employee_code) && $hasEmployeeCode) {
                        $q->orWhere('employee_code', $user->employee_code);
                    }
                });
                $leaderboardUsers = $user->id;
            }
        }

        $totalGoals = (clone $goalQuery)->count();
        $completedGoals = (clone $goalQuery)->where('status', 'completed')->count();
        $pendingAppraisals = (clone $goalQuery)->where('status', 'submitted')->count();
        $approvedAppraisals = (clone $goalQuery)->whereIn('status', ['approved', 'review_completed'])->count();

        if ($isAdmin) {
            $usersWithGoals = Goal::distinct('user_id')->count('user_id');
            $completionRate = $totalEmployees > 0 ? round(($usersWithGoals / $totalEmployees) * 100, 1) : 0;
        } else {
            $completionRate = $totalGoals > 0 ? round(($completedGoals / $totalGoals) * 100, 1) : 0;
        }

        // Recent Goals
        $recentGoals = (clone $goalQuery)->with('user')
            ->orderBy('updated_at', 'desc')
            ->take(5)
            ->get();

        // Weekly Progress (Goals created in the last 7 days)
        $days = [];
        $counts = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $days[] = $date->format('D');
            $counts[] = (clone $goalQuery)->whereDate('created_at', $date->toDateString())->count();
        }

        // Top Employees — admin sees global leaderboard, manager sees team, employee sees own
        $topEmployees = $this->buildLeaderboard(5, $leaderboardUsers);

        return response()->json([
            'status' => 'success',
            'data' => [
                'stats' => [
                    'total_employees' => $totalEmployees,
                    'total_goals' => $totalGoals,
                    'completed_goals' => $completedGoals,
                    'pending_appraisals' => $pendingAppraisals,
                    'approved_appraisals' => $approvedAppraisals,
                    'completion_rate' => $completionRate
                ],
                'weekly_progress' => [
                    'labels' => $days,
                    'data' => $counts
                ],
                'recent_goals' => $recentGoals,
                'top_employees' => $topEmployees
            ]
        ]);
    }

    /**
     * Leaderboard endpoint — returns all employees ranked by rating and goal completion.
     */
    public function leaderboard(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'data' => $this->buildLeaderboard()
        ]);
    }

    /**
     * Shared helper to build leaderboard from all goals.
     * Groups by candidate_name (trimmed, lowercased) to avoid duplicates.
     */
    private function buildLeaderboard($limit = null, $userId = null)
    {
        $query = Goal::with('user');
        if (is_array($userId)) {
            $query->whereIn('user_id', $userId);
        } elseif ($userId) {
            $query->where('user_id', $userId);
        }
        $goals = $query->get();

        $employeeMap = [];

        foreach ($goals as $goal) {
            // Use candidate_name if available, otherwise user name
            $name = trim($goal->candidate_name ?: ($goal->user->name ?? 'Unknown'));
            $key = strtolower($name); // deduplicate by normalized name

            if (!isset($employeeMap[$key])) {
                $employeeMap[$key] = [
                    'user_id' => $goal->user_id,
                    'name' => $name,
                    'department' => $goal->department ?: ($goal->user->department ?? ''),
                    'job_title' => $goal->job_title ?: ($goal->user->position_id ?? 'Staff'),
                    'total_goals' => 0,
                    'completed_goals' => 0,
                    'total_rating' => 0,
                    'rated_count' => 0,
                    'goals' => [],
                ];
            }

            $emp = &$employeeMap[$key];
            $emp['total_goals']++;

            if (in_array($goal->status, ['completed', 'approved', 'review_completed'])) {
                $emp['completed_goals']++;
            }

            // Extract rating from appraisal_data competencies
            $rating = 0;
            $appraisalData = is_array($goal->appraisal_data) ? $goal->appraisal_data : json_decode($goal->appraisal_data ?? '{}', true);
            $competencies = $appraisalData['competencies'] ?? [];
            if (count($competencies) > 0) {
                $ratedComps = array_filter($competencies, fn($c) => floatval($c['managerRating'] ?? 0) > 0);
                if (count($ratedComps) > 0) {
                    $sum = array_sum(array_map(fn($c) => floatval($c['managerRating']), $ratedComps));
                    $rating = $sum / count($ratedComps);
                }
            }

            if ($rating > 0) {
                $emp['total_rating'] += $rating;
                $emp['rated_count']++;
            }

            $emp['goals'][] = [
                'id' => $goal->id,
                'title' => $goal->title,
                'status' => $goal->status,
                'target' => $goal->target,
                'actual' => $goal->actual,
                'due_date' => $goal->due_date ? $goal->due_date->format('Y-m-d') : null,
                'completion_date' => $goal->completion_date ? $goal->completion_date->format('Y-m-d') : null,
                'rating' => $rating > 0 ? round($rating, 1) : null,
                'category' => $goal->category,
            ];
        }

        // Compute averages and sort by rating desc
        $leaderboard = [];
        foreach ($employeeMap as $emp) {
            $avgRating = $emp['rated_count'] > 0 ? round($emp['total_rating'] / $emp['rated_count'], 1) : 0;
            $completionPct = $emp['total_goals'] > 0 ? round(($emp['completed_goals'] / $emp['total_goals']) * 100, 1) : 0;

            $leaderboard[] = [
                'user_id' => $emp['user_id'],
                'name' => $emp['name'],
                'department' => $emp['department'],
                'job_title' => $emp['job_title'],
                'avg_rating' => $avgRating,
                'completion_pct' => $completionPct,
                'total_goals' => $emp['total_goals'],
                'completed_goals' => $emp['completed_goals'],
                'goals' => $emp['goals'],
            ];
        }

        // Sort: highest rating first, then by completion %
        usort($leaderboard, function ($a, $b) {
            if ($b['avg_rating'] != $a['avg_rating']) return $b['avg_rating'] <=> $a['avg_rating'];
            return $b['completion_pct'] <=> $a['completion_pct'];
        });

        if ($limit) {
            $leaderboard = array_slice($leaderboard, 0, $limit);
        }

        return $leaderboard;
    }
}

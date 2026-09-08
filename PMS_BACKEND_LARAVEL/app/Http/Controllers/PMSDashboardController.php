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
            $isManager = $user->is_manager || $user->position_id === 3 || $user->position_id === 4 || $user->admin;
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

        // Fetch all goals matching the user's scope
        $allGoals = (clone $goalQuery)->with('user')->get();

        $deptMap = [];
        $managerMap = [];
        $ratingTiers = [
            'tier5' => ['tier' => 5, 'label' => 'Outstanding (4.5 - 5.0)', 'short' => 'Outstanding', 'count' => 0, 'color' => '#10b981'],
            'tier4' => ['tier' => 4, 'label' => 'Exceeds (3.5 - 4.4)', 'short' => 'Exceeds', 'count' => 0, 'color' => '#3b82f6'],
            'tier3' => ['tier' => 3, 'label' => 'Meets (2.5 - 3.4)', 'short' => 'Meets', 'count' => 0, 'color' => '#6366f1'],
            'tier2' => ['tier' => 2, 'label' => 'Needs Imp. (1.5 - 2.4)', 'short' => 'Needs Imp.', 'count' => 0, 'color' => '#f59e0b'],
            'tier1' => ['tier' => 1, 'label' => 'Unacceptable (< 1.5)', 'short' => 'Unacceptable', 'count' => 0, 'color' => '#ef4444'],
            'unrated' => ['tier' => 0, 'label' => 'Pending / Unrated', 'short' => 'Unrated', 'count' => 0, 'color' => '#94a3b8']
        ];
        $competencyScores = [];
        $underperformingStaff = [];
        $pendingBottlenecks = [];
        $totalOrgRating = 0;
        $totalOrgRatedCount = 0;

        $statusCounts = [
            'assigned' => 0,
            'in_progress' => 0,
            'submitted' => 0,
            'appraisal_completed' => 0,
            'review_completed' => 0,
            'draft' => 0
        ];

        foreach ($allGoals as $goal) {
            $statusKey = $goal->status ?: 'draft';
            if (isset($statusCounts[$statusKey])) {
                $statusCounts[$statusKey]++;
            }

            // Extract rating from appraisal_data competencies or overall_rating
            $rating = 0;
            $appData = is_array($goal->appraisal_data) ? $goal->appraisal_data : json_decode($goal->appraisal_data ?? '{}', true);
            $competencies = $appData['competencies'] ?? [];
            if (!empty($competencies) && is_array($competencies)) {
                $ratedComps = array_filter($competencies, fn($c) => floatval($c['managerRating'] ?? 0) > 0);
                if (count($ratedComps) > 0) {
                    $sum = array_sum(array_map(fn($c) => floatval($c['managerRating']), $ratedComps));
                    $rating = $sum / count($ratedComps);
                }
                // Track per competency scores
                foreach ($competencies as $comp) {
                    $cTitle = trim($comp['title'] ?? 'Competency');
                    $cRating = floatval($comp['managerRating'] ?? 0);
                    if ($cRating > 0 && !empty($cTitle)) {
                        if (!isset($competencyScores[$cTitle])) {
                            $competencyScores[$cTitle] = ['total' => 0, 'count' => 0];
                        }
                        $competencyScores[$cTitle]['total'] += $cRating;
                        $competencyScores[$cTitle]['count']++;
                    }
                }
            }

            if ($rating == 0 && !empty($goal->overall_rating)) {
                $rating = floatval($goal->overall_rating);
            }

            if ($rating > 0) {
                $totalOrgRating += $rating;
                $totalOrgRatedCount++;
            }

            // Rating tier classification
            if ($rating >= 4.5) {
                $ratingTiers['tier5']['count']++;
            } elseif ($rating >= 3.5) {
                $ratingTiers['tier4']['count']++;
            } elseif ($rating >= 2.5) {
                $ratingTiers['tier3']['count']++;
            } elseif ($rating >= 1.5) {
                $ratingTiers['tier2']['count']++;
            } elseif ($rating > 0) {
                $ratingTiers['tier1']['count']++;
            } else {
                $ratingTiers['unrated']['count']++;
            }

            $candidateName = trim($goal->candidate_name ?: ($goal->user->name ?? 'Employee #' . $goal->id));
            $empCode = $goal->employee_code ?: ($goal->user->employee_code ?? '');
            $dept = trim($goal->department ?: ($goal->user->department ?? 'General')) ?: 'General';
            $mgrName = trim($goal->manager_name ?: 'Unassigned') ?: 'Unassigned';

            // Underperforming staff check (Needs improvement: 0 < rating < 3.0)
            if ($rating > 0 && $rating < 3.0) {
                $underperformingStaff[] = [
                    'id' => $goal->id,
                    'name' => $candidateName,
                    'employee_code' => $empCode,
                    'department' => $dept,
                    'manager_name' => $mgrName,
                    'rating' => round($rating, 1),
                    'status' => $goal->status,
                    'job_title' => $goal->job_title ?: ($goal->user->position_id ?? 'Staff')
                ];
            }

            // Pending review bottleneck
            if ($goal->status === 'submitted') {
                $pendingBottlenecks[] = [
                    'id' => $goal->id,
                    'name' => $candidateName,
                    'employee_code' => $empCode,
                    'department' => $dept,
                    'manager_name' => $mgrName,
                    'submitted_at' => $goal->updated_at ? $goal->updated_at->format('Y-m-d') : null
                ];
            }

            // Department Grouping
            if (!isset($deptMap[$dept])) {
                $deptMap[$dept] = [
                    'department' => $dept,
                    'total_goals' => 0,
                    'completed_goals' => 0,
                    'total_rating' => 0,
                    'rated_count' => 0,
                    'employees' => []
                ];
            }
            $deptMap[$dept]['total_goals']++;
            if (in_array($goal->status, ['completed', 'approved', 'review_completed'])) {
                $deptMap[$dept]['completed_goals']++;
            }
            if ($rating > 0) {
                $deptMap[$dept]['total_rating'] += $rating;
                $deptMap[$dept]['rated_count']++;
            }
            $deptMap[$dept]['employees'][$candidateName] = true;

            // Manager Grouping
            if (!isset($managerMap[$mgrName])) {
                $managerMap[$mgrName] = [
                    'manager_name' => $mgrName,
                    'total_goals' => 0,
                    'reviewed_goals' => 0,
                    'pending_reviews' => 0,
                    'total_rating' => 0,
                    'rated_count' => 0,
                    'employees' => []
                ];
            }
            $managerMap[$mgrName]['total_goals']++;
            if (in_array($goal->status, ['completed', 'approved', 'review_completed'])) {
                $managerMap[$mgrName]['reviewed_goals']++;
            }
            if ($goal->status === 'submitted') {
                $managerMap[$mgrName]['pending_reviews']++;
            }
            if ($rating > 0) {
                $managerMap[$mgrName]['total_rating'] += $rating;
                $managerMap[$mgrName]['rated_count']++;
            }
            $managerMap[$mgrName]['employees'][$candidateName] = true;
        }

        // Finalize Department Stats
        $departmentStats = [];
        $laggingDepartments = [];
        foreach ($deptMap as $d) {
            $avgRat = $d['rated_count'] > 0 ? round($d['total_rating'] / $d['rated_count'], 1) : 0;
            $compRate = $d['total_goals'] > 0 ? round(($d['completed_goals'] / $d['total_goals']) * 100, 1) : 0;
            $empCnt = count($d['employees']);
            $item = [
                'department' => $d['department'],
                'total_goals' => $d['total_goals'],
                'completed_goals' => $d['completed_goals'],
                'completion_rate' => $compRate,
                'avg_rating' => $avgRat,
                'employee_count' => $empCnt
            ];
            $departmentStats[] = $item;
            if ($compRate < 50 || ($avgRat > 0 && $avgRat < 3.0)) {
                $laggingDepartments[] = $item;
            }
        }
        usort($departmentStats, fn($a, $b) => $b['avg_rating'] <=> $a['avg_rating'] ?: $b['total_goals'] <=> $a['total_goals']);

        // Finalize Line Manager Stats
        $lineManagerStats = [];
        foreach ($managerMap as $m) {
            $avgRat = $m['rated_count'] > 0 ? round($m['total_rating'] / $m['rated_count'], 1) : 0;
            $revRate = $m['total_goals'] > 0 ? round(($m['reviewed_goals'] / $m['total_goals']) * 100, 1) : 0;
            $lineManagerStats[] = [
                'manager_name' => $m['manager_name'],
                'total_goals' => $m['total_goals'],
                'reviewed_goals' => $m['reviewed_goals'],
                'pending_reviews' => $m['pending_reviews'],
                'review_rate' => $revRate,
                'avg_rating' => $avgRat,
                'team_size' => count($m['employees'])
            ];
        }
        usort($lineManagerStats, fn($a, $b) => $b['total_goals'] <=> $a['total_goals']);

        // Finalize Competency Gaps
        $competencyGaps = [];
        foreach ($competencyScores as $title => $data) {
            $avg = $data['count'] > 0 ? round($data['total'] / $data['count'], 1) : 0;
            $competencyGaps[] = [
                'competency' => $title,
                'avg_score' => $avg,
                'evaluations_count' => $data['count'],
                'status' => $avg >= 4.0 ? 'strong' : ($avg >= 3.0 ? 'satisfactory' : 'needs_attention')
            ];
        }
        // Lowest score first so areas needing improvement appear at the top
        usort($competencyGaps, fn($a, $b) => $a['avg_score'] <=> $b['avg_score']);

        $orgAvgRating = $totalOrgRatedCount > 0 ? round($totalOrgRating / $totalOrgRatedCount, 1) : 0.0;

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
                    'completion_rate' => $completionRate,
                    'org_avg_rating' => $orgAvgRating,
                    'needs_improvement_count' => count($underperformingStaff)
                ],
                'weekly_progress' => [
                    'labels' => $days,
                    'data' => $counts
                ],
                'department_stats' => $departmentStats,
                'rating_distribution' => array_values($ratingTiers),
                'line_manager_stats' => $lineManagerStats,
                'needs_improvement' => [
                    'underperforming_staff' => $underperformingStaff,
                    'competency_gaps' => $competencyGaps,
                    'lagging_departments' => $laggingDepartments,
                    'pending_bottlenecks' => $pendingBottlenecks
                ],
                'status_distribution' => $statusCounts,
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

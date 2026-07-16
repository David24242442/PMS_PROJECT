<?php

namespace App\Http\Controllers;

use App\Models\Goal;
use App\Models\Appraisal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PMSDashboardController extends Controller
{
    /**
     * Get statistics for the PMS Dashboard.
     */
    public function index()
    {
        $totalEmployees = \App\Models\User::count();
        $totalGoals = Goal::count();
        $completedGoals = Goal::where('status', 'completed')->count();
        $pendingAppraisals = Appraisal::where('status', 'pending')->count();
        $approvedAppraisals = Appraisal::where('status', 'approved')->count();

        // Weekly progress — count goals created per day for the last 7 days
        $weekLabels = [];
        $weekData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $weekLabels[] = $date->format('D');
            $weekData[] = Goal::whereDate('created_at', $date->toDateString())->count();
        }
        $weeklyProgress = [
            'labels' => $weekLabels,
            'data' => $weekData
        ];

        // Recent goals
        $recentGoals = Goal::with('user:id,name')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Top employees — aggregate goals per user
        $topEmployees = $this->buildLeaderboard(5);

        return response()->json([
            'status' => 'success',
            'data' => [
                'stats' => [
                    'total_employees' => $totalEmployees,
                    'total_goals' => $totalGoals,
                    'completed_goals' => $completedGoals,
                    'pending_appraisals' => $pendingAppraisals,
                    'approved_appraisals' => $approvedAppraisals,
                    'completion_rate' => $totalGoals > 0 ? round(($completedGoals / $totalGoals) * 100, 2) : 0
                ],
                'weekly_progress' => $weeklyProgress,
                'recent_goals' => $recentGoals,
                'top_employees' => $topEmployees
            ]
        ]);
    }

    /**
     * Get the full employee leaderboard.
     */
    public function leaderboard()
    {
        $employees = $this->buildLeaderboard();

        return response()->json([
            'status' => 'success',
            'data' => $employees
        ]);
    }

    /**
     * Build leaderboard data from goals and appraisals.
     */
    private function buildLeaderboard($limit = null)
    {
        // Get all users who have goals
        $users = \App\Models\User::whereHas('goals')->get();

        $leaderboard = [];

        foreach ($users as $user) {
            $goals = Goal::where('user_id', $user->id)->get();
            $totalGoals = $goals->count();
            $completedGoals = $goals->where('status', 'completed')->count();
            $completionPct = $totalGoals > 0 ? round(($completedGoals / $totalGoals) * 100) : 0;

            // Get latest appraisal rating
            $appraisal = Appraisal::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->first();

            $avgRating = 0;
            if ($appraisal && $appraisal->overall_rating) {
                $avgRating = round($appraisal->overall_rating, 1);
            } elseif ($appraisal && $appraisal->competencies_data) {
                $comps = is_string($appraisal->competencies_data) ? json_decode($appraisal->competencies_data, true) : $appraisal->competencies_data;
                if (is_array($comps) && count($comps) > 0) {
                    $sum = 0;
                    $count = 0;
                    foreach ($comps as $c) {
                        $r = floatval($c['managerRating'] ?? $c['rating'] ?? 0);
                        if ($r > 0) { $sum += $r; $count++; }
                    }
                    $avgRating = $count > 0 ? round($sum / $count, 1) : 0;
                }
            }

            // Build goals list for modal
            $goalsList = $goals->map(function ($g) {
                return [
                    'id' => $g->id,
                    'title' => $g->title,
                    'status' => $g->status,
                    'category' => $g->category,
                    'target' => $g->target,
                    'actual' => $g->actual,
                    'rating' => $g->rating,
                    'due_date' => $g->due_date ? $g->due_date->format('Y-m-d') : null,
                ];
            });

            $leaderboard[] = [
                'user_id' => $user->id,
                'name' => $user->name,
                'department' => $user->department ?? null,
                'job_title' => $user->job_title ?? null,
                'total_goals' => $totalGoals,
                'completed_goals' => $completedGoals,
                'completion_pct' => $completionPct,
                'avg_rating' => $avgRating,
                'goals' => $goalsList,
            ];
        }

        // Sort by rating descending
        usort($leaderboard, function ($a, $b) {
            return $b['avg_rating'] <=> $a['avg_rating'];
        });

        if ($limit) {
            $leaderboard = array_slice($leaderboard, 0, $limit);
        }

        return $leaderboard;
    }
}

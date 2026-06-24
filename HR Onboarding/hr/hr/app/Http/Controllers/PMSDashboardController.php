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
    public function index()
    {
        $totalEmployees = User::count();
        $totalGoals = Goal::count();
        $completedGoals = Goal::where('status', 'completed')->count();
        $pendingAppraisals = Goal::where('status', 'submitted')->count();
        $approvedAppraisals = Goal::where('status', 'approved')->count();

        // Completion Rate: % of users who have at least one goal
        $usersWithGoals = Goal::distinct('user_id')->count('user_id');
        $completionRate = $totalEmployees > 0 ? round(($usersWithGoals / $totalEmployees) * 100, 1) : 0;

        // Recent Goals
        $recentGoals = Goal::with('user')
            ->orderBy('updated_at', 'desc')
            ->take(5)
            ->get();

        // Weekly Progress (Goals created in the last 7 days)
        $days = [];
        $counts = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $days[] = $date->format('D');
            $counts[] = Goal::whereDate('created_at', $date->toDateString())->count();
        }

        // Top Employees (Based on completed goals or submitted appraisals)
        // For now, we'll just pull users who have the most goals or recently completed them.
        // In a real scenario, this would be based on Appraisal Scores.
        $topEmployees = User::select('users.id', 'users.name', 'users.department', DB::raw('COUNT(goals.id) as goals_count'))
            ->join('goals', 'users.id', '=', 'goals.user_id')
            ->groupBy('users.id', 'users.name', 'users.department')
            ->orderBy('goals_count', 'desc')
            ->take(5)
            ->get()
            ->map(function($user) {
                $names = explode(' ', $user->name);
                $initials = '';
                foreach ($names as $name) {
                    $initials .= substr($name, 0, 1);
                }
                
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'role' => $user->department ?? 'Employee',
                    'score' => $user->goals_count * 10, // Mock score logic for now
                    'avatar' => substr($initials, 0, 2),
                    'color' => 'bg-indigo-100 text-indigo-600'
                ];
            });

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
}

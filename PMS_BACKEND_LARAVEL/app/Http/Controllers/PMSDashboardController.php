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
 
        // Weekly progress logic (mock data for now or aggregate from goals)
        $weeklyProgress = [
            'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            'data' => [65, 59, 80, 81, 56, 55, 40]
        ];
 
        // Recent goals
        $recentGoals = Goal::with('user:id,name')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
 
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
                'recent_goals' => $recentGoals
            ]
        ]);
    }
}

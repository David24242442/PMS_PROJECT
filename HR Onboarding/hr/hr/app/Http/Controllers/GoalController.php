<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GoalController extends Controller
{
    /**
     * Compute display_status for a goal.
     */
    private function computeDisplayStatus($goal, $completedReviewEmpCodes = null)
    {
        if ($completedReviewEmpCodes === null) {
            $year = $goal->year ?? date('Y');
            $completedReviewEmpCodes = \App\Models\Review::where('year', $year)
                ->where('status', 'completed')
                ->pluck('emp_code')
                ->toArray();
        }

        if ($goal->status === 'draft') {
            return 'draft';
        } elseif ($goal->status === 'review_completed') {
            return 'review_completed';
        } elseif ($goal->status === 'completed' && $goal->employee_code && in_array($goal->employee_code, $completedReviewEmpCodes)) {
            return 'review_completed';
        } elseif ($goal->status === 'completed') {
            return 'appraisal_completed';
        } else {
            return 'goal_created';
        }
    }

    /**
     * Fetch goals for the authenticated user unless specified otherwise.
     */
    public function index(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $user = $request->user();
        $userId = $user ? $user->id : $request->get('user_id');

        if (!$userId) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        // Fetch team user IDs if applicable
        $teamUserIds = \App\Models\User::where('line_manager_id', $userId)->pluck('id')->toArray();

        $goals = \App\Models\Goal::where(function ($query) use ($userId, $user, $teamUserIds) {
                $query->where('user_id', $userId)
                      ->orWhereIn('user_id', $teamUserIds);
                if ($user) {
                    $query->orWhere('manager_name', $user->name);
                }
            })
            ->where('year', $year)
            ->orderBy('created_at', 'desc')
            ->get();

        // Fetch completed reviews for this year to determine review status
        $completedReviewEmpCodes = \App\Models\Review::where('year', $year)
            ->where('status', 'completed')
            ->pluck('emp_code')
            ->toArray();

        // Add computed display_status to each goal
        $goals->each(function ($goal) use ($completedReviewEmpCodes) {
            $goal->display_status = $this->computeDisplayStatus($goal, $completedReviewEmpCodes);
        });

        return response()->json([
            'status' => 'success',
            'data' => $goals
        ]);
    }

    /**
     * Store a new goal.
     */
    public function store(Request $request)
    {
        // Debug: log what we receive
        \Log::info('GoalController@store - incoming data keys: ' . implode(', ', array_keys($request->all())));
        
        $goal = \App\Models\Goal::create($request->all());
        $goal->display_status = $this->computeDisplayStatus($goal);

        return response()->json([
            'status' => 'success',
            'message' => 'Goal created successfully',
            'data' => $goal
        ]);
    }

    /**
     * Update an existing goal.
     */
    public function update(Request $request, $id)
    {
        try {
            $goal = \App\Models\Goal::findOrFail($id);
            
            // Exclude dynamically computed fields from the update payload
            $goal->update($request->except(['display_status']));
            $goal->display_status = $this->computeDisplayStatus($goal);

            return response()->json([
                'status' => 'success',
                'message' => 'Goal updated successfully',
                'data' => $goal
            ]);
        } catch (\Exception $e) {
            \Log::error('GoalController@update failed: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update goal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a goal.
     */
    public function destroy($id)
    {
        $goal = \App\Models\Goal::findOrFail($id);
        $goal->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Goal deleted successfully'
        ]);
    }

    /**
     * Handle file uploads for goal attachments.
     */
    public function uploadAttachment(Request $request)
    {
        $request->validate([
            'files.*' => 'required|file|mimes:jpeg,png,jpg,gif,svg,pdf,doc,docx,xls,xlsx,csv,txt|max:10240', // 10MB max
        ]);

        if ($request->hasFile('files')) {
            $files = $request->file('files');
            $uploadedFiles = [];

            foreach ($files as $file) {
                // Generate a unique filename
                $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
                
                // Store the file in the 'public/attachments' directory
                $path = $file->storeAs('public/attachments', $filename);
                
                // Keep track of uploaded files to return to frontend
                $uploadedFiles[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => Storage::url($path), // Generate public URL
                    'type' => $file->getClientMimeType(),
                ];
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Files uploaded successfully',
                'files' => $uploadedFiles
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'No files uploaded'
        ], 400);
    }

    /**
     * Fetch all goals with appraisal data for HR review.
     */
    public function allAppraisals(Request $request)
    {
        try {
            $year = $request->get('year', date('Y'));
            
            $goals = \App\Models\Goal::with('user')
                ->where('year', $year)
                ->whereNotNull('appraisal_data')
                ->where('status', '!=', 'draft')
                ->orderBy('submitted_at', 'desc')
                ->orderBy('updated_at', 'desc')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $goals
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in allAppraisals: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Database error. Please ensure migrations are run.'
            ], 500);
        }
    }

    /**
     * Review and evaluate an appraisal (HR Action).
     */
    public function reviewAppraisal(Request $request, $id)
    {
        $goal = \App\Models\Goal::findOrFail($id);
        
        $goal->update([
            'status' => $request->input('status'),
            'hr_comments' => $request->input('hr_comments'),
            'overall_rating' => $request->input('overall_rating'),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Appraisal reviewed successfully',
            'data' => $goal
        ]);
    }

    /**
     * Fetch goals with appraisal data for the Appraisal view.
     */
    public function appraisals(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $userId = $request->get('user_id', $request->user()->id);
        $goalId = $request->get('goal_id');

        \Log::info("GoalController@appraisals - year: $year, userId: $userId, goalId: $goalId");

        // If a specific goal_id is provided, prioritize it
        if ($goalId) {
            $goal = \App\Models\Goal::find($goalId);
            if ($goal) {
                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'goal' => $goal,
                        'appraisal_data' => $goal->appraisal_data,
                        'all_goals' => [] // Optional: could still fetch others if needed
                    ]
                ]);
            }
        }

        // Default behavior: Fetch all appraisals with data
        $goals = \App\Models\Goal::where('user_id', $userId)
            ->where('year', $year)
            ->whereNotNull('appraisal_data')
            ->where('status', '!=', 'draft')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($goals->isEmpty()) {
            return response()->json([
                'status' => 'empty',
                'message' => 'No appraisal data found for this year'
            ]);
        }

        $primaryGoal = $goals->first();

        return response()->json([
            'status' => 'success',
            'data' => [
                'goal' => $primaryGoal,
                'appraisal_data' => $primaryGoal->appraisal_data,
                'all_goals' => $goals
            ]
        ]);
    }
}

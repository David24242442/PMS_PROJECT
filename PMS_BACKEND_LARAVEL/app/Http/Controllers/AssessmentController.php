<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AssessmentController extends Controller
{
    /**
     * Get assessment history for a user.
     */
    public function index(Request $request)
    {
        $userId = $request->query('user_id');

        if (!$userId) {
            return response()->json([
                'status' => 'error',
                'message' => 'User ID is required'
            ], 400);
        }

        $assessments = Assessment::with('assessor:id,name')
            ->where('user_id', $userId)
            ->orderBy('assessment_date', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $assessments
        ]);
    }

    /**
     * Save a new assessment (Admin/Manager flow).
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'user_id' => 'required|exists:users,id',
            'checklist_items' => 'nullable|array',
            'attendance_rating' => 'nullable|integer|min:1|max:5',
            'task_completion_rating' => 'nullable|integer|min:1|max:5',
            'quality_rating' => 'nullable|integer|min:1|max:5',
            'teamwork_rating' => 'nullable|integer|min:1|max:5',
            'initiative_rating' => 'nullable|integer|min:1|max:5',
            'strengths' => 'nullable|string',
            'improvements' => 'nullable|string',
            'action_items' => 'nullable|string',
            'overall_notes' => 'nullable|string'
        ]);

        $assessment = Assessment::create([
            'user_id' => $validatedData['user_id'],
            'assessed_by' => Auth::id(),
            'checklist_items' => $validatedData['checklist_items'],
            'attendance_rating' => $validatedData['attendance_rating'],
            'task_completion_rating' => $validatedData['task_completion_rating'],
            'quality_rating' => $validatedData['quality_rating'],
            'teamwork_rating' => $validatedData['teamwork_rating'],
            'initiative_rating' => $validatedData['initiative_rating'],
            'strengths' => $validatedData['strengths'],
            'improvements' => $validatedData['improvements'],
            'action_items' => $validatedData['action_items'],
            'overall_notes' => $validatedData['overall_notes'],
            'assessment_date' => now()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Assessment saved successfully',
            'data' => $assessment
        ], 201);
    }
}

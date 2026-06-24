<?php

namespace App\Http\Controllers;

use App\Models\Goal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GoalController extends Controller
{
    /**
     * Display a listing of goals for the authenticated user or a specific employee.
     */
    public function index(Request $request)
    {
        $userId = $request->query('user_id');
        $year = $request->query('year', date('Y'));

        $query = Goal::where('year', $year);

        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            // Default to the current authenticated user's goals
            $query->where('user_id', Auth::id()); 
        }

        $goals = $query->orderBy('due_date', 'asc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $goals
        ]);
    }

    /**
     * Store a newly created goal.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'purposes' => 'nullable|string',
            'challenges' => 'nullable|string',
            'category' => 'nullable|string|max:255',
            'weight' => 'nullable|numeric|min:0|max:100',
            'target' => 'nullable|integer|min:0',
            'due_date' => 'nullable|date',
            'completion_date' => 'nullable|date',
            'smart_criteria' => 'nullable|array',
            'quarterly_tracking' => 'nullable|array',
            'year' => 'required|integer'
        ]);

        $goal = Goal::create([
            'user_id' => $validatedData['user_id'],
            'title' => $validatedData['title'],
            'description' => $validatedData['description'] ?? null,
            'purposes' => $validatedData['purposes'] ?? null,
            'challenges' => $validatedData['challenges'] ?? null,
            'category' => $validatedData['category'] ?? 'General',
            'weight' => $validatedData['weight'] ?? 0,
            'target' => $validatedData['target'] ?? 100,
            'actual' => 0,
            'status' => 'not_started',
            'due_date' => $validatedData['due_date'] ?? null,
            'completion_date' => $validatedData['completion_date'] ?? null,
            'smart_criteria' => $validatedData['smart_criteria'] ?? null,
            'quarterly_tracking' => $validatedData['quarterly_tracking'] ?? null,
            'year' => $validatedData['year'],
            'created_by' => Auth::id()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Goal created successfully',
            'data' => $goal
        ], 201);
    }

    /**
     * Update the specified goal.
     */
    public function update(Request $request, Goal $goal)
    {
        $validatedData = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'actual' => 'nullable|integer|min:0',
            'rating' => 'nullable|integer|min:1|max:5',
            'status' => 'nullable|string|in:not_started,in_progress,completed,on_hold',
            'due_date' => 'nullable|date'
        ]);

        $goal->update($validatedData);

        return response()->json([
            'status' => 'success',
            'message' => 'Goal updated successfully',
            'data' => $goal
        ]);
    }

    /**
     * Remove the specified goal.
     */
    public function destroy(Goal $goal)
    {
        $goal->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Goal deleted successfully'
        ]);
    }

    /**
     * Upload an attachment for a goal (e.g., milestone evidence).
     */
    public function uploadAttachment(Request $request)
    {
        $request->validate([
            'files.*' => 'required|file|mimes:pdf,doc,docx,xls,xlsx,csv,jpg,jpeg,png|max:10240', // 10MB max
        ]);

        $uploadedFiles = [];

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $fileName = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('goal_attachments', $fileName, 'public');

                $uploadedFiles[] = [
                    'file_url' => asset('storage/' . $path),
                    'file_name' => $file->getClientOriginalName()
                ];
            }

            return response()->json([
                'status' => 'success',
                'files' => $uploadedFiles
            ]);
        }

        return response()->json(['status' => 'error', 'message' => 'No files uploaded'], 400);
    }
}

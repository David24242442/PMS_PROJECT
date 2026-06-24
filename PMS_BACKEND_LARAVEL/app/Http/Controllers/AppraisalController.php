<?php

namespace App\Http\Controllers;

use App\Models\Appraisal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AppraisalController extends Controller
{
    /**
     * Display a listing of all appraisals for admin.
     */
    public function index(Request $request)
    {
        $year = $request->query('year', date('Y'));
        $appraisals = Appraisal::with('user')->where('year', $year)->get();
 
        return response()->json([
            'status' => 'success',
            'data' => $appraisals
        ]);
    }
 
    /**
     * Get appraisal for an employee for a specific year.
     */
    public function show(Request $request)
    {
        $userId = $request->query('user_id', Auth::id());
        $year = $request->query('year', date('Y'));
 
        $appraisal = Appraisal::where('user_id', $userId)
            ->where('year', $year)
            ->first();

        if (!$appraisal) {
            return response()->json([
                'status' => 'success',
                'data' => null,
                'message' => 'No appraisal found for this year'
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => $appraisal
        ]);
    }

    /**
     * Save appraisal draft or submit.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'user_id' => 'required|exists:users,id',
            'year' => 'required|integer',
            'status' => 'required|string|in:draft,pending',
            'goals_data' => 'nullable|array',
            'competencies_data' => 'nullable|array',
            'self_assessment' => 'nullable|string',
            'achievements' => 'nullable|string',
            'improvements' => 'nullable|string',
            'development_plan' => 'nullable|string'
        ]);

        $appraisal = Appraisal::updateOrCreate(
            [
                'user_id' => $validatedData['user_id'],
                'year' => $validatedData['year']
            ],
            [
                'status' => $validatedData['status'],
                'goals_data' => $validatedData['goals_data'] ?? null,
                'competencies_data' => $validatedData['competencies_data'] ?? null,
                'self_assessment' => $validatedData['self_assessment'] ?? null,
                'achievements' => $validatedData['achievements'] ?? null,
                'improvements' => $validatedData['improvements'] ?? null,
                'development_plan' => $validatedData['development_plan'] ?? null,
                'overall_rating' => $request->input('overall_rating'), // Allow saving overall rating
                'candidate_name' => $request->input('candidate_name'), // Save header info
                'manager_signature_name' => $request->input('manager_signature_name'),
                'candidate_signature_name' => $request->input('candidate_signature_name'),
                'signature_date' => $request->input('signature_date'),
                'submitted_at' => $validatedData['status'] === 'pending' ? now() : null
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => $validatedData['status'] === 'pending' ? 'Appraisal submitted successfully' : 'Draft saved successfully',
            'data' => $appraisal
        ]);
    }

    /**
     * HR or Manager reviews appraisal.
     */
    public function review(Request $request, Appraisal $appraisal)
    {
        $validatedData = $request->validate([
            'status' => 'required|string|in:reviewed,approved,rejected',
            'manager_comments' => 'nullable|string',
            'hr_comments' => 'nullable|string',
            'overall_rating' => 'nullable|numeric|min:1|max:5'
        ]);

        $updateData = [
            'status' => $validatedData['status'],
            'manager_comments' => $validatedData['manager_comments'] ?? $appraisal->manager_comments,
            'hr_comments' => $validatedData['hr_comments'] ?? $appraisal->hr_comments,
            'overall_rating' => $validatedData['overall_rating'] ?? $appraisal->overall_rating
        ];

        if ($validatedData['status'] === 'reviewed') {
            $updateData['reviewed_at'] = now();
            $updateData['reviewed_by'] = Auth::id();
        } elseif ($validatedData['status'] === 'approved') {
            $updateData['approved_at'] = now();
            $updateData['approved_by'] = Auth::id();
        }

        $appraisal->update($updateData);

        return response()->json([
            'status' => 'success',
            'message' => 'Appraisal status updated to ' . $validatedData['status'],
            'data' => $appraisal
        ]);
    }
}

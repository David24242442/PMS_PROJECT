<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    public function show(Request $request)
    {
        $userId = $request->query('user_id', Auth::id());
        $year = $request->query('year', date('Y'));

        $review = Review::where('user_id', $userId)
            ->where('year', $year)
            ->first();

        if (!$review) {
            return response()->json([
                'status' => 'not_found',
                'message' => 'No review found'
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => $review
        ]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'year' => 'required|integer',
            'status' => 'required|string',
            'emp_code' => 'nullable|string',
            'name' => 'nullable|string',
            'division' => 'nullable|string',
            'position' => 'nullable|string',
            'date' => 'nullable|date',
            'goal_achievement' => 'nullable|array',
            'initiatives' => 'nullable|array',
            'next_steps' => 'nullable|array',
            'improvements' => 'nullable|array',
            'manager_feedback' => 'nullable|array',
        ]);

        $review = Review::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'year' => $validatedData['year']
            ],
            $validatedData
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Review saved successfully',
            'data' => $review
        ]);
    }
}

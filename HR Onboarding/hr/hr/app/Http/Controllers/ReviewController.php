<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $userId = $request->get('user_id', $request->user()->id);

        $review = Review::where('user_id', $userId)
            ->where('year', $year)
            ->first();

        if (!$review) {
            return response()->json([
                'status' => 'empty',
                'message' => 'No review found'
            ], 200);
        }

        return response()->json([
            'status' => 'success',
            'data' => $review
        ]);
    }

    public function store(Request $request)
    {
        $userId = $request->user() ? $request->user()->id : ($request->input('user_id') ?: Auth::id());
        $year = $request->input('year', date('Y'));

        $review = Review::updateOrCreate(
            [
                'user_id' => $userId,
                'year' => $year,
            ],
            $request->except(['user_id', 'year'])
        );

        try {
            \App\Helpers\ActivityLogger::log('SUBMIT', 'PMS_REVIEW', "Performance review saved for FY {$year} (User #{$userId}).", [
                'review_id' => $review->id,
                'user_id' => $userId,
                'year' => $year,
                'status' => $review->status ?? 'submitted'
            ]);
        } catch (\Throwable $e) {}

        return response()->json([
            'status' => 'success',
            'message' => 'Review saved successfully',
            'data' => $review
        ]);
    }
}

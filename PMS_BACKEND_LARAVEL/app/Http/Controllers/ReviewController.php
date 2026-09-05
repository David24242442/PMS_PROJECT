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
        $review = Review::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'year' => $request->input('year', date('Y')),
            ],
            $request->except(['user_id', 'year'])
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Review saved successfully',
            'data' => $review
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Draft;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DraftController extends Controller
{
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'type' => 'required|string',
            'data' => 'required|array'
        ]);

        $draft = Draft::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'type' => $validatedData['type']
            ],
            [
                'data' => $validatedData['data']
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Draft saved successfully',
            'data' => $draft
        ]);
    }

    public function show(Request $request)
    {
        $type = $request->query('type');
        
        $draft = Draft::where('user_id', Auth::id())
            ->where('type', $type)
            ->first();

        if (!$draft) {
            return response()->json([
                'status' => 'not_found',
                'message' => 'No draft found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $draft
        ]);
    }

    public function destroy(Request $request)
    {
        $type = $request->query('type');
        
        Draft::where('user_id', Auth::id())
            ->where('type', $type)
            ->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Draft deleted'
        ]);
    }
}

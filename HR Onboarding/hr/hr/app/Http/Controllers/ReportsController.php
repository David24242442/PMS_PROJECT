<?php

namespace App\Http\Controllers;

use App\Models\PMSReport;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    public function index(Request $request)
    {
        $reports = PMSReport::with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $reports
        ]);
    }

    public function store(Request $request)
    {
        $report = PMSReport::create([
            'title' => $request->input('title'),
            'type' => $request->input('type'),
            'department_id' => $request->input('department_id'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'notes' => $request->input('notes'),
            'user_id' => $request->user()->id
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Report generated successfully',
            'data' => $report
        ]);
    }

    public function destroy($id)
    {
        $report = PMSReport::findOrFail($id);
        $report->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Report deleted successfully'
        ]);
    }
}

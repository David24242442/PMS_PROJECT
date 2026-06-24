<?php

namespace App\Http\Controllers;

use App\Models\HrReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HrReportController extends Controller
{
    /**
     * Get list of generated reports.
     */
    public function index()
    {
        $reports = HrReport::orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $reports
        ]);
    }

    /**
     * Generate a new report.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|string', // appraisal_summary, goal_progress, department_ranking
            'department_id' => 'nullable|exists:departments,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'parameters' => 'nullable|array'
        ]);

        // In a real app, this would trigger a background job or logic to fetch data
        // For now, we'll create the record with dummy data or just the request params
        $report = HrReport::create([
            'title' => $validatedData['title'],
            'type' => $validatedData['type'],
            'department_id' => $validatedData['department_id'],
            'date_from' => $validatedData['date_from'],
            'date_to' => $validatedData['date_to'],
            'parameters' => $validatedData['parameters'],
            'generated_by' => Auth::id(),
            'status' => 'generated'
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Report generated successfully',
            'data' => $report
        ], 201);
    }

    /**
     * Download or view report data.
     */
    public function show(HrReport $report)
    {
        return response()->json([
            'status' => 'success',
            'data' => $report
        ]);
    }

    /**
     * Delete a report.
     */
    public function destroy(HrReport $report)
    {
        $report->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Report deleted successfully'
        ]);
    }
}

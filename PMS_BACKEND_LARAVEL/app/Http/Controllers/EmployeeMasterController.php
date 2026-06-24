<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeMasterController extends Controller
{
    /**
     * Display a listing of all users for Employee Master.
     */
    public function index()
    {
        $users = User::select('id', 'name', 'username', 'email', 'position_id', 'department', 'line_manager_id', 'manager_name', 'employee_code', 'location')
            // ->with('position') // Removed to prevent 500 error as Position model doesn't exist yet
            ->get();

        // Attach Line Manager name manually if not using self-relation
        $users->transform(function ($user) {
            $manager = User::find($user->line_manager_id);
            $user->line_manager_name = $manager ? $manager->name : null;
            return $user;
        });

        return response()->json([
            'status' => 'success',
            'data' => $users
        ]);
    }

    /**
     * Update user details including Line Manager.
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        $validatedData = $request->validate([
            'line_manager_id' => 'nullable|exists:users,id',
            'employee_code' => 'nullable|string',
            'location' => 'nullable|string',
            'department' => 'nullable|string'
        ]);

        // If line_manager_id is updated, also update manager_name for backward compatibility
        if (isset($validatedData['line_manager_id'])) {
            $manager = User::find($validatedData['line_manager_id']);
            $user->manager_name = $manager ? $manager->name : null;
        }

        $user->update($validatedData);

        return response()->json([
            'status' => 'success',
            'message' => 'Employee updated successfully',
            'data' => $user
        ]);
    }

    /**
     * Get users reporting to the current logged-in user (My Team).
     */
    public function myTeam(Request $request)
    {
        $managerId = Auth::id();

        // Fetch users where line_manager_id matches current user
        // OR where manager_name matches current user's name (backup)
        $team = User::where('line_manager_id', $managerId)
            ->orWhere('manager_name', Auth::user()->name)
            ->select('id', 'name', 'username', 'email', 'employee_code', 'location', 'department', 'position_id')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $team
        ]);
    }
}

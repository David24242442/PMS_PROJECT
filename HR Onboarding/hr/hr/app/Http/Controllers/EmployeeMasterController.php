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
        $users = User::query()
            ->leftJoin('employees', 'users.employee_code', '=', 'employees.employeeid')
            ->select(
                'users.id',
                'users.name',
                'users.username',
                'users.email',
                'users.position_id',
                'users.line_manager_id',
                // Prefer employees table data, fallback to users table
                \DB::raw("COALESCE(employees.employeeid, users.employee_code) as employee_code"),
                \DB::raw("COALESCE(users.department, '') as department"),
                \DB::raw("COALESCE(users.location, '') as location"),
                'employees.firstname',
                'employees.surname',
                'employees.joiningposition',
                'employees.mobileno',
                'employees.gender',
                'employees.contracttype',
                'employees.joiningdate',
                'employees.status as emp_status',
                'users.admin',
                'users.is_manager',
                'users.report_to'
            )
            ->get()
            ->map(function ($user) {
                // Resolve manager name
                $managerName = null;
                if ($user->line_manager_id) {
                    $manager = User::find($user->line_manager_id);
                    $managerName = $manager ? $manager->name : null;
                }

                // Build display name: prefer employees table name, else users.name
                $displayName = $user->name;
                if ($user->firstname && $user->surname) {
                    $displayName = $user->firstname . ' ' . $user->surname;
                }

                return [
                    'id' => $user->id,
                    'name' => $displayName,
                    'username' => $user->username,
                    'email' => $user->email,
                    'position_id' => $user->position_id,
                    'department' => $user->department,
                    'report_to' => $user->report_to,
                    'employee_code' => $user->employee_code,
                    'location' => $user->location,
                    'position' => $user->joiningposition,
                    'phone' => $user->mobileno,
                    'gender' => $user->gender,
                    'contract_type' => $user->contracttype,
                    'joining_date' => $user->joiningdate,
                    'emp_status' => $user->emp_status,
                    'admin' => $user->admin,
                    'is_manager' => $user->is_manager,
                    'has_subordinates' => \DB::table('employees')->where('line_manager_id', $user->id)->exists()
                ];
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

        $team = User::where('line_manager_id', $managerId)
            ->get()
            ->map(function ($user) {
                 return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'employee_code' => $user->employee_code,
                    'location' => $user->location,
                    'department' => $user->department,
                    'position_id' => $user->position_id
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $team
        ]);
    }
    /**
     * Get employees from the HR master table for dropdowns.
     * Admins see all employees. Managers see only their assigned team.
     */
    public function getEmployees(Request $request)
    {
        $user = $request->user();
        $isAdmin = $user && $user->admin;

        $hasJoiningDept = \Schema::hasColumn('employees', 'joiningdepartment');
        $hasLocation = \Schema::hasColumn('employees', 'location');
        $hasJoiningLocation = \Schema::hasColumn('employees', 'joininglocation');

        $selectColumns = [
            'employees.id as emp_id',
            'employees.employeeid', 
            'employees.firstname', 
            'employees.surname',
            'employees.department as emp_dept',
            'employees.job_title as emp_position',
            'employees.joining_dept_id',
            'employees.joining_branch_id',
            'employees.line_manager_id', // Source of truth for standard staff
            'users.id as user_id',
            'users.department',
            'users.location',
        ];

        if ($hasJoiningDept) $selectColumns[] = 'employees.joiningdepartment';
        if ($hasLocation) $selectColumns[] = 'employees.location as emp_location';
        else if ($hasJoiningLocation) $selectColumns[] = 'employees.joininglocation as emp_location';

        $query = \App\Models\Employee::leftJoin('users', 'users.employee_code', '=', 'employees.employeeid')
            ->select($selectColumns);

        // Non-admin users only see their assigned team members
        if (!$isAdmin && $user) {
            $query->where('employees.line_manager_id', $user->id);
        }

        $employees = $query->orderBy('employees.firstname')->get();

        $employees = $employees->map(function ($emp) use ($hasJoiningDept) {
            // Resolve manager name if they have a line_manager_id in employees table
            $managerName = null;
            if ($emp->line_manager_id) {
                $mgr = User::find($emp->line_manager_id);
                $managerName = $mgr ? $mgr->name : 'Unknown';
            }

                // Fallback logic for Department
                $dept = $emp->department; // From users table
                if (empty($dept) || $dept === 'N/A') {
                    $dept = $emp->emp_dept ?: ($emp->joiningdepartment ?? 'N/A');
                }

                // Fallback logic for Location
                $loc = $emp->location; // From users table
                if (empty($loc) || $loc === 'N/A') {
                    $loc = $emp->emp_location ?? 'N/A';
                }

                return [
                    'id' => $emp->emp_id,
                    'user_id' => $emp->user_id,
                    'employee_code' => $emp->employeeid,
                    'name' => $emp->firstname . ' ' . $emp->surname,
                    'location' => $loc,
                    'department' => $dept,
                    'position' => $emp->emp_position ?? 'N/A',
                    'joining_dept_id' => $emp->joining_dept_id,
                    'joining_branch_id' => $emp->joining_branch_id,
                    'line_manager_id' => $emp->line_manager_id,
                    'line_manager_name' => $managerName,
                    'full_string' => $emp->employeeid . ' - ' . $emp->firstname . ' ' . $emp->surname
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $employees
        ]);
    }

    /**
     * Sync team members for a manager.
     * Automatically creates user accounts for employees if they don't exist.
     */
    public function syncTeam(Request $request)
    {
        $managerId = $request->input('manager_id');
        $employees = $request->input('employees', []);

        if (empty($managerId)) {
            return response()->json(['status' => 'error', 'message' => 'Manager ID is required'], 400);
        }

        // Extract all codes from the payload
        $selectedCodes = collect($employees)->pluck('employee_code')->filter()->toArray();

        // 1. CLEAR existing assignments for this manager (only those not in the new list)
        // This ensures the list stays clean
        \DB::table('employees')
            ->where('line_manager_id', $managerId)
            ->whereNotIn('employeeid', $selectedCodes)
            ->whereNotIn('emp_code', $selectedCodes)
            ->update(['line_manager_id' => null]);

        // 2. ASSIGN new/remaining members
        $syncedCount = 0;
        foreach ($selectedCodes as $code) {
            $updated = \DB::table('employees')
                ->where('employeeid', $code)
                ->orWhere('emp_code', $code)
                ->update(['line_manager_id' => $managerId]);
            
            if ($updated) $syncedCount++;
        }

        return response()->json([
            'status' => 'success',
            'message' => "Successfully synchronized team. $syncedCount members assigned to roster.",
        ]);
    }
}

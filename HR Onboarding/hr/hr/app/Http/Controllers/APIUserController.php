<?php

namespace App\Http\Controllers;


use App\Models\User;

// use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;


class APIUserController extends Controller
{
    //
    public function index(Request $request){
        $users = User::all();
        return $users;
    }

    public function show(Request $request){
        $user = User::find($request->id);
        if(!$user) return response()->json(['error' => 'User not found'], 404);

        // Fetch associated employee record by email to get "checked details"
        $employee = \App\Models\Employee::where('email', $user->email)->first();

        if($employee) {
            $user->employee_details = $employee;
            
            // Build the checklist based on existing documents/data
            $user->checklist = [
                'goals_set' => $employee->employee()->exists(), // Example check
                'training_completed' => $employee->probationconfs()->exists(), 
                'feedback_given' => $employee->appletters()->exists(),
                'one_on_one' => $employee->appointmentletters()->exists(),
                'documentation_updated' => $employee->cv()->exists(),
                'kpis_reviewed' => $employee->ssnit()->exists(),
                'development_plan' => $employee->petratrust()->exists(),
                'recognition_given' => $employee->ghcard()->exists(),
                
                // Raw file checks as per EmployeesView logic
                'has_app_letter' => $employee->appletters()->exists(),
                'has_appointment_letter' => $employee->appointmentletters()->exists(),
                'has_cv' => $employee->cv()->exists(),
                'has_ghcard' => $employee->ghcard()->exists(),
                'has_ssnit' => $employee->ssnit()->exists(),
                'has_tin' => $employee->nhis()->exists(),
                'has_birthcert' => $employee->birthcert()->exists(),
                'has_police_clearance' => $employee->pclearanceform()->exists(),
            ];

            // Add other details directly to user object for easy access
            $user->position_name = $user->position_id; // Need to map this if positions table exists? Or Front end masks it.
            $user->role = $user->admin ? 'Administrator' : 'User';
            $user->department = $employee->joining_dept_id; // Front end maps this ID
            $user->joining_date = $employee->joiningdate;
            $user->status = $employee->status; // 1=Active, etc.
        }

        return $user;
    }

    public function fetchEmployees(Request $request){
        $query = \App\Models\Employee::with(['creator'])->orderBy('id', 'DESC');

        // Global search (Employee Info field)
        if($request->employeeinfo){
            $search = $request->employeeinfo;
            $query->where(function($q) use ($search) {
                $q->where('firstname', 'like', "%$search%")
                  ->orWhere('surname', 'like', "%$search%")
                  ->orWhere('employeeid', 'like', "%$search%")
                  ->orWhere('emp_code', 'like', "%$search%")
                  ->orWhere('mobileno', 'like', "%$search%")
                  ->orWhere('joiningposition', 'like', "%$search%");
            });
        }

        // Advanced filters
        if($request->filters && count((array)$request->filters)){
            $filters = $request->filters;
            foreach ($filters as $data) {
                $op = '=';
                switch($data['op']){
                    case 'is': $op = '='; break;
                    case 'isnot': $op = "!="; break;
                    case 'gt': $op = ">"; break;
                    case 'gtq': $op = ">="; break;
                    case 'lt': $op = "<"; break;
                    case 'ltq': $op = "<="; break;
                    case 'cont': $op = "like"; break;
                }
                $val = $data['val'];
                if($data['op'] == 'cont') $val = "%$val%";

                if($data['cond'] == "a"){
                    if($data['attr'] == 'creator'){
                        $query->whereHas('creator', function ($q) use ($val, $op) {
                            $q->where('name', $op, $val);
                        });
                    } else {
                        $query->where($data['attr'], $op, $val);
                    }
                } else {
                    if($data['attr'] == 'creator'){
                        $query->orWhereHas('creator', function ($q) use ($val, $op) {
                            $q->where('name', $op, $val);
                        });
                    } else {
                        $query->orWhere($data['attr'], $op, $val);
                    }
                }
            }
        }

        // Date range filters
        if($request->createdfrom){
            $query->where('created_at', '>=', $request->createdfrom);
        }
        if($request->createdto){
            $query->where('created_at', '<=', $request->createdto.' 23:59:59');
        }

        $employees = $query->paginate($request->per_page ?? 50);
        return $employees;
    }

    public function fetchEmployee(Request $request){
        $employee = \App\Models\Employee::find($request->id);
        
        if(!$employee) return response()->json(['error' => 'Employee not found'], 404);

        // Construct a User-like object for the frontend
        $user = new \stdClass();
        $user->id = $employee->id;
        $user->name = $employee->firstname . ' ' . $employee->surname;
        $user->email = $employee->email;
        $user->position_id = $employee->joiningposition;
        $user->department = $employee->joining_dept_id;
        $user->joining_date = $employee->joiningdate;
        $user->status = $employee->status;
        $user->admin = false; // Default
        $user->employee_details = $employee;

        // Build checklist
        $user->checklist = [
            'goals_set' => $employee->employee()->exists(), 
            'training_completed' => $employee->probationconfs()->exists(), 
            'feedback_given' => $employee->appletters()->exists(),
            'one_on_one' => $employee->appointmentletters()->exists(),
            'documentation_updated' => $employee->cv()->exists(),
            'kpis_reviewed' => $employee->ssnit()->exists(),
            'development_plan' => $employee->petratrust()->exists(),
            'recognition_given' => $employee->ghcard()->exists(),
            
            'has_app_letter' => $employee->appletters()->exists(),
            'has_appointment_letter' => $employee->appointmentletters()->exists(),
            'has_cv' => $employee->cv()->exists(),
            'has_ghcard' => $employee->ghcard()->exists(),
            'has_ssnit' => $employee->ssnit()->exists(),
            'has_tin' => $employee->nhis()->exists(),
            'has_birthcert' => $employee->birthcert()->exists(),
            'has_police_clearance' => $employee->pclearanceform()->exists(),
        ];

        return response()->json($user);
    }

    public function store(Request $request){
        // return $request;

        $user = $request;
       

        $userDetails = [
            'admin' => isset($user['admin']) && $user['admin'] ? 1 : 0,
            'user_id' => Auth::user()->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'password' => bcrypt($user['password']),
            'position_id' => $user->position_id
        ];

        $u = User::create($userDetails);

        return $u;

    }

    public function updateuser(Request $request){
        // return $request;

        $user = $request;
        $userDetails = [
            'admin' => $user['admin'],
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'position_id' => $user->position_id
        ];

        if (isset($user['permissions'])) {
            $userDetails['permissions'] = is_array($user['permissions']) ? $user['permissions'] : json_decode($user['permissions'], true);
        }

        $u = User::find($user['id']);
        $u->update($userDetails);

        if(isset($user['password'])){
            $pass = [
                'password' => bcrypt($user['password'])
            ];
            
            $u->update($pass);
        }

        return $u;

    }

    public function updatepassword(Request $request){
        // return $request;

        // return auth()->user();
        // return bcrypt($request->npassword);

        if (!Hash::check($request->opassword, Auth::user()->password)) {
            
            return response()->json(array(
                'success' => false,
                'error' => 'The old password is not correct.'
            ), 202);
        }

        $authuser = Auth::user();
        $authuser->update([
            'password' => bcrypt($request->npassword)
        ]);

        

        return 'ok';

    }

    public function login(Request $request){
        $validator = \Validator::make($request->all(),[
            'username'=>'required',
            'password'=>'required'
        ]);

        if ($validator->fails())
        {
            return response()->json(array(
                'success' => false,
                'errors' => $validator->getMessageBag()->toArray()
            ), 202);
        }

        $loginInput = trim($request->username);
        $password = $request->password;
        $cleanCode = preg_replace('/[^a-zA-Z0-9]/', '', $loginInput);

        // Ensure critical columns exist in users table dynamically
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('users')) {
                \Illuminate\Support\Facades\Schema::table('users', function ($table) {
                    if (!\Illuminate\Support\Facades\Schema::hasColumn('users', 'employee_code')) {
                        $table->string('employee_code')->nullable();
                    }
                    if (!\Illuminate\Support\Facades\Schema::hasColumn('users', 'permissions')) {
                        $table->text('permissions')->nullable();
                    }
                    if (!\Illuminate\Support\Facades\Schema::hasColumn('users', 'line_manager_id')) {
                        $table->unsignedBigInteger('line_manager_id')->nullable();
                    }
                });
            }
        } catch (\Throwable $e) {}

        $hasEmployeeCode = \Illuminate\Support\Facades\Schema::hasColumn('users', 'employee_code');

        // 1. Direct search in users table: by username, employee_code, or email (case-insensitive)
        $user = User::where(function ($q) use ($loginInput, $cleanCode, $hasEmployeeCode) {
            $q->where('username', $loginInput)
              ->orWhere('email', $loginInput)
              ->orWhereRaw('LOWER(username) = ?', [strtolower($loginInput)])
              ->orWhereRaw('LOWER(email) = ?', [strtolower($loginInput)])
              ->orWhere('username', 'like', '%' . $loginInput)
              ->orWhere('username', 'like', '%' . $cleanCode)
              ->orWhere('email', 'like', strtolower($cleanCode) . '@%');

            if ($hasEmployeeCode) {
                $q->orWhere('employee_code', $loginInput)
                  ->orWhereRaw('LOWER(employee_code) = ?', [strtolower($loginInput)])
                  ->orWhere('employee_code', $cleanCode);
            }
        })->first();

        // 2. If not found, look up via Goals table (did line manager assign a goal with this employee_code?)
        $goal = null;
        if (!$user && \Illuminate\Support\Facades\Schema::hasTable('goals')) {
            $goal = \App\Models\Goal::where('employee_code', $loginInput)
                ->orWhere('employee_code', $cleanCode)
                ->orWhereRaw('LOWER(employee_code) = ?', [strtolower($loginInput)])
                ->orWhere('candidate_name', $loginInput)
                ->orWhereRaw('LOWER(candidate_name) = ?', [strtolower($loginInput)])
                ->latest()
                ->first();

            if ($goal) {
                // If goal has a user_id, resolve that user
                if (!empty($goal->user_id)) {
                    $user = User::find($goal->user_id);
                }
                // If still not found, search user by candidate name or generated email
                if (!$user) {
                    $fallbackEmail = strtolower($cleanCode) . '@melcomgroup.com';
                    $user = User::where('email', $fallbackEmail)
                        ->orWhere('name', $goal->candidate_name)
                        ->orWhere('username', 'like', '%' . $cleanCode)
                        ->first();
                }
            }
        }

        // 3. If not found, look up via Employees table
        $emp = null;
        if (!$user && \Illuminate\Support\Facades\Schema::hasTable('employees')) {
            $emp = \App\Models\Employee::where(function ($q) use ($loginInput, $cleanCode) {
                if (\Illuminate\Support\Facades\Schema::hasColumn('employees', 'employeeid')) {
                    $q->where('employeeid', $loginInput)
                      ->orWhere('employeeid', $cleanCode)
                      ->orWhereRaw('LOWER(employeeid) = ?', [strtolower($loginInput)]);
                }
                if (\Illuminate\Support\Facades\Schema::hasColumn('employees', 'emp_code')) {
                    $q->orWhere('emp_code', $loginInput)
                      ->orWhere('emp_code', $cleanCode)
                      ->orWhereRaw('LOWER(emp_code) = ?', [strtolower($loginInput)]);
                }
            })->first();

            if ($emp) {
                if (!empty($emp->email)) {
                    $user = User::where('email', $emp->email)->first();
                }
                if (!$user) {
                    $empName = trim(($emp->firstname ?? '') . ' ' . ($emp->surname ?? ''));
                    $user = User::where('name', $empName)->first();
                }
            }
        }

        // 4. If user is still not found, auto-provision now
        if (!$user && ($goal || $emp)) {
            try {
                $empCode = $goal ? $goal->employee_code : ($emp->employeeid ?: $emp->emp_code);
                $empName = $goal ? $goal->candidate_name : trim(($emp->firstname ?? '') . ' ' . ($emp->surname ?? ''));
                $userEmail = ($emp && !empty($emp->email) && filter_var($emp->email, FILTER_VALIDATE_EMAIL))
                    ? $emp->email 
                    : (strtolower($cleanCode) . '@melcomgroup.com');

                if (User::where('email', $userEmail)->exists()) {
                    $userEmail = strtolower($cleanCode) . '_' . substr(md5(uniqid()), 0, 5) . '@melcomgroup.com';
                }

                $newUserData = [
                    'name' => $empName,
                    'username' => $empCode,
                    'email' => $userEmail,
                    'password' => bcrypt('password'),
                    'position_id' => 1,
                    'user_id' => ($goal && !empty($goal->created_by)) ? $goal->created_by : 1,
                    'admin' => 0,
                    'is_manager' => 0,
                ];
                if ($hasEmployeeCode) $newUserData['employee_code'] = $empCode;
                if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'permissions')) {
                    $newUserData['permissions'] = ['/pms/goals'];
                }
                if ($goal && !empty($goal->created_by) && \Illuminate\Support\Facades\Schema::hasColumn('users', 'line_manager_id')) {
                    $newUserData['line_manager_id'] = $goal->created_by;
                }
                $user = User::create($newUserData);
                if ($goal && empty($goal->user_id)) {
                    $goal->update(['user_id' => $user->id]);
                }
            } catch (\Throwable $e) {
                \Log::error('Auto-provisioning in login failed: ' . $e->getMessage());
            }
        }

        // 5. Authenticate password
        $authenticated = false;
        if ($user) {
            $isDefaultPassword = (strtolower($password) === 'password');
            if (Hash::check($password, $user->password) || 
                Hash::check('password', $user->password) || 
                Hash::check('Password', $user->password) || 
                ($isDefaultPassword && !$user->admin)) {
                $authenticated = true;

                // Sync username, employee_code, and default password for seamless future logins
                try {
                    $updates = [];
                    if (!$user->admin) {
                        $updates['password'] = bcrypt('password');
                    }
                    if ($hasEmployeeCode && (empty($user->employee_code) || $user->employee_code !== $loginInput)) {
                        $updates['employee_code'] = $loginInput;
                    }
                    if (empty($user->username) || $user->username !== $loginInput) {
                        $updates['username'] = $loginInput;
                    }
                    if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'permissions')) {
                        $isUserMgr = ($user->admin || $user->is_manager || in_array($user->position_id, [3, 4]));
                        if ($isUserMgr) {
                            $currentPerms = is_array($user->permissions) ? $user->permissions : [];
                            $updates['permissions'] = array_values(array_unique(array_merge($currentPerms, ['/dashboard', '/pms/dashboard', '/pms/goals', '/pms/appraisal', '/pms/review'])));
                        } else {
                            $updates['permissions'] = ['/pms/goals'];
                        }
                    }
                    if (!empty($updates)) {
                        $user->update($updates);
                    }

                    // Auto-link any goal for this employee code to this user account
                    if (\Illuminate\Support\Facades\Schema::hasTable('goals')) {
                        \App\Models\Goal::where(function ($gq) use ($loginInput, $cleanCode) {
                            $gq->where('employee_code', $loginInput)
                               ->orWhere('employee_code', $cleanCode);
                        })->update(['user_id' => $user->id]);
                    }
                } catch (\Throwable $syncEx) {}
            }
        }

        if (!$authenticated || !$user) {
            return response()->json([
                'result' => false,
                'message' => 'Invalid credentials'
            ]);
        }

        Auth::login($user);
        $token = $user->createToken('api_token')->plainTextToken;

        return response()->json([
            'result' => true,
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * Delete / Remove a user from the system
     */
    public function destroy(Request $request, $id = null)
    {
        $userId = $id ?: $request->input('id') ?: $request->route('id');

        if (!$userId) {
            return response()->json([
                'status' => 'error',
                'message' => 'User ID is required for deletion'
            ], 400);
        }

        // Security check: Only administrators can delete users
        $currentUser = Auth::user();
        $isUserAdmin = $currentUser && ($currentUser->admin || (isset($currentUser->position_id) && $currentUser->position_id == 4) || (isset($currentUser->role) && strtolower($currentUser->role) === 'admin'));
        if (!$isUserAdmin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized: Only users with administrator privileges can delete user accounts.'
            ], 403);
        }

        // Prevent self-deletion if logged in
        if (Auth::check() && Auth::id() == $userId) {
            return response()->json([
                'status' => 'error',
                'message' => 'You cannot delete your own active account while logged in.'
            ], 400);
        }

        try {
            $user = User::find($userId);
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not found'
                ], 404);
            }

            // Revoke active sanctum tokens if any
            if (method_exists($user, 'tokens')) {
                $user->tokens()->delete();
            }

            $userName = $user->name ?: $user->username;
            $user->delete();

            return response()->json([
                'status' => 'success',
                'message' => "User {$userName} was successfully removed from the system."
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete user: ' . $e->getMessage()
            ], 500);
        }
    }
}


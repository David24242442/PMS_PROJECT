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

        $usertoauth = [
            'username' => $request->username,
            'password' => $request->password
        ];
        
        $authStatus = Auth::attempt($usertoauth);
        if(!$authStatus){
            return response()->json([
                'result' => false,
                'message' => 'Invalid credentials'
            ]);
        }

        $user = Auth::user();
        $token = $user->createToken('api_token')->plainTextToken;
        
        return response()->json([
            'result'=>true,
            'user'=>$user,
            'token'=>$token,
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


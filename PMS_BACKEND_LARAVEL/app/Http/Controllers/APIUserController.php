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
        $query = \App\Models\Employee::orderBy('firstname');

        if($request->has('search') && !empty($request->search)){
            $search = $request->search;
            $query->where(function($q) use ($search){
                 $q->where('firstname', 'LIKE', "%{$search}%")
                   ->orWhere('surname', 'LIKE', "%{$search}%")
                   ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        $employees = $query->paginate(10);
        
        // Transform the collection within the paginator
        $employees->getCollection()->transform(function($emp){
            return [
                'id' => $emp->id,
                'name' => $emp->firstname . ' ' . $emp->surname,
                'position_id' => $emp->joiningposition, 
                'email' => $emp->email,
                'department' => $emp->joining_dept_id,
                'status' => $emp->status,
                'joining_date' => $emp->joiningdate,
                'admin' => false, 
                'employee_details' => $emp 
            ];
        });

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

    
}

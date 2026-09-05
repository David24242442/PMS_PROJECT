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

    public function store(Request $request){
        $validator = \Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'email' => 'nullable|string|email|max:255|unique:users',
            'password' => 'required|string|min:4',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation Failed',
                'errors' => $validator->errors()
            ], 202); 
            // 202 helps Axios catch clean responses if they don't block 422 triggers
        }

        $user = $request;

        $userDetails = [
            'admin' => isset($user['admin']) && $user['admin'] ? 1 : 0,
            'user_id' => Auth::user() ? Auth::user()->id : 1, // Fallback for migration/seeding
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'password' => bcrypt($user['password']),
            'position_id' => $user->position_id,
            'employee_code' => $user->employee_code,
            'department' => $user->department,
            'location' => $user->location,
            'report_to' => $user->report_to,
            'is_manager' => isset($user['is_manager']) ? ($user['is_manager'] ? 1 : 0) : 0,
            'permissions' => $user->permissions,
        ];

        try {
            $u = User::create($userDetails);

            return response()->json([
                'status' => 'success',
                'message' => 'User created successfully',
                'data' => $u
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function updateuser(Request $request){
        $user = $request;
        
        $validator = \Validator::make($request->all(), [
            'id' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
            'username' => 'required|string',
            'email' => 'nullable|string|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation Failed',
                'errors' => $validator->errors()
            ], 202);
        }

        $userDetails = [
            'admin' => isset($user['admin']) ? ($user['admin'] ? 1 : 0) : 0,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'position_id' => $user->position_id,
            'employee_code' => $user->employee_code,
            'department' => $user->department,
            'location' => $user->location,
            'report_to' => $user->report_to,
            'is_manager' => isset($user['is_manager']) ? ($user['is_manager'] ? 1 : 0) : 0,
            'permissions' => $user->permissions,
        ];

        try {
            $u = User::find($user['id']);
            if (!$u) {
                return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
            }

            $u->update($userDetails);

            if(isset($user['password']) && !empty($user['password'])){
                $u->update(['password' => bcrypt($user['password'])]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'User updated successfully',
                'data' => $u
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
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

        

        return response()->json([
            'success' => true,
            'message' => 'Your password has been updated successfully.'
        ]);

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
        
        $authuser = Auth::attempt($usertoauth);
        if(!$authuser){
            return response()->json([
                'result' => false,
            ]);
        }

        $user = Auth::user();
        
        $token = $user->createToken('api_token')->plainTextToken;
        // $persmissions = $user->getAllPermissions();

        
        return response()->json([
            'result'=>true,
            'user'=>$user,
            'token'=>$token,
            // 'permissions'=>$persmissions,
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


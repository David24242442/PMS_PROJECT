<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GoalController extends Controller
{
    /**
     * Ensure critical PMS database schema exists.
     */
    private function ensureSchema()
    {
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('goals')) {
                \Illuminate\Support\Facades\Schema::table('goals', function ($table) {
                    if (!\Illuminate\Support\Facades\Schema::hasColumn('goals', 'created_by')) {
                        $table->unsignedBigInteger('created_by')->nullable();
                    }
                    if (!\Illuminate\Support\Facades\Schema::hasColumn('goals', 'manager_name')) {
                        $table->string('manager_name')->nullable();
                    }
                    if (!\Illuminate\Support\Facades\Schema::hasColumn('goals', 'appraisal_data')) {
                        $table->longText('appraisal_data')->nullable();
                    }
                    if (!\Illuminate\Support\Facades\Schema::hasColumn('goals', 'employee_code')) {
                        $table->string('employee_code')->nullable();
                    }
                    if (!\Illuminate\Support\Facades\Schema::hasColumn('goals', 'candidate_name')) {
                        $table->string('candidate_name')->nullable();
                    }
                    if (!\Illuminate\Support\Facades\Schema::hasColumn('goals', 'department')) {
                        $table->string('department')->nullable();
                    }
                    if (!\Illuminate\Support\Facades\Schema::hasColumn('goals', 'location')) {
                        $table->string('location')->nullable();
                    }
                    if (!\Illuminate\Support\Facades\Schema::hasColumn('goals', 'job_title')) {
                        $table->string('job_title')->nullable();
                    }
                    if (!\Illuminate\Support\Facades\Schema::hasColumn('goals', 'smart_criteria')) {
                        $table->json('smart_criteria')->nullable();
                    }
                    if (!\Illuminate\Support\Facades\Schema::hasColumn('goals', 'quarterly_tracking')) {
                        $table->json('quarterly_tracking')->nullable();
                    }
                    if (!\Illuminate\Support\Facades\Schema::hasColumn('goals', 'submitted_at')) {
                        $table->timestamp('submitted_at')->nullable();
                    }
                    if (!\Illuminate\Support\Facades\Schema::hasColumn('goals', 'hr_comments')) {
                        $table->text('hr_comments')->nullable();
                    }
                    if (!\Illuminate\Support\Facades\Schema::hasColumn('goals', 'overall_rating')) {
                        $table->decimal('overall_rating', 3, 2)->nullable();
                    }
                });
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('users')) {
                \Illuminate\Support\Facades\Schema::table('users', function ($table) {
                    if (!\Illuminate\Support\Facades\Schema::hasColumn('users', 'employee_code')) {
                        $table->string('employee_code')->nullable();
                    }
                    if (!\Illuminate\Support\Facades\Schema::hasColumn('users', 'line_manager_id')) {
                        $table->unsignedBigInteger('line_manager_id')->nullable();
                    }
                    if (!\Illuminate\Support\Facades\Schema::hasColumn('users', 'report_to')) {
                        $table->string('report_to')->nullable();
                    }
                    if (!\Illuminate\Support\Facades\Schema::hasColumn('users', 'is_manager')) {
                        $table->boolean('is_manager')->default(0);
                    }
                    if (!\Illuminate\Support\Facades\Schema::hasColumn('users', 'permissions')) {
                        $table->text('permissions')->nullable();
                    }
                    if (!\Illuminate\Support\Facades\Schema::hasColumn('users', 'appraisal_template')) {
                        $table->longText('appraisal_template')->nullable();
                    }
                });
            }
        } catch (\Throwable $e) {
            \Log::warning("GoalController ensureSchema warning: " . $e->getMessage());
        }
    }

    /**
     * Compute display_status for a goal.
     */
    private function computeDisplayStatus($goal, $completedReviewEmpCodes = null)
    {
        if ($completedReviewEmpCodes === null) {
            $year = $goal->year ?? date('Y');
            $completedReviewEmpCodes = [];
            try {
                if (\Illuminate\Support\Facades\Schema::hasTable('reviews') && 
                    \Illuminate\Support\Facades\Schema::hasColumn('reviews', 'emp_code') && 
                    \Illuminate\Support\Facades\Schema::hasColumn('reviews', 'status')) {
                    $completedReviewEmpCodes = \App\Models\Review::where('year', $year)
                        ->where('status', 'completed')
                        ->pluck('emp_code')
                        ->toArray();
                }
            } catch (\Throwable $e) {
                $completedReviewEmpCodes = [];
            }
        }

        if ($goal->status === 'draft') {
            return 'draft';
        } elseif ($goal->status === 'assigned') {
            return 'assigned';
        } elseif ($goal->status === 'submitted') {
            return 'submitted';
        } elseif ($goal->status === 'review_completed') {
            return 'review_completed';
        } elseif ($goal->status === 'completed' && $goal->employee_code && in_array($goal->employee_code, $completedReviewEmpCodes)) {
            return 'review_completed';
        } elseif ($goal->status === 'completed') {
            return 'appraisal_completed';
        } else {
            return 'goal_created';
        }
    }

    /**
     * Fetch goals for the authenticated user unless specified otherwise.
     */
    public function index(Request $request)
    {
        try {
            $this->ensureSchema();

            $year = $request->get('year', date('Y'));
            $user = $request->user();
            $userId = $user ? $user->id : $request->get('user_id');

            if (!$userId) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
            }

            $isAdmin = $user && $user->admin;

            // Admin sees all goals; regular users see their own + their team's
            $query = \App\Models\Goal::where('year', $year);

            if (!$isAdmin) {
                $userName = $user ? trim($user->name) : '';
                $userUsername = $user ? trim($user->username ?? '') : '';
                $userEmpCode = $user ? trim($user->employee_code ?? '') : '';

                $teamUserIds = [];
                $teamEmpCodes = [];
                try {
                    if (\Illuminate\Support\Facades\Schema::hasTable('users') && \Illuminate\Support\Facades\Schema::hasColumn('users', 'line_manager_id')) {
                        $teamUserIds = \App\Models\User::where('line_manager_id', $userId)->pluck('id')->toArray();
                    }
                    if (\Illuminate\Support\Facades\Schema::hasTable('employees') && \Illuminate\Support\Facades\Schema::hasColumn('employees', 'line_manager_id')) {
                        if (\Illuminate\Support\Facades\Schema::hasColumn('employees', 'employeeid')) {
                            $teamEmpCodes = \App\Models\Employee::where('line_manager_id', $userId)->pluck('employeeid')->filter()->toArray();
                        }
                        if (empty($teamEmpCodes) && \Illuminate\Support\Facades\Schema::hasColumn('employees', 'emp_code')) {
                            $teamEmpCodes = \App\Models\Employee::where('line_manager_id', $userId)->pluck('emp_code')->filter()->toArray();
                        }
                    }
                } catch (\Throwable $e) {}

                $hasCreatedBy = \Illuminate\Support\Facades\Schema::hasColumn('goals', 'created_by');
                $hasManagerName = \Illuminate\Support\Facades\Schema::hasColumn('goals', 'manager_name');
                $hasEmployeeCode = \Illuminate\Support\Facades\Schema::hasColumn('goals', 'employee_code');

                $query->where(function ($q) use ($userId, $userName, $userUsername, $userEmpCode, $teamUserIds, $teamEmpCodes, $hasCreatedBy, $hasManagerName, $hasEmployeeCode) {
                    // 1. Employee's own assigned goal
                    $q->where('user_id', $userId);

                    if (!empty($userEmpCode) && $hasEmployeeCode) {
                        $q->orWhere('employee_code', $userEmpCode);
                    }

                    // 2. Goals created or assigned by this user (as Line Manager / Creator)
                    if ($hasCreatedBy) {
                        $q->orWhere('created_by', $userId);
                    }

                    // 3. Goals where manager_name matches or contains this Line Manager's name/username
                    if ($hasManagerName) {
                        if (!empty($userName)) {
                            $q->orWhere('manager_name', $userName)
                              ->orWhere('manager_name', 'like', '%' . $userName . '%');
                        }
                        if (!empty($userUsername) && $userUsername !== $userName) {
                            $q->orWhere('manager_name', $userUsername)
                              ->orWhere('manager_name', 'like', '%' . $userUsername . '%');
                        }
                    }

                    // 4. Subordinate goals in reporting line
                    if (!empty($teamUserIds)) {
                        $q->orWhereIn('user_id', $teamUserIds);
                    }
                    if (!empty($teamEmpCodes) && $hasEmployeeCode) {
                        $q->orWhereIn('employee_code', $teamEmpCodes);
                    }
                });
            }

            $goals = $query->orderBy('created_at', 'desc')->get();

            // Fetch completed reviews for this year to determine review status
            $completedReviewEmpCodes = [];
            try {
                if (\Illuminate\Support\Facades\Schema::hasTable('reviews') && 
                    \Illuminate\Support\Facades\Schema::hasColumn('reviews', 'emp_code') && 
                    \Illuminate\Support\Facades\Schema::hasColumn('reviews', 'status')) {
                    $completedReviewEmpCodes = \App\Models\Review::where('year', $year)
                        ->where('status', 'completed')
                        ->pluck('emp_code')
                        ->toArray();
                }
            } catch (\Throwable $e) {}

            // Add computed display_status to each goal and resolve job_title from employee joining position
            $goals->each(function ($goal) use ($completedReviewEmpCodes) {
                try {
                    $goal->display_status = $this->computeDisplayStatus($goal, $completedReviewEmpCodes);
                    if ((empty($goal->job_title) || in_array($goal->job_title, ['Employee', 'N/A', ''])) && !empty($goal->employee_code)) {
                        $emp = null;
                        if (\Illuminate\Support\Facades\Schema::hasTable('employees')) {
                            if (\Illuminate\Support\Facades\Schema::hasColumn('employees', 'employeeid')) {
                                $emp = \App\Models\Employee::where('employeeid', $goal->employee_code)->first();
                            }
                            if (!$emp && \Illuminate\Support\Facades\Schema::hasColumn('employees', 'emp_code')) {
                                $emp = \App\Models\Employee::where('emp_code', $goal->employee_code)->first();
                            }
                        }
                        if ($emp) {
                            $pos = $emp->job_title ?: ($emp->joiningposition ?? null);
                            if ($pos && $pos !== 'N/A' && $pos !== 'Employee') {
                                $goal->job_title = $pos;
                            }
                        }
                    }
                } catch (\Throwable $e) {}
            });

            return response()->json([
                'status' => 'success',
                'data' => $goals
            ]);
        } catch (\Throwable $e) {
            \Log::error('GoalController@index error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load goals: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Store a new goal.
     */
    public function store(Request $request)
    {
        // Debug: log what we receive
        \Log::info('GoalController@store - incoming data keys: ' . implode(', ', array_keys($request->all())));
        
        $data = $request->all();
        if ((empty($data['job_title']) || in_array($data['job_title'], ['Employee', 'N/A', ''])) && !empty($data['employee_code'])) {
            $emp = \App\Models\Employee::where('employeeid', $data['employee_code'])->first();
            if ($emp) {
                $pos = $emp->job_title ?: ($emp->joiningposition ?? null);
                if ($pos && $pos !== 'N/A' && $pos !== 'Employee') {
                    $data['job_title'] = $pos;
                }
            }
        }

        $goal = \App\Models\Goal::create($data);
        $goal->display_status = $this->computeDisplayStatus($goal);

        return response()->json([
            'status' => 'success',
            'message' => 'Goal created successfully',
            'data' => $goal
        ]);
    }

    /**
     * Update an existing goal.
     */
    public function update(Request $request, $id)
    {
        try {
            $goal = \App\Models\Goal::findOrFail($id);
            
            $data = $request->except(['display_status']);
            if ((empty($data['job_title']) || in_array($data['job_title'], ['Employee', 'N/A', ''])) && !empty($goal->employee_code)) {
                $emp = \App\Models\Employee::where('employeeid', $goal->employee_code)->first();
                if ($emp) {
                    $pos = $emp->job_title ?: ($emp->joiningposition ?? null);
                    if ($pos && $pos !== 'N/A' && $pos !== 'Employee') {
                        $data['job_title'] = $pos;
                    }
                }
            }

            $goal->update($data);
            $goal->display_status = $this->computeDisplayStatus($goal);

            return response()->json([
                'status' => 'success',
                'message' => 'Goal updated successfully',
                'data' => $goal
            ]);
        } catch (\Exception $e) {
            \Log::error('GoalController@update failed: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update goal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a goal.
     */
    public function destroy($id)
    {
        $goal = \App\Models\Goal::findOrFail($id);
        $goal->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Goal deleted successfully'
        ]);
    }

    /**
     * Handle file uploads for goal attachments.
     */
    public function uploadAttachment(Request $request)
    {
        $request->validate([
            'files.*' => 'required|file|mimes:jpeg,png,jpg,gif,svg,pdf,doc,docx,xls,xlsx,csv,txt|max:10240', // 10MB max
        ]);

        if ($request->hasFile('files')) {
            $files = $request->file('files');
            $uploadedFiles = [];

            foreach ($files as $file) {
                // Generate a unique filename
                $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
                
                // Store the file in the 'public/attachments' directory
                $path = $file->storeAs('public/attachments', $filename);
                
                // Keep track of uploaded files to return to frontend
                $uploadedFiles[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => Storage::url($path), // Generate public URL
                    'type' => $file->getClientMimeType(),
                ];
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Files uploaded successfully',
                'files' => $uploadedFiles
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'No files uploaded'
        ], 400);
    }

    /**
     * Fetch all goals with appraisal data for HR review.
     */
    public function allAppraisals(Request $request)
    {
        try {
            $year = $request->get('year', date('Y'));
            $user = $request->user();
            $isAdmin = $user && $user->admin;

            $query = \App\Models\Goal::with('user')
                ->where('year', $year)
                ->whereNotNull('appraisal_data')
                ->where('status', '!=', 'draft');

            // Non-admin users only see their own submissions
            if (!$isAdmin && $user) {
                $query->where('user_id', $user->id);
            }

            $goals = $query->orderBy('submitted_at', 'desc')
                ->orderBy('updated_at', 'desc')
                ->get();

            $goals->each(function ($goal) {
                if ((empty($goal->job_title) || in_array($goal->job_title, ['Employee', 'N/A', ''])) && !empty($goal->employee_code)) {
                    $emp = \App\Models\Employee::where('employeeid', $goal->employee_code)->first();
                    if ($emp) {
                        $pos = $emp->job_title ?: ($emp->joiningposition ?? null);
                        if ($pos && $pos !== 'N/A' && $pos !== 'Employee') {
                            $goal->job_title = $pos;
                        }
                    }
                }
            });

            return response()->json([
                'status' => 'success',
                'data' => $goals
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in allAppraisals: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Database error. Please ensure migrations are run.'
            ], 500);
        }
    }

    /**
     * Review and evaluate an appraisal (HR Action).
     */
    public function reviewAppraisal(Request $request, $id)
    {
        $goal = \App\Models\Goal::findOrFail($id);
        
        $goal->update([
            'status' => $request->input('status'),
            'hr_comments' => $request->input('hr_comments'),
            'overall_rating' => $request->input('overall_rating'),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Appraisal reviewed successfully',
            'data' => $goal
        ]);
    }

    /**
     * Fetch goals with appraisal data for the Appraisal view.
     */
    public function appraisals(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $user = $request->user();
        $userId = $request->get('user_id', $user ? $user->id : null);
        $goalId = $request->get('goal_id');

        \Log::info("GoalController@appraisals - year: $year, userId: $userId, goalId: $goalId");

        // If a specific goal_id is provided, prioritize it
        if ($goalId) {
            $goal = \App\Models\Goal::find($goalId);
            if ($goal) {
                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'goal' => $goal,
                        'appraisal_data' => $goal->appraisal_data,
                        'all_goals' => []
                    ]
                ]);
            }
        }

        $employeeCode = $request->get('employee_code');

        // For employee or specific candidate query
        $query = \App\Models\Goal::where('year', $year);
        if (!empty($employeeCode)) {
            $query->where('employee_code', $employeeCode);
        } elseif ($user && !$user->admin && !$user->is_manager && $user->position_id !== 1) {
            $query->where(function ($q) use ($user, $userId) {
                if (!empty($user->employee_code)) {
                    $q->where('employee_code', $user->employee_code);
                }
                if ($userId) {
                    $q->orWhere('user_id', $userId);
                }
            });
        } else {
            $query->where('user_id', $userId);
        }

        $goals = $query->orderBy('created_at', 'desc')->get();

        if ($goals->isEmpty()) {
            return response()->json([
                'status' => 'empty',
                'message' => 'No appraisal data found for this year'
            ]);
        }

        $primaryGoal = $goals->first();

        return response()->json([
            'status' => 'success',
            'data' => [
                'goal' => $primaryGoal,
                'appraisal_data' => $primaryGoal->appraisal_data,
                'all_goals' => $goals
            ]
        ]);
    }

    /**
     * Assign Goal and Appraisal template to single employee or all team members in bulk.
     */
    public function assign(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
            }

            $year = $request->input('year', date('Y'));
            $assignType = $request->input('assign_type', 'single'); // 'single' or 'all_team'
            $selectedEmployees = $request->input('employees', []);
            
            $goalTitle = trim($request->input('title', ''));
            if (empty($goalTitle)) {
                $goalTitle = 'Yearly SMART Goals FY ' . $year;
            }
            $description = $request->input('description', ['']);
            $purposes = $request->input('purposes', ['']);
            $challenges = $request->input('challenges', ['']);
            $category = $request->input('category', 'Operational');
            $target = $request->input('target', 100);
            $dueDate = $request->input('due_date', $year . '-12-31');
            $completionDate = $request->input('completion_date', null);
            $smartCriteria = $request->input('smart_criteria', [
                'specific' => false,
                'measurable' => false,
                'attainable' => false,
                'relevant' => false,
                'time_bound' => false,
            ]);
            $quarterlyTracking = $request->input('quarterly_tracking', null);
            $appraisalTemplate = $request->input('appraisal_data', null);

            // If no explicit appraisal_data provided, fallback to line manager's saved custom template
            if (empty($appraisalTemplate) || empty($appraisalTemplate['competencies'])) {
                if (!empty($user->appraisal_template)) {
                    $rawComps = is_array($user->appraisal_template) ? $user->appraisal_template : json_decode($user->appraisal_template, true);
                    if (!empty($rawComps)) {
                        $appraisalTemplate = [
                            'competencies' => $rawComps,
                            'comments' => '',
                            'impressedMost' => '',
                            'impressedLeast' => '',
                            'performanceRating' => 0,
                            'rating_comments' => ['1' => '', '2' => '', '3' => '', '4' => '', '5' => ''],
                            'candidate_signature_name' => '',
                            'manager_signature_name' => $user->name,
                            'signature_date' => date('Y-m-d')
                        ];
                    }
                }
            }

            // Resolve target employee list
            $targetList = [];
            if ($assignType === 'all_team') {
                $empQuery = \App\Models\Employee::query();
                if (!$user->admin) {
                    $empQuery->where('line_manager_id', $user->id);
                }
                $targetEmployees = $empQuery->get();
                foreach ($targetEmployees as $emp) {
                    $targetList[] = [
                        'employee_code' => $emp->employeeid,
                        'name' => trim($emp->firstname . ' ' . $emp->surname),
                        'firstname' => $emp->firstname,
                        'surname' => $emp->surname,
                        'department' => $emp->department ?: ($emp->joiningdepartment ?? 'N/A'),
                        'location' => $emp->location ?: ($emp->joininglocation ?? 'N/A'),
                        'job_title' => $emp->job_title ?: ($emp->joiningposition ?? 'Employee'),
                        'email' => $emp->email,
                    ];
                }
            } else {
                if (is_array($selectedEmployees) && isset($selectedEmployees[0])) {
                    $targetList = $selectedEmployees;
                } elseif (!empty($selectedEmployees)) {
                    $targetList = [$selectedEmployees];
                }
            }

            if (empty($targetList)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No employees selected or found to assign goals to.'
                ], 422);
            }

            // Ensure table columns exist before inserting
            try {
                if (\Illuminate\Support\Facades\Schema::hasTable('goals')) {
                    \Illuminate\Support\Facades\Schema::table('goals', function ($table) {
                        if (!\Illuminate\Support\Facades\Schema::hasColumn('goals', 'appraisal_data')) {
                            $table->longText('appraisal_data')->nullable();
                        }
                        if (!\Illuminate\Support\Facades\Schema::hasColumn('goals', 'manager_name')) {
                            $table->string('manager_name')->nullable();
                        }
                    });
                }
            } catch (\Throwable $e) {
                \Log::warning("Schema check in assign: " . $e->getMessage());
            }

            $userCols = \Illuminate\Support\Facades\Schema::hasTable('users') ? \Illuminate\Support\Facades\Schema::getColumnListing('users') : [];
            $goalCols = \Illuminate\Support\Facades\Schema::hasTable('goals') ? \Illuminate\Support\Facades\Schema::getColumnListing('goals') : [];
            $empCols  = \Illuminate\Support\Facades\Schema::hasTable('employees') ? \Illuminate\Support\Facades\Schema::getColumnListing('employees') : [];

            $assignedCount = 0;
            $createdGoals = [];

            foreach ($targetList as $empData) {
                try {
                    $empCode = $empData['employee_code'] ?? null;
                    if (!$empCode) continue;

                    $candName = $empData['name'] ?? trim(($empData['firstname'] ?? '') . ' ' . ($empData['surname'] ?? ''));
                    $dept = $empData['department'] ?? 'N/A';
                    $loc = $empData['location'] ?? 'N/A';
                    $jobTitle = $empData['job_title'] ?? ($empData['position'] ?? 'Employee');
                    $email = $empData['email'] ?? null;

                    // Ensure manager is flagged as is_manager
                    if (!$user->is_manager) {
                        try {
                            $user->update(['is_manager' => 1]);
                        } catch (\Throwable $mEx) {}
                    }

                    // Provision or resolve login account for employee
                    // Username = EmployeeCode (e.g. SBPHD360), Password = password
                    $firstName = !empty($empData['firstname']) ? trim($empData['firstname']) : explode(' ', $candName)[0];
                    $generatedUsername = $empCode;

                    // Build a guaranteed valid, non-null, unique email
                    $cleanEmpCode = preg_replace('/[^a-zA-Z0-9]/', '', $empCode);
                    $fallbackEmail = strtolower($cleanEmpCode) . '@melcomgroup.com';
                    $userEmail = (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) ? $email : $fallbackEmail;
                    if (\App\Models\User::where('email', $userEmail)->where('employee_code', '!=', $empCode)->exists()) {
                        $userEmail = strtolower($cleanEmpCode) . '_' . substr(md5(uniqid()), 0, 6) . '@melcomgroup.com';
                    }

                    $empUser = null;
                    if (in_array('employee_code', $userCols)) {
                        $empUser = \App\Models\User::where('employee_code', $empCode)
                            ->orWhere('employee_code', $cleanEmpCode)
                            ->first();
                    }
                    if (!$empUser) {
                        $empUser = \App\Models\User::where('username', $generatedUsername)
                            ->orWhere('username', $empCode)
                            ->orWhere('username', 'like', '%' . $empCode)
                            ->orWhere('email', $userEmail)
                            ->orWhere('email', $fallbackEmail)
                            ->orWhere('name', $candName)
                            ->first();
                    }

                    if (!$empUser) {
                        try {
                            $newUserData = [
                                'name' => $candName,
                                'username' => $empCode,
                                'email' => $userEmail,
                                'password' => bcrypt('password'),
                                'position_id' => 1,
                                'user_id' => $user->id,
                                'is_manager' => 0,
                                'admin' => 0,
                            ];
                            if (in_array('employee_code', $userCols)) $newUserData['employee_code'] = $empCode;
                            if (in_array('department', $userCols)) $newUserData['department'] = $dept;
                            if (in_array('location', $userCols)) $newUserData['location'] = $loc;
                            if (in_array('line_manager_id', $userCols)) $newUserData['line_manager_id'] = $user->id;
                            if (in_array('report_to', $userCols)) $newUserData['report_to'] = $user->name ?: $user->username;
                            if (in_array('permissions', $userCols)) $newUserData['permissions'] = ['/pms/goals'];

                            $empUser = \App\Models\User::create($newUserData);
                        } catch (\Throwable $e) {
                            \Log::warning("Could not auto-create user for {$candName}: " . $e->getMessage());
                            // Fallback retry with uniquely generated email
                            try {
                                $newUserData['email'] = strtolower($cleanEmpCode) . '_' . time() . '@melcomgroup.com';
                                $empUser = \App\Models\User::create($newUserData);
                            } catch (\Throwable $e2) {
                                \Log::error("Retry create user failed for {$candName}: " . $e2->getMessage());
                            }
                        }
                    } else {
                        try {
                            $updateData = [
                                'password' => bcrypt('password'), // Ensure login password is password
                                'username' => $empCode,
                            ];
                            if (in_array('line_manager_id', $userCols)) $updateData['line_manager_id'] = $user->id;
                            if (in_array('report_to', $userCols)) $updateData['report_to'] = $user->name ?: $user->username;
                            if (in_array('employee_code', $userCols)) {
                                $updateData['employee_code'] = $empCode;
                            }
                            if (in_array('permissions', $userCols)) {
                                if (!$empUser->admin && !$empUser->is_manager && !in_array($empUser->position_id, [3, 4])) {
                                    $updateData['permissions'] = ['/pms/goals'];
                                }
                            }
                            $empUser->update($updateData);
                        } catch (\Throwable $e) {
                            \Log::warning("Could not update manager for {$candName}: " . $e->getMessage());
                        }
                    }

                    // Sync reporting line in employees table as well
                    if (in_array('line_manager_id', $empCols)) {
                        try {
                            \App\Models\Employee::where('employeeid', $empCode)->update([
                                'line_manager_id' => $user->id
                            ]);
                        } catch (\Throwable $e) {
                            // ignore
                        }
                    }

                    // Prepare Appraisal Data snapshot for this employee
                    $empAppraisalData = $appraisalTemplate ?: [
                        'competencies' => [
                            ['id' => 1, 'title' => 'Performance & Teamwork', 'weight' => 20, 'selfRating' => 0, 'managerRating' => 0, 'descriptions' => ['Overall performance based on feedback from Line or Operations Managers', 'Teamwork and people management issues']],
                            ['id' => 2, 'title' => 'Customer Service / Relationship Building', 'weight' => 20, 'selfRating' => 0, 'managerRating' => 0, 'descriptions' => ['Super saver cards and service quality', 'Google rating improvement and satisfaction']],
                            ['id' => 3, 'title' => 'Execution / Sales Results Driven', 'weight' => 20, 'selfRating' => 0, 'managerRating' => 0, 'descriptions' => ['Business driven metric set by department', 'Loss to company % mitigation and delivery']],
                            ['id' => 4, 'title' => 'Compliance & Quality Standards', 'weight' => 20, 'selfRating' => 0, 'managerRating' => 0, 'descriptions' => ['Adherence to company policies, SOPs, safety, and regulatory compliance', 'Wooqer checklist and department standards implementation']],
                            ['id' => 5, 'title' => 'Continuous Improvement in workflows/processes', 'weight' => 20, 'selfRating' => 0, 'managerRating' => 0, 'descriptions' => ['Culture of adaptability and operational innovation', 'Flexibility and problem solving']],
                        ],
                        'comments' => '',
                        'impressedMost' => '',
                        'impressedLeast' => '',
                        'performanceRating' => 0,
                        'rating_comments' => ['1' => '', '2' => '', '3' => '', '4' => '', '5' => ''],
                        'candidate_signature_name' => $candName,
                        'manager_signature_name' => $user->name,
                        'signature_date' => date('Y-m-d')
                    ];

                    // Check if a goal already exists for this employee in this year
                    $existingGoal = \App\Models\Goal::where('year', $year)
                        ->where(function ($q) use ($empCode, $empUser) {
                            $q->where('employee_code', $empCode);
                            if ($empUser) {
                                $q->orWhere('user_id', $empUser->id);
                            }
                        })
                        ->first();

                    $rawGoalData = [
                        'title' => $goalTitle,
                        'description' => is_array($description) ? $description : (json_decode($description, true) ?: [$description]),
                        'purposes' => is_array($purposes) ? $purposes : (json_decode($purposes, true) ?: [$purposes]),
                        'challenges' => is_array($challenges) ? $challenges : (json_decode($challenges, true) ?: [$challenges]),
                        'category' => $category,
                        'target' => $target,
                        'due_date' => $dueDate,
                        'completion_date' => $completionDate,
                        'year' => $year,
                        'user_id' => $empUser ? $empUser->id : $user->id,
                        'created_by' => $user->id,
                        'candidate_name' => $candName,
                        'employee_code' => $empCode,
                        'location' => $loc,
                        'department' => $dept,
                        'job_title' => $jobTitle,
                        'manager_name' => $user->name,
                        'smart_criteria' => $smartCriteria,
                        'quarterly_tracking' => $quarterlyTracking,
                        'appraisal_data' => $empAppraisalData,
                        'status' => 'assigned',
                    ];

                    // Filter goal data by columns actually present in goals table
                    $goalData = !empty($goalCols) ? array_intersect_key($rawGoalData, array_flip($goalCols)) : $rawGoalData;

                    if ($existingGoal) {
                        if (in_array($existingGoal->status, ['assigned', 'draft', 'in_progress'])) {
                            $existingGoal->update($goalData);
                            $existingGoal->display_status = $this->computeDisplayStatus($existingGoal);
                            $createdGoals[] = $existingGoal;
                        }
                    } else {
                        $newG = \App\Models\Goal::create($goalData);
                        $newG->display_status = $this->computeDisplayStatus($newG);
                        $createdGoals[] = $newG;
                    }

                    $assignedCount++;
                } catch (\Throwable $itemEx) {
                    \Log::error("GoalController@assign item failed for {$candName}: " . $itemEx->getMessage());
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => "Successfully assigned goals and appraisal to {$assignedCount} employee(s).",
                'count' => $assignedCount,
                'data' => $createdGoals
            ]);
        } catch (\Throwable $e) {
            \Log::error('GoalController@assign fatal error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to assign goals: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get the appraisal template for the line manager (or current user's line manager).
     */
    public function getManagerTemplate(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $manager = null;

        // 1. If specific manager_id requested
        if ($request->has('manager_id')) {
            $manager = \App\Models\User::find($request->manager_id);
        }
        
        // 2. If employee_code requested
        if (!$manager && $request->has('employee_code')) {
            $emp = \App\Models\Employee::where('employeeid', $request->employee_code)->first();
            if ($emp && !empty($emp->line_manager_id)) {
                $manager = \App\Models\User::find($emp->line_manager_id);
            }
            if (!$manager) {
                $u = \App\Models\User::where('employee_code', $request->employee_code)->first();
                if ($u && !empty($u->line_manager_id)) {
                    $manager = \App\Models\User::find($u->line_manager_id);
                }
            }
        }

        // 3. If current user is a manager or admin and no specific employee_code requested, return their own template
        if (!$manager && ($user->admin || $user->is_manager || $user->position_id === 3 || $user->position_id === 4)) {
            $manager = $user;
        }

        // 4. If user is an employee, look up their line manager
        if (!$manager) {
            if (!empty($user->line_manager_id)) {
                $manager = \App\Models\User::find($user->line_manager_id);
            }
            if (!$manager && !empty($user->employee_code)) {
                $emp = \App\Models\Employee::where('employeeid', $user->employee_code)->first();
                if ($emp && !empty($emp->line_manager_id)) {
                    $manager = \App\Models\User::find($emp->line_manager_id);
                }
            }
            // 5. Look up via their active assigned Goal
            if (!$manager) {
                $goal = \App\Models\Goal::where(function ($q) use ($user) {
                    if (!empty($user->employee_code)) $q->where('employee_code', $user->employee_code);
                    $q->orWhere('user_id', $user->id);
                })->latest()->first();
                if ($goal) {
                    if (!empty($goal->created_by)) {
                        $manager = \App\Models\User::find($goal->created_by);
                    }
                    if (!$manager && !empty($goal->manager_name)) {
                        $manager = \App\Models\User::where('name', $goal->manager_name)
                            ->orWhere('name', 'like', '%' . trim($goal->manager_name) . '%')
                            ->first();
                    }
                }
            }
        }

        $template = null;
        if ($manager && !empty($manager->appraisal_template)) {
            $raw = $manager->appraisal_template;
            $template = is_array($raw) ? $raw : json_decode($raw, true);
        }

        return response()->json([
            'status' => 'success',
            'manager_id' => $manager ? $manager->id : null,
            'manager_name' => $manager ? $manager->name : null,
            'template' => $template
        ]);
    }

    /**
     * Save the appraisal template for the authenticated line manager.
     */
    public function saveManagerTemplate(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $template = $request->input('template') ?: $request->input('competencies');
        if (!$template) {
            return response()->json(['status' => 'error', 'message' => 'Template data is required'], 422);
        }

        // Check if users table has appraisal_template column; if not create it dynamically
        if (!\Illuminate\Support\Facades\Schema::hasColumn('users', 'appraisal_template')) {
            \Illuminate\Support\Facades\Schema::table('users', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->longText('appraisal_template')->nullable();
            });
        }

        $parsedTemplate = is_array($template) ? $template : json_decode($template, true);
        $user->appraisal_template = $parsedTemplate;
        $user->save();

        // Also propagate updated template to any active unsubmitted assigned/draft goals for this manager
        try {
            $activeGoals = \App\Models\Goal::where(function ($q) use ($user) {
                    $q->where('created_by', $user->id)
                      ->orWhere('manager_name', $user->name)
                      ->orWhere('manager_name', 'like', '%' . trim($user->name) . '%');
                })
                ->whereIn('status', ['assigned', 'draft'])
                ->get();

            foreach ($activeGoals as $ag) {
                $ad = is_array($ag->appraisal_data) ? $ag->appraisal_data : json_decode($ag->appraisal_data, true);
                if (!empty($ad)) {
                    $currentComps = $ad['competencies'] ?? [];
                    $updatedComps = [];
                    foreach ($parsedTemplate as $idx => $nc) {
                        $match = null;
                        foreach ($currentComps as $cc) {
                            if (($cc['id'] ?? null) == ($nc['id'] ?? null)) {
                                $match = $cc;
                                break;
                            }
                        }
                        $updatedComps[] = array_merge($nc, [
                            'selfRating' => $match['selfRating'] ?? 0,
                            'managerRating' => $match['managerRating'] ?? 0,
                        ]);
                    }
                    $ad['competencies'] = $updatedComps;
                    $ag->appraisal_data = $ad;
                    $ag->save();
                }
            }
        } catch (\Exception $e) {
            \Log::warning("Could not propagate template to active goals: " . $e->getMessage());
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Appraisal template saved successfully for line manager ' . $user->name,
            'template' => $user->appraisal_template
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Goal;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

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
    /**
     * Lookup maps for resolving joining_dept_id and joining_branch_id to names.
     * These match the frontend masterdata.js reference data.
     */
    private static $deptLookup = [
        1=>'ACCOUNTS',17=>'ADVERT',2=>'ADMINISTRATION',18=>'ARTS KITCHEN',3=>'AUDIT',
        19=>'BAKERY',20=>'BAR',21=>'BUTCHERY',16=>'CHOP BAR',22=>'CROWN STAR',
        23=>'ELECTRICAL APPLIANCES',24=>'GIORDANO',25=>'HALLAB',26=>'HOMEDECORE & TEXTILES',
        27=>'HOUSEWARE & KITCHENWARE',6=>'HR',8=>'IT',7=>'IMPORT',28=>'LOGISTICS',
        29=>'LUGGAGE',30=>'MANAGEMENT',10=>'MARKETING',11=>'MERCHANDISE',13=>'OPERATIONS',
        31=>'OTHERS',14=>'PIZZA HUT',15=>'PROJECT',32=>'PRODUCTION',33=>'RESTAURANT',
        34=>'RETAIL',35=>'SECURITY',36=>'SERVICE CENTER',37=>'SPORTS & FITNESS',
        38=>'SUPERMARKET',39=>'WARE HOUSE',40=>'MELCOM NOW',41=>'DIGITAL COMMERCE',
        42=>'CENTURY',43=>'SIP CAFE',44=>'ON THE ROCK, BAR',45=>'WASABI',
        46=>'MAHARAJA',47=>'YOLE',48=>'SIP GOURMET',
    ];

    private static $branchLookup = [
        1=>'ABLEKUMA',2=>'ACCRA',3=>'ACCRA MALL',4=>'ACCRA WHOLESALES',
        6=>'ACHIMOTA',7=>'ACHIMOTA MALL',9=>'ADABRAKA',10=>'ADENTA',
        12=>'AFEINYA',13=>'AFLAO',14=>'AMASAMAN',16=>'ARCADIA SPINTEX NEW',
        24=>'ASAMANKESE',26=>'ASHAIMAN',29=>'ASHONGMAN',30=>'ASSIN FOSU',
        33=>'BEREKUM',34=>'BIBIANI',35=>'BOLGATANGA',36=>'CAPECOAST',
        39=>'DANSOMAN',41=>'EAST LEGON',47=>'EASTLEGON BOUNDARY ROAD',
        49=>'FRAFRAHA',50=>'GBAWE',62=>'HAATSO',66=>'HAMPTON SQUARE',
        67=>'HEAD OFFICE',68=>'HO',69=>'HOHOE',71=>'KANESHIE',75=>'KASOA',
        78=>'KASS',79=>'KISSEMAN',80=>'KOFORIDIA',83=>'KUMASI',
        91=>'KUMASI MALL',92=>'KUMASI SANTASI',94=>'LABADI',95=>'LABONE',
        96=>'LAPAZ SHOP',97=>'LOGISTICS',98=>'MADINA',102=>'MAKOLA',
        103=>'MANKESSIM',107=>'MELCOM NOW',110=>'MINI-DOME',112=>'NANAKROM',
        113=>'NKAWKAW',114=>'OBUASI',120=>'SAKUMONO',121=>'SEFWI WIAWSO',
        122=>'SERVICE CENTER',128=>'SPINTEX NEW MALL',133=>'SPINTEX RD.',
        134=>'SUNIYANI',137=>'SWEDRU',140=>'TAKORADI',141=>'TAMALE',
        143=>'TARKWA',144=>'TECHIMAN',148=>'TEMA',153=>'TEPA',
        154=>'TESHIE NUNGUA',156=>'UPSA',157=>'WA SHOP',161=>'WAREHOUSE',
        163=>'WEIJA SHOP',164=>'WENCHI',165=>'WH-TEMA-FZ',178=>'DOME',
        180=>'OYARIFA',181=>'LASHIBI',
    ];

    /**
     * Ensure database schemas and necessary columns are present across tables.
     */
    private function ensureSchema()
    {
        try {
            \App\Http\Controllers\MonthlyEmployeeController::ensureTableExists();
            $mTable = \App\Http\Controllers\MonthlyEmployeeController::getActualTableName();
            if (\Schema::hasTable($mTable) && !\Schema::hasColumn($mTable, 'line_manager_id')) {
                \Schema::table($mTable, function ($table) {
                    $table->unsignedBigInteger('line_manager_id')->nullable()->index();
                });
            }

            if (\Schema::hasTable('users')) {
                \Schema::table('users', function ($table) {
                    if (!\Schema::hasColumn('users', 'employee_code')) {
                        $table->string('employee_code')->nullable()->index();
                    }
                    if (!\Schema::hasColumn('users', 'line_manager_id')) {
                        $table->unsignedBigInteger('line_manager_id')->nullable()->index();
                    }
                    if (!\Schema::hasColumn('users', 'report_to')) {
                        $table->string('report_to')->nullable();
                    }
                    if (!\Schema::hasColumn('users', 'is_manager')) {
                        $table->boolean('is_manager')->default(0);
                    }
                    if (!\Schema::hasColumn('users', 'permissions')) {
                        $table->text('permissions')->nullable();
                    }
                    if (!\Schema::hasColumn('users', 'appraisal_template')) {
                        $table->longText('appraisal_template')->nullable();
                    }
                });
            }

            if (\Schema::hasTable('goals')) {
                \Schema::table('goals', function ($table) {
                    if (!\Schema::hasColumn('goals', 'appraisal_data')) {
                        $table->longText('appraisal_data')->nullable();
                    }
                    if (!\Schema::hasColumn('goals', 'manager_name')) {
                        $table->string('manager_name')->nullable();
                    }
                    if (!\Schema::hasColumn('goals', 'employee_code')) {
                        $table->string('employee_code')->nullable()->index();
                    }
                });

                // Automatic cleanup: reset any assigned goals with dummy text so fields and SMART criteria start blank
                try {
                    \DB::table('goals')
                        ->where('status', 'assigned')
                        ->where(function ($q) {
                            $q->where('description', 'like', '%Achieve operational excellence%')
                              ->orWhere('purposes', 'like', '%Performance review and competency evaluation%')
                              ->orWhere('challenges', 'like', '%Mitigate operational losses%');
                        })
                        ->update([
                            'description' => json_encode(['']),
                            'purposes' => json_encode(['']),
                            'challenges' => json_encode(['']),
                            'smart_criteria' => json_encode([
                                'specific' => false,
                                'measurable' => false,
                                'attainable' => false,
                                'relevant' => false,
                                'time_bound' => false
                            ]),
                        ]);
                } catch (\Throwable $clEx) {
                    \Log::warning("Reset dummy goals notice: " . $clEx->getMessage());
                }

                // Automatic cleanup: fix goals and users where department is 'HEAD OFFICE' or empty
                try {
                    $mTable = \App\Http\Controllers\MonthlyEmployeeController::getActualTableName();
                    if (\Schema::hasTable($mTable)) {
                        if (\Schema::hasColumn('goals', 'department')) {
                            $badGoals = \DB::table('goals')
                                ->where(function($q) {
                                    $q->whereNull('department')
                                      ->orWhere('department', '')
                                      ->orWhere('department', 'HEAD OFFICE')
                                      ->orWhere('department', 'N/A');
                                })
                                ->get();
                            foreach ($badGoals as $bg) {
                                $code = $bg->employee_code;
                                $mEmp = $code ? \DB::table($mTable)->where('emp_id', $code)->first() : null;
                                $desig = $mEmp ? $mEmp->designation : ($bg->job_title ?? '');
                                $inferred = self::inferDepartment($desig, null, null, null, $bg->location ?? '');
                                if ($inferred && $inferred !== 'General' && $inferred !== 'Operations') {
                                    \DB::table('goals')->where('id', $bg->id)->update([
                                        'department' => $inferred,
                                        'job_title' => $mEmp ? $mEmp->designation : $bg->job_title
                                    ]);
                                }
                            }
                        }
                        if (\Schema::hasColumn('users', 'department')) {
                            $badUsers = \DB::table('users')
                                ->where(function($q) {
                                    $q->whereNull('department')
                                      ->orWhere('department', '')
                                      ->orWhere('department', 'HEAD OFFICE')
                                      ->orWhere('department', 'N/A');
                                })
                                ->get();
                            foreach ($badUsers as $bu) {
                                $code = $bu->employee_code ?: $bu->username;
                                $mEmp = $code ? \DB::table($mTable)->where('emp_id', $code)->first() : null;
                                $desig = $mEmp ? $mEmp->designation : ($bu->designation ?? '');
                                $inferred = self::inferDepartment($desig, null, null, null, $bu->location ?? '');
                                if ($inferred && $inferred !== 'General' && $inferred !== 'Operations') {
                                    \DB::table('users')->where('id', $bu->id)->update([
                                        'department' => $inferred,
                                        'designation' => $mEmp ? $mEmp->designation : $bu->designation
                                    ]);
                                }
                            }
                        }
                    }
                } catch (\Throwable $fixDeptEx) {
                    \Log::warning("Reset bad departments notice: " . $fixDeptEx->getMessage());
                }
            }
        } catch (\Throwable $e) {
            \Log::warning("EmployeeMasterController ensureSchema notice: " . $e->getMessage());
        }
    }

    /**
     * Standard 5 appraisal competencies weighted at 20% each.
     */
    public static function getDefaultCompetencies()
    {
        return [
            ['id' => 1, 'title' => 'Job Knowledge & Quality of Work', 'weight' => 20, 'selfRating' => 0, 'managerRating' => 0, 'descriptions' => ['Demonstrates required technical knowledge and functional skills', 'Consistently delivers accurate, high-quality, and reliable output']],
            ['id' => 2, 'title' => 'Customer Focus / Business Acumen', 'weight' => 20, 'selfRating' => 0, 'managerRating' => 0, 'descriptions' => ['Delivers exceptional customer satisfaction (internal/external)', 'Understands business context, market needs, and company standards']],
            ['id' => 3, 'title' => 'Execution / Sales Results Driven', 'weight' => 20, 'selfRating' => 0, 'managerRating' => 0, 'descriptions' => ['Business driven metric set by department', 'Loss to company % mitigation and delivery']],
            ['id' => 4, 'title' => 'Compliance & Quality Standards', 'weight' => 20, 'selfRating' => 0, 'managerRating' => 0, 'descriptions' => ['Adherence to company policies, SOPs, safety, and regulatory compliance', 'Wooqer checklist and department standards implementation']],
            ['id' => 5, 'title' => 'Communication & People Leadership', 'weight' => 20, 'selfRating' => 0, 'managerRating' => 0, 'descriptions' => ['Active listening, clear expression, and effective peer engagement', 'Mentorship, teamwork, problem solving, and positive contribution']]
        ];
    }

    /**
     * Intelligently infer department from designation, user record, or manager department,
     * ensuring it never inappropriately duplicates the location (e.g. HEAD OFFICE).
     * Designation keywords are prioritized.
     */
    public static function inferDepartment($designation, $managerDept = null, $userDept = null, $legacyDept = null, $location = null)
    {
        $desig = strtoupper(trim($designation ?? ''));

        // 1. Keyword / designation inference (PRIORITY: always resolve directly from designation)
        if (preg_match('/\b(HR|HUMAN RESOURCE|PERSONNEL|RECRUIT|TALENT|TRAINING)\b/i', $desig)) {
            return 'Human Resources';
        }
        if (preg_match('/\b(ACCOUNT|ACCOUNTS|AUDIT|FINANCE|TAX|TREASURY|PAYROLL|CREDIT|BILLING|COST)\b/i', $desig)) {
            return 'Accounts & Finance';
        }
        if (preg_match('/\b(IT|SOFTWARE|DEVELOPER|SYSTEM|NETWORK|DATABASE|PROGRAMMER|TECHNICAL|HARDWARE|EDP)\b/i', $desig)) {
            return 'Information Technology';
        }
        if (preg_match('/\b(MARKETING|BRAND|ADVERTIS|CREATIVE|DIGITAL|PROMOTION|GRAPHIC)\b/i', $desig)) {
            return 'Marketing';
        }
        if (preg_match('/\b(WAREHOUSE|LOGISTICS|SUPPLY CHAIN|INVENTORY|DISPATCH|FORKLIFT|STORES?|PACKING)\b/i', $desig)) {
            return 'Warehouse & Logistics';
        }
        if (preg_match('/\b(SECURITY|LOSS PREVENTION|SURVEILLANCE|CCTV|GUARD)\b/i', $desig)) {
            return 'Security';
        }
        if (preg_match('/\b(MAINTENANCE|ENGINEER|ELECTRIC|PLUMB|FACILIT|ESTATE|FITTER|CARPENTER)\b/i', $desig)) {
            return 'Maintenance & Engineering';
        }
        if (preg_match('/\b(LEGAL|COMPLIANCE|GOVERNANCE)\b/i', $desig)) {
            return 'Legal & Compliance';
        }
        if (preg_match('/\b(PROCUREMENT|PURCHAS|BUYER|SOURCING)\b/i', $desig)) {
            return 'Procurement';
        }
        if (preg_match('/\b(TRANSPORT|DRIVER|VEHICLE|FLEET|CHAUFFEUR)\b/i', $desig)) {
            return 'Transport';
        }
        if (preg_match('/\b(CUSTOMER SERVICE|CALL CENTER|FRONT DESK|RECEPTION)\b/i', $desig)) {
            return 'Customer Service';
        }
        if (preg_match('/\b(CASHIER|TELLER|TILL)\b/i', $desig)) {
            return 'Cash Office';
        }
        if (preg_match('/\b(SHOP|RETAIL|SUPERMARKET|BRANCH|SALES|MERCHANDISE|SECTION HEAD)\b/i', $desig)) {
            return 'Retail Operations';
        }

        // 2. If valid user department exists and does not match location or HEAD OFFICE
        if (!empty($userDept) && $userDept !== 'N/A' && strcasecmp(trim($userDept), 'HEAD OFFICE') !== 0 && strcasecmp(trim($userDept), trim($location ?? '')) !== 0) {
            return $userDept;
        }
        // 3. If valid legacy department exists and does not match location or HEAD OFFICE
        if (!empty($legacyDept) && $legacyDept !== 'N/A' && strcasecmp(trim($legacyDept), 'HEAD OFFICE') !== 0 && strcasecmp(trim($legacyDept), trim($location ?? '')) !== 0) {
            return $legacyDept;
        }

        // 4. Inherit from manager if manager has a valid department
        if (!empty($managerDept) && $managerDept !== 'N/A' && strcasecmp(trim($managerDept), 'HEAD OFFICE') !== 0 && strcasecmp(trim($managerDept), trim($location ?? '')) !== 0) {
            return $managerDept;
        }

        return 'Operations';
    }

    public function getEmployees(Request $request)
    {
        try {
            $this->ensureSchema();
            $user = $request->user();
            $isAdmin = $user && $user->admin;
            $search = $request->input('search');

            // 1. PRIMARY SOURCE: Query Monthly_Employees table
            $mTable = \App\Http\Controllers\MonthlyEmployeeController::getActualTableName();

            if (\Schema::hasTable($mTable)) {
                $monthlyCount = DB::table($mTable)->count();
                if ($monthlyCount > 0) {
                    $mQuery = DB::table($mTable);
                    if (!empty($search)) {
                        $term = trim($search);
                        $mQuery->where(function ($q) use ($term) {
                            $q->where('emp_id', 'like', "%{$term}%")
                              ->orWhere('employee_name', 'like', "%{$term}%")
                              ->orWhere('designation', 'like', "%{$term}%")
                              ->orWhere('location', 'like', "%{$term}%");
                        })->limit(200);
                    } else {
                        // Allow full roster retrieval up to 10,000 records
                        $limit = $request->input('limit', 10000);
                        $mQuery->limit($limit);
                    }
                    
                    $mRecords = $mQuery->orderBy('sr_no', 'asc')->get();

                    if ($mRecords->isNotEmpty()) {
                        // Match with users table in 1 single bulk query
                        $empIds = $mRecords->pluck('emp_id')->filter()->toArray();
                        $usersMap = [];
                        $managerIds = [];
                        if (!empty($empIds) && \Schema::hasColumn('users', 'employee_code')) {
                            $users = \App\Models\User::whereIn('employee_code', $empIds)->get();
                            foreach ($users as $u) {
                                $usersMap[$u->employee_code] = $u;
                                if (!empty($u->line_manager_id)) {
                                    $managerIds[] = $u->line_manager_id;
                                }
                            }
                        }

                        // Also collect any manager IDs directly on Monthly_Employees
                        if (\Schema::hasColumn($mTable, 'line_manager_id')) {
                            foreach ($mRecords as $mr) {
                                if (!empty($mr->line_manager_id)) {
                                    $managerIds[] = $mr->line_manager_id;
                                }
                            }
                        }

                        // Preload all managers in 1 query (eliminates N+1 query timeout!)
                        $managersMap = [];
                        if (!empty($managerIds)) {
                            $managersMap = \App\Models\User::whereIn('id', array_unique($managerIds))->get()->keyBy('id');
                        }

                        $employees = $mRecords->map(function ($emp) use ($usersMap, $managersMap) {
                            $user = $usersMap[$emp->emp_id] ?? null;
                            $managerName = null;
                            $managerDept = null;
                            $lineMgrId = $user ? $user->line_manager_id : ($emp->line_manager_id ?? null);
                            if (!empty($lineMgrId) && isset($managersMap[$lineMgrId])) {
                                $mgr = $managersMap[$lineMgrId];
                                $managerName = $mgr->name;
                                $managerDept = $mgr->department;
                            }

                            $loc = $emp->location ?: 'N/A';
                            $desig = $emp->designation ?: 'N/A';
                            $dept = self::inferDepartment($desig, $managerDept, $user ? $user->department : null, null, $loc);

                            return [
                                'id' => $emp->id,
                                'user_id' => $user ? $user->id : null,
                                'employee_code' => $emp->emp_id,
                                'name' => $emp->employee_name,
                                'firstname' => explode(' ', $emp->employee_name)[0] ?? '',
                                'surname' => substr(strstr($emp->employee_name, ' '), 1) ?: '',
                                'email' => $user ? $user->email : null,
                                'location' => $loc,
                                'department' => $dept,
                                'position' => $desig,
                                'designation' => $desig,
                                'sex' => $emp->sex,
                                'category' => $emp->category,
                                'line_manager_id' => $lineMgrId,
                                'line_manager_name' => $managerName,
                                'full_string' => $emp->emp_id . ' - ' . $emp->employee_name . ' (' . $loc . ')',
                                'source' => 'monthly_employees'
                            ];
                        });

                        return response()->json([
                            'status' => 'success',
                            'data' => $employees
                        ]);
                    }
                }
            }

            // 2. FALLBACK to legacy query if Monthly_Employees is empty
            $hasJoiningDept = \Schema::hasColumn('employees', 'joiningdepartment');
            $hasLocation = \Schema::hasColumn('employees', 'location');
            $hasJoiningLocation = \Schema::hasColumn('employees', 'joininglocation');
            $hasJobTitle = \Schema::hasColumn('employees', 'job_title');
            $hasLineManager = \Schema::hasColumn('employees', 'line_manager_id');
            $hasEmpDept = \Schema::hasColumn('employees', 'department');
            $hasEmpEmail = \Schema::hasColumn('employees', 'email');
            $hasUserLocation = \Schema::hasColumn('users', 'location');
            $hasUserDept = \Schema::hasColumn('users', 'department');
            $hasUserEmpCode = \Schema::hasColumn('users', 'employee_code');

            $selectColumns = [
                'employees.id as emp_id',
                'employees.employeeid',
                'employees.firstname',
                'employees.surname',
                'employees.joiningposition',
                'employees.joining_dept_id',
                'employees.joining_branch_id',
            ];

            if ($hasEmpDept) $selectColumns[] = 'employees.department as emp_dept';
            if ($hasJobTitle) $selectColumns[] = 'employees.job_title as emp_position';
            if ($hasLineManager) $selectColumns[] = 'employees.line_manager_id';
            if ($hasEmpEmail) $selectColumns[] = 'employees.email as emp_email';
            if ($hasJoiningDept) $selectColumns[] = 'employees.joiningdepartment';
            if ($hasLocation) $selectColumns[] = 'employees.location as emp_location';
            else if ($hasJoiningLocation) $selectColumns[] = 'employees.joininglocation as emp_location';

            if ($hasUserEmpCode) {
                $selectColumns[] = 'users.id as user_id';
                if ($hasUserDept) $selectColumns[] = 'users.department';
                if ($hasUserLocation) $selectColumns[] = 'users.location';
                $selectColumns[] = 'users.email as user_email';

                $query = \App\Models\Employee::leftJoin('users', 'users.employee_code', '=', 'employees.employeeid')
                    ->select($selectColumns);
            } else {
                $query = \App\Models\Employee::select($selectColumns);
            }

            // Non-admin users only see their assigned team members if column exists
            if (!$isAdmin && $user && $hasLineManager) {
                $query->where('employees.line_manager_id', $user->id);
            }

            $employees = $query->orderBy('employees.firstname')->get();

            $employees = $employees->map(function ($emp) use ($hasJoiningDept, $hasLineManager) {
                $managerName = null;
                if ($hasLineManager && !empty($emp->line_manager_id)) {
                    $mgr = User::find($emp->line_manager_id);
                    $managerName = $mgr ? $mgr->name : 'Unknown';
                }

                $dept = $emp->department ?? null;
                if (empty($dept) || $dept === 'N/A') {
                    $dept = $emp->emp_dept ?: ($emp->joiningdepartment ?? null);
                }
                if (empty($dept) || $dept === 'N/A') {
                    $dept = self::$deptLookup[$emp->joining_dept_id] ?? 'N/A';
                }

                $loc = $emp->location ?? null;
                if (empty($loc) || $loc === 'N/A') {
                    $loc = $emp->emp_location ?? null;
                }
                if (empty($loc) || $loc === 'N/A') {
                    $loc = self::$branchLookup[$emp->joining_branch_id] ?? 'N/A';
                }

                $position = $emp->emp_position ?? null;
                if (empty($position) || $position === 'N/A') {
                    $position = $emp->joiningposition ?? 'N/A';
                }

                return [
                    'id' => $emp->emp_id,
                    'user_id' => $emp->user_id ?? null,
                    'employee_code' => $emp->employeeid,
                    'name' => trim($emp->firstname . ' ' . $emp->surname),
                    'firstname' => $emp->firstname,
                    'surname' => $emp->surname,
                    'email' => $emp->user_email ?? ($emp->emp_email ?? null),
                    'location' => $loc,
                    'department' => $dept,
                    'position' => $position,
                    'joining_dept_id' => $emp->joining_dept_id,
                    'joining_branch_id' => $emp->joining_branch_id,
                    'line_manager_id' => $emp->line_manager_id ?? null,
                    'line_manager_name' => $managerName,
                    'full_string' => $emp->employeeid . ' - ' . trim($emp->firstname . ' ' . $emp->surname)
                ];
            });

            // --- Central Database Fallback Logic ---
            try {
                $localEmployeeIds = $employees->pluck('employee_code')->filter()->toArray();
                
                $centralQuery = \App\Models\CentralEmployee::query();
                if (!empty($localEmployeeIds)) {
                    $centralQuery->whereNotIn('employeeid', $localEmployeeIds);
                }

                if ($isAdmin || !$user) {
                    $centralEmployeesData = $centralQuery->get()->map(function ($emp) {
                        $dept = self::$deptLookup[$emp->joining_dept_id ?? 0] ?? $emp->department ?? $emp->joiningdepartment ?? 'N/A';
                        $loc = self::$branchLookup[$emp->joining_branch_id ?? 0] ?? $emp->location ?? $emp->joininglocation ?? 'N/A';
                        $position = $emp->job_title ?? $emp->joiningposition ?? 'N/A';

                        return [
                            'id' => $emp->id,
                            'user_id' => null,
                            'employee_code' => $emp->employeeid,
                            'name' => trim(($emp->firstname ?? '') . ' ' . ($emp->surname ?? '')),
                            'firstname' => $emp->firstname ?? '',
                            'surname' => $emp->surname ?? '',
                            'email' => $emp->email ?? null,
                            'location' => $loc,
                            'department' => $dept,
                            'position' => $position,
                            'joining_dept_id' => $emp->joining_dept_id,
                            'joining_branch_id' => $emp->joining_branch_id,
                            'line_manager_id' => null,
                            'line_manager_name' => null,
                            'full_string' => $emp->employeeid . ' - ' . trim(($emp->firstname ?? '') . ' ' . ($emp->surname ?? '')),
                            'is_central' => true
                        ];
                    });

                    $employees = $employees->concat($centralEmployeesData)->values();
                }
            } catch (\Throwable $e) {
                \Log::warning('Central DB Fallback Notice: ' . $e->getMessage());
            }

            return response()->json([
                'status' => 'success',
                'data' => $employees
            ]);
        } catch (\Throwable $e) {
            \Log::error('EmployeeMasterController@getEmployees error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load employees: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Sync team members for a manager.
     * Automatically creates/updates user accounts for employees (Username: Emp ID, Password: Password)
     * and automatically generates and assigns their FY 2026 Goals & Appraisal dossiers.
     */
    public function syncTeam(Request $request)
    {
        try {
            $this->ensureSchema();

            $managerId = $request->input('manager_id');
            $employees = $request->input('employees', []);
            $year = $request->input('year', 2026);

            if (empty($managerId)) {
                return response()->json(['status' => 'error', 'message' => 'Manager ID is required'], 400);
            }

            $manager = User::find($managerId);
            if (!$manager) {
                return response()->json(['status' => 'error', 'message' => 'Line Manager not found'], 404);
            }

            // Ensure manager is flagged as is_manager
            if (!$manager->is_manager) {
                try {
                    $manager->update(['is_manager' => 1]);
                } catch (\Throwable $e) {}
            }

            // Extract all codes from the payload (handles both objects and strings)
            $selectedCodes = collect($employees)->map(function ($item) {
                if (is_array($item)) {
                    return $item['employee_code'] ?? ($item['emp_id'] ?? null);
                }
                return $item;
            })->filter()->unique()->values()->toArray();

            // Prevent self-assignment (manager cannot report to themselves)
            $managerCodes = array_filter([$manager->employee_code, $manager->username]);
            $selectedCodes = array_values(array_filter($selectedCodes, function ($code) use ($managerCodes) {
                return !in_array($code, $managerCodes);
            }));

            $mTable = \App\Http\Controllers\MonthlyEmployeeController::getActualTableName();
            $hasMonthlyTable = Schema::hasTable($mTable);
            $hasMonthlyLineManager = $hasMonthlyTable && Schema::hasColumn($mTable, 'line_manager_id');
            $hasEmpLineManager = Schema::hasTable('employees') && Schema::hasColumn('employees', 'line_manager_id');
            $hasUserLineManager = Schema::hasTable('users') && Schema::hasColumn('users', 'line_manager_id');

            // 1. UNASSIGN existing members who are no longer in this manager's team
            if ($hasUserLineManager) {
                User::where('line_manager_id', $managerId)
                    ->whereNotIn('employee_code', $selectedCodes)
                    ->whereNotIn('username', $selectedCodes)
                    ->update(['line_manager_id' => null, 'report_to' => null]);
            }

            if ($hasEmpLineManager) {
                DB::table('employees')
                    ->where('line_manager_id', $managerId)
                    ->whereNotIn('employeeid', $selectedCodes)
                    ->whereNotIn('emp_code', $selectedCodes)
                    ->update(['line_manager_id' => null]);
            }

            if ($hasMonthlyLineManager) {
                DB::table($mTable)
                    ->where('line_manager_id', $managerId)
                    ->whereNotIn('emp_id', $selectedCodes)
                    ->update(['line_manager_id' => null]);
            }

            if (empty($selectedCodes)) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Team roster cleared successfully for ' . $manager->name,
                    'synced_count' => 0
                ]);
            }

            // 2. Resolve Manager's Appraisal Template
            $competencies = self::getDefaultCompetencies();
            if (!empty($manager->appraisal_template)) {
                $saved = is_array($manager->appraisal_template) ? $manager->appraisal_template : json_decode($manager->appraisal_template, true);
                if (!empty($saved) && is_array($saved)) {
                    $competencies = $saved;
                }
            }

            // 3. Preload all reference data in bulk for blazing fast execution (NO N+1)
            $monthlyMap = [];
            if ($hasMonthlyTable) {
                $monthlyRows = DB::table($mTable)->whereIn('emp_id', $selectedCodes)->get();
                foreach ($monthlyRows as $mr) {
                    $monthlyMap[$mr->emp_id] = $mr;
                }
            }

            $legacyEmpMap = [];
            if (Schema::hasTable('employees')) {
                $legacyRows = DB::table('employees')
                    ->whereIn('employeeid', $selectedCodes)
                    ->orWhereIn('emp_code', $selectedCodes)
                    ->get();
                foreach ($legacyRows as $lr) {
                    if (!empty($lr->employeeid)) $legacyEmpMap[$lr->employeeid] = $lr;
                    if (!empty($lr->emp_code)) $legacyEmpMap[$lr->emp_code] = $lr;
                }
            }

            $usersMap = [];
            $existingUsers = User::whereIn('employee_code', $selectedCodes)
                ->orWhereIn('username', $selectedCodes)
                ->get();
            foreach ($existingUsers as $eu) {
                if (!empty($eu->employee_code)) $usersMap[$eu->employee_code] = $eu;
                if (!empty($eu->username)) $usersMap[$eu->username] = $eu;
            }

            $goalsMap = [];
            if (Schema::hasTable('goals')) {
                $existingGoals = Goal::where('year', $year)
                    ->whereIn('employee_code', $selectedCodes)
                    ->whereIn('status', ['assigned', 'draft', 'in_progress'])
                    ->get();
                foreach ($existingGoals as $eg) {
                    $goalsMap[$eg->employee_code] = $eg;
                }
            }

            // Convert request employees array to map if passed as objects
            $payloadEmpMap = [];
            foreach ($employees as $empObj) {
                if (is_array($empObj) && !empty($empObj['employee_code'])) {
                    $payloadEmpMap[$empObj['employee_code']] = $empObj;
                }
            }

            $syncedCount = 0;
            $accountsProvisioned = 0;
            $goalsAssigned = 0;

            $userCols = Schema::getColumnListing('users');
            $hasEmpCodeCol = in_array('employee_code', $userCols);
            $hasReportToCol = in_array('report_to', $userCols);
            $hasPermsCol = in_array('permissions', $userCols);
            $hasDeptCol = in_array('department', $userCols);
            $hasLocCol = in_array('location', $userCols);
            $hasDesigCol = in_array('designation', $userCols);
            $hasRoleCol = in_array('role', $userCols);
            $hasPosCol = in_array('position_id', $userCols);

            // 4. Process each employee
            foreach ($selectedCodes as $code) {
                $mEmp = $monthlyMap[$code] ?? null;
                $legEmp = $legacyEmpMap[$code] ?? null;
                $pEmp = $payloadEmpMap[$code] ?? null;

                // Resolve name
                $candName = null;
                if ($mEmp && !empty($mEmp->employee_name)) {
                    $candName = $mEmp->employee_name;
                } elseif ($legEmp) {
                    $candName = trim(($legEmp->firstname ?? '') . ' ' . ($legEmp->surname ?? ''));
                } elseif ($pEmp && !empty($pEmp['name'])) {
                    $candName = $pEmp['name'];
                } else {
                    $candName = 'Employee ' . $code;
                }

                // Resolve location
                $loc = $mEmp->location ?? ($legEmp->joininglocation ?? ($pEmp['location'] ?? 'N/A'));

                // Resolve designation
                $designation = $mEmp->designation ?? ($legEmp->job_title ?? ($pEmp['position'] ?? ($pEmp['designation'] ?? 'Employee')));

                // Resolve department intelligently
                $dept = self::inferDepartment($designation, $manager->department, $empUser ? $empUser->department : null, $legEmp->department ?? null, $loc);

                // Clean code & email
                $cleanCode = preg_replace('/[^a-zA-Z0-9]/', '', $code);
                $fallbackEmail = strtolower($cleanCode) . '@melcomgroup.com';
                $email = (!empty($legEmp->email) && filter_var($legEmp->email, FILTER_VALIDATE_EMAIL))
                    ? $legEmp->email
                    : $fallbackEmail;

                // 4a. User Provisioning / Updating
                $empUser = $usersMap[$code] ?? null;

                if (!$empUser) {
                    // Create new user account with default credentials: Username = Emp ID, Password = Password
                    if (User::where('email', $email)->exists()) {
                        $email = strtolower($cleanCode) . '_' . substr(md5(uniqid()), 0, 5) . '@melcomgroup.com';
                    }

                    $newUserData = [
                        'name' => $candName,
                        'username' => $code,
                        'email' => $email,
                        'password' => Hash::make('Password'),
                        'is_manager' => 0,
                        'admin' => 0,
                        'user_id' => $manager->id,
                    ];
                    if ($hasEmpCodeCol) $newUserData['employee_code'] = $code;
                    if ($hasUserLineManager) $newUserData['line_manager_id'] = $manager->id;
                    if ($hasReportToCol) $newUserData['report_to'] = $manager->name ?: $manager->username;
                    if ($hasDeptCol) $newUserData['department'] = $dept;
                    if ($hasLocCol) $newUserData['location'] = $loc;
                    if ($hasDesigCol) $newUserData['designation'] = $designation;
                    if ($hasPosCol) $newUserData['position_id'] = 5;
                    if ($hasRoleCol) $newUserData['role'] = 'Basic';
                    if ($hasPermsCol) $newUserData['permissions'] = ['/pms/goals', '/pms/appraisal'];

                    try {
                        $empUser = User::create($newUserData);
                        $usersMap[$code] = $empUser;
                        $accountsProvisioned++;
                    } catch (\Throwable $cuEx) {
                        Log::error("User creation failed for {$code}: " . $cuEx->getMessage());
                    }
                } else {
                    // Existing User: Update reporting line and ensure credentials
                    $updateData = [
                        'password' => Hash::make('Password'), // Default password reset to Password
                    ];
                    if ($hasEmpCodeCol) $updateData['employee_code'] = $code;
                    if (empty($empUser->username) || is_numeric($empUser->username)) {
                        $updateData['username'] = $code;
                    }
                    if ($hasUserLineManager) $updateData['line_manager_id'] = $manager->id;
                    if ($hasReportToCol) $updateData['report_to'] = $manager->name ?: $manager->username;

                    // Hierarchy preservation: Do NOT demote someone who is already a manager or admin!
                    $isManagerOrAdmin = $empUser->admin || $empUser->is_manager || in_array($empUser->position_id, [2, 3, 4]);
                    if (!$isManagerOrAdmin) {
                        if ($hasDeptCol && empty($empUser->department)) $updateData['department'] = $dept;
                        if ($hasLocCol && empty($empUser->location)) $updateData['location'] = $loc;
                        if ($hasDesigCol && empty($empUser->designation)) $updateData['designation'] = $designation;
                        if ($hasPosCol && empty($empUser->position_id)) $updateData['position_id'] = 5;
                        if ($hasPermsCol && empty($empUser->permissions)) $updateData['permissions'] = ['/pms/goals', '/pms/appraisal'];
                        if ($hasRoleCol && empty($empUser->role)) $updateData['role'] = 'Basic';
                    }

                    try {
                        $empUser->update($updateData);
                    } catch (\Throwable $uuEx) {
                        Log::warning("User update notice for {$code}: " . $uuEx->getMessage());
                    }
                }

                // 4b. Sync reporting line in Monthly_Employees & employees tables
                if ($hasMonthlyLineManager) {
                    try {
                        DB::table($mTable)->where('emp_id', $code)->update(['line_manager_id' => $manager->id]);
                    } catch (\Throwable $e) {}
                }

                if ($hasEmpLineManager) {
                    try {
                        DB::table('employees')
                            ->where('employeeid', $code)
                            ->orWhere('emp_code', $code)
                            ->update(['line_manager_id' => $manager->id]);
                    } catch (\Throwable $e) {}
                }

                // 4c. Automatic Goal & Appraisal Dossier Creation for FY 2026
                if (Schema::hasTable('goals') && $empUser) {
                    try {
                        $empAppraisalData = [
                            'competencies' => $competencies,
                            'comments' => '',
                            'impressedMost' => '',
                            'impressedLeast' => '',
                            'performanceRating' => 0,
                            'rating_comments' => ['1' => '', '2' => '', '3' => '', '4' => '', '5' => ''],
                            'candidate_signature_name' => $candName,
                            'manager_signature_name' => $manager->name,
                            'signature_date' => date('Y-m-d')
                        ];

                        $blankSmartCriteria = [
                            'specific' => false,
                            'measurable' => false,
                            'attainable' => false,
                            'relevant' => false,
                            'time_bound' => false
                        ];

                        $existingGoal = $goalsMap[$code] ?? null;
                        if ($existingGoal) {
                            $updateGoal = [
                                'created_by' => $manager->id,
                                'manager_name' => $manager->name,
                                'location' => $loc,
                                'department' => $dept,
                                'job_title' => $designation,
                            ];
                            // If currently in assigned status, ensure clean blank inputs for employee
                            if ($existingGoal->status === 'assigned') {
                                $descStr = is_array($existingGoal->description) ? ($existingGoal->description[0] ?? '') : (string)$existingGoal->description;
                                if (empty($descStr) || str_contains($descStr, 'Achieve operational excellence')) {
                                    $updateGoal['description'] = [''];
                                    $updateGoal['purposes'] = [''];
                                    $updateGoal['challenges'] = [''];
                                    $updateGoal['smart_criteria'] = $blankSmartCriteria;
                                }
                            }
                            if (empty($existingGoal->appraisal_data)) {
                                $updateGoal['appraisal_data'] = $empAppraisalData;
                            }
                            $existingGoal->update($updateGoal);
                            $goalsAssigned++;
                        } else {
                            Goal::create([
                                'title' => 'Yearly SMART Goals FY ' . $year,
                                'description' => [''],
                                'purposes' => [''],
                                'challenges' => [''],
                                'category' => 'Operational',
                                'target' => 100,
                                'due_date' => $year . '-12-31',
                                'year' => $year,
                                'user_id' => $empUser->id,
                                'created_by' => $manager->id,
                                'candidate_name' => $candName,
                                'employee_code' => $code,
                                'location' => $loc,
                                'department' => $dept,
                                'job_title' => $designation,
                                'manager_name' => $manager->name,
                                'smart_criteria' => $blankSmartCriteria,
                                'appraisal_data' => $empAppraisalData,
                                'status' => 'assigned',
                            ]);
                            $goalsAssigned++;
                        }
                    } catch (\Throwable $gEx) {
                        Log::error("Goal auto-assignment failed for {$code}: " . $gEx->getMessage());
                    }
                }

                $syncedCount++;
            }

            return response()->json([
                'status' => 'success',
                'message' => "Successfully synchronized {$syncedCount} team member(s) to {$manager->name}. User accounts provisioned (Username: Emp ID, Password: Password) and FY {$year} Goals & Appraisals automatically assigned.",
                'synced_count' => $syncedCount,
                'accounts_provisioned' => $accountsProvisioned,
                'goals_assigned' => $goalsAssigned,
                'manager_name' => $manager->name,
            ]);

        } catch (\Throwable $e) {
            Log::error("EmployeeMasterController@syncTeam fatal error: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to synchronize team: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Parse a CSV of employee codes and return matched employee records.
     * Matches primarily against Monthly_Employees (all 5,920 employees), fallback to legacy employees.
     */
    public function parseTeamCsv(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:4096',
        ]);

        $file = $request->file('file');
        $content = file_get_contents($file->getRealPath());
        $lines = array_filter(array_map('trim', explode("\n", $content)));

        if (empty($lines)) {
            return response()->json(['status' => 'error', 'message' => 'CSV file is empty'], 400);
        }

        // Detect header row
        $header = str_getcsv(array_shift($lines));
        $header = array_map(function ($h) {
            return strtolower(trim(str_replace([' ', '_', '-'], ['_', '_', '_'], $h)));
        }, $header);

        // Find the employee_code column index
        $codeIndex = null;
        $possibleNames = ['employee_code', 'employeecode', 'emp_code', 'empcode', 'code', 'employeeid', 'employee_id', 'emp_id', 'empid', 'id', 'sr_no'];
        foreach ($possibleNames as $name) {
            $idx = array_search($name, $header);
            if ($idx !== false) {
                $codeIndex = $idx;
                break;
            }
        }

        // If no matching header, assume single-column CSV (just codes)
        $codes = [];
        if ($codeIndex === null) {
            $allLines = array_merge([$header[0] ?? ''], $lines);
            foreach ($allLines as $line) {
                $val = trim(str_getcsv($line)[0] ?? '');
                if (!empty($val)) $codes[] = $val;
            }
        } else {
            foreach ($lines as $line) {
                $cols = str_getcsv($line);
                $val = trim($cols[$codeIndex] ?? '');
                if (!empty($val)) $codes[] = $val;
            }
        }

        $codes = array_values(array_unique(array_filter($codes)));

        $this->ensureSchema();
        $mTable = \App\Http\Controllers\MonthlyEmployeeController::getActualTableName();

        // 1. PRIMARY MATCH: Monthly_Employees table (contains all 5,920 current employees)
        $matchedMonthly = collect();
        if (\Schema::hasTable($mTable)) {
            $matchedMonthly = DB::table($mTable)
                ->whereIn('emp_id', $codes)
                ->get();
        }

        $matchedMonthlyCodes = $matchedMonthly->pluck('emp_id')->filter()->unique()->toArray();
        $remainingCodes = array_values(array_diff($codes, $matchedMonthlyCodes));

        // 2. SECONDARY MATCH: legacy employees table
        $matchedLegacy = collect();
        if (!empty($remainingCodes) && \Schema::hasTable('employees')) {
            $matchedLegacy = DB::table('employees')
                ->whereIn('employeeid', $remainingCodes)
                ->orWhereIn('emp_code', $remainingCodes)
                ->get();
        }

        $matchedLegacyCodes = $matchedLegacy->pluck('employeeid')->merge($matchedLegacy->pluck('emp_code'))->filter()->unique()->toArray();
        $allMatchedCodes = array_unique(array_merge($matchedMonthlyCodes, $matchedLegacyCodes));
        $unmatched = array_values(array_diff($codes, $allMatchedCodes));

        // Preload users to attach user_id and line_manager_id
        $usersMap = [];
        if (!empty($allMatchedCodes) && \Schema::hasColumn('users', 'employee_code')) {
            $users = User::whereIn('employee_code', $allMatchedCodes)->get();
            foreach ($users as $u) {
                $usersMap[$u->employee_code] = $u;
            }
        }

        $employees = collect();

        // Map Monthly_Employees
        foreach ($matchedMonthly as $emp) {
            $user = $usersMap[$emp->emp_id] ?? null;
            $mgrId = $user ? $user->line_manager_id : ($emp->line_manager_id ?? null);
            $loc = $emp->location ?: 'N/A';
            $desig = $emp->designation ?: 'N/A';
            $dept = self::inferDepartment($desig, null, $user ? $user->department : null, null, $loc);

            $employees->push([
                'id' => $emp->id,
                'user_id' => $user ? $user->id : null,
                'employee_code' => $emp->emp_id,
                'name' => $emp->employee_name,
                'location' => $loc,
                'department' => $dept,
                'position' => $desig,
                'designation' => $desig,
                'line_manager_id' => $mgrId,
                'full_string' => $emp->emp_id . ' - ' . $emp->employee_name . ' (' . $loc . ')',
                'source' => 'monthly_employees'
            ]);
        }

        // Map legacy employees (that weren't already added from Monthly_Employees)
        foreach ($matchedLegacy as $emp) {
            $code = $emp->employeeid ?: $emp->emp_code;
            if ($matchedMonthlyCodes && in_array($code, $matchedMonthlyCodes)) {
                continue;
            }
            $user = $usersMap[$code] ?? null;

            $dept = $user->department ?? $emp->department ?? null;
            if (empty($dept) || $dept === 'N/A') {
                $dept = self::$deptLookup[$emp->joining_dept_id ?? 0] ?? 'N/A';
            }
            $loc = $user->location ?? $emp->joininglocation ?? null;
            if (empty($loc) || $loc === 'N/A') {
                $loc = self::$branchLookup[$emp->joining_branch_id ?? 0] ?? 'N/A';
            }
            $position = $emp->job_title ?? $emp->joiningposition ?? 'Employee';
            $name = trim(($emp->firstname ?? '') . ' ' . ($emp->surname ?? ''));

            $employees->push([
                'id' => $emp->id,
                'user_id' => $user ? $user->id : null,
                'employee_code' => $code,
                'name' => $name,
                'location' => $loc,
                'department' => $dept,
                'position' => $position,
                'designation' => $position,
                'line_manager_id' => $user ? $user->line_manager_id : $emp->line_manager_id,
                'full_string' => $code . ' - ' . $name . ' (' . $loc . ')',
                'source' => 'employees'
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => $employees->values(),
            'matched_count' => $employees->count(),
            'unmatched_codes' => $unmatched,
            'total_parsed' => count($codes),
        ]);
    }

    /**
     * Push fresh blank Goals & Appraisal templates to all team members under a Line Manager.
     * Preserves any existing completed reviews while setting up fresh blank templates for the new period.
     */
    public function pushFreshGoals(Request $request)
    {
        try {
            $this->ensureSchema();
            $managerId = $request->input('manager_id');
            $year = $request->input('year', 2026);

            if (empty($managerId)) {
                $authUser = $request->user();
                if ($authUser) {
                    $managerId = $authUser->id;
                } else {
                    return response()->json(['status' => 'error', 'message' => 'Manager ID is required'], 400);
                }
            }

            $manager = User::find($managerId);
            if (!$manager) {
                return response()->json(['status' => 'error', 'message' => 'Line Manager not found'], 404);
            }

            // Find all assigned team members
            $assignedUsers = User::where('line_manager_id', $managerId)->get();
            $assignedCodes = $assignedUsers->pluck('employee_code')->filter()->unique()->toArray();

            // Also check Monthly_Employees for members assigned to this manager
            $mTable = \App\Http\Controllers\MonthlyEmployeeController::getActualTableName();
            if (Schema::hasTable($mTable) && Schema::hasColumn($mTable, 'line_manager_id')) {
                $monthlyCodes = DB::table($mTable)->where('line_manager_id', $managerId)->pluck('emp_id')->filter()->unique()->toArray();
                $assignedCodes = array_values(array_unique(array_merge($assignedCodes, $monthlyCodes)));
            }

            if (empty($assignedCodes)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No team members are currently assigned to ' . $manager->name . '.'
                ], 400);
            }

            // Load manager custom competencies or standard defaults
            $competencies = null;
            if (!empty($manager->appraisal_template)) {
                $saved = is_array($manager->appraisal_template) ? $manager->appraisal_template : json_decode($manager->appraisal_template, true);
                if (!empty($saved) && is_array($saved)) {
                    $competencies = $saved;
                }
            }
            if (!$competencies) {
                $competencies = self::getDefaultCompetencies();
            }

            // Map employees
            $monthlyMap = [];
            if (Schema::hasTable($mTable)) {
                $monthlyMap = DB::table($mTable)->whereIn('emp_id', $assignedCodes)->get()->keyBy('emp_id');
            }
            $userMap = User::whereIn('employee_code', $assignedCodes)->get()->keyBy('employee_code');

            $pushedCount = 0;

            foreach ($assignedCodes as $code) {
                $mEmp = $monthlyMap[$code] ?? null;
                $empUser = $userMap[$code] ?? null;

                $candName = $mEmp ? $mEmp->employee_name : ($empUser ? $empUser->name : 'Employee ' . $code);
                $loc = $mEmp ? $mEmp->location : ($empUser ? $empUser->location : 'N/A');
                $designation = $mEmp ? $mEmp->designation : ($empUser ? $empUser->designation : 'Employee');
                $dept = self::inferDepartment($designation, $manager->department, $empUser ? $empUser->department : null, null, $loc);

                $empAppraisalData = [
                    'competencies' => $competencies,
                    'comments' => '',
                    'impressedMost' => '',
                    'impressedLeast' => '',
                    'performanceRating' => 0,
                    'rating_comments' => ['1' => '', '2' => '', '3' => '', '4' => '', '5' => ''],
                    'candidate_signature_name' => $candName,
                    'manager_signature_name' => $manager->name,
                    'signature_date' => date('Y-m-d')
                ];

                $blankSmartCriteria = [
                    'specific' => false,
                    'measurable' => false,
                    'attainable' => false,
                    'relevant' => false,
                    'time_bound' => false
                ];

                // Check existing goal
                $existingGoal = Goal::where(function($q) use ($code, $empUser) {
                    $q->where('employee_code', $code);
                    if ($empUser) {
                        $q->orWhere('user_id', $empUser->id);
                    }
                })->where('year', $year)->orderBy('id', 'desc')->first();

                if ($existingGoal) {
                    // If existing goal is already completed or submitted, create a fresh new one so history is preserved
                    if (in_array($existingGoal->status, ['submitted', 'in_progress', 'appraisal_completed', 'review_completed', 'completed'])) {
                        Goal::create([
                            'title' => 'Yearly SMART Goals FY ' . $year,
                            'description' => [''],
                            'purposes' => [''],
                            'challenges' => [''],
                            'category' => 'Operational',
                            'target' => 100,
                            'due_date' => $year . '-12-31',
                            'year' => $year,
                            'user_id' => $empUser ? $empUser->id : null,
                            'created_by' => $manager->id,
                            'candidate_name' => $candName,
                            'employee_code' => $code,
                            'location' => $loc,
                            'department' => $dept,
                            'job_title' => $designation,
                            'manager_name' => $manager->name,
                            'smart_criteria' => $blankSmartCriteria,
                            'appraisal_data' => $empAppraisalData,
                            'status' => 'assigned',
                        ]);
                        $pushedCount++;
                    } else {
                        // Reset existing assigned/draft goal to fresh blank state
                        $existingGoal->update([
                            'title' => 'Yearly SMART Goals FY ' . $year,
                            'description' => [''],
                            'purposes' => [''],
                            'challenges' => [''],
                            'smart_criteria' => $blankSmartCriteria,
                            'appraisal_data' => $empAppraisalData,
                            'status' => 'assigned',
                            'created_by' => $manager->id,
                            'manager_name' => $manager->name,
                            'department' => $dept,
                            'location' => $loc,
                            'job_title' => $designation,
                        ]);
                        $pushedCount++;
                    }
                } else {
                    Goal::create([
                        'title' => 'Yearly SMART Goals FY ' . $year,
                        'description' => [''],
                        'purposes' => [''],
                        'challenges' => [''],
                        'category' => 'Operational',
                        'target' => 100,
                        'due_date' => $year . '-12-31',
                        'year' => $year,
                        'user_id' => $empUser ? $empUser->id : null,
                        'created_by' => $manager->id,
                        'candidate_name' => $candName,
                        'employee_code' => $code,
                        'location' => $loc,
                        'department' => $dept,
                        'job_title' => $designation,
                        'manager_name' => $manager->name,
                        'smart_criteria' => $blankSmartCriteria,
                        'appraisal_data' => $empAppraisalData,
                        'status' => 'assigned',
                    ]);
                    $pushedCount++;
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => "Successfully pushed fresh blank Goals & Appraisal templates to {$pushedCount} team member(s) under {$manager->name}.",
                'pushed_count' => $pushedCount,
            ]);

        } catch (\Throwable $e) {
            Log::error("EmployeeMasterController@pushFreshGoals error: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to push fresh goals: ' . $e->getMessage()], 500);
        }
    }
}

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

    public function getEmployees(Request $request)
    {
        try {
            $user = $request->user();
            $isAdmin = $user && $user->admin;

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

    /**
     * Parse a CSV of employee codes and return matched employee records.
     * Accepts a CSV file with an employee_code column (or single-column with codes).
     */
    public function parseTeamCsv(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
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
            return strtolower(trim(str_replace([' ', '_'], ['_', '_'], $h)));
        }, $header);

        // Find the employee_code column index
        $codeIndex = null;
        $possibleNames = ['employee_code', 'employeecode', 'emp_code', 'empcode', 'code', 'employeeid', 'employee_id', 'id'];
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
            // Try treating the header itself as a code (single-column, no header)
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

        $codes = array_unique($codes);

        // Match against employees table
        $matched = \DB::table('employees')
            ->whereIn('employeeid', $codes)
            ->orWhereIn('emp_code', $codes)
            ->get();

        $matchedCodes = $matched->pluck('employeeid')->merge($matched->pluck('emp_code'))->filter()->unique()->toArray();
        $unmatched = array_values(array_diff($codes, $matchedCodes));

        // Build response in the same format as getEmployees
        $employees = $matched->map(function ($emp) {
            $user = User::where('employee_code', $emp->employeeid)->first();

            // Resolve department
            $dept = $user->department ?? $emp->department ?? null;
            if (empty($dept) || $dept === 'N/A') {
                $dept = self::$deptLookup[$emp->joining_dept_id ?? 0] ?? 'N/A';
            }

            // Resolve location from branch
            $loc = $user->location ?? $emp->joininglocation ?? null;
            if (empty($loc) || $loc === 'N/A') {
                $loc = self::$branchLookup[$emp->joining_branch_id ?? 0] ?? 'N/A';
            }

            // Resolve position
            $position = $emp->job_title ?? $emp->joiningposition ?? 'N/A';

            return [
                'id' => $emp->id,
                'user_id' => $user ? $user->id : null,
                'employee_code' => $emp->employeeid,
                'name' => ($emp->firstname ?? '') . ' ' . ($emp->surname ?? ''),
                'location' => $loc,
                'department' => $dept,
                'position' => $position,
                'line_manager_id' => $emp->line_manager_id,
                'full_string' => $emp->employeeid . ' - ' . ($emp->firstname ?? '') . ' ' . ($emp->surname ?? ''),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $employees->values(),
            'matched_count' => $employees->count(),
            'unmatched_codes' => $unmatched,
            'total_parsed' => count($codes),
        ]);
    }
}

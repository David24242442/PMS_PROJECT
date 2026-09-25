<?php

namespace App\Http\Controllers;

use App\Models\MonthlyEmployee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Carbon\Carbon;

class MonthlyEmployeeController extends Controller
{
    /**
     * Dynamically resolve the actual table name across MySQL casing variations.
     */
    public static function getActualTableName(): string
    {
        static $cachedName = null;
        if ($cachedName !== null) {
            return $cachedName;
        }

        try {
            $candidates = ['Monthly_Employees', 'monthly_employees', 'monthly_employee', 'Monthly_Employee'];
            foreach ($candidates as $candidate) {
                if (Schema::hasTable($candidate)) {
                    $cachedName = $candidate;
                    return $cachedName;
                }
            }

            // Direct check via SHOW TABLES
            $tables = DB::select("SHOW TABLES LIKE '%employee%'");
            foreach ($tables as $tblObj) {
                $row = (array)$tblObj;
                $tName = reset($row);
                if (stripos($tName, 'monthly') !== false) {
                    $cachedName = $tName;
                    return $cachedName;
                }
            }
        } catch (\Throwable $e) {
            \Log::warning("getActualTableName notice: " . $e->getMessage());
        }

        $cachedName = 'Monthly_Employees';
        return $cachedName;
    }

    /**
     * Auto-ensure Monthly_Employees table exists on Server 20 / local.
     */
    public static function ensureTableExists()
    {
        try {
            $tableName = self::getActualTableName();

            // Safe key lengths: varchar(190) to prevent MySQL 1071 (max key length 767/1000 bytes)
            DB::statement("CREATE TABLE IF NOT EXISTS `{$tableName}` (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `sr_no` int(10) unsigned DEFAULT NULL,
                `emp_id` varchar(50) NOT NULL,
                `employee_name` varchar(190) NOT NULL,
                `location` varchar(190) DEFAULT NULL,
                `designation` varchar(190) DEFAULT NULL,
                `sex` varchar(20) DEFAULT NULL,
                `category` varchar(100) DEFAULT NULL,
                `month_year` varchar(50) NOT NULL DEFAULT 'AUGUST 2026',
                `status` varchar(50) NOT NULL DEFAULT 'ACTIVE',
                `created_at` timestamp NULL DEFAULT NULL,
                `updated_at` timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_me_emp_id` (`emp_id`),
                KEY `idx_me_name` (`employee_name`),
                KEY `idx_me_location` (`location`),
                KEY `idx_me_category` (`category`),
                KEY `idx_me_month` (`month_year`),
                KEY `idx_me_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        } catch (\Throwable $e) {
            \Log::warning("ensureTableExists notice: " . $e->getMessage());
        }
    }

    /**
     * Display a paginated listing of monthly employees with filters.
     */
    public function index(Request $request)
    {
        try {
            self::ensureTableExists();
            $tableName = self::getActualTableName();

            $search = $request->input('search');
            $location = $request->input('location');
            $category = $request->input('category');
            $monthYear = $request->input('month_year');
            $sortBy = $request->input('sort_by', 'sr_no');
            $sortOrder = strtolower($request->input('sort_order', 'asc')) === 'desc' ? 'desc' : 'asc';
            $perPage = min(max((int)$request->input('per_page', 25), 5), 500);

            $query = DB::table($tableName);

            if (!empty($search)) {
                $term = trim($search);
                $query->where(function ($q) use ($term) {
                    $q->where('emp_id', 'like', "%{$term}%")
                      ->orWhere('employee_name', 'like', "%{$term}%")
                      ->orWhere('designation', 'like', "%{$term}%")
                      ->orWhere('location', 'like', "%{$term}%");
                });
            }

            if (!empty($location) && $location !== 'all') {
                $query->where('location', $location);
            }

            if (!empty($category) && $category !== 'all') {
                $query->where('category', $category);
            }

            if (!empty($monthYear) && $monthYear !== 'all') {
                $query->where('month_year', $monthYear);
            }

            $allowedSorts = ['id', 'sr_no', 'emp_id', 'employee_name', 'location', 'designation', 'sex', 'category', 'month_year', 'created_at'];
            if (in_array($sortBy, $allowedSorts)) {
                $query->orderBy($sortBy, $sortOrder);
            } else {
                $query->orderBy('sr_no', 'asc');
            }

            $employees = $query->paginate($perPage);

            // Attach Onboarding Status for the current page items
            $items = $employees->items();
            $empIds = [];
            foreach ($items as $item) {
                if (!empty($item->emp_id)) {
                    $clean = trim((string)$item->emp_id);
                    $empIds[] = $clean;
                    $noZeros = ltrim($clean, '0');
                    if ($noZeros !== '') $empIds[] = $noZeros;
                }
            }

            $onboardingMap = [];
            if (!empty($empIds)) {
                $onboardingQuery = self::getOnboardingEmployeesQuery();
                if ($onboardingQuery) {
                    $obRows = $onboardingQuery
                        ->where(function($q) use ($empIds) {
                            $q->whereIn('employeeid', $empIds)
                              ->orWhereIn('emp_code', $empIds);
                        })
                        ->select('id', 'employeeid', 'emp_code', 'firstname', 'surname', 'status')
                        ->get();

                    foreach ($obRows as $ob) {
                        if (!empty($ob->employeeid)) {
                            $c = trim((string)$ob->employeeid);
                            $onboardingMap[$c] = $ob;
                            $z = ltrim($c, '0');
                            if ($z !== '') $onboardingMap[$z] = $ob;
                        }
                        if (!empty($ob->emp_code)) {
                            $c = trim((string)$ob->emp_code);
                            $onboardingMap[$c] = $ob;
                            $z = ltrim($c, '0');
                            if ($z !== '') $onboardingMap[$z] = $ob;
                        }
                    }
                }
            }

            foreach ($items as $item) {
                $pId = trim((string)$item->emp_id);
                $pIdNoZero = ltrim($pId, '0');
                $matched = $onboardingMap[$pId] ?? ($onboardingMap[$pIdNoZero] ?? null);
                if ($matched) {
                    $item->onboarding_matched = true;
                    $item->onboarding_id = $matched->id;
                    $item->onboarding_name = trim(($matched->firstname ?? '') . ' ' . ($matched->surname ?? ''));
                    $item->onboarding_status = self::mapOnboardingStatus($matched->status, true);
                } else {
                    $item->onboarding_matched = false;
                    $item->onboarding_id = null;
                    $item->onboarding_name = null;
                    $item->onboarding_status = self::mapOnboardingStatus(null, false);
                }
            }

            // Distinct filter options for UI dropdowns
            $locations = DB::table($tableName)
                ->whereNotNull('location')
                ->where('location', '!=', '')
                ->distinct()
                ->orderBy('location', 'asc')
                ->pluck('location');

            $categories = DB::table($tableName)
                ->whereNotNull('category')
                ->where('category', '!=', '')
                ->distinct()
                ->orderBy('category', 'asc')
                ->pluck('category');

            $months = DB::table($tableName)
                ->whereNotNull('month_year')
                ->where('month_year', '!=', '')
                ->distinct()
                ->orderBy('month_year', 'desc')
                ->pluck('month_year');

            return response()->json([
                'status' => 'success',
                'data' => $employees,
                'filters' => [
                    'locations' => $locations,
                    'categories' => $categories,
                    'months' => $months,
                ]
            ]);
        } catch (\Throwable $e) {
            \Log::error('MonthlyEmployeeController@index error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load employee records: ' . $e->getMessage(),
                'data' => [
                    'data' => [],
                    'total' => 0,
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 25
                ],
                'filters' => [
                    'locations' => [],
                    'categories' => [],
                    'months' => []
                ]
            ], 200);
        }
    }

    /**
     * Statistical overview of monthly employees.
     */
    public function stats(Request $request)
    {
        try {
            self::ensureTableExists();
            $tableName = self::getActualTableName();

            $monthYear = $request->input('month_year');

            $baseQuery = DB::table($tableName);
            if (!empty($monthYear) && $monthYear !== 'all') {
                $baseQuery->where('month_year', $monthYear);
            }

            $totalEmployees = (clone $baseQuery)->count();
            
            // Safe distinct locations count
            $totalLocations = (clone $baseQuery)
                ->whereNotNull('location')
                ->where('location', '!=', '')
                ->distinct()
                ->count('location');
            
            $categoriesBreakdown = (clone $baseQuery)
                ->whereNotNull('category')
                ->where('category', '!=', '')
                ->select('category', DB::raw('count(*) as count'))
                ->groupBy('category')
                ->pluck('count', 'category');

            $genderBreakdown = (clone $baseQuery)
                ->whereNotNull('sex')
                ->where('sex', '!=', '')
                ->select('sex', DB::raw('count(*) as count'))
                ->groupBy('sex')
                ->pluck('count', 'sex');

            $latestMonth = DB::table($tableName)->orderBy('id', 'desc')->value('month_year') ?: 'AUGUST 2026';

            return response()->json([
                'status' => 'success',
                'data' => [
                    'total_employees' => $totalEmployees,
                    'total_locations' => $totalLocations,
                    'categories' => $categoriesBreakdown,
                    'gender' => $genderBreakdown,
                    'latest_month' => $latestMonth,
                ]
            ]);
        } catch (\Throwable $e) {
            \Log::error('MonthlyEmployeeController@stats error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load stats: ' . $e->getMessage(),
                'data' => [
                    'total_employees' => 0,
                    'total_locations' => 0,
                    'categories' => [],
                    'gender' => [],
                    'latest_month' => 'AUGUST 2026',
                ]
            ], 200);
        }
    }

    /**
     * Get unique locations list.
     */
    public function locations()
    {
        try {
            self::ensureTableExists();
            $tableName = self::getActualTableName();

            $locations = DB::table($tableName)
                ->whereNotNull('location')
                ->where('location', '!=', '')
                ->distinct()
                ->orderBy('location')
                ->pluck('location');

            return response()->json([
                'status' => 'success',
                'data' => $locations
            ]);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'data' => []], 200);
        }
    }

    /**
     * Upload and parse employee file (.xlsx, .xls, .csv).
     */
    public function upload(Request $request)
    {
        try {
            self::ensureTableExists();

            $request->validate([
                'file' => 'required|file',
                'month_year' => 'nullable|string'
            ]);

            $file = $request->file('file');
            $monthYear = trim($request->input('month_year') ?: '');

            // If month_year not explicitly provided, try to infer from filename or default
            if (empty($monthYear)) {
                $filename = $file->getClientOriginalName();
                if (preg_match('/(january|february|march|april|may|june|july|august|september|october|november|december)\s*\d{4}/i', $filename, $matches)) {
                    $monthYear = strtoupper($matches[0]);
                } else {
                    $monthYear = strtoupper(date('F Y'));
                }
            }

            $ext = strtolower($file->getClientOriginalExtension());
            $path = $file->getRealPath();

            $records = [];
            if ($ext === 'csv' || $ext === 'txt') {
                $records = $this->parseCsvFile($path);
            } elseif ($ext === 'xlsx') {
                $records = $this->parseXlsxFile($path);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => "Unsupported file type: .{$ext}. Please upload a .xlsx or .csv file."
                ], 422);
            }

            if (empty($records)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No valid employee records found in the uploaded file.'
                ], 422);
            }

            return $this->saveRecordsToDb($records, $monthYear);
        } catch (\Throwable $e) {
            \Log::error('Upload error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ingest records: updates existing employees and adds new employees.
     */
    private function saveRecordsToDb(array $records, string $monthYear)
    {
        $now = Carbon::now();
        $batchSize = 500;
        $totalInserted = 0;
        $totalUpdated = 0;

        self::ensureTableExists();
        $tableName = self::getActualTableName();

        // 1. Clean up any corrupted records where emp_id contains spaces (names mistakenly put in emp_id)
        try {
            DB::table($tableName)->where('emp_id', 'like', '% %')->delete();
        } catch (\Throwable $e) {
            // ignore if query fails
        }

        // 2. Process in chunks of 500
        $chunks = array_chunk($records, $batchSize);

        foreach ($chunks as $chunk) {
            $empIds = [];
            $validRows = [];
            foreach ($chunk as $row) {
                $empId = trim($row['emp_id'] ?? '');
                if (empty($empId)) continue;
                $empIds[] = $empId;
                $validRows[$empId] = $row;
            }

            if (empty($empIds)) continue;

            // Check which employees already exist in DB
            $existing = DB::table($tableName)
                ->whereIn('emp_id', $empIds)
                ->pluck('id', 'emp_id')
                ->toArray();

            $insertRows = [];

            foreach ($validRows as $empId => $row) {
                $srNo = !empty($row['sr_no']) ? (int)$row['sr_no'] : null;
                $name = mb_substr(trim($row['employee_name'] ?? 'N/A'), 0, 190);
                $location = mb_substr(trim($row['location'] ?? 'N/A'), 0, 190);
                $designation = mb_substr(trim($row['designation'] ?? 'N/A'), 0, 190);
                $sex = mb_substr(trim($row['sex'] ?? ''), 0, 20);
                $category = mb_substr(trim($row['category'] ?? 'N/A'), 0, 100);

                if (isset($existing[$empId])) {
                    // Update existing employee with new month details
                    DB::table($tableName)
                        ->where('id', $existing[$empId])
                        ->update([
                            'sr_no' => $srNo,
                            'employee_name' => $name,
                            'location' => $location,
                            'designation' => $designation,
                            'sex' => $sex,
                            'category' => $category,
                            'month_year' => $monthYear,
                            'status' => 'ACTIVE',
                            'updated_at' => $now,
                        ]);
                    $totalUpdated++;
                } else {
                    // Add new employee
                    $insertRows[] = [
                        'sr_no' => $srNo,
                        'emp_id' => $empId,
                        'employee_name' => $name,
                        'location' => $location,
                        'designation' => $designation,
                        'sex' => $sex,
                        'category' => $category,
                        'month_year' => $monthYear,
                        'status' => 'ACTIVE',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            if (!empty($insertRows)) {
                DB::table($tableName)->insert($insertRows);
                $totalInserted += count($insertRows);
            }
        }

        $totalProcessed = $totalInserted + $totalUpdated;

        return response()->json([
            'status' => 'success',
            'message' => "Successfully processed {$totalProcessed} employees for {$monthYear}: {$totalInserted} new added, {$totalUpdated} updated.",
            'data' => [
                'total_processed' => $totalProcessed,
                'new_added' => $totalInserted,
                'updated' => $totalUpdated,
                'month_year' => $monthYear,
                'current_total' => DB::table($tableName)->count()
            ]
        ]);
    }

    /**
     * Wipes corrupted employee table and freshly ingests all 5,920 records from server Excel file.
     */
    public function cleanReingest(Request $request)
    {
        try {
            self::ensureTableExists();
            $tableName = self::getActualTableName();

            // Truncate to wipe any previous corrupted rows
            DB::table($tableName)->truncate();

            return $this->syncLocalExcel($request);
        } catch (\Throwable $e) {
            \Log::error('cleanReingest error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Clean re-ingest failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Direct endpoint to sync from local Excel file on server filesystem.
     */
    public function syncLocalExcel(Request $request)
    {
        try {
            self::ensureTableExists();

            $possiblePaths = [
                base_path('AUGUST 2026 PAYROLL DATA.xlsx'),
                base_path('../AUGUST 2026 PAYROLL DATA.xlsx'),
                base_path('../../AUGUST 2026 PAYROLL DATA.xlsx'),
                'c:\\Users\\USER\\Workspaces\\htdocs\\PMS\\AUGUST 2026 PAYROLL DATA.xlsx',
                'C:\\xampp\\htdocs\\PMS\\AUGUST 2026 PAYROLL DATA.xlsx',
                'C:\\xampp\\htdocs\\AUGUST 2026 PAYROLL DATA.xlsx',
                storage_path('app/AUGUST 2026 PAYROLL DATA.xlsx'),
                '\\\\192.168.0.24\\it-software\\IT DEV DAVID\\PMS\\AUGUST 2026 PAYROLL DATA.xlsx'
            ];

            $filePath = null;
            foreach ($possiblePaths as $p) {
                if (file_exists($p)) {
                    $filePath = $p;
                    break;
                }
            }

            if (!$filePath) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Local Excel file not found on server paths.',
                    'checked_paths' => $possiblePaths
                ], 404);
            }

            $monthYear = $request->input('month_year', 'AUGUST 2026');
            $rows = $this->parseXlsxFile($filePath);

            if (empty($rows)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No rows parsed from file: ' . $filePath
                ], 422);
            }

            return $this->saveRecordsToDb($rows, $monthYear);
        } catch (\Throwable $e) {
            \Log::error('syncLocalExcel error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Sync failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Parse an XLSX file strictly mapping the relative columns from the Excel file:
     * Column A: Sr.No
     * Column B: Emp Id
     * Column C: Employee Name
     * Column D: Location
     * Column E: Designation
     * Column F: Sex
     * Column G: Category
     */
    private function parseXlsxFile($filePath)
    {
        $rows = [];
        $zip = new \ZipArchive();

        if ($zip->open($filePath) !== true) {
            return $rows;
        }

        // 1. Read shared strings
        $sharedStrings = [];
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedStringsXml !== false) {
            $xml = simplexml_load_string($sharedStringsXml);
            if ($xml && isset($xml->si)) {
                foreach ($xml->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string)$si->t;
                    } elseif (isset($si->r)) {
                        $text = '';
                        foreach ($si->r as $r) {
                            $text .= (string)$r->t;
                        }
                        $sharedStrings[] = $text;
                    } else {
                        $sharedStrings[] = '';
                    }
                }
            }
        }

        // 2. Read first sheet
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXml === false) {
            $zip->close();
            return $rows;
        }

        $sheet = simplexml_load_string($sheetXml);
        $zip->close();

        if (!$sheet || !isset($sheet->sheetData->row)) {
            return $rows;
        }

        $rowCount = 0;

        foreach ($sheet->sheetData->row as $r) {
            $rowCount++;
            if ($rowCount === 1) {
                continue; // Skip header row
            }

            $rowCells = [];

            foreach ($r->c as $c) {
                $cellRef = (string)$c['r'];
                $colLetter = preg_replace('/[0-9]/', '', $cellRef);
                $type = (string)$c['t'];
                $val = isset($c->v) ? (string)$c->v : '';

                if ($type === 's' && isset($sharedStrings[(int)$val])) {
                    $val = $sharedStrings[(int)$val];
                }
                // Clean non-breaking spaces (ASCII 160)
                $val = trim(str_replace("\xc2\xa0", ' ', $val));
                $rowCells[$colLetter] = $val;
            }

            if (empty($rowCells)) continue;

            // STRICT RELATIVE COLUMNS:
            // Column A: Sr.No
            // Column B: Emp Id
            // Column C: Employee Name
            // Column D: Location
            // Column E: Designation
            // Column F: Sex
            // Column G: Category
            $empId = trim($rowCells['B'] ?? '');
            $empName = trim($rowCells['C'] ?? '');

            if (empty($empId) && empty($empName)) {
                continue;
            }

            $rows[] = [
                'sr_no' => !empty($rowCells['A']) && is_numeric($rowCells['A']) ? (int)$rowCells['A'] : null,
                'emp_id' => $empId,
                'employee_name' => $empName,
                'location' => trim($rowCells['D'] ?? ''),
                'designation' => trim($rowCells['E'] ?? ''),
                'sex' => trim($rowCells['F'] ?? ''),
                'category' => trim($rowCells['G'] ?? '')
            ];
        }

        return $rows;
    }

    /**
     * Parse a standard CSV or text file using strict relative columns.
     */
    private function parseCsvFile($filePath)
    {
        $rows = [];
        if (($handle = fopen($filePath, 'r')) !== false) {
            $lineCount = 0;

            while (($data = fgetcsv($handle, 4096, ',')) !== false) {
                $lineCount++;
                if ($lineCount === 1) {
                    continue; // Skip header
                }

                // Strict relative columns:
                // 0: Sr.No
                // 1: Emp Id
                // 2: Employee Name
                // 3: Location
                // 4: Designation
                // 5: Sex
                // 6: Category
                $empId = trim($data[1] ?? '');
                $empName = trim($data[2] ?? '');

                if (empty($empId) && empty($empName)) {
                    continue;
                }

                $rows[] = [
                    'sr_no' => !empty($data[0]) && is_numeric($data[0]) ? (int)$data[0] : null,
                    'emp_id' => $empId,
                    'employee_name' => $empName,
                    'location' => trim($data[3] ?? ''),
                    'designation' => trim($data[4] ?? ''),
                    'sex' => trim($data[5] ?? ''),
                    'category' => trim($data[6] ?? '')
                ];
            }
            fclose($handle);
        }

        return $rows;
    }

    /**
     * Download sample template CSV.
     */
    public function template()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="Monthly_Employees_Template.csv"',
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Sr.No', 'Emp Id', 'Employee Name', 'Location', 'Designation', 'Sex', 'Category']);
            fputcsv($file, ['1', 'H664', 'YEBOAH DAVID ADOM', 'HEAD OFFICE', 'IT TECHNICIAN', 'Male', 'CONTRACT']);
            fputcsv($file, ['2', '202274', 'FRANCIS TEYE', 'CENTURY', 'WELDER', 'Male', 'CONTRACT']);
            fputcsv($file, ['3', '206275', 'AYINE JACOB', 'ACCRA MALL', 'ELECTRICIAN', 'Male', 'PERMANENT']);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Public / Protected runner to ensure table migration on Server 20.
     */
    public function migrateTable()
    {
        try {
            self::ensureTableExists();
            $tableName = self::getActualTableName();
            $count = DB::table($tableName)->count();
            return response()->json([
                'status' => 'success',
                'message' => "Monthly_Employees table ({$tableName}) verified and ready on Server 20.",
                'table_used' => $tableName,
                'current_employee_count' => $count
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Migration failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Resolve the query for the onboarding 'employees' table across local or central connections.
     */
    public static function getOnboardingEmployeesQuery()
    {
        // Try central connection first (if reachable)
        try {
            DB::connection('central')->getPdo();
            if (Schema::connection('central')->hasTable('employees')) {
                return DB::connection('central')->table('employees');
            }
        } catch (\Throwable $e) {
            // Central unreachable, fallback to local
        }

        // Fallback to local default connection
        if (Schema::hasTable('employees')) {
            return DB::table('employees');
        }

        return null;
    }

    /**
     * Normalize status codes into standardized business status objects.
     */
    public static function mapOnboardingStatus($rawStatus, bool $isOnboarded = true): array
    {
        if (!$isOnboarded) {
            return [
                'code' => 'NOT_ONBOARDED',
                'label' => 'Not Onboarded',
                'severity' => 'warning',
                'badge_class' => 'badge-not-onboarded',
                'audit_flag' => 'Missing Onboarding Record',
                'recommendation' => 'Needs Onboarding Profile Created',
                'is_discrepancy' => true,
            ];
        }

        $val = trim((string)$rawStatus);
        $lower = strtolower($val);

        // Standard IDs from masterdata.js:
        // 1: Active, 2: Resigned, 3: Absconded, 4: Terminated, 5: Dismissed, 6: Deceased
        $knownStatuses = [
            '1' => 'Active',
            '2' => 'Resigned',
            '3' => 'Absconded',
            '4' => 'Terminated',
            '5' => 'Dismissed',
            '6' => 'Deceased',
        ];

        if (isset($knownStatuses[$val])) {
            $statusLabel = $knownStatuses[$val];
        } elseif ($lower === 'active') {
            $statusLabel = 'Active';
        } elseif ($lower === 'resigned') {
            $statusLabel = 'Resigned';
        } elseif ($lower === 'absconded') {
            $statusLabel = 'Absconded';
        } elseif ($lower === 'terminated') {
            $statusLabel = 'Terminated';
        } elseif ($lower === 'dismissed') {
            $statusLabel = 'Dismissed';
        } elseif ($lower === 'deceased') {
            $statusLabel = 'Deceased';
        } elseif ($lower === 'stop' || $lower === 'stopped' || $lower === 'hold' || $lower === 'inactive') {
            $statusLabel = 'Stop';
        } elseif (!empty($val)) {
            $statusLabel = ucwords($lower);
        } else {
            $statusLabel = 'Active'; // Default in employees schema is 1
        }

        $isActive = ($statusLabel === 'Active');
        $code = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '_', $statusLabel));

        if ($isActive) {
            return [
                'code' => 'ACTIVE',
                'label' => 'Active',
                'severity' => 'success',
                'badge_class' => 'badge-active',
                'audit_flag' => 'Verified Active in HR',
                'recommendation' => 'Compliant - Record in sync',
                'is_discrepancy' => false,
            ];
        }

        return [
            'code' => $code,
            'label' => $statusLabel,
            'severity' => 'danger',
            'badge_class' => 'badge-' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $statusLabel)),
            'audit_flag' => "CRITICAL: {$statusLabel} in HR, on Payroll",
            'recommendation' => "Review payroll - Employee status is {$statusLabel}",
            'is_discrepancy' => true,
        ];
    }

    /**
     * Reconcile payroll upload with onboarding records.
     * Source of truth for employees: Monthly_Employees.
     * Source of truth for employee status: Onboarding (employees table).
     */
    public function reconcileWithOnboarding(Request $request)
    {
        try {
            self::ensureTableExists();
            $tableName = self::getActualTableName();

            $monthYear = $request->input('month_year');
            if (empty($monthYear) || $monthYear === 'all') {
                $latestMonth = DB::table($tableName)->orderBy('id', 'desc')->value('month_year');
                $monthYear = $latestMonth ?: 'AUGUST 2026';
            }

            $statusFilter = strtolower(trim((string)$request->input('status_filter', 'all')));
            $search = trim((string)$request->input('search', ''));
            $location = trim((string)$request->input('location', ''));
            $perPage = min(max((int)$request->input('per_page', 50), 10), 500);
            $page = max((int)$request->input('page', 1), 1);

            // Fetch all monthly payroll employees for this batch
            $payrollQuery = DB::table($tableName)->where('month_year', $monthYear);
            $allPayrollEmployees = $payrollQuery->orderBy('sr_no', 'asc')->get();

            // Fetch all onboarding employees to build the cross-reference map
            $onboardingQuery = self::getOnboardingEmployeesQuery();
            $totalOnboarding = 0;
            $onboardingMap = [];
            if ($onboardingQuery) {
                try {
                    $totalOnboarding = (clone $onboardingQuery)->count();
                } catch (\Throwable $e) {
                    $totalOnboarding = 0;
                }

                $onboardingRows = $onboardingQuery
                    ->select('id', 'employeeid', 'emp_code', 'firstname', 'surname', 'status', 'email', 'mobileno', 'joiningposition', 'company', 'joining_branch_id')
                    ->get();

                if ($totalOnboarding === 0 && $onboardingRows) {
                    $totalOnboarding = count($onboardingRows);
                }

                foreach ($onboardingRows as $row) {
                    if (!empty($row->employeeid)) {
                        $cleanId = strtoupper(trim(preg_replace('/\s+/', '', (string)$row->employeeid)));
                        if ($cleanId !== '') {
                            $onboardingMap[$cleanId] = $row;
                            $noZeros = ltrim($cleanId, '0');
                            if ($noZeros !== '' && !isset($onboardingMap[$noZeros])) {
                                $onboardingMap[$noZeros] = $row;
                            }
                        }
                    }
                    if (!empty($row->emp_code)) {
                        $cleanCode = strtoupper(trim(preg_replace('/\s+/', '', (string)$row->emp_code)));
                        if ($cleanCode !== '') {
                            if (!isset($onboardingMap[$cleanCode])) {
                                $onboardingMap[$cleanCode] = $row;
                            }
                            $noZerosCode = ltrim($cleanCode, '0');
                            if ($noZerosCode !== '' && !isset($onboardingMap[$noZerosCode])) {
                                $onboardingMap[$noZerosCode] = $row;
                            }
                        }
                    }
                }
            }

            // Cross-reference and calculate metrics
            $reconciledRecords = [];
            $stats = [
                'total_payroll' => count($allPayrollEmployees),
                'total_onboarding' => $totalOnboarding,
                'active' => 0,
                'not_onboarded' => 0,
                'resigned' => 0,
                'absconded' => 0,
                'terminated' => 0,
                'dismissed' => 0,
                'deceased' => 0,
                'stop' => 0,
                'unknown' => 0,
                'status_conflicts' => 0,
                'total_discrepancies' => 0
            ];

            foreach ($allPayrollEmployees as $emp) {
                $empId = strtoupper(trim(preg_replace('/\s+/', '', (string)$emp->emp_id)));
                $empIdNoZero = ltrim($empId, '0');
                $obMatch = $onboardingMap[$empId] ?? ($onboardingMap[$empIdNoZero] ?? null);

                $statusMeta = self::mapOnboardingStatus($obMatch ? $obMatch->status : null, (bool)$obMatch);

                // Increment stats
                $statusCode = strtolower($statusMeta['code']);
                if (isset($stats[$statusCode])) {
                    $stats[$statusCode]++;
                } else {
                    $stats['unknown']++;
                }

                if ($obMatch && $statusMeta['is_discrepancy']) {
                    $stats['status_conflicts']++;
                }

                if ($statusMeta['is_discrepancy']) {
                    $stats['total_discrepancies']++;
                }

                $record = [
                    'monthly_id' => $emp->id,
                    'sr_no' => $emp->sr_no,
                    'emp_id' => $emp->emp_id,
                    'employee_name' => $emp->employee_name,
                    'location' => $emp->location,
                    'designation' => $emp->designation,
                    'sex' => $emp->sex,
                    'category' => $emp->category,
                    'month_year' => $emp->month_year,
                    'payroll_status' => $emp->status,
                    'onboarding_matched' => (bool)$obMatch,
                    'onboarding_id' => $obMatch ? $obMatch->id : null,
                    'onboarding_name' => $obMatch ? trim(($obMatch->firstname ?? '') . ' ' . ($obMatch->surname ?? '')) : null,
                    'onboarding_status_raw' => $obMatch ? $obMatch->status : null,
                    'onboarding_status' => $statusMeta,
                ];

                $reconciledRecords[] = $record;
            }

            // Apply Filters (status_filter, search, location)
            $filtered = collect($reconciledRecords);

            if ($statusFilter !== 'all' && $statusFilter !== '') {
                if ($statusFilter === 'discrepancies' || $statusFilter === 'discrepancies_only') {
                    $filtered = $filtered->filter(fn($r) => $r['onboarding_status']['is_discrepancy']);
                } elseif ($statusFilter === 'conflicts' || $statusFilter === 'status_conflicts') {
                    $filtered = $filtered->filter(fn($r) => $r['onboarding_matched'] && $r['onboarding_status']['is_discrepancy']);
                } else {
                    $filtered = $filtered->filter(function ($r) use ($statusFilter) {
                        return strtolower($r['onboarding_status']['code']) === $statusFilter;
                    });
                }
            }

            if (!empty($location) && $location !== 'all') {
                $filtered = $filtered->filter(function ($r) use ($location) {
                    return strcasecmp($r['location'] ?? '', $location) === 0;
                });
            }

            if (!empty($search)) {
                $term = strtolower($search);
                $filtered = $filtered->filter(function ($r) use ($term) {
                    return stripos($r['emp_id'] ?? '', $term) !== false ||
                           stripos($r['employee_name'] ?? '', $term) !== false ||
                           stripos($r['location'] ?? '', $term) !== false ||
                           stripos($r['designation'] ?? '', $term) !== false ||
                           stripos($r['onboarding_name'] ?? '', $term) !== false;
                });
            }

            $totalFiltered = $filtered->count();
            $lastPage = max((int)ceil($totalFiltered / $perPage), 1);
            $page = min($page, $lastPage);
            $offset = ($page - 1) * $perPage;
            $paginatedItems = $filtered->slice($offset, $perPage)->values();

            // Distinct lists for UI filters
            $availableLocations = collect($reconciledRecords)->pluck('location')->filter()->unique()->sort()->values();
            $availableMonths = DB::table($tableName)->distinct()->orderBy('month_year', 'desc')->pluck('month_year');

            return response()->json([
                'status' => 'success',
                'month_year' => $monthYear,
                'stats' => $stats,
                'data' => [
                    'data' => $paginatedItems,
                    'current_page' => $page,
                    'last_page' => $lastPage,
                    'per_page' => $perPage,
                    'total' => $totalFiltered,
                    'from' => $totalFiltered > 0 ? $offset + 1 : 0,
                    'to' => min($offset + $perPage, $totalFiltered)
                ],
                'filters' => [
                    'locations' => $availableLocations,
                    'months' => $availableMonths,
                ]
            ]);
        } catch (\Throwable $e) {
            \Log::error('MonthlyEmployeeController@reconcileWithOnboarding error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to reconcile records: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export the full reconciliation report as CSV.
     */
    public function exportReconciliationCsv(Request $request)
    {
        try {
            self::ensureTableExists();
            $tableName = self::getActualTableName();

            $monthYear = $request->input('month_year');
            if (empty($monthYear) || $monthYear === 'all') {
                $latestMonth = DB::table($tableName)->orderBy('id', 'desc')->value('month_year');
                $monthYear = $latestMonth ?: 'AUGUST 2026';
            }

            $statusFilter = strtolower(trim((string)$request->input('status_filter', 'all')));
            $search = trim((string)$request->input('search', ''));
            $location = trim((string)$request->input('location', ''));

            $payrollEmployees = DB::table($tableName)
                ->where('month_year', $monthYear)
                ->orderBy('sr_no', 'asc')
                ->get();

            $onboardingQuery = self::getOnboardingEmployeesQuery();
            $onboardingMap = [];
            if ($onboardingQuery) {
                $onboardingRows = $onboardingQuery
                    ->select('id', 'employeeid', 'emp_code', 'firstname', 'surname', 'status')
                    ->get();
                foreach ($onboardingRows as $row) {
                    if (!empty($row->employeeid)) {
                        $clean = strtoupper(trim(preg_replace('/\s+/', '', (string)$row->employeeid)));
                        if ($clean !== '') {
                            $onboardingMap[$clean] = $row;
                            $z = ltrim($clean, '0');
                            if ($z !== '' && !isset($onboardingMap[$z])) $onboardingMap[$z] = $row;
                        }
                    }
                    if (!empty($row->emp_code)) {
                        $clean = strtoupper(trim(preg_replace('/\s+/', '', (string)$row->emp_code)));
                        if ($clean !== '') {
                            if (!isset($onboardingMap[$clean])) $onboardingMap[$clean] = $row;
                            $z = ltrim($clean, '0');
                            if ($z !== '' && !isset($onboardingMap[$z])) $onboardingMap[$z] = $row;
                        }
                    }
                }
            }

            $cleanBatch = preg_replace('/[^A-Za-z0-9_\-]/', '_', $monthYear);
            $filename = "Payroll_vs_Onboarding_Audit_{$cleanBatch}.csv";

            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0'
            ];

            $callback = function () use ($payrollEmployees, $onboardingMap, $statusFilter, $search, $location) {
                $output = fopen('php://output', 'w');
                // UTF-8 BOM
                fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

                fputcsv($output, [
                    'Sr. No',
                    'Payroll Emp ID',
                    'Payroll Employee Name',
                    'Payroll Location',
                    'Payroll Designation',
                    'Payroll Category',
                    'Payroll Month Batch',
                    'Onboarding ID',
                    'Onboarding Full Name',
                    'Onboarding Status (Source of Truth)',
                    'Audit Discrepancy Flag',
                    'Action Recommendation',
                    'Is Discrepancy'
                ]);

                foreach ($payrollEmployees as $emp) {
                    $empId = strtoupper(trim(preg_replace('/\s+/', '', (string)$emp->emp_id)));
                    $empIdNoZero = ltrim($empId, '0');
                    $obMatch = $onboardingMap[$empId] ?? ($onboardingMap[$empIdNoZero] ?? null);
                    $statusMeta = self::mapOnboardingStatus($obMatch ? $obMatch->status : null, (bool)$obMatch);

                    if ($statusFilter !== 'all' && $statusFilter !== '') {
                        if ($statusFilter === 'discrepancies' && !$statusMeta['is_discrepancy']) {
                            continue;
                        } elseif (($statusFilter === 'conflicts' || $statusFilter === 'status_conflicts') && !($obMatch && $statusMeta['is_discrepancy'])) {
                            continue;
                        } elseif ($statusFilter !== 'discrepancies' && $statusFilter !== 'conflicts' && $statusFilter !== 'status_conflicts' && strtolower($statusMeta['code']) !== $statusFilter) {
                            continue;
                        }
                    }

                    if (!empty($location) && $location !== 'all' && strcasecmp($emp->location ?? '', $location) !== 0) {
                        continue;
                    }

                    if (!empty($search)) {
                        $term = strtolower($search);
                        $obName = $obMatch ? trim(($obMatch->firstname ?? '') . ' ' . ($obMatch->surname ?? '')) : '';
                        if (stripos($emp->emp_id ?? '', $term) === false &&
                            stripos($emp->employee_name ?? '', $term) === false &&
                            stripos($emp->location ?? '', $term) === false &&
                            stripos($emp->designation ?? '', $term) === false &&
                            stripos($obName, $term) === false) {
                            continue;
                        }
                    }

                    fputcsv($output, [
                        $emp->sr_no ?? '',
                        $emp->emp_id,
                        $emp->employee_name,
                        $emp->location ?? '',
                        $emp->designation ?? '',
                        $emp->category ?? '',
                        $emp->month_year ?? '',
                        $obMatch ? $obMatch->id : 'NOT FOUND',
                        $obMatch ? trim(($obMatch->firstname ?? '') . ' ' . ($obMatch->surname ?? '')) : 'NOT FOUND IN ONBOARDING',
                        $statusMeta['label'],
                        $statusMeta['audit_flag'],
                        $statusMeta['recommendation'],
                        $statusMeta['is_discrepancy'] ? 'YES' : 'NO'
                    ]);
                }

                fclose($output);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Throwable $e) {
            \Log::error('exportReconciliationCsv error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}

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

        // Process in chunks of 500
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
     * Parse an XLSX file without third-party dependencies using ZipArchive + SimpleXML.
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

        $headerMap = [];
        $rowCount = 0;

        foreach ($sheet->sheetData->row as $r) {
            $rowCount++;
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

            if ($rowCount === 1) {
                // Determine headers: Note that 'name' must be checked BEFORE 'id'/'emp' to prevent 'Employee Name' from matching 'emp'
                foreach ($rowCells as $col => $headerName) {
                    $norm = strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', $headerName)));
                    if (strpos($norm, 'sr') !== false || $norm === 'no' || $norm === 'sno') {
                        $headerMap[$col] = 'sr_no';
                    } elseif (strpos($norm, 'name') !== false) {
                        $headerMap[$col] = 'employee_name';
                    } elseif (strpos($norm, 'id') !== false || strpos($norm, 'code') !== false || $norm === 'empid' || $norm === 'employeeid' || $norm === 'emp') {
                        $headerMap[$col] = 'emp_id';
                    } elseif (strpos($norm, 'loc') !== false || strpos($norm, 'branch') !== false) {
                        $headerMap[$col] = 'location';
                    } elseif (strpos($norm, 'desig') !== false || strpos($norm, 'title') !== false || strpos($norm, 'pos') !== false) {
                        $headerMap[$col] = 'designation';
                    } elseif (strpos($norm, 'sex') !== false || strpos($norm, 'gender') !== false) {
                        $headerMap[$col] = 'sex';
                    } elseif (strpos($norm, 'cat') !== false || strpos($norm, 'type') !== false) {
                        $headerMap[$col] = 'category';
                    }
                }
            } else {
                if (empty($rowCells)) continue;

                $data = [
                    'sr_no' => null,
                    'emp_id' => '',
                    'employee_name' => '',
                    'location' => '',
                    'designation' => '',
                    'sex' => '',
                    'category' => ''
                ];

                foreach ($rowCells as $col => $val) {
                    if (isset($headerMap[$col])) {
                        $field = $headerMap[$col];
                        $data[$field] = $val;
                    }
                }

                // Positional fallbacks (A: Sr.No, B: Emp Id, C: Employee Name, D: Location, E: Designation, F: Sex, G: Category)
                if (empty($data['emp_id']) && isset($rowCells['B'])) {
                    $data['emp_id'] = $rowCells['B'];
                }
                if (empty($data['employee_name']) && isset($rowCells['C'])) {
                    $data['employee_name'] = $rowCells['C'];
                }
                if (empty($data['location']) && isset($rowCells['D'])) {
                    $data['location'] = $rowCells['D'];
                }
                if (empty($data['designation']) && isset($rowCells['E'])) {
                    $data['designation'] = $rowCells['E'];
                }
                if (empty($data['sex']) && isset($rowCells['F'])) {
                    $data['sex'] = $rowCells['F'];
                }
                if (empty($data['category']) && isset($rowCells['G'])) {
                    $data['category'] = $rowCells['G'];
                }

                if (!empty($data['emp_id'])) {
                    $rows[] = $data;
                }
            }
        }

        return $rows;
    }

    /**
     * Parse a standard CSV or text file.
     */
    private function parseCsvFile($filePath)
    {
        $rows = [];
        if (($handle = fopen($filePath, 'r')) !== false) {
            $headerMap = [];
            $lineCount = 0;

            while (($data = fgetcsv($handle, 4096, ',')) !== false) {
                $lineCount++;
                if ($lineCount === 1) {
                    foreach ($data as $idx => $headerName) {
                        $norm = strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', $headerName)));
                        if (strpos($norm, 'sr') !== false || $norm === 'no' || $norm === 'sno') {
                            $headerMap[$idx] = 'sr_no';
                        } elseif (strpos($norm, 'name') !== false) {
                            $headerMap[$idx] = 'employee_name';
                        } elseif (strpos($norm, 'id') !== false || strpos($norm, 'code') !== false || $norm === 'empid' || $norm === 'employeeid' || $norm === 'emp') {
                            $headerMap[$idx] = 'emp_id';
                        } elseif (strpos($norm, 'loc') !== false || strpos($norm, 'branch') !== false) {
                            $headerMap[$idx] = 'location';
                        } elseif (strpos($norm, 'desig') !== false || strpos($norm, 'title') !== false || strpos($norm, 'pos') !== false) {
                            $headerMap[$idx] = 'designation';
                        } elseif (strpos($norm, 'sex') !== false || strpos($norm, 'gender') !== false) {
                            $headerMap[$idx] = 'sex';
                        } elseif (strpos($norm, 'cat') !== false || strpos($norm, 'type') !== false) {
                            $headerMap[$idx] = 'category';
                        }
                    }
                } else {
                    $row = [
                        'sr_no' => null,
                        'emp_id' => '',
                        'employee_name' => '',
                        'location' => '',
                        'designation' => '',
                        'sex' => '',
                        'category' => ''
                    ];

                    foreach ($data as $idx => $val) {
                        $val = trim($val);
                        if (isset($headerMap[$idx])) {
                            $field = $headerMap[$idx];
                            $row[$field] = $val;
                        }
                    }

                    // Positional fallbacks
                    if (empty($row['emp_id']) && isset($data[1])) $row['emp_id'] = trim($data[1]);
                    if (empty($row['employee_name']) && isset($data[2])) $row['employee_name'] = trim($data[2]);
                    if (empty($row['location']) && isset($data[3])) $row['location'] = trim($data[3]);
                    if (empty($row['designation']) && isset($data[4])) $row['designation'] = trim($data[4]);
                    if (empty($row['sex']) && isset($data[5])) $row['sex'] = trim($data[5]);
                    if (empty($row['category']) && isset($data[6])) $row['category'] = trim($data[6]);

                    if (!empty($row['emp_id'])) {
                        $rows[] = $row;
                    }
                }
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
}

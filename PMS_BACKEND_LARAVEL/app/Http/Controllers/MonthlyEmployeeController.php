<?php

namespace App\Http\Controllers;

use App\Models\MonthlyEmployee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class MonthlyEmployeeController extends Controller
{
    /**
     * Auto-ensure Monthly_Employees table exists on Server 20 / local.
     */
    public static function ensureTableExists()
    {
        if (!Schema::hasTable('Monthly_Employees')) {
            Schema::create('Monthly_Employees', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('sr_no')->nullable();
                $table->string('emp_id', 50)->index();
                $table->string('employee_name', 255)->index();
                $table->string('location', 255)->nullable()->index();
                $table->string('designation', 255)->nullable();
                $table->string('sex', 20)->nullable();
                $table->string('category', 100)->nullable()->index();
                $table->string('month_year', 50)->default('AUGUST 2026')->index();
                $table->string('status', 50)->default('ACTIVE')->index();
                $table->timestamps();

                $table->index(['emp_id', 'month_year']);
            });
        }
    }

    /**
     * Display a paginated listing of monthly employees with filters.
     */
    public function index(Request $request)
    {
        self::ensureTableExists();

        $search = $request->input('search');
        $location = $request->input('location');
        $category = $request->input('category');
        $monthYear = $request->input('month_year');
        $sortBy = $request->input('sort_by', 'sr_no');
        $sortOrder = strtolower($request->input('sort_order', 'asc')) === 'desc' ? 'desc' : 'asc';
        $perPage = min(max((int)$request->input('per_page', 25), 5), 500);

        $query = MonthlyEmployee::query()
            ->search($search)
            ->location($location)
            ->category($category)
            ->monthYear($monthYear);

        // Sorting
        $allowedSorts = ['id', 'sr_no', 'emp_id', 'employee_name', 'location', 'designation', 'sex', 'category', 'month_year'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('sr_no', 'asc');
        }

        $employees = $query->paginate($perPage);

        // Distinct filter options for UI dropdowns
        $locations = MonthlyEmployee::select('location')
            ->whereNotNull('location')
            ->where('location', '!=', '')
            ->distinct()
            ->orderBy('location')
            ->pluck('location');

        $categories = MonthlyEmployee::select('category')
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        $months = MonthlyEmployee::select('month_year')
            ->whereNotNull('month_year')
            ->where('month_year', '!=', '')
            ->distinct()
            ->orderBy('id', 'desc')
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
    }

    /**
     * Statistical overview of monthly employees.
     */
    public function stats(Request $request)
    {
        self::ensureTableExists();

        $monthYear = $request->input('month_year');
        $baseQuery = MonthlyEmployee::query();
        if (!empty($monthYear) && $monthYear !== 'all') {
            $baseQuery->where('month_year', $monthYear);
        }

        $totalEmployees = (clone $baseQuery)->count();
        $totalLocations = (clone $baseQuery)->distinct('location')->count('location');
        
        $categoriesBreakdown = (clone $baseQuery)
            ->select('category', DB::raw('count(*) as count'))
            ->groupBy('category')
            ->get()
            ->pluck('count', 'category');

        $genderBreakdown = (clone $baseQuery)
            ->select('sex', DB::raw('count(*) as count'))
            ->groupBy('sex')
            ->get()
            ->pluck('count', 'sex');

        $latestMonth = MonthlyEmployee::orderBy('id', 'desc')->value('month_year') ?: 'AUGUST 2026';

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
    }

    /**
     * Get unique locations list.
     */
    public function locations()
    {
        self::ensureTableExists();

        $locations = MonthlyEmployee::select('location')
            ->whereNotNull('location')
            ->where('location', '!=', '')
            ->distinct()
            ->orderBy('location')
            ->pluck('location');

        return response()->json([
            'status' => 'success',
            'data' => $locations
        ]);
    }

    /**
     * Upload and parse employee file (.xlsx, .xls, .csv).
     */
    public function upload(Request $request)
    {
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
    }

    /**
     * Ingest records in clean batches.
     */
    private function saveRecordsToDb(array $records, string $monthYear)
    {
        $now = now();
        $batch = [];
        $batchSize = 500;
        $insertedCount = 0;

        DB::beginTransaction();
        try {
            // Delete previous records for this month to ensure completely clean, uncorrupted ingestion
            MonthlyEmployee::where('month_year', $monthYear)->delete();

            foreach ($records as $row) {
                $empId = trim($row['emp_id'] ?? '');
                if (empty($empId)) {
                    continue;
                }

                $batch[] = [
                    'sr_no' => !empty($row['sr_no']) ? (int)$row['sr_no'] : null,
                    'emp_id' => $empId,
                    'employee_name' => trim($row['employee_name'] ?? 'N/A'),
                    'location' => trim($row['location'] ?? 'N/A'),
                    'designation' => trim($row['designation'] ?? 'N/A'),
                    'sex' => trim($row['sex'] ?? ''),
                    'category' => trim($row['category'] ?? 'N/A'),
                    'month_year' => $monthYear,
                    'status' => 'ACTIVE',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (count($batch) >= $batchSize) {
                    DB::table('Monthly_Employees')->insert($batch);
                    $insertedCount += count($batch);
                    $batch = [];
                }
            }

            if (!empty($batch)) {
                DB::table('Monthly_Employees')->insert($batch);
                $insertedCount += count($batch);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => "Successfully uploaded and ingested all {$insertedCount} employee records for {$monthYear}.",
                'data' => [
                    'total_processed' => $insertedCount,
                    'month_year' => $monthYear,
                ]
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('MonthlyEmployee upload error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process employee upload: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Direct endpoint to sync from local Excel file on server.
     */
    public function syncLocalExcel()
    {
        self::ensureTableExists();

        $candidates = [
            'C:\\xampp\\htdocs\\PMS\\AUGUST 2026 PAYROLL DATA.xlsx',
            base_path('AUGUST 2026 PAYROLL DATA.xlsx'),
            base_path('../AUGUST 2026 PAYROLL DATA.xlsx'),
            base_path('../../AUGUST 2026 PAYROLL DATA.xlsx'),
            'C:\\Users\\USER\\Workspaces\\htdocs\\PMS\\AUGUST 2026 PAYROLL DATA.xlsx'
        ];

        $found = null;
        foreach ($candidates as $c) {
            if (file_exists($c)) {
                $found = $c;
                break;
            }
        }

        if (!$found) {
            return response()->json([
                'status' => 'error',
                'message' => 'Local AUGUST 2026 PAYROLL DATA.xlsx not found on server paths.',
                'checked_paths' => $candidates
            ], 404);
        }

        $records = $this->parseXlsxFile($found);
        return $this->saveRecordsToDb($records, 'AUGUST 2026');
    }

    /**
     * Parse .xlsx file natively using PHP ZipArchive and SimpleXML.
     */
    private function parseXlsxFile($filePath)
    {
        $zip = new \ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new \Exception("Cannot open .xlsx file.");
        }

        // 1. Read shared strings
        $sharedStrings = [];
        $ssXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($ssXml) {
            $xml = simplexml_load_string($ssXml);
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

        // 2. Read first sheet XML
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if (!$sheetXml) {
            // Try to find any sheet
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (strpos($name, 'xl/worksheets/sheet') === 0) {
                    $sheetXml = $zip->getFromName($name);
                    break;
                }
            }
        }

        if (!$sheetXml) {
            $zip->close();
            throw new \Exception("Could not find worksheet in .xlsx file.");
        }

        $xml = simplexml_load_string($sheetXml);
        $zip->close();

        $rows = [];
        $headerMap = [];
        $rowCount = 0;

        foreach ($xml->sheetData->row as $row) {
            $rowCount++;
            $rowCells = [];
            foreach ($row->c as $c) {
                $ref = (string)$c['r']; // e.g. A1, B1
                $colLetter = preg_replace('/[0-9]/', '', $ref);
                $type = (string)$c['t'];
                $val = (string)$c->v;

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
     * Parse .csv file.
     */
    private function parseCsvFile($filePath)
    {
        $rows = [];
        if (($handle = fopen($filePath, 'r')) !== false) {
            $headerMap = [];
            $lineCount = 0;

            while (($data = fgetcsv($handle, 2000, ',')) !== false) {
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
                        $val = trim(str_replace("\xc2\xa0", ' ', $val));
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
     * Ingest directly from local Excel file if present on server filesystem.
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
                storage_path('app/AUGUST 2026 PAYROLL DATA.xlsx')
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
                    'message' => 'Local Excel file not found on server filesystem.'
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
            \Log::error('syncLocalExcel error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Sync failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Public / Protected runner to ensure table migration on Server 20.
     */
    public function migrateTable()
    {
        try {
            self::ensureTableExists();
            $count = MonthlyEmployee::count();
            return response()->json([
                'status' => 'success',
                'message' => 'Monthly_Employees table verified and ready on Server 20.',
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

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ImportReportingLines extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:reporting-lines {file : The path to the CSV file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import reporting lines from a CSV file';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("File does not exist: {$filePath}");
            return 1;
        }

        $handle = fopen($filePath, "r");
        if ($handle === false) {
            $this->error("Could not open file: {$filePath}");
            return 1;
        }

        // Header Row 1: TEAM REPORTING LINE
        // Header Row 2: Employee Detail,,,,,Line Manager
        // Header Row 3: No.,Name,Emp. Code,Job Title,Department,Report To...
        fgetcsv($handle); // Skip Row 1
        fgetcsv($handle); // Skip Row 2
        fgetcsv($handle); // Skip Row 3

        // ──────────────────────────────────────────────────
        // PASS 1: Read ALL CSV rows into memory
        // ──────────────────────────────────────────────────
        $rows = [];
        $managers = []; // Collect unique managers keyed by emp_code

        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) < 10) continue;

            $empName  = $this->cleanString($data[1]);
            $empCode  = $this->cleanCode($data[2]);
            $jobTitle = $this->cleanString($data[3]);
            $dept     = $this->cleanString($data[4]);

            $lmDesignation = $this->cleanString($data[5]); // 'Report To' column header
            $lmName   = $this->cleanString($data[6]);
            $lmCode   = $this->cleanCode($data[7]);

            $almDesignation = $this->cleanString($data[8]); // 'Line 2 Manager Designation'
            $almName  = $this->cleanString($data[9]);
            $almCode  = $this->cleanCode($data[10]);

            if (empty($empCode) || empty($empName)) continue;

            $rows[] = compact(
                'empName', 'empCode', 'jobTitle', 'dept',
                'lmName', 'lmCode', 'lmDesignation',
                'almName', 'almCode', 'almDesignation'
            );

            // Collect unique Line Managers
            if (!empty($lmCode) && !isset($managers[$lmCode])) {
                $managers[$lmCode] = [
                    'name'     => $lmName,
                    'code'     => $lmCode,
                    'dept'     => $dept,
                    'jobTitle' => $lmDesignation,
                ];
            }

            // Collect unique Above Line Managers
            if (!empty($almCode) && !isset($managers[$almCode])) {
                $managers[$almCode] = [
                    'name'     => $almName,
                    'code'     => $almCode,
                    'dept'     => $dept,
                    'jobTitle' => $almDesignation,
                ];
            }
        }

        fclose($handle);

        // ──────────────────────────────────────────────────
        // PASS 2: Create ONLY unique Managers in the USERS table
        // ──────────────────────────────────────────────────
        $this->info("--- Creating Manager Accounts (users table) ---");
        $managerIdMap = []; // emp_code => user.id

        foreach ($managers as $code => $mgr) {
            $user = $this->ensureManager($mgr['code'], $mgr['name'], $mgr['dept'], $mgr['jobTitle']);
            $managerIdMap[$code] = $user->id;
            $this->comment("Manager ready: {$mgr['name']} ({$code})");
        }

        // Link Line Managers to their Above Line Managers in users table
        // This populates the "Reports To" column in the Line Manager Console
        foreach ($rows as $row) {
            if (!empty($row['lmCode']) && !empty($row['almName'])) {
                $lmUserId = $managerIdMap[$row['lmCode']] ?? null;
                if ($lmUserId) {
                    User::where('id', $lmUserId)->update(['report_to' => $row['almName']]);
                }
            }
        }

        // ──────────────────────────────────────────────────
        // PASS 3: Sync ALL employees (everyone) into the EMPLOYEES table
        // ──────────────────────────────────────────────────
        $this->info("--- Syncing All Employee Records (employees table) ---");
        $count = 0;

        foreach ($rows as $row) {
            $this->syncEmployee(
                $row['empCode'], $row['empName'], $row['dept'], $row['jobTitle']
            );

            $count++;
            $this->info("Processed: {$row['empName']} ({$row['empCode']})");
        }

        // Also ensure managers themselves are in the employees table
        foreach ($managers as $code => $mgr) {
            $this->syncEmployee($mgr['code'], $mgr['name'], $mgr['dept'], $mgr['jobTitle']);
        }

        $this->info("Import completed! Total employee records: {$count} | Unique managers: " . count($managers));
        return 0;
    }

    private function cleanCode($code)
    {
        return preg_replace('/[^A-Za-z0-9]/', '', $code);
    }

    private function cleanString($str)
    {
        if (empty($str)) return null;
        // Handle non-breaking spaces (\xA0) and trim
        $str = str_replace("\xA0", ' ', $str);
        // Replace multiple spaces with a single space
        $str = preg_replace('/\s+/', ' ', $str);
        return trim($str);
    }

    private function ensureManager($code, $name, $dept, $jobTitle = null)
    {
        $user = User::where('employee_code', $code)->first();
        
        if (!$user) {
            $user = User::create([
                'name' => $name,
                'email' => null,
                'username' => $code,
                'password' => Hash::make('Password'),
                'employee_code' => $code,
                'department' => $dept,
                'job_title' => $jobTitle,
                'location' => 'HO',
                'position_id' => 1,
                'user_id' => 1,
                'admin' => 0
            ]);
            $this->comment("Created Manager Account: {$name}");
        } else {
            $user->update([
                'name' => $name,
                'email' => null,
                'department' => $dept,
                'username' => $user->username ?: $code,
                'job_title' => $jobTitle ?? $user->job_title,
                'location' => 'HO'
            ]);
        }

        return $user;
    }

    private function syncEmployee($code, $name, $dept, $jobTitle)
    {
        $employee = \DB::table('employees')->where('employeeid', $code)->orWhere('emp_code', $code)->first();

        $data = [
            'full_name' => $name,
            'job_title' => $jobTitle,
            'department' => $dept,
            'employeeid' => $code,
            'emp_code' => $code,
            'updated_at' => now()
        ];

        if ($employee) {
            \DB::table('employees')->where('id', $employee->id)->update($data);
            $this->comment("Updated Employee: {$name}");
        } else {
            $data['created_at'] = now();
            
            // To satisfy strictly required name fields in the old schema
            $nameParts = explode(' ', trim($name), 2);
            $data['firstname'] = $nameParts[0] ?? '';
            $data['surname'] = $nameParts[1] ?? '';

            // Dynamically fulfill all other missing NOT NULL column requirements 
            // so MySQL never throws a "Field doesn't have a default value" error again
            $schemaColumns = \DB::select("SHOW COLUMNS FROM employees");
            foreach ($schemaColumns as $col) {
                $colName = $col->Field;
                if ($colName === 'id' || array_key_exists($colName, $data)) continue;

                if ($col->Null === 'NO' && $col->Default === null) {
                    if (strpos(strtolower($col->Type), 'int') !== false) {
                        $data[$colName] = 0;
                    } elseif (strpos(strtolower($col->Type), 'date') !== false || strpos(strtolower($col->Type), 'time') !== false) {
                        $data[$colName] = '2000-01-01';
                    } else {
                        $data[$colName] = 'N/A';
                    }
                }
            }

            try {
                \DB::table('employees')->insert($data);
                $this->comment("Created Employee: {$name}");
            } catch (\Exception $e) {
                $this->error("Failed Employee Record for {$name}: " . $e->getMessage());
            }
        }
    }
}


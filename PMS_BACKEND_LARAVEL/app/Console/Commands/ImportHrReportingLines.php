<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ImportHrReportingLines extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:hr-reporting-lines {file : The path to the HR CSV file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import HR reporting lines from a specialized CSV format';

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

        // Header Structure for HR CSV:
        // Row 1: HR TEAM REPORTING LINE
        // Row 2: No.,Name,Emp. Code,Job Title,Department,Report To,Name(s)
        fgetcsv($handle); // Skip Row 1
        fgetcsv($handle); // Skip Row 2

        $rows = [];
        $lookupByName = []; // name -> code

        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) < 7) continue;

            $empName  = $this->cleanString($data[1]);
            $empCode  = $this->cleanCode($data[2]);
            $jobTitle = $this->cleanString($data[3]);
            $dept     = $this->cleanString($data[4]);
            $lmDesignation = $this->cleanString($data[5]);
            $reportToNames = $this->cleanString($data[6]);

            // Special handling for Ajay Singh who has no code in CSV
            if ($empName === 'Ajay Singh' && empty($empCode)) {
                $empCode = 'HR@admin';
            }

            if (empty($empCode) || empty($empName)) continue;

            $rows[] = compact(
                'empName', 'empCode', 'jobTitle', 'dept',
                'lmDesignation', 'reportToNames'
            );

            $lookupByName[$empName] = [
                'code' => $empCode,
                'dept' => $dept,
                'jobTitle' => $jobTitle
            ];
        }

        fclose($handle);

        // Define the specific managers who need User accounts
        $managersToCreate = [
            'Ajay Singh',
            'Samuel Nana Elegba',
            'Nana Ama Dwum Bediako'
        ];

        // ──────────────────────────────────────────────────
        // PASS 2: Create Manager Accounts in USERS table
        // ──────────────────────────────────────────────────
        $this->info("--- Creating HR Manager Accounts (users table) ---");
        
        foreach ($managersToCreate as $mgrName) {
            if (!isset($lookupByName[$mgrName])) {
                $this->warn("Manager not found in employee list: {$mgrName}");
                continue;
            }

            $mgrData = $lookupByName[$mgrName];
            $user = $this->ensureManager($mgrData['code'], $mgrName, $mgrData['dept'], $mgrData['jobTitle']);
            
            // Find the reporting line for this manager from the rows
            foreach ($rows as $row) {
                if ($row['empName'] === $mgrName) {
                    $user->update(['report_to' => $row['reportToNames']]);
                    $this->comment("Portal account ready for {$mgrName}. Reports to: {$row['reportToNames']}");
                    break;
                }
            }
        }

        // ──────────────────────────────────────────────────
        // PASS 3: Sync ALL employees into EMPLOYEES table
        // ──────────────────────────────────────────────────
        $this->info("--- Syncing HR Employee Records (employees table) ---");
        $count = 0;

        foreach ($rows as $row) {
            $this->syncEmployee(
                $row['empCode'], $row['empName'], $row['dept'], $row['jobTitle']
            );

            $count++;
            $this->info("Processed: {$row['empName']} ({$row['empCode']})");
        }

        $this->info("HR Import completed! Total records: {$count}");
        return 0;
    }

    private function cleanCode($code)
    {
        if (strpos($code, '@') !== false) return trim($code); // Keep special codes like HR@admin
        return preg_replace('/[^A-Za-z0-9]/', '', $code);
    }

    private function cleanString($str)
    {
        if (empty($str)) return null;
        $str = str_replace("\xA0", ' ', $str);
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
        } else {
            $data['created_at'] = now();
            
            $nameParts = explode(' ', trim($name), 2);
            $data['firstname'] = $nameParts[0] ?? '';
            $data['surname'] = $nameParts[1] ?? '';

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
            } catch (\Exception $e) {
                $this->error("Failed Employee Record for {$name}: " . $e->getMessage());
            }
        }
    }
}

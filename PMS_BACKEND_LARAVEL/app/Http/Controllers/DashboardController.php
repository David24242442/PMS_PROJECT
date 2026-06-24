<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(){
        $totalemp = DB::table('employees')->count();

        $genderCounts = DB::table('employees')
        ->select('gender', DB::raw('COUNT(*) as total_count'))
        ->groupBy('gender')
        ->get();

        $companiesCounts = DB::table('employees')
        ->select('company', DB::raw('COUNT(*) as total_count'))
        ->groupBy('company')
        ->get();

        $citizenshipCounts = DB::table('employees')
        ->select('citizenship', DB::raw('COUNT(*) as total_count'))
        ->groupBy('citizenship')
        ->get();

        $deptsCounts = DB::table('employees')
        ->select('joining_dept_id', DB::raw('COUNT(*) as total_count'))
        ->groupBy('joining_dept_id')
        ->get();

        $locationCounts = DB::table('employees')
        ->select('joining_branch_id', DB::raw('COUNT(*) as total_count'))
        ->groupBy('joining_branch_id')
        ->get();

        $employees = Employee::select('dob')->get();

      
        $ageGroups = [
            '18-25' => 0,
            '26-30' => 0,
            '31-35' => 0,
            '36-40' => 0,
            '41-45' => 0,
            '46-50' => 0,
            '50+' => 0
        ];
        
        foreach ($employees as $user) {
            $age = Carbon::parse($user->dob)->age;
            
            if ($age >= 18 && $age <= 25) {
                $ageGroups['18-25']++;
            } elseif ($age >= 26 && $age <= 30) {
                $ageGroups['26-30']++;
            } elseif ($age >= 31 && $age <= 35) {
                $ageGroups['31-35']++;
            } elseif ($age >= 36 && $age <= 40) {
                $ageGroups['36-40']++;
            } elseif ($age >= 41 && $age <= 45) {
                $ageGroups['41-45']++;
            } elseif ($age >= 46 && $age <= 50) {
                $ageGroups['46-50']++;
            }elseif ($age > 50) {
                $ageGroups['50+']++;
            }
        }

        $currentYear = now()->year;
    
        // Predefined month order
        $monthOrder = [
            'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 
            'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
        ];
        
        // Get monthly data
        $monthlyData = Employee::selectRaw('DATE_FORMAT(joiningdate, "%b") as month, COUNT(*) as count')
            ->whereYear('joiningdate', $currentYear)
            ->groupBy('month')
            ->pluck('count', 'month')
            ->toArray();
        
        // Sort and fill in missing months
        $sortedData = [];
        foreach ($monthOrder as $month) {
            $sortedData[$month] = $monthlyData[$month] ?? 0;
        }
        

        return [
            'totalemp' => $totalemp,
            'genderCounts' => $genderCounts,
            'companiesCounts' => $companiesCounts,
            'citizenshipCounts' => $citizenshipCounts,
            'deptsCount' => $deptsCounts,
            'locationCount' => $locationCounts,
            'ageGroups' => $ageGroups,
            'monthlyData' => $sortedData
        ];
    }

    public function loadEmpDepts(Request $request){
        
        // return $request;
        
        $emps = Employee::with('profilepicture')->select('id','firstname','employeeid','emp_code')->orderBy('id', 'DESC');
        
        if($request->type == 'dept'){
            $emps = $emps->where('joining_dept_id',$request->value);
        }
        elseif ($request->type == 'loc') {
            $emps = $emps->where('joining_branch_id',$request->value);
        }
        elseif ($request->type == 'gender') {
            $emps = $emps->where('gender',$request->value);
        }
        elseif ($request->type == 'comp') {
            $emps = $emps->where('company',$request->value);
        }
        elseif ($request->type == 'citizenship') {
            $emps = $emps->where('citizenship',$request->value);
        }
        elseif ($request->type == 'agegroup') {
            
            $ageRange = $request->value;
            if($request->value == "50+") $ageRange = '50-100';

            list($minAge, $maxAge) = explode('-', $ageRange);
            $today = now();
            $maxDate = $today->copy()->subYears($minAge)->format('Y-m-d'); 
            $minDate = $today->copy()->subYears($maxAge + 1)->addDay()->format('Y-m-d'); 
            
            $emps = $emps->whereBetween('dob', [$minDate, $maxDate]);
        }
        elseif ($request->type == 'monthdata') {
            $month = $request->value;

            $monthMap = [
                'Jan' => 1, 'Feb' => 2, 'Mar' => 3, 'Apr' => 4,
                'May' => 5, 'Jun' => 6, 'Jul' => 7, 'Aug' => 8,
                'Sep' => 9, 'Oct' => 10, 'Nov' => 11, 'Dec' => 12
            ];

            $currentYear = now()->year;
            $monthNumber = $monthMap[$month];

            $emps = $emps->whereYear('joiningdate', $currentYear)
            ->whereMonth('joiningdate', $monthNumber);
        }
        else{
            return [];
        }
        
        $emps = $emps->get();

        return $emps;
    }
}

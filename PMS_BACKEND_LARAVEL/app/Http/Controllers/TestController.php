<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\History;
use App\Models\Employee;
use App\Models\Education;
use App\Models\BankSocial;
use App\Models\IrrGuarantee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;



?>


<?php
class TestController extends Controller
{
    public function test(){
        
        // $histories = History::with(['histos'])
        //     ->with(['employee' => function($query) {
        //         $query->whereHas('histos', function($subQuery) {
        //             $subQuery->where('histos_type', '!=', 'App\Models\Employee');
        //         });
        //     }])
        //     ->get();
        // return $histories;

        // $histories = History::with(['histos' => function($query) {
        //     // Only load the relationship when model_type is BankSocial or IrrGuarantee
        //     $query->where(function($q) {
        //         $q->where('histos_type ', 'App\Models\BankSocial')
        //           ->orWhere('histos_type ', 'App\Models\IrrGuarantee');
        //     });
            
        //     // And then load the employee relationship from those models
        //     $query->with('employee');
        // }])->get();

        // $records = History::whereIn('histos_type', [
        //     App\Models\BankSocial::class,
        //     App\Models\IrrGuarantee::class
        // ])->with('histos')->get();

        // $histories = History::with(['histos.employee:id,firstname,middlename,surname'])->get();

        $histories = History::with(['histos.employee:id,firstname,middlename,surname'])->get();   
        // }])->get();


        // ['educations.files', 'workexps', 'refs', 'childrens', 'wives', 'soccontact', 'econs.files', 'guarantos.files', 'presentjob', 'banksocial.lasthistory.creator:id,name', 'profilepicture', 'ghcard', 'signature', 'guarsignature', 'appletters','appointmentletters','probationconfs','cv','petratrust','nhis','birthcert','pclearanceform','ssnit','unioninfo','workpermit','driverlicense.files', 'nominee.files','lasthistory.creator:id,name',
        //     'irrguarantor' => function ($query) {
        //         $query->with(['witnesses', 'signature', 'idcard','profilepicture', 'guarforms', 'lasthistory.creator:id,name']);   // Each child's picture
        //     }/* ,'irrguarantor' => function ($query) {
        //         $query->with(['witnesses', 'signature', 'idcard','profilepicture', 'guarforms']);   // Each child's picture
        //     } */
        //     ]

        // foreach ($histories as $history) {
        //     if($history->histos_type == 'App\Models\BankSocial' || $history->histos_type == 'App\Models\IrrGuarantee'){
        //         $history->histos->load('employee');
        //     }
        // }
        return $histories;

        
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Wife;
use App\Models\Union;
use App\Models\Nominee;
use App\Models\Children;
use App\Models\Employee;
use App\Models\Education;
use App\Models\Guarantor;
use App\Models\Reference;
use App\Models\BankSocial;
use App\Models\PresentJob;
use App\Models\Workpermit;
use Illuminate\Support\Str;
use App\Models\IrrGuarantee;
use Illuminate\Http\Request;
use App\Models\Driverlicense;
use App\Models\SocialContact;
use App\Models\IrrGuarWitness;
use App\Models\WorkExperience;
use Illuminate\Validation\Rule;
use App\Models\EmergencyContact;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Try central DB first, fallback to local
        try {
            \Illuminate\Support\Facades\DB::connection('central')->getPdo();
            $employees = Employee::on('central')->with(['creator']);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Central DB Connection Error: ' . $e->getMessage());
            $employees = Employee::with(['creator']);
        }

        if($request->employeeinfo){
            $search = $request->employeeinfo;
            $employees = $employees->where(function($query) use($search) {
                $query->where('firstname', 'like', "%$search%")
                ->orWhere('surname', 'like', "%$search%")
                ->orWhere('employeeid', 'like', "%$search%")
                ->orWhere('emp_code', 'like', "%$search%")
                ->orWhere('mobileno', 'like', "%$search%")
                ->orWhere('joiningposition', 'like', "%$search%");
            });
            
        }
        // if($request->dept){
        //     $employees = $employees->where('joining_dept_id', $request->dept);
        // }

        // if($request->branch){
        //     $employees = $employees->where('joining_branch_id', $request->branch);
        // }

        // return $request->filters;
        if(count((array)$request->filters)){
            $filters = $request->filters;
            

            foreach ($filters as $data) {

                $op = '=';
                switch($data['op']){
                    case 'is':
                        $op = '=';
                        break;
                    case 'isnot':
                        $op = "!=";
                        break;
                    case 'gt':
                        $op = ">";
                        break;
                    case 'gtq':
                        $op = ">=";
                        break;
                    case 'lt':
                        $op = "<";
                        break;
                    case 'ltq':
                        $op = "<=";
                        break;
                    case 'cont':
                        $op = "like";
                        break;
                    
                }

                $val = $data['val'];
                if($data['op'] == 'cont'){
                    $val = "%$val%";
                }

                if($data['cond'] == "a"){
                    if($data['attr'] == 'creator'){
                        $employees->whereHas('creator', function ($query) use ($val, $op) {
                            $query->where('name', $op, $val);
                        });
                    }elseif($data['attr'] == 'employeeid'){
                        $employees->where(function($q) use ($val, $op) {
                            $q->where('employeeid', $op, $val)
                              ->orWhere('emp_code', $op, $val);
                        });
                    }else{
                        $employees->where($data['attr'], $op, $val);
                    }

                }else{
                    if($data['attr'] == 'creator'){
                        $employees->orWhereHas('creator', function ($query) use ($val, $op) {
                            $query->where('name', $op, $val);
                        });
                    }elseif($data['attr'] == 'employeeid'){
                        $employees->where(function($q) use ($val, $op) {
                            $q->where('employeeid', $op, $val)
                              ->orWhere('emp_code', $op, $val);
                        });
                    }else{
                        $employees->orWhere($data['attr'], $op, $val);
                    }

                }
            }


        }

        if($request->createdfrom){
            $employees = $employees->where('created_at', '>=', $request->createdfrom);
        }
        if($request->createdto){
            $employees = $employees->where('created_at', '<=', $request->createdto.' 23:59:59');
        }

        // Apply dynamic sorting
        $sortBy = $request->sort_by ?? 'newest';
        switch ($sortBy) {
            case 'oldest':
                $employees->orderBy('created_at', 'ASC')->orderBy('id', 'ASC');
                break;
            case 'name_asc':
                $employees->orderBy('firstname', 'ASC')->orderBy('surname', 'ASC');
                break;
            case 'name_desc':
                $employees->orderBy('firstname', 'DESC')->orderBy('surname', 'DESC');
                break;
            case 'surname_asc':
                $employees->orderBy('surname', 'ASC')->orderBy('firstname', 'ASC');
                break;
            case 'surname_desc':
                $employees->orderBy('surname', 'DESC')->orderBy('firstname', 'DESC');
                break;
            case 'empid_asc':
                $employees->orderBy('employeeid', 'ASC');
                break;
            case 'empid_desc':
                $employees->orderBy('employeeid', 'DESC');
                break;
            case 'email_asc':
                $employees->orderBy('email', 'ASC');
                break;
            case 'email_desc':
                $employees->orderBy('email', 'DESC');
                break;
            case 'status_asc':
                $employees->orderBy('status', 'ASC');
                break;
            case 'status_desc':
                $employees->orderBy('status', 'DESC');
                break;
            case 'joining_desc':
                $employees->orderBy('joiningdate', 'DESC');
                break;
            case 'joining_asc':
                $employees->orderBy('joiningdate', 'ASC');
                break;
            case 'newest':
            default:
                $employees->orderBy('created_at', 'DESC')->orderBy('id', 'DESC');
                break;
        }

        $employees = $employees->paginate($request->per_page);
        return $employees;
    }
    public function fetchemployeesforexport(Request $request)
    {
        
        $employees = Employee::select('id', 'firstname', 'surname', 'employeeid', 'company', 'joining_branch_id', 'joining_dept_id', 'joiningposition', 'status')->with(
            ['ghcard','appletters','appointmentletters','probationconfs','cv','petratrust','nhis','birthcert','pclearanceform','ssnit','irrguarantor','banksocial'
            ]
        )->orderBy('id', 'DESC');

        if($request->employeeinfo){
            $search = $request->employeeinfo;
            $employees = $employees->where(function($query) use($search) {
                $query->where('firstname', 'like', "%$search%")
                ->orWhere('surname', 'like', "%$search%")
                ->orWhere('employeeid', 'like', "%$search%")
                ->orWhere('emp_code', 'like', "%$search%")
                ->orWhere('mobileno', 'like', "%$search%")
                ->orWhere('joiningposition', 'like', "%$search%");
            });
            
        }
        
        if(count((array)$request->filters)){
            $filters = $request->filters;
            

            foreach ($filters as $data) {

                $op = '=';
                switch($data['op']){
                    case 'is':
                        $op = '=';
                        break;
                    case 'isnot':
                        $op = "!=";
                        break;
                    case 'gt':
                        $op = ">";
                        break;
                    case 'gtq':
                        $op = ">=";
                        break;
                    case 'lt':
                        $op = "<";
                        break;
                    case 'ltq':
                        $op = "<=";
                        break;
                    case 'cont':
                        $op = "like";
                        break;
                    
                }

                $val = $data['val'];
                if($data['op'] == 'cont'){
                    $val = "%$val%";
                }
                
                if($data['cond'] == "a"){
                    if($data['attr'] == 'creator'){
                        $employees->whereHas('creator', function ($query) use ($val, $op) {
                            $query->where('name', $op, $val);
                        });
                    }else{
                        $employees->where($data['attr'], $op, $val);
                    }
                    
                }else{
                    if($data['attr'] == 'creator'){
                        $employees->orWhereHas('creator', function ($query) use ($val, $op) {
                            $query->where('name', $op, $val);
                        });
                    }else{
                        $employees->orWhere($data['attr'], $op, $val);
                    }
                    
                }
            }

            
        }
        
        if($request->createdfrom){
            $employees = $employees->where('created_at', '>=', $request->createdfrom);
        }
        if($request->createdto){
            $employees = $employees->where('created_at', '<=', $request->createdto.' 23:59:59');
        }

        
        $employees = $employees->get();
        return $employees;
    }

    public function fetchemployeesdumpforexport(Request $request)
    {
        
        $employees = Employee::query();

        if($request->employeeinfo){
            $search = $request->employeeinfo;
            $employees = $employees->where(function($query) use($search) {
                $query->where('firstname', 'like', "%$search%")
                ->orWhere('surname', 'like', "%$search%")
                ->orWhere('employeeid', 'like', "%$search%")
                ->orWhere('emp_code', 'like', "%$search%")
                ->orWhere('mobileno', 'like', "%$search%")
                ->orWhere('joiningposition', 'like', "%$search%");
            });
            
        }
        
        if(count((array)$request->filters)){
            $filters = $request->filters;
            

            foreach ($filters as $data) {

                $op = '=';
                switch($data['op']){
                    case 'is':
                        $op = '=';
                        break;
                    case 'isnot':
                        $op = "!=";
                        break;
                    case 'gt':
                        $op = ">";
                        break;
                    case 'gtq':
                        $op = ">=";
                        break;
                    case 'lt':
                        $op = "<";
                        break;
                    case 'ltq':
                        $op = "<=";
                        break;
                    case 'cont':
                        $op = "like";
                        break;
                    
                }

                $val = $data['val'];
                if($data['op'] == 'cont'){
                    $val = "%$val%";
                }
                
                if($data['cond'] == "a"){
                    if($data['attr'] == 'creator'){
                        $employees->whereHas('creator', function ($query) use ($val, $op) {
                            $query->where('name', $op, $val);
                        });
                    }else{
                        $employees->where($data['attr'], $op, $val);
                    }
                    
                }else{
                    if($data['attr'] == 'creator'){
                        $employees->orWhereHas('creator', function ($query) use ($val, $op) {
                            $query->where('name', $op, $val);
                        });
                    }else{
                        $employees->orWhere($data['attr'], $op, $val);
                    }
                    
                }
            }

            
        }
        
        if($request->createdfrom){
            $employees = $employees->where('created_at', '>=', $request->createdfrom);
        }
        if($request->createdto){
            $employees = $employees->where('created_at', '<=', $request->createdto.' 23:59:59');
        }

        // Apply dynamic sorting
        $sortBy = $request->sort_by ?? 'name_asc';
        switch ($sortBy) {
            case 'oldest':
                $employees->orderBy('created_at', 'ASC')->orderBy('id', 'ASC');
                break;
            case 'newest':
                $employees->orderBy('created_at', 'DESC')->orderBy('id', 'DESC');
                break;
            case 'name_desc':
                $employees->orderBy('firstname', 'DESC')->orderBy('surname', 'DESC');
                break;
            case 'surname_asc':
                $employees->orderBy('surname', 'ASC')->orderBy('firstname', 'ASC');
                break;
            case 'surname_desc':
                $employees->orderBy('surname', 'DESC')->orderBy('firstname', 'DESC');
                break;
            case 'empid_asc':
                $employees->orderBy('employeeid', 'ASC');
                break;
            case 'empid_desc':
                $employees->orderBy('employeeid', 'DESC');
                break;
            case 'email_asc':
                $employees->orderBy('email', 'ASC');
                break;
            case 'email_desc':
                $employees->orderBy('email', 'DESC');
                break;
            case 'status_asc':
                $employees->orderBy('status', 'ASC');
                break;
            case 'status_desc':
                $employees->orderBy('status', 'DESC');
                break;
            case 'joining_desc':
                $employees->orderBy('joiningdate', 'DESC');
                break;
            case 'joining_asc':
                $employees->orderBy('joiningdate', 'ASC');
                break;
            case 'name_asc':
            default:
                $employees->orderBy('firstname', 'ASC')->orderBy('surname', 'ASC');
                break;
        }

        $employees = $employees->get();
        return $employees;
    }

    public function search(Request $request)
    {
        $search = $request->data;

        // echo $search;
        $employees = Employee::select('id','firstname','employeeid','emp_code')
            ->where('firstname', 'like', "%$search%")
            ->orWhere('employeeid', 'like', "%$search%")
            ->orWhere('emp_code', 'like', "%$search%")
            ->orderBy('id', 'DESC')
            ->take(10)->get();
        return $employees;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $validator = \Validator::make($request->emp,[
            "firstname" => ['required'],
            "surname" => ['required'],
            "email" => ['nullable','email',Rule::unique("employees", "email")],
            "socialsecurityno" => ['nullable',Rule::unique("employees", "socialsecurityno")],
            "ghcardno" => ['required',Rule::unique("employees", "ghcardno")],
            "mobileno" => ['required',Rule::unique("employees", "mobileno")],
            "employeeid" => ['sometimes',Rule::unique("employees", "employeeid")],
            'signature' => ['required'],
        ]);

        
        if ($validator->fails())
        {
            return response()->json(array(
                'success' => false,
                'errors' => $validator->getMessageBag()->toArray()
            ), 202);
        }
        
        $randomString = strtoupper(Str::random(12));

        $r = $request;
        
        $empData = $request->emp;
        $empData['emp_code'] = $randomString;
        $empData['user_id'] = Auth::user()->id;
        $empData['recordstatus'] = 0;

        $profilepicturefiles = $request->emp['profilepicture'];
        unset($empData['profilepicture']);

        $ghcardfilefiles = $request->emp['ghcardfile'];
        unset($empData['ghcardfile']);

        $signature = $request->emp['signature'];
        unset($empData['signature']);

        $guarsignature = $request->emp['guarsignature'];
        unset($empData['guarsignature']);

        $emp = Employee::create($empData);
        $empid = $emp->id;

        
        foreach($profilepicturefiles as $file){
            $image = base64_decode($file);
            $fileName = uniqid() . '.jpg';
            $path = 'profilepicture/'.$fileName;
            Storage::disk('public')->put($path, $image);

            $emp->profilepicture()->create([
                'type' => 'profilepicture',
                'path' => $path
            ]);
        }
        
        foreach($ghcardfilefiles as $file){
            $image = base64_decode($file);
            $fileName = uniqid() . '.jpg';

            $path = 'ghcard/'.$fileName;

            Storage::disk('public')->put($path, $image);

            $emp->ghcard()->create([
                'type' => 'ghcard',
                'path' => $path
            ]);
        }

        $image_parts = explode(";base64,", $signature);
        $sign_image = base64_decode($image_parts[1]);

        $signfileName = uniqid() . '.jpg';
        $signpath = 'employeesignatures/'.$signfileName;
        Storage::disk('public')->put($signpath, $sign_image);

        $emp->signature()->create([
            'type' => 'signature',
            'path' => $signpath
        ]);


        $guarimage_parts = explode(";base64,", $guarsignature);
        $guarsign_image = base64_decode($guarimage_parts[1]);

        $guarsignfileName = uniqid() . '.jpg';
        $guarsignpath = 'employeeguarsignatures/'.$guarsignfileName;
        Storage::disk('public')->put($guarsignpath, $guarsign_image);

        $emp->guarsignature()->create([
            'type' => 'guarsignature',
            'path' => $guarsignpath
        ]);

        if(count($request->edus)){
            foreach($request->edus as $edu) {
                $files = $edu['files'];
                unset($edu['files']);
                
                $edu['emp_id'] = $empid;
                $education = Education::create($edu); 

                foreach($files as $file){
                    $file = base64_decode($file);
                    $fileName = uniqid() . '.jpg';
                    $path = 'educations/'.$fileName;
                    Storage::disk('public')->put($path, $file);
                    

                    $education->files()->create([
                        'path' => $path
                    ]);
                }
                         
            }
        }
        
        if(count($request->econts)){
            foreach($request->econts as $econt) {
                $files = $econt['files'];
                unset($econt['files']);
                
                $econt['emp_id'] = $empid;
                $econtact = EmergencyContact::create($econt); 

                foreach($files as $file){
                    $file = base64_decode($file);
                    $fileName = uniqid() . '.jpg';
                    $path = 'econtactids/'.$fileName;
                    Storage::disk('public')->put($path, $file);
                    

                    $econtact->files()->create([
                        'path' => $path
                    ]);
                }
                         
            }
        }

        if(count($r['curwork'])){
            $data = $r['curwork'];
            $data['emp_id'] = $empid;
            PresentJob::create($data);  
        }

        if(count($r['unioninfo'])){
            $data = $r['unioninfo'];
            $data['emp_id'] = $empid;
            Union::create($data);  
        }

        if(count($r['wpermit'])){
            $data = $r['wpermit'];
            $data['emp_id'] = $empid;
            Workpermit::create($data);  
        }

        if(count($r['dlicense'])){
            $data = $r['dlicense'];

            $files = $data['files'];
            unset($data['files']);
            
            $data['emp_id'] = $empid;
            $dlicense = Driverlicense::create($data); 

            foreach($files as $file){
                $file = base64_decode($file);
                $fileName = uniqid() . '.jpg';
                $path = 'driverlicenses/'.$fileName;
                Storage::disk('public')->put($path, $file);
                

                $dlicense->files()->create([
                    'path' => $path
                ]);
            }

        }

        if(count($r['nominee'])){
            $data = $r['nominee'];

            $files = [];
            if(isset($data['files'])){
                $files = $data['files'];
            }
            
            unset($data['files']);
            
            $data['emp_id'] = $empid;
            $nominee = Nominee::create($data); 

            foreach($files as $file){
                $file = base64_decode($file);
                $fileName = uniqid() . '.jpg';
                $path = 'nominees/'.$fileName;
                Storage::disk('public')->put($path, $file);
                

                $nominee->files()->create([
                    'path' => $path
                ]);
            }

        }

        if(count($r['soccont'])){
            $data = $r['soccont'];
            $data['emp_id'] = $empid;
            SocialContact::create($data);
        }
        if(count($r['childrens'])){
            foreach ($r['childrens'] as $child) {
                $child['emp_id'] = $empid;
                Children::create($child);  
            }
        }

        if(count($r['wives'])){
            foreach ($r['wives'] as $wife) {
                
                $wife['emp_id'] = $empid;
                Wife::create($wife);  
                
            }
        }

        if(count($r['refs'])){
            foreach ($r['refs'] as $ref) {
                $ref['emp_id'] = $empid;
                Reference::create($ref);            
            }
        }
        if(count($r['workexps'])){
            foreach ($r['workexps'] as $workexp) {
                $workexp['emp_id'] = $empid;
                WorkExperience::create($workexp);            
            }
        }

        $emp->histories()->create([
            'user_id' => Auth::user()->id,
            'recordstatus' => 0,
            'details' => 'The information is created'
        ]);

        return $emp->load(['educations.files', 'workexps', 'refs', 'childrens', 'wives', 'soccontact', 'econs.files', 'lasthistory.creator:id,name', /* 'guarantos.files', */ 'presentjob', 'profilepicture', 'ghcard', 'signature', 'guarsignature', 'unioninfo','workpermit', 'driverlicense.files', 'nominee.files']);

    }
    public function updateemp(Request $request)
    {
       
        

        $r = $request;
        $empData = $request->emp;

        $validator = \Validator::make($request->emp,[
            "firstname" => ['required'],
            "surname" => ['required'],
            "email" => ['nullable','email',Rule::unique("employees", "email")->ignore($request->emp['id'])],
            "socialsecurityno" => ['nullable',Rule::unique("employees", "socialsecurityno")->ignore($request->emp['id'])],
            "ghcardno" => ['required',Rule::unique("employees", "ghcardno")->ignore($request->emp['id'])],
            "mobileno" => ['required',Rule::unique("employees", "mobileno")->ignore($request->emp['id'])],
            "employeeid" => ['sometimes',Rule::unique("employees", "employeeid")->ignore($request->emp['id'])],
            
        ]);

        if ($validator->fails())
        {
            return response()->json(array(
                'success' => false,
                'errors' => $validator->getMessageBag()->toArray()
            ), 202);
        }

        unset($empData['profilepicture']);
        unset($empData['ghcardfile']);
        unset($empData['signature']);
        unset($empData['guarsignature']);
        unset($empData['banksocial']);
        unset($empData['childrens']);
        unset($empData['wives']);
        unset($empData['econs']);
        unset($empData['educations']);
        unset($empData['ghcard']);
        unset($empData['guarantos']);
        unset($empData['irrguarantor']);
        unset($empData['presentjob']);
        unset($empData['unioninfo']);
        unset($empData['workpermit']);
        unset($empData['nominee']);
        unset($empData['driverlicense']);
        unset($empData['refs']);
        unset($empData['workexps']);
        unset($empData['soccontact']);
        unset($empData['appletters']);
        unset($empData['cv']);
        unset($empData['petratrust']);
        unset($empData['nhis']);
        unset($empData['birthcert']);
        unset($empData['pclearanceform']);
        unset($empData['ssnit']);
        unset($empData['appointmentletters']);
        unset($empData['probationconfs']);
        unset($empData['lasthistory']);
        
        $emp = Employee::find($empData['id']);
        $emp->update($empData);
        $empid = $emp->id;

        if(count($r['curwork'])){
            if(isset($r['curwork']['id'])){
                PresentJob::find($r['curwork']['id'])->update($r['curwork']);
            }else{
                $data = $r['curwork'];
                $data['emp_id'] = $empid;
                PresentJob::create($data); 
            } 
        }

        if(count($r['unioninfo'])){
            if(isset($r['unioninfo']['id'])){
                Union::find($r['unioninfo']['id'])->update($r['unioninfo']);
            }else{
                $data = $r['unioninfo'];
                $data['emp_id'] = $empid;
                Union::create($data); 
            } 
        }

        if(count($r['wpermit'])){
            if(!isset($r['wpermit']['id'])){
                $data = $r['wpermit'];
                $data['emp_id'] = $empid;
                Workpermit::create($data); 
            }else{
                Workpermit::find($r['wpermit']['id'])->update($r['wpermit']);
            }
        }

        if(count($r['dlicense'])){
            if(!isset($r['dlicense']['id'])){
                $data = $r['dlicense'];

                $files = $data['files'];
                unset($data['files']);
                
                $data['emp_id'] = $empid;
                $dlicense = Driverlicense::create($data); 

                foreach($files as $file){
                    $file = base64_decode($file);
                    $fileName = uniqid() . '.jpg';
                    $path = 'driverlicenses/'.$fileName;
                    Storage::disk('public')->put($path, $file);
                    

                    $dlicense->files()->create([
                        'path' => $path
                    ]);
                }
            }else{
                $d = $r['dlicense'];

                unset($d['files']);
                Driverlicense::find($r['dlicense']['id'])->update($d);
            }
        }

        if(count($r['nominee'])){

            if(!isset($r['nominee']['id'])){

                $data = $r['nominee'];

                $files = $data['files'];
                unset($data['files']);
                
                $data['emp_id'] = $empid;
                $nominee = Nominee::create($data); 

                foreach($files as $file){
                    $file = base64_decode($file);
                    $fileName = uniqid() . '.jpg';
                    $path = 'nominees/'.$fileName;
                    Storage::disk('public')->put($path, $file);
                    

                    $nominee->files()->create([
                        'path' => $path
                    ]);
                }
            }else{
                $d = $r['nominee'];

                unset($d['files']);
                Nominee::find($r['nominee']['id'])->update($d);
            }
        }

        if(count($r['soccont'])){
            if(isset($r['soccont']['id'])){
                SocialContact::find($r['soccont']['id'])->update($r['soccont']);
            }else{
                $data = $r['soccont'];
                $data['emp_id'] = $empid;
                SocialContact::create($data);
            }
        }

        if(count($request->edus)){
            foreach($request->edus as $edu) {

                if(!isset($edu['id'])){
                    $files = $edu['files'];
                    unset($edu['files']);
                    
                    $edu['emp_id'] = $empid;
                    $education = Education::create($edu); 

                    foreach($files as $file){
                        $file = base64_decode($file);
                        $fileName = uniqid() . '.jpg';
                        $path = 'educations/'.$fileName;
                        Storage::disk('public')->put($path, $file);
                        

                        $education->files()->create([
                            'path' => $path
                        ]);
                    }
                }
                         
            }
        }
        
        if(count($r['workexps'])){
            foreach ($r['workexps'] as $workexp) {
                if(!isset($workexp['id'])){
                    $workexp['emp_id'] = $empid;
                    WorkExperience::create($workexp);
                }            
            }
        }
        if(count($r['refs'])){
            foreach ($r['refs'] as $ref) {
                if(!isset($ref['id'])){
                    $ref['emp_id'] = $empid;
                    Reference::create($ref);    
                }        
            }
        }
        if(count($r['childrens'])){
            foreach ($r['childrens'] as $child) {
                if(!isset($child['id'])){
                    $child['emp_id'] = $empid;
                    Children::create($child);  
                }
            }
        }

        if(count($r['wives'])){
            foreach ($r['wives'] as $wife) {
                if(!isset($wife['id'])){
                    $wife['emp_id'] = $empid;
                    Wife::create($wife);  
                }
            }
        }

        $emp->histories()->create([
            'user_id' => Auth::user()->id,
            'recordstatus' => $emp->recordstatus,
            'details' => 'The information is updated'
        ]);

        if(count($request->econts)){
            foreach($request->econts as $econt) {
                if(!isset($econt['id'])){
                    $files = $econt['files'];
                    unset($econt['files']);
                    
                    $econt['emp_id'] = $empid;
                    $econtact = EmergencyContact::create($econt); 

                    foreach($files as $file){
                        $file = base64_decode($file);
                        $fileName = uniqid() . '.jpg';
                        $path = 'econtactids/'.$fileName;
                        Storage::disk('public')->put($path, $file);
                        

                        $econtact->files()->create([
                            'path' => $path
                        ]);
                    }
                }
                         
            }
        }
        
        return $emp->load(['educations.files', 'workexps', 'refs', 'childrens', 'wives', 'soccontact', 'econs.files', 'lasthistory.creator:id,name',/* 'guarantos.files', */ 'presentjob', 'profilepicture', 'ghcard', 'signature', 'guarsignature','appletters','appointmentletters','probationconfs','cv','petratrust', 'nhis','birthcert','pclearanceform','ssnit', 'unioninfo','workpermit', 'driverlicense.files', 'nominee.files']);

    }

    public function updateempstatus(Request $request)
    {
        
        $emp = Employee::find($request->empid);
        $emp->status = $request->status;
        $emp->save();

        $emp->histories()->create([
            'user_id' => Auth::user()->id,
            'recordstatus' => 0,
            'details' => 'Employee status is updated'
        ]);

        return 'ok';

    }

    public function changeprofilepicture(Request $request)
    {
        // return $request;

        $emp = Employee::find($request->empid);
        

        $emp->profilepicture()->delete();
        

        foreach($request->newpicture as $file){
            $image = base64_decode($file);
            $fileName = uniqid() . '.jpg';
            $path = 'profilepicture/'.$fileName;
            Storage::disk('public')->put($path, $image);

            $emp->profilepicture()->create([
                'type' => 'profilepicture',
                'path' => $path
            ]);
        }

        $emp->histories()->create([
            'user_id' => Auth::user()->id,
            'recordstatus' => 0,
            'details' => 'Profile picture is updated'
        ]);

        return $emp->load('profilepicture');
    }
    
    public function addbanksocial(Request $request){
        
        
        $banksocial = $request->banksocial;
        $banksocial['emp_id'] = $request->emp;
        $banksocial['user_id'] = Auth::user()->id;
        $banksocial['recordstatus'] = 0;

        

        $bs = BankSocial::create($banksocial);

        $bs->histories()->create([
            'user_id' => Auth::user()->id,
            'recordstatus' => 1,
            'details' => 'The information is created'
        ]);
        return $bs->load('lasthistory.creator:id,name');
    }
    public function updatebanksocial(Request $request){
        
        $bs = $request->banksocial;
        
        $banksocial = BankSocial::find($request->banksocial['id']);

        $recordstatus = $banksocial->recordstatus;

        unset($bs['lasthistory']);

        $banksocial->update($bs);

        $banksocial->histories()->create([
            'user_id' => Auth::user()->id,
            'recordstatus' => $recordstatus,
            'details' => 'The information is updated'
        ]);
        return $banksocial->load('lasthistory.creator:id,name');
    }
    public function addirrguarantee(Request $request){
        
        $r = $request;

        // return $r;

        $irrguarantee = $request->irrguar;
        $irrguarantee['emp_id'] = $request->emp;
        $irrguarantee['user_id'] = Auth::user()->id;
        $irrguarantee['recordstatus'] = 0;
        
        unset($irrguarantee['guaramountletters']);

        $signature = $irrguarantee['signature'];
        unset($irrguarantee['signature']);

        $profilepicture = $irrguarantee['profileimage'];
        unset($irrguarantee['profileimage']);
        
        $idcard = $irrguarantee['idcard'];
        unset($irrguarantee['idcard']);

        $guarforms = $irrguarantee['guarform'];
        unset($irrguarantee['guarform']);
        
        
        $ir = IrrGuarantee::create($irrguarantee);
        
        $image_parts = explode(";base64,", $signature);
        $sign_image = base64_decode($image_parts[1]);

        $signfileName = uniqid() . '.jpg';
        $signpath = 'guaranteesignatures/'.$signfileName;
        Storage::disk('public')->put($signpath, $sign_image);

        $ir->signature()->create([
            'type' => 'signature',
            'path' => $signpath
        ]);

        $imageprofile = base64_decode($profilepicture);
        $fileNameprofile = uniqid() . '.jpg';
        $pathprofile = 'guaranteeprofiles/'.$fileNameprofile;
        Storage::disk('public')->put($pathprofile, $imageprofile);

        $ir->profilepicture()->create([
            'type' => 'profilepicture',
            'path' => $pathprofile
        ]);


        foreach($idcard as $file){
            $image = base64_decode($file);
            $fileName = uniqid() . '.jpg';
            $path = 'guaranteeidcards/'.$fileName;
            Storage::disk('public')->put($path, $image);

            $ir->idcard()->create([
                'type' => 'idcard',
                'path' => $path
            ]);
        }

        foreach($guarforms as $file){
            $image = base64_decode($file);
            $fileName = uniqid() . '.jpg';
            $path = 'guaranteeforms/'.$fileName;
            Storage::disk('public')->put($path, $image);

            $ir->guarforms()->create([
                'type' => 'guarform',
                'path' => $path
            ]);
        }

        
        if(count($r['irrguarwitnesses'])){
            foreach ($r['irrguarwitnesses'] as $witness) {
                $witness['irrguar_id'] = $ir->id;
                IrrGuarWitness::create($witness);            
            }
        }

        $ir->histories()->create([
            'user_id' => Auth::user()->id,
            'recordstatus' => 0,
            'details' => 'The information is created'
        ]);

        return $ir->load('profilepicture','lasthistory.creator:id,name');
    }

    public function updateirrguarantee(Request $request){
        
        
        $r = $request;
        
        // return $r;
        $irrguarantee = $request->irrguar;
        $ir = IrrGuarantee::find($irrguarantee['id']);

        unset($irrguarantee['signature']);
        unset($irrguarantee['profilepicture']);
        unset($irrguarantee['idcard']);
        unset($irrguarantee['witnesses']);
        unset($irrguarantee['guarforms']);
        unset($irrguarantee['lasthistory']);
        
        $ir->update($irrguarantee);

        $ir->histories()->create([
            'user_id' => Auth::user()->id,
            'recordstatus' => $ir->recordstatus,
            'details' => 'The information is updated'
        ]);

        if(count($r['irrguarwitnesses'])){
            foreach ($r['irrguarwitnesses'] as $witness) {
                if(!isset($witness['id'])){
                    $witness['irrguar_id'] = $ir->id;
                    IrrGuarWitness::create($witness);
                }
                            
            }
        }

        return $ir->load('profilepicture','witnesses', 'lasthistory.creator:id,name');
    }

    public function uploadappletter(Request $request){
        // return $request['files'];

        $emp = Employee::find($request->emp);

        if(count($request['files'])){

            // return 'okok';
            foreach($request['files'] as $file) {

                $file = base64_decode($file);
                $fileName = uniqid() . '.jpg';
                $path = 'appletters/'.$fileName;
                Storage::disk('public')->put($path, $file);

                $emp->appletters()->create([
                    'type' => 'appletters',
                    'path' => $path
                ]);
                         
            }
        }

        return $emp->load(['appletters']);
    }

    public function uploadappointmentletter(Request $request){
        // return $request['files'];

        $emp = Employee::find($request->emp);

        if(count($request['files'])){

            // return 'okok';
            foreach($request['files'] as $file) {

                $file = base64_decode($file);
                $fileName = uniqid() . '.jpg';
                $path = 'appointmentletters/'.$fileName;
                Storage::disk('public')->put($path, $file);

                $emp->appointmentletters()->create([
                    'type' => 'appointmentletters',
                    'path' => $path
                ]);
                         
            }
        }

        return $emp->load(['appointmentletters']);
    }

    public function uploadprobationconf(Request $request){
        // return $request['files'];

        $emp = Employee::find($request->emp);

        if(count($request['files'])){

            foreach($request['files'] as $file) {

                $file = base64_decode($file);
                $fileName = uniqid() . '.jpg';
                $path = 'probationconfs/'.$fileName;
                Storage::disk('public')->put($path, $file);

                $emp->probationconfs()->create([
                    'type' => 'probationconfs',
                    'path' => $path
                ]);
                         
            }
        }

        return $emp->load(['probationconfs']);
    }

    public function uploadcv(Request $request){
        // return $request['files'];

        $emp = Employee::find($request->emp);

        if(count($request['files'])){

            foreach($request['files'] as $file) {

                $file = base64_decode($file);
                $fileName = uniqid() . '.jpg';
                $path = 'cvs/'.$fileName;
                Storage::disk('public')->put($path, $file);

                $emp->cv()->create([
                    'type' => 'cv',
                    'path' => $path
                ]);
                         
            }
        }

        return $emp->load(['cv']);
    }

    public function uploadpetratrust(Request $request){
        // return $request['files'];

        $emp = Employee::find($request->emp);

        if(count($request['files'])){

            foreach($request['files'] as $file) {

                $file = base64_decode($file);
                $fileName = uniqid() . '.jpg';
                $path = 'petratrusts/'.$fileName;
                Storage::disk('public')->put($path, $file);

                $emp->appletters()->create([
                    'type' => 'petratrust',
                    'path' => $path
                ]);
                         
            }
        }

        return $emp->load(['petratrust']);
    }

    public function uploadnhis(Request $request){
        // return $request['files'];

        $emp = Employee::find($request->emp);

        if(count($request['files'])){

            foreach($request['files'] as $file) {

                $file = base64_decode($file);
                $fileName = uniqid() . '.jpg';
                $path = 'nhis/'.$fileName;
                Storage::disk('public')->put($path, $file);

                $emp->nhis()->create([
                    'type' => 'nhis',
                    'path' => $path
                ]);
                         
            }
        }

        return $emp->load(['nhis']);
    }

    public function uploadbirthcert(Request $request){
        
        $emp = Employee::find($request->emp);

        if(count($request['files'])){

            foreach($request['files'] as $file) {

                $file = base64_decode($file);
                $fileName = uniqid() . '.jpg';
                $path = 'birthcerts/'.$fileName;
                Storage::disk('public')->put($path, $file);

                $emp->birthcert()->create([
                    'type' => 'birthcert',
                    'path' => $path
                ]);
                         
            }
        }

        return $emp->load(['birthcert']);
    }

    public function uploadpclearanceform(Request $request){
        // return $request['files'];

        $emp = Employee::find($request->emp);

        if(count($request['files'])){

            foreach($request['files'] as $file) {

                $file = base64_decode($file);
                $fileName = uniqid() . '.jpg';
                $path = 'pclearanceforms/'.$fileName;
                Storage::disk('public')->put($path, $file);

                $emp->pclearanceform()->create([
                    'type' => 'pclearanceform',
                    'path' => $path
                ]);
                         
            }
        }

        return $emp->load(['pclearanceform']);
    }

    public function uploadssnit(Request $request){
        // return $request['files'];

        $emp = Employee::find($request->emp);

        if(count($request['files'])){

            foreach($request['files'] as $file) {

                $file = base64_decode($file);
                $fileName = uniqid() . '.jpg';
                $path = 'ssnits/'.$fileName;
                Storage::disk('public')->put($path, $file);

                $emp->nhis()->create([
                    'type' => 'ssnit',
                    'path' => $path
                ]);
                         
            }
        }

        return $emp->load(['ssnit']);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Employee  $employee
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        // Try central DB first, fallback to local
        try {
            \Illuminate\Support\Facades\DB::connection('central')->getPdo();
            $empQuery = Employee::on('central');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Central DB Connection Error: ' . $e->getMessage());
            $empQuery = Employee::query();
        }

        $emp = $empQuery->with(
            ['educations.files', 'workexps', 'refs', 'childrens', 'wives', 'soccontact', 'econs.files', 'guarantos.files', 'presentjob', 'banksocial.lasthistory.creator:id,name', 'profilepicture', 'ghcard', 'signature', 'guarsignature', 'appletters','appointmentletters','probationconfs','cv','petratrust','nhis','birthcert','pclearanceform','ssnit','unioninfo','workpermit','driverlicense.files', 'nominee.files','lasthistory.creator:id,name',
            'irrguarantor' => function ($query) {
                $query->with(['witnesses', 'signature', 'idcard','profilepicture', 'guarforms', 'lasthistory.creator:id,name']);   // Each child's picture
            }/* ,'irrguarantor' => function ($query) {
                $query->with(['witnesses', 'signature', 'idcard','profilepicture', 'guarforms']);   // Each child's picture
            } */
            ]
        )->find($request->data);
        return $emp;

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Employee  $employee
     * @return \Illuminate\Http\Response
     */
    public function edit(Employee $employee)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Employee  $employee
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Employee $employee)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Employee  $employee
     * @return \Illuminate\Http\Response
     */
    public function destroy(Employee $employee)
    {
        //
    }
    public function submitempinfo(Request $request){

        $record = Employee::find($request)->first();

        $record->recordstatus = 1;
        $record->save();

        $record->histories()->create([
            'user_id' => Auth::user()->id,
            'recordstatus' => 1,
            'details' => 'The information is submitted'
        ]);

        return true;

    }
    public function verifyempinfo(Request $request){

        $record = Employee::find($request)->first();

        $record->recordstatus = 2;
        $record->save();

        $record->histories()->create([
            'user_id' => Auth::user()->id,
            'recordstatus' => 2,
            'details' => 'The information is verified'
        ]);

        return $record;

    }
    public function approveempinfo(Request $request){

        $record = Employee::find($request)->first();

        $record->recordstatus = 3;
        $record->save();

        $record->histories()->create([
            'user_id' => Auth::user()->id,
            'recordstatus' => 3,
            'details' => 'The information is approved'
        ]);

        return $record;

    }
    public function moveempinfotorecheck(Request $request){

        $record = Employee::find($request)->first();

        $record->recordstatus = 1;
        $record->save();

        $record->histories()->create([
            'user_id' => Auth::user()->id,
            'recordstatus' => 1,
            'details' => 'The information is moved back to recheck status'
        ]);

        return $record;

    }
    public function moveempinfotodraft(Request $request){

        $record = Employee::find($request)->first();

        $record->recordstatus = 0;
        $record->save();

        $record->histories()->create([
            'user_id' => Auth::user()->id,
            'recordstatus' => 0,
            'details' => 'The information is moved back to draft status'
        ]);

        return $record;

    }
    public function loadempinfohistories(Request $request){

        $record = Employee::find($request)->first();

        $histories = $record->histories;

        return $histories->load('creator:id,name');

    }




}

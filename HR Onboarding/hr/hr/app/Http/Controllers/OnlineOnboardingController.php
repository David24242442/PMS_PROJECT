<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\OnlineOnboarding;
use App\Models\Employee;
use App\Models\BankSocial;
use App\Models\Guarantor;
use App\Models\IrrGuarantee;
use App\Models\IrrGuarWitness;
use App\Models\SocialContact;
use App\Models\PresentJob;
use App\Models\Union;
use App\Models\Workpermit;
use App\Models\Driverlicense;
use App\Models\Education;
use App\Models\WorkExperience;
use App\Models\EmergencyContact;
use App\Models\Nominee;
use App\Models\Children;
use App\Models\Wife;
use App\Models\Reference;
use App\Models\Upload;
use App\Models\User;
use App\Helpers\ActivityLogger;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class OnlineOnboardingController extends Controller
{
    /**
     * Auto-ensure online_onboardings table and created_by column exist in database.
     */
    public static function ensureTableExists()
    {
        try {
            if (!Schema::hasTable('online_onboardings')) {
                Schema::create('online_onboardings', function (Blueprint $table) {
                    $table->id();
                    $table->string('reference_number')->unique();
                    $table->string('candidate_name');
                    $table->string('ghcardno')->nullable()->index();
                    $table->string('mobileno')->nullable();
                    $table->string('email')->nullable();
                    $table->string('position')->nullable();
                    $table->string('department')->nullable();
                    $table->string('branch')->nullable();
                    $table->string('company')->nullable();
                    $table->longText('submission_data')->nullable();
                    $table->string('status')->default('pending')->index();
                    $table->text('rejection_reason')->nullable();
                    $table->string('created_by')->nullable();
                    $table->unsignedBigInteger('approved_by_user_id')->nullable();
                    $table->dateTime('approved_at')->nullable();
                    $table->unsignedBigInteger('synced_employee_id')->nullable()->index();
                    $table->string('ip_address')->nullable();
                    $table->timestamps();
                });
            }
            if (Schema::hasTable('employees')) {
                Schema::table('employees', function (Blueprint $table) {
                    if (!Schema::hasColumn('employees', 'created_by')) {
                        $table->string('created_by')->nullable()->after('user_id');
                    }
                });
            }
        } catch (\Throwable $e) {
            \Log::warning('OnlineOnboarding ensureTableExists error: ' . $e->getMessage());
        }
    }

    /**
     * Submit an employee self-onboarding application from the public online portal.
     * Stored as a Pending Online Submission ready for HR review & sync.
     */
    public function submit(Request $request)
    {
        self::ensureTableExists();

        // 1. Validate required candidate fields
        $rules = [
            'emp.firstname' => 'required|string|max:100',
            'emp.surname' => 'required|string|max:100',
            'emp.dob' => 'required|date',
            'emp.gender' => 'required|string',
            'emp.mobileno' => 'required|string|max:25',
            'emp.ghcardno' => 'required|string|max:30',
        ];

        $validator = Validator::make($request->all(), $rules, [
            'emp.firstname.required' => 'First name is required.',
            'emp.surname.required' => 'Surname is required.',
            'emp.dob.required' => 'Date of birth is required.',
            'emp.gender.required' => 'Gender is required.',
            'emp.mobileno.required' => 'Mobile phone number is required.',
            'emp.ghcardno.required' => 'Ghana Card Number is required.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Please correct the highlighted fields.',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if Ghana Card is already in live employees table or pending
        $ghcard = strtoupper(trim($request->input('emp.ghcardno')));
        $existingEmp = Employee::where('ghcardno', $ghcard)->first();
        if ($existingEmp) {
            return response()->json([
                'status' => 'error',
                'message' => "An employee with Ghana Card '{$ghcard}' is already registered in the Melcom HR database (Employee ID: " . ($existingEmp->employeeid ?: $existingEmp->emp_code) . "). Please contact Melcom HR."
            ], 409);
        }

        $existingPending = OnlineOnboarding::where('ghcardno', $ghcard)->where('status', 'pending')->first();
        if ($existingPending) {
            return response()->json([
                'status' => 'error',
                'message' => "An online onboarding submission for Ghana Card '{$ghcard}' is already pending review (Reference: {$existingPending->reference_number})."
            ], 409);
        }

        DB::beginTransaction();
        try {
            // Generate clean unique codes
            $year = date('Y');
            $refNumber = 'MEL-ONB-' . $year . '-' . strtoupper(Str::random(6));

            $empData = $request->input('emp', []);
            $candidateName = strtoupper(trim(($empData['firstname'] ?? '') . ' ' . ($empData['surname'] ?? '')));

            // Process and store base64 files
            $processedData = $request->all();

            if (!empty($empData['profilepicture'])) {
                $savedPath = $this->storeBase64File($empData['profilepicture'], 'profilepicture', 'profile_');
                if ($savedPath) {
                    $processedData['emp']['profilepicture_path'] = $savedPath;
                }
            }

            if (!empty($empData['ghcardfile'])) {
                $savedPath = $this->storeBase64File($empData['ghcardfile'], 'ghcard', 'ghcard_');
                if ($savedPath) {
                    $processedData['emp']['ghcardfile_path'] = $savedPath;
                }
            }

            if (!empty($empData['signature'])) {
                $savedPath = $this->storeBase64File($empData['signature'], 'employeesignatures', 'sign_');
                if ($savedPath) {
                    $processedData['emp']['signature_path'] = $savedPath;
                }
            }

            // Process Guarantor & Irrevocable Guarantee signatures
            $irrData = $request->input('irrguar', []);
            if (!empty($irrData['signature'])) {
                $savedPath = $this->storeBase64File($irrData['signature'], 'guaranteesignatures', 'guarsign_');
                if ($savedPath) {
                    $processedData['irrguar']['signature_path'] = $savedPath;
                }
            }

            // Process Document Uploads
            $docs = $request->input('documents', []);
            if (is_array($docs)) {
                $processedData['documents_paths'] = [];
                foreach ($docs as $docType => $docBase64) {
                    if (empty($docBase64)) continue;
                    $path = $this->storeBase64File($docBase64, $docType, $docType . '_');
                    if ($path) {
                        $processedData['documents_paths'][$docType] = $path;
                    }
                }
            }

            // Create Online Onboarding Record
            $submission = OnlineOnboarding::create([
                'reference_number' => $refNumber,
                'candidate_name' => $candidateName,
                'ghcardno' => $ghcard,
                'mobileno' => trim($empData['mobileno'] ?? ''),
                'email' => strtolower(trim($empData['email'] ?? '')) ?: null,
                'position' => strtoupper(trim($empData['joiningposition'] ?? 'Employee')),
                'department' => $empData['joining_dept_id'] ?? null,
                'branch' => $empData['joining_branch_id'] ?? null,
                'company' => $empData['joining_company_id'] ?? null,
                'submission_data' => $processedData,
                'status' => 'pending',
                'ip_address' => $request->ip(),
            ]);

            // Log activity
            ActivityLogger::log(
                'SUBMIT',
                'ONBOARDING',
                "Candidate {$candidateName} submitted online onboarding application (Ref: {$refNumber}).",
                ['reference_number' => $refNumber, 'ghcardno' => $ghcard]
            );

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Congratulations! Your onboarding application has been successfully submitted to Melcom HR.',
                'reference_number' => $refNumber,
                'candidate_name' => $candidateName,
                'position' => $submission->position,
                'submission_date' => date('d M Y, h:i A')
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Online Onboarding Submission Error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while submitting your onboarding form. Please check your data and try again.',
                'detail' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * List all online onboarding submissions for authorized HR users.
     */
    public function index(Request $request)
    {
        try {
            self::ensureTableExists();

            $query = OnlineOnboarding::query()->orderBy('id', 'desc');

            if ($request->filled('status') && $request->input('status') !== 'all') {
                $query->where('status', strtolower(trim($request->input('status'))));
            }

            if ($request->filled('search')) {
                $search = trim($request->input('search'));
                $query->where(function ($q) use ($search) {
                    $q->where('candidate_name', 'like', "%{$search}%")
                      ->orWhere('reference_number', 'like', "%{$search}%")
                      ->orWhere('ghcardno', 'like', "%{$search}%")
                      ->orWhere('mobileno', 'like', "%{$search}%")
                      ->orWhere('position', 'like', "%{$search}%");
                });
            }

            $perPage = (int)($request->input('per_page', 15));
            $submissions = $query->paginate($perPage);

            $stats = [
                'total' => OnlineOnboarding::count(),
                'pending' => OnlineOnboarding::where('status', 'pending')->count(),
                'approved' => OnlineOnboarding::where('status', 'approved')->count(),
                'rejected' => OnlineOnboarding::where('status', 'rejected')->count(),
            ];

            return response()->json([
                'status' => 'success',
                'data' => $submissions,
                'stats' => $stats
            ]);
        } catch (\Throwable $e) {
            \Log::error('OnlineOnboarding index error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load online onboarding submissions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * View full details of a single online onboarding submission.
     */
    public function show($id)
    {
        try {
            $submission = OnlineOnboarding::findOrFail($id);
            return response()->json([
                'status' => 'success',
                'data' => $submission
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Submission not found: ' . $e->getMessage()
            ], 404);
        }
    }

    /**
     * Approve and sync an online onboarding submission to the live Server 20 employee database.
     * Records the approving user's Name & Employee ID in the employee's created_by column.
     */
    public function approveAndSync($id, Request $request)
    {
        $submission = OnlineOnboarding::findOrFail($id);

        if ($submission->status === 'approved') {
            return response()->json([
                'status' => 'error',
                'message' => "This application was already approved and synced (Employee ID: {$submission->synced_employee_id})."
            ], 400);
        }

        $authUser = $request->user() ?: Auth::user();
        if (!$authUser) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated. Please log into PMS to approve submissions.'
            ], 401);
        }

        // Determine created_by identifier: user name and emp id
        $approverEmpId = $authUser->employee_code ?: ($authUser->emp_id ?: ('USR-' . $authUser->id));
        $createdByText = "{$authUser->name} ({$approverEmpId})";

        DB::beginTransaction();
        try {
            $data = $submission->submission_data ?? [];
            $empData = $data['emp'] ?? [];

            // Generate clean employee code ONB...
            $empCode = 'ONB' . date('y') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);

            // 1. Create Employee Record on Server 20
            $employee = Employee::create([
                'emp_code' => $empCode,
                'firstname' => strtoupper(trim($empData['firstname'] ?? '')),
                'surname' => strtoupper(trim($empData['surname'] ?? '')),
                'othername' => strtoupper(trim($empData['othername'] ?? ($empData['middlename'] ?? ''))),
                'fullname' => strtoupper(trim(($empData['firstname'] ?? '') . ' ' . ($empData['surname'] ?? ''))),
                'email' => strtolower(trim($empData['email'] ?? '')) ?: null,
                'phoneno' => trim($empData['phoneno'] ?? ($empData['mobileno'] ?? '')),
                'mobileno' => trim($empData['mobileno'] ?? ''),
                'altnumber' => trim($empData['whatsappno'] ?? ($empData['altphoneno'] ?? '')),
                'dob' => $empData['dob'] ?? null,
                'gender' => strtoupper(substr($empData['gender'] ?? 'M', 0, 1)),
                'maritalstatus' => (int)($empData['maritalstatus'] ?? 5),
                'religion' => $empData['religion'] ?? '',
                'hometown' => strtoupper(trim($empData['hometown'] ?? '')),
                'region_id' => $empData['region_id'] ?? null,
                'placeofbirth' => strtoupper(trim($empData['placeofbirth'] ?? '')),
                'nationality_id' => (int)($empData['nationality_id'] ?? 1),
                'citizenship' => $empData['citizenship'] ?? 'GH',
                'ghcardno' => strtoupper(trim($empData['ghcardno'] ?? '')),
                'ghcardexpiry' => $empData['ghcardexpiry'] ?? null,
                'socialsecurityno' => strtoupper(trim($empData['socialsecurityno'] ?? '')) ?: null,
                'tin_no' => strtoupper(trim($empData['tin_no'] ?? '')) ?: null,
                'joining_company_id' => (int)($empData['joining_company_id'] ?? 3), // Melcom
                'joining_dept_id' => (int)($empData['joining_dept_id'] ?? 34),
                'joining_branch_id' => (int)($empData['joining_branch_id'] ?? 1),
                'joiningposition' => strtoupper(trim($empData['joiningposition'] ?? 'Employee')),
                'job_title' => strtoupper(trim($empData['joiningposition'] ?? 'Employee')),
                'contracttype' => $empData['contracttype'] ?? 'contract',
                'joiningdate' => $empData['joiningdate'] ?? date('Y-m-d'),
                'resaddress' => strtoupper(trim($empData['resaddress'] ?? '')),
                'daddress' => strtoupper(trim($empData['digitaladdress'] ?? ($empData['gpsaddress'] ?? ''))),
                'hdaddress' => strtoupper(trim($empData['digitaladdress'] ?? ($empData['gpsaddress'] ?? ''))),
                'permanentaddress' => strtoupper(trim($empData['permanentaddress'] ?? '')),
                'fathersname' => strtoupper(trim($empData['fathersname'] ?? '')),
                'mothersname' => strtoupper(trim($empData['mothersname'] ?? '')),
                'user_id' => $authUser->id,
                'created_by' => $createdByText, // USER NAME AND EMP ID OF THE APPROVER!
                'status' => 1,
                'recordstatus' => 2, // 2 = Verified / Approved Online
                'livestatus' => 1
            ]);

            $empId = $employee->id;

            // 2. Attach Profile Picture & Ghana Card
            $profilePath = $empData['profilepicture_path'] ?? null;
            if ($profilePath) {
                $employee->uploads()->create(['type' => 'profilepicture', 'path' => $profilePath]);
            }
            $ghcardPath = $empData['ghcardfile_path'] ?? null;
            if ($ghcardPath) {
                $employee->uploads()->create(['type' => 'ghcard', 'path' => $ghcardPath]);
            }
            $signPath = $empData['signature_path'] ?? null;
            if ($signPath) {
                $employee->uploads()->create(['type' => 'signature', 'path' => $signPath]);
            }

            // 3. Save Emergency Contacts
            $econts = $data['econts'] ?? [];
            if (is_array($econts)) {
                foreach ($econts as $econt) {
                    if (empty($econt['name']) && empty($econt['phoneno'])) continue;
                    EmergencyContact::create([
                        'emp_id' => $empId,
                        'name' => strtoupper(trim($econt['name'] ?? '')),
                        'relationship' => strtoupper(trim($econt['relationship'] ?? ($econt['relation'] ?? 'FAMILY'))),
                        'phoneno' => trim($econt['phoneno'] ?? ($econt['mobile'] ?? '')),
                        'altphoneno' => trim($econt['altphoneno'] ?? ''),
                        'address' => strtoupper(trim($econt['address'] ?? ''))
                    ]);
                }
            }

            // 4. Save Nominee / Next of Kin
            $nominee = $data['nominee'] ?? [];
            if (!empty($nominee['name'])) {
                Nominee::create([
                    'emp_id' => $empId,
                    'name' => strtoupper(trim($nominee['name'] ?? '')),
                    'relationship' => strtoupper(trim($nominee['relationship'] ?? ($nominee['relation'] ?? 'FAMILY'))),
                    'phoneno' => trim($nominee['phoneno'] ?? ($nominee['mobile'] ?? '')),
                    'address' => strtoupper(trim($nominee['address'] ?? ''))
                ]);
            }

            // 5. Save Education
            $edus = $data['edus'] ?? [];
            if (is_array($edus)) {
                foreach ($edus as $edu) {
                    if (empty($edu['schoolname']) && empty($edu['qualification'])) continue;
                    Education::create([
                        'emp_id' => $empId,
                        'schoolname' => strtoupper(trim($edu['schoolname'] ?? '')),
                        'qualification' => strtoupper(trim($edu['qualification'] ?? '')),
                        'coursetitle' => strtoupper(trim($edu['coursetitle'] ?? ($edu['programme'] ?? ''))),
                        'fromdate' => $edu['fromdate'] ?? null,
                        'todate' => $edu['todate'] ?? null
                    ]);
                }
            }

            // 6. Save Work Experience
            $workexps = $data['workexps'] ?? [];
            if (is_array($workexps)) {
                foreach ($workexps as $w) {
                    if (empty($w['companyname']) && empty($w['jobtitle'])) continue;
                    WorkExperience::create([
                        'emp_id' => $empId,
                        'companyname' => strtoupper(trim($w['companyname'] ?? '')),
                        'jobtitle' => strtoupper(trim($w['jobtitle'] ?? '')),
                        'fromdate' => $w['fromdate'] ?? null,
                        'todate' => $w['todate'] ?? null,
                        'reasonforleaving' => trim($w['reasonforleaving'] ?? ''),
                        'salary' => trim($w['salary'] ?? '')
                    ]);
                }
            }

            // 7. Save References
            $refs = $data['refs'] ?? [];
            if (is_array($refs)) {
                foreach ($refs as $ref) {
                    if (empty($ref['name']) && empty($ref['phoneno'])) continue;
                    Reference::create([
                        'emp_id' => $empId,
                        'name' => strtoupper(trim($ref['name'] ?? '')),
                        'position' => strtoupper(trim($ref['position'] ?? ($ref['jobtitle'] ?? ''))),
                        'organization' => strtoupper(trim($ref['organization'] ?? ($ref['company'] ?? ''))),
                        'phoneno' => trim($ref['phoneno'] ?? ''),
                        'email' => strtolower(trim($ref['email'] ?? ''))
                    ]);
                }
            }

            // 8. Save Spouse & Children
            $spouse = $data['spouse'] ?? [];
            if (!empty($spouse['name'])) {
                Wife::create([
                    'emp_id' => $empId,
                    'name' => strtoupper(trim($spouse['name'])),
                    'phoneno' => trim($spouse['phoneno'] ?? ''),
                    'occupation' => strtoupper(trim($spouse['occupation'] ?? '')),
                    'address' => strtoupper(trim($spouse['address'] ?? ''))
                ]);
            }

            $childrens = $data['childrens'] ?? [];
            if (is_array($childrens)) {
                foreach ($childrens as $child) {
                    if (empty($child['name'])) continue;
                    Children::create([
                        'emp_id' => $empId,
                        'name' => strtoupper(trim($child['name'])),
                        'gender' => strtoupper(substr($child['gender'] ?? 'M', 0, 1)),
                        'dob' => $child['dob'] ?? null
                    ]);
                }
            }

            // 8b. Save Social Contact
            $socData = $data['soccont'] ?? [];
            if (!empty($socData['churchmemberloc']) || !empty($socData['pastor']) || !empty($socData['closestfriend']) || !empty($socData['mobileno'])) {
                SocialContact::create([
                    'emp_id' => $empId,
                    'churchmemberloc' => strtoupper(trim($socData['churchmemberloc'] ?? '')),
                    'pastor' => strtoupper(trim($socData['pastor'] ?? '')),
                    'memduration' => $socData['memduration'] ?? null,
                    'mobileno' => trim($socData['mobileno'] ?? ''),
                    'ghcardno' => strtoupper(trim($socData['ghcardno'] ?? '')),
                    'closestfriend' => strtoupper(trim($socData['closestfriend'] ?? '')),
                    'contactsphone' => trim($socData['contactsphone'] ?? ''),
                    'email' => strtolower(trim($socData['email'] ?? '')),
                    'digitaladdress' => strtoupper(trim($socData['digitaladdress'] ?? '')),
                    'workaddress' => strtoupper(trim($socData['workaddress'] ?? ''))
                ]);
            }

            // 8c. Save Job at Melcom (Present Job)
            $curwork = $data['curwork'] ?? [];
            if (!empty($data['iscurwork']) && !empty($curwork['title'])) {
                PresentJob::create([
                    'emp_id' => $empId,
                    'title' => strtoupper(trim($curwork['title'] ?? '')),
                    'employeeid' => strtoupper(trim($curwork['employeeid'] ?? '')),
                    'doj' => $curwork['doj'] ?? null,
                    'dept_id' => (int)($curwork['dept_id'] ?? 34),
                    'branch_id' => (int)($curwork['branch_id'] ?? 1),
                    'region_id' => (int)($curwork['region_id'] ?? 7)
                ]);
            }

            // 8d. Save Union Information
            $unioninfo = $data['unioninfo'] ?? [];
            if (!empty($data['isunion']) && !empty($unioninfo['union_name'])) {
                Union::create([
                    'emp_id' => $empId,
                    'union_name' => strtoupper(trim($unioninfo['union_name'] ?? '')),
                    'union_regdate' => $unioninfo['union_regdate'] ?? null,
                    'union_number' => strtoupper(trim($unioninfo['union_number'] ?? ''))
                ]);
            }

            // 8e. Save Work Permit (if expat)
            $wpermit = $data['wpermit'] ?? [];
            if (($empData['contracttype'] ?? '') === 'expat' && !empty($wpermit['number'])) {
                Workpermit::create([
                    'emp_id' => $empId,
                    'renewalno' => $wpermit['renewalno'] ?? null,
                    'passedate' => $wpermit['passedate'] ?? null,
                    'idate' => $wpermit['idate'] ?? null,
                    'edate' => $wpermit['edate'] ?? null,
                    'number' => strtoupper(trim($wpermit['number'] ?? ''))
                ]);
            }

            // 8f. Save Driver's License (if driver)
            $dlicense = $data['dlicense'] ?? [];
            if (($empData['joiningposition'] ?? '') === 'DRIVER' && !empty($dlicense['licenseno'])) {
                Driverlicense::create([
                    'emp_id' => $empId,
                    'name' => strtoupper(trim($dlicense['name'] ?? '')),
                    'licenseno' => strtoupper(trim($dlicense['licenseno'] ?? '')),
                    'idate' => $dlicense['idate'] ?? null,
                    'edate' => $dlicense['edate'] ?? null,
                    'ref' => strtoupper(trim($dlicense['ref'] ?? '')),
                    'rdate' => $dlicense['rdate'] ?? null
                ]);
            }

            // 9. Save Bank & Social Security Information
            $bankData = $data['banksocial'] ?? [];
            if (!empty($bankData['bankname']) || !empty($bankData['accountnumber'])) {
                $bs = BankSocial::create([
                    'emp_id' => $empId,
                    'bankname' => (int)($bankData['bankname'] ?? 1),
                    'bankbranch' => strtoupper(trim($bankData['bankbranch'] ?? ($bankData['branch'] ?? ''))),
                    'accountname' => strtoupper(trim($bankData['accountname'] ?? $employee->fullname)),
                    'accounttype' => (int)($bankData['accounttype'] ?? 1),
                    'accountnumber' => trim($bankData['accountnumber'] ?? ''),
                    'socialfundnumber' => strtoupper(trim($bankData['socialfundnumber'] ?? ($empData['socialsecurityno'] ?? ''))),
                    'petratrustnumber' => strtoupper(trim($bankData['petratrustnumber'] ?? ($bankData['tier2'] ?? ''))),
                    'user_id' => $authUser->id,
                    'recordstatus' => 2 // Verified
                ]);

                $bs->histories()->create([
                    'user_id' => $authUser->id,
                    'recordstatus' => 2,
                    'details' => "Bank details approved and synced by {$createdByText} (Ref: {$submission->reference_number})"
                ]);
            }

            // 10. Save Guarantor Information
            $guarData = $data['guarantor'] ?? [];
            if (!empty($guarData['name'])) {
                Guarantor::create([
                    'emp_id' => $empId,
                    'name' => strtoupper(trim($guarData['name'])),
                    'relation' => strtoupper(trim($guarData['relation'] ?? ($guarData['relationship'] ?? ''))),
                    'occupation' => strtoupper(trim($guarData['occupation'] ?? '')),
                    'employer' => strtoupper(trim($guarData['employer'] ?? '')),
                    'phoneno' => trim($guarData['phoneno'] ?? ($guarData['mobile'] ?? '')),
                    'address' => strtoupper(trim($guarData['address'] ?? '')),
                    'ghcardno' => strtoupper(trim($guarData['ghcardno'] ?? ''))
                ]);
            }

            // 11. Save Irrevocable Guarantee
            $irrData = $data['irrguar'] ?? [];
            if (!empty($irrData['guarname']) || !empty($irrData['name']) || !empty($irrData['signature_path'])) {
                $ir = IrrGuarantee::create([
                    'emp_id' => $empId,
                    'guarname' => strtoupper(trim($irrData['guarname'] ?? ($irrData['name'] ?? ($guarData['name'] ?? '')))),
                    'ssfno' => strtoupper(trim($irrData['ssfno'] ?? '')),
                    'annualincome' => (float)($irrData['annualincome'] ?? 0),
                    'propertyvalue' => (float)($irrData['propertyvalue'] ?? 0),
                    'occupation' => strtoupper(trim($irrData['occupation'] ?? ($guarData['occupation'] ?? ''))),
                    'mobileno' => trim($irrData['mobileno'] ?? ($guarData['phoneno'] ?? '')),
                    'alternate_mobileno' => trim($irrData['alternate_mobileno'] ?? ''),
                    'primary_email' => strtolower(trim($irrData['primary_email'] ?? '')),
                    'secondary_email' => strtolower(trim($irrData['secondary_email'] ?? '')),
                    'region_id' => $irrData['region_id'] ?? 7,
                    'businessaddr' => strtoupper(trim($irrData['businessaddr'] ?? '')),
                    'residenceaddr' => strtoupper(trim($irrData['residenceaddr'] ?? ($guarData['address'] ?? ''))),
                    'ghcard' => strtoupper(trim($irrData['ghcard'] ?? ($guarData['ghcardno'] ?? ''))),
                    'digitaladdr' => strtoupper(trim($irrData['digitaladdr'] ?? '')),
                    'relation' => $irrData['relation'] ?? 1,
                    'relationyear' => (int)($irrData['relationyear'] ?? 3),
                    'company' => strtoupper(trim($irrData['company'] ?? ($guarData['employer'] ?? ''))),
                    'empposition' => strtoupper(trim($employee->joiningposition ?? '')),
                    'guaramount' => (float)($irrData['guaramount'] ?? 5000),
                    'secondary_contact_person_name' => strtoupper(trim($irrData['secondary_contact_person_name'] ?? '')),
                    'secondary_contact_person_number' => trim($irrData['secondary_contact_person_number'] ?? ''),
                    'user_id' => $authUser->id,
                    'recordstatus' => 2
                ]);

                if (!empty($irrData['signature_path'])) {
                    $ir->uploads()->create([
                        'type' => 'signature',
                        'path' => $irrData['signature_path']
                    ]);
                }

                // Save Irrevocable Guarantee Witnesses
                $witnesses = $data['irrguarwitnesses'] ?? ($data['witnesses'] ?? []);
                if (is_array($witnesses)) {
                    foreach ($witnesses as $wit) {
                        if (empty($wit['name']) && empty($wit['phoneno'])) continue;
                        IrrGuarWitness::create([
                            'irrguar_id' => $ir->id,
                            'name' => strtoupper(trim($wit['name'] ?? '')),
                            'address' => strtoupper(trim($wit['address'] ?? '')),
                            'phoneno' => trim($wit['phoneno'] ?? '')
                        ]);
                    }
                }
            }

            // 12. Save Documents
            $docPaths = $data['documents_paths'] ?? [];
            foreach ($docPaths as $docType => $path) {
                $employee->uploads()->create([
                    'type' => $docType,
                    'path' => $path
                ]);
            }

            // 13. Audit history on employee
            $employee->histories()->create([
                'user_id' => $authUser->id,
                'recordstatus' => 2,
                'details' => "Application approved and synced to Server 20 by {$createdByText} (Online Ref: {$submission->reference_number})"
            ]);

            // 14. Update Online Onboarding Record
            $submission->update([
                'status' => 'approved',
                'created_by' => $createdByText,
                'approved_by_user_id' => $authUser->id,
                'approved_at' => Carbon::now(),
                'synced_employee_id' => $employee->id
            ]);

            // 15. Record Session Activity Log
            ActivityLogger::log(
                'APPROVE',
                'ONBOARDING',
                "User {$createdByText} approved and synced online onboarding for {$employee->fullname} to Server 20 (Emp Code: {$employee->emp_code}).",
                [
                    'online_submission_id' => $submission->id,
                    'reference_number' => $submission->reference_number,
                    'employee_id' => $employee->id,
                    'emp_code' => $employee->emp_code,
                    'approved_by' => $createdByText
                ],
                $authUser
            );

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => "Application successfully approved and synced to Server 20! New Employee Code: {$employee->emp_code}.",
                'employee' => $employee,
                'submission' => $submission
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Approve and Sync Error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to approve and sync application: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject an online onboarding application.
     */
    public function reject($id, Request $request)
    {
        $submission = OnlineOnboarding::findOrFail($id);

        $authUser = $request->user() ?: Auth::user();
        $reason = $request->input('reason', 'Application did not meet requirements.');

        $approverEmpId = $authUser ? ($authUser->employee_code ?: ($authUser->emp_id ?: ('USR-' . $authUser->id))) : 'HR';
        $createdByText = $authUser ? "{$authUser->name} ({$approverEmpId})" : 'HR Admin';

        $submission->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'created_by' => $createdByText,
            'approved_by_user_id' => $authUser ? $authUser->id : null,
            'approved_at' => Carbon::now(),
        ]);

        ActivityLogger::log(
            'REJECT',
            'ONBOARDING',
            "User {$createdByText} rejected online onboarding application for {$submission->candidate_name} (Ref: {$submission->reference_number}). Reason: {$reason}",
            ['submission_id' => $submission->id, 'reason' => $reason],
            $authUser
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Application rejected successfully.',
            'submission' => $submission
        ]);
    }

    /**
     * Return lookup dropdown values for company, departments, branches, regions, id types, and banks.
     */
    public function metadata()
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'companies' => [
                    ['id' => 1, 'name' => 'CENTURY'],
                    ['id' => 2, 'name' => 'CROWN STAR'],
                    ['id' => 3, 'name' => 'MELCOM'],
                    ['id' => 4, 'name' => 'EAD'],
                    ['id' => 5, 'name' => 'SBP'],
                    ['id' => 6, 'name' => 'BTC'],
                    ['id' => 7, 'name' => 'JIREH 7'],
                    ['id' => 8, 'name' => 'IVA GREEN'],
                    ['id' => 9, 'name' => 'JIT'],
                    ['id' => 10, 'name' => 'PHILIPS OUTSOURCING LIMITED']
                ],
                'contracttypes' => [
                    ['code' => 'contract', 'name' => 'Contract'],
                    ['code' => 'permanent', 'name' => 'Permanent'],
                    ['code' => 'probation', 'name' => 'Probationary'],
                    ['code' => 'casual', 'name' => 'Casual'],
                    ['code' => 'expat', 'name' => 'Expat']
                ],
                'marital_statuses' => [
                    ['id' => 5, 'name' => 'SINGLE'],
                    ['id' => 2, 'name' => 'MARRIED'],
                    ['id' => 1, 'name' => 'DIVORCED'],
                    ['id' => 4, 'name' => 'OTHERS'],
                    ['id' => 3, 'name' => 'NOT DISCLOSED']
                ]
            ]
        ]);
    }

    /**
     * Helper to decode and store base64 images/files into storage/app/public
     */
    protected function storeBase64File($base64Data, $folder, $prefix = 'file_')
    {
        try {
            if (empty($base64Data) || !is_string($base64Data)) return null;

            $ext = 'jpg';
            $data = $base64Data;

            if (strpos($base64Data, ';base64,') !== false) {
                list($typePart, $data) = explode(';base64,', $base64Data);
                if (strpos($typePart, 'png') !== false) $ext = 'png';
                elseif (strpos($typePart, 'pdf') !== false) $ext = 'pdf';
                elseif (strpos($typePart, 'webp') !== false) $ext = 'webp';
            }

            $decoded = base64_decode($data);
            if (!$decoded) return null;

            $filename = $prefix . uniqid() . '.' . $ext;
            $relativePath = $folder . '/' . $filename;

            Storage::disk('public')->put($relativePath, $decoded);

            return $relativePath;
        } catch (\Throwable $e) {
            \Log::warning('storeBase64File error: ' . $e->getMessage());
            return null;
        }
    }
}

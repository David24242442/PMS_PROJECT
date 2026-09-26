<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\Employee;
use App\Models\BankSocial;
use App\Models\Guarantor;
use App\Models\IrrGuarantee;
use App\Models\Education;
use App\Models\WorkExperience;
use App\Models\EmergencyContact;
use App\Models\Nominee;
use App\Models\Children;
use App\Models\Wife;
use App\Models\Reference;
use App\Models\Upload;
use App\Models\User;

class OnlineOnboardingController extends Controller
{
    /**
     * Submit an employee self-onboarding application from the public online portal.
     * Publicly accessible, mobile-optimized, and audit-logged.
     */
    public function submit(Request $request)
    {
        // 1. Validate required candidate fields
        $rules = [
            'emp.firstname' => 'required|string|max:100',
            'emp.surname' => 'required|string|max:100',
            'emp.dob' => 'required|date',
            'emp.gender' => 'required|string|in:M,F,Male,Female',
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

        // Check if Ghana Card is already onboarded
        $ghcard = strtoupper(trim($request->input('emp.ghcardno')));
        $existing = Employee::where('ghcardno', $ghcard)->first();
        if ($existing) {
            return response()->json([
                'status' => 'error',
                'message' => "An employee record with Ghana Card '{$ghcard}' is already registered in the Melcom HR database (Employee ID: " . ($existing->employeeid ?: $existing->emp_code) . "). If you have questions, please contact Melcom HR."
            ], 409);
        }

        DB::beginTransaction();
        try {
            // Generate clean unique codes
            $year = date('Y');
            $refNumber = 'MEL-ONB-' . $year . '-' . strtoupper(Str::random(6));
            $empCode = 'ONB' . date('y') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);

            // Default system user ID for foreign key integrity
            $systemUserId = User::where('admin', 1)->value('id') ?: 1;

            $empData = $request->input('emp', []);

            // Map and sanitize employee fields
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
                'ghcardno' => $ghcard,
                'ghcardexpiry' => $empData['ghcardexpiry'] ?? null,
                'socialsecurityno' => strtoupper(trim($empData['socialsecurityno'] ?? '')) ?: null,
                'tin_no' => strtoupper(trim($empData['tin_no'] ?? '')) ?: null,
                'joining_company_id' => (int)($empData['joining_company_id'] ?? 3), // 3 = Melcom
                'joining_dept_id' => (int)($empData['joining_dept_id'] ?? 34), // Default Retail/Operations
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
                'user_id' => $systemUserId,
                'status' => 1,
                'recordstatus' => 1, // 1 = Submitted Online / Pending Verification
                'livestatus' => 1
            ]);

            $empId = $employee->id;

            // 2. Save Profile Picture (supports base64 and standard uploads)
            $profilePic = $request->input('emp.profilepicture');
            if (!empty($profilePic)) {
                $savedPath = $this->storeBase64File($profilePic, 'profilepicture', 'profile_');
                if ($savedPath) {
                    $employee->uploads()->create([
                        'type' => 'profilepicture',
                        'path' => $savedPath
                    ]);
                }
            }

            // 3. Save Ghana Card file
            $ghCardFile = $request->input('emp.ghcardfile');
            if (!empty($ghCardFile)) {
                $savedPath = $this->storeBase64File($ghCardFile, 'ghcard', 'ghcard_');
                if ($savedPath) {
                    $employee->uploads()->create([
                        'type' => 'ghcard',
                        'path' => $savedPath
                    ]);
                }
            }

            // 4. Save Candidate Digital Touch Signature
            $signature = $request->input('emp.signature');
            if (!empty($signature)) {
                $savedPath = $this->storeBase64File($signature, 'employeesignatures', 'sign_');
                if ($savedPath) {
                    $employee->uploads()->create([
                        'type' => 'signature',
                        'path' => $savedPath
                    ]);
                }
            }

            // 5. Save Emergency Contacts
            $econts = $request->input('econts', []);
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

            // 6. Save Next of Kin / Nominee
            $nominee = $request->input('nominee', []);
            if (!empty($nominee['name'])) {
                Nominee::create([
                    'emp_id' => $empId,
                    'name' => strtoupper(trim($nominee['name'] ?? '')),
                    'relationship' => strtoupper(trim($nominee['relationship'] ?? ($nominee['relation'] ?? 'FAMILY'))),
                    'phoneno' => trim($nominee['phoneno'] ?? ($nominee['mobile'] ?? '')),
                    'address' => strtoupper(trim($nominee['address'] ?? ''))
                ]);
            }

            // 7. Save Education Background
            $edus = $request->input('edus', []);
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

            // 8. Save Work Experience
            $workexps = $request->input('workexps', []);
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

            // 9. Save References
            $refs = $request->input('refs', []);
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

            // 10. Save Spouse & Children
            $spouse = $request->input('spouse', []);
            if (!empty($spouse['name'])) {
                Wife::create([
                    'emp_id' => $empId,
                    'name' => strtoupper(trim($spouse['name'])),
                    'phoneno' => trim($spouse['phoneno'] ?? ''),
                    'occupation' => strtoupper(trim($spouse['occupation'] ?? '')),
                    'address' => strtoupper(trim($spouse['address'] ?? ''))
                ]);
            }

            $childrens = $request->input('childrens', []);
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

            // 11. Save Bank & Social Security Information
            $bankData = $request->input('banksocial', []);
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
                    'user_id' => $systemUserId,
                    'recordstatus' => 1
                ]);

                $bs->histories()->create([
                    'user_id' => $systemUserId,
                    'recordstatus' => 1,
                    'details' => "Bank details submitted online (Ref: {$refNumber})"
                ]);
            }

            // 12. Save Guarantor Information
            $guarData = $request->input('guarantor', []);
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

            // 13. Save Irrevocable Guarantee (if filled)
            $irrData = $request->input('irrguar', []);
            if (!empty($irrData['name']) || !empty($irrData['signature'])) {
                $irrRecord = [
                    'emp_id' => $empId,
                    'name' => strtoupper(trim($irrData['name'] ?? ($guarData['name'] ?? ''))),
                    'occupation' => strtoupper(trim($irrData['occupation'] ?? ($guarData['occupation'] ?? ''))),
                    'address' => strtoupper(trim($irrData['address'] ?? ($guarData['address'] ?? ''))),
                    'phoneno' => trim($irrData['phoneno'] ?? ($guarData['phoneno'] ?? '')),
                    'amount' => $irrData['amount'] ?? '5000',
                    'user_id' => $systemUserId,
                    'recordstatus' => 1
                ];

                $irrSign = $irrData['signature'] ?? null;
                $ir = IrrGuarantee::create($irrRecord);

                if (!empty($irrSign)) {
                    $savedPath = $this->storeBase64File($irrSign, 'guaranteesignatures', 'guarsign_');
                    if ($savedPath) {
                        $ir->uploads()->create([
                            'type' => 'signature',
                            'path' => $savedPath
                        ]);
                    }
                }
            }

            // 14. Save Miscellaneous Uploaded Documents
            $docs = $request->input('documents', []);
            if (is_array($docs)) {
                foreach ($docs as $docType => $docBase64) {
                    if (empty($docBase64)) continue;
                    $path = $this->storeBase64File($docBase64, $docType, $docType . '_');
                    if ($path) {
                        $employee->uploads()->create([
                            'type' => $docType,
                            'path' => $path
                        ]);
                    }
                }
            }

            // 15. Record audit history
            $employee->histories()->create([
                'user_id' => $systemUserId,
                'recordstatus' => 1,
                'details' => "Application submitted via Online Melcom Employee Onboarding Portal (Ref: {$refNumber})"
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Congratulations! Your onboarding application has been successfully submitted to Melcom HR.',
                'reference_number' => $refNumber,
                'employee_code' => $empCode,
                'candidate_name' => $employee->fullname,
                'position' => $employee->joiningposition,
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

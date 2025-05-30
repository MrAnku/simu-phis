<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BlueCollarGroup;
use App\Models\DomainVerified;
use App\Models\OutlookAdToken;
use App\Models\Users;
use App\Models\UsersGroup;
use App\Services\EmployeeService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ApiEmployeesController extends Controller
{
    public function index()
    {
        try {
            $companyId = Auth::user()->company_id;
            $groups = UsersGroup::withCount('users')->where('company_id', $companyId)->get();

            $totalEmps = Users::where('company_id', $companyId)->pluck('user_email')->unique()->count();
            $verifiedDomains = DomainVerified::where('verified', 1)->where('company_id', $companyId)->get();
            $notVerifiedDomains = DomainVerified::where('verified', 0)->where('company_id', $companyId)->get();

            $allDomains = DomainVerified::where('company_id', $companyId)->get();

            $hasOutlookAdToken = OutlookAdToken::where('company_id', $companyId)->exists();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_employees' => $totalEmps,
                    'groups' => $groups,
                    'verified_domains' => $verifiedDomains,
                    'not_verified_domains' => $notVerifiedDomains,
                    'all_domains' => $allDomains,
                    'has_outlook_ad_token' => $hasOutlookAdToken
                ],
                'message' => __('Employee data retrieved successfully')
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error:') . $e->getMessage()], 500);
        }
    }

    public function allEmployee()
    {
        try {
            $companyId = Auth::user()->company_id;

            $totalEmps = Users::where('company_id', $companyId)->pluck('user_email')->unique()->count();
            $verifiedDomains = DomainVerified::where('verified', 1)->where('company_id', $companyId)->get();
            $notVerifiedDomains = DomainVerified::where('verified', 0)->where('company_id', $companyId)->get();

            $allDomains = DomainVerified::where('company_id', $companyId)->get();
            $allEmployees = Users::where('company_id', $companyId)
                ->whereIn('id', function ($query) {
                    $query->selectRaw('MAX(id)')
                        ->from('users')
                        ->groupBy('user_email');
                })
                ->get();

            $hasOutlookAdToken = OutlookAdToken::where('company_id', $companyId)->exists();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_employees' => $totalEmps,
                    'verified_domains' => $verifiedDomains,
                    'not_verified_domains' => $notVerifiedDomains,
                    'all_domains' => $allDomains,
                    'has_outlook_ad_token' => $hasOutlookAdToken,
                    'all_employees' => $allEmployees
                ],
                'message' => __('All employee data retrieved successfully')
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function employeeDetail(Request $request)
    {
        try {
            $base_encode_id = $request->route('base_encode_id');
            if (!$base_encode_id) {
                return response()->json(['success' => false, 'message' => __('ID is required')], 422);
            }
            $companyId = Auth::user()->company_id;
            $id = base64_decode($base_encode_id);
            $employee = Users::with(['campaigns', 'assignedTrainings', 'whatsappCamps', 'aiCalls'])->where('id', $id)->where('company_id', $companyId)->first();

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => __('Employee not found')
                ], 404);
            }
            return response()->json([
                'success' => true,
                'data' => $employee,
                'message' => __('Employee details retrieved successfully')
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function sendDomainVerifyOtp(Request $request)
    {
        try {
            $input = $request->all();
            foreach ($input as $key => $value) {
                if (preg_match('/<[^>]*>|<\?php/', $value)) {
                    return response()->json(['success' => false, 'message' => __('Invalid input detected')], 422);
                }
            }
            array_walk_recursive($input, function (&$input) {
                $input = strip_tags($input);
            });
            $request->merge($input);

            $verifyEmail = $request->verificationEmail;

            $domain = explode("@", $verifyEmail)[1];

            $notAllowedDomains = [
                'gmail.com',
                'yahoo.com',
                'outlook.com',
                'hotmail.com',
                'aol.com',
                'yandex.com',
                'icloud.com',
                'protonmail.com'
            ];

            if (in_array($domain, $notAllowedDomains)) {
                return response()->json(['success' => false, 'message' => __('This email provider is not allowed.')], 422);
            }

            $companyId = Auth::user()->company_id; // Assuming company_id is stored in the authenticated user
            $verifiedDomain = DomainVerified::where('domain', $domain)
                ->first();

            if ($verifiedDomain && $verifiedDomain->verified == '1') {
                return response()->json(['success' => false, 'message' => __('Domain already verified or by some other company')], 409);
            }

            if ($verifiedDomain && $verifiedDomain->verified == '0') {
                $genCode = generateRandom(6);
                $verifiedDomain->temp_code = $genCode;
                $verifiedDomain->company_id = $companyId;
                $verifiedDomain->save();

                $this->domainVerificationMail($verifyEmail, $genCode);
            } else {
                $genCode = generateRandom(6);
                DomainVerified::create([
                    'domain' => $domain,
                    'temp_code' => $genCode,
                    'verified' => '0',
                    'company_id' => $companyId,
                ]);

                $this->domainVerificationMail($verifyEmail, $genCode);
            }

            log_action("Domain verification mail sent: {$verifyEmail}");
            return response()->json(['success' => true, 'message' => __('Verification email sent')], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    private function domainVerificationMail($email, $code)
    {
        Mail::send('emails.domainVerification', ['code' => $code], function ($message) use ($email) {
            $message->to($email)->subject('Domain Verification');
        });
    }

    public function verifyOtp(Request $request)
    {
        //xss check start
        try {
            $input = $request->all();
            foreach ($input as $key => $value) {
                if (preg_match('/<[^>]*>|<\?php/', $value)) {
                    return response()->json(['success' => false, 'message' => __('Invalid input detected.')], 422);
                }
            }
            array_walk_recursive($input, function (&$input) {
                $input = strip_tags($input);
            });
            $request->merge($input);

            //xss check end

            $verificationCode = $request->input('emailOTP');
            $companyId = Auth::user()->company_id; // Assuming company_id is stored in the authenticated user

            $verifiedDomain = DomainVerified::where('temp_code', $verificationCode)
                ->where('company_id', $companyId)
                ->first();

            if ($verifiedDomain) {
                $verifiedDomain->verified = '1';
                $verifiedDomain->save();

                log_action("Domain verified successfully : {$verifiedDomain->domain}");

                return response()->json(['success' => true, 'message' => __('Domain verified successfully')], 200);
            } else {
                return response()->json(['success' => false, 'message' => __('Invalid Code')], 422);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function deleteDomain(Request $request)
    {
        try {
            $domain = $request->route('domain');
            if (!$domain) {
                return response()->json(['success' => false, 'message' => __('Domain is required')], 422);
            }

            $isDomainExists = DomainVerified::where('domain', $domain)->exists();
            if (!$isDomainExists) {
                return response()->json(['success' => false, 'message' => __('Domain not found')], 404);
            }

            DB::transaction(function () use ($domain) {
                // Delete users with the domain
                Users::where('user_email', 'LIKE', '%' . $domain)->delete();

                // Delete the domain
                DomainVerified::where('domain', $domain)->delete();
            });

            log_action("Domain {$domain} deleted from platform");

            return response()->json(['success' => true, 'message' => __('Domain and associated users deleted successfully')], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function createNewGroup(Request $request)
    {
        try {
            $request->validate([
                'usrGroupName' => 'required'
            ]);
            $input = $request->all();
            foreach ($input as $key => $value) {
                if (preg_match('/<[^>]*>|<\?php/', $value)) {
                    return response()->json(['success' => false, 'message' => __('Invalid input detected')], 422);
                }
            }
            array_walk_recursive($input, function (&$input) {
                $input = strip_tags($input);
            });
            $request->merge($input);

            $grpName = $request->input('usrGroupName');
            $grpId = generateRandom(6);
            $companyId = Auth::user()->company_id;

            UsersGroup::create([
                'group_id' => $grpId,
                'group_name' => $grpName,
                'users' => null,
                'company_id' => $companyId,
            ]);

            log_action("New employee group {$grpName} created");

            return response()->json(['success' => true, 'message' => __('New Employee Group created successfully')], 201);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function viewUsers(Request $request)
    {
        try {
            $groupId = $request->route('groupId');
            if (!$groupId) {
                return response()->json(['success' => false, 'message' => __('Group ID is required')], 422);
            }
            $companyId = Auth::user()->company_id;
            $group = UsersGroup::where('group_id', $groupId)
                ->where('company_id', $companyId)
                ->first();

            if (!$group) {
                return response()->json(['success' => false, 'message' => __('Group Not found')], 404);
            }

            if ($group->users == null) {
                return response()->json(['success' => true, 'data' => [], 'message' => __('No Employees Found')]);
            }

            $userIdsArray = json_decode($group->users, true);

            $users = Users::whereIn('id', $userIdsArray)->get();

            return response()->json([
                'success' => true,
                'data' => $users,
                'message' => __('Employees retrieved successfully')
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function viewUniqueEmails()
    {
        try {
            $companyId = Auth::user()->company_id;
            $users = Users::where('company_id', $companyId)
                ->whereIn('id', function ($query) {
                    $query->selectRaw('MAX(id)')
                        ->from('users')
                        ->groupBy('user_email');
                })
                ->get();
            if (!$users->isEmpty()) {
                return response()->json(['success' => true, 'data' => $users, 'message' => __('Employees retrieved successfully')], 200);
            } else {
                return response()->json(['success' => false, 'message' => __('No Employees Found')], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function addEmpFromAllEmp(Request $request)
    {
        try {
            foreach ($request->user_ids as $id) {
                $user = Users::find($id);
                if ($user) {
                    $employee = new EmployeeService();
                    // Check if the user is already in the group
                    $emailExists = $employee->emailExistsInGroup($request->groupId, $user->user_email);
                    if ($emailExists) {
                        return response()->json(['success' => false, 'message' => __('This email(s) already exists in this group')], 409);
                    }
                    $addedEmployee = $employee->addEmployee(
                        $user->user_name,
                        $user->user_email,
                        $user->user_company,
                        $user->user_job_title,
                        $user->whatsapp
                    );
                    if ($addedEmployee['status'] == 1) {
                        $addedInGroup = $employee->addEmployeeInGroup($request->groupId, $addedEmployee['user_id']);
                        if ($addedInGroup['status'] == 0) {
                            return response()->json(['success' => false, 'message' => $addedInGroup['msg']], 409);
                        }
                    } else {
                        return response()->json(['success' => false, 'message' => $addedEmployee['msg']], 403);
                    }
                }
            }
            $UsersGroup = UsersGroup::where('group_id', $request->groupId)->first();
            log_action("Employee(s) added to the group : {$UsersGroup->group_name}");
            return response()->json(['success' => true, 'message' => __('Employee(s) successfully added to the group')], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function deleteUser(Request $request)
    {
        if (!$request->route('user_id')) {
            return response()->json(['success' => false, 'message' => __('User ID is required')], 422);
        }
        $user_id = base64_decode($request->route('user_id'));
        $isUserExists =   Users::where('id', $user_id)->where('company_id', Auth::user()->company_id)->first();
        if (!$isUserExists) {
            return response()->json(['success' => false, 'message' => __('User not found')], 404);
        }
        $employee = new EmployeeService();
        try {
            $employee->deleteEmployeeById($user_id);
            log_action("Employee deleted : {$isUserExists->user_name}");
            return response()->json(['success' => true, 'message' => __('Employee deleted successfully')], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function deleteUserByEmail(Request $request)
    {
        try {
            $request->validate([
                'user_email' => 'required'
            ]);
            $user_email = $request->input('user_email');

            $user = Users::where('user_email', $user_email)->where('company_id', Auth::user()->company_id)->first();
            $user_name = $user->user_name;

            $users = Users::where('user_email', $user_email)->where('company_id', Auth::user()->company_id)->get();
            if ($users->isEmpty()) {
                return response()->json(['success' => false, 'message' => __('Employee not found')], 404);
            }
            $employee = new EmployeeService();
            foreach ($users as $user) {
                try {
                    $employee->deleteEmployeeById($user->id);
                } catch (\Exception $e) {
                    return response()->json(['success' => false, 'message' => __('Failed to delete employee')]);
                }
            }

            log_action("Employee deleted : {$user_name}");
            return response()->json(['success' => true, 'message' => __('Employee deleted successfully')], 200);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function addUser(Request $request)
    {
        try {
            //xss check start

            $input = $request->all();
            foreach ($input as $key => $value) {
                if (preg_match('/<[^>]*>|<\?php/', $value)) {
                    return response()->json(['success' => false, 'message' => __('Invalid input detected.')], 422);
                }
            }
            array_walk_recursive($input, function (&$input) {
                $input = strip_tags($input);
            });
            $request->merge($input);

            //xss check end

            $validator = Validator::make($request->all(), [
                'groupId' => 'required',
                'usrName' => 'required|string|max:255',
                'usrEmail' => 'required|email|max:255',
                'usrCompany' => 'nullable|string|max:255',
                'usrJobTitle' => 'nullable|string|max:255',
                'usrWhatsapp' => 'nullable|digits_between:11,15',
            ]);

            $request->merge([
                'usrWhatsapp' => preg_replace('/\D/', '', $request->usrWhatsapp)
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
            }

            $employee = new EmployeeService();
            //if email exists in group
            $emailExists = $employee->emailExistsInGroup($request->groupId, $request->usrEmail);
            if ($emailExists) {
                return response()->json(['success' => false, 'message' => __('This email already exists in this group')], 422);
            }
            $addedEmployee = $employee->addEmployee(
                $request->usrName,
                $request->usrEmail,
                !empty($request->usrCompany) ? $request->usrCompany : null,
                !empty($request->usrJobTitle) ? $request->usrJobTitle : null,
                !empty($request->usrWhatsapp) ? $request->usrWhatsapp : null
            );
            if ($addedEmployee['status'] == 1) {
                $addedInGroup = $employee->addEmployeeInGroup($request->groupId, $addedEmployee['user_id']);

                if ($addedInGroup['status'] == 0) {
                    return response()->json(['success' => false, 'message' => $addedInGroup['msg']]);
                }
                log_action("Employee Added");
                return response()->json(['success' => true, 'message' => __('Employee Added Successfully')], 201);
            } else {
                return response()->json(['success' => false, 'message' => $addedEmployee['msg']]);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function addPlanUser(Request $request)
    {
        try {
            // XSS check start
            $input = $request->all();
            foreach ($input as $key => $value) {
                if (preg_match('/<[^>]*>|<\?php/', $value)) {
                    return response()->json(['success' => false, 'message' => __('Invalid input detected.')], 422);
                }
            }
            array_walk_recursive($input, function (&$input) {
                $input = strip_tags($input);
            });
            $request->merge($input);
            // XSS check end

            $validator = Validator::make($request->all(), [
                'usrName' => 'required|string|max:255',
                'usrEmail' => 'required|email|max:255',
                'usrCompany' => 'nullable|string|max:255',
                'usrJobTitle' => 'nullable|string|max:255',
                'usrWhatsapp' => 'nullable|digits_between:11,15',
            ]);

            $request->merge([
                'usrWhatsapp' => preg_replace('/\D/', '', $request->usrWhatsapp)
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
            }

            //check if this email already exists in table
            $user = Users::where('user_email', $request->usrEmail)->where('company_id', Auth::user()->company_id)->exists();
            if ($user) {
                return response()->json(['success' => false, 'message' => __('This email already exists')], 422);
            }

            $employee = new EmployeeService();

            $addedEmployee = $employee->addEmployee(
                $request->usrName,
                $request->usrEmail,
                !empty($request->usrCompany) ? $request->usrCompany : null,
                !empty($request->usrJobTitle) ? $request->usrJobTitle : null,
                !empty($request->usrWhatsapp) ? $request->usrWhatsapp : null
            );
            if ($addedEmployee['status'] == 1) {
                log_action("Employee Added : { $request->usrName}");
                return response()->json(['success' => true, 'message' => __('Employee Added Successfully')], 201);
            } else {
                return response()->json(['success' => false, 'message' => $addedEmployee['msg']]);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function importCsv(Request $request)
    {
        try {
            $grpId = $request->input('groupId');
            $file = $request->file('usrCsv');
            $companyId = Auth::user()->company_id;

            // Validate that the selected file is a CSV file
            $validator = Validator::make($request->all(), [
                'usrCsv' => 'required|file|mimes:csv,txt',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
            }

            // Path to store the uploaded file
            $path = $file->storeAs('uploads', $file->getClientOriginalName());

            // Read data from CSV file
            if (($handle = fopen(storage_path('app/' . $path), "r")) !== FALSE) {
                // Flag to track if it's the first row
                $firstRow = true;
                $employee = new EmployeeService();

                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    // Skip the first row
                    if ($firstRow) {
                        $firstRow = false;
                        continue;
                    }

                    $name = $data[0];
                    $email = $data[1];
                    $company = !empty($data[2]) ? preg_replace('/[^\w\s]/u', '', $data[2]) : null;
                    $job_title = !empty($data[3]) ? preg_replace('/[^\w\s]/u', '', $data[3]) : null;
                    $whatsapp = !empty($data[4]) ? preg_replace('/\D/', '', $data[4]) : null;

                    if (!$name || !$email) {
                        continue;
                    }

                    if (!empty($grpId)) {
                        // Check if the user is already in the group
                        $emailExists = $employee->emailExistsInGroup($grpId, $email);
                        if ($emailExists) {
                            continue;
                        } else {
                            $addedEmployee = $employee->addEmployee(
                                $name,
                                $email,
                                $company,
                                $job_title,
                                $whatsapp
                            );

                            if ($addedEmployee['status'] == 0) {
                                continue;
                            }
                            if ($addedEmployee['status'] == 1) {
                                $employee->addEmployeeInGroup($grpId, $addedEmployee['user_id']);
                            }
                        }
                    } else {
                        $addedEmployee = $employee->addEmployee(
                            $name,
                            $email,
                            $company,
                            $job_title,
                            $whatsapp
                        );

                        if ($addedEmployee['status'] == 0) {
                            continue;
                        }
                    }
                }
                fclose($handle);

                log_action("Employees added by csv file");
                return response()->json(['success' => true, 'message' => __('CSV file imported successfully!')], 200);
            } else {
                log_action("Unable to open csv file");
                return response()->json(['success' => false, 'message' => __('Invalid file type. Please upload a CSV file.')], 422);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function deleteGroup(Request $request)
    {
        try {
            $grpId = $request->route('groupId');
            if (!$grpId) {
                return response()->json(['success' => false, 'message' => __('Group ID is required')], 422);
            }
            $employee = new EmployeeService();
            $deleted = $employee->deleteGroup($grpId);
            if ($deleted['status'] == 1) {

                log_action("Employee Group deleted");
                return response()->json(['success' => true, 'msg' => $deleted['msg']], 200);
            } else {
                return response()->json(['success' => false, 'msg' => $deleted['msg']], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function checkAdConfig()
    {
        try {
            $companyId = Auth::user()->company_id;

            $ldap_config = DB::table('ldap_ad_config')
                ->where('company_id', $companyId)
                ->first();

            if ($ldap_config) {
                return response()->json([
                    "success" => true,
                    "message" => __('config exists'),
                    "data" => $ldap_config
                ], 200);
            } else {
                return response()->json([
                    "success" => false,
                    "message" => __('config not exists')
                ], 422);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function updateLdapConfig(Request $request)
    {
        try {
            $companyId = Auth::user()->company_id;

            $request->validate([
                'ldap_host' => 'required|min:5|max:50|regex:/^[^<>]*$/',
                'ldap_dn' => 'required|min:5|max:50|regex:/^[^<>]*$/',
                'ldap_admin' => 'required|min:5|max:50|regex:/^[^<>]*$/',
                'ldap_pass' => 'required|min:5|max:50|regex:/^[^<>]*$/',
            ]);

            DB::table('ldap_ad_config')
                ->where('company_id', $companyId)
                ->update([
                    "ldap_host" => $request->ldap_host,
                    "ldap_dn" => $request->ldap_dn,
                    "admin_username" => $request->ldap_admin,
                    "admin_password" => $request->ldap_pass,
                    "updated_at" => now()
                ]);

            log_action("LDAP config updated");
            return response()->json([
                "success" => true,
                "message" => __('LDAP Config Updated')
            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function addLdapConfig(Request $request)
    {
        try {
            $companyId = Auth::user()->company_id;

            $validator = Validator::make($request->all(), [
                'host' => 'required|min:5|max:50',
                'dn' => 'required|min:5|max:50',
                'user' => 'required|min:5|max:50',
                'pass' => 'required|min:5|max:50',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
            }

            DB::table('ldap_ad_config')
                ->insert([
                    "ldap_host" => $request->host,
                    "ldap_dn" => $request->dn,
                    "admin_username" => $request->user,
                    "admin_password" => $request->pass,
                    "updated_at" => now(),
                    "created_at" => now(),
                    "company_id" => $companyId
                ]);

            log_action("LDAP config saved");

            return response()->json([
                'success' => true,
                'message' => "LDAP Config Saved"
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function syncLdap()
    {
        try {
            $companyId = Auth::user()->company_id;
            // Retrieve LDAP/AD configuration from the database
            $ldapConfig = DB::table('ldap_ad_config')->where('company_id', $companyId)->first();

            if (!$ldapConfig) {
                return response()->json([
                    'success' => 0,
                    'message' => __('LDAP configuration not found in the database.'),
                ], 404);
            }

            // Extract LDAP configuration
            $ldapHost = $ldapConfig->ldap_host;
            $ldapDn = $ldapConfig->ldap_dn;
            $adminUsername = $ldapConfig->admin_username;
            $adminPassword = $ldapConfig->admin_password;

            // Initialize LDAP connection
            $ldapConn = ldap_connect($ldapHost);
            ldap_set_option($ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($ldapConn, LDAP_OPT_REFERRALS, 0);

            if (!$ldapConn) {
                return response()->json([
                    'success' => false,
                    'message' => __('Failed to connect to LDAP server.'),
                ], 422);
            }

            // Bind to the LDAP server with admin credentials
            $ldapBind = @ldap_bnd($ldapConn, "CN=$adminUsername,$ldapDn", $adminPassword);

            if (!$ldapBind) {
                return response()->json([
                    'success' => false,
                    'message' => __('LDAP bind failed. Check admin credentials.'),
                ], 422);
            }

            // Search for all users in the AD
            $searchFilter = "(objectClass=user)";
            $attributes = ["samaccountname", "givenName", "sn", "mail"];
            $result = @ldap_search($ldapConn, $ldapDn, $searchFilter, $attributes);

            if (!$result) {
                ldap_unbind($ldapConn);
                return response()->json([
                    'success' => false,
                    'message' => __('LDAP search failed.'),
                ], 422);
            }

            // Get entries from the LDAP result
            $entries = ldap_get_entries($ldapConn, $result);

            if ($entries['count'] === 0) {
                ldap_unbind($ldapConn);
                return response()->json([
                    'success' => false,
                    'message' => __('No users found in the LDAP directory.'),
                ], 422);
            }

            // Process and format user data
            $users = [];
            for ($i = 0; $i < $entries["count"]; $i++) {
                $users[] = [
                    'username' => $entries[$i]["samaccountname"][0] ?? 'N/A',
                    'given_name' => $entries[$i]["givenname"][0] ?? 'N/A',
                    'surname' => $entries[$i]["sn"][0] ?? 'N/A',
                    'email' => $entries[$i]["mail"][0] ?? 'N/A',
                ];
            }

            // Close the LDAP connection
            ldap_unbind($ldapConn);

            log_action("Users synchronized using LDAP");

            // Return the user data as JSON
            return response()->json([
                'success' => true,
                'message' => __('User sync completed successfully.'),
                'data' => $users,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }
}

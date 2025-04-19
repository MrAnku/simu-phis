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
use Illuminate\Validation\ValidationException;

class ApiEmployeesController extends Controller
{
    public function index()
    {
        try {
            $companyId = Auth::user()->company_id;
            $groups = UsersGroup::where('company_id', $companyId)->get();

            $totalEmps = Users::where('company_id', $companyId)->pluck('user_email')->unique()->count();
            $verifiedDomains = DomainVerified::where('verified', 1)->where('company_id', $companyId)->get();
            $notVerifiedDomains = DomainVerified::where('verified', 0)->where('company_id', $companyId)->get();

            $allDomains = DomainVerified::where('company_id', $companyId)->get();

            $hasOutlookAdToken = OutlookAdToken::where('company_id', $companyId)->exists();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_employees' => $totalEmps,
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

            log_action("Domain verification mail sent");
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

                log_action("Domain verified successfully");

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

            if (!$group || $group->users == null) {
                return response()->json(['success' => false, 'message' => __('No Employees Found')], 404);
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
            return response()->json(['success' => true, 'message' => __('Employee(s) successfully added to the group')], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function deleteUser(Request $request)
    {
        if(!$request->route('user_id')) {
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
            return response()->json(['success' => true, 'message' => __('Employee deleted successfully')], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }
}

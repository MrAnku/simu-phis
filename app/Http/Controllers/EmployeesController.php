<?php

namespace App\Http\Controllers;

use App\Models\Users;
use App\Models\Company;
use App\Models\Campaign;
use App\Models\UsersGroup;
use App\Models\CampaignLive;
use Illuminate\Http\Request;
use App\Models\CampaignReport;
use App\Models\DomainVerified;
use Illuminate\Support\Facades\DB;
use App\Models\TrainingAssignedUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class EmployeesController extends Controller
{
    //

    public function index()
    {

        $companyId = Auth::user()->company_id;
        $groups = UsersGroup::withCount('users')
            ->where('company_id', $companyId)
            ->get();

        $totalEmps = $groups->sum('users_count');
        $verifiedDomains = DomainVerified::where('verified', 1)->where('company_id', $companyId)->get();
        $notVerifiedDomains = DomainVerified::where('verified', 0)->where('company_id', $companyId)->get();

        $allDomains = DomainVerified::where('company_id', $companyId)->get();

        return view('employees', compact('groups', 'totalEmps', 'verifiedDomains', 'notVerifiedDomains', 'allDomains'));
    }

    public function sendDomainVerifyOtp(Request $request)
    {

        $verifyEmail = $request->verificationEmail;

        $domain = explode("@", $verifyEmail)[1];

        $notAllowedDomains = [
            'gmail.com',
            'yahoo.com',
            'icloud.com',
            'zoho.com',
            'protonmail.com'
        ];

        if (in_array($domain, $notAllowedDomains)) {
            return response()->json(['status' => 0, 'msg' => 'This email provider is not allowed.']);
        }

        $companyId = auth()->user()->company_id; // Assuming company_id is stored in the authenticated user
        $verifiedDomain = DomainVerified::where('domain', $domain)
            ->where('company_id', $companyId)
            ->first();

        if ($verifiedDomain) {
            if ($verifiedDomain->verified == '0') {
                $genCode = generateRandom(6);
                $verifiedDomain->temp_code = $genCode;
                $verifiedDomain->save();

                $this->domainVerificationMail($verifyEmail, $genCode);
            } else {
                return response()->json(['status' => 0, 'msg' => 'Domain already verified']);
            }
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

        return response()->json(['status' => 1, 'msg' => 'Verification email sent']);
    }

    private function domainVerificationMail($email, $code)
    {
        Mail::send('emails.domainVerification', ['code' => $code], function ($message) use ($email) {
            $message->to($email)->subject('Domain Verification');
        });
    }


    public function verifyOtp(Request $request)
    {

        $verificationCode = $request->input('emailOTP');
        $companyId = auth()->user()->company_id; // Assuming company_id is stored in the authenticated user

        $verifiedDomain = DomainVerified::where('temp_code', $verificationCode)
            ->where('company_id', $companyId)
            ->first();

        if ($verifiedDomain) {
            $verifiedDomain->verified = '1';
            $verifiedDomain->save();

            return response()->json(['status' => 1, 'msg' => 'Domain verified successfully']);
        } else {
            return response()->json(['status' => 0, 'msg' => 'Invalid Code']);
        }
    }

    public function deleteDomain(Request $request)
    {
        $domain = $request->vDomainId;

        DB::transaction(function () use ($domain) {
            // Delete users with the domain
            Users::where('user_email', 'LIKE', '%' . $domain)->delete();

            // Delete the domain
            DomainVerified::where('domain', $domain)->delete();
        });

        return response()->json(['status' => 1, 'msg' => 'Domain and associated users deleted successfully']);
    }

    public function newGroup(Request $request)
    {
        $grpName = $request->input('usrGroupName');
        $grpId = generateRandom(6);
        $companyId = auth()->user()->company_id; // Assuming company_id is stored in the authenticated user

        UsersGroup::create([
            'group_id' => $grpId,
            'group_name' => $grpName,
            'users' => null,
            'company_id' => $companyId,
        ]);

        return redirect()->route('employees');
    }

    public function viewUsers($groupid)
    {
        $companyId = auth()->user()->company_id;
        $users = Users::where('group_id', $groupid)->where('company_id', $companyId)->get();

        if (!$users->isEmpty()) {
            return response()->json(['status' => 1, 'data' => $users]);
        } else {
            return response()->json(['status' => 0, 'msg' => 'no employees found']);
        }
    }

    public function deleteUser(Request $request)
    {
        $user = Users::find($request->user_id);

        if ($user) {
            $user->delete();
            return response()->json(['status' => 1, 'msg' => 'User deleted successfully'], 200);
        } else {
            return response()->json(['status' => 0, 'msg' => 'User not found'], 404);
        }
    }

    public function addUser(Request $request)
    {
        $grpId = $request->input('groupid');
        $usrName = $request->input('usrName');
        $usrEmail = $request->input('usrEmail');
        $usrCompany = $request->input('usrCompany');
        $usrJobTitle = $request->input('usrJobTitle');
        $usrWhatsapp = $request->input('usrWhatsapp');
        $companyId = auth()->user()->company_id; // Assuming the authenticated user has a company_id attribute

        // Retrieve the company record
        $company = Company::where('company_id', $companyId)->first();

        if (!$company) {
            return response()->json(['status' => 0, 'msg' => 'Company not found']);
        }

        // Check if usedemployees is greater than or equal to employees
        if ($company->usedemployees >= $company->employees) {
            return response()->json(['status' => 0, 'msg' => 'Employee limit has been reached']);
        }

        if ($this->domainVerified($usrEmail, $companyId)) {
            if ($this->uniqueEmail($usrEmail)) {
                if ($this->checkLimit($companyId)) {
                    $user = new Users();
                    $user->group_id = $grpId;
                    $user->user_name = $usrName;
                    $user->user_email = $usrEmail;
                    $user->user_company = $usrCompany;
                    $user->user_job_title = $usrJobTitle;
                    $user->whatsapp = $usrWhatsapp;
                    $user->company_id = $companyId;

                    if ($user->save()) {
                        // Increment the usedemployees column for the company
                        $company->increment('usedemployees');

                        return response()->json(['status' => 1, 'msg' => 'Added Successfully']);
                    } else {
                        return response()->json(['status' => 0, 'msg' => 'Failed to add user']);
                    }
                } else {
                    return response()->json(['status' => 0, 'msg' => 'Your limit has exceeded']);
                }
            } else {
                return response()->json(['status' => 0, 'msg' => 'This email already exists / Or added by some other company']);
            }
        } else {
            return response()->json(['status' => 0, 'msg' => 'Domain is not verified']);
        }
    }



    private function domainVerified($email, $companyId)
    {
        $domain = explode("@", $email)[1];
        $checkDomain = DomainVerified::where('domain', $domain)
            ->where('verified', 1)
            ->where('company_id', $companyId)
            ->exists();

        return $checkDomain;
    }

    private function uniqueEmail($email)
    {
        return !Users::where('user_email', $email)->exists();
    }

    private function checkLimit($companyId)
    {
        $userCount = Users::where('company_id', $companyId)->count();
        $userCount++;
        $noOfEmp = Auth::user()->employees; // Assuming no_of_emp is a column in the users table

        return $userCount <= (int)$noOfEmp;
    }

    public function deleteGroup(Request $request)
    {

        $grpId = $request->input('group_id');
        $companyId = Auth::user()->company_id;

        DB::beginTransaction();
        try {
            // Delete the group
            UsersGroup::where('group_id', $grpId)
                ->where('company_id', $companyId)
                ->delete();

            // Find all users in the group
            $users = Users::where('group_id', $grpId)->get();

            // Delete associated data for each user
            foreach ($users as $user) {
                DB::table('user_login')->where('user_id', $user->id)->delete();
                TrainingAssignedUser::where('user_id', $user->id)->delete();
            }

            // Check if any campaigns are using this group
            $campaigns = Campaign::where('users_group', $grpId)
                ->where('company_id', $companyId)
                ->get();

            foreach ($campaigns as $campaign) {
                Campaign::where('campaign_id', $campaign->campaign_id)
                    ->where('company_id', $companyId)
                    ->delete();

                CampaignLive::where('campaign_id', $campaign->campaign_id)
                    ->where('company_id', $companyId)
                    ->delete();

                CampaignReport::where('campaign_id', $campaign->campaign_id)
                    ->where('company_id', $companyId)
                    ->delete();
            }

            // Delete all users in the group
            Users::where('group_id', $grpId)->delete();

            DB::commit();

            return response()->json(['status' => 1, 'msg' => 'Employee group deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['status' => 0, 'msg' => 'An error occurred while deleting the employee group']);
        }
    }

    public function importCsv(Request $request)
    {
        $grpId = $request->input('groupid');
        $file = $request->file('usrCsv');
        $companyId = Auth::user()->company_id;

        // Path to store the uploaded file
        $path = $file->storeAs('uploads', $file->getClientOriginalName());

        // Read data from CSV file
        if (($handle = fopen(storage_path('app/' . $path), "r")) !== FALSE) {
            // Flag to track if it's the first row
            $firstRow = true;

            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                // Skip the first row
                if ($firstRow) {
                    $firstRow = false;
                    continue;
                }

                $name = $data[0];
                $email = $data[1];
                $company = $data[2];
                $job_title = $data[3];
                $whatsapp = $data[4];

                if ($this->domainVerified($email, $companyId)) {
                    if ($this->uniqueEmail($email)) {
                        if ($this->checkLimit($companyId)) {
                            // Users::create([
                            //     'group_id' => $grpId,
                            //     'user_name' => $name,
                            //     'user_email' => $email,
                            //     'user_company' => $company,
                            //     'user_job_title' => $job_title,
                            //     'company_id' => $companyId,
                            // ]);

                            $user = new Users();
                            $user->group_id = $grpId;
                            $user->user_name = $name;
                            $user->user_email = $email;
                            $user->user_company = $company;
                            $user->user_job_title = $job_title;
                            $user->whatsapp = $whatsapp;
                            $user->company_id = $companyId;
                            $user->save();
                        }
                    }
                }
            }
            fclose($handle);
            return redirect()->back()->with('success', 'CSV file imported successfully!');
        } else {
            return redirect()->back()->with('error', 'Error: Unable to open file.');
        }
    }

    public function checkAdConfig()
    {
        $companyId = Auth::user()->company_id;

        $ldap_config = DB::table('ldap_ad_config')
            ->where('company_id', $companyId)
            ->first();

        if ($ldap_config) {
            return response()->json([
                "status" => 1,
                "msg" => "config exists",
                "data" => $ldap_config
            ]);
        } else {
            return response()->json([
                "status" => 0,
                "msg" => "config not exists"
            ]);
        }
    }

    public function saveLdapConfig(Request $request)
    {

        $companyId = Auth::user()->company_id;

        $request->validate([
            'ldap_host' => 'required|min:5|max:50',
            'ldap_dn' => 'required|min:5|max:50',
            'ldap_admin' => 'required|min:5|max:50',
            'ldap_pass' => 'required|min:5|max:50',
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

        return redirect()->back()->with('success', 'LDAP Config Updated');
    }

    public function addLdapConfig(Request $request)
    {

        $companyId = Auth::user()->company_id;


        $validator = Validator::make($request->all(), [
            'host' => 'required|min:5|max:50',
            'dn' => 'required|min:5|max:50',
            'user' => 'required|min:5|max:50',
            'pass' => 'required|min:5|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'msg' => $validator->errors(), // Validation errors
            ]); // 422 Unprocessable Entity
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

        return response()->json([
            'status' => 1,
            'msg' => "LDAP Config Saved"
        ]);
    }

    public function syncLdap()
    {

        $companyId = Auth::user()->company_id;
        // Retrieve LDAP/AD configuration from the database
        $ldapConfig = DB::table('ldap_ad_config')->where('company_id', $companyId)->first();

        if (!$ldapConfig) {
            return response()->json([
                'status' => 0,
                'message' => 'LDAP configuration not found in the database.',
            ]);
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
                'status' => 0,
                'message' => 'Failed to connect to LDAP server.',
            ]);
        }

        // Bind to the LDAP server with admin credentials
        $ldapBind = @ldap_bind($ldapConn, "CN=$adminUsername,$ldapDn", $adminPassword);

        if (!$ldapBind) {
            return response()->json([
                'status' => 0,
                'message' => 'LDAP bind failed. Check admin credentials.',
            ]);
        }

        // Search for all users in the AD
        $searchFilter = "(objectClass=user)";
        $attributes = ["samaccountname", "givenName", "sn", "mail"];
        $result = @ldap_search($ldapConn, $ldapDn, $searchFilter, $attributes);

        if (!$result) {
            ldap_unbind($ldapConn);
            return response()->json([
                'status' => 0,
                'message' => 'LDAP search failed.',
            ]);
        }

        // Get entries from the LDAP result
        $entries = ldap_get_entries($ldapConn, $result);

        if ($entries['count'] === 0) {
            ldap_unbind($ldapConn);
            return response()->json([
                'status' => 0,
                'message' => 'No users found in the LDAP directory.',
            ]);
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

        // Return the user data as JSON
        return response()->json([
            'status' => 1,
            'message' => 'User sync completed successfully.',
            'data' => $users,
        ]);
    }
}

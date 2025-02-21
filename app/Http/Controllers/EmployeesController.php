<?php

namespace App\Http\Controllers;

use App\Models\Users;
use App\Models\Company;
use App\Models\Campaign;
use App\Models\UsersGroup;
use App\Models\CampaignLive;
use Illuminate\Http\Request;
use App\Models\BreachedEmail;
use App\Models\CampaignReport;
use App\Models\DomainVerified;
use App\Models\OutlookAdToken;
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

        $hasOutlookAdToken = OutlookAdToken::where('company_id', $companyId)->exists();

        return view('employees', compact('groups', 'totalEmps', 'verifiedDomains', 'notVerifiedDomains', 'allDomains', 'hasOutlookAdToken'));
    }

    public function sendDomainVerifyOtp(Request $request)
    {
        $input = $request->all();
        foreach ($input as $key => $value) {
            if (preg_match('/<[^>]*>|<\?php/', $value)) {
                return response()->json(['status' => 0, 'msg' => 'Invalid input detected.']);
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
            return response()->json(['status' => 0, 'msg' => 'This email provider is not allowed.']);
        }

        $companyId = auth()->user()->company_id; // Assuming company_id is stored in the authenticated user
        $verifiedDomain = DomainVerified::where('domain', $domain)
            ->first();

        if ($verifiedDomain && $verifiedDomain->verified == '1') {
            return response()->json(['status' => 0, 'msg' => 'Domain already verified or by some other company']);
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
        //xss check start

        $input = $request->all();
        foreach ($input as $key => $value) {
            if (preg_match('/<[^>]*>|<\?php/', $value)) {
                return response()->json(['status' => 0, 'msg' => 'Invalid input detected.']);
            }
        }
        array_walk_recursive($input, function (&$input) {
            $input = strip_tags($input);
        });
        $request->merge($input);

        //xss check end

        $verificationCode = $request->input('emailOTP');
        $companyId = auth()->user()->company_id; // Assuming company_id is stored in the authenticated user

        $verifiedDomain = DomainVerified::where('temp_code', $verificationCode)
            ->where('company_id', $companyId)
            ->first();

        if ($verifiedDomain) {
            $verifiedDomain->verified = '1';
            $verifiedDomain->save();

            log_action("Domain verified successfully");

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

        log_action("Domain {$domain} deleted from platform");

        return response()->json(['status' => 1, 'msg' => 'Domain and associated users deleted successfully']);
    }

    public function newGroup(Request $request)
    {
        $input = $request->all();
        foreach ($input as $key => $value) {
            if (preg_match('/<[^>]*>|<\?php/', $value)) {
                return redirect()->back()->with('error', 'Invalid input detected.');
            }
        }
        array_walk_recursive($input, function (&$input) {
            $input = strip_tags($input);
        });
        $request->merge($input);

        $grpName = $request->input('usrGroupName');
        $grpId = generateRandom(6);
        $companyId = auth()->user()->company_id; // Assuming company_id is stored in the authenticated user

        UsersGroup::create([
            'group_id' => $grpId,
            'group_name' => $grpName,
            'users' => null,
            'company_id' => $companyId,
        ]);

        log_action("New employee group {$grpName} created");

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
        $ifBreached = BreachedEmail::where('email', $user->user_email)->delete();

        if ($user) {
            $ifBreached = BreachedEmail::where('email', $user->user_email)->delete();
            log_action("User {$user->user_email} deleted");
            $user->delete();

            return response()->json(['status' => 1, 'msg' => 'User deleted successfully'], 200);
        } else {

            log_action("User not found to delete");
            return response()->json(['status' => 0, 'msg' => 'User not found'], 404);
        }
    }

    public function addUser(Request $request)
    {
        //xss check start

        $input = $request->all();
        foreach ($input as $key => $value) {
            if (preg_match('/<[^>]*>|<\?php/', $value)) {
                return response()->json(['status' => 0, 'msg' => 'Invalid input detected.']);
            }
        }
        array_walk_recursive($input, function (&$input) {
            $input = strip_tags($input);
        });
        $request->merge($input);

        //xss check end

        $validator = Validator::make($request->all(), [
            'groupid' => 'required',
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
            return response()->json(['status' => 0, 'msg' => $validator->errors()->first()]);
        }
        $companyId = Auth::user()->company_id;

        // Checking the limit of employees
        if (Auth::user()->usedemployees >= Auth::user()->employees) {
            log_action("Employee limit has exceeded");
            return response()->json(['status' => 0, 'msg' => 'Employee limit has been reached']);
        }

        //checking if the domain is verified
        if (!$this->domainVerified($request->usrEmail, $companyId)) {
            return response()->json(['status' => 0, 'msg' => 'Domain is not verified']);
        }

        //checking if the email is unique
        $user = Users::where('user_email', $request->usrEmail)->exists();
        if ($user) {
            return response()->json(['status' => 0, 'msg' => 'This email already exists / Or added by some other company']);
        }

        Users::create(
            [
                'group_id' => $request->groupid,
                'user_name' => $request->usrName,
                'user_email' => $request->usrEmail,
                'user_company' => !empty($request->usrCompany) ? $request->usrCompany : null,
                'user_job_title' => !empty($request->usrJobTitle) ? $request->usrJobTitle : null,
                'whatsapp' => !empty($request->usrWhatsapp) ? $request->usrWhatsapp : null,
                'company_id' => $companyId,
            ]
        );
        Auth::user()->increment('usedemployees');
        log_action("Employee {$request->usrEmail} added");
        return response()->json(['status' => 1, 'msg' => 'Employee Added Successfully']);
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

            log_action("Employee group deleted");
            return response()->json(['status' => 1, 'msg' => 'Employee group deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();

            log_action("An error occurred while deleting the employee group");
            return response()->json(['status' => 0, 'msg' => 'An error occurred while deleting the employee group']);
        }
    }

    public function importCsv(Request $request)
    {
        $grpId = $request->input('groupid');
        $file = $request->file('usrCsv');
        $companyId = Auth::user()->company_id;

        // Validate that the selected file is a CSV file
        $validator = Validator::make($request->all(), [
            'usrCsv' => 'required|file|mimes:csv,txt',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', 'Invalid file type. Please upload a CSV file.');
        }

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

                // Checking the limit of employees
                if (Auth::user()->usedemployees >= Auth::user()->employees) {
                    break;
                }

                $name = $data[0];
                $email = $data[1];
                $company = !empty($data[2]) ? preg_replace('/[^\w\s]/u', '', $data[2]) : null;
                $job_title = !empty($data[3]) ? preg_replace('/[^\w\s]/u', '', $data[3]) : null;
                $whatsapp = !empty($data[4]) ? preg_replace('/\D/', '', $data[4]) : null;

                if (!$name || !$email) {
                    continue;
                }

                if (!$this->domainVerified($email, $companyId)) {
                    continue;
                }

                $user = Users::where('user_email', $email)->exists();
                if ($user) {
                    continue;
                }

                $user = new Users();
                $user->group_id = $grpId;
                $user->user_name = $name;
                $user->user_email = $email;
                $user->user_company = $company;
                $user->user_job_title = $job_title;
                $user->whatsapp = $whatsapp;
                $user->company_id = $companyId;
                $user->save();

                Auth::user()->increment('usedemployees');
            }
            fclose($handle);

            log_action("Employees added by csv file");
            return redirect()->back()->with('success', 'CSV file imported successfully!');
        } else {
            log_action("Unable to open csv file");
            return redirect()->back()->with('error', 'Invalid file type. Please upload a CSV file.');
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

        log_action("LDAP config updated");
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

        log_action("LDAP config saved");

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

        log_action("Users synchronized using LDAP");

        // Return the user data as JSON
        return response()->json([
            'status' => 1,
            'message' => 'User sync completed successfully.',
            'data' => $users,
        ]);
    }
}

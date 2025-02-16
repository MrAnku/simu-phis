<?php

namespace App\Http\Controllers;

use App\Models\Users;
use Illuminate\Http\Request;
use App\Models\DomainVerified;
use App\Models\OutlookAdToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redirect;

class OutlookAdController extends Controller
{
    public function loginMicrosoft()
    {
        $authUrl = env('MS_AUTHORITY') . "authorize?" . http_build_query([
            "client_id" => env('MS_CLIENT_ID'),
            "response_type" => "code",
            "redirect_uri" => env('MS_REDIRECT_URI'),
            "response_mode" => "query",
            "scope" => "openid profile email User.Read Directory.Read.All",
            "state" => csrf_token() // Use CSRF token for security
        ]);

        return Redirect::to($authUrl);
    }
    public function handleMicrosoftCallback(Request $request)
    {
        if (!$request->has('code')) {
            return abort(403, "Authorization failed!");
        }

        $tokenUrl = env('MS_AUTHORITY') . "token";
        $response = Http::asForm()->post($tokenUrl, [
            "client_id" => env('MS_CLIENT_ID'),
            "client_secret" => env('MS_CLIENT_SECRET'),
            "code" => $request->query('code'),
            "redirect_uri" => env('MS_REDIRECT_URI'),
            "grant_type" => "authorization_code",
        ]);

        $tokenData = $response->json();

        if (!isset($tokenData['access_token'])) {
            return abort(500, "Failed to get access token.");
        }

        $accessToken = $tokenData['access_token'];

        // Store the token in Laravel storage
        OutlookAdToken::create([
            'access_token' => $accessToken,
            'company_id' => auth()->user()->company_id
        ]);


        // Redirect to employee list page
        return redirect()->route('employees')->with('success', 'Authorization successful! Now your can sync your employees.');
    }

    public function fetchGroups()
    {
        $company_id = auth()->user()->company_id;
        $token = OutlookAdToken::where('company_id', $company_id)->first();
        if (!$token) {
            return response()->json(['status' => 0, 'msg' => 'You are not authorized to Sync Outlook AD Users']);
        }

        // Define API URL
        $apiUrl = env('MS_GRAPH_API_URL') . "groups";

        // Fetch data from Microsoft Graph API
        $response = Http::withToken($token->access_token)->get($apiUrl);

        if ($response->failed()) {
            $error = $response->json();
            if ($error['error']['code'] == "InvalidAuthenticationToken") {
                OutlookAdToken::where('company_id', $company_id)->delete();
                return response()->json(['status' => 0, 'msg' => 'Your authentication token has expired. Please re-authorize.']);
            }
            return response()->json(['status' => 0, 'msg' => $response->body()]);
        }

        $groups = $response->json();

        // Ensure the response contains group data
        if (!isset($groups['value'])) {
            return response()->json(['status' => 0, 'msg' => 'No groups found.']);
        }

        return response()->json(['status' => 1, 'groups' => $groups['value']]);
    }

    public function fetchEmps($groupId)
    {
        $company_id = auth()->user()->company_id;
        // Validate group ID
        if (!$groupId) {
            return response()->json(['status' => 1, 'msg' => 'Group ID is required.']);
        }

        $groupId = htmlspecialchars($groupId); // Sanitize input

        $token = OutlookAdToken::where('company_id', $company_id)->first();
        if (!$token) {
            return response()->json(['status' => 0, 'msg' => 'You are not authorized to Sync Outlook AD Users']);
        }


        // Define API URL
        $apiUrl = env('MS_GRAPH_API_URL') . "groups/{$groupId}/members";

        // Fetch data from Microsoft Graph API
        $response = Http::withToken($token->access_token)->get($apiUrl);

        if ($response->failed()) {
            return response()->json(['status' => 0, 'msg' => 'Failed to fetch users for the selected group.']);
        }

        $users = $response->json();

        // Ensure the response contains user data
        if (!isset($users['value'])) {
            return response()->json(['status' => 0, 'msg' => 'No users found in this group.']);
        }

        return response()->json(['status' => 1, 'employees' => $users['value']]);
    }

    public function saveOutlookEmps(Request $request)
    {
        //xss check start
        $inputs = $request->employees;
        foreach ($inputs as $key => $input) {
            foreach ($input as $key => $value) {
                if (preg_match('/<[^>]*>|<\?php/', $value)) {
                    return response()->json(['status' => 0, 'msg' => 'Invalid input detected.']);
                }
            }
        }
        //xss check end

        $company_id = auth()->user()->company_id;
        $groupId = $request->groupId;
        if (!$groupId) {
            return response()->json(['status' => 0, 'msg' => 'Group ID is required.']);
        }

        $error = '';

        foreach ($request->employees as $emp) {

            // Validate required fields
            if (!isset($emp['email']) || !isset($emp['name'])) {
                $error = 'Email and Name are required fields.';
                break;
            }

            //checking domain verification
            $domain = explode("@", $emp['email'])[1];
            $checkDomain = DomainVerified::where('domain', $domain)
                ->where('verified', 1)
                ->where('company_id', $company_id)
                ->exists();
            if (!$checkDomain) {
                $error = 'Domain is not verified.';
                break;
            }

            //checking email duplication
            $checkEmail = Users::where('user_email', $emp['email'])
                ->where('company_id', $company_id)
                ->exists();
            if ($checkEmail) {
                $error = 'Email already exists.';
                break;
            }

            //checking employees limit
            if ((int)auth()->user()->usedemployees >= (int)auth()->user()->employees) {
                $error = 'You have reached your employees limit.';
                break;
            }

            Users::firstOrCreate(
                ['user_email' => $emp['email']], // Avoid duplicates
                [
                    'group_id' => $groupId,
                    'user_name' => $emp['name'],
                    'user_email' => $emp['email'],
                    'user_company' => $emp['company'],
                    'user_job_title' => $emp['jobTitle'],
                    'whatsapp' => $emp['whatsapp'],
                    'company_id' => $company_id
                ]
            );

            // Update used employees count
            auth()->user()->increment('usedemployees');
        }

        if($error){
            log_action("Failed to add employees from Outlook AD", $error);
            return response()->json(['status' => 0, 'msg' => $error]);
        }
        log_action("Employees added from Outlook AD");
        return response()->json(['status' => 1, 'msg' => 'Employees saved successfully.']);
    }
}

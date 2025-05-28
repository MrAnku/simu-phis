<?php

namespace App\Http\Controllers;

use App\Models\Users;
use Illuminate\Http\Request;
use App\Models\DomainVerified;
use App\Models\OutlookAdToken;
use App\Services\EmployeeService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;

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
        $employee = new EmployeeService();
        $errors = [];
        foreach ($request->employees as $emp) {
            $validator = Validator::make($emp, [
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'company' => 'nullable|string|max:255',
                'jobTitle' => 'nullable|string|max:255',
                'whatsapp' => 'nullable|digits_between:11,15',
            ]);

            $emp['whatsapp'] = preg_replace('/\D/', '', $emp['whatsapp']);

            if ($validator->fails()) {
                array_push($errors, $validator->errors()->all());
                continue;
            }

            //check if this email already exists in table
            $user = Users::where('user_email', $emp['email'])->where('company_id', Auth::user()->company_id)->exists();
            if ($user) {
                array_push($errors, "Email {$emp['email']} already exists.");
                continue;
            }

            $addedEmployee = $employee->addEmployee(
                $emp['name'],
                $emp['email'],
                !empty($emp['company']) ? $emp['company'] : null,
                !empty($emp['jobTitle']) ? $emp['jobTitle'] : null,
                !empty($emp['whatsapp']) ? $emp['whatsapp'] : null
            );
            if ($addedEmployee['status'] == 1) {

                if($groupId !== null){
                    $addedInGroup = $employee->addEmployeeInGroup($groupId, $addedEmployee['user_id']);
                        if ($addedInGroup['status'] == 0) {
                            array_push($errors, $addedInGroup['msg']);
                            
                        }
                }

                continue;
            } else {
                array_push($errors, $addedEmployee['msg']);
                continue;
            }
        }
        if (count($errors) > 0) {
            return response()->json(['status' => 0, 'msg' => $errors]);
        }

      
        return response()->json(['status' => 1, 'msg' => 'Employees saved successfully.']);
    }
}

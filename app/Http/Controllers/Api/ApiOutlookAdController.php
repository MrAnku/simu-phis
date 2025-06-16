<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OutlookAdToken;
use App\Models\Users;
use App\Services\EmployeeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class ApiOutlookAdController extends Controller
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

        if (!$authUrl) {
            return response()->json([
                'success' => false,
                'message' => __('Failed to generate authentication URL')
            ], 500);
        }

        return response()->json([
            'success' => true,
            'auth_url' => $authUrl
        ]);
    }

    public function saveOutlookCode(Request $request)
    {
        if (!$request->has('code')) {
            return response()->json([
                'success' => false,
                'message' => 'Authorization failed!'
            ], 403);
        }

        $tokenUrl = env('MS_AUTHORITY') . "token";
        $response = Http::asForm()->post($tokenUrl, [
            "client_id" => env('MS_CLIENT_ID'),
            "client_secret" => env('MS_CLIENT_SECRET'),
            "code" => $request->code,
            "redirect_uri" => env('MS_REDIRECT_URI'),
            "grant_type" => "authorization_code",
        ]);

        $tokenData = $response->json();

        if (!isset($tokenData['access_token'])) {
            return response()->json([
                'success' => false,
                'message' => __('Failed to get access token')
            ], 500);
        }

        $accessToken = $tokenData['access_token'];

        // Store the token in Laravel storage
        OutlookAdToken::create([
            'access_token' => $accessToken,
            'company_id' => Auth::user()->company_id
        ]);

        return response()->json([
            'success' => true,
            'message' => __('Authorization Successfull! Now you can sync your employees')
        ], 200);
    }

    public function fetchGroups()
    {
        $company_id = Auth::user()->company_id;
        $token = OutlookAdToken::where('company_id', $company_id)->first();
        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => __('You are not authorized to Sync Outlook AD Users')
            ], 403);
        }

        // Define API URL
        $apiUrl = env('MS_GRAPH_API_URL') . "groups";

        // Fetch data from Microsoft Graph API
        $response = Http::withToken($token->access_token)->get($apiUrl);

        if ($response->failed()) {
            $error = $response->json();
            if ($error['error']['code'] == "InvalidAuthenticationToken") {
                OutlookAdToken::where('company_id', $company_id)->delete();
                return response()->json([
                    'success' => false,
                    'message' => __('Your authentication token has expired. Please re-authorize')
                ], 401);
            }
            return response()->json([
                'success' => false,
                'message' => $response->body()
            ], 500);
        }

        $groups = $response->json();

        // Ensure the response contains group data
        if (!isset($groups['value'])) {
            return response()->json([
                'success' => false,
                'message' => __('No groups found')
            ], 404);
        }

        return response()->json([
            'success' => true,
            'groups' => $groups['value'],
            'message' => __('Groups fetched successfully')
        ], 200);
    }

    public function fetchEmps(Request $request)
    {
        $groupId = $request->route('groupId');
        // Validate group ID
        if (!$groupId) {
            return response()->json(['success' => false, 'message' => __('Group ID is required')], 400);
        }
        $company_id = Auth::user()->company_id;

        $groupId = htmlspecialchars($groupId); // Sanitize input

        $token = OutlookAdToken::where('company_id', $company_id)->first();
        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => __('You are not authorized to Sync Outlook AD Users')
            ], 403);
        }
        // Define API URL
        $apiUrl = env('MS_GRAPH_API_URL') . "groups/{$groupId}/members";

        // Fetch data from Microsoft Graph API
        $response = Http::withToken($token->access_token)->get($apiUrl);

        if ($response->failed()) {
            return response()->json([
                'success' => false,
                'message' => __('Failed to fetch users for the selected group')
            ], 500);
        }

        $users = $response->json();

        // Ensure the response contains user data
        if (!isset($users['value'])) {
            return response()->json([
                'success' => false,
                'message' => __('No users found in this group')
            ], 404);
        }

        return response()->json([
            'success' => true,
            'employees' => $users['value'],
            'message' => __('Employees fetched successfully')
        ], 200);
    }

    public function saveOutlookEmps(Request $request)
    {
        //xss check start
        
        $inputs = $request->employees;
        foreach ($inputs as $key => $input) {
            foreach ($input as $key => $value) {
                if (preg_match('/<[^>]*>|<\?php/', $value)) {
                    continue;
                }
            }
        }
        //xss check end

        $company_id = Auth::user()->company_id;
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
           
            if ($addedEmployee['status'] == true) {

                if ($groupId !== null) {
                    $addedInGroup = $employee->addEmployeeInGroup($groupId, $addedEmployee['user_id']);
                    if ($addedInGroup['status'] == false) {
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
            return response()->json(['success' => false, 'message' => $errors], 422);
        }
        log_action("Employees from the Outlook AD saved");
        return response()->json(['success' => true, 'message' => __('Employees saved successfully')], 201);
    }
}

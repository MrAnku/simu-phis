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
            'company_id' => Auth::user()->company_id
        ]);


        // Redirect to employee list page
        return redirect()->to(env('NEXT_APP_URL') . '/integration?msg=You are successfully authorized to Sync Outlook AD Users');
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

    
}

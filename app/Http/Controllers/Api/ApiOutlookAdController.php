<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OutlookAdToken;
use App\Models\OutlookDmiToken;
use App\Models\Users;
use App\Services\EmployeeService;
use App\Services\OutlookAdService;
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
            "scope" => "offline_access openid profile email User.Read Directory.Read.All",
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
                'message' => __('Authorization failed!')
            ], 403);
        }

        $newAdService = new OutlookAdService(Auth::user()->company_id);
        $tokenSaved = $newAdService->getAccessToken($request->code);
        if (!$tokenSaved) {
            return response()->json([
                'success' => false,
                'message' => __('Failed to save access token')
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => __('Authorization Successful! Now you can sync your employees')
        ], 200);
    }


    public function saveOutlookDmiCode(Request $request)
    {
        if (!$request->has('code')) {
            return response()->json([
                'success' => false,
                'message' => __('Authorization failed!')
            ], 403);
        }

        $tokenUrl = env('MS_DMI_AUTHORITY_URL') . "/token";
        $response = Http::asForm()->post($tokenUrl, [
            'client_id' => env('MS_DMI_CLIENT_ID'),
            'scope' => env('MS_DMI_SCOPE'),
            'code' => $request->code,
            'redirect_uri' => env('MS_DMI_REDIRECT_URI'),
            'grant_type' => 'authorization_code',
            'client_secret' => env('MS_DMI_CLIENT_SECRET'),
        ]);

        $tokenData = $response->json();

        if (!isset($tokenData['access_token'])) {
            return response()->json([
                'success' => false,
                'message' => $tokenData
            ], 500);
        }

        $accessToken = $tokenData['access_token'];

        // Store the token in Laravel storage
        OutlookDmiToken::create([
            'access_token' => $accessToken,
            'company_id' => Auth::user()->company_id
        ]);

        return response()->json([
            'success' => true,
            'message' => __('Authorization Successful! Now you can send phishing emails to outlook users')
        ], 200);
    }

    public function fetchGroups()
    {
        try {
            $company_id = Auth::user()->company_id;

            $newAdService = new OutlookAdService($company_id);

            if (!$newAdService->hasToken()) {
                return response()->json([
                    'success' => false,
                    'message' => __('You are not authorized to Sync Outlook AD Users')
                ], 403);
            }
            if (!$newAdService->isTokenValid()) {
                $tokenRegenerated = $newAdService->refreshAccessToken();
                if (!$tokenRegenerated) {
                    return response()->json([
                        'success' => false,
                        'message' => __('Failed to refresh access token')
                    ], 401);
                }
            }

            $groups = $newAdService->fetchGroups();

            // Option 3: Most concise if you're certain $groups is always an array
            if (count($groups) === 0) {
                return response()->json([
                    'success' => false,
                    'message' => __('No groups found')
                ], 404);
            }

            return response()->json([
                'success' => true,
                'groups' => $groups,
                'message' => __('Groups fetched successfully')
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Failed to fetch groups: ') . $e->getMessage()
            ], 500);
        }
    }


    public function fetchEmps(Request $request)
    {
        try {
            $groupId = $request->route('groupId');
            // Validate group ID
            if (!$groupId) {
                return response()->json(['success' => false, 'message' => __('Group ID is required')], 400);
            }
            $company_id = Auth::user()->company_id;

            $groupId = htmlspecialchars($groupId); // Sanitize input

            $newAdService = new OutlookAdService($company_id);

            if (!$newAdService->hasToken()) {
                return response()->json([
                    'success' => false,
                    'message' => __('You are not authorized to Sync Outlook AD Users')
                ], 403);
            }
            if (!$newAdService->isTokenValid()) {
                $tokenRegenerated = $newAdService->refreshAccessToken();
                if (!$tokenRegenerated) {
                    return response()->json([
                        'success' => false,
                        'message' => __('Failed to refresh access token')
                    ], 401);
                }
            }

            $employees = $newAdService->fetchGroupMembers($groupId);
            if (empty($employees)) {
                return response()->json([
                    'success' => false,
                    'message' => __('No employees found in this group')
                ], 404);
            }

            return response()->json([
                'success' => true,
                'employees' => $employees,
                'total_count' => count($employees),
                'message' => __('Employees fetched successfully')
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
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
        $employee = new EmployeeService($company_id);
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

            // Check if user already exists when adding to a group
            $existingUser = Users::where('user_email', $emp['email'])
                ->where('company_id', Auth::user()->company_id)
                ->first();

            if ($groupId !== null) {
                if ($existingUser) {
                    // User exists, just add to group
                    $addedInGroup = $employee->addEmployeeInGroup($groupId, $existingUser->id);
                    if ($addedInGroup['status'] == false) {
                        array_push($errors, "Email {$emp['email']} already exists in group.");
                    }
                    continue;
                }
            } else {
                if ($existingUser) {
                    array_push($errors, "Email {$emp['email']} already exists.");
                    continue;
                }
            }

            $addedEmployee = $employee->addEmployee(
                $emp['name'],
                $emp['email'],
                !empty($emp['company']) ? $emp['company'] : null,
                !empty($emp['jobTitle']) ? $emp['jobTitle'] : null,
                !empty($emp['whatsapp']) ? $emp['whatsapp'] : null,
                false, // not from all employees
                true // Set to true to skip domain verification
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

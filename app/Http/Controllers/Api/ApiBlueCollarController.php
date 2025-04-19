<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BlueCollarEmployee;
use App\Models\BlueCollarGroup;
use App\Models\OutlookAdToken;
use App\Models\WhatsAppCampaignUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApiBlueCollarController extends Controller
{
    public function index()
    {
        try {
            $companyId = Auth::user()->company_id;

            $groups = BlueCollarGroup::withCount('bluecollarusers')
                ->where('company_id', $companyId)
                ->get();

            $totalEmployeeCount = BlueCollarEmployee::where('company_id', $companyId)->get()->count();
            $totalActiveEmployees = WhatsAppCampaignUser::where("employee_type", "Bluecollar")
                ->where('company_id', $companyId)
                ->get()
                ->count();
            $totalCompromisedEmployees = WhatsAppCampaignUser::where("employee_type", "Bluecollar")
                ->where("emp_compromised", 1)
                ->where('company_id', $companyId)
                ->get()
                ->count();

            $totalEmps = $groups->sum('bluecollarusers_count');

            $hasOutlookAdToken = OutlookAdToken::where('company_id', $companyId)->exists();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_employee_count' => $totalEmployeeCount,
                    'totalEmps' => $totalEmps,
                    'total_active_employees' => $totalActiveEmployees,
                    'total_compromised_employees' => $totalCompromisedEmployees,
                    'groups' => $groups,
                    'has_outlook_ad_token' => $hasOutlookAdToken
                ],
                'message' => __('Employee data retrieved successfully')
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function blueCollarNewGroup(Request $request)
    {
        try {
            $input = $request->all();
            foreach ($input as $key => $value) {
                if (preg_match('/<[^>]*>|<\?php/', $value)) {
                    return response()->json([
                        'success' => false,
                        'message' => __('Invalid input detected')
                    ], 422);
                }
            }
            array_walk_recursive($input, function (&$input) {
                $input = strip_tags($input);
            });
            $request->merge($input);

            $grpName = $request->input('usrGroupName');
            $grpId = generateRandom(6);
            $companyId = Auth::user()->company_id;

            BlueCollarGroup::create([
                'group_id' => $grpId,
                'group_name' => $grpName,
                'users' => null,
                'company_id' => $companyId,
            ]);

            log_action("New employee group {$grpName} created");

            return response()->json([
                'success' => true,
                'message' => __('NeW Blue Collar Group created successfully')
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error:') . $e->getMessage()], 500);
        }
    }

    public function viewBlueCollarUsers(Request $request)
    {
        try {
            $groupId = $request->route('groupId');
            if (!$groupId) {
                return response()->json(['success' => false, 'message' => _('Group ID is required')], 422);
            }
            $companyId = Auth::user()->company_id;
            $users = BlueCollarEmployee::where('group_id', $groupId)->where('company_id', $companyId)->get();

            if (!$users->isEmpty()) {
                return response()->json(['success' => true, 'data' => $users]);
            } else {
                return response()->json(['success' => false, 'message' => __('No Employees Found')], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error : ') . $e->getMessage()], 500);
        }
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BlueCollarEmployee;
use App\Models\BlueCollarGroup;
use App\Models\BlueCollarTrainingUser;
use App\Models\OutlookAdToken;
use App\Models\WhatsappCampaign;
use App\Models\WhatsAppCampaignUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

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
                return response()->json(['success' => true, 'data' => $users, 'message' => __('Employees retrieved successfully')]);
            } else {
                return response()->json(['success' => true, 'data' => [], 'message' => __('No employees found')]);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error : ') . $e->getMessage()], 500);
        }
    }

    public function deleteBlueUser(Request $request)
    {
        try {
            if (!$request->route('user_id')) {
                return response()->json(['success' => false, 'message' => __('User ID is required')], 422);
            }
            $user = BlueCollarEmployee::find($request->route('user_id'));

            if ($user) {
                $user->delete();

                return response()->json(['success' => true, 'message' => __('User deleted successfully')], 200);
            } else {
                log_action("User not found to delete");
                return response()->json(['success' => false, 'message' => __('User not found')], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error : ') . $e->getMessage()], 500);
        }
    }

    public function addBlueCollarUser(Request $request)
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
            $companyId = Auth::user()->company_id;

            // Checking the limit of employees
            if (Auth::user()->usedemployees >= Auth::user()->employees) {
                log_action("Employee limit has exceeded");
                return response()->json(['success' => false, 'message' => __('mployee limit has been reached')]);
            }

            //checking if the email is unique
            $user = BlueCollarEmployee::where('whatsapp', $request->usrWhatsapp)->exists();
            if ($user) {
                return response()->json(['success' => false, 'message' => __('This Whatsapp already exists / Or added by some other company')], 422);
            }

            BlueCollarEmployee::create(
                [
                    'group_id' => $request->groupId,
                    'user_name' => $request->usrName,
                    'user_company' => !empty($request->usrCompany) ? $request->usrCompany : null,
                    'user_job_title' => !empty($request->usrJobTitle) ? $request->usrJobTitle : null,
                    'whatsapp' => !empty($request->usrWhatsapp) ? $request->usrWhatsapp : null,
                    'company_id' => $companyId,
                ]
            );
            Auth::user()->increment('usedemployees');

            return response()->json(['success' => true, 'message' => __('Employee Added Successfully')], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error : ') . $e->getMessage()], 500);
        }
    }

    public function deleteBlueGroup(Request $request)
    {
        $grpId = $request->route('group_id');
        if (!$grpId) {
            return response()->json(['success' => false, 'message' => __('Group ID is required')], 422);
        }
        $companyId = Auth::user()->company_id;
        // return  $grpId;
        DB::beginTransaction();
        try {
            // Delete the group
            $group = BlueCollarGroup::where('group_id', $grpId)
                ->where('company_id', $companyId)
                ->first();
            if (!$group) {
                return response()->json(['success' => false, 'message' => __('Group not found')], 404);
            }
            BlueCollarGroup::where('group_id', $grpId)
                ->where('company_id', $companyId)
                ->delete();

            // Find all users in the group
            $users = BlueCollarEmployee::where('group_id', $grpId)->get();

            if ($users->isNotEmpty()) {
                foreach ($users as $user) {
                    BlueCollarTrainingUser::where('user_id', $user->id)->delete();
                }
            }

            // Check if any campaigns are using this group
            $campaigns = WhatsappCampaign::where('user_group', $grpId)
                ->where('company_id', $companyId)
                ->get();


            if ($campaigns->isNotEmpty()) {
                foreach ($campaigns as $campaign) {
                    WhatsappCampaign::where('camp_id', $campaign->camp_id)
                        ->where('company_id', $companyId)
                        ->delete();

                    WhatsAppCampaignUser::where('camp_id', $campaign->camp_id)
                        ->where('company_id', $companyId)
                        ->delete();
                }
            }
            // return $users;
            // Delete employees in the group regardless of campaigns
            BlueCollarEmployee::where('group_id', $grpId)->delete();

            DB::commit();
            log_action("Employee group deleted");
            return response()->json(['success' => true, 'message' => __('Employee group deleted successfully')], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            log_action("An error occurred while deleting the employee group");
            return response()->json(['success' => false, 'message' => __('Error : ') . $e->getMessage()], 500);
        }
    }
}

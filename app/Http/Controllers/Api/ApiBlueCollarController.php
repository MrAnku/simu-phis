<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\CompanyLicense;
use App\Models\OutlookAdToken;
use App\Models\BlueCollarGroup;
use App\Models\WhatsappCampaign;
use App\Models\BlueCollarEmployee;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\WhatsAppCampaignUser;
use Illuminate\Support\Facades\Auth;
use App\Models\BlueCollarTrainingUser;
use App\Models\DeletedBlueCollarEmployee;
use App\Models\WaCampaign;
use App\Models\WaLiveCampaign;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ApiBlueCollarController extends Controller
{
    public function index()
    {
        try {
            $companyId = Auth::user()->company_id;

            $groups = BlueCollarGroup::withCount('bluecollarusers')
                ->where('company_id', $companyId)
                ->get();

            $totalEmployeeCount = BlueCollarEmployee::where('company_id', $companyId)->count();
            $totalActiveEmployees = WaLiveCampaign::where("employee_type", "bluecollar")
                ->where('company_id', $companyId)
                ->count();
            $totalCompromisedEmployees = WaLiveCampaign::where("employee_type", "bluecollar")
                ->where("compromised", 1)
                ->where('company_id', $companyId)
                ->count();

            $totalEmps = $groups->sum('bluecollarusers_count');

            $hasOutlookAdToken = OutlookAdToken::where('company_id', $companyId)->exists();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_employee_count' => $totalEmployeeCount,
                    'total_employee_count_pp' => $this->getEmpPp(),
                    'totalEmps' => $totalEmps,
                    'total_active_employees' => $totalActiveEmployees,
                    'active_emp_pp' => $this->activeEmpPpBluecollar(),
                    'total_compromised_employees' => $totalCompromisedEmployees,
                    'compromised_pp' => $this->compromisedPp(),
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

    private function activeEmpPpBluecollar()
    {
        $companyId = Auth::user()->company_id;

        // Current 14 days
        $now = now();
        $startCurrent = $now->copy()->subDays(14)->startOfDay();
        $endCurrent = $now->copy()->endOfDay();

        $currentCount = WaLiveCampaign::where('company_id', $companyId)
            ->whereBetween('created_at', [$startCurrent, $endCurrent])
            ->where('employee_type', 'bluecollar')
            ->count();
        // Previous 14 days
        $startPrev = $now->copy()->subDays(28)->startOfDay();
        $endPrev = $now->copy()->subDays(14)->endOfDay();
        $prevCount = WaLiveCampaign::where('company_id', $companyId)
            ->whereBetween('created_at', [$startPrev, $endPrev])
            ->where('employee_type', 'bluecollar')
            ->count();
        // Calculate percent change
        if ($prevCount == 0 && $currentCount == 0) {
            return 0; // No change if both counts are zero
        } elseif ($prevCount == 0) {
            return 100; // 100% increase if previous count is zero
        } elseif ($currentCount == 0) {
            return -100; // 100% decrease if current count is zero
        } else {
            $percentChange = (($currentCount - $prevCount) / $prevCount) *
                100; // Calculate percent change
            return round($percentChange, 2); // Round to 2 decimal places
        }
    }

    private function compromisedPp()
    {
        $companyId = Auth::user()->company_id;

        // Current 14 days
        $now = now();
        $startCurrent = $now->copy()->subDays(14)->startOfDay();
        $endCurrent = $now->copy()->endOfDay();

        $currentCount = WaLiveCampaign::where('company_id', $companyId)
            ->whereBetween('created_at', [$startCurrent, $endCurrent])
            ->where('employee_type', 'bluecollar')
            ->where('compromised', 1)
            ->count();
        // Previous 14 days
        $startPrev = $now->copy()->subDays(28)->startOfDay();
        $endPrev = $now->copy()->subDays(14)->endOfDay();
        $prevCount = WaLiveCampaign::where('company_id', $companyId)
            ->whereBetween('created_at', [$startPrev, $endPrev])
            ->where('employee_type', 'bluecollar')
            ->where('compromised', 1)
            ->count();
        // Calculate percent change
        if ($prevCount == 0 && $currentCount == 0) {
            return 0; // No change if both counts are zero
        } elseif ($prevCount == 0) {
            return 100; // 100% increase if previous count is zero
        } elseif ($currentCount == 0) {
            return -100; // 100% decrease if current count is zero
        } else {
            $percentChange = (($currentCount - $prevCount) / $prevCount) *
                100; // Calculate percent change
            return round($percentChange, 2); // Round to 2 decimal places
        }
    }

    private function getEmpPp()
    {
        $companyId = Auth::user()->company_id;

        // Current 14 days
        $now = now();
        $startCurrent = $now->copy()->subDays(14)->startOfDay();
        $endCurrent = $now->copy()->endOfDay();

        $currentCount = BlueCollarEmployee::where('company_id', $companyId)
            ->whereBetween('created_at', [$startCurrent, $endCurrent])
            ->distinct('whatsapp')
            ->count('whatsapp');
        // Previous 14 days
        $startPrev = $now->copy()->subDays(28)->startOfDay();
        $endPrev = $now->copy()->subDays(14)->endOfDay();
        $prevCount = BlueCollarEmployee::where('company_id', $companyId)
            ->whereBetween('created_at', [$startPrev, $endPrev])
            ->distinct('whatsapp')
            ->count('whatsapp');
        // Calculate percent change
        if ($prevCount == 0 && $currentCount == 0) {
            return 0; // No change if both counts are zero
        } elseif ($prevCount == 0) {
            return 100; // 100% increase if previous count is zero
        } elseif ($currentCount == 0) {
            return -100; // 100% decrease if current count is zero
        } else {
            $percentChange = (($currentCount - $prevCount) / $prevCount) *
                100; // Calculate percent change
            return round($percentChange, 2); // Round to 2 decimal places
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

            // Check for existing groups with the same base name
            $existingGroups = BlueCollarGroup::where('company_id', $companyId)
                ->where('group_name', 'LIKE', "{$grpName}%")
                ->pluck('group_name')
                ->toArray();

            if (in_array($grpName, $existingGroups)) {
                // Find the next available suffix
                $suffix = 1;
                do {
                    $newGrpName = "{$grpName}-({$suffix})";
                    $suffix++;
                } while (in_array($newGrpName, $existingGroups));
                $grpName = $newGrpName;
            }

            BlueCollarGroup::create([
                'group_id' => $grpId,
                'group_name' => $grpName,
                'users' => null,
                'company_id' => $companyId,
            ]);

            log_action("New employee group {$grpName} created");

            return response()->json([
                'success' => true,
                'message' => __('New Blue Collar Division created successfully')
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error:') . $e->getMessage()], 500);
        }
    }

    public function updateBlueCollarGroup(Request $request)
    {
        try {
            $request->validate([
                'group_id' => 'required',
                'group_name' => 'required'
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

            $groupId = $request->input('group_id');
            $groupName = $request->input('group_name');
            $companyId = Auth::user()->company_id;

            $group = BlueCollarGroup::where('group_id', $groupId)
                ->where('company_id', $companyId)
                ->first();

            if (!$group) {
                return response()->json(['success' => false, 'message' => __('Group Not found')], 404);
            }

            $group->group_name = $groupName;
            $group->save();

            log_action("Blue Collar group {$groupName} updated");

            return response()->json(['success' => true, 'message' => __('Blue Collar Group updated successfully')], 200);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
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
                BlueCollarTrainingUser::where('user_whatsapp', $user->whatsapp)
                    ->where('company_id', Auth::user()->company_id)
                    ->delete();

                $emailExists = DeletedBlueCollarEmployee::where('whatsapp', $user->whatsapp)
                    ->where('company_id', Auth::user()->company_id)
                    ->exists();
                if (!$emailExists) {
                    DeletedBlueCollarEmployee::create([
                        'whatsapp' => $user->whatsapp,
                        'company_id' => Auth::user()->company_id,
                    ]);
                }

                log_action("Blue Collar User deleted : {$user->user_name}");

                return response()->json(['success' => true, 'message' => __('Employee deleted successfully')], 200);
            } else {
                log_action("Employee not found to delete");
                return response()->json(['success' => false, 'message' => __('Employee not found')], 404);
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
            // if (Auth::user()->usedemployees >= Auth::user()->employees) {
            //     log_action("Employee limit has exceeded");
            //     return response()->json(['success' => false, 'message' => __('mployee limit has been reached')]);
            // }

            //check License limit
            $company_license = CompanyLicense::where('company_id', $companyId)->first();

            if ($company_license->used_blue_collar_employees >= $company_license->blue_collar_employees) {
                return response()->json(['success' => false, 'message' => __('Blue Collar Employee limit exceeded')], 422);
            }

            // Check License Expiry
            if (now()->toDateString() > $company_license->expiry) {
                return response()->json(['success' => false, 'message' => __('Your License has beeen Expired')], 422);
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

            $userExists = BlueCollarEmployee::where('whatsapp', $request->usrWhatsapp)
                ->where('company_id', Auth::user()->company_id)
                ->exists();

            $deletedEmployee = DeletedBlueCollarEmployee::where('whatsapp', $request->usrWhatsapp)
                ->where('company_id', Auth::user()->company_id)
                ->exists();

            if (!$userExists || !$deletedEmployee) {
                if ($company_license) {
                    $company_license->increment('used_blue_collar_employees');
                }
            }



            // Auth::user()->increment('usedemployees');

            log_action("Blue Collar User added : {$request->usrName}");
            return response()->json(['success' => true, 'message' => __('Employee Added Successfully')], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error : ') . $e->getMessage()], 500);
        }
    }

    public function updateBlueCollarUser(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|string',
                'usrName' => 'required|string|max:255',
                'usrCompany' => 'nullable|string|max:255',
                'usrJobTitle' => 'nullable|string|max:255'
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
            }

            $companyId = Auth::user()->company_id;

            // Check if user exists
            $user = BlueCollarEmployee::where('id', base64_decode($request->id))
                ->where('company_id', $companyId)
                ->first();

            if (!$user) {
                return response()->json(['success' => false, 'message' => __('User not found')], 404);
            }

            // Update user details
            $user->update([
                'user_name' => $request->usrName,
                'user_company' => $request->usrCompany,
                'user_job_title' => $request->usrJobTitle,
            ]);

            //update in wa campaign
            WaLiveCampaign::where('employee_type', 'bluecollar')
                ->where('user_id', $user->id)
                ->where('company_id', $companyId)
                ->update(['user_name' => $request->usrName]);

            log_action("Blue Collar User updated : {$request->usrName}");
            return response()->json(['success' => true, 'message' => __('Employee Updated Successfully')], 200);
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

            log_action("Blue Collar group deleted : {$group->group_name}");

            $existsCampaign = WaCampaign::where('users_group', $grpId)
                ->where('company_id', $companyId)
                ->exists();
            if ($existsCampaign) {
                return response()->json(['success' => false, 'message' => __('This division is associated with a campaign delete campaign first')], 422);
            }

            BlueCollarGroup::where('group_id', $grpId)
                ->where('company_id', $companyId)
                ->delete();

            // Delete employees in the group regardless of campaigns
            $users = BlueCollarEmployee::where('group_id', $grpId)->get();
            $whatsapp = $users->pluck('whatsapp')->toArray();
            BlueCollarTrainingUser::whereIn('user_whatsapp', $whatsapp)
                ->where('company_id', Auth::user()->company_id)
                ->delete();

            DB::commit();
            return response()->json(['success' => true, 'message' => __('Blue Collar Division deleted successfully')], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            log_action("An error occurred while deleting the Blue Collar group");
            return response()->json(['success' => false, 'message' => __('Error : ') . $e->getMessage()], 500);
        }
    }
}

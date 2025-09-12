<?php

namespace App\Services;

use App\Models\Users;
use App\Models\Campaign;
use App\Models\UsersGroup;
use App\Models\QuishingCamp;
use App\Models\AiCallCampaign;
use App\Models\AssignedPolicy;
use App\Models\CompanyLicense;
use App\Models\DeletedEmployee;
use App\Models\DomainVerified;
use App\Models\InfoGraphicCampaign;
use App\Models\PolicyCampaign;
use App\Models\ScormAssignedUser;
use App\Models\SmishingCampaign;
use App\Models\TrainingAssignedUser;
use App\Models\WaCampaign;

class EmployeeService
{
    protected $companyId;
    protected $newUser = false;

    //constructor
    public function __construct($companyId)
    {
        $this->companyId = $companyId;
    }

    public function addEmployee($name, $email, $company = null, $jobTitle = null, $whatsapp = null, $fromAllEmployees = false, $fromOutlookAd = false)
    {
        if (!$fromAllEmployees) {
            //check License limit
            if ($this->isLimitExceeded()) {
                return [
                    'status' => 0,
                    'msg' => __('Employee limit exceeded')
                ];
            }

            // check expiry
            if ($this->isExpired()) {
                return [
                    'status' => 0,
                    'msg' => __('Your License has beeen Expired')
                ];
            }

            if (!$fromOutlookAd) {
                //domain verified
                if (!$this->domainVerified($email)) {
                    return [
                        'status' => 0,
                        'msg' => __('This domain is not verified')
                    ];
                }
            }

            //increase the limit
            $this->increaseLimit($email);
        }

        $user = Users::create(
            [
                'user_name' => $name,
                'user_email' => $email,
                'user_company' => $company,
                'user_job_title' => $jobTitle,
                'whatsapp' => $whatsapp,
                'company_id' => $this->companyId,
            ]
        );

        // Notify when 95% of license used
        $company_license = CompanyLicense::where('company_id', $this->companyId)->first();
        if ($company_license->used_employees == $company_license->employees * 0.95) {
            sendNotification('95% of your employee license has been used.', $this->companyId);
        }
        if ($this->newUser) {
            $trigger = new TriggerService('new_user', 'normal', $this->companyId);
            $trigger->executeTriggerActions();
        }
        return [
            'status' => 1,
            'user_id' => $user->id,
            'msg' => __('Employee added successfully')
        ];
    }

    private function domainVerified($email)
    {
        $domain = explode("@", $email)[1];
        $checkDomain = DomainVerified::where('domain', $domain)
            ->where('verified', 1)
            ->where('company_id', $this->companyId)
            ->exists();

        return $checkDomain;
    }
    public function addEmployeeInGroup($groupId, $employeeId)
    {
        $group = UsersGroup::where('group_id', $groupId)->first();
        if (!$group) {
        }
        if ($group->users !== null) {
            $usersArray = json_decode($group->users, true);
            //check if employee already exists in group
            if (in_array($employeeId, $usersArray)) {
                return [
                    'status' => 0,
                    'msg' => __('Employee already exists in group')
                ];
            }
            $usersArray[] = $employeeId;
            $group->users = json_encode($usersArray);
            $group->save();
            return [
                'status' => 1,
                'msg' => __('Employee added to group successfully')
            ];
        } else {
            $group->users = json_encode([$employeeId]);
            $group->save();
            return [
                'status' => 1,
                'msg' => __('Employee added to group successfully')
            ];
        }
    }

    public function deleteGroup($groupId)
    {
        $group = UsersGroup::where('group_id', $groupId)->first();
        if (!$group) {
            return [
                'status' => 0,
                'msg' => __('Division not found')
            ];
        }
        $exists = $this->checkCampaignsExists($groupId);
        if ($exists) {
            return [
                'status' => 0,
                'msg' => __('This division is associated with campaigns, please delete campaigns first')
            ];
        }
        //check if this group is associated with autosync
        $autoSyncExists = $group->autoSyncProviders()->exists();
        if ($autoSyncExists) {
            return [
                'status' => 0,
                'msg' => __('This division is associated with auto-sync, please change the division in auto sync config.')
            ];
        }
        //delete this group users also
        if ($group->users !== null) {
            $usersArray = json_decode($group->users, true);
            foreach ($usersArray as $userId) {
                $this->deleteEmployeeById($userId);
            }
        }
        $group->delete();

        return [
            'status' => 1,
            'msg' => __('Division deleted successfully')
        ];
    }
    public function checkCampaignsExists($groupId)
    {
        $companyId = $this->companyId;
        $campaigns = [
            Campaign::class,
            QuishingCamp::class,
            AiCallCampaign::class,
            PolicyCampaign::class,
            SmishingCampaign::class,
            WaCampaign::class,
            InfoGraphicCampaign::class
        ];

        foreach ($campaigns as $campaign) {
            if ($campaign == AiCallCampaign::class) {
                if ($campaign::where('users_group', $groupId)->exists()) {
                    return true;
                }
            } else {
                if ($campaign::where('users_group', $groupId)->where('company_id', $companyId)->exists()) {
                    return true;
                }
            }
        }
        return false;
    }

    public function deleteEmployeeById($employeeId)
    {

        $user = Users::where('id', $employeeId)->where('company_id', $this->companyId)->first();
        // $ifBreached = BreachedEmail::where('email', $user->user_email)->delete();

        if ($user) {

            //remove this id users column array from all groups
            $groups = UsersGroup::where('company_id', $this->companyId)->get();
            if ($groups) {
                foreach ($groups as $group) {
                    if ($group->users !== null) {
                        $usersArray = json_decode($group->users, true);
                        if (in_array($user->id, $usersArray)) {
                            $key = array_search($user->id, $usersArray);
                            unset($usersArray[$key]);
                            if (count($usersArray) >= 1) {
                                $group->users = json_encode(array_values($usersArray));
                                $group->save();
                            } else {
                                $group->users = null;
                                $group->save();
                            }
                        }
                    }
                }
            }


            $user->delete();
        }
    }
    public function deleteAssignedTrainingAndPolicy($email)
    {

        TrainingAssignedUser::where('user_email', $email)
            ->where('company_id', $this->companyId)
            ->delete();
        ScormAssignedUser::where('user_email', $email)
            ->where('company_id', $this->companyId)
            ->delete();
        AssignedPolicy::where('user_email', $email)
            ->where('company_id', $this->companyId)
            ->delete();
    }

    public function emailExistsInGroup($groupId, $email)
    {
        $users = Users::where('user_email', $email)->where('company_id', $this->companyId)->get();
        if (!$users) {
            return false;
        }
        $group = UsersGroup::where('group_id', $groupId)->first();
        if ($group->users === null) {
            return false;
        }
        $usersArray = json_decode($group->users, true);
        foreach ($users as $user) {

            if (in_array($user->id, $usersArray)) {
                return true;
            }
        }
        return false;
    }
    private function isLimitExceeded()
    {
        $company_id = $this->companyId;
        $company_license = CompanyLicense::where('company_id', $company_id)->first();

        if ($company_license->used_employees >= $company_license->employees) {
            return true;
        } else {
            return false;
        }
    }

    public function isExpired()
    {
        $company_id = $this->companyId;
        $company_license = CompanyLicense::where('company_id', $company_id)->first();
        if (!$company_license) {
            return true;
        }

        return now()->toDateString() > $company_license->expiry;
    }

    private function increaseLimit($email)
    {
        $userExists = Users::where('user_email', $email)
            ->where('company_id', $this->companyId)
            ->exists();

        $deletedEmployee = DeletedEmployee::where('email', $email)
            ->where('company_id', $this->companyId)
            ->exists();

        if (!$userExists && !$deletedEmployee) {
            $company_license = CompanyLicense::where('company_id', $this->companyId)->first();
            if ($company_license) {
                $company_license->increment('used_employees');
                $this->newUser = true;
            }
        }
    }
}

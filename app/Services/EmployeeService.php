<?php

namespace App\Services;

use App\Models\Users;
use App\Models\Campaign;
use App\Models\UserLogin;
use App\Models\UsersGroup;
use App\Models\CampaignLive;
use App\Models\QuishingCamp;
use App\Models\BreachedEmail;
use App\Models\AiCallCampaign;
use App\Models\AiCallCampLive;
use App\Models\CampaignReport;
use App\Models\DomainVerified;
use App\Models\QuishingLiveCamp;
use App\Models\WhatsappCampaign;
use App\Models\TrainingAssignedUser;
use App\Models\WhatsAppCampaignUser;
use Illuminate\Support\Facades\Auth;

class EmployeeService
{

    public function addEmployee($name, $email, $company = null, $jobTitle = null, $whatsapp = null)
    {
        //check limit
        if ($this->isLimitExceeded()) {
            return [
                'status' => 0,
                'msg' => 'Employee limit exceeded'
            ];
        }
        //domain verified
        if (!$this->domainVerified($email)) {
            return [
                'status' => 0,
                'msg' => 'This domain is not verified'
            ];
        }
        //increase the limit
        $this->increaseLimit($email);

        $user = Users::create(
            [
                'user_name' => $name,
                'user_email' => $email,
                'user_company' => $company,
                'user_job_title' => $jobTitle,
                'whatsapp' => $whatsapp,
                'company_id' => Auth::user()->company_id,
            ]
        );
        return [
            'status' => 1,
            'user_id' => $user->id,
            'msg' => 'Employee added successfully'
        ];
    }

    private function domainVerified($email)
    {
        $domain = explode("@", $email)[1];
        $checkDomain = DomainVerified::where('domain', $domain)
            ->where('verified', 1)
            ->where('company_id', Auth::user()->company_id)
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
                    'msg' => 'Employee already exists in group'
                ];
            }
            $usersArray[] = $employeeId;
            $group->users = json_encode($usersArray);
            $group->save();
            return [
                'status' => 1,
                'msg' => 'Employee added to group successfully'
            ];
        } else {
            $group->users = json_encode([$employeeId]);
            $group->save();
            return [
                'status' => 1,
                'msg' => 'Employee added to group successfully'
            ];
        }
    }

    public function deleteGroup($groupId)
    {
        $group = UsersGroup::where('group_id', $groupId)->first();
        if (!$group) {
            return [
                'status' => 0,
                'msg' => 'Group not found'
            ];
        }
        //delete this group users also
        if ($group->users !== null) {
            $usersArray = json_decode($group->users, true);
            foreach ($usersArray as $userId) {
                $this->deleteCampaignsByGroupId($groupId);
                $this->deleteEmployeeById($userId);
            }
        }
        $group->delete();
        return [
            'status' => 1,
            'msg' => 'Group deleted successfully'
        ];
    }
    public function deleteCampaignsByGroupId($groupId)
    {

        $email_camps = Campaign::where('users_group', $groupId)->get();
        if ($email_camps) {
            //deleting campaign reports
            $email_camps->each(function ($camp) {
                CampaignReport::where('campaign_id', $camp->campaign_id)->delete();
            });
            Campaign::where('users_group', $groupId)->delete();
        }

        AiCallCampaign::where('emp_group', $groupId)->delete();

        QuishingCamp::where('users_group', $groupId)->delete();
        WhatsappCampaign::where('user_group', $groupId)->delete();
    }
    public function deleteEmployeeById($employeeId)
    {

        $user = Users::where('id', $employeeId)->where('company_id', Auth::user()->company_id)->first();
        // $ifBreached = BreachedEmail::where('email', $user->user_email)->delete();

        if ($user) {

            //delete from email campaign live
            CampaignLive::where('user_id', $user->id)->delete();


            //delete ai campaign live
            AiCallCampLive::where('user_id', $user->id)->delete();


            //delete from whatsapp campaign live
            WhatsAppCampaignUser::where('user_id', $user->id)->delete();


            //delete from quishing campaign live
            QuishingLiveCamp::where('user_id', $user->id)->delete();


            //delete from training assigned table
            TrainingAssignedUser::where('user_id', $user->id)->delete();

            //delete from user login table
            UserLogin::where('user_id', $user->id)->delete();

            //checking if this is the last email
            $emailsExists = Users::where('user_email', $user->user_email)->where('company_id', Auth::user()->company_id)->count();
            if ($emailsExists == 1) {
                BreachedEmail::where('email', $user->user_email)->delete();
            }

            //remove this id users column array from all groups
            $groups = UsersGroup::where('company_id', Auth::user()->company_id)->get();
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
                            }else{
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

    public function emailExistsInGroup($groupId, $email)
    {
        $users = Users::where('user_email', $email)->where('company_id', Auth::user()->company_id)->get();
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
        if (Auth::user()->usedemployees >= Auth::user()->employees) {
            return true;
        } else {
            return false;
        }
    }

    private function increaseLimit($email)
    {
        $userExists = Users::where('user_email', $email)
            ->where('company_id', Auth::user()->company_id)
            ->exists();

        if (!$userExists) {
            Auth::user()->increment('usedemployees');
        }
    }
}

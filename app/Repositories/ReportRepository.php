<?php

namespace App\Repositories;

use App\Models\Campaign;
use App\Models\CampaignLive;
use App\Models\QuishingCamp;
use App\Models\QuishingLiveCamp;
use App\Models\WaCampaign;
use App\Models\WaLiveCampaign;
use App\Models\TprmCampaign;
use App\Models\TprmCampaignLive;
use App\Models\TrainingAssignedUser;
use App\Models\CompanyLicense;
use App\Models\Users;
use App\Models\UsersGroup;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;

class ReportRepository
{
    /**
     * Get all users for a company
     */
    public function getCompanyUsers(string $companyId): Collection
    {
        return Users::where('company_id', $companyId)->get();
    }

    /**
     * Get count of all users for a company
     */
    public function getCompanyUsersCount(string $companyId): int
    {
        return Users::where('company_id', $companyId)->count();
    }

    /**
     * Get all user groups for a company
     */
    public function getUserGroups(string $companyId): Collection
    {
        return UsersGroup::where('company_id', $companyId)->get();
    }

    /**
     * Get campaign counts grouped by users group
     */
    public function getCampaignCountsByGroup(string $companyId): \Illuminate\Support\Collection
    {
        return Campaign::where('company_id', $companyId)
            ->select('users_group', DB::raw('COUNT(*) as count'))
            ->groupBy('users_group')
            ->pluck('count', 'users_group');
    }

    /**
     * Get simulation status for Email (bulk)
     */
    public function getEmailSimulationData(string $companyId, array $userIds): Collection
    {
        return CampaignLive::where('company_id', $companyId)
            ->whereIn('user_id', $userIds)
            ->select('user_id', 'emp_compromised')
            ->get();
    }

    /**
     * Get simulation status for Quishing (bulk)
     */
    public function getQuishingSimulationData(string $companyId, array $userIds): Collection
    {
        return QuishingLiveCamp::where('company_id', $companyId)
            ->whereIn('user_id', $userIds)
            ->select('user_id', 'compromised')
            ->get();
    }

    /**
     * Get simulation status for WhatsApp (bulk)
     */
    public function getWhatsappSimulationData(string $companyId, array $userIds): Collection
    {
        return WaLiveCampaign::where('company_id', $companyId)
            ->whereIn('user_id', $userIds)
            ->select('user_id', 'compromised')
            ->get();
    }

    /**
     * Get Email campaigns with stats for last 7 days
     */
    public function getEmailCampaignsLast7Days(string $companyId, $startDate): Collection
    {
        return Campaign::where('company_id', $companyId)
            ->where('created_at', '>=', $startDate)
            ->withCount(['campLive as total_users', 'campLive as compromised_users' => function ($query) {
                $query->where('emp_compromised', 1);
            }])
            ->get();
    }

    /**
     * Get Quishing campaigns with stats for last 7 days
     */
    public function getQuishingCampaignsLast7Days(string $companyId, $startDate): Collection
    {
        return QuishingCamp::where('company_id', $companyId)
            ->where('created_at', '>=', $startDate)
            ->withCount(['campLive as total_users', 'campLive as compromised_users' => function ($query) {
                $query->where('compromised', '1');
            }])
            ->get();
    }

    /**
     * Get WhatsApp campaigns with stats for last 7 days
     */
    public function getWhatsappCampaignsLast7Days(string $companyId, $startDate): Collection
    {
        return WaCampaign::where('company_id', $companyId)
            ->where('created_at', '>=', $startDate)
            ->withCount(['campLive as total_users', 'campLive as compromised_users' => function ($query) {
                $query->where('compromised', 1);
            }])
            ->get();
    }

    /**
     * Get TPRM campaigns with stats for last 7 days
     */
    public function getTprmCampaignsLast7Days(string $companyId, $startDate): Collection
    {
        return TprmCampaign::where('company_id', $companyId)
            ->where('created_at', '>=', $startDate)
            ->withCount(['campLive as total_users', 'campLive as compromised_users' => function ($query) {
                $query->where('emp_compromised', 1);
            }])
            ->get();
    }

    /**
     * Get training counts (total, not started, in progress, completed, educated)
     */
    public function getTrainingCounts(string $companyId)
    {
        return TrainingAssignedUser::where('company_id', $companyId)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN training_started = 0 THEN 1 ELSE 0 END) as not_started,
                SUM(CASE WHEN training_started = 1 AND completed = 0 THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN completed = 1 THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN personal_best >= 10 THEN 1 ELSE 0 END) as educated
            ')
            ->first();
    }

    /**
     * Get company license
     */
    public function getCompanyLicense(string $companyId)
    {
        return CompanyLicense::where('company_id', $companyId)->first();
    }

    /**
     * Get all training assigned users with eager loaded training data
     */
    public function getAllTrainingAssignments(string $companyId): Collection
    {
        return TrainingAssignedUser::where('company_id', $companyId)
            ->with(['trainingData:id,name,passing_score'])
            ->get();
    }
}

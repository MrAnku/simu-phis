<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PolicyCampaign extends Model
{
    protected $fillable = [
        'campaign_name',
        'campaign_id',
        'users_group',
        'policy',
        'scheduled_at',
        'company_id',
        'status',
    ];

    protected $appends = ['policy_detail', 'assigned_policies', 'target_employees'];

    public function getScheduledAtAttribute($value)
    {
        return $value ? \Carbon\Carbon::parse($value)->format('d M Y h:i A') : null;
    }

    public function getTargetEmployeesAttribute()
    {
        $userIdsJson = UsersGroup::where('group_id', $this->users_group)
            ->where('company_id', $this->company_id)
            ->value('users');
        if(!$userIdsJson) {
            return [];
        }

        $userIds = json_decode($userIdsJson, true) ?? [];
        return Users::whereIn('id', $userIds)->pluck('user_name')->toArray();
    }

    public function campLive()
    {
        return $this->hasMany(PolicyCampaignLive::class, 'campaign_id', 'campaign_id');
    }

    public function getPolicyDetailAttribute()
    {
        $ids = json_decode($this->policy, true) ?? [];
        if (empty($ids)) {
            return [];
        }
        return Policy::whereIn('id', $ids)->get();
    }

    public function groupDetail()
    {
        return $this->belongsTo(UsersGroup::class, 'users_group', 'group_id');
    }

    public function getAssignedPoliciesAttribute()
    {
        $policyIds = json_decode($this->policy, true) ?? [];
        return AssignedPolicy::with('policyData')
            ->whereIn('policy', $policyIds)
            ->get();
    }
}

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

    public function campLive()
    {
        return $this->hasMany(PolicyCampaignLive::class, 'campaign_id', 'campaign_id');
    }

    public function assignedPolicies(){
        return $this->hasMany(AssignedPolicy::class, 'campaign_id', 'campaign_id'); 
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmishingCampaign extends Model
{
    protected $fillable = [
        'campaign_id',
        'campaign_name',
        'campaign_type',
        'users_group',
        'template_id',
        'template_lang',
        'website_id',
        'training_module',
        'training_assignment',
        'days_until_due',
        'training_lang',
        'training_type',
        'launch_time',
        'status',
        'company_id',
    ];

    public function campLive()
    {
        return $this->hasMany(SmishingLiveCampaign::class, 'campaign_id', 'campaign_id');
    }

    public function userGroupData()
    {
        return $this->belongsTo(UsersGroup::class, 'users_group', 'group_id');
    }
}

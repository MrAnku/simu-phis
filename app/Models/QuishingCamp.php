<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuishingCamp extends Model
{
    protected $fillable = [
        'campaign_id',
        'campaign_name',
        'campaign_type',
        'users_group',
        'training_module',
        'training_assignment',
        'days_until_due',
        'training_lang',
        'training_type',
        'quishing_material',
        'quishing_lang',
        'status',
        'company_id',
    ];

    public function userGroupData()
    {
        return $this->hasOne(UsersGroup::class, 'group_id', 'users_group');
    }
    public function campLive()
    {
        return $this->hasMany(QuishingLiveCamp::class, 'campaign_id', 'campaign_id');
    }

    public function campaignActivity()
    {
        return $this->hasMany(QuishingActivity::class, 'campaign_id', 'campaign_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    use HasFactory;

    protected $table = 'all_campaigns';

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
        'phishing_material',
        'email_lang',
        'launch_time',
        'launch_type',
        'email_freq',
        'startTime',
        'endTime',
        'timeZone',
        'expire_after',
        'status',
        'company_id'
    ];

    public function trainingModules()
    {
        $ids = json_decode($this->training_module, true);
        return TrainingModule::whereIn('id', $ids ?? []);
    }
    public function phishingMaterials()
    {
        $ids = json_decode($this->phishing_material, true);
        return PhishingEmail::whereIn('id', $ids ?? []);
    }

    public function campLive()
    {
        return $this->hasMany(CampaignLive::class, 'campaign_id', 'campaign_id');
    }

    public function campReport()
    {
        return $this->hasOne(CampaignReport::class, 'campaign_id', 'campaign_id');
    }

    public function trainingAssignedUsers()
    {
        return $this->hasMany(TrainingAssignedUser::class, 'campaign_id', 'campaign_id');
    }

    public function campaignActivity()
    {
        return $this->hasMany(EmailCampActivity::class, 'campaign_id', 'campaign_id');
    }



    public function noOfUsers()
    {
        return $this->hasMany(Users::class, 'group_id', 'users_group');
    }

    public function usersGroup()
    {
        return $this->hasOne(UsersGroup::class, 'group_id', 'users_group');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Campaign extends Model
{
    use HasFactory;

    protected $table = 'all_campaigns';
    protected $appends = ['formatted_created_at'];

    protected $fillable = [
        'campaign_id',
        'campaign_name',
        'campaign_type',
        'users_group',
        'selected_users',
        'training_module',
        'training_assignment',
        'days_until_due',
        'training_lang',
        'training_type',
        'policies',
        'training_on_click',
        'compromise_on_click',
        'phishing_material',
        'sender_profile',
        'email_lang',
        'launch_time',
        'launch_type',
        'email_freq',
        'startTime',
        'endTime',
        'timeZone',
        'expire_after',
        'status',
        'company_id',
        'scorm_training'
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

      public function scormAssignedUsers()
    {
        return $this->hasMany(ScormAssignedUser::class, 'campaign_id', 'campaign_id');
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



    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at ? $this->created_at->format('d M Y h:i A') : null;
    }

    public function emailReplies()
    {
        return $this->hasMany(PhishingReply::class, 'campaign_id', 'campaign_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaCampaign extends Model
{
    protected $fillable = [
        'campaign_id',
        'campaign_name',
        'campaign_type',
        'employee_type',
        'phishing_website',
        'training_module',
        'training_assignment',
        'days_until_due',
        'training_lang',
        'training_type',
        'template_name',
        'users_group',
        'schedule_type',
        'launch_time',
        'status',
        'variables',
        'company_id',
    ];

    public function trainingModules()
    {
        $ids = json_decode($this->training_module, true);
        return TrainingModule::whereIn('id', $ids ?? []);
    }
   

    public function trainingData()
    {
        return $this->belongsTo(TrainingModule::class, 'training_module', 'id');
    }
    public function userGroupData()
    {
        return $this->hasOne(UsersGroup::class, 'group_id', 'users_group');
    }
    public function campLive()
    {
        return $this->hasMany(WaLiveCampaign::class, 'campaign_id', 'campaign_id');
    }
    public function campaignActivity()
    {
        return $this->hasMany(WhatsappActivity::class, 'campaign_id', 'campaign_id');
    }

    protected $appends = ['formatted_created_at'];

    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at ? $this->created_at->format('d M Y h:i A') : null;
    }
}

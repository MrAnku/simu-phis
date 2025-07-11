<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TprmCampaign extends Model
{
    use HasFactory;

    protected $table = 'tprm_all_campaigns';

    protected $fillable = [
        'campaign_id',
        'campaign_name',
        'campaign_type',
        'users_group',
        'training_module',
        'training_lang',
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
        'company_id'
    ];

    public function noOfUsers()
    {
        return $this->hasMany(TprmUsers::class, 'group_id', 'users_group');
    }
    public function tprmReport()
    {
        return $this->hasMany(TprmCampaignReport::class, 'campaign_id', 'campaign_id');
    }
    public function campLive()
    {
        return $this->hasMany(TprmCampaignLive::class, 'campaign_id', 'campaign_id');
    }
    public function campaignActivity()
    {
        return $this->hasMany(TprmActivity::class, 'campaign_id', 'campaign_id');
    }

    protected $appends = ['formatted_created_at'];

    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at ? $this->created_at->format('d M Y h:i A') : null;
    }
}

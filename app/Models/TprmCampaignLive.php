<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TprmCampaignLive extends Model
{
    use HasFactory;

    protected $table = 'tprm_campaign_live';

    protected $fillable = [
        'campaign_id',
        'campaign_name',
        'user_id',
        'user_name',
        'user_email',
        'training_module',
        'training_lang',
        'launch_time',
        'phishing_material',
        'sender_profile',
        'mail_open',
        'payload_clicked',
        'email_reported',
        'email_lang',
        'sent',
        'training_assigned',
        'company_id'
    ];

    public function campaignActivity()
    {
        return $this->hasOne(TprmActivity::class, 'campaign_live_id', 'id');
    }

    protected $appends = ['formatted_created_at'];

    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at ? $this->created_at->format('d M Y h:i A') : null;
    }
}

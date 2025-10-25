<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampaignLive extends Model
{
    use HasFactory;

    protected $table = 'campaign_live';
    protected $appends = ['formatted_created_at', 'replied'];

    protected $fillable = [
        'campaign_id',
        'campaign_name',
        'user_id',
        'user_name',
        'user_email',
        'training_module',
        'days_until_due',
        'training_lang',
        'training_type',
        'launch_time',
        'phishing_material',
        'sender_profile',
        'email_lang',
        'sent',
        'mail_open',
        'payload_clicked',
        'emp_compromised',
        'email_reported',
        'training_assigned',
        'company_id',
        'scorm_training',
        'send_time'
    ];

    public function camp(){
        return $this->belongsTo(Campaign::class, 'campaign_id', 'campaign_id');
    }

    public function trainingAssigned(){
        return $this->hasOne(TrainingAssignedUser::class, 'campaign_id', 'campaign_id');
    }

    public function training(){
        return $this->belongsTo(TrainingModule::class, 'training_module', 'id');
    }

    public function game(){
        return $this->belongsTo(TrainingGame::class, 'training_module', 'id');
    }

    public function campaignActivity()
    {
        return $this->belongsTo(EmailCampActivity::class, 'id', 'campaign_live_id');
    }

    

    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at ? $this->created_at->format('d M Y h:i A') : null;
    }

    public function getRepliedAttribute()
    {
        return PhishingReply::where('from_address', $this->user_email)->exists() ? 1 : 0;
    }
}

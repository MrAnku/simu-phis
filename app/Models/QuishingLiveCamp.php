<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuishingLiveCamp extends Model
{
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
        'quishing_material',
        'sender_profile',
        'quishing_lang',
        'sent',
        'mail_open',
        'qr_scanned',
        'compromised',
        'email_reported',
        'training_assigned',
        'company_id',
        'scorm_training'
    ];

    public function trainingData(){
        return $this->belongsTo(TrainingModule::class, 'training_module', 'id');
    }

    public function templateData(){
        return $this->belongsTo(QshTemplate::class, 'quishing_material', 'id');
    }

    public function campaignActivity()
    {
        return $this->hasOne(QuishingActivity::class, 'campaign_live_id', 'id');
    }

    protected $appends = ['formatted_created_at'];

    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at ? $this->created_at->format('d M Y h:i A') : null;
    }
    
}

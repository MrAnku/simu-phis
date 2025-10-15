<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaLiveCampaign extends Model
{
    protected $fillable = [
        'campaign_id',
        'campaign_name',
        'campaign_type',
        'employee_type',
        'user_name',
        'user_id',
        'user_email',
        'user_phone',
        'phishing_website',
        'training_module',
        'training_assignment',
        'days_until_due',
        'training_lang',
        'training_type',
        'template_name',
        'variables',
        'sent',
        'payload_clicked',
        'compromised',
        'training_assigned',
        'company_id',
        'scorm_training',
    ];

    public function camp()
    {
        return $this->belongsTo(WaCampaign::class, 'campaign_id', 'campaign_id');
    }

    public function phishingWebsite()
    {
        return $this->belongsTo(PhishingWebsite::class, 'phishing_website', 'id');
    }
    public function whatsTrainingData()
    {
        return $this->hasMany(BlueCollarTrainingUser::class, 'campaign_id', 'campaign_id');
    }

    public function scormTrainingData()
    {
        return $this->hasMany(BlueCollarScormAssignedUser::class, 'campaign_id', 'campaign_id');
    }


    public function trainingData()
    {
        return $this->belongsTo(TrainingModule::class, 'training_module', 'id');
    }

    public function campaignActivity()
    {
        return $this->hasOne(WhatsappActivity::class, 'campaign_live_id', 'id');
    }

    protected $appends = ['formatted_created_at'];

    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at ? $this->created_at->format('d M Y h:i A') : null;
    }


}

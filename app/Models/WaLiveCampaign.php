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
    ];
    public function whatsTrainingData()
    {
        return $this->hasMany(TrainingAssignedUser::class, 'campaign_id', 'campaign_id');
    }

    public function trainingData()
    {
        return $this->belongsTo(TrainingModule::class, 'training_module', 'id');
    }

    public function campaignActivity()
    {
        return $this->hasOne(WhatsappActivity::class, 'campaign_live_id', 'id');
    }


}

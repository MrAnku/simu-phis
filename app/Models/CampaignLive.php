<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampaignLive extends Model
{
    use HasFactory;

    protected $table = 'campaign_live';
    public $timestamps = false;

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
        'email_lang',
        'sent',
        'mail_open',
        'payload_clicked',
        'emp_compromised',
        'email_reported',
        'training_assigned',
        'company_id'
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
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiCallCampaign extends Model
{
    use HasFactory;

    protected $table = 'ai_call_campaigns';

    protected $fillable = [
        'campaign_id',
        'campaign_name',
        'users_group',
        'users_grp_name',
        'training_module',
        'training_lang',
        'training_type',
        'ai_agent',
        'ai_agent_name',
        'phone_no',
        'status',
        'company_id',
        'scorm_training',
        'launch_time',
        'launch_type',
        'employee_type',
        'training_assignment'
    ];

    public function individualCamps(){
        return $this->hasMany(AiCallCampLive::class, 'campaign_id', 'campaign_id');
    }

    public function trainingName(){
        return $this->hasOne(TrainingModule::class, 'id', 'training_module');
    }

    protected $appends = ['formatted_created_at'];

    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at ? $this->created_at->format('d M Y h:i A') : null;
    }
    
}

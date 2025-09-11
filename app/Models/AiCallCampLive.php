<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiCallCampLive extends Model
{
    use HasFactory;

    protected $table = 'ai_call_camp_live';

    protected $fillable = [
        'campaign_id',
        'campaign_name',
        'user_id',
        'user_name',
        'user_email',
        'training',
        'training_lang',
        'training_type',
        'from_mobile',
        'to_mobile',
        'agent_id',
        'call_id',
        'status',
        'training_assigned',
        'compromised',
        'call_send_response',
        'call_end_response',
        'call_report',
        'company_id',
        'scorm_training',
        'employee_type'
    ];

    public function camp(){
        return $this->belongsTo(AiCallCampaign::class, 'campaign_id', 'campaign_id');
    }

    public function trainingAsssigned(){
        return $this->belongsTo(TrainingAssignedUser::class, 'campaign_id', 'campaign_id');
    }

    public function trainingData(){
        return $this->belongsTo(TrainingModule::class, 'training', 'id');
    }

    public function agentDetail(){
        return $this->belongsTo(AiCallAgent::class, 'agent_id', 'agent_id');
    }

    protected $appends = ['formatted_created_at'];

    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at ? $this->created_at->format('d M Y h:i A') : null;
    }
}

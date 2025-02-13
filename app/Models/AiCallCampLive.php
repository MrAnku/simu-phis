<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiCallCampLive extends Model
{
    use HasFactory;

    protected $table = 'ai_call_camp_live';
    public $timestamps = false;

    protected $fillable = [
        'campaign_id',
        'campaign_name',
        'user_id',
        'employee_name',
        'employee_email',
        'training',
        'training_lang',
        'training_type',
        'from_mobile',
        'to_mobile',
        'agent_id',
        'call_id',
        'status',
        'training_assigned',
        'call_send_response',
        'call_end_response',
        'call_report',
        'created_at',
        'company_id'
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
}

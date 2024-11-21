<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiCallCampaign extends Model
{
    use HasFactory;

    protected $table = 'ai_call_campaigns';
    public $timestamps = false;

    protected $fillable = [
        'campaign_id',
        'campaign_name',
        'emp_group',
        'emp_grp_name',
        'training',
        'training_lang',
        'training_type',
        'ai_agent',
        'ai_agent_name',
        'phone_no',
        'status',
        'created_at',
        'company_id'
    ];

    public function individualCamps(){
        return $this->hasMany(AiCallCampLive::class, 'campaign_id', 'campaign_id');
    }

    public function trainingName(){
        return $this->hasOne(TrainingModule::class, 'id', 'training');
    }
    
}

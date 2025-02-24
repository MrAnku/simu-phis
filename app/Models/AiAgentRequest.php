<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiAgentRequest extends Model
{
    protected $fillable = [
        'agent_name',
        'prompt',
        'language',
        'audio_file',
        'status',
        'company_id',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'company_id');
    }

    public function agent()
    {
        return $this->hasOne(AiCallAgent::class, 'request_id', 'id');
    }
}

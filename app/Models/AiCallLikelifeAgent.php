<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiCallLikelifeAgent extends Model
{
    protected $fillable = [
        'agent_name',
        'agent_id',
        'user_id',
        'llm',
        'tts_provider',
        'tts_voice',
        'language',
        'welcome_message',
        'system_prompt',
        'linked_number',
        'use_memory',
        'auto_generate_welcome_message',
        'auto_end_call',
        'auto_end_call_duration',
        'tts_speed',
        'tts_stability',
        'tts_similarity_boost',
        'company_id',
    ];
    protected $hidden = [
        'llm',
        'tts_provider',
    ];
}

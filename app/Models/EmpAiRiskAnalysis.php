<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmpAiRiskAnalysis extends Model
{
    protected $fillable = [
        'user_email',
        'ai_analysis',
        'company_id',
    ];

    protected $casts = [
        'ai_analysis' => 'array',
    ];
}

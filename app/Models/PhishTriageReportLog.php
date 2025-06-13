<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhishTriageReportLog extends Model
{
    protected $fillable = [
        'user_email',
        'reported_email',
        'subject',
        'headers',
        'body',
        'company_id',
        'ai_analysis'
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingSetting extends Model
{
    protected $fillable = [
        'company_id',
        'content_survey',
        'survey_questions',
        'company_email',
    ];

    protected $casts = [
        'content_survey' => 'boolean',
        'questions' => 'array',
    ];
}

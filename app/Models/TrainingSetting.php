<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingSetting extends Model
{
    protected $fillable = [
        'company_id',
        'content_survey',
        'survey_questions',
        'email',
    ];

    protected $casts = [
        'content_survey' => 'boolean',
        'survey_questions' => 'array',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingSetting extends Model
{
    protected $fillable = [
        'company_id',
        'content_survey',
        'survey_questions',
        'localized_notification',
        'help_redirect_to',
        'email',
    ];

   protected $casts = [
    'content_survey' => 'boolean',
    'localized_notification' => 'boolean',
    'survey_questions' => 'array',   
];

}




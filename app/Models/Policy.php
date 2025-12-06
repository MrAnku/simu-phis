<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Policy extends Model
{
    protected $fillable = [
        'policy_name',
        'policy_description',
        'policy_prompt',
        'generated_text',
        'policy_file',
        'has_quiz',
        'json_quiz',
         'company_id',
    ];
}

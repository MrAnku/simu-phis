<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyTriggerTraining extends Model
{
    protected $fillable = [
        'employee_type',
        'user_id',
        'user_name',
        'user_email',
        'user_whatsapp',
        'training',
        'policy',
        'scorm',
        'sent',
        'company_id'
    ];
}

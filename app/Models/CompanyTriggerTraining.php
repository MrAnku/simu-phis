<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyTriggerTraining extends Model
{
    protected $fillable = [
        'user_email',
        'training',
        'policy',
        'sent',
        'company_id'
    ];
}

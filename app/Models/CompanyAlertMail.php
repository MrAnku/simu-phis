<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyAlertMail extends Model
{
    protected $fillable = [
        'company_id',
        'license_expired',
        'need_support',
        'user_limit_exceed',
    ];
}

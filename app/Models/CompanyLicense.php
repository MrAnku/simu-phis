<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyLicense extends Model
{
    public $table = 'company_licenses';

    protected $fillable = [
        'company_id',
        'employees',
        'tprm_employees',
        'blue_collar_employees',
        'expiry',
    ];
}

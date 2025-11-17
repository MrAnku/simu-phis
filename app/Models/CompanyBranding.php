<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyBranding extends Model
{
    protected $fillable = [
        'company_name',
        'favicon',
        'light_logo',
        'dark_logo',
        'company_id',
    ];
}

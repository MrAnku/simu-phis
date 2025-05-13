<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhiteLabelledCompany extends Model
{
    use HasFactory;
    protected $fillable = [
        'company_id',
        'company_email',
        'domain',
        'learn_domain',
        'dark_logo',
        'light_logo',
        'favicon',
        'company_name',
        'approved_by_partner',
        'date',
    ];
}

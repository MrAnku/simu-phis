<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomisedReporting extends Model
{
    protected $fillable = [
        'report_name',
        'report_description',
        'widgets',
        'company_id',
    ];

    
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyTour extends Model
{
    protected $fillable = [
        'company_id',
        'dashboard',
        'sidebar',
        'settings',
    ];

}

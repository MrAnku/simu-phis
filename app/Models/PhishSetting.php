<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhishSetting extends Model
{
    protected $fillable = [
        'company_id',
        'email',
        'phish_results_visible'
    ];
}

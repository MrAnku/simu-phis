<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OverallReport extends Model
{
    protected $fillable = [
        'company_id',
        'report_path',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmartGroup extends Model
{
    protected $fillable = ['group_name', 'risk_type', 'company_id'];
}

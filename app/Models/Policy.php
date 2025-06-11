<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Policy extends Model
{
    protected $fillable = [
        'policy_name',
        'policy_description',
        'policy_file',
        'company_id',
    ];
}

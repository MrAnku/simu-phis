<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewIpLogin extends Model
{
    protected $fillable = [
        'email',
        'ip_address',
        'login_time',
        'timezone',
        'notified',
        'company_id'
    ];
}

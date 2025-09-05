<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'company_id',
        'user_id',
        'action',
        'description',
        'ip_address',
        'user_agent',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'company_id',
        'user_email',
        'user_whatsapp',
        'user_type',
        'action',
        'description',
        'ip_address',
        'user_agent',
    ];
}

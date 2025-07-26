<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Otp extends Model
{
    protected $fillable = [
        'otp',
        'otp_expiry',
        'email',
        'company_id'
    ];
}

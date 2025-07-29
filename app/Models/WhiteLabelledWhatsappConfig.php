<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhiteLabelledWhatsappConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'from_phone_id',
        'access_token',
        'business_id',
        'company_id'
    ];
}

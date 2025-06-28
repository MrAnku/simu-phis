<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestWhatsappTemplate extends Model
{
    protected $fillable = [
        'template_id',
        'name',
        'category',
        'language',
        'status',
        'waba_id',
        'company_id',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyWhatsappTemplate extends Model
{
    protected $fillable = [
        'template',
        'company_id',
    ];
}

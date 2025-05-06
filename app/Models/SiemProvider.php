<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiemProvider extends Model
{
    
    protected $fillable = [
        'provider_name',
        'url',
        'token',
        'status',
        'company_id'
    ];
    
}

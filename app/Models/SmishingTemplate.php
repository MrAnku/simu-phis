<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmishingTemplate extends Model
{
    protected $fillable = [
        'name',
        'category',
        'message',
        'company_id',
    ];
}

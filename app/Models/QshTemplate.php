<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QshTemplate extends Model
{
    protected $fillable = [
        'name',
        'email_subject',
        'difficulty',
        'file',
        'website',
        'sender_profile',
        'company_id',
    ];
}

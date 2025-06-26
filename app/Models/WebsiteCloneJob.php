<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebsiteCloneJob extends Model
{
    protected $fillable = [
        'url',
        'status',
        'file_url',
        'error_message',
        'company_id',
    ];
}

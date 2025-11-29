<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomNotificationEmail extends Model
{
       protected $casts = [
        'status' => 'boolean',
    ];
}

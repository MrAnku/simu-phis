<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlueCollarLearnerLoginSession extends Model
{
    public $timestamps = true;
    
    protected $fillable = [
        'token',
        'whatsapp_number',
        'expiry',
    ];
}

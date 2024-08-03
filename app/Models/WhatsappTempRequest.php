<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappTempRequest extends Model
{
    use HasFactory;
    protected $table = 'whatsapp_temp_requests';
    public $timestamps = false;

}

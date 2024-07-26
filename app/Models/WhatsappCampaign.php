<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappCampaign extends Model
{
    use HasFactory;
    protected $table = 'whatsapp_campaigns';
    public $timestamps = false;
}

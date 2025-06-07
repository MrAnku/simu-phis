<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappActivity extends Model
{
    protected $fillable = ['campaign_id', 'campaign_live_id', 'whatsapp_sent_at','payload_clicked_at', 'compromised_at', 'client_details', 'company_id'];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TprmActivity extends Model
{
    protected $fillable = ['campaign_id', 'campaign_live_id', 'email_sent_at', 'email_viewed_at', 'payload_clicked_at', 'compromised_at', 'client_details', 'company_id'];
}

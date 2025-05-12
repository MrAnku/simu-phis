<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmishingLiveCampaign extends Model
{
    protected $fillable = [
        'campaign_id',
        'campaign_name',
        'campaign_type',
        'user_id',
        'user_name',
        'user_phone',
        'template_id',
        'template_lang',
        'website_id',
        'training_module',
        'days_until_due',
        'training_lang',
        'training_type',
        'sent',
        'payload_clicked',
        'compromised',
        'training_assigned',
        'company_id',
    ];
}

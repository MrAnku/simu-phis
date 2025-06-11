<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PolicyCampaignLive extends Model
{
    protected $fillable = [
        'campaign_name',
        'campaign_id',
        'user_name',
        'user_email',
        'sent',
        'accepted',
        'accepted_at',
        'policy',
        'company_id',
    ];
}

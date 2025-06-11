<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PolicyCampaign extends Model
{
    protected $fillable = [
        'campaign_name',
        'campaign_id',
        'users_group',
        'policy',
        'scheduled_at',
        'company_id',
    ];
}

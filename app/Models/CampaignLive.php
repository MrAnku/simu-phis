<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampaignLive extends Model
{
    use HasFactory;

    protected $table = 'campaign_live';
    public $timestamps = false;

    protected $fillable = [
        'campaign_id',
        'campaign_name',
        'user_id',
        'user_name',
        'user_email',
        'training_module',
        'days_until_due',
        'training_lang',
        'training_type',
        'launch_time',
        'phishing_material',
        'email_lang',
        'sent',
        'training_assigned',
        'company_id'
    ];
}

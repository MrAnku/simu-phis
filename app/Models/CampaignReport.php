<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampaignReport extends Model
{
    use HasFactory;

    protected $table = 'campaign_reports';
    public $timestamps = false;

    protected $fillable = [
        'campaign_id',
        'campaign_name',
        'campaign_type',
        'status',
        'email_lang',
        'training_lang',
        'scheduled_date',
        'company_id',
    ];
}

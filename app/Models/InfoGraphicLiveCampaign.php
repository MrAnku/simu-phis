<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InfoGraphicLiveCampaign extends Model
{
    protected $fillable = [
        'campaign_id',
        'campaign_name',
        'user_name',
        'user_email',
        'sent',
        'infographic',
        'comic',
        'company_id',
    ];

    public function infographicData()
    {
        return $this->belongsTo(Inforgraphic::class, 'infographic', 'id');
    }
}

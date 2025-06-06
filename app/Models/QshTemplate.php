<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QshTemplate extends Model
{
    protected $fillable = [
        'name',
        'email_subject',
        'difficulty',
        'file',
        'website',
        'sender_profile',
        'company_id',
    ];

    public function senderProfile()
    {
        return $this->belongsTo(SenderProfile::class, 'sender_profile', 'id');
    }
    public function website(){
        return $this->belongsTo(PhishingWebsite::class, 'website', 'id');
    }
    public function emailCampLive(): HasMany
    {
        return $this->hasMany(QuishingLiveCamp::class, 'quishing_material', 'id');
    }
}

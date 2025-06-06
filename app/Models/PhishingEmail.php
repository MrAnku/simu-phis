<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PhishingEmail extends Model
{
    use HasFactory;

    protected $table = 'phishing_emails';
    public $timestamps = false;

    protected $fillable = [
        'name', 'email_subject', 'difficulty', 'mailBodyFilePath', 'website', 'senderProfile', 'company_id'
    ];

    public function web(): HasOne
    {
        return $this->hasOne(PhishingWebsite::class, 'id', 'website');
    }

    public function sender_p(): HasOne
    {
        return $this->hasOne(SenderProfile::class, 'id', 'senderProfile');
    }
    public function emailCampLive(): HasMany
    {
        return $this->hasMany(CampaignLive::class, 'phishing_material', 'id');
    }
}

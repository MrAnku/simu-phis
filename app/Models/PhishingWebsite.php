<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PhishingWebsite extends Model
{
    use HasFactory;

    protected $table = 'phishing_websites';
    protected $appends = ['url'];
    public $timestamps = false;

    public function phishingEmail()
    {
        return $this->belongsTo(PhishingEmail::class, 'website', 'id');
    }


    public function getUrlAttribute()
    {
        return 'http://' . Str::random(6)
            . '.'
            . getRandomDomain()
            . '/'
            . Str::random(10)
            . '?v=r&c=' . Str::random(10)
            . '&p=' . $this->id
            . '&l=' . Str::slug($this->name);
    }
    public function whatsappCampLive()
    {
        return $this->hasMany(WaLiveCampaign::class, 'phishing_website', 'id');
    }
}

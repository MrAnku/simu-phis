<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhishingWebsite extends Model
{
    use HasFactory;

    protected $table = 'phishing_websites';
    public $timestamps = false;

    public function phishingEmail()
    {
        return $this->belongsTo(PhishingEmail::class, 'website', 'id');
    }
}

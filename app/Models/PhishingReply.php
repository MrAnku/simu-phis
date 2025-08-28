<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhishingReply extends Model
{
    use HasFactory;

    protected $table = 'phishing_replies';

    protected $fillable = [
        'from_address',
        'subject',
        'headers',
        'body',
        'campaign_id',
        'campaign_type',
        'company_id'
    ];
}

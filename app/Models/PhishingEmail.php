<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhishingEmail extends Model
{
    use HasFactory;

    protected $table = 'phishing_emails';
    public $timestamps = false;
}

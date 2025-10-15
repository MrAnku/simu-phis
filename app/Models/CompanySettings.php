<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanySettings extends Model
{
    use HasFactory;

    protected $table = 'company_settings';
    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'email',
        'country',
        'time_zone',
        'date_format',
        'mfa',
        'mfa_secret',
        'default_phishing_email_lang',
        'default_training_lang',
        'default_notifications_lang',
        'phish_redirect',
        'phish_redirect_url',
        'phish_reporting',
        'training_assign_remind_freq_days',
        'time_to_click',
        'phish_reply',
        'overall_report'
    ];
}

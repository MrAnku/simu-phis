<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuishingLiveCamp extends Model
{
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
    'quishing_material',
    'quishing_lang',
    'company_id',
];
}

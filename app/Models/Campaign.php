<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    use HasFactory;

    protected $table = 'all_campaigns';
    public $timestamps = false;

    protected $fillable = [
        'campaign_id',
        'campaign_name',
        'campaign_type',
        'users_group',
        'training_module',
        'days_until_due',
        'training_lang',
        'training_type',
        'phishing_material',
        'email_lang',
        'launch_time',
        'launch_type',
        'email_freq',
        'startTime',
        'endTime',
        'timeZone',
        'expire_after',
        'status',
        'company_id'
    ];

    public function noOfUsers(){
        return $this->hasMany(Users::class, 'group_id', 'users_group');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TprmCampaign extends Model
{
    use HasFactory;

    protected $table = 'tprm_all_campaigns';
    public $timestamps = false;

    protected $fillable = [
        'campaign_id',
        'campaign_name',
        'campaign_type',
        'users_group',
        'training_module',
        'training_lang',
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
        return $this->hasMany(TprmUsers::class, 'group_id', 'users_group');
    }
}

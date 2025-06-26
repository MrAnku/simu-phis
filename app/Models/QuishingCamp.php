<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuishingCamp extends Model
{
    protected $fillable = [
        'campaign_id',
        'campaign_name',
        'campaign_type',
        'users_group',
        'training_module',
        'training_assignment',
        'days_until_due',
        'training_lang',
        'training_type',
        'quishing_material',
        'quishing_lang',
        'status',
        'company_id',
    ];

    public function trainingModules()
    {
        $ids = json_decode($this->training_module, true);
        return TrainingModule::whereIn('id', $ids ?? []);
    }
    public function quishingMaterials()
    {
        $ids = json_decode($this->quishing_material, true);
        return QshTemplate::whereIn('id', $ids ?? []);
    }

    public function userGroupData()
    {
        return $this->hasOne(UsersGroup::class, 'group_id', 'users_group');
    }
    public function campLive()
    {
        return $this->hasMany(QuishingLiveCamp::class, 'campaign_id', 'campaign_id');
    }

    public function campaignActivity()
    {
        return $this->hasMany(QuishingActivity::class, 'campaign_id', 'campaign_id');
    }

    protected $appends = ['formatted_created_at'];

    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at ? $this->created_at->format('d M Y h:i A') : null;
    }
}

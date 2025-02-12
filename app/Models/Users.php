<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Users extends Model
{
    use HasFactory;

    protected $table = 'users';
    public $timestamps = false;

    public function userGroup(): BelongsTo
    {
        return $this->belongsTo(UsersGroup::class, 'group_id', 'group_id');
    }

    public function campaigns() 
    {
        return $this->hasMany(CampaignLive::class, 'user_id', 'id');
    }

    public function assignedTrainings() 
    {
        return $this->hasMany(TrainingAssignedUser::class, 'user_id', 'id');
    }

    public function whatsappCamps()
    {
        return $this->hasMany(WhatsAppCampaignUser::class, 'user_id', 'id');
    }

    public function aiCalls(){
        return $this->hasMany(AiCallCampLive::class, 'user_id', 'id');
    }


}

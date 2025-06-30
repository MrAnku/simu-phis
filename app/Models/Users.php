<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Users extends Model
{
    use HasFactory;

    protected $table = 'users';
    protected $fillable = ['group_id', 'user_name', 'user_email', 'user_company', 'user_job_title', 'whatsapp', 'company_id'];

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

    public function assignedTrainingsNew() 
    {
        return $this->hasMany(TrainingAssignedUser::class, 'user_email', 'user_email');
    }


}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Importing required models
use App\Models\UsersGroup;
use App\Models\CampaignLive;
use App\Models\TrainingAssignedUser;
use App\Models\WhatsAppCampaignUser;
use App\Models\AiCallCampLive;

class BlueCollarEmployee extends Model
{
    use HasFactory;

    protected $table = 'blue_collar_employees';
    public $timestamps = false;

    protected $fillable = [
        'group_id',
        'user_name',
        'user_company',
        'user_job_title',
        'whatsapp',
        'company_id'
    ];

    public function userGroup(): BelongsTo
    {
        return $this->belongsTo(UsersGroup::class, 'group_id', 'group_id');
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(CampaignLive::class, 'user_id', 'id');
    }

    public function assignedTrainings(): HasMany
    {
        return $this->hasMany(TrainingAssignedUser::class, 'user_id', 'id');
    }

    public function whatsappCamps(): HasMany
    {
        return $this->hasMany(WhatsAppCampaignUser::class, 'user_id', 'id');
    }

    public function aiCalls(): HasMany
    {
        return $this->hasMany(AiCallCampLive::class, 'user_id', 'id');
    }
}
